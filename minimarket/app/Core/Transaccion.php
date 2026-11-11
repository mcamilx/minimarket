<?php

namespace App\Core;

use PDO;
use Throwable;

/*
 * Ejecuta un bloque de código dentro de una transacción de base de datos.
 * Si todo sale bien hace COMMIT; si ocurre cualquier error hace ROLLBACK.
 * Los servicios la reciben por constructor y no necesitan conocer PDO.
 */
class Transaccion
{
    public function __construct(private PDO $conexion)
    {
    }

    public function ejecutar(callable $operacion): mixed
    {
        $this->conexion->beginTransaction();

        try {
            $resultado = $operacion();
            $this->conexion->commit();

            return $resultado;
        } catch (Throwable $error) {
            $this->conexion->rollBack();
            throw $error;
        }
    }
}
