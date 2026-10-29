<?php

declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\ValidatorBuilder as v;

final class OrdenTrabajoValidator
{
    private function reglas(): array
    {
        return [

            'placa_vehiculo' => [
                'regla' => v::stringType()
                    ->notBlank()
                    ->length(v::between(5, 10))
                    ->regex('/^[A-Za-z0-9]{1,3}-?[0-9]{3,6}$/'),
                'mensaje' => 'La placa es obligatoria, debe tener entre 5 y 10 caracteres y un formato valido (ej. BCD123, 659874, CL-2045)',
            ],

            'propietario' => [
                'regla' => v::stringType()
                    ->notBlank()
                    ->length(v::between(6, 100))
                    ->regex('/^[A-Za-zÑñÁÉÍÓÚáéíóú]{2,}(?: [A-Za-zÑñÁÉÍÓÚáéíóú]{2,}){1,3}$/u'),
                'mensaje' => 'El propietario es obligatorio y debe contener entre 6 y 100 caracteres, solo letras y espacios',
            ],

            'descripcion_falla' => [
                'regla' => v::stringType()
                    ->notBlank()
                    ->length(v::between(10, 1000)),
                'mensaje' => 'La descripcion de la falla es obligatoria y debe tener entre 10 y 1000 caracteres',
            ],

            'costo_estimado' => [
                'regla' => v::numericVal()->positive(),
                'mensaje' => 'El costo estimado es obligatorio y debe ser un numero mayor a 0',
            ],

            'estado' => [
                'regla' => v::stringType()
                    ->regex('/^(Pendiente|En Proceso|Finalizado|Entregado)$/'),
                'mensaje' => 'El estado debe ser Pendiente, En Proceso, Finalizado o Entregado',
            ],

        ];
    }

    public function validate(array $data, bool $parcial = false): array
    {
        $errores = [];

        foreach ($this->reglas() as $campo => $config) {

            $valor = $data[$campo] ?? null;
            $regla = $config['regla'];
            $mensaje = $config['mensaje'];

            if ($parcial && !array_key_exists($campo, $data)) {
                continue;
            }

            if ($campo === 'estado' && ($valor === null || $valor === "")) {
                continue;
            }

            if ($regla->validate($valor)->hasFailed()) {
                $errores[$campo] = $mensaje;
            }
        }

        return $errores;
    }
}
