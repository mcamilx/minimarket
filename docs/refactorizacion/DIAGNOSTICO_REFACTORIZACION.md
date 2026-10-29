# Diagnóstico técnico y refactorización — F-01 Registrar venta

## 1. Contexto

- **Proyecto:** Sistema de ventas de un Minimarket de Barrio.
- **Equipo:** [Laura Ximena Osorio - Maria Camila Melo - Sofia Soto Mejia].
- **Funcionalidad:** F-01 — Registrar venta.
- **Requisitos relacionados:**
  - **BR — Requisito de negocio:** Registrar cada venta del minimarket de forma controlada y trazable para evitar pérdidas e inconsistencias en el inventario.
  - **StR — Requisito del interesado:** El cajero debe poder registrar los productos seleccionados por el cliente sin errores en los códigos ni en los precios.
  - **SyR — Requisito del sistema:** El sistema debe permitir registrar los productos de una compra, validar sus códigos y calcular el total automáticamente.
  - **SWR — Requisito de software:** El módulo de ventas debe validar el código de cada producto contra la base de datos antes de registrarlo en la venta y calcular el total correspondiente.

## 2. Arquitectura real

La funcionalidad F-01 tiene como objetivo registrar los productos seleccionados por el cliente, verificar que existan en el sistema, calcular el valor total de la compra y conservar la información de la venta.

- **Controladores:** Su responsabilidad esperada es recibir los datos, validar la solicitud y delegar el procesamiento a la capa correspondiente.
- **Servicios:** Debe coordinar la validación de productos, el cálculo del total y el registro de la operación.
- **Dominio y entidades:** Estas clases deben contener la información necesaria para representar los productos incluidos en una compra y sus respectivos valores.
- **Repositorios y persistencia:** Su responsabilidad es consultar los productos y almacenar la información de las ventas de acuerdo con la tecnología utilizada.
- **Pruebas existentes:** Se debe comprobar si existen pruebas para validar los códigos de los productos, calcular correctamente el total y registrar una venta.

**Recorrido esperado de la información:** el cajero ingresa los productos seleccionados por el cliente; el sistema recibe los códigos y las cantidades; consulta los productos almacenados; valida su existencia; calcula el total de la compra y registra la venta con sus detalles.

Este recorrido debe compararse con la implementación real para identificar posibles problemas de diseño o funcionamiento.

## 3. Hallazgos

### H-01 — Posible concentración de responsabilidades en el controlador

- **Tipo:** Oportunidad de mejora / Riesgo arquitectónico.
- **Evidencia:** Pendiente de inspección del código fuente. Se debe comprobar si el controlador realiza directamente la validación de productos, el cálculo del total y el registro de la venta.
- **Observación:** Si el controlador concentra varias reglas del negocio, puede asumir responsabilidades que deberían estar separadas en los servicios o en el dominio.
- **Criterio afectado:** Principio de responsabilidad única y separación de responsabilidades.
- **Impacto y riesgo:** La concentración de lógica puede dificultar el mantenimiento, las pruebas y la reutilización de las reglas de registro de ventas.
- **Acción propuesta:** Si se confirma el problema, trasladar la lógica de negocio correspondiente a un servicio encargado del registro de ventas, manteniendo en el controlador la recepción y respuesta de las solicitudes.
- **Verificación:** Revisar el controlador antes y después del cambio y ejecutar pruebas que comprueben que el registro de una venta conserva su comportamiento.
- **Estado:** Pendiente de verificación.

### H-02 — Posible duplicación de la lógica de cálculo del total

- **Tipo:** Deuda técnica / Oportunidad de mejora.
- **Evidencia:** Pendiente de inspección. Se debe buscar si el cálculo del total se encuentra repetido en diferentes métodos o componentes.
- **Observación:** Si existen varias implementaciones del cálculo, podrían producir resultados distintos cuando se modifiquen las reglas de precios, cantidades o descuentos.
- **Criterio afectado:** Principio de reutilización, mantenibilidad y consistencia de los cálculos.
- **Impacto y riesgo:** La duplicación puede ocasionar diferencias entre el total mostrado al cajero, el valor registrado y el importe utilizado para validar el pago.
- **Acción propuesta:** Si se confirma la duplicación, centralizar el cálculo del total en un único componente responsable y reutilizarlo desde los demás componentes.
- **Verificación:** Ejecutar pruebas con diferentes cantidades y precios, comparar los resultados y comprobar que el total registrado coincide con la suma de los valores de los productos.
- **Estado:** Pendiente de verificación.

