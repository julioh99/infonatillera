# Documentación de InfoNatillera

Guías por módulo con las reglas vigentes del sistema (MySQL, ciclo diciembre a noviembre, cuota $55.000 / $60.000 / $65.000 con ronda y rifa). Despliegue, pruebas y respaldos están en el [README principal](../README.md).

1. [Autenticación, roles y modo dual](./01_autenticacion_y_roles.md): permisos, claves temporales, CSRF y límite de intentos.
2. [Llamado a lista y autopréstamos](./02_llamado_a_lista.md): cuotas, desglose ahorro/ronda/rifa, regla de 24 horas.
3. [Préstamos e intereses](./03_prestamos_e_intereses.md): interés progresivo, abonos, validaciones.
4. [Actividades (tamales)](./04_actividades_tamales.md)
5. [Programación de reuniones, ronda y rifa](./05_programacion_reuniones.md)
6. [Notificaciones push y recordatorios](./06_notificaciones_push.md)
7. [Gestión de socios](./07_gestion_socios.md)
8. [Panel del socio](./08_dashboard_socio.md)
9. [Entregas, cierres, fondos y histórico de años](./09_entregas_rondas_rifas.md)
10. [Identidad visual 2027](./10_identidad_visual_logo.md)

Otros: [Pruebas locales](./PRUEBAS_LOCALES.md), [permisos del llamado a lista](./permisos_roles_modulo_llamado_lista.md).

Operación rápida:
- Desplegar: copiar archivos, `php scripts/migrate.php --status`, `--dry-run`, `php scripts/migrate.php`, revisar `.env`, DocumentRoot en `public/`.
- Probar: `DB_DATABASE=nt_higiene DB_USERNAME=root DB_PASSWORD= php tests/run.php`.
- Regenerar `db.sql` tras nuevas migraciones: `php scripts/migrate.php` y `php scripts/generar_db_sql.php` sobre una base local.

[Volver al README](../README.md)
