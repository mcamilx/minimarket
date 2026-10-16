# Diagramas de arquitectura del Minimarket

Este documento presenta la arquitectura implementada en el sistema usando los
niveles de C4 y una vista adicional de secuencia. Los diagramas están escritos
en Mermaid; se pueden visualizar en editores compatibles con Mermaid, como
GitHub y extensiones de Markdown para VS Code.

## Alcance y perfiles

El sistema tiene dos perfiles seleccionables desde la pantalla de inicio:

- **Administrador:** gestiona productos y consulta inventario e historial.
- **Cajero:** registra ventas, guarda y retoma ventas pendientes, y consulta
  inventario e historial.

La selección de perfil se almacena en una sesión PHP. No se solicitan
credenciales; por eso, la selección de rol es una separación funcional y no
constituye autenticación adecuada para un despliegue público.

## 1. C4 — Diagrama de contexto

El contexto muestra quién usa el sistema y el servicio externo con el que
persiste información.

```mermaid
C4Context
    title Contexto del sistema Minimarket

    Person(admin, "Administrador", "Gestiona productos y consulta inventario e historial.")
    Person(cajero, "Cajero", "Registra ventas, las pone en espera y las retoma.")

    System(minimarket, "Sistema Minimarket", "Aplicación web para productos, inventario, ventas y facturación.")
    System_Ext(mysql, "MySQL", "Almacena productos, ventas completadas, detalles y ventas pendientes.")

    Rel(admin, minimarket, "Gestiona productos y consulta información")
    Rel(cajero, minimarket, "Registra y consulta ventas")
    Rel(minimarket, mysql, "Lee y persiste datos", "MySQLi / SQL")
```

## 2. C4 — Diagrama de contenedores

La aplicación es un monolito web renderizado en el servidor. El navegador envía
formularios y recibe HTML generado por PHP; PHP consulta y actualiza MySQL.
Bootstrap se carga desde un CDN para la presentación visual.

```mermaid
C4Container
    title Contenedores del sistema Minimarket

    Person(usuario, "Administrador o cajero", "Usa el sistema desde un navegador.")

    System_Boundary(sistema, "Sistema Minimarket") {
        Container(navegador, "Navegador web", "Chrome, Edge u otro", "Muestra las páginas HTML y envía formularios HTTP.")
        Container(aplicacion, "Aplicación web", "PHP 8.2, HTML, Bootstrap y JavaScript", "Procesa solicitudes, sesiones, permisos, reglas de negocio y vistas.")
        ContainerDb(baseDatos, "Base de datos", "MySQL / minimarket_db", "Guarda productos, ventas completadas y ventas en espera.")
    }

    System_Ext(cdn, "CDN de Bootstrap", "Entrega la hoja de estilos Bootstrap 5.3.3.")

    Rel(usuario, navegador, "Interactúa")
    Rel(navegador, aplicacion, "Solicitudes y formularios", "HTTP")
    Rel(aplicacion, navegador, "Páginas renderizadas", "HTML")
    Rel(aplicacion, baseDatos, "Consulta y actualiza datos", "MySQLi")
    Rel(navegador, cdn, "Descarga estilos", "HTTPS")
```

## 3. C4 — Diagrama de componentes

Los componentes corresponden a las páginas y archivos PHP que organizan la
aplicación. `auth.php`, `navbar.php` y `conexion.php` son compartidos por las
páginas que los requieren.

```mermaid
C4Component
    title Componentes de la aplicación web Minimarket

    Container_Boundary(app, "Aplicación web PHP") {
        Component(inicio, "index.php", "PHP", "Selección de perfil, panel y estadísticas.")
        Component(auth, "auth.php", "PHP / sesiones", "Obtiene el usuario actual y restringe acceso por rol.")
        Component(nav, "navbar.php", "PHP / HTML", "Navegación común, perfil, regreso al panel y cierre de sesión.")
        Component(productos, "productos.php", "PHP", "Registro y consulta de productos; solo administrador.")
        Component(inventario, "inventario.php", "PHP", "Consulta y búsqueda de productos y existencias.")
        Component(venta, "venta.php", "PHP / sesión", "Carrito, guardado y recuperación de ventas pendientes.")
        Component(factura, "factura.php", "PHP", "Validación del cobro, registro transaccional y emisión de factura.")
        Component(historial, "historial.php", "PHP", "Consulta de ventas finalizadas y pendientes; filtro del administrador.")
        Component(conexion, "conexion.php", "MySQLi", "Conexión a minimarket_db.")
        Component(js, "script.js", "JavaScript", "Ayuda de interfaz para la selección de pago y el formulario.")
    }

    ContainerDb(db, "MySQL / minimarket_db", "MySQL", "Persistencia del sistema.")

    Rel(inicio, auth, "Usa sesión y rol")
    Rel(inicio, conexion, "Consulta estadísticas")
    Rel(productos, auth, "Exige rol administrador")
    Rel(inventario, auth, "Exige perfil válido")
    Rel(venta, auth, "Exige perfil válido")
    Rel(factura, auth, "Exige perfil válido")
    Rel(historial, auth, "Exige perfil válido")
    Rel(inicio, nav, "Incluye navegación al entrar")
    Rel(productos, nav, "Incluye navegación")
    Rel(inventario, nav, "Incluye navegación")
    Rel(venta, nav, "Incluye navegación")
    Rel(factura, nav, "Incluye navegación")
    Rel(historial, nav, "Incluye navegación")
    Rel(productos, conexion, "Lee y registra productos")
    Rel(inventario, conexion, "Lee productos")
    Rel(venta, conexion, "Lee productos y persiste pendientes")
    Rel(factura, conexion, "Registra venta, detalles y existencias")
    Rel(historial, conexion, "Consulta ventas")
    Rel(venta, js, "Carga ayuda de interfaz")
    Rel(conexion, db, "Ejecuta SQL", "MySQLi")
```

