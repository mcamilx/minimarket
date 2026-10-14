<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function currentUser()
{
    $usuario = $_SESSION["usuario"] ?? null;

    if (is_string($usuario)) {
        $rol = strtolower(trim($usuario));
        $nombres = [
            "admin" => "Administrador",
            "administrador" => "Administrador",
            "cajero" => "Cajero"
        ];

        if (isset($nombres[$rol])) {
            $usuario = [
                "nombre" => $nombres[$rol],
                "rol" => $rol === "administrador" ? "admin" : $rol
            ];
            $_SESSION["usuario"] = $usuario;
        }
    }

    if (
        !is_array($usuario) ||
        !isset($usuario["nombre"], $usuario["rol"]) ||
        !in_array($usuario["rol"], ["admin", "cajero"], true)
    ) {
        unset($_SESSION["usuario"]);
        return null;
    }

    return $usuario;
}

function currentRole()
{
    $usuario = currentUser();
    return $usuario["rol"] ?? null;
}

function requireRole(array $roles)
{
    if (currentUser() === null) {
        header("Location: index.php");
        exit;
    }

    if (!in_array(currentRole(), $roles, true)) {
        http_response_code(403);
        exit("No tienes permisos para acceder a esta sección.");
    }
}
