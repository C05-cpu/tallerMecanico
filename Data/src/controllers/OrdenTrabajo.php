<?php
declare(strict_types=1);
namespace App\controllers;

use App\Models\OrdenTrabajoModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OrdenTrabajo
{

    private const ESTADOS = ['Pendiente', 'En Proceso', 'Finalizado', 'Entregado'];

    private ContainerInterface $container;


    public function __construct(ContainerInterface $c)
    {
        $this->container = $c;
    }

    public function read(Request $request, Response $response, array $args): Response
    {
        $this->container->get('eloquent');

        if (isset($args['id'])) {
            $orden = OrdenTrabajoModel::query()
                ->select(['id', 'placa_vehiculo', 'propietario', 'descripcion_falla', 'costo_estimado', 'estado', 'fecha_ingreso'])
                ->find($args['id']);

            if ($orden === null) {
                return $this->json($response, ['error' => 'Orden de trabajo no encontrada.'], 404);
            }

            return $this->json($response, ['data' => $orden->toArray()], 200);
        }

        $ordenes = OrdenTrabajoModel::query()
            ->select(['id', 'placa_vehiculo', 'propietario', 'descripcion_falla', 'costo_estimado', 'estado', 'fecha_ingreso'])
            ->get()
            ->toArray();

        return $this->json($response, ['data' => $ordenes], 200);
    }


    public function create(Request $request, Response $response, array $args): Response
    {
       $this->container->get('eloquent');
       $data = $request->getParsedBody();
       $data = is_array($data) ? $data : [];

        $errors = [];
        $plate = strtoupper(trim((string) ($data['placa_vehiculo'] ?? '')));
        $owner = trim((string) ($data['propietario'] ?? ''));
        $failure = trim((string) ($data['descripcion_falla'] ?? ''));
        $cost = filter_var($data['costo_estimado'] ?? null, FILTER_VALIDATE_FLOAT);
        $status = isset($data['estado']) ? trim((string) $data['estado']) : 'Pendiente';

        if ($plate === '' || mb_strlen($plate) > 10) {
            $errors['placa_vehiculo'] = 'La placa es obligatoria y debe tener máximo 10 caracteres.';
        }
        if ($owner === '' || mb_strlen($owner) > 100) {
            $errors['propietario'] = 'El propietario es obligatorio y debe tener máximo 100 caracteres.';
        }
        if ($failure === '') {
            $errors['descripcion_falla'] = 'La descripción de la falla es obligatoria.';
        }
        if ($cost === false || $cost === null || $cost <= 0) {
            $errors['costo_estimado'] = 'El costo estimado debe ser un número positivo.';
        }
        if (!in_array($status, self::ESTADOS, true)) {
            $errors['estado'] = 'El estado debe ser Pendiente, En Proceso, Finalizado o Entregado.';
        }

        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        if ($this->tieneOrdenActiva($plate)) {
            return $this->json($response, ['error' => 'El vehículo ya tiene una orden de trabajo activa.'], 409);
        }

        $orden = OrdenTrabajoModel::create([
            'placa_vehiculo' => $plate,
            'propietario' => $owner,
            'descripcion_falla' => $failure,
            'costo_estimado' => $cost,
            'estado' => $status,
        ]);

        return $this->json($response, ['data' => OrdenTrabajoModel::find($orden->id)->toArray()], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $this->container->get('eloquent');
        $orden = OrdenTrabajoModel::find($args['id']);

        if ($orden === null) {
            return $this->json($response, ['error' => 'Orden de trabajo no encontrada.'], 404);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        if (isset($data['placa_vehiculo'])) {
            $plate = strtoupper(trim((string) $data['placa_vehiculo']));
            if ($plate === '' || mb_strlen($plate) > 10) {
                return $this->json($response, ['error' => 'Placa inválida.'], 422);
            }
            if ($plate !== $orden->placa_vehiculo && $this->tieneOrdenActiva($plate, $orden->id)) {
                return $this->json($response, ['error' => 'El vehículo ya tiene una orden de trabajo activa.'], 409);
            }
            $orden->placa_vehiculo = $plate;
        }

        if (isset($data['propietario'])) {
            $owner = trim((string) $data['propietario']);
            if ($owner === '' || mb_strlen($owner) > 100) {
                return $this->json($response, ['error' => 'Propietario inválido.'], 422);
            }
            $orden->propietario = $owner;
        }

        if (isset($data['descripcion_falla'])) {
            $failure = trim((string) $data['descripcion_falla']);
            if ($failure === '') {
                return $this->json($response, ['error' => 'Descripción de la falla inválida.'], 422);
            }
            $orden->descripcion_falla = $failure;
        }

        if (isset($data['costo_estimado'])) {
            $cost = filter_var($data['costo_estimado'], FILTER_VALIDATE_FLOAT);
            if ($cost === false || $cost <= 0) {
                return $this->json($response, ['error' => 'Costo estimado inválido.'], 422);
            }
            $orden->costo_estimado = $cost;
        }

        if (isset($data['estado'])) {
            $status = trim((string) $data['estado']);
            if (!in_array($status, self::ESTADOS, true)) {
                return $this->json($response, ['error' => 'Estado inválido.'], 422);
            }
            $orden->estado = $status;
        }

        $orden->save();

        return $this->json($response, ['data' => $orden->toArray()], 200);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $this->container->get('eloquent');
        $orden = OrdenTrabajoModel::find($args['id']);

        if ($orden === null) {
            return $this->json($response, ['error' => 'Orden de trabajo no encontrada.'], 404);
        }

        $orden->delete();

        return $response->withStatus(204);
    }

    private function tieneOrdenActiva(string $plate, ?int $exceptId = null): bool
    {
        $query = OrdenTrabajoModel::query()
            ->where('placa_vehiculo', $plate)
            ->where('estado', '!=', 'Entregado');

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->count() > 0;
    }

    private function json(Response $response, array $payload, int $status): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }

}
