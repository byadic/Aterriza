# Aterriza

## Despliegue con Docker

La imagen usa PHP 8.2 con Apache y `pdo_mysql`. Apache sirve `public/` como raíz del sitio. Define las credenciales de MySQL en el entorno del servicio usando `MYSQLHOST`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD` y `MYSQLPORT`, o proporciona `DATABASE_URL` con una URL `mysql://`.

Los valores `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` y `DB_PORT` tienen prioridad si también están definidos. En local, sin variables, se usan `localhost`, `aterriza`, `root`, contraseña vacía y el puerto `3306`.

## Base de datos

Primero crea la base de datos en el proveedor y luego importa `database/aterriza.sql` dentro de ella. El archivo crea las tablas y agrega datos de ejemplo; ejecútalo una sola vez por base de datos.
