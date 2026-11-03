> Versión anterior del panel. Para instalar y validar la tienda actual, sigue **LEEME_TIENDA.md**.

# Guitarixta — panel de administrador

## Instalar en Laragon
1. Respalda tu carpeta anterior y tu base de datos.
2. Copia la carpeta EslavaProyecto completa a C:\laragon\www\EslavaProyecto (reemplaza los archivos del proyecto anterior).
3. Inicia Apache y MySQL.
4. En HeidiSQL/phpMyAdmin importa **actualizar_panel.sql** sobre `proyectoeslava`. Agrega `empleados`, `bitacora` y `productos`; no borra tus cuentas ni inventario existentes. Si ya tienes tablas con esos nombres y otra estructura, compara sus columnas antes de importar: CREATE TABLE IF NOT EXISTS no modifica tablas existentes.
5. Opcional: importa **productos_ejemplo.sql**. Incluye las tres fotos adjuntas como datos binarios reales. Stock inicial 0, editable; no es inventario real. No duplica ejemplos si conservas sus nombres.
6. Revisa `includes/config.php`. Se conservó tu puerto MySQL **4400**, base `proyectoeslava`, usuario `root` y contraseña vacía. Ajusta a tu instalación. El puerto de Apache (por ejemplo 90) es diferente del de MySQL.
7. Abre `http://localhost/EslavaProyecto/` (o `http://localhost:90/EslavaProyecto/` si Apache usa 90). Inicia sesión con tu administrador existente de `registrarAdmins`.

Si comienzas con una base vacía, importa en este orden: `base_datos_opcional.sql`, `base_datos_admins.sql`, `actualizar_panel.sql`, y opcionalmente `productos_ejemplo.sql`. No se crea un administrador ni una contraseña universal. Para crear el primero, sigue las instrucciones SQL del archivo `base_datos_admins.sql`, usando un hash de PHP.

## Menú desplegable
- **Usuarios y empleados:** primera pantalla; conserva usuarios, cuentas antiguas y administradores con sus opciones de edición/eliminación. El botón de empleados abre su propia sección para crear, editar y eliminar personal.
- **Bitácora:** ID, fecha, hora, intento; se agregan usuario/correo y evento para saber quién entró y salió. Filtro por exitoso/fallido, 50 eventos por página.
- **Productos:** ID, producto, stock, imagen visible, fecha y hora. Crear producto; editar y eliminar como acciones de interfaz (no son columnas en MySQL).

Los empleados usan correo y contraseña para entrar a la bienvenida. No tienen permisos de administrador. Solo cuentas de `registrarAdmins` acceden al panel y a sus imágenes.

## Bitácora
Registra entradas exitosas, contraseñas incorrectas, correos inexistentes, peticiones de acceso inválidas recibidas por PHP y salidas usando **Cerrar sesión**. Nunca guarda las contraseñas. Los intentos bloqueados por validación del navegador no llegan al servidor. Al registrar públicamente un usuario, el inicio automático también queda registrado.
El cierre de una pestaña, apagar el equipo o perder internet no equivale a una salida confirmada. Los eventos anteriores a esta actualización no se pueden recuperar. Se usa horario de Ciudad de México. Si MySQL no está disponible, no es posible guardar eventos; el cierre de sesión sigue funcionando y muestra un aviso.

## Imagen binaria
`productos.imagen` es **MEDIUMBLOB**: contiene los bytes del JPG, PNG o WebP, no una ruta ni URL. `imagen_tipo` guarda su tipo MIME para que el navegador la interprete correctamente.
Pulsa una foto: se abre la imagen, tamaño y los primeros 512 bytes representados como grupos de 8 ceros/unos. Un botón descarga TODOS los bits a TXT. Son los bytes del archivo codificado, no una lista directa de píxeles; cada grupo de 8 bits puede convertirse de nuevo a un byte para reconstruir el archivo original.
La fecha y hora son las de creación y se conservan al editar. Las imágenes tienen límite de 3 MB. En php.ini usa `upload_max_filesize=4M`, `post_max_size=8M`; habilita PDO MySQL y fileinfo. En MySQL `max_allowed_packet` debe ser al menos 8 MB. Reinicia los servicios tras cambios.

## Comprobación en tu equipo
1. Entra como administrador: abre el menú y sus tres opciones.
2. Crea un empleado, edita sus datos; sal e inicia sesión con ese empleado. No debe poder abrir admin.php.
3. Prueba un correo inexistente y contraseña incorrecta de una cuenta existente; entra como administrador y revisa ambos eventos fallidos.
4. Cierra sesión y vuelve a entrar: revisa entrada y salida en bitácora.
5. Crea una guitarra con foto, cambia stock sin seleccionar otra foto y verifica que se conserva. Cambia la foto, abre su binario y descarga el TXT. Elimina el producto de prueba.
6. Prueba stock negativo, archivo que no sea imagen y accesos al panel sin sesión: deben rechazarse.

## Validación de entrega
Los 24 archivos PHP pasaron el análisis sintáctico (Tree-sitter PHP). Se revisaron la estructura, consultas parametrizadas, controles de administrador y CSRF, rutas internas y fidelidad de los bytes de las tres imágenes en el SQL. Este entorno no cuenta con PHP/MySQL ejecutables; la prueba integral debe realizarse en tu Laragon con tu base real. No se modificó ninguna base remota.
