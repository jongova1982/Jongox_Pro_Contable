# JONGOX PRO

Aplicación web PHP y MySQL para gestionar productos, clientes, proveedores,
compras, ventas e inventario.

## Requisitos

- PHP 8 o posterior con PDO MySQL habilitado
- MySQL o MariaDB
- Apache (por ejemplo, XAMPP)

## Instalación local con XAMPP

1. Copia el proyecto en `C:\xampp\htdocs\jongox_pro_release`.
2. Inicia Apache y MySQL desde el panel de XAMPP.
3. Entra en `http://localhost/phpmyadmin` y crea la base `jongox_contable`.
4. Selecciona la base e importa `database.sql`.
5. Abre `http://localhost/jongox_pro_release/`.

La configuración local predeterminada está en `config.php`: host `localhost`,
puerto `3306`, usuario `root` y contraseña vacía, como suele venir XAMPP.
Si tu instalación usa otros datos, configura las variables de entorno
`JONGOX_DB_HOST`, `JONGOX_DB_PORT`, `JONGOX_DB_NAME`, `JONGOX_DB_USER` y
`JONGOX_DB_PASS` antes de iniciar PHP.

## Estructura

- `index.php`: interfaz y vistas de la aplicación.
- `action.php`: operaciones de alta y movimientos de inventario.
- `status.php`: indicador JSON de conexión a la base de datos.
- `config.php`: configuración local y conexión PDO.
- `database.sql`: esquema inicial y datos de configuración mínimos.
- `assets/`: hojas de estilo, JavaScript e imágenes de marca.

## Publicación

Sube el contenido del repositorio a GitHub. No agregues contraseñas,
exportaciones con datos reales ni copias de seguridad. En otro entorno,
configura las variables de entorno de base de datos y ejecuta `database.sql`
en una base vacía. Para servir la aplicación públicamente, usa HTTPS y una
cuenta de base de datos con permisos limitados.
