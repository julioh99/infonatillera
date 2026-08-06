<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-6 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-users-gear text-primary"></i>
            Gestión de Socios e Información Personal
        </h2>
        <p class="text-muted m-0 fs-7">Consulta y actualización de datos personales, teléfonos, fechas de cumpleaños y roles.</p>
    </div>
    <div class="col-12 col-md-6 text-md-end">
        <button type="button" class="btn btn-outline-dark rounded-pill fw-semibold btn-sm shadow-sm" data-bs-toggle="collapse" data-bs-target="#collapseCumpleanos">
            <i class="fa-solid fa-cake-candles text-danger me-1"></i>Ver Cumpleaños de Socios
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
                            <div class="fw-bold text-dark font-outfit"><?= htmlspecialchars($s['nombre_completo']) ?></div>
                            <small class="text-muted">C.C. <?= htmlspecialchars($s['cedula']) ?></small>
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
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-user-pen me-2"></i>Editar Información del Socio</h5>
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
                            <input type="text" name="telefono" id="edit_telefono" class="form-control" placeholder="Ej: 3001234567">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_fecha_nacimiento" class="form-label fw-semibold fs-7">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento" class="form-control">
                        </div>
                        <div class="col-6">
                            <label for="edit_rol_id" class="form-label fw-semibold fs-7">Rol Asignado</label>
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
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4">Guardar Cambios</button>
                </div>
            </form>
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
});
</script>
