# Sala Estrella Manager

Sala Estrella Manager es un plugin especializado para WordPress diseñado para la gestión integral de salas de cine, cartelería, programación de funciones y reserva de asientos con integración profunda en WooCommerce.

## Características Principales

- **Gestión de Películas**: Custom Post Type (pelicula) con taxonomías para Géneros, Clasificaciones y Sedes.
- **Editor de Salas**: CRUD de salas con un editor de disposición (layout) basado en JSON para definir filas, columnas y tipos de asientos.
- **Programación de Funciones**: Sistema de gestión de horarios con validación automática de solapamiento de funciones por sala.
- **Sistema de Reservas**: 
    - Selección de asientos en tiempo real mediante AJAX.
    - Bloqueo temporal de asientos (10 minutos) para evitar duplicidad.
    - Limpieza automática de reservas expiradas mediante tareas Cron de WordPress.
- **Integración con WooCommerce**: 
    - Conversión de asientos seleccionados en ítems del carrito.
    - Inyección de metadatos (sala, función, fila, número) en el pedido.
    - Generación automática de códigos de tickets únicos tras el pago.

## Requisitos

- **WordPress**: 6.0 o superior.
- **PHP**: 7.4 o superior.
- **WooCommerce**: Debe estar instalado y activo.
- **MySQL**: 5.7 o superior.

## Instalación

1. Clona este repositorio en tu directorio de plugins:
   ```bash
   git clone https://github.com/tu-usuario/sala-estrella-manager.git wp-content/plugins/sala-estrella-manager
   ```
2. Activa el plugin desde el panel de administración de WordPress.
3. El plugin creará automáticamente las tablas necesarias (wp_cnes_salas, wp_cnes_asientos, wp_cnes_funciones, wp_cnes_reservas).

## Arquitectura del Código

El plugin sigue un patrón de diseño orientado a objetos (OOP) basado en ganchos (hooks):

- **CNES_Plugin**: El orquestador central que inicializa todos los módulos.
- **includes/**: Contiene la lógica central, incluyendo el cargador de hooks (CNES_Loader), la gestión de AJAX y la lógica de WooCommerce.
- **admin/**: Módulos específicos para el panel de administración (gestión de salas y funciones).
- **public/**: Lógica para el frontend y shortcodes.
- **templates/**: Archivos de plantilla para la visualización pública.

## Flujo de Reserva

1. **Selección**: El usuario utiliza el shortcode [cnes_seleccion_asientos] en una página.
2. **Bloqueo**: Al hacer clic en un asiento, se crea un registro seleccionado en la base de datos con una expiración programada.
3. **Carrito**: Los asientos se añaden al carrito de WooCommerce con los precios correspondientes.
4. **Pago**: Una vez completado el pedido, el estado cambia a pagado y se confirma la reserva definitiva.

## Convenciones de Desarrollo

- **Prefijo**: Todas las funciones, clases y tablas utilizan el prefijo cnes_ o CNES_.
- **Seguridad**: Uso estricto de $wpdb->prepare(), verificación de nonces y validación de capacidades (manage_options).
- **Traducciones**: El plugin está localizado en español utilizando las funciones de internacionalización de WordPress.

---
Desarrollado para Cine Sala Estrella.
