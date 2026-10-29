# Diagnóstico técnico y refactorización — F-01 Registrar venta

## 1. Contexto

- **Proyecto:** Sistema de ventas de un Minimarket de Barrio.
- **Equipo:** [Laura Ximena Osorio - Maria Camila Melo - Sofia Soto Mejia].
- **Repositorio:** https://github.com/mcamilx/minimarket
- **Funcionalidad:** F-01 — Registrar venta.
- **Tecnología:** PHP 8 (monolito web renderizado en servidor), MySQL/MariaDB mediante MySQLi, Bootstrap.
- **Requisitos relacionados:**
  - **BR — Requisito de negocio:** Registrar cada venta del minimarket de forma controlada y trazable para evitar pérdidas e inconsistencias en el inventario.
  - **StR — Requisito del interesado:** El cajero debe poder registrar los productos seleccionados por el cliente sin errores en los códigos ni en los precios.
  - **SyR — Requisito del sistema:** El sistema debe permitir registrar los productos de una compra, validar sus códigos y calcular el total automáticamente.
  - **SWR — Requisito de software:** El módulo de ventas debe validar el código de cada producto contra la base de datos antes de registrarlo en la venta y calcular el total correspondiente.

## 2. Arquitectura real

La funcionalidad F-01 tiene como objetivo registrar los productos seleccionados por el cliente, verificar que existan en el sistema, calcular el valor total de la compra y conservar la información de la venta.

Según `ARQUITECTURA_C4.md`, la aplicación es un monolito PHP sin capas formales: cada página `.php` recibe la solicitud, ejecuta la lógica, consulta la base de datos y genera el HTML.

**Componentes que participan en F-01 (antes de la refactorización):**

| Componente | Responsabilidad real |
|---|---|
| `minimarket/venta.php` | Arma el carrito en la sesión (`$_SESSION["carrito"]`), valida existencia y stock al agregar productos (usa `ProductoRepository::buscarPorId`) y calcula el total que se muestra al cajero. |
| `minimarket/factura.php` | Recibe el formulario de pago y hace **todo** lo demás: valida al cliente, calcula el total, valida el pago, ejecuta la transacción SQL completa y genera la factura en HTML. |
| `minimarket/patrones_diseno.php` | `MetodoPagoStrategy` + `MetodoPagoFactory` (validar pago y calcular cambio) y `ProductoRepository`. |
| `minimarket/conexion.php` | Crea la conexión `mysqli` compartida. |
| `minimarket/auth.php` | Sesión y control de acceso por rol (`requireRole`). |
| Base de datos | Tablas `productos`, `ventas`, `detalle_venta`, `ventas_pendientes`, `detalle_venta_pendiente`. |

**Flujo real:** el cajero agrega productos en `venta.php` → se guardan en la sesión → envía el formulario de pago a `factura.php` → se valida al cliente y el pago → se abre una transacción → se inserta la venta → por cada producto se bloquea la fila (`FOR UPDATE`), se comprueba el stock, se inserta el detalle y se descuenta el inventario → si la venta venía de una pendiente, se elimina → `commit` (o `rollback` si algo falla) → se muestra la factura.

**Pruebas existentes:** ninguna.

## 3. Hallazgos

### H-01 — Concentración de responsabilidades en `factura.php`

- **Tipo:** Riesgo arquitectónico.
- **Ubicación:** `minimarket/factura.php` (versión anterior a la refactorización, commit `22c9229`), líneas 1–530.
- **Evidencia:** un solo archivo de 530 líneas concentra: lectura y validación de los datos del cliente (líneas 17–73), página de error del cliente (75–105), cálculo del total y validación del pago (107–129), transacción con todo el SQL de venta, stock, detalle, inventario y venta pendiente (131–274), limpieza de la sesión (276–279) y la vista HTML de la factura (282–530).
- **Observación:** el archivo actúa a la vez como controlador, validador, servicio, repositorio y vista. Cualquier cambio en una de esas partes obliga a modificar el mismo archivo y no es posible probar la lógica de registro sin un navegador y una base de datos.
- **Criterio afectado:** Principio de responsabilidad única (SRP) y separación de capas.
- **Impacto y riesgo:** dificulta el mantenimiento y las pruebas; un error al editar la vista puede afectar la transacción y viceversa.
- **Acción propuesta:** extraer la lógica a una capa de servicios y repositorio, dejando `factura.php` como controlador y vista.
- **Verificación:** pruebas automatizadas del servicio y pruebas manuales antes/después del registro de ventas.
- **Estado:** Implementado y verificado.

