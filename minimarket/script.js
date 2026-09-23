document.addEventListener("DOMContentLoaded", function () {

    const metodoPago = document.getElementById("metodo_pago");
    const montoPagado = document.getElementById("monto_pagado");

    if (metodoPago && montoPagado) {

        metodoPago.addEventListener("change", function () {

            if (metodoPago.value === "Tarjeta") {
                montoPagado.placeholder = "Ingrese el valor pagado con tarjeta";
            } else if (metodoPago.value === "Efectivo") {
                montoPagado.placeholder = "Ingrese el efectivo recibido";
            } else {
                montoPagado.placeholder = "";
            }

        });

    }


    const formularioPago = montoPagado
        ? montoPagado.closest("form")
        : null;

    if (formularioPago) {

        formularioPago.addEventListener("submit", function (event) {

            const metodo = metodoPago.value;
            const monto = parseFloat(montoPagado.value);

            if (metodo === "") {

                event.preventDefault();

                alert("Seleccione un método de pago.");

                return;
            }

            if (isNaN(monto) || monto <= 0) {

                event.preventDefault();

                alert("Ingrese un valor de pago válido.");

            }

        });

    }

});