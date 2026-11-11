<?php
/*
 * Funciones de ayuda que usan las vistas.
 */

/** Escapa un texto para mostrarlo de forma segura en HTML (evita XSS). */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

/** Formatea un valor como dinero colombiano: 19600 -> 19.600 */
function dinero($valor): string
{
    return number_format((float) $valor, 0, ",", ".");
}

/** Construye la URL de una ruta del sistema: url("ventas") -> index.php?ruta=ventas */
function url(string $ruta = "inicio", array $parametros = []): string
{
    $parametros = ["ruta" => $ruta] + $parametros;

    return "index.php?" . http_build_query($parametros);
}
