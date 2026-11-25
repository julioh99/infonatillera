# Módulo 07: Gestión de socios y directorio

Padrón de la natillera: datos personales, roles, límites de crédito y orden de la lista.

## Componentes
- Controlador: [`app/Controllers/SocioController.php`](../app/Controllers/SocioController.php)
- Modelo: [`app/Models/Usuario.php`](../app/Models/Usuario.php)
- Vista: `app/Views/admin/socios.php`
- Permisos: Presidente y Secretaria General.

## Funciones
1. **Nuevo socio:** cédula, nombre, teléfono, cumpleaños y rol. **No se escribe clave**: el sistema genera una **clave temporal aleatoria** y la muestra **una sola vez** a quien lo crea (hay que anotarla y entregarla). El socio debe cambiarla en su primer ingreso. Ver módulo 01.
2. **Restablecer clave:** genera otra clave temporal aleatoria con el mismo mecanismo y obliga de nuevo al cambio.
3. **Hoja del socio (expediente):** resumen con total ahorrado (cuotas de $40.000 más extras), intereses frente a la meta de $400.000, deudas (préstamos y actividades), beneficios recibidos, cuotas por reunión, actividades, préstamos con abonos y entregas con firma y foto.
4. **Edición de datos** personales, rol y tope de préstamo personalizado.
5. **Cumpleaños:** widget con los cumpleaños por mes.
6. **Orden de socios:** la columna `natillera_usuarios.orden` ordena todas las listas (llamado a lista, préstamos, actividades, entregas). Se cambia desde el Histórico de años (guardar solo el orden) o al crear el año nuevo.
7. **Activos e inactivos:** al crear un año nuevo se elige quiénes continúan; los demás quedan inactivos sin perder su historia.
8. **Liquidación anual:** vista de cierre por socio; un socio solo ve la suya.

## Seguridad
La Secretaria General no puede asignar el rol Presidente. Las claves nunca se guardan en claro ni se incluyen en las copias del histórico.

---
[Anterior](./06_notificaciones_push.md) | [Índice](./README.md) | [Siguiente: Panel del socio](./08_dashboard_socio.md)
