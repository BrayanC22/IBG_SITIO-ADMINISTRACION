# Verificación de la primera versión

Pruebas realizadas el 1 de noviembre de 2026 sobre PHP 8.2.12 y MariaDB 10.4.32 local.

## Resultado

- 21 comprobaciones HTTP de catálogo, autenticación, CSRF, edición, relaciones entre categorías/servicios/paquetes, vigencia, cantidades inválidas, consentimiento, idempotencia de solicitudes, precios canónicos, seguimiento, conservación del historial y cierre de sesión: correctas.
- Carga autenticada de JPG y persistencia en biblioteca: correcta.
- Edición de contenido desde administración y lectura desde el catálogo público: correcta.
- Navegador: filtro Climatización muestra 8 servicios, carrito añade el servicio, formulario guarda la solicitud, administrador la ve, actualiza estado/notas y guarda un servicio: correcto.
- Se verificaron la portada y el panel visualmente, incluyendo una vista estrecha del sitio. No se observó desbordamiento horizontal de la página en las dimensiones revisadas.
- Revisión de sintaxis PHP y JavaScript: correcta.

Las categorías, servicios, paquetes y solicitudes sintéticas de prueba fueron retirados. No se enviaron mensajes de WhatsApp ni correos.

## Límites

No se ha probado en un hosting público, con correo SMTP, con una pasarela de pagos ni bajo carga elevada. No se realizó una auditoría externa de seguridad o accesibilidad. No se ha validado la compatibilidad con todos los navegadores y dispositivos.

## Revisiones 2 y 3

- Modal de cotización, cantidades y navegación a WhatsApp verificados sin enviar mensajes.
- Destinos configurables e idempotencia verificados; valores originales restaurados.
- Menú de perfil, menú móvil y ancho de 320/375 px verificados en navegador.
- Excel descargado desde el panel; PDF generado sin errores de consola.
- Exportaciones sintéticas: dos hojas Excel, acentos y subtotal 136.50; PDF de cuatro páginas con texto largo revisado visualmente; CSV UTF-8.
- Vista estática online desplegada correctamente; PHP/MySQL permanecen en local.

## Rediseño del catálogo y cotización

El sitio público carga únicamente Bootstrap y premium.css consolidado. site.css queda para administración, sin modificar su diseño.

Comprobado en navegador: seis servicios seleccionados; apertura de detalle y transición de carrito a formulario; quitar una selección y actualizar el contador; buscar y limpiar; paginación. A 320, 375 y 1280 px no se detectó desbordamiento horizontal en las vistas probadas ni contenido desbordado dentro de las filas del formulario. Consola sin errores durante las interacciones. Sintaxis PHP y JavaScript correcta. No se envió ninguna solicitud ni mensaje durante esta revisión.

Respaldo de los tres archivos previos en work/redesign, fuera del proyecto distribuible. Esta revisión es local; no actualiza la copia estática online ni GitHub.