### H-03 — Posible ausencia o insuficiencia de pruebas para registrar ventas

- **Tipo:** Riesgo técnico / Oportunidad de mejora.
- **Evidencia:** Pendiente de inspección. Se debe revisar si existen pruebas automatizadas para el registro de una venta, la validación de códigos y el cálculo del total.
- **Observación:** Si estos escenarios no están cubiertos por pruebas, los cambios en la funcionalidad podrían introducir errores sin que se detecten oportunamente.
- **Criterio afectado:** Verificabilidad, confiabilidad y mantenibilidad del software.
- **Impacto y riesgo:** Un error podría permitir registrar productos inexistentes, calcular un total incorrecto o almacenar información inconsistente.
- **Acción propuesta:** Si se identifica una cobertura insuficiente, crear o ampliar las pruebas automatizadas para los escenarios principales y los casos de error del registro de ventas.
- **Verificación:** Ejecutar las pruebas y comprobar que una venta válida se registra correctamente y que los productos inexistentes son rechazados.
- **Estado:** Pendiente de verificación.

## 4. Priorización

La siguiente tabla presenta una priorización inicial ilustrativa. Las puntuaciones deben ajustarse según los hallazgos que se confirmen y el esfuerzo real de implementación.

Escala: 1 = bajo, 2 = medio, 3 = alto.

| ID | Impacto (1–3) | Riesgo (1–3) | Esfuerzo (1–3) | Justificación |
|---|---:|---:|---:|---|
| H-01 | 2 | 2 | 2 | Separar las responsabilidades puede facilitar el mantenimiento y las pruebas. El esfuerzo depende de la cantidad de lógica que tenga el controlador. |
| H-02 | 3 | 3 | 2 | Si se confirma que el cálculo está duplicado, podrían producirse inconsistencias en el total de la venta. Centralizarlo tendría un esfuerzo moderado. |
| H-03 | 3 | 2 | 2 | Las pruebas ayudan a detectar errores en una operación esencial del sistema. El esfuerzo depende de la infraestructura de pruebas existente. |

**Prioridad propuesta:** H-02, siempre que la inspección confirme que el cálculo del total está duplicado. Se propone atenderlo primero porque la consistencia del importe es esencial para el registro de una venta. 

## 5. Intervención

- **Hallazgo seleccionado:** H-02 — Posible duplicación de la lógica de cálculo del total, sujeto a confirmación.
- **Motivo de selección:** El cálculo correcto del total es fundamental para registrar las ventas y mantener la coherencia entre los productos seleccionados, el valor cobrado y la información almacenada.
- **Diseño antes del cambio:** [Describir la implementación encontrada]. Si se confirma la duplicación, documentar los componentes que realizan el cálculo y cómo se comunican entre sí.
- **Diseño después del cambio:** Centralizar el cálculo en un único componente responsable y hacer que los demás componentes utilicen su resultado, evitando mantener varias implementaciones de la misma regla.
- **Comportamiento que debe conservarse:** La funcionalidad debe seguir permitiendo registrar los productos seleccionados, validar sus códigos, calcular correctamente el total y almacenar la venta. La refactorización no debe cambiar el comportamiento esperado ni introducir funcionalidades nuevas.

## 6. Cierre

- **Lecciones aprendidas:** El diagnóstico técnico permite identificar oportunidades de mejora en la organización del código. La refactorización debe realizarse a partir de evidencias, con un objetivo concreto y verificando que el comportamiento del sistema se conserve.
- **Relación con los requisitos y el proceso de desarrollo:** El diagnóstico y la refactorización se relacionan con los requisitos BR, StR, SyR y SWR de F-01 porque buscan facilitar un registro de ventas correcto, trazable y mantenible. La verificación mediante pruebas permite comprobar que los cambios no afectan las condiciones funcionales esperadas.