### H-02 — Duplicación de la lógica de cálculo del total

- **Tipo:** Deuda técnica.
- **Ubicación y evidencia:** la fórmula `precio * cantidad` está repetida en:
  - `minimarket/factura.php` (versión anterior): línea 115 (total), línea 168 (subtotal del detalle) y líneas 385–387 (subtotal en la vista).
  - `minimarket/venta.php`: línea 70 (total de la venta pendiente), línea 228 (total mostrado al cajero) y líneas 460–461 (subtotal en la tabla).
- **Observación:** si cambian las reglas de precio (por ejemplo, descuentos), habría que modificar seis lugares; olvidar uno produciría diferencias entre el total mostrado, el registrado y el usado para validar el pago.
- **Criterio afectado:** principio DRY, mantenibilidad y consistencia de los cálculos.
- **Impacto y riesgo:** inconsistencias en el importe de la venta.
- **Acción propuesta:** centralizar el cálculo en un único componente y reutilizarlo.
- **Verificación:** pruebas automatizadas del cálculo con distintos precios y cantidades.
- **Estado:** Implementado parcialmente (centralizado en `CalculadoraVenta` para el registro de venta; `venta.php` queda pendiente).

### H-03 — Ausencia de pruebas para registrar ventas

- **Tipo:** Riesgo técnico.
- **Ubicación y evidencia:** el repositorio no contenía ninguna carpeta ni archivo de pruebas automatizadas antes de esta intervención.
- **Observación:** ningún escenario de F-01 (venta válida, pago insuficiente, producto inexistente, inventario insuficiente) estaba protegido por pruebas.
- **Criterio afectado:** verificabilidad, confiabilidad y mantenibilidad.
- **Impacto y riesgo:** un cambio podría permitir registrar productos inexistentes, calcular un total incorrecto o dejar el inventario inconsistente sin que se detecte.
- **Acción propuesta:** crear pruebas automatizadas para los escenarios principales y de error.
- **Verificación:** ejecutar las pruebas y comprobar que todas pasan.
- **Estado:** Implementado (se creó `pruebas/test_registrar_venta.php`).

## 4. Priorización

Escala: 1 = bajo, 2 = medio, 3 = alto.

| ID | Impacto (1–3) | Riesgo (1–3) | Esfuerzo (1–3) | Justificación |
|---|---:|---:|---:|---|
| H-01 | 3 | 2 | 2 | Afecta a toda la funcionalidad y bloquea la posibilidad de probarla. Separar responsabilidades tiene un esfuerzo moderado. |
| H-02 | 3 | 3 | 1 | Puede causar inconsistencias en el importe. Centralizar el cálculo es sencillo. |
| H-03 | 3 | 2 | 2 | Sin pruebas no hay forma de comprobar que una refactorización conserva el comportamiento. |

**Prioridad seleccionada:** H-01. Al separar responsabilidades se crea un componente único de cálculo (resuelve H-02 en el registro de venta) y las piezas quedan aisladas para poder probarlas (resuelve H-03).

## 5. Intervención

- **Hallazgo seleccionado:** H-01 — Concentración de responsabilidades en `factura.php`.
- **Diseño antes:** `factura.php` actuaba a la vez como controlador, validador, servicio, repositorio (SQL directo) y vista.
- **Diseño después:** se creó `minimarket/servicios_venta.php` con cuatro clases, una por responsabilidad:

| Clase | Responsabilidad |
|---|---|
| `CalculadoraVenta` | Calcular subtotal y total (regla de negocio única). |
| `ValidadorCliente` | Leer y validar los datos opcionales del cliente. |
| `VentaRepository` | Único lugar con SQL: insertar venta, bloquear stock, insertar detalle, descontar inventario, eliminar venta pendiente. |
| `RegistrarVentaService` | Coordinar el caso de uso: validar el pago (Strategy existente) y ejecutar la transacción con commit/rollback. |

