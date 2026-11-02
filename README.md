# IBG · Sitio web y administración

Aplicación funcional con Bootstrap 5.3.8, PHP 8.2+ y MySQL/MariaDB. El catálogo, contenido, promociones y solicitudes se guardan en la base de datos. phpMyAdmin puede administrar e importar esa base.

## Revisar la versión local

- Sitio: http://127.0.0.1:8097/
- Administración: http://127.0.0.1:8097/admin.php
- Usuario: `admin@ibg.local`. Usa la contraseña que ya configuraste; no se modificó durante esta revisión.
- Si el servidor local está apagado: ejecutar `start-local.ps1` desde PowerShell. Inicia únicamente los servidores de este proyecto en los puertos 3308 y 8097, enlazados a localhost.
- La base de prueba está aislada en `work/mysql`, fuera de los datos de XAMPP existentes. No se modificó la configuración global de XAMPP.

## Organización del catálogo

**Categoría → servicios.** Se cargaron tres categorías: Servicios eléctricos (9), Redes de distribución (5), Climatización (8). Cada servicio pertenece a una categoría. Puedes crear, editar, ordenar y ocultar categorías; para borrar una categoría con servicios primero debes reasignar esos servicios.

**Paquete → uno o varios servicios.** Un paquete puede combinar distintas categorías. Tiene descripción, condiciones, etiqueta, precio opcional y fechas de publicación. Los tres ejemplos están en borrador; no son promociones reales ni tienen precios inventados. Al activar un paquete, verifica sus condiciones y servicios incluidos. Si alguno de sus servicios o categorías queda oculto, el paquete también deja de mostrarse.

**Cotizaciones → cliente y selección.** El carrito guarda temporalmente la selección en el navegador; el formulario guarda la solicitud en MySQL. Cada solicitud recibe una referencia y conserva el nombre y precio de los servicios al momento de solicitarla, aunque después se edite el catálogo. Estados: Nueva, En revisión, Cotizada, Aprobada, Cerrada. Se pueden registrar notas privadas y exportar los resultados filtrados a CSV, Excel y PDF.

## Funciones del panel

1. **Resumen:** métricas y últimas solicitudes.
2. **Cotizaciones:** búsqueda, filtros, datos del cliente, servicios, seguimiento, notas y exportación.
3. **Categorías:** nombre, descripción, imagen, orden y visibilidad.
4. **Servicios:** categoría, nombre, descripción, alcance, imagen, precio referencial, destacado, orden y visibilidad.
5. **Paquetes y ofertas:** servicios incluidos, condiciones, precio referencial, etiqueta, vigencia y publicación.
6. **Textos y contacto:** marca, portada, imágenes de identidad, presentación de la empresa, WhatsApp, correo, cobertura, disponibilidad, pie de página e información sobre uso de datos.
7. **Imágenes:** biblioteca con carga de JPG, PNG o WebP de hasta 5 MB. Las imágenes se seleccionan desde el editor correspondiente.
8. **Mi cuenta:** cambio de contraseña.

Los textos de las fotos se reorganizaron para la web. Los alcances son textos iniciales editables que deben ajustarse a lo que realmente cubre cada servicio. Las piezas gráficas suministradas se conservan como imágenes de apoyo; no se presentan como evidencia de proyectos ejecutados.

## Instalar en un hosting

Requisitos: PHP 8.2 o superior con PDO MySQL, mbstring, fileinfo y sesiones; MySQL 8 o MariaDB compatible; HTTPS; permiso de escritura en `app/storage` y `public/assets/uploads`. Bootstrap y las imágenes están incluidos localmente: no necesitan un CDN para funcionar.

1. Crear una base de datos vacía en el hosting, con codificación `utf8mb4`.
2. En phpMyAdmin seleccionar la base e importar **`database/ibg-import.sql`**. Contiene tablas, las tres categorías, 22 servicios, tres paquetes en borrador y textos. No contiene contraseñas, usuarios administrativos ni consultas de clientes.
3. Subir `app`, `bin`, `database` y `public` manteniendo la estructura. **Configurar la raíz web del dominio para que apunte únicamente a `public`.** No publicar la carpeta completa del proyecto, configuración, scripts CLI o archivos de acceso. En hosting que obliga a usar `public_html`, poner el contenido de `public` en `public_html`, y colocar `app` y demás carpetas como hermanas fuera de `public_html`.
4. Copiar `app/config.example.php` a `app/config.local.php`. Configurar DSN, usuario y contraseña de la base. En HTTPS usar `secure_cookies => true`. No reutilizar las credenciales de la prueba local.
5. Crear el administrador ejecutando `php bin/install.php` desde terminal, con las variables de entorno `IBG_ADMIN_EMAIL` e `IBG_ADMIN_PASSWORD` definidas. Usar una contraseña de al menos 12 caracteres. El instalador no reemplaza un administrador existente y no duplica el contenido importado. Estas variables son solo para la instalación; retirarlas del entorno al terminar.
6. Configurar PHP con `display_errors=Off`, `upload_max_filesize=5M`, `post_max_size=6M` y un directorio temporal de carga escribible. El límite de tamaño de petición del servidor web debe permitir 6 MB.
7. Entrar a `admin.php`, revisar datos de contacto, alcances y política de datos, cargar fotos reales si se dispone de ellas y activar solamente los paquetes aprobados.
8. Realizar una solicitud propia de prueba y confirmar que se recibe en administración. Respaldar la base de datos y `public/assets/uploads` regularmente.

