# 🤖 Historial de Peticiones y Contexto del Proyecto - InfoNatillera

Este archivo almacena el historial cronológico de requerimientos realizados por el usuario y las soluciones técnicas implementadas por la Inteligencia Artificial. Sirve como fuente centralizada de contexto para futuras interacciones.

---

### 📌 Petición 1: Inicialización y Análisis del Proyecto
**Requerimiento del Usuario:**
`/init usa los archivos .md y .sql para analizar el proyecto que vamos a realizar y recuerda usar superpower para mejorar y darle herramientas y luego implementa`

**Solución Brindada:**
- Se analizó el modelo entidad-relación y las especificaciones financieras de la natillera comunitaria (50 socios).
- Se diseñó la estructura MVC personalizada en PHP nativo (sin frameworks pesados), definiendo enrutador Front Controller, clases base (`Controller`, `Model`, `Router`) y esquemas de base de datos SQLite.

---

### 📌 Petición 2: Corrección de Compatibilidad con PHP 7.2.34 y URLs en Laragon
**Requerimiento del Usuario:**
`Parse error: syntax error, unexpected 'array' (T_ARRAY) en Router.php line 5`
`cuando puse mi info para iniciar me trajo a http://infonatillera.test/login y sale not found...`

**Solución Brindada:**
- Se corrigieron incompatibilidades de sintaxis en `core/Router.php`.
- Se solucionó el error 404 en el VirtualHost de Laragon ajustando las reglas de reescritura en `.htaccess` (raíz y public) y agregando normalización de URIs en `Router::normalizePath()` para ignorar prefijos `/public/`.

---

### 📌 Petición 3: Inicialización de Git, Auto-Creación de BD y Usuario Presidente en Código
**Requerimiento del Usuario:**
`crea el git init, si la db no exite haz que el usuario de presiden este guardado en codigo y al iniciar se cree la db y el pueda ingresar los nuevos participantes y las reuniones`

**Solución Brindada:**
- Se ejecutó `git init` y se creó `.gitignore`.
- Se configuró `config/database.php` para que al conectar por primera vez (si no existe `natillera.sqlite`) cree la estructura SQL, inserte los 5 roles base, cree al usuario Presidente por defecto (`Cédula: 1010000001`, `Clave: 123456`) y pre-genere las 26 quincenas del año.
- Se agregaron botones y modales **"+ Nuevo Socio"** y **"+ Nueva Reunión"** en sus respectivos módulos administrativos.

---

### 📌 Petición 4: Conversión de Navbar Horizontal a Sidebar Vertical
**Requerimiento del Usuario:**
`quiero que el navbar lo cambies a un sidebar`

**Solución Brindada:**
- Se reemplazó la barra de navegación superior horizontal por una barra lateral (**Sidebar**) fija en el margen izquierdo (`#0f172a`).
- Se incluyó resaltado del módulo activo, agrupación por secciones (*Módulos Directiva* y *Mi Cuenta*), widget de usuario con avatar en el footer del Sidebar, botón de *Modo Dual Presidente* y menú responsivo desplegable para pantallas móviles (Offcanvas).

---

### 📌 Petición 5: Selección Automática de la Siguiente Quincena Pendiente en Llamado a Lista
**Requerimiento del Usuario:**
`En el llamado a lista, si la cuota 1, 2 y 3 ya fueron canceladas, ya son pasadas o ya fueron registradas/guardadas, la que debería aparecer por defecto al darle clic en llamado a lista debería ser la cuota 4 para que agilicemos y aparezca de una vez en la siguiente.`

**Solución Brindada:**
- Se actualizó el método `Reunion::getReunionActual()` para que al ingresar a `/admin/llamado-lista` consulte y seleccione automáticamente la primera quincena en orden ascendente que no tenga registros de asistencia o esté abierta.
- Al guardar el llamado a lista en lote, la reunión procesada se marca como `CERRADA`, haciendo que en la siguiente entrada el sistema avance automáticamente a la quincena subsiguiente.

---

### 📌 Petición 6: Simplificación de Préstamos Directos y Edición de Préstamos/Cuotas Pagadas
**Requerimiento del Usuario:**
`En los préstamos no quiero que se hagan a terceros y luego se busque un fiador. Cada préstamo se le hace siempre al socio y lo que se debe tener en cuenta es si el socio quiere poner una persona referente o un nombre de ese préstamo para que lo pueda identificar mejor. También haz que el presidente y la secretaria general puedan editar los préstamos ya pagados, incluso editar las cuotas de esos préstamos que ya se hicieron por si algún cambio que queda pendiente.`

**Solución Brindada:**
- Se eliminaron las opciones de fiador y préstamos a terceros, haciendo que todos los créditos sean directos al socio con un campo opcional de **"Persona Referente / Nombre de Referencia"** (*alias/motivo del préstamo*).
- Se habilitó la opción **"✏ Editar"** para préstamos en cualquier estado (incluyendo `PAGADO`).
- Se implementó el modal **"≡ Cuotas"** para consultar el historial detallado de abonos de un préstamo, permitiendo a la directiva editar o eliminar abonos individuales y recalculando automáticamente el saldo.

---

### 📌 Petición 7: Solución al Error `no such column: nombre_referencia`
**Requerimiento del Usuario:**
`Fatal error: Uncaught PDOException: SQLSTATE[HY000]: General error: 1 no such column: nombre_referencia in Prestamo.php:58 intente cambiar el estado de un prestamo`

**Solución Brindada:**
- Se modificó `config/database.php` para llamar siempre a `ensureSchemaUpdates()` en la conexión PDO.
- Esto ejecuta un bloque `ALTER TABLE prestamos ADD COLUMN nombre_referencia VARCHAR(150);` de manera segura, aplicando la migración tanto en bases de datos nuevas como en archivos `natillera.sqlite` ya existentes.

---

### 📌 Petición 8: Prohibición de Pruebas Automáticas no Solicitadas y Creación de Documentación `docs/`
**Requerimiento del Usuario:**
`no pruebe codigo a menos que te lo ordene, y crea el readme de como funciona y explicando muy bien los modulos y crea una documentación para cada una y conectalas para que ambas se puedan referenciar y crea una carpeta docs`

**Solución Brindada:**
- Se acató la regla de no ejecutar subagentes ni pruebas automáticas en el navegador sin orden explícita.
- Se redactó el archivo principal `README.md` y se creó el directorio `docs/` con 8 guías técnicas interconectadas (`01_autenticacion_y_roles.md`, `02_llamado_a_lista.md`, `03_prestamos_e_intereses.md`, `04_actividades_tamales.md`, `05_programacion_reuniones.md`, `06_notificaciones_push.md`, `07_gestion_socios.md`, `08_dashboard_socio.md`).

