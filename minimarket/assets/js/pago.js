/*
 * Ayudas del formulario de pago (solo en el navegador; el servidor vuelve a validar todo).
 */
document.addEventListener("DOMContentLoaded", function () {
    const metodoPago = document.getElementById("metodo_pago");
    const montoPagado = document.getElementById("monto_pagado");
    const formularioPago = document.getElementById("formulario_pago");

    if (!metodoPago || !montoPagado || !formularioPago) {
        return;
    }

    metodoPago.addEventListener("change", function () {
        if (metodoPago.value === "Tarjeta") {
            montoPagado.placeholder = "Ingrese el valor pagado con tarjeta";
        } else if (metodoPago.value === "Efectivo") {
            montoPagado.placeholder = "Ingrese el efectivo recibido";
        } else {
            montoPagado.placeholder = "";
        }
    });

    formularioPago.addEventListener("submit", function (event) {
        const monto = parseFloat(montoPagado.value);

        if (metodoPago.value === "") {
            event.preventDefault();
            alert("Seleccione un método de pago.");
            return;
        }

        if (isNaN(monto) || monto <= 0) {
            event.preventDefault();
            alert("Ingrese un valor de pago válido.");
        }
    });
});
