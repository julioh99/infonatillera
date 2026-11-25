# Pruebas locales (sin tocar la base remota)

La base remota `skylined_pruebas` NO se usa para probar cambios. Hay copias locales en el MySQL de Laragon
(usuario `root`, sin clave, host 127.0.0.1) creadas desde el respaldo del 2026-11-25:
`natillera_test` (general) y una por modulo: `nt_seguridad`, `nt_historico`, `nt_endurecimiento`, `nt_fondos`, `nt_push`, `nt_higiene`.

Las variables de entorno reales tienen prioridad sobre el `.env` (ver `Database::loadEnv`), asi que basta con:

    # scripts o pruebas por consola
    DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=nt_fondos DB_USERNAME=root DB_PASSWORD= \
      /c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe tests/mi_prueba.php

    # servidor web de prueba (puerto propio) y peticiones con curl
    DB_HOST=127.0.0.1 DB_DATABASE=nt_fondos DB_USERNAME=root DB_PASSWORD= \
      /c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe -S 127.0.0.1:8104 -t public public/index.php &

Cliente MySQL: `/c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql -h 127.0.0.1 -u root <base>`.
Para entrar a la web de prueba crea en TU copia local un usuario con clave conocida (por ejemplo actualiza `password_hash` de un Presidente con `password_hash('Prueba123', PASSWORD_DEFAULT)` y `password_changed = 1`).
Si algo se corrompe, reimporta el respaldo: `mysql ... <base> < J:/www/infonatillera_backups/2026-11-25_antes_rediseno/db_skylined_pruebas_2026-11-25.sql`.
