<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-6 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-users-gear text-primary"></i>
            Gestión de Socios e Información Personal
        </h2>
        <p class="text-muted m-0 fs-7">Consulta y actualización de datos personales, teléfonos, fechas de cumpleaños y roles.</p>
    </div>
    <div class="col-12 col-md-6 d-flex justify-content-md-end gap-2">
        <button type="button" class="btn btn-outline-dark rounded-pill fw-semibold btn-sm shadow-sm" data-bs-toggle="collapse" data-bs-target="#collapseCumpleanos">
            <i class="fa-solid fa-cake-candles text-danger me-1"></i>Ver Cumpleaños
        </button>
        <button type="button" class="btn btn-primary rounded-pill fw-bold btn-sm shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#modalNuevoSocio">
            <i class="fa-solid fa-user-plus me-1"></i>Nuevo Socio
        </button>
    </div>
</div>

<!-- Widget de Cumpleaños Colapsable -->
<div class="collapse mb-4" id="collapseCumpleanos">
    <div class="card border-0 shadow-sm rounded-4 bg-gradient-navy text-white p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="font-outfit fw-bold m-0 text-warning d-flex align-items-center gap-2">
                <i class="fa-solid fa-cake-candles fs-4"></i>
                Calendario de Cumpleaños de los Socios
            </h5>
            <span class="badge bg-white bg-opacity-20 rounded-pill">Total: <?= count($cumpleanos) ?> socios</span>
        </div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 overflow-auto" style="max-height: 250px;">
            <?php 
            $mesesEs = [1=>'Ene', 2=>'Feb', 3=>'Mar', 4=>'Abr', 5=>'May', 6=>'Jun', 7=>'Jul', 8=>'Ago', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dic'];
            foreach ($cumpleanos as $c): 
                $f = strtotime($c['fecha_nacimiento']);
                $mes = (int)date('m', $f);
                $dia = date('d', $f);
            ?>
                <div class="col">
                    <div class="p-2.5 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold font-outfit text-white fs-7"><?= htmlspecialchars($c['nombre_completo']) ?></div>
                            <small class="text-white-50 fs-8"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($c['telefono'] ?: 'Sin tel') ?></small>
                        </div>
                        <span class="badge bg-warning text-dark font-outfit fw-bold fs-7">
                            <?= $dia ?> <?= $mesesEs[$mes] ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Filtro de Búsqueda Rápida -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" id="buscarSocioAdmin" class="form-control border-start-0 ps-0" placeholder="Buscar socio por nombre, cédula o teléfono...">
        </div>
    </div>
</div>

<!-- Tabla Principal de Socios -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaSocios">
            <thead class="bg-dark text-white font-outfit fs-7 text-uppercase">
                <tr>
                    <th class="ps-4">Socio / Cédula</th>
                    <th>Teléfono</th>
                    <th>Fecha de Nacimiento</th>
                    <th>Rol en Natillera</th>
                    <th>Tope de Crédito</th>
                    <th class="text-end pe-4">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($socios as $s): 
                    $fNac = !empty($s['fecha_nacimiento']) ? date('d/m/Y', strtotime($s['fecha_nacimiento'])) : 'No registrada';
                ?>
                    <tr class="socio-admin-row" data-search="<?= strtolower(htmlspecialchars($s['nombre_completo'] . ' ' . $s['cedula'] . ' ' . $s['telefono'])) ?>">
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary font-outfit fs-7">#<?= $s['id'] ?></span>
                                <div>
                                    <a href="#" class="fw-bold text-dark font-outfit text-decoration-none btnVerExpediente" data-id="<?= $s['id'] ?>" data-bs-toggle="modal" data-bs-target="#modalExpedienteSocio" title="Ver Expediente de <?= htmlspecialchars($s['nombre_completo']) ?>">
                                        <?= htmlspecialchars($s['nombre_completo']) ?>
                                    </a>
                                    <small class="d-block text-muted">C.C. <?= htmlspecialchars($s['cedula']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($s['telefono'])): ?>
                                <a href="tel:<?= htmlspecialchars($s['telefono']) ?>" class="text-decoration-none text-dark">
                                    <i class="fa-solid fa-phone text-success me-1"></i><?= htmlspecialchars($s['telefono']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted fs-7">No registrado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($s['fecha_nacimiento'])): ?>
                                <span class="badge bg-light text-dark border">
                                    <i class="fa-solid fa-cake-candles text-danger me-1"></i><?= $fNac ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted fs-7">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($s['rol_nombre']) ?></span>
                        </td>
                        <td class="fw-bold font-outfit text-dark">$<?= number_format($s['tope_prestamo_personalizado'], 0, ',', '.') ?></td>
                        <td class="text-end pe-4">
                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3 py-1 btnVerExpediente me-1"
                                    data-id="<?= $s['id'] ?>"
                                    data-bs-toggle="modal" data-bs-target="#modalExpedienteSocio">
                                <i class="fa-solid fa-folder-open me-1"></i>Ver Hoja del Socio
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 btnEditarSocio"
                                    data-id="<?= $s['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($s['nombre_completo']) ?>"
                                    data-cedula="<?= htmlspecialchars($s['cedula']) ?>"
                                    data-telefono="<?= htmlspecialchars($s['telefono']) ?>"
                                    data-nacimiento="<?= htmlspecialchars($s['fecha_nacimiento']) ?>"
                                    data-rol="<?= $s['rol_id'] ?>"
                                    data-bs-toggle="modal" data-bs-target="#modalEditarSocio">
                                <i class="fa-solid fa-user-pen me-1"></i>Editar Datos
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Editar Datos del Socio -->
<div class="modal fade" id="modalEditarSocio" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-user-pen me-2 text-warning"></i>Editar Información del Socio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/socios/actualizar" method="POST">
                <input type="hidden" name="socio_id" id="edit_socio_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_nombre_completo" class="form-label fw-semibold fs-7">Nombre Completo</label>
                        <input type="text" name="nombre_completo" id="edit_nombre_completo" class="form-control fw-bold" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_cedula" class="form-label fw-semibold fs-7">Cédula de Identidad</label>
                            <input type="text" name="cedula" id="edit_cedula" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label for="edit_telefono" class="form-label fw-semibold fs-7">Teléfono de Contacto</label>
                            <input type="text" name="telefono" id="edit_telefono" class="form-control">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_fecha_nacimiento" class="form-label fw-semibold fs-7">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento" class="form-control">
                        </div>
                        <div class="col-6">
                            <label for="edit_rol_id" class="form-label fw-semibold fs-7">Rol en Natillera</label>
                            <select name="rol_id" id="edit_rol_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 border-top pt-3">
                        <label for="edit_password" class="form-label fw-semibold fs-7 text-muted">Cambiar Contraseña (Opcional)</label>
                        <input type="password" name="password" id="edit_password" class="form-control" placeholder="Dejar en blanco para mantener la actual">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill fw-bold px-4">Actualizar Socio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Registrar Nuevo Socio -->
<div class="modal fade" id="modalNuevoSocio" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Registrar Nuevo Socio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/socios/crear" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="new_nombre_completo" class="form-label fw-semibold fs-7">Nombre Completo</label>
                        <input type="text" name="nombre_completo" id="new_nombre_completo" class="form-control fw-bold" placeholder="Ej: Juan Pérez" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="new_cedula" class="form-label fw-semibold fs-7">Cédula de Identidad</label>
                            <input type="text" name="cedula" id="new_cedula" class="form-control" placeholder="Ej: 1010999888" required>
                        </div>
                        <div class="col-6">
                            <label for="new_telefono" class="form-label fw-semibold fs-7">Teléfono de Contacto</label>
                            <input type="text" name="telefono" id="new_telefono" class="form-control" placeholder="Ej: 3001234567">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="new_fecha_nacimiento" class="form-label fw-semibold fs-7">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" id="new_fecha_nacimiento" class="form-control">
                        </div>
                        <div class="col-6">
                            <label for="new_rol_id" class="form-label fw-semibold fs-7">Rol Asignado</label>
                            <select name="rol_id" id="new_rol_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $r['nombre'] === 'Socio' ? 'selected' : '' ?>><?= htmlspecialchars($r['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 border-top pt-3">
                        <label for="new_password" class="form-label fw-semibold fs-7 text-muted">Contraseña Inicial</label>
                        <input type="password" name="password" id="new_password" class="form-control" value="123456" required>
                        <small class="text-muted fs-8">Por defecto es '123456'</small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4">Crear Socio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hoja del Socio / Expediente Financiero Completo -->
<div class="modal fade" id="modalExpedienteSocio" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary font-outfit fs-6 px-3 py-2" id="exp_socio_id_badge">#--</span>
                    <div>
                        <h5 class="modal-title font-outfit fw-bold m-0" id="exp_socio_nombre">Cargando Hoja del Socio...</h5>
                        <small class="text-white-50 fs-8 d-block" id="exp_socio_detalles">---</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div id="expedienteSpinner" class="text-center py-5">
                    <i class="fa-solid fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                    <h5 class="font-outfit text-muted">Cargando expediente e historial financiero completo...</h5>
                </div>

                <div id="expedienteContent" class="d-none">
                    <!-- Resumen KPIs Rápidos -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-success">
                                <span class="fs-8 text-muted fw-semibold">Total Ahorrado (Cuotas + Extras)</span>
                                <h4 class="font-outfit fw-bold text-success m-0 mt-1" id="exp_kpi_ahorro">$0</h4>
                                <small class="text-muted fs-8 d-block mt-1" id="exp_kpi_ahorro_det">Cuotas: $0 | Extra: $0</small>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-warning">
                                <span class="fs-8 text-muted fw-semibold">Intereses Generados / Aportados</span>
                                <h4 class="font-outfit fw-bold text-warning m-0 mt-1" id="exp_kpi_interes">$0</h4>
                                <small class="text-muted fs-8 d-block mt-1" id="exp_kpi_interes_det">Meta $400.000 (0%)</small>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-danger">
                                <span class="fs-8 text-muted fw-semibold">Deudas Activas Totales</span>
                                <h4 class="font-outfit fw-bold text-danger m-0 mt-1" id="exp_kpi_deuda">$0</h4>
                                <small class="text-muted fs-8 d-block mt-1" id="exp_kpi_deuda_det">Préstamos: $0 | Actividades: $0</small>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-info">
                                <span class="fs-8 text-muted fw-semibold">Entregas / Beneficios Recibidos</span>
                                <h4 class="font-outfit fw-bold text-info m-0 mt-1" id="exp_kpi_entregas">$0</h4>
                                <small class="text-muted fs-8 d-block mt-1" id="exp_kpi_entregas_det">0 entregas registradas</small>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs de Navegación de Contenido -->
                    <ul class="nav nav-pills font-outfit mb-3 bg-white p-2 rounded-4 shadow-sm" id="tabExpediente" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill px-3 py-1.5 fw-semibold" id="tab-cuotas-tab" data-bs-toggle="pill" data-bs-target="#tab-cuotas" type="button">
                                <i class="fa-solid fa-sack-dollar me-1 text-success"></i>Cuotas y Ahorros
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-3 py-1.5 fw-semibold" id="tab-actividades-tab" data-bs-toggle="pill" data-bs-target="#tab-actividades" type="button">
                                <i class="fa-solid fa-utensils me-1 text-info"></i>Actividades (Tamales/Rifas)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-3 py-1.5 fw-semibold" id="tab-prestamos-tab" data-bs-toggle="pill" data-bs-target="#tab-prestamos" type="button">
                                <i class="fa-solid fa-hand-holding-dollar me-1 text-warning"></i>Préstamos e Intereses
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-3 py-1.5 fw-semibold" id="tab-entregas-tab" data-bs-toggle="pill" data-bs-target="#tab-entregas" type="button">
                                <i class="fa-solid fa-signature me-1 text-primary"></i>Entregas y Comprobantes
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="tabExpedienteContent">
                        <!-- Tab 1: Cuotas y Ahorros -->
                        <div class="tab-pane fade show active" id="tab-cuotas" role="tabpanel">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle fs-7 mb-0">
                                        <thead class="bg-dark text-white font-outfit">
                                            <tr>
                                                <th>Reunión / Quincena</th>
                                                <th>Fecha</th>
                                                <th>Cuota Base ($40.000)</th>
                                                <th>Ahorro Voluntario</th>
                                                <th>Total Aportado</th>
                                                <th>Estado</th>
                                                <th>Observación / Multas</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyExpCuotas"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Actividades Comunitarias -->
                        <div class="tab-pane fade" id="tab-actividades" role="tabpanel">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle fs-7 mb-0">
                                        <thead class="bg-dark text-white font-outfit">
                                            <tr>
                                                <th>Actividad</th>
                                                <th>Fecha Realización</th>
                                                <th>Cuota Asignada</th>
                                                <th>Monto Pagado</th>
                                                <th>Saldo Pendiente</th>
                                                <th>Estado</th>
                                                <th class="text-center">Abonos</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyExpActividades"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Préstamos e Intereses -->
                        <div class="tab-pane fade" id="tab-prestamos" role="tabpanel">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle fs-7 mb-0">
                                        <thead class="bg-dark text-white font-outfit">
                                            <tr>
                                                <th>Fecha Inicio</th>
                                                <th>Monto Prestado</th>
                                                <th>Tasa Interés %</th>
                                                <th>Capital Pagado</th>
                                                <th>Saldo Capital</th>
                                                <th>Intereses Pagados</th>
                                                <th>Estado</th>
                                                <th class="text-center">Historial Abonos</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyExpPrestamos"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 4: Entregas y Comprobantes -->
                        <div class="tab-pane fade" id="tab-entregas" role="tabpanel">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle fs-7 mb-0">
                                        <thead class="bg-dark text-white font-outfit">
                                            <tr>
                                                <th>Tipo Entrega</th>
                                                <th>Fecha y Hora</th>
                                                <th>Monto Entregado</th>
                                                <th>Reunión / Quincena</th>
                                                <th class="text-center">Evidencia Firma</th>
                                                <th class="text-center">Comprobante Foto</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyExpEntregas"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill fw-bold" data-bs-dismiss="modal">Cerrar Hoja del Socio</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Filtro de Búsqueda Rápida
    const inputBuscar = document.getElementById('buscarSocioAdmin');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            document.querySelectorAll('.socio-admin-row').forEach(row => {
                const text = row.getAttribute('data-search');
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // Pasar datos al modal de edición
    const btnsEditar = document.querySelectorAll('.btnEditarSocio');
    btnsEditar.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_socio_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_nombre_completo').value = btn.getAttribute('data-nombre');
            document.getElementById('edit_cedula').value = btn.getAttribute('data-cedula');
            document.getElementById('edit_telefono').value = btn.getAttribute('data-telefono');
            document.getElementById('edit_fecha_nacimiento').value = btn.getAttribute('data-nacimiento');
            document.getElementById('edit_rol_id').value = btn.getAttribute('data-rol');
            document.getElementById('edit_password').value = '';
        });
    });

    // Cargar Hoja del Socio / Expediente via AJAX
    const btnsExpediente = document.querySelectorAll('.btnVerExpediente');
    btnsExpediente.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const socioId = btn.getAttribute('data-id');
            const spinner = document.getElementById('expedienteSpinner');
            const content = document.getElementById('expedienteContent');

            spinner.classList.remove('d-none');
            content.classList.add('d-none');

            document.getElementById('exp_socio_id_badge').innerText = `#${socioId}`;
            document.getElementById('exp_socio_nombre').innerText = 'Cargando...';

            fetch(`/admin/socios/expediente-json?socio_id=${socioId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.socio) {
                        alert(data.message || 'Error al cargar expediente.');
                        return;
                    }

                    const s = data.socio;
                    const r = data.resumen;

                    document.getElementById('exp_socio_nombre').innerText = s.nombre_completo;
                    document.getElementById('exp_socio_detalles').innerText = `C.C. ${s.cedula} | Tel: ${s.telefono || 'Sin registro'} | Rol: ${s.rol_nombre} | Nac: ${s.fecha_nacimiento || 'N/A'}`;

                    // KPIs
                    document.getElementById('exp_kpi_ahorro').innerText = `$${new Intl.NumberFormat('es-CO').format(r.total_ahorrado)} COP`;
                    document.getElementById('exp_kpi_ahorro_det').innerText = `Cuotas: $${new Intl.NumberFormat('es-CO').format(r.total_cuotas)} | Extra: $${new Intl.NumberFormat('es-CO').format(r.total_ahorro_extra)}`;

                    document.getElementById('exp_kpi_interes').innerText = `$${new Intl.NumberFormat('es-CO').format(r.total_interes_generado)} COP`;
                    document.getElementById('exp_kpi_interes_det').innerText = `Meta $400.000 (${r.porcentaje_meta}%)`;

                    const totalDeudas = (r.deuda_prestamos_capital || 0) + (r.deuda_actividades || 0);
                    document.getElementById('exp_kpi_deuda').innerText = `$${new Intl.NumberFormat('es-CO').format(totalDeudas)} COP`;
                    document.getElementById('exp_kpi_deuda_det').innerText = `Préstamos: $${new Intl.NumberFormat('es-CO').format(r.deuda_prestamos_capital || 0)} | Actividades: $${new Intl.NumberFormat('es-CO').format(r.deuda_actividades || 0)}`;

                    let totalMontoEntregas = 0;
                    (data.entregas || []).forEach(e => { totalMontoEntregas += parseFloat(e.monto_entregado || 0); });
                    document.getElementById('exp_kpi_entregas').innerText = `$${new Intl.NumberFormat('es-CO').format(totalMontoEntregas)} COP`;
                    document.getElementById('exp_kpi_entregas_det').innerText = `${(data.entregas || []).length} entregas registradas`;

                    // Render Tab 1: Cuotas
                    const tbodyCuotas = document.getElementById('tbodyExpCuotas');
                    if (!data.cuotas || data.cuotas.length === 0) {
                        tbodyCuotas.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No hay registros de cuotas de reunión aún.</td></tr>';
                    } else {
                        let html = '';
                        data.cuotas.forEach(c => {
                            const cuotaVal = parseFloat(c.monto_cuota || 0);
                            const extraVal = parseFloat(c.monto_ahorro_extra || 0);
                            const tot = cuotaVal + extraVal;
                            const isPagada = c.cuota_pagada == 1;
                            html += `
                                <tr>
                                    <td class="fw-bold">Q${c.numero_quincena}</td>
                                    <td>${c.fecha_reunion}</td>
                                    <td class="fw-bold text-dark">$${new Intl.NumberFormat('es-CO').format(cuotaVal)}</td>
                                    <td class="text-success fw-bold">$${new Intl.NumberFormat('es-CO').format(extraVal)}</td>
                                    <td class="fw-bold font-outfit text-primary">$${new Intl.NumberFormat('es-CO').format(tot)}</td>
                                    <td><span class="badge bg-${isPagada ? 'success' : 'danger'}">${isPagada ? 'Pagada' : 'Pendiente/Mora'}</span></td>
                                    <td>${c.observaciones || '-'}</td>
                                </tr>
                            `;
                        });
                        tbodyCuotas.innerHTML = html;
                    }

                    // Render Tab 2: Actividades
                    const tbodyAct = document.getElementById('tbodyExpActividades');
                    if (!data.actividades || data.actividades.length === 0) {
                        tbodyAct.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">El socio no ha participado en actividades especiales aún.</td></tr>';
                    } else {
                        let html = '';
                        data.actividades.forEach(a => {
                            const cuota = parseFloat(a.cuota_asignada || 0);
                            const pagado = parseFloat(a.monto_pagado || 0);
                            const saldo = cuota - pagado;
                            const isPagado = a.estado_pago === 'PAGADO' || saldo <= 0;
                            const abonos = a.abonos || [];

                            let abonosList = '';
                            if (abonos.length > 0) {
                                abonosList = '<ul class="list-unstyled mb-0 fs-8">';
                                abonos.forEach(ab => {
                                    abonosList += `<li><i class="fa-solid fa-angle-right text-info me-1"></i>$${new Intl.NumberFormat('es-CO').format(parseFloat(ab.monto_abono))} (${ab.fecha_abono})</li>`;
                                });
                                abonosList += '</ul>';
                            } else {
                                abonosList = '<span class="text-muted fs-8">Sin abonos</span>';
                            }

                            html += `
                                <tr>
                                    <td class="fw-bold">${a.nombre_actividad}</td>
                                    <td>${a.fecha_actividad}</td>
                                    <td class="fw-bold">$${new Intl.NumberFormat('es-CO').format(cuota)}</td>
                                    <td class="text-success fw-bold">$${new Intl.NumberFormat('es-CO').format(pagado)}</td>
                                    <td class="text-danger fw-bold">$${new Intl.NumberFormat('es-CO').format(Math.max(0, saldo))}</td>
                                    <td><span class="badge bg-${isPagado ? 'success' : 'warning text-dark'}">${isPagado ? 'Al Día' : 'Pendiente'}</span></td>
                                    <td>${abonosList}</td>
                                </tr>
                            `;
                        });
                        tbodyAct.innerHTML = html;
                    }

                    // Render Tab 3: Préstamos
                    const tbodyLoans = document.getElementById('tbodyExpPrestamos');
                    if (!data.prestamos || data.prestamos.length === 0) {
                        tbodyLoans.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">El socio no tiene historial de préstamos solicitados.</td></tr>';
                    } else {
                        let html = '';
                        data.prestamos.forEach(p => {
                            const prestado = parseFloat(p.monto_prestado || 0);
                            const capPag = parseFloat(p.capital_pagado || 0);
                            const intPag = parseFloat(p.interes_pagado || 0);
                            const saldoCap = Math.max(0, prestado - capPag);
                            const cuotas = p.cuotas || [];

                            let cuotasList = '';
                            if (cuotas.length > 0) {
                                cuotasList = '<ul class="list-unstyled mb-0 fs-8">';
                                cuotas.forEach(c => {
                                    cuotasList += `<li><i class="fa-solid fa-check text-success me-1"></i>Cap: $${new Intl.NumberFormat('es-CO').format(parseFloat(c.monto_capital_pagado))} | Int: $${new Intl.NumberFormat('es-CO').format(parseFloat(c.monto_interes_pagado))} (${c.fecha_abono})</li>`;
                                });
                                cuotasList += '</ul>';
                            } else {
                                cuotasList = '<span class="text-muted fs-8">Sin abonos</span>';
                            }

                            html += `
                                <tr>
                                    <td>${p.fecha_inicio}</td>
                                    <td class="fw-bold">$${new Intl.NumberFormat('es-CO').format(prestado)}</td>
                                    <td class="fw-bold text-primary">${p.tasa_interes_mensual}%</td>
                                    <td class="text-success fw-bold">$${new Intl.NumberFormat('es-CO').format(capPag)}</td>
                                    <td class="text-danger fw-bold">$${new Intl.NumberFormat('es-CO').format(saldoCap)}</td>
                                    <td class="text-warning fw-bold">$${new Intl.NumberFormat('es-CO').format(intPag)}</td>
                                    <td><span class="badge bg-${p.estado === 'PAGADO' ? 'success' : 'warning text-dark'}">${p.estado}</span></td>
                                    <td>${cuotasList}</td>
                                </tr>
                            `;
                        });
                        tbodyLoans.innerHTML = html;
                    }

                    // Render Tab 4: Entregas
                    const tbodyEntregas = document.getElementById('tbodyExpEntregas');
                    if (!data.entregas || data.entregas.length === 0) {
                        tbodyEntregas.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No hay registros de entregas ni desembolsos firmados.</td></tr>';
                    } else {
                        let html = '';
                        data.entregas.forEach(e => {
                            const monto = parseFloat(e.monto_entregado || 0);
                            const firmaImg = e.firma_digital_path ? `<a href="${e.firma_digital_path}" target="_blank"><img src="${e.firma_digital_path}" class="border rounded bg-white p-1" style="height: 45px;" alt="Firma"></a>` : '<span class="text-muted fs-8">Sin firma</span>';
                            const fotoImg = e.foto_evidencia_path ? `<a href="${e.foto_evidencia_path}" target="_blank"><img src="${e.foto_evidencia_path}" class="border rounded p-1" style="height: 45px; object-fit: cover;" alt="Comprobante"></a>` : '<span class="text-muted fs-8">Sin foto</span>';
                            
                            html += `
                                <tr>
                                    <td><span class="badge bg-${e.tipo_beneficio === 'PRESTAMO' ? 'primary' : (e.tipo_beneficio === 'RONDA' ? 'success' : 'warning text-dark')}">${e.tipo_beneficio}</span></td>
                                    <td>${new Date(e.fecha_entrega).toLocaleString('es-CO')}</td>
                                    <td class="fw-bold text-success">$${new Intl.NumberFormat('es-CO').format(monto)}</td>
                                    <td>Quincena Q${e.numero_quincena || '-'}</td>
                                    <td class="text-center">${firmaImg}</td>
                                    <td class="text-center">${fotoImg}</td>
                                </tr>
                            `;
                        });
                        tbodyEntregas.innerHTML = html;
                    }

                    spinner.classList.add('d-none');
                    content.classList.remove('d-none');
                })
                .catch(() => {
                    spinner.classList.add('d-none');
                    alert('Error de conexión al cargar la información del socio.');
                });
        });
    });
});
</script>
