# Flujo de Datos y Visualización: Sala Estrella Manager

Este documento detalla el ciclo de vida de los datos, desde su almacenamiento en el Back-end hasta su representación visual en el Front-end (DOM), y el cierre de la transacción mediante el sistema de reservas y WooCommerce.

---

## 1. Capa de Almacenamiento (Back-end: MySQL)
Los datos residen en el servidor bajo tres estructuras principales:
*   **Custom Post Types (CPT):** `pelicula` almacena la información estática (título, sinopsis, póster, trailer).
*   **Tablas Personalizadas:**
    *   `wp_cnes_salas`: Define dimensiones, sedes y el `layout` (un JSON con la posición de cada asiento).
    *   `wp_cnes_funciones`: Horarios, precios, película asociada y estado de la venta.
    *   `wp_cnes_reservas`: Registro dinámico de asientos bloqueados o pagados con *timestamps* de expiración.

---

## 2. Capa de Descubrimiento (Theme: Cartelera)
El usuario ve las películas disponibles mediante un proceso asíncrono:
1.  **Petición API:** `cartelera.js` solicita datos a `/wp-json/wp/v2/pelicula?_embed`.
2.  **Filtrado en Cliente:** El script filtra el JSON según la sede seleccionada en el Theme (Punta Arenas o Puerto Natales).
3.  **Visualización (DOM):**
    *   Se recorre el array de películas.
    *   Se generan **Template Literals** de HTML para cada "card".
    *   Se inyectan en el contenedor `<div id="carteleraGrid"></div>` usando `.innerHTML`.

---

## 3. Preparación de la Selección (PHP: Shortcode & Localización)
Cuando el usuario elige un horario, el sistema prepara el mapa de asientos:
1.  **Controlador:** El shortcode `[cnes_seleccion_asientos]` activa `render_seleccion_asientos`.
2.  **Extracción de Datos:** El plugin consulta la base de datos usando el `funcion_id` de la URL.
3.  **Inyección (Localización):** Los datos (precios, layout de sala, estado de asientos) se "inyectan" en el navegador mediante `wp_localize_script`, creando el objeto global `cnesAsientos`.
4.  **Estructura Base:** El servidor entrega un HTML vacío (esqueleto) con contenedores como `#mapaAsientos`.

---

## 4. Visualización Interactiva (Front-end: DOM & AJAX)
El archivo `cnes-seleccion-asientos.js` construye la interfaz interactiva:
1.  **Construcción del Mapa:**
    *   Se aplica **CSS Grid** al contenedor `#mapaAsientos` basado en las columnas de la sala.
    *   Se crean nodos de tipo `<button>` para cada asiento usando `document.createElement`.
    *   Se asignan clases CSS (`.asiento--vip`, `.ocupado`, `.seleccionado`) y atributos `data-id`.
2.  **Interacción (Ciclo AJAX):**
    *   **Clic en Asiento:** Se envía una petición `cnes_bloquear_asiento`. El Back-end valida la disponibilidad y responde.
    *   **Actualización del DOM:** Si el Back-end confirma, se añade la clase `.seleccionado` al botón y se actualiza el panel `#resumenSeleccion`.
3.  **Sincronización (Polling):** Cada 30 segundos, una llamada a `cnes_verificar_disponibilidad` actualiza el estado de los asientos en el DOM por si otros usuarios compraron entradas.

---

## 5. Cierre de Transacción (WooCommerce Integration)
El flujo finaliza integrando la reserva con el sistema de ventas:
1.  **Traspaso al Carrito:** Al hacer clic en "Continuar", el plugin añade un "Producto Genérico de Entrada" al carrito de WooCommerce.
2.  **Metadatos y Precios:**
    *   Se adjuntan los IDs de los asientos y la función como metadatos del ítem del carrito.
    *   El hook `woocommerce_before_calculate_totals` de PHP intercepta el carrito y cambia el precio del producto por el precio real (Normal/VIP) definido en la función.
3.  **Confirmación de Pago:**
    *   Tras el pago, el hook `woocommerce_order_status_completed` cambia el estado de los asientos en `wp_cnes_reservas` de `seleccionado` a `pagado`.
    *   El asiento ahora aparecerá como `.ocupado` (gris/deshabilitado) para cualquier otro usuario.

---

## Resumen Visual del Flujo
```text
[ DB: MySQL ] <--> [ PHP: Plugin Logic ] <--> [ API / Localized JSON ]
                         |                           |
                         |                    [ JS: Theme/DOM Construction ]
                         |                           |
                  [ WooCommerce ] <----------- [ Usuario: Clic/Reserva ]
```