`factura.php` quedó como controlador y vista: recibe la solicitud, delega en `RegistrarVentaService` y muestra la factura o el error.

```
Antes:  factura.php ──► (validación + cálculo + pago + SQL + HTML)

Después: factura.php (controlador + vista)
            ├─► ValidadorCliente
            └─► RegistrarVentaService
                    ├─► CalculadoraVenta
                    ├─► MetodoPagoFactory / MetodoPagoStrategy
                    └─► VentaRepository ──► MySQL/MariaDB
```

- **Archivos afectados:** `minimarket/factura.php` (modificado), `minimarket/servicios_venta.php` (nuevo), `pruebas/test_registrar_venta.php` (nuevo).
- **Comportamiento conservado:** mismos mensajes de error, mismos códigos HTTP 422, misma transacción con `FOR UPDATE` y rollback, mismo HTML de la factura. No se agregaron funcionalidades ni se corrigieron defectos.

## 6. Verificación

**Pruebas automatizadas:** `php pruebas/test_registrar_venta.php` → 22 aprobadas, 0 fallidas (ver `evidencias/resultado_pruebas.txt`). Cubren: cálculo de subtotal y total, validación del cliente, venta válida en efectivo y con tarjeta, pago insuficiente, método de pago inválido, producto inexistente, inventario insuficiente y venta pendiente eliminada, comprobando commit o rollback en cada caso.

**Pruebas manuales antes/después** (Apache + MariaDB en Ubuntu; carrito: 2 Arroz a $3.500 + 3 Leche a $4.200 = $19.600; inventario inicial: 10 de cada producto):

| Prueba | Antes | Después |
|---|---|---|
| A. Efectivo, paga 20000 | Factura, cambio $400 | Factura, cambio $400 |
| B. Tarjeta, paga 19600 | Factura, cambio $0 | Factura, cambio $0 |
| C. Efectivo, paga 1000 | Mensaje de pago inferior al total | Mensaje de pago inferior al total |
| Inventario final | Arroz 6, Leche 4 | Arroz 6, Leche 4 |

Evidencias: `evidencias/`: prueba A → `Factura efectivo antes.png` / `Factura efectivo despues.png`; prueba B → `Factura tarjeta antes.png` / `Factura tarjeta despues.png`; prueba C → `valor inferior antes.png` / `valor inferior despues.png`; inventario → `inventario antes.png` / `inventario despues.png`.

**Limitaciones:** las pruebas automatizadas usan un repositorio falso en memoria; no ejecutan SQL real contra la base de datos. El SQL se verificó con las pruebas manuales.

## 7. Cierre

- **Lecciones aprendidas:** el diagnóstico técnico permite identificar oportunidades de mejora en la organización del código. La refactorización debe realizarse a partir de evidencias, con un objetivo concreto y verificando que el comportamiento del sistema se conserve.
- **Relación con los requisitos y el proceso de desarrollo:** el diagnóstico y la refactorización se relacionan con los requisitos BR, StR, SyR y SWR de F-01 porque buscan facilitar un registro de ventas correcto, trazable y mantenible. La verificación mediante pruebas permite comprobar que los cambios no afectan las condiciones funcionales esperadas.
- **Deuda técnica pendiente:**
  - Reutilizar `CalculadoraVenta` en `venta.php` (líneas 70, 228 y 460–461).
  - Las credenciales de la base de datos están escritas en `conexion.php` dentro de un repositorio público.
- **Pregunta de cierre:** la escritura de pruebas automatizadas desde el inicio y la revisión de código antes de integrar cambios habrían detectado que `factura.php` acumulaba responsabilidades y que no existía forma de verificar el registro de ventas.

## 8. Git

| Commit | Hash |
|---|---|
| docs: registrar diagnostico tecnico | [`aeea09d`](https://github.com/mcamilx/minimarket/commit/aeea09d) |
| refactor: separar responsabilidades de registrar venta | [`34e8003`](https://github.com/mcamilx/minimarket/commit/34e8003) |
| docs: documentar verificacion de refactorizacion | (ver historial del repositorio) |