Alternativa: con una base vacía y configuración lista, el mismo `bin/install.php` crea tablas y datos iniciales sin importar SQL. El script solo funciona por consola.

En producción, el usuario de base debe tener solo los permisos necesarios sobre esta base: SELECT, INSERT, UPDATE y DELETE; CREATE y REFERENCES se necesitan para la instalación. Retirar permisos de instalación después de completar el proceso. No ejecutar el servidor integrado de PHP como hosting público.

## Seguridad aplicada

Contraseñas con `password_hash` / `password_verify`, consultas preparadas, autenticación en endpoints privados, protección CSRF, renovación de sesión al iniciar sesión, vencimiento por inactividad, cookies HttpOnly/SameSite, escape de contenido en HTML, CSP, validación de imágenes por MIME y límites de intentos. La exportación CSV neutraliza fórmulas. Precios y disponibilidad se validan nuevamente en servidor al recibir la solicitud.

## Alcance de esta versión

- Carrito de **cotización**, sin cobro ni pasarela de pagos. Los valores opcionales son referenciales; alcance, impuestos y precio final se confirman en el presupuesto.
- El botón de WhatsApp abre un resumen para que la persona lo envíe; no envía mensajes automáticamente.
- Las solicitudes se reciben en el panel. No se ha configurado correo automático ni SMTP.
- No hay reseñas, certificados, cifras de clientes o galería de proyectos inventados.
- No está publicado en un dominio de Internet. La instalación pública requiere hosting PHP/MySQL y sus datos de acceso.
- El panel está pensado para un administrador y un volumen inicial de solicitudes; no incluye roles de equipo ni paginación de grandes volúmenes.

## Verificación

Ver `VERIFICACION.md`. El código PHP pasó revisión de sintaxis y ambos archivos JavaScript pasaron `node --check`. Se probó el flujo de cotización y seguimiento en navegador y las operaciones de base de datos con solicitudes HTTP reales.

Bootstrap: https://getbootstrap.com/docs/5.3/getting-started/introduction/ (licencia MIT incluida en las cabeceras de sus archivos).

## Revisión 3

- Cotización en ventana emergente con selección editable y datos del cliente; guarda la solicitud local y abre WhatsApp con el resumen preparado.
- WhatsApp y correo se configuran en **Textos y contacto**.
- Menú administrativo desplegable en móvil y con desplazamiento en pantallas bajas.
- **Mi cuenta** y **Cerrar sesión** están en el icono del perfil.
- **Ver sitio público** aparece junto a **Actualizar**. Actualizar vuelve a leer los datos guardados en la base; no guarda formularios ni publica cambios.
- Exportación de los resultados filtrados: CSV UTF-8 separado por punto y coma; Excel con logo, dos hojas, filtros y subtotales; PDF con identidad de IBG, resumen y detalle paginado.
- Eliminada la franja superior del sitio público.

## Vista online

https://ibg-soluciones-vista.pearlyprawn129.chatgpt.site

Vista de revisión alojada con Sites. Contiene el catálogo y el formulario que prepara WhatsApp. Este alojamiento no ejecuta PHP/MySQL: no incluye administración ni almacena solicitudes en la base local. Es una copia del catálogo al publicar; los cambios locales no se sincronizan automáticamente. El acceso puede requerir la cuenta propietaria. Para alojar la aplicación completa, sigue las instrucciones de hosting PHP de este documento.

## Imágenes de paquetes

Cada paquete dispone de imagen propia, seleccionable desde la biblioteca o cargable directamente al editar el paquete (JPG/PNG/WebP, hasta 5 MB). La tarjeta pública y el detalle usan esa imagen. Las listas de paquetes muestran seis elementos por página cuando es necesario.

Para actualizar una base existente, ejecutar con una cuenta de administración de MySQL: `ALTER TABLE packages ADD COLUMN image VARCHAR(250) NOT NULL DEFAULT '';`. Ejecutar solo si la columna no existe. Las instalaciones nuevas ya incluyen la columna en schema.sql. La base local ya está actualizada; no se modificaron las promociones existentes.