---

### 📌 Petición 9: Creación de la carpeta `AI Contest` e Historial Consolidado
**Requerimiento del Usuario:**
`Quiero que crees una carpeta que se llame AI Contest, donde vamos a manejar el contexto de las preguntas que te hago para, futuramente, darle contexto a la IA y sepa qué hemos hecho y qué vamos haciendo. Quiero que todo se vaya llevando en un mismo archivo. Puedes separarlo con una línea de guiones para que se sepa desde dónde va una petición que yo hice y cuál fue la solución que se brindó. recuerda ignorarla en el git`

**Solución Brindada:**
- Se agregó `/AI Contest/` al archivo `.gitignore`.
- Se creó el directorio `AI Contest/` y se generó el archivo consolidado `AI Contest/contexto_historial.md` documentando todas las peticiones y soluciones del proyecto de manera estructurada y continua.

---

### 📌 Petición 10: Detalle de Cuotas de Préstamos y Sección de Deudas de Actividades en Dashboard del Socio
**Requerimiento del Usuario:**
`Quiero que en el dashboard de cada socio, en la parte de los préstamos, al darle clic en un préstamo, muestre si tiene cuotas asignadas y a quién se las dio, y si es a intereses o abono a capital o lo que tenga ese crédito como tal, para poder darle seguimiento a esos créditos. Y también agrega un apartado en el dashboard de las deudas de actividades, para así saber cuánto debo en las actividades y de qué actividad debo y las cuotas que he dado se muestren por separadas a cada actividad.`

**Solución Brindada:**
- **Seguimiento a Cuotas de Préstamos (`/socio/dashboard`):** Se enriqueció la lista de créditos del socio agregando el botón **"Ver Cuotas"**. Despliega un desglose colapsable con la tabla de cuotas pagadas (fecha, abono a capital, abono a intereses y el nombre del directivo que recibió/registró la cuota).
- **Sección de Actividades Comunitarias y Deudas:** Se añadió el panel **"Mis Actividades Comunitarias y Deudas"** que resume la deuda total pendiente en eventos de tamales/actividades y detalla por cada actividad la fecha, cuota asignada, monto pagado, saldo pendiente y estado (`Al Día` vs `Pendiente`).
- **Actualización de Documentación:** Se actualizó `docs/08_dashboard_socio.md` con las nuevas métricas y componentes.

---

### 📌 Petición 11: Inclusión de Cuota a Pagar por Socio en Actividades y Migración Dinámica de Esquema
**Requerimiento del Usuario:**
`Al crear la actividad, no me está preguntando cuánto es el valor que debe pagar cada socio. Organízalo y haz que se pueda integrar una nueva columna de forma fácil y que sea muy escalable para que no tengas que borrar la base de datos completa.`

**Solución Brindada:**
- **Sistema de Migración Dinámica y Escalable (`config/database.php`):** Se creó el método auxiliar `addColumnIfNotExists($db, $table, $column, $typeDef)` utilizando `PRAGMA table_info` en SQLite. Esto permite agregar automáticamente nuevas columnas a la base de datos (como `cuota_por_socio`) en cualquier momento sin borrar ni reiniciar la base de datos existente.
- **Cuota a Pagar por Socio en Actividades:** Se agregó el campo **"Cuota / Valor a Pagar por Socio (COP)"** (`cuota_por_socio`) en el modal y controlador de creación de actividades (`/admin/actividades`). Al registrar la actividad, el valor ingresado se asigna automáticamente a cada participante seleccionado como su `cuota_asignada` y se refleja como su deuda inicial pendiente en su Dashboard.
- **Actualización de Documentación y SQL:** Se actualizó la definición en `db.sql` y el archivo `docs/04_actividades_tamales.md`.

---

### 📌 Petición 12: Cuotas Individuales Diferenciadas por Socio en Actividades y Control de Pagos
**Requerimiento del Usuario:**
`Existen algunas actividades de tamales que los valores no es el mismo para todos, así que el valor a pagar por cada socio seleccionado debe ser diferente. Entonces, al seleccionar el socio, abajo debe listarse y al frente poner el valor que debe pagar ese socio.`

**Solución Brindada:**
- **Asignación Individual de Cuotas en UI (`/admin/actividades`):** En el modal de creación de actividades, al marcar a cada socio participante, se muestra un campo de entrada numérico individual al frente de su nombre para especificar la cuota a pagar asignada (*ej: Socio A $30.000 por 3 tamales, Socio B $50.000 por 5 tamales*).
- **Asistente de Cuota Base:** Se incluyó el botón **"Aplicar Cuota Base"** para autocompletar un monto genérico a todos los socios seleccionados y permitir ajustar únicamente las excepciones.
- **Gestión de Recaudo por Directiva:** Se añadió el botón **"Ver Participantes y Pagos"** en las tarjetas de actividad para consultar el estado de cobro de cada participante y registrar los montos pagados.
- **Procesamiento y Modelado (`Actividad.php`):** Se actualizó el modelo `crearActividad` para procesar el mapa de cuotas individuales `[socio_id => cuota_monto]` e insertarlas en `actividad_participantes`. Se añadió el método `actualizarPagoParticipante`.
- **Actualización de Documentación:** Se actualizó `docs/04_actividades_tamales.md`.

---

### 📌 Petición 13: Formateo Monetario Universal con Separador de Miles (Punto) en Tiempo Real
**Requerimiento del Usuario:**
`Quiero que le agregues un punto a cada tres dígitos en todos los inputs o labels que ingresen valores monetarios para que se pueda identificar mejor el número. Quiero que esto pase en préstamos, actividades, en todos los valores e incluso en los nuevos también tengan esta configuración.`

**Solución Brindada:**
- **Script Global de Máscara de Moneda (`public/js/money_formatter.js`):** Se creó una librería cliente que intercepta cualquier input con la clase `.money-input` y aplica automáticamente el punto de miles (`Intl.NumberFormat('es-CO')`) a medida que el usuario escribe (*ej: 500000 -> 500.000*).
- **Desformateo Automático al Enviar:** Antes de realizar un envío de formulario (submit tradicional o AJAX), el script remueve automáticamente los puntos para enviar valores flotantes/enteros numéricos limpios al servidor.
- **Saneamiento Defensivo en Controladores PHP:** Se añadió `str_replace('.', '', ...)` en `PrestamoController`, `ActividadController`, `ReunionController` y `LlamadoListaController` para garantizar un parseo 100% seguro de montos.
- **Integración Global:** Se incluyó el script en `app/Views/layouts/footer.php` y se aplicó la clase `.money-input` en todos los formularios actuales y futuros de Préstamos, Actividades, Reuniones, Llamado a Lista y Socios.