### Tablas principales

| Tabla | Uso |
|---|---|
| `productos` | Catálogo y stock disponible. |
| `ventas` | Ventas cobradas, método de pago, cliente y estado. |
| `detalle_venta` | Líneas de productos incluidas en cada venta cobrada. |
| `ventas_pendientes` | Encabezado y total de una venta guardada en espera. |
| `detalle_venta_pendiente` | Productos, precios y cantidades de la venta en espera. |

Las ventas en espera se almacenan separadas de las ventas cobradas. No reducen
el stock ni reservan un número de factura; al cobrarlas se crea el registro
normal en `ventas` y se elimina la versión pendiente dentro de la misma
transacción.

## 4. Vista de código y dependencias

Las páginas siguen siendo PHP procedural, pero el flujo de ventas ahora usa
clases pequeñas para aplicar tres patrones de diseño: Repository, Strategy y
Factory. El siguiente diagrama muestra estos elementos implementados y sus
dependencias reales.

```mermaid
flowchart LR
    subgraph PHP["Archivos PHP"]
        Index["index.php<br/>inicio, perfiles y panel"]
        Auth["auth.php<br/>sesión y autorización"]
        Nav["navbar.php<br/>navegación compartida"]
        Products["productos.php<br/>gestión de productos"]
        Inventory["inventario.php<br/>consulta de stock"]
        Sale["venta.php<br/>carrito y ventas pendientes"]
        Invoice["factura.php<br/>cobro y factura"]
        History["historial.php<br/>historial y búsqueda"]
        Connection["conexion.php<br/>conexión MySQLi"]
        Patterns["patrones_diseno.php<br/>patrones aplicados al flujo"]
        ProductRepo["ProductoRepository<br/>búsqueda y reposición de stock"]
        PaymentStrategy["MetodoPagoStrategy<br/>validación y cambio"]
        CashPayment["PagoEfectivoStrategy"]
        CardPayment["PagoTarjetaStrategy"]
        PaymentFactory["MetodoPagoFactory<br/>creación de estrategia"]
    end

    subgraph Data["MySQL / minimarket_db"]
        ProductTable[("productos")]
        SalesTable[("ventas")]
        DetailTable[("detalle_venta")]
        PendingTable[("ventas_pendientes")]
        PendingDetailTable[("detalle_venta_pendiente")]
    end

    Index --> Auth
    Products --> Auth
    Inventory --> Auth
    Sale --> Auth
    Invoice --> Auth
    History --> Auth

    Index -. incluye .-> Nav
    Products -. incluye .-> Nav
    Inventory -. incluye .-> Nav
    Sale -. incluye .-> Nav
    Invoice -. incluye .-> Nav
    History -. incluye .-> Nav

    Index --> Connection
    Products --> Connection
    Inventory --> Connection
    Sale --> Connection
    Invoice --> Connection
    History --> Connection

    Connection --> ProductTable
    Connection --> SalesTable
    Connection --> DetailTable
    Connection --> PendingTable
    Connection --> PendingDetailTable

    Products --> ProductTable
    Inventory --> ProductTable
    Sale --> ProductTable
    Sale --> Patterns
    Sale --> ProductRepo
    ProductRepo --> ProductTable
    Patterns --> ProductRepo
    Patterns --> PaymentStrategy
    Patterns --> PaymentFactory
    Invoice --> Patterns
    Invoice --> PaymentFactory
    PaymentFactory --> CashPayment
    PaymentFactory --> CardPayment
    CashPayment -. implementa .-> PaymentStrategy
    CardPayment -. implementa .-> PaymentStrategy
    Sale --> PendingTable
    Sale --> PendingDetailTable
    Invoice --> ProductTable
    Invoice --> SalesTable
    Invoice --> DetailTable
    Invoice --> PendingTable
    History --> SalesTable
    History --> PendingTable
```

