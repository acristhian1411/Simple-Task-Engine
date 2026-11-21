# Maius Bug Recorder — instalación manual

La extensión se carga manualmente, sin publicarla en una tienda. La API debe estar desplegada y accesible desde el navegador. Para generar el paquete con el manifest correcto para cada navegador, ejecuta desde la raíz del repo:

```sh
sh extension/package.sh
```

Si despliegas con Docker Compose, reconstruye PHP y Nginx para aplicar los límites de carga de video: `docker compose build back-task back-nginx` y `docker compose up -d back-task back-nginx`.
Si hay otro proxy delante de Nginx, también debe permitir cuerpos de hasta 560 MB.

## 1. Crear un token de extensión en Maius

1. Inicia sesión en la aplicación.
2. Abre **Grabaciones** en el menú.
3. Pulsa **Conectar extensión**, copia el token recién generado y guárdalo temporalmente. Solo se muestra una vez.
4. No compartas el token: permite crear bugs y subir grabaciones como tu usuario.

## 2. Cargar en Chromium

1. Abre `chrome://extensions` (o la página equivalente del navegador Chromium).
2. Activa **Modo de desarrollador**.
3. Pulsa **Cargar descomprimida** y selecciona `extension/dist/chromium/`.
4. Fija el icono de **Maius Bug Recorder** si quieres tenerlo a mano.

## 3. Cargar en Firefox

1. Abre `about:debugging#/runtime/this-firefox`.
2. Pulsa **Cargar complemento temporal**.
3. Selecciona `extension/dist/firefox/manifest.json`.

La carga temporal de Firefox se pierde al cerrar el navegador; vuelve a cargar el manifest para usarla de nuevo.

## 4. Conectar y grabar

1. Abre el menú de la extensión.
2. En **URL de la API**, escribe la base de API, incluyendo `/api` (por ejemplo `https://mi-dominio.example/api`).
3. Pega el token y pulsa **Guardar y verificar conexión**.
4. Abre la página donde ocurre el bug y pulsa **Grabar pestaña actual**.
5. En la pestaña de grabación, elige una pantalla o pestaña y permite compartirla. Desde la barra flotante de la página puedes pausar, detener, silenciar el micrófono o dibujar anotaciones; el micrófono se habilita antes de iniciar.
6. Elige un bug existente o crea uno nuevo; al detener la captura se suben el video, la consola y los registros de red.

La captura comienza después de aceptar compartir pantalla y solo registra el tráfico de la pestaña seleccionada. No cierres la pestaña de grabación antes de que termine la carga.

## Datos y permisos

La extensión solicita permisos para leer actividad de red y ejecutar el capturador en páginas HTTP/HTTPS. Captura URL, método, estado, tiempos, headers no sensibles y cuerpos textuales de `fetch`/XHR cuando el navegador los expone. Omite cuerpos binarios. Elimina headers de autenticación/cookies y redacta campos y parámetros con nombres de contraseña, token, secreto o API key. La redacción es heurística, no una garantía: revisa el contenido antes de compartir una grabación. Los registros se guardan en el almacenamiento privado del servidor.

Revoca tokens desde **Grabaciones** en Maius cuando ya no necesites la extensión. Mientras se graba, los registros se mantienen temporalmente en el almacenamiento local de la extensión; tras subirlos, el video y logs quedan en el storage privado de la app. Las grabaciones pueden contener datos privados de las páginas visitadas; elimina las que no deban conservarse.