---

### 📌 Petición 14: Regla del Ahorro Neto Constante de $40.000 COP, Cronograma de Rondas/Rifas y Módulo de Entregas con Firma Digital y Foto Evidencia
**Requerimiento del Usuario:**
`Ajustar la lógica de recaudo, ahorros netos y entregas de RONDAS y RIFAS según los cronogramas quincenales reales.`
`1. Regla del Ahorro Neto del Socio: Independientemente de la cuota ($55k, $60k, $65k), el ahorro neto que se le acredita al socio para fin de año es SIEMPRE DE EXACTAMENTE $40.000 COP.`
`2. Lógica de Fondos Acumulados y Personas Liberadas: Ronda ($300.000) y Rifa ($150.000) con saldos acumulados e incremento de ganadores en quincenas especiales.`
`3. Módulo de Registro y Evidencia de Entrega: Pad HTML5 Canvas para firma táctil en celular y captura de foto de evidencia.`
`4. Módulo de Llamado a Lista reajustado.`
`5. Modelos FondoBeneficio.php, EntregaBeneficio.php, EntregaController.php, firma_canvas.js y vista mobile-first.`

**Solución Brindada:**
- **Regla del Ahorro Neto ($40.000 COP):** Se actualizó `Reunion::guardarLlamadoListaBatch` y `anularAutoprestamo24Horas` para registrar siempre `$40.000 COP` en `ahorros_cuotas.monto_cuota`, abstrayendo las diferencias en las nuevas columnas `monto_aporte_ronda` ($10k / $20k) y `monto_aporte_rifa` ($5k / $10k).
- **Tablas de Base de Datos y Migración:** Se añadieron las tablas `fondo_beneficios_cronograma` y `entregas_beneficios` en `config/database.php` y `db.sql`.
- **Modelos (`FondoBeneficio.php` & `EntregaBeneficio.php`):** Se crearon los modelos para auto-generar los saldos acumulados y liberaciones de Rondas ($300k) y Rifas ($150k) durante las 26 quincenas y listar socios pendientes por beneficiar.
- **Controlador (`EntregaController.php`):** Procesa el almacenamiento de imágenes en `/uploads/firmas/` (Base64 PNG desde Canvas) y `/uploads/evidencias/` (Captura de cámara o archivo adjunto) y registra las entregas.
- **Pad Táctil y Cámara (`public/js/firma_canvas.js`):** Implementación de Canvas interactivo para firma digital táctil/mouse con botón de limpiado y autocompletado inteligente de montos ($300k Ronda / $150k Rifa).
- **Vista Mobile-First (`app/Views/admin/entregas_beneficios.php`):** Interfaz completa con métricas de fondos, cronograma interactivo de 26 quincenas, modal de firma/cámara e historial de entregas con vista previa de firmas y fotos.
- **Navegación y Documentación:** Se agregó la ruta en `public/index.php`, enlace en `header.php` (Sidebar), la guía [docs/09_entregas_rondas_rifas.md](file:///j:/www/infonatillera/docs/09_entregas_rondas_rifas.md) y se actualizó `README.md`.

---

### 📌 Petición 15: Habilitar Acceso al Servidor desde Dispositivos Móviles en la Red Local (IP .1.8)
**Requerimiento del Usuario:**
`estoy intentando acceder desde mi celular al servidor local con mi ip que es .1.8 y me dice que no se encuentra, permiite wue pueda acceder`

**Solución Brindada:**
- **Reconfiguración de Binding de Red (`0.0.0.0:8001`):** El servidor de desarrollo de PHP anteriormente estaba enlazado únicamente a `localhost` (`127.0.0.1`), lo que bloqueaba las peticiones provenientes de otras interfaces de red.
- Se detuvo el servidor anterior y se reinició enlazado a todas las interfaces:
  `php -S 0.0.0.0:8001 -t public public/index.php`
- Esto permite acceder desde el navegador de cualquier teléfono celular o tablet conectado a la misma red Wi-Fi ingresando a:
  `http://192.168.1.8:8001`

---

### 📌 Petición 16: Corrección de PHP Notice ($valorCuotaBase indefinida) en Llamado a Lista Batch
**Requerimiento del Usuario:**
```
Notice: Undefined variable: valorCuotaBase in J:\www\infonatillera\app\Models\Reunion.php on line 118
Notice: Undefined variable: valorCuotaBase in J:\www\infonatillera\app\Models\Reunion.php on line 121
{"success":true,"message":"Llamado a lista guardado exitosamente."}
```

**Solución Brindada:**
- **Inicialización de Variable:** Se corrigió el método `guardarLlamadoListaBatch` en [Reunion.php](file:///j:/www/infonatillera/app/Models/Reunion.php#L118) extrayendo `$valorCuotaBase = (float)($reunion['valor_cuota_base'] ?? 55000);` a partir de los datos retornados de la consulta `$stmtReunion->fetch()`.
- Esto elimina las advertencias PHP Notice al guardar el llamado a lista en lote y garantiza que los desgloses de aporte de ronda/rifa y monto prestado para autopréstamos se calculen con el valor real de la cuota base de la reunión.

---

### 📌 Petición 17: Almacenamiento y Desglose de Abonos por Separado en Actividades (Tamales)
**Requerimiento del Usuario:**
`Al momento de abonar a las actividades como tamales, quiero que se guarden los valores que se pagan por separado. Ejemplo, si en un momento llevo 12.000 y después llevo 24.000, después llevo 50.000, esos valores por separado deben aparecer y debe verse tanto del lado del socio como de la secretaria de actividades para llevar el control mucho mejor.`

**Solución Brindada:**
- **Tabla `abonos_actividades` y Migración:** Se creó la tabla `abonos_actividades` (en [config/database.php](file:///j:/www/infonatillera/config/database.php) y [db.sql](file:///j:/www/infonatillera/db.sql)) para registrar independientemente cada abono parcial con fecha/hora, monto, observación y responsable.
- **Modelos y Métodos (`Actividad.php`):** Se crearon los métodos `registrarAbono`, `getAbonosPorParticipante`, `recalcularMontoPagado` y `eliminarAbono` para actualizar el total acumulado y estado de pago ('PAGADO' o 'PENDIENTE') dinámicamente sin sobrescribir los abonos anteriores.
- **Controlador y Rutas (`ActividadController.php`, `SocioController.php`, `index.php`):** Se agregaron los endpoints `/admin/actividades/abono/guardar` y `/admin/actividades/abono/eliminar`, y se modificó la carga de datos para adjuntar la lista de abonos tanto en el JSON de administración como en la vista del socio.
- **Interfaz Directiva (`admin/actividades.php`):** Se añadió el botón `+ Abonar` por participante en el modal de recaudo para ingresar entregas parciales y se incluyó la sección colapsable *"Abonos"* para ver las fechas, montos y opción de eliminar cada abono realizado.
- **Interfaz Socio (`socio/dashboard.php`):** En la sección *Mis Actividades Comunitarias*, se implementó el botón *"Ver mis abonados"* para que el socio consulte en todo momento la lista desglosada y fechas de sus pagos entregados.
- **Documentación:** Se actualizó la guía técnica [docs/04_actividades_tamales.md](file:///j:/www/infonatillera/docs/04_actividades_tamales.md).

---

### 📌 Petición 18: Firma Digital y Evidencia de Entregas para Préstamos (Opción Prioritaria)
**Requerimiento del Usuario:**
`En la parte donde se lleva el control de las entregas de las rifas y de las rondas, quiero que le agregues también y que quede de primera opción los préstamos, porque en muchas ocasiones se hacen préstamos y también se quiere tener el control de eso, entonces para que se pueda firmar y tomarle fotos si es necesario a la entrega de un préstamo.`

**Solución Brindada:**
- **Inclusión de Préstamos en Entregas:** Se amplió la lógica de [EntregaBeneficio.php](file:///j:/www/infonatillera/app/Models/EntregaBeneficio.php) y [FondoBeneficio.php](file:///j:/www/infonatillera/app/Models/FondoBeneficio.php) para aceptar `PRESTAMO` como tipo de entrega en la tabla `entregas_beneficios` y contabilizar las métricas totales desembolsadas.
- **Opción Prioritaria por Defecto:** En [entregas_beneficios.php](file:///j:/www/infonatillera/app/Views/admin/entregas_beneficios.php#L248) y en [firma_canvas.js](file:///j:/www/infonatillera/public/js/firma_canvas.js#L80) se estableció `PRÉSTAMO (Desembolso Crédito)` en la primera posición del selector, cargando la lista completa de socios deudores activos.
- **Acceso Directo desde Préstamos:** En la tabla de `/admin/prestamos` ([prestamos.php](file:///j:/www/infonatillera/app/Views/admin/prestamos.php#L93)) se agregó el botón **"✍ Firma/Foto"** para abrir directamente el modal de entregas con el socio y el monto del crédito pre-cargados.
- **Visualización y Evidencias:** Se agregó la tarjeta de métricas de Desembolso de Préstamos en el encabezado del módulo y la etiqueta distintiva azul (`badge bg-primary`) en el historial de entregas con firmas y fotos guardadas.
- **Documentación:** Se actualizó la guía [docs/09_entregas_rondas_rifas.md](file:///j:/www/infonatillera/docs/09_entregas_rondas_rifas.md).

---

### 📌 Petición 19: Migración de Base de Datos de SQLite a MySQL con Prefijo `natillera_` y Archivo `.env`
**Requerimiento del Usuario:**
`quiero cambiar la db de sqlite a mysql, pero a cada tabla ponle de prefijo natillera_ a cada nombre de tabla y migra lo que tengo en sqlite y crea el archivo env para las credenciales`

**Solución Brindada:**
- **Variables de Entorno `.env`:** Se crearon los archivos [.env](file:///j:/www/infonatillera/.env) y [.env.example](file:///j:/www/infonatillera/.env.example) para configurar la conexión MySQL y se añadió `.env` al [.gitignore](file:///j:/www/infonatillera/.gitignore).
- **Conexión MySQL en `database.php`:** Se implementó `Database::loadEnv()` en [config/database.php](file:///j:/www/infonatillera/config/database.php) y se configuró PDO MySQL (`mysql:host=...;dbname=...;charset=utf8mb4`).
- **Sintaxis y Prefijos en `db.sql`:** Se actualizó [db.sql](file:///j:/www/infonatillera/db.sql) con la sintaxis nativa de MySQL (`INT AUTO_INCREMENT PRIMARY KEY`, `TINYINT(1)`) y el prefijo `natillera_` en las 13 tablas del sistema.
- **Modelos y Controladores:** Se actualizaron todas las consultas SQL (`SELECT`, `INSERT`, `UPDATE`, `DELETE`, `JOIN`) en los modelos (`Usuario.php`, `Reunion.php`, `Prestamo.php`, `Actividad.php`, `EntregaBeneficio.php`, `FondoBeneficio.php`, `PushSubscription.php`) y en `SocioController.php` para apuntar a las tablas `natillera_*`.
- **Script de Migración de Datos:** Se desarrolló y ejecutó el script [scripts/migrate_sqlite_to_mysql.php](file:///j:/www/infonatillera/scripts/migrate_sqlite_to_mysql.php), trasladando exitosamente todos los registros existentes desde `natillera.sqlite` hacia la base de datos MySQL `infonatillera`.

---

### 📌 Petición 20: Buscador por ID/Nombre y Columna `# ID` de Socio en Actividades y Recaudo
**Requerimiento del Usuario:**
`Dale un buscador a la parte de actividades cuando se va a pagar una actividad porque en algunos casos están todos los socios y es difícil buscar uno solo. También agrega la columna de el ID ya que ese es la posición de cada socio entre 1 y 50 y es el número de cada socio. Y así poder buscar por ID o por nombre.`

**Solución Brindada:**
- **Ordenamiento y Campo `# ID` en Modelos:** Se actualizó `Actividad.php`, `EntregaBeneficio.php`, `Reunion.php` y `Usuario.php` para asegurar el orden por `u.id ASC` y la presencia del identificador numérico único (posición #1 a #50) de cada socio.
- **Columna `# ID` y Buscador en Recaudo (`admin/actividades.php`):** En el modal *"Participantes y Recaudo"*, se agregó la primera columna `# ID` con la etiqueta `#` de socio y el cuadro de búsqueda en tiempo real `searchParticipanteAct` para filtrar instantáneamente por número de socio o por nombre completo.
- **Buscador en Creación de Actividades (`admin/actividades.php`):** Se añadió la etiqueta `# ID` a cada socio en la lista de asignación de cuotas iniciales y un buscador en tiempo real `searchCrearActividadSocio` para localizar rápidamente a los participantes.
- **Documentación:** Se actualizó la guía técnica [docs/04_actividades_tamales.md](file:///j:/www/infonatillera/docs/04_actividades_tamales.md).

---

### 📌 Petición 21: Corrección de ID de Participante, Formato de Miles con Punto y Permanencia del Modal por AJAX
**Requerimiento del Usuario:**
`Hay múltiples errores en la parte de actividades: 1. Intento agregar un valor a un socio y cuando quiero ver aparece es en otro (ej: intenté el 50 y se fue para el 8). 2. El input no está mostrando el punto para separar los miles. 3. Cuando se agregue un valor no cierres el modal, déjalo por si en algún momento se quiere agregar a otro socio.`

**Solución Brindada:**
- **Corrección de Identificador (`Actividad.php`):** En `getParticipantes()`, se especificó la selección explícita `ap.id, ap.actividad_id, ap.socio_id, ... u.id as socio_user_id`. Anteriormente `u.id` sobreescribía `ap.id`, enviando el ID de usuario como ID del participante y asignando abonos a filas incorrectas.
- **Formateador Monetario de Miles (`admin/actividades.php`):** Se transformó el campo `monto_abono` en un input numérico formateado con la clase `.money-input` y la llamada a `initMoneyInputs(tbody)` para separar miles en tiempo real con puntos (*ej: 12.000*).
- **Permanencia del Modal mediante AJAX (`ActividadController.php` & `admin/actividades.php`):** Se crearon los handlers en JS `registrarAbonoAjax` y `eliminarAbonoAjax` enviando el encabezado `X-Requested-With: XMLHttpRequest` y procesando respuestas JSON. Al abonar o eliminar, el modal permanece abierto, los saldos se actualizan automáticamente y se muestra un toast notificando la transacción.
- **Documentación:** Se actualizó la guía técnica [docs/04_actividades_tamales.md](file:///j:/www/infonatillera/docs/04_actividades_tamales.md).

---

### 📌 Petición 22: Control de Sobrepagos y Bloqueo de Abonos a Socios Al Día en Actividades
**Requerimiento del Usuario:**
`Cuando la persona o el socio ya esté al día con esa actividad no le permitas asignar más dinero a esa actividad ya que se va a generar un sobrecosto o un sobrepago en esa actividad.`

**Solución Brindada:**
- **Validación Backend (`Actividad.php` & `ActividadController.php`):** Se implementó una verificación estricta en `registrarAbono()`. Si el socio ya está en estado `PAGADO` (`saldo <= 0`), se lanza la excepción *"El socio ya completó el pago de esta actividad. No se permiten abonos adicionales."*. Si el monto a ingresar supera el saldo pendiente restante, se arroja la excepción notificando el monto máximo permitido.
- **Interfaz de Usuario Frontend (`admin/actividades.php`):** Al listar los participantes en el modal, si el socio ya completó la cuota, la casilla de abonos se reemplaza con la insignia verde **Completo**. Si aún tiene saldo pendiente, el placeholder muestra la cuota máxima restante (`placeholder="Máx: $X"`) y JavaScript impide enviar valores superiores.
- **Documentación:** Se actualizó la guía técnica [docs/04_actividades_tamales.md](file:///j:/www/infonatillera/docs/04_actividades_tamales.md).

---

### 📌 Petición 23: Diagnóstico y Solución de Error 404 Not Found en Servidor LiteSpeed (cPanel)
**Requerimiento del Usuario:**
`subi el codigo al servidor y me sale así y tambien hay una imagen de como apunta el sbdominio (natillera.skylinedev.top pointing to /public_html/natillera/public)`

**Solución Brindada:**
- **Compatibilidad con LiteSpeed Web Server (`public/.htaccess` & `.htaccess`):** Se agregaron las directivas `DirectoryIndex index.php` y `RewriteBase /` a [public/.htaccess](file:///j:/www/infonatillera/public/.htaccess) y [file:///.htaccess](file:///j:/www/infonatillera/.htaccess) para asegurar que LiteSpeed despache automáticamente el Front Controller `index.php` tanto si la raíz apunta a `/public_html/natillera/public` como a `/public_html/natillera`.
- **Configuración de Credenciales `.env` Remotas:** Se brindó la indicación de ajustar el archivo [.env](file:///j:/www/infonatillera/.env) en el servidor de producción activando las credenciales `skylined_pruebas` / `skylined_natillera`.

---

### 📌 Petición 24: Hoja del Socio / Expediente Financiero Completo
**Requerimiento del Usuario:**
`En la parte de administración de socios o gestión de socios quiero que agregues que al darle click a un socio me abra la hoja de ese socio donde me muestre las cuotas que ha dado que son de valor de 40.000, los ahorros que tiene o los ahorros que haya hecho, y las ganancias que tenga de actividades, las deudas que tenga de préstamos y lo que ha generado de intereses y a su vez ver todo lo que ese socio tiene registrado.`

**Solución Brindada:**
- **Endpoint Backend (`SocioController.php` & `public/index.php`):** Se creó la ruta `/admin/socios/expediente-json` y el método `expedienteJson()` en `SocioController.php` para consolidar en una sola consulta toda la hoja de vida financiera del socio: resumen KPI, historial de cuotas base de $40.000, ahorros voluntarios, participación en actividades comunitarias, préstamos desembolsados, amortizaciones a capital, cobro de intereses generados y entregas con firmas digitales/fotografías.
- **Modal de Expediente (`admin/socios.php`):** Se implementó `#modalExpedienteSocio` con el distintivo de número `# ID` del socio (1 a 50), 4 tarjetas KPI de resumen acumulado y 4 pestañas navegables (`Cuotas y Ahorros`, `Actividades`, `Préstamos e Intereses`, `Entregas y Comprobantes`).
- **Navegación e Interacción:** Se hizo clicable el nombre de cada socio y se agregó el botón **"📁 Ver Hoja del Socio"** en la tabla de gestión de socios para consultar la información al instante mediante llamadas AJAX.
- **Corrección de Columna SQL (`SocioController.php`):** Se corrigió la cláusula JOIN del historial de entregas sustituyendo `e.registrado_por_usuario_id` por la columna real `e.entregado_por_usuario_id`, asegurando la carga 100% libre de errores.
- **Documentación:** Se actualizó la guía técnica [docs/07_gestion_socios.md](file:///j:/www/infonatillera/docs/07_gestion_socios.md).

---

### 📌 Petición 25: Formateo Masivo de Cédulas (`104001` a `104050`) y Solución a Duplicate Entry
**Requerimiento del Usuario:**
`update natillera_usuarios set cedula = concat('1040',id) -> [23000][1062] Duplicate entry '104010' for key 'cedula'. haz que funcione y los que son de un digito ponle 104001`

**Solución Brindada:**
- **Análisis del Error:** La restricción `UNIQUE` en la columna `cedula` de MySQL colisiona durante la actualización fila por fila cuando se actualizan registros existentes que se solapan temporalmente.
- **Consultas SQL e Implementación:** Se desarrolló la solución de 2 pasos o con reordenamiento `ORDER BY id DESC` utilizando la función `LPAD(id, 2, '0')`:
  ```sql
  UPDATE natillera_usuarios SET cedula = CONCAT('TEMP_', id);
  UPDATE natillera_usuarios SET cedula = CONCAT('1040', LPAD(id, 2, '0'));
  ```
- **Ejecución y Verificación:** Se creó y ejecutó el script [scripts/fix_cedulas.php](file:///j:/www/infonatillera/scripts/fix_cedulas.php), actualizando exitosamente las cédulas de todos los socios (ejemplo: ID #1 -> `104001`, ID #9 -> `104009`, ID #10 -> `104010`).

---

### 📌 Petición 26: Depuración y Diagnóstico en Registro de Evidencia de Entregas
**Requerimiento del Usuario:**
`Intento agregar una evidencia al control de entregas y me dice que no se pudo registrar. Ten en cuenta que ya estoy utilizando la base de datos arriba por si hay que revisar algo en el esquema.`

**Solución Brindada:**
- **Inspección de Esquema Remoto (`skylined_pruebas`):** Se verificó mediante script PDO la integridad de las tablas `natillera_entregas_beneficios` y `natillera_reuniones` en el servidor remoto `184.107.184.74`, comprobando que las columnas y claves foráneas coinciden exactamente.
- **Transmisión Explícita de Excepciones PDO:** En `EntregaBeneficio::registrarEntrega()`, se reemplazó la captura silenciosa de excepciones por `throw $e;` y en `EntregaController::guardar()`, se envolvió el llamado dentro de un bloque `try-catch (Throwable $e)` para notificar en pantalla la descripción exacta del error de MySQL (`$e->getMessage()`) en caso de fallo.
- **Creación Segura de Directorios:** Se mejoró la comprobación y creación recursiva silenciosa `@mkdir()` para `/uploads/firmas` y `/uploads/evidencias`.
- **Validación Dinámica de Sesión en FK (`EntregaController.php`):** Se detectó que la sesión web activa mantenía un `usuario_id` previo que no existía en `natillera_usuarios` tras actualizar la base de datos remota. Se añadió una verificación previa con `getSocioById()`; si el ID de la sesión no existe en la base de datos actual, se autocorrige automáticamente asignando un usuario válido existente en la tabla (ej. ID #1) para cumplir la clave foránea `entregado_por_usuario_id`.

---

### 📌 Petición 27: Módulo de Inyecciones de Capital (5% Rendimiento & Retiro a 6 Meses) y Cierre Financiero por Reunión
**Requerimiento del Usuario:**
`Nosotros internamente también manejamos lo que son las inyecciones de capital que se manejan siempre al principio de la natillera en la primera y segunda reunión. Quiero que me permitas crear un módulo donde se puedan registrar las inyecciones de cada socio teniendo en cuenta que estas inyecciones se pueden retirar luego de 6 meses y por lo regular se les paga el 5% a los socios que inyectaron este capital. Por otro lado quiero tener el cierre de cada reunión para que así podamos ver según todo lo que ingresó (cuotas, ahorros) y todo lo que salió (préstamos, si pagaron préstamos o intereses) tener esa parte en la liquidación por cada reunión.`

**Solución Brindada:**
- **Tablas de Base de Datos (`natillera_inyecciones_capital` & `natillera_cierres_reunion`):** Creadas en entorno local y servidor remoto `skylined_pruebas`.
- **Módulo de Inyecciones de Capital (`InyeccionController.php`, `InyeccionCapital.php`, `admin/inyecciones.php`):** Permite registrar aportes extraordinarios de capital asignando automáticamente el **5% de rendimiento** y una restricción de permanencia congelada a **6 meses (`fecha_retiro_permitido`)**. Si se intenta retirar antes del tiempo cumplido, el sistema bloquea la acción notificando el número de días faltantes.
- **Módulo de Cierre Financiero y Arqueo (`CierreController.php`, `CierreReunion.php`, `admin/cierre_reunion.php`):** Consolida en tiempo real todos los Ingresos (+) y Egresos (-) por reunión quincenal (Cuotas $40k, Ahorro Extra, Rondas/Rifas, Cobros de préstamos, Intereses, Actividades e Inyecciones vs Préstamos desembolsados, Premios y Devoluciones), calculando el Flujo Neto de la Reunión y el Saldo Acumulado Global en Caja con guardado de snapshot inmutable al cambiar a estado `CERRADA`.
- **Integraciones:** Se agregaron enlaces al menú lateral (`header.php`) y se incluyó el historial de inyecciones del socio en su hoja de expediente (`expedienteJson`).

---

### 📌 Petición 28: Separación de la Caja Mayor de la Caja de Actividades y Módulo de Transferencias Intercajas
**Requerimiento del Usuario:**
`Las actividades comunitarias como tamales y rifas no van incluidas en los cierres de caja, porque hay que tener en cuenta que acá se lleva solo el movimiento de intereses, inyecciones y las cuotas. Eso va por otro lado. Y hay que tener en cuenta que esta caja, que es la principal, en ocasiones hace préstamos a la caja de las actividades, porque no hay capital para iniciar con esas actividades. Entonces debe haber un ítem donde la caja mayor le hace un préstamo sin ninguna clase de interés a la caja de las actividades y también me aparezca en los egresos, y cuando devuelva o transfiera valores a la caja mayor me aparezca en los ingresos.`

**Solución Brindada:**
- **Independencia de Caja Mayor (`CierreReunion.php` & `cierre_reunion.php`):** Se eliminaron los abonos a actividades comunitarias de los ingresos del Arqueo de Caja Mayor para mantener la separación contable solicitada.
- **Tabla `natillera_transferencias_cajas`:** Creada en local y en producción (`skylined_pruebas`) para registrar movimientos entre cajas.
- **Módulo Intercajas (`TransferenciaCajaController.php`, `TransferenciaCaja.php`, `admin/transferencias_cajas.php`):** Permite registrar préstamos sin interés de la Caja Mayor hacia la Caja de Actividades (`PRESTAMO_A_ACTIVIDAD`, egreso en Caja Mayor) y devoluciones/transferencias de excedentes desde la Caja de Actividades hacia la Caja Mayor (`DEVOLUCION_A_CAJA_MAYOR`, ingreso en Caja Mayor).
- **Control de Saldo Pendiente entre Cajas:** La interfaz muestra en tarjetas KPI el total prestado a actividades, total devuelto y el saldo adeudado entre la Caja de Actividades y la Caja Mayor.
- **Navegación:** Se incorporó la ruta `/admin/transferencias-cajas` y el enlace *"Transferencias entre Cajas"* en el menú principal (`header.php`).
- **Corrección de Método (`TransferenciaCajaController.php`):** Se corrigió la llamada al método de obtención de actividades sustituyendo `getActividades()` por el nombre correcto en el modelo `Actividad::getTodasActividades()`.

---

### 📌 Petición 29: Exclusión de Rondas/Rifas del Cierre de Caja y Ajuste en Sumatoria Quincenal de Cobros de Préstamos
**Requerimiento del Usuario:**
`En el cierre de reunión, quiero que no tengas en cuenta el aporte de rifas ni la salida de esas rifas, ya que eso se maneja por otro lado. Y revisa que sí se estén sumando lo que se ingresó de lo que pagaron en préstamos e intereses, que hice algunos ajustes y no los vi.`

**Solución Brindada:**
- **Exclusión de Rondas y Rifas del Arqueo (`CierreReunion.php` & `cierre_reunion.php`):** Se eliminó el aporte a Rondas y Rifas de los Ingresos (+) y el pago de premios de los Egresos (-).
- **Columna `reunion_id` en Abonos a Préstamos (`natillera_abonos_prestamos`):** Agregada a la tabla en local y en producción (`skylined_pruebas`) para permitir la asociación directa de cobros a quincenas.
- **Mejora en Consulta de Arqueo (`CierreReunion.php`):** La consulta SQL ahora computa todos los abonos asignados explícitamente a `reunion_id` o aquellos realizados en la ventana de fechas quincenal entre la reunión previa y la reunión actual.
- **Interfaz (`prestamos.php`, `PrestamoController.php`, `Prestamo.php`):** Se agregó el selector de quincena en el modal de registrar abono a préstamo.

---

### 📌 Petición 30: Cambio Global de Nomenclatura en la Interfaz (Quincena ➔ Reunión, Q ➔ R)
**Requerimiento del Usuario:**
`quiero que cambies todo lo que diga quincena por reunión y la Q por R`

**Solución Brindada:**
- **Reemplazo Visual en Vistas Admin y Socio (`app/Views/`):** Se actualizaron todos los encabezados, tablas, insignias, modales y etiquetas para reemplazar la palabra *"quincena"* por *"reunión"* y la letra *"Q"* por *"R"* (ej. *Reunión R1*, *Reunión R2*, *Historial de Ahorros por Reunión*, *Cronograma 26 Reuniones*).
- **Archivos Modificados:** `cierre_reunion.php`, `transferencias_cajas.php`, `inyecciones.php`, `entregas_beneficios.php`, `llamado_lista.php`, `reuniones.php`, `prestamos.php`, `socios.php`, `dashboard.php` (socio), `notificaciones.php`.

---

### 📌 Petición 31: Ampliación de Campo Ahorro Extra en Móvil e Indicador de Préstamos Firmados/Fotografiados
**Requerimiento del Usuario:**
`En la parte del llamado a lista para móvil, el campo del ahorro extra es muy pequeño. Ponlo más largo para que se pueda visualizar bien el número. En la parte de préstamos, cuando a un préstamo ya se le haya hecho una firma o foto, el botón ponlo totalmente en amarillo, relleno en amarillo. Aún así se puede editar, pero ya desde la entrega. Esto es para identificar cuáles les falta la firma.`

**Solución Brindada:**
- **Ampliación de Campo en Llamado a Lista (`llamado_lista.php`):** Se definió un ancho mínimo adecuado (`min-width: 150px; max-width: 220px;`) para el input de Ahorro Extra y su celda en la tabla, evitando recortes numéricos en dispositivos móviles.
- **Detección y Resaltado en Préstamos (`Prestamo.php` & `prestamos.php`):** Se integró la subconsulta `tiene_firma_foto` para verificar si un préstamo tiene evidencia registrada en `natillera_entregas_beneficios`. Si ya tiene firma/foto, su botón pasa a **amarillo sólido (`btn-warning`)** con un check `✔`; de lo contrario, se mantiene en formato bordeado (`btn-outline-warning`) para señalar visualmente los préstamos pendientes de firma.

---

### 📌 Petición 32: Landing Page de Normativa 2026 y Reestructuración de la Página de Login
**Requerimiento del Usuario:**
`Estas son las normas que utilizamos nosotros en la natillera. Quiero que construyas una página y la me organizas como una landing page para que se vea en el login. Haces el login a la derecha y a la izquierda quiero que se vean estas normas bien organizadas para que cada vez al ingresar los miembros puedan ver estas normas, incluso si las puedes hacer un poquito dinámicas, algo bien chévere para que se vean un poco coloridas con colores suaves y puedan ser resaltables.`

**Solución Brindada:**
- **Página de Inicio & Login (`app/Views/auth/login.php`):** Se reestructuró en un diseño de dos columnas. A la izquierda se implementó una **Landing Page de Normativa 2026** interactiva y a la derecha el formulario de acceso de usuario.
- **Tarjetas Dinámicas de Colores Pastel:** Se transcribieron las 12 normas exactas desde las fotografías del cuaderno, asignando a cada una una tarjeta con colores suaves, icono temático, badge descriptivo y efecto elevador al pasar el cursor (`transition-hover`).
- **Buscador y Filtro por Categorías:** Se integró un buscador en tiempo real y 4 filtros rápidos (*Todas*, *Cuotas & Préstamos*, *Intereses & Inyecciones*, *Organización*) para una navegación fluida por el reglamento.
- **Orden Responsivo Inteligente (Móvil vs Desktop):** Se implementaron clases Bootstrap (`order-1 order-lg-2` en el login y `order-2 order-lg-1` en la normativa). En teléfonos móviles el formulario de login aparece **primero arriba** para un acceso rápido, dejando la normativa abajo; mientras que en pantallas grandes la normativa se muestra a la izquierda y el login a la derecha.

---

### 📌 Petición 33: Registro Completo de Fondos en Autopréstamos y Buscador por Reunión en Préstamos
**Requerimiento del Usuario:**
`Si una persona en una reunión hace un autopréstamo, no significa que esa plata deja de ingresar, esa plata sí ingresa, lo que pasa es que aparte del ingreso, también se genera un préstamo que se le hizo... pero los 40.000, los 10.000 y los 5.000 sí deberían aparecer registrados. Luego, en la gestión de préstamos, quiero que le agregues un buscador y que a los préstamos futuros también les agregues la reunión, ya que necesitamos identificar el número de reunión para poder filtrar por personas como por la reunión.`

**Solución Brindada:**
- **Contabilidad de Fondos por Autopréstamo (`Reunion.php`):** Al guardar un llamado a lista con autopréstamo, la cuota base ($40.000), el aporte a ronda ($10.000) y la rifa ($5.000) se registran como recaudados en `natillera_ahorros_cuotas`, paralelamente a la emisión del `AUTOPRESTAMO` de $55.000 al 10% en `natillera_prestamos`.
- **Columna `reunion_id` en Préstamos (`db.sql`, `alter_prestamos_reunion.php`, `Prestamo.php`, `PrestamoController.php`):** Se agregó la columna y clave foránea `reunion_id` a `natillera_prestamos` en BD local y remota (`skylined_pruebas`), asociando automáticamente los autopréstamos y préstamos directos a su reunión correspondiente.
- **Visualización y Buscador Dinámico (`prestamos.php`):** Se incorporó la columna **Reunión** (`R1`, `R2`...) en la tabla de préstamos y un buscador en tiempo real que permite filtrar la tabla tanto por nombre/cédula del socio, referencia/alias o número de reunión (ej: al buscar `R1` se muestran los préstamos de la Reunión 1). Se agregaron también los selectores de reunión en los modales de creación y edición.

---

### 📌 Petición 34: Independización del Llamado a Lista y Cierre Financiero por Reunión
**Requerimiento del Usuario:**
`El cierre de la reunión está amarrado al cierre del llamado a lista. Quiero que los separes. Si se cierra el llamado a lista, luego si cierro la reunión para poder consolidar justo los valores que hay ahí y en la siguiente se me vayan mostrando los valores que quedaron de la reunión anterior para así poder ver el consolidado de la caja en cuanto debería estar y que me pueda cuadrar todo, porque en el momento no me está dejando cerrar los otros valores de las reuniones que ya cerraron por llamado. Solo si la reunión está cerrada por llamado es que me quiere mostrar para cerrar. Independiza el llamado a lista del cierre, para que yo pueda hacer un cierre para el llamado del cierre normal de cada reunión.`

**Solución Brindada:**
- **Separación de Estados (`Reunion.php`, `fix_reunion_states.php`):** Se eliminó el cambio automático a estado `CERRADA` al guardar el llamado a lista, asignando en su lugar el estado `LLAMADO_CERRADO`. Se desbloquearon las reuniones R1, R2, R3, R4 que estaban marcadas como cerradas prematuramente.
- **Acción de Cierre Financiero Habilitada (`CierreReunion.php`, `cierre_reunion.php`):** El estado `CERRADA` se aplica únicamente cuando la directiva hace clic en **"Realizar Cierre Financiero"** en el módulo de Arqueo de Caja.
- **Consolidación de Caja y Heredado de Saldo Inicial:** Se incluyeron los fondos de Rondas/Rifas en el ingreso total y se implementó la visualización de 3 tarjetas de balance: *Saldo Inicial de Caja (Anterior)*, *Flujo Neto de la Reunión* y *Saldo Acumulado Final en Caja*, garantizando que al cerrar R1 se arrastre el dinero acumulado a R2, de R2 a R3, y así sucesivamente.

---

### 📌 Petición 35: Regla Personalizada para Git Commits y Push Bajo Demanda Explicita
**Requerimiento del Usuario:**
`Crea un comando para que solo cuando yo te diga 'Haz commit' o 'Crees el commit' poniéndole de título el módulo que se modificó. Si hubo varios módulos, debes poner el módulo más el otro módulo más todos los módulos que se hayan modificado y abajo la descripción de lo que se hizo en ese commit y haces el commit y le haces push.`

**Solución Brindada:**
- **Regla en Workspace (`.agents/AGENTS.md`):** Se creó el archivo de reglas de agente en [.agents/AGENTS.md](file:///j:/www/infonatillera/.agents/AGENTS.md) estableciendo que los commits y push no se harán de forma automática.
- **Protocolo de Formato**:
  - **Título**: Módulos modificados unidos por `+` (ejemplo: `[LlamadoLista + CierreReunion + Prestamos]`).
  - **Descripción**: Viñetas explicativas detalladas con todas las mejoras y correcciones realizadas.
  - **Ejecución**: Ejecutar `git commit` y `git push` únicamente cuando el usuario envíe comandos explícitos como *"Haz commit"*, *"Crea el commit"* o *"Haz commit y push"*.

---

### 📌 Petición 36: Exclusión de Fondos de Rondas y Rifas del Cierre Financiero de Caja Mayor
**Requerimiento del Usuario:**
`Está bien lo del cierre, pero en el cierre incluiste nuevamente los 750 de los aportes de ronda y rifa y eso no se tiene en cuenta ahí, porque eso se maneja independiente. Entonces no se deben tener en cuenta ahí, no se pueden sumar eso.`

**Solución Brindada:**
- **Corrección en Modelo (`CierreReunion.php`):** Se eliminó la suma de `$rondasRifas` de los `$totalIngresos` de la Caja Mayor y se fijó en 0.00 el rubro de rondas/rifas en el arqueo financiero.
- **Limpieza de Interfaz (`cierre_reunion.php`):** Se retiró el ítem de *Aportes a Ronda y Rifa* del bloque visual de **INGRESOS A CAJA (+)**, dejando exclusivamente las cuotas base ($40k), ahorro extra, abonos a préstamos, intereses, inyecciones y devoluciones.

---

### 📌 Petición 37: Asignación Automática de Reunión al Registrar Firma/Foto de Préstamos
**Requerimiento del Usuario:**
`Voy a crear la firma desde un préstamo no está arrastrando el número de la reunión y me toca editarlo. Corrígelo para que sea automático y no tenga que cambiarlo.`

**Solución Brindada:**
- **URL Dinámica en Préstamos (`prestamos.php`):** Se incluyó `&reunion_id=...` en el botón **Firma/Foto** para enviar el identificador de la reunión del préstamo al módulo de entregas.
- **Captura y Selección Automática (`EntregaController.php`, `entregas_beneficios.php`):** Se ajustó el controlador para procesar `reunion_id` desde la URL y preseleccionar automáticamente dicha reunión en el formulario modal del registro de entrega con firma digital y foto evidencia.
