### Patrones de diseño implementados

1. **Repository — `ProductoRepository`:** concentra las consultas usadas por
   el flujo de ventas para buscar un producto por identificador y listar los
   productos disponibles. También ofrece la operación de reponer un producto
   agotado por su código, usada por la gestión de productos. `venta.php` y
   `productos.php` solicitan estas operaciones al repositorio en vez de
   construir esas consultas directamente. Esto permite separar el acceso a
   productos del procesamiento de las pantallas.
2. **Strategy — `MetodoPagoStrategy`:** define un contrato común para validar
   un pago y calcular el cambio. `PagoEfectivoStrategy` calcula el cambio en
   efectivo; `PagoTarjetaStrategy` devuelve cero. Cada estrategia rechaza un
   monto inferior al total.
3. **Factory — `MetodoPagoFactory`:** elige y construye la estrategia
   correspondiente a `"Efectivo"` o `"Tarjeta"`. `factura.php` trabaja con el
   contrato común, sin tener que decidir cómo se calcula el cambio para cada
   método.

Estas implementaciones conservan los métodos de pago, las reglas de cobro y el
flujo de inventario ya existentes. Se encuentran en
[`minimarket/patrones_diseno.php`](./minimarket/patrones_diseno.php).

## 5. C4+1 — Diagrama de secuencia

La vista dinámica siguiente documenta el escenario principal solicitado:
guardar una venta en espera, atender otra operación, recuperar la primera y
cobrarla. Los mensajes al navegador representan respuestas HTTP renderizadas;
las escrituras de finalización se agrupan en una transacción.

```mermaid
sequenceDiagram
    autonumber
    actor Cajero
    participant Navegador
    participant Venta as venta.php
    participant Sesion as Sesión PHP
    participant MySQL
    participant Factura as factura.php

    Cajero->>Navegador: Agrega productos y cantidades
    Navegador->>Venta: POST agregar productos
    Venta->>MySQL: Consulta productos y precios
    MySQL-->>Venta: Datos actuales del catálogo
    Venta->>Sesion: Guarda líneas en carrito
    Venta-->>Navegador: Muestra carrito y total

    Cajero->>Navegador: Guarda venta en espera
    Navegador->>Venta: POST guardar_pendiente
    Venta->>MySQL: INSERT ventas_pendientes
    Venta->>MySQL: INSERT detalle_venta_pendiente por línea
    Note over Venta,MySQL: No se modifica productos.cantidad ni se inserta en ventas
    Venta->>Sesion: Vacía carrito activo
    Venta-->>Navegador: Confirma venta pendiente

    Cajero->>Navegador: Selecciona otra venta y luego retoma la anterior
    Navegador->>Venta: POST retomar_pendiente con ID P-n
    Venta->>MySQL: Lee encabezado y detalle pendiente
    MySQL-->>Venta: Total y líneas guardadas
    Venta->>Sesion: Restaura carrito y venta_pendiente_id
    Venta-->>Navegador: Muestra líneas y total restaurados

    Cajero->>Navegador: Confirma pago y datos opcionales del cliente
    Navegador->>Factura: POST cobro
    Factura->>Factura: Valida pago y datos del cliente
    Factura->>MySQL: Inicia transacción
    Factura->>MySQL: Bloquea y verifica stock con SELECT FOR UPDATE
    MySQL-->>Factura: Existencias actuales
    Factura->>MySQL: INSERT venta APROBADA y detalle_venta
    Factura->>MySQL: UPDATE productos para descontar cantidades
    Factura->>MySQL: DELETE venta pendiente original
    Factura->>MySQL: COMMIT
    Factura-->>Navegador: Factura con estado APROBADA
```

## Archivos relacionados

- Esquema de base de datos: [`minimarket/database.sql`](./minimarket/database.sql).
- Migración de la versión 0.2: [`minimarket/migracion_v0_2.sql`](./minimarket/migracion_v0_2.sql).
- Perfiles y sesiones: [`minimarket/auth.php`](./minimarket/auth.php).
- Ventas y ventas pendientes: [`minimarket/venta.php`](./minimarket/venta.php).
- Cobro y factura: [`minimarket/factura.php`](./minimarket/factura.php).
- Consulta del historial: [`minimarket/historial.php`](./minimarket/historial.php).
