<link rel="stylesheet" href="/css/pages/gestion-dinero.css">

<div class="gd-head">
    <div class="gd-head__main">
        <span class="gd-head__icon gd-head__icon--gold" aria-hidden="true"><i class="fa-solid fa-hand-holding-dollar"></i></span>
        <div class="gd-head__text">
            <h2 class="nt-page-title">Gestión de Préstamos e Intereses</h2>
            <p class="nt-page-sub">Control directo a socios, abonos a capital/interés e historial de cuotas.</p>
        </div>
    </div>
    <div class="gd-head__actions">
        <?php if (in_array($userRole, ['Presidente', 'Secretaria General'])): ?>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalTopeCredit">
                <i class="fa-solid fa-sliders me-2"></i>Ajustar Tope Socio
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoPrestamo">
            <i class="fa-solid fa-plus me-2"></i>Nuevo Préstamo
        </button>
    </div>
</div>

<!-- Barra de Filtros y Búsqueda en Préstamos -->
<div class="gd-toolbar">
    <div class="row g-2 align-items-center">
        <div class="col-12 col-md-8">
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="buscarPrestamo" class="form-control" value="<?= htmlspecialchars($_GET['search'] ?? ($_GET['search_query'] ?? '')) ?>" placeholder="Buscar por socio, cédula, referencia o reunión (ej: R1, R2, Juan)..." aria-label="Buscar préstamo">
            </div>
        </div>
        <div class="col-12 col-md-4">
            <select id="filtrarEstadoPrestamo" class="form-select fw-semibold" aria-label="Filtrar por estado">
                <option value="">-- Todos los Préstamos --</option>
                <option value="ACTIVO">Solo Activos</option>
                <option value="PAGADO">Solo Pagados</option>
                <option value="AUTOPRESTAMO">Solo Autopréstamos</option>
            </select>
        </div>
    </div>
</div>

<!-- Lista de Préstamos -->
<div class="gd-surface">
    <div class="table-responsive">
        <table class="table table-hover align-middle gd-table gd-cards">
            <thead>
                <tr>
                    <th>Socio Deudor</th>
                    <th>Reunión</th>
                    <th>Referencia / Alias</th>
                    <th class="text-end">Monto Prestado</th>
                    <th class="text-end">Tasa %</th>
                    <th class="text-end">Capital Pagado</th>
                    <th class="text-end">Interés Pagado</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prestamos)): ?>
                    <tr>
                        <td colspan="9" class="gd-empty">No hay préstamos registrados aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($prestamos as $p):
                        $saldoCapital = (float)$p['monto_prestado'] - (float)$p['total_capital_pagado'];
                        $refText = !empty($p['nombre_referencia']) ? $p['nombre_referencia'] : (!empty($p['tercero_nombre']) ? $p['tercero_nombre'] : '');
                        $numR = !empty($p['numero_quincena']) ? 'R' . $p['numero_quincena'] : '';
                    ?>
                        <tr class="prestamo-row"
                            data-search="<?= strtolower(htmlspecialchars($p['deudor_nombre'] . ' ' . $p['deudor_cedula'] . ' ' . $refText . ' ' . $numR . ' ' . ($p['es_autoprestamo'] ? 'autoprestamo' : ''))) ?>"
                            data-estado="<?= $p['estado'] ?>"
                            data-tipo="<?= $p['tipo_prestamo'] ?>">
                            <td class="gd-cell-title">
                                <div class="gd-name"><?= htmlspecialchars($p['deudor_nombre']) ?></div>
                                <small class="gd-sub">C.C. <?= htmlspecialchars($p['deudor_cedula']) ?></small>
                            </td>
                            <td data-label="Reunión">
                                <?php $fechaPDisplay = !empty($p['fecha_prestamo']) ? $p['fecha_prestamo'] : (!empty($p['fecha_reunion']) ? $p['fecha_reunion'] : date('Y-m-d', strtotime($p['fecha_inicio']))); ?>
                                <div>
                                    <div class="gd-date"><i class="fa-regular fa-calendar me-1"></i><?= date('d/m/Y', strtotime($fechaPDisplay)) ?></div>
                                    <?php if (!empty($p['numero_quincena'])): ?>
                                        <span class="badge bg-primary font-outfit fs-8 mt-1">R<?= $p['numero_quincena'] ?></span>
                                    <?php else: ?>
                                        <small class="gd-sub mt-1">Sin Reunión</small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Referencia">
                                <?php if (!empty($refText)): ?>
                                    <span class="gd-ref">
                                        <i class="fa-solid fa-bookmark"></i><?= htmlspecialchars($refText) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted fs-7">---</span>
                                <?php endif; ?>
                            </td>
                            <td class="gd-num fw-bold font-outfit" data-label="Monto prestado">$<?= number_format($p['monto_prestado'], 0, ',', '.') ?></td>
                            <td class="gd-num" data-label="Tasa"><span class="badge bg-info"><?= number_format($p['tasa_interes_mensual'], 1) ?>%</span></td>
                            <td class="gd-num fw-semibold gd-pos" data-label="Capital pagado">$<?= number_format($p['total_capital_pagado'], 0, ',', '.') ?></td>
                            <td class="gd-num fw-semibold gd-gold" data-label="Interés pagado">$<?= number_format($p['total_interes_pagado'], 0, ',', '.') ?>
                                <?php $mesesCob = (int)($p['meses_cobrados'] ?? 0); $mesTxt = $mesesCob === 1 ? 'mes' : 'meses'; ?>
                                <?php if (!$p['anulado_sin_interes'] && (float)($p['interes_pendiente'] ?? 0) > 0): ?>
                                    <small class="gd-sub d-block" title="Interés mensual progresivo sobre el capital pendiente">Pendiente: $<?= number_format($p['interes_pendiente'], 0, ',', '.') ?> · <?= $mesesCob ?> <?= $mesTxt ?></small>
                                <?php elseif (!$p['anulado_sin_interes'] && $mesesCob > 0): ?>
                                    <small class="gd-sub d-block">Al día · <?= $mesesCob ?> <?= $mesTxt ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="gd-cell-state" data-label="Estado">
                                <div class="text-md-start">
                                    <?php if ($p['anulado_sin_interes']): ?>
                                        <span class="badge bg-secondary">Anulado 24h</span>
                                    <?php elseif ($p['estado'] === 'PAGADO'): ?>
                                        <span class="badge bg-success">Pagado ✔</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Activo (Bal: $<?= number_format($saldoCapital, 0, ',', '.') ?>)</span>
                                    <?php endif; ?>
                                    <?php if ($p['es_autoprestamo']): ?>
                                        <small class="gd-flag d-flex"><i class="fa-solid fa-bolt"></i>Autopréstamo</small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="gd-cell-actions text-end">
                                <div class="gd-actions">
                                    <?php if ($p['estado'] === 'ACTIVO' && !$p['anulado_sin_interes']): ?>
                                        <button type="button" class="btn btn-outline-success rounded-pill gd-act btnAbonar"
                                                data-id="<?= $p['id'] ?>"
                                                data-deudor="<?= htmlspecialchars($p['deudor_nombre']) ?>"
                                                data-saldo="<?= $saldoCapital ?>"
                                                data-interes-pendiente="<?= round((float)($p['interes_pendiente'] ?? 0)) ?>"
                                                data-meses-interes="<?= (int)($p['meses_cobrados'] ?? 0) ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalAbono">
                                            <i class="fa-solid fa-coins"></i>Abonar
                                        </button>
                                    <?php endif; ?>

                                    <!-- Ver / Editar Cuotas -->
                                    <button type="button" class="btn btn-outline-primary rounded-pill gd-act btnVerCuotas"
                                            data-id="<?= $p['id'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalHistorialCuotas">
                                        <i class="fa-solid fa-list-check"></i>Cuotas
                                    </button>

                                    <!-- Firma Digital y Foto Evidencia -->
                                    <?php $tieneFirmaFoto = !empty($p['tiene_firma_foto']) && $p['tiene_firma_foto'] > 0; ?>
                                    <a href="/admin/entregas?tipo=PRESTAMO&socio_id=<?= $p['socio_deudor_id'] ?>&monto=<?= $p['monto_prestado'] ?>&reunion_id=<?= $p['reunion_id'] ?? '' ?>"
                                       class="btn <?= $tieneFirmaFoto ? 'btn-warning text-dark font-outfit fw-bold' : 'btn-outline-secondary' ?> rounded-pill gd-act"
                                       title="<?= $tieneFirmaFoto ? 'Firma/Foto Registrada - Click para ver/editar en Módulo Entregas' : 'Registrar Firma Digital y Foto Evidencia del Desembolso' ?>">
                                        <i class="fa-solid <?= $tieneFirmaFoto ? 'fa-circle-check' : 'fa-signature' ?>"></i>Firma/Foto<?= $tieneFirmaFoto ? ' ✔' : '' ?>
                                    </a>

                                    <!-- Editar Préstamo (Presidente / Sec General) -->
                                    <?php if (in_array($userRole, ['Presidente', 'Secretaria General'])): ?>
                                        <button type="button" class="btn btn-outline-secondary rounded-pill gd-act btnEditarPrestamo"
                                                data-id="<?= $p['id'] ?>"
                                                data-deudor-id="<?= $p['socio_deudor_id'] ?>"
                                                data-reunion-id="<?= $p['reunion_id'] ?? '' ?>"
                                                data-fecha-prestamo="<?= htmlspecialchars($fechaPDisplay) ?>"
                                                data-monto="<?= $p['monto_prestado'] ?>"
                                                data-tasa="<?= $p['tasa_interes_mensual'] ?>"
                                                data-ref="<?= htmlspecialchars($refText) ?>"
                                                data-estado="<?= $p['estado'] ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarPrestamo">
                                            <i class="fa-solid fa-pen"></i>Editar
                                        </button>
                                    <?php endif; ?>

                                    <!-- Eliminar Préstamo (SOLO Presidente) -->
                                    <?php if ($userRole === 'Presidente'): ?>
                                        <form action="/admin/prestamos/eliminar" method="POST" class="d-inline" onsubmit="return confirm('¿⚠️ ATENCIÓN: Estás seguro de eliminar el Préstamo N° <?= $p['id'] ?> de <?= htmlspecialchars($p['deudor_nombre']) ?>?\n\nEsta acción eliminará el registro y sus cuotas asociadas permanentemente.')">
                                            <input type="hidden" name="prestamo_id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="search_query" value="<?= htmlspecialchars($_GET['search'] ?? ($_GET['search_query'] ?? '')) ?>">
                                            <button type="submit" class="btn btn-outline-danger rounded-pill gd-act" title="Borrar préstamo (Permiso exclusivo de Presidente)">
                                                <i class="fa-solid fa-trash"></i>Borrar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nuevo Préstamo -->
<div class="modal fade" id="modalNuevoPrestamo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content gd-modal">
            <div class="modal-header gd-mh gd-tone-warning">
                <h5 class="modal-title"><i class="fa-solid fa-hand-holding-dollar"></i>Registrar Nuevo Préstamo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="/admin/prestamos/guardar" method="POST" enctype="multipart/form-data" id="formNuevoPrestamo">
                <input type="hidden" name="search_query" class="search-query-hidden">
                <input type="hidden" name="firma_base64" id="firma_base64_prestamo">
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-7">
                            <label for="socio_deudor_id" class="form-label">Socio Deudor <span class="text-danger">*</span></label>
                            <div class="input-group mb-2">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="buscarSocioDeudorInput" class="form-control" placeholder="Filtrar socio por nombre o cédula...">
                            </div>
                            <select name="socio_deudor_id" id="socio_deudor_id" class="form-select" required>
                                <option value="">-- Seleccionar Socio --</option>
                                <?php foreach ($socios as $s): ?>
                                    <option value="<?= $s['id'] ?>" data-search="<?= strtolower(htmlspecialchars($s['nombre_completo'] . ' ' . $s['cedula'])) ?>">
                                        <?= htmlspecialchars($s['nombre_completo']) ?> (C.C. <?= htmlspecialchars($s['cedula']) ?> - Tope: $<?= number_format($s['tope_prestamo_personalizado'], 0, ',', '.') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-5">
                            <label for="fecha_prestamo_nuevo" class="form-label">Fecha del Préstamo <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_prestamo" id="fecha_prestamo_nuevo" class="form-control fw-bold" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reunion_id_nuevo" class="form-label">Reunión Asociada (Opcional)</label>
                        <select name="reunion_id" id="reunion_id_nuevo" class="form-select">
                            <option value="">-- General / Sin Reunión Específica --</option>
                            <?php foreach ($reuniones as $r):
                                $isAct = (!empty($reunionActual) && $reunionActual['id'] == $r['id']) ? 'selected' : '';
                                $lblEstado = ($r['estado'] === 'LLAMADO_CERRADO') ? ' (Llamado Cerrado ★)' : '';
                            ?>
                                <option value="<?= $r['id'] ?>" <?= $isAct ?>>R<?= $r['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?><?= $lblEstado ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="gd-field-hint">Asocia este préstamo al acta/reunión seleccionada (opcional).</small>
                    </div>

                    <div class="mb-3">
                        <label for="nombre_referencia" class="form-label">Persona Referente / Nombre de Referencia (Opcional)</label>
                        <input type="text" name="nombre_referencia" id="nombre_referencia" class="form-control" placeholder="Ej: Para negocio de empanadas / Ref: Juan">
                        <small class="gd-field-hint">Sirve como alias para identificar rápidamente el motivo o referente del préstamo.</small>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-7">
                            <label for="monto_prestado" class="form-label">Monto a Prestar (COP)</label>
                            <input type="text" name="monto_prestado" id="monto_prestado" class="form-control money-input fw-bold" inputmode="numeric" placeholder="Ej: 500.000" required>
                        </div>
                        <div class="col-5">
                            <label for="tasa_interes_mensual" class="form-label">Tasa Interés (%)</label>
                            <input type="number" step="0.5" min="0" name="tasa_interes_mensual" id="tasa_interes_mensual" class="form-control fw-bold" inputmode="decimal" value="10.0">
                        </div>
                    </div>

                    <!-- Firma Digital & Foto Evidencia en un solo formulario -->
                    <div class="gd-callout">
                        <h6 class="mb-3">
                            <i class="fa-solid fa-file-signature text-warning me-1"></i>Constancia de Entrega Directa (Firma & Foto Evidencia)
                        </h6>
                        <div class="row g-3">
                            <div class="col-12 col-md-7">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                    <label class="form-label mb-0">
                                        <i class="fa-solid fa-pen-nib text-primary me-1"></i>Firma Digital del Socio (Opcional):
                                    </label>
                                    <button type="button" class="btn btn-sm btn-outline-danger px-3" id="btnLimpiarFirmaPrestamo">
                                        <i class="fa-solid fa-eraser me-1"></i>Limpiar
                                    </button>
                                </div>
                                <div class="gd-sign-box text-center">
                                    <canvas id="canvasFirmaPrestamo" width="340" height="130" class="w-100 cursor-crosshair" style="touch-action: none; background-color: #ffffff;"></canvas>
                                </div>
                                <small class="gd-field-hint">Si el socio firma aquí, la constancia de entrega se creará automáticamente.</small>
                            </div>
                            <div class="col-12 col-md-5">
                                <label for="foto_evidencia_prestamo" class="form-label">
                                    <i class="fa-solid fa-camera text-info me-1"></i>Foto Evidencia de Entrega:
                                </label>
                                <input type="file" name="foto_evidencia" id="foto_evidencia_prestamo" class="form-control mb-2" accept="image/*" capture="environment">
                                <small class="gd-field-hint">Sube o toma una foto del socio recibiendo el dinero.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Crear Préstamo y Constancia</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Préstamo (Presidente / Secretaria General) -->
<?php if (in_array($userRole, ['Presidente', 'Secretaria General'])): ?>
<div class="modal fade" id="modalEditarPrestamo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content gd-modal">
            <div class="modal-header gd-mh gd-tone-dark">
                <h5 class="modal-title"><i class="fa-solid fa-pen-to-square"></i>Editar Préstamo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="/admin/prestamos/actualizar" method="POST">
                <input type="hidden" name="search_query" class="search-query-hidden">
                <input type="hidden" name="prestamo_id" id="edit_prestamo_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_socio_deudor_id" class="form-label">Socio Deudor</label>
                        <select name="socio_deudor_id" id="edit_socio_deudor_id" class="form-select" required>
                            <?php foreach ($socios as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_completo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="edit_fecha_prestamo" class="form-label">Fecha del Préstamo <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_prestamo" id="edit_fecha_prestamo" class="form-control fw-bold" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="edit_reunion_id" class="form-label">Reunión Asociada</label>
                            <select name="reunion_id" id="edit_reunion_id" class="form-select">
                                <option value="">-- Sin Reunión Específica --</option>
                                <?php foreach ($reuniones as $r): ?>
                                    <option value="<?= $r['id'] ?>">R<?= $r['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_nombre_referencia" class="form-label">Persona Referente / Alias</label>
                        <input type="text" name="nombre_referencia" id="edit_nombre_referencia" class="form-control">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-7">
                            <label for="edit_monto_prestado" class="form-label">Monto Prestado (COP)</label>
                            <input type="text" name="monto_prestado" id="edit_monto_prestado" class="form-control money-input fw-bold" inputmode="numeric" required>
                        </div>
                        <div class="col-5">
                            <label for="edit_tasa_interes_mensual" class="form-label">Tasa Interés (%)</label>
                            <input type="number" step="0.5" min="0" name="tasa_interes_mensual" id="edit_tasa_interes_mensual" class="form-control fw-bold" inputmode="decimal" required>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label for="edit_estado" class="form-label">Estado del Préstamo</label>
                        <select name="estado" id="edit_estado" class="form-select fw-bold">
                            <option value="ACTIVO">ACTIVO (Pendiente de Pago)</option>
                            <option value="PAGADO">PAGADO (Finalizado)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Historial y Edición de Cuotas / Abonos -->
<div class="modal fade" id="modalHistorialCuotas" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content gd-modal">
            <div class="modal-header gd-mh gd-tone-primary">
                <h5 class="modal-title"><i class="fa-solid fa-list-check"></i>Historial de Cuotas / Abonos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="gd-person">
                    <div class="min-w-0">
                        <h6 class="gd-person__name" id="cuotas_deudor_nombre">---</h6>
                        <small class="text-muted" id="cuotas_prestamo_resumen">---</small>
                    </div>
                    <div class="gd-person__chips">
                        <span id="cuotas_estado_badge" class="badge bg-secondary font-outfit fs-7">---</span>
                        <span class="badge bg-success font-outfit fs-7" id="cuotas_total_abonos">$0 COP</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle gd-table mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Capital Pagado</th>
                                <th>Interés Pagado</th>
                                <th>Registrado Por</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tblCuotasBody">
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">Cargando historial...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registrar Abono Individual -->
<div class="modal fade" id="modalAbono" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content gd-modal">
            <div class="modal-header gd-mh gd-tone-success">
                <h5 class="modal-title"><i class="fa-solid fa-coins"></i>Registrar Abono a Préstamo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="/admin/prestamos/abono" method="POST">
                <input type="hidden" name="search_query" class="search-query-hidden">
                <input type="hidden" name="prestamo_id" id="abono_prestamo_id">
                <div class="modal-body">
                    <div class="gd-saldo-row">
                        <div class="min-w-0">
                            <span class="gd-bal__label">Socio</span>
                            <strong id="abono_deudor_nombre" class="gd-person__name d-block"></strong>
                        </div>
                        <div class="text-sm-end">
                            <span class="gd-bal__label">Saldo pendiente capital</span>
                            <strong id="abono_saldo_capital" class="gd-total d-block"></strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="abono_reunion_id" class="form-label">Reunión Correspondiente</label>
                        <select name="reunion_id" id="abono_reunion_id" class="form-select fw-bold">
                            <option value="">-- Autodetectar por Fecha --</option>
                            <?php if (!empty($reuniones)): ?>
                                <?php foreach ($reuniones as $r):
                                    $isActA = (!empty($reunionActual) && $reunionActual['id'] == $r['id']) ? 'selected' : '';
                                    $lblEstado = ($r['estado'] === 'LLAMADO_CERRADO') ? ' (Llamado Cerrado ★)' : '';
                                ?>
                                    <option value="<?= $r['id'] ?>" <?= $isActA ?>>R<?= $r['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?><?= $lblEstado ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="gd-field-hint">Vincula directamente este abono al arqueo de caja de la reunión seleccionada.</small>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="monto_interes_pagado" class="form-label">Abono a Intereses (COP)</label>
                            <input type="text" name="monto_interes_pagado" id="monto_interes_pagado" class="form-control money-input gd-gold fw-bold" inputmode="numeric" placeholder="0">
                            <small class="gd-field-hint" id="abono_interes_hint"></small>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="monto_capital_pagado" class="form-label">Abono a Capital (COP)</label>
                            <input type="text" name="monto_capital_pagado" id="monto_capital_pagado" class="form-control money-input gd-pos fw-bold" inputmode="numeric" placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success px-4">Guardar Abono</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Cuota Específica -->
<?php if (in_array($userRole, ['Presidente', 'Secretaria General'])): ?>
<div class="modal fade" id="modalEditarCuotaItem" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content gd-modal">
            <div class="modal-header gd-mh gd-tone-primary">
                <h5 class="modal-title"><i class="fa-solid fa-pen"></i>Editar Cuota / Abono</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="/admin/prestamos/abono/actualizar" method="POST">
                <input type="hidden" name="search_query" class="search-query-hidden">
                <input type="hidden" name="abono_id" id="edit_abono_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_fecha_abono" class="form-label">Fecha del Abono</label>
                        <input type="text" name="fecha_abono" id="edit_fecha_abono" class="form-control" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="edit_monto_capital_pagado" class="form-label">Monto Capital (COP)</label>
                            <input type="text" name="monto_capital_pagado" id="edit_monto_capital_pagado" class="form-control money-input gd-pos fw-bold" inputmode="numeric" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="edit_monto_interes_pagado" class="form-label">Monto Interés (COP)</label>
                            <input type="text" name="monto_interes_pagado" id="edit_monto_interes_pagado" class="form-control money-input gd-gold fw-bold" inputmode="numeric" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Actualizar Cuota</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Excepción Tope de Crédito -->
<?php if (in_array($userRole, ['Presidente', 'Secretaria General'])): ?>
<div class="modal fade" id="modalTopeCredit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content gd-modal">
            <div class="modal-header gd-mh gd-tone-dark">
                <h5 class="modal-title"><i class="fa-solid fa-sliders"></i>Excepción de Tope de Crédito</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="/admin/prestamos/actualizar-tope" method="POST">
                <input type="hidden" name="search_query" class="search-query-hidden">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="socio_id_tope" class="form-label">Seleccionar Socio</label>
                        <select name="socio_id" id="socio_id_tope" class="form-select" required>
                            <option value="">-- Seleccionar Socio --</option>
                            <?php foreach ($socios as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_completo']) ?> (Actual: $<?= number_format($s['tope_prestamo_personalizado'], 0, ',', '.') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label for="nuevo_tope" class="form-label">Nuevo Tope Personalizado (COP)</label>
                        <input type="text" name="nuevo_tope" id="nuevo_tope" class="form-control money-input fw-bold" inputmode="numeric" placeholder="Ej: 3.000.000" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Actualizar Tope</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const isCanEdit = <?= json_encode(in_array($userRole, ['Presidente', 'Secretaria General'])) ?>;

document.addEventListener('DOMContentLoaded', () => {
    // Abrir Modal de Abono
    const btnsAbonar = document.querySelectorAll('.btnAbonar');
    btnsAbonar.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const deudor = btn.getAttribute('data-deudor');
            const saldo = btn.getAttribute('data-saldo');
            
            document.getElementById('abono_prestamo_id').value = id;
            document.getElementById('abono_deudor_nombre').innerText = deudor;
            document.getElementById('abono_saldo_capital').innerText = '$' + new Intl.NumberFormat('es-CO').format(saldo);

            // Interés mensual progresivo: el campo se precarga con lo pendiente (el servidor valida los topes)
            const interesPend = Math.round(parseFloat(btn.getAttribute('data-interes-pendiente') || '0')) || 0;
            const meses = parseInt(btn.getAttribute('data-meses-interes') || '0', 10) || 0;
            const inputInteres = document.getElementById('monto_interes_pagado');
            const hintInteres = document.getElementById('abono_interes_hint');
            if (inputInteres) inputInteres.value = interesPend > 0 ? new Intl.NumberFormat('es-CO').format(interesPend) : '';
            if (hintInteres) hintInteres.innerText = interesPend > 0 ? `Interés pendiente: $${new Intl.NumberFormat('es-CO').format(interesPend)} (${meses} ${meses === 1 ? 'mes cobrado' : 'meses cobrados'}). Puedes cambiarlo.` : 'Sin interés pendiente.';
        });
    });

    // Buscador y Filtro Dinámico de Préstamos con Persistencia
    const inputBuscarPrestamo = document.getElementById('buscarPrestamo');
    const selectFiltrarEstado = document.getElementById('filtrarEstadoPrestamo');
    const prestamoRows = document.querySelectorAll('.prestamo-row');

    // Recuperación y sincronización de estado de búsqueda
    const urlParams = new URLSearchParams(window.location.search);
    const searchFromUrl = urlParams.get('search') || urlParams.get('search_query');

    if (searchFromUrl !== null && searchFromUrl !== '') {
        if (inputBuscarPrestamo) inputBuscarPrestamo.value = searchFromUrl;
        localStorage.setItem('prestamos_search_query', searchFromUrl);
    } else if (localStorage.getItem('prestamos_search_query')) {
        const savedSearch = localStorage.getItem('prestamos_search_query');
        if (inputBuscarPrestamo && savedSearch) {
            inputBuscarPrestamo.value = savedSearch;
        }
    }

    if (localStorage.getItem('prestamos_estado_filter')) {
        const savedEstado = localStorage.getItem('prestamos_estado_filter');
        if (selectFiltrarEstado && savedEstado) {
            selectFiltrarEstado.value = savedEstado;
        }
    }

    function syncSearchQueryInputs() {
        const val = inputBuscarPrestamo ? inputBuscarPrestamo.value.trim() : '';
        document.querySelectorAll('.search-query-hidden').forEach(inp => {
            inp.value = val;
        });
    }

    function filterPrestamosTable() {
        const query = inputBuscarPrestamo ? inputBuscarPrestamo.value.toLowerCase().trim() : '';
        const estadoFilter = selectFiltrarEstado ? selectFiltrarEstado.value : '';

        prestamoRows.forEach(row => {
            const searchText = row.getAttribute('data-search') || '';
            const rowEstado = row.getAttribute('data-estado') || '';
            const rowTipo = row.getAttribute('data-tipo') || '';

            const matchQuery = (!query || searchText.includes(query));
            let matchEstado = true;

            if (estadoFilter === 'ACTIVO') {
                matchEstado = (rowEstado === 'ACTIVO');
            } else if (estadoFilter === 'PAGADO') {
                matchEstado = (rowEstado === 'PAGADO');
            } else if (estadoFilter === 'AUTOPRESTAMO') {
                matchEstado = (rowTipo === 'AUTOPRESTAMO');
            }

            row.style.display = (matchQuery && matchEstado) ? '' : 'none';
        });
    }

    if (inputBuscarPrestamo) {
        inputBuscarPrestamo.addEventListener('input', () => {
            localStorage.setItem('prestamos_search_query', inputBuscarPrestamo.value.trim());
            syncSearchQueryInputs();
            filterPrestamosTable();
        });
    }

    if (selectFiltrarEstado) {
        selectFiltrarEstado.addEventListener('change', () => {
            localStorage.setItem('prestamos_estado_filter', selectFiltrarEstado.value);
            filterPrestamosTable();
        });
    }

    // Sincronizar e iniciar filtrado
    syncSearchQueryInputs();
    filterPrestamosTable();

    // Abrir Modal de Editar Préstamo
    const btnsEditarPrestamo = document.querySelectorAll('.btnEditarPrestamo');
    btnsEditarPrestamo.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_prestamo_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_socio_deudor_id').value = btn.getAttribute('data-deudor-id');
            const editReunionEl = document.getElementById('edit_reunion_id');
            if (editReunionEl) editReunionEl.value = btn.getAttribute('data-reunion-id') || '';
            const editFechaEl = document.getElementById('edit_fecha_prestamo');
            if (editFechaEl) editFechaEl.value = btn.getAttribute('data-fecha-prestamo') || '<?= date('Y-m-d') ?>';
            document.getElementById('edit_monto_prestado').value = btn.getAttribute('data-monto');
            document.getElementById('edit_tasa_interes_mensual').value = btn.getAttribute('data-tasa');
            document.getElementById('edit_nombre_referencia').value = btn.getAttribute('data-ref') || '';
            document.getElementById('edit_estado').value = btn.getAttribute('data-estado');
        });
    });

    // Cargar Historial de Cuotas via AJAX
    const btnsVerCuotas = document.querySelectorAll('.btnVerCuotas');
    btnsVerCuotas.forEach(btn => {
        btn.addEventListener('click', () => {
            const prestamoId = btn.getAttribute('data-id');
            const tbody = document.getElementById('tblCuotasBody') || document.getElementById('tbodyHistorialCuotas');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Cargando cuotas...</td></tr>';
            }

            fetch(`/admin/prestamos/abonos-json?prestamo_id=${prestamoId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.prestamo) {
                        if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-danger">Error al cargar datos.</td></tr>';
                        return;
                    }

                    const p = data.prestamo;
                    const elNombre = document.getElementById('cuotas_deudor_nombre');
                    const elResumen = document.getElementById('cuotas_prestamo_resumen');
                    const elBadge = document.getElementById('cuotas_estado_badge');
                    const elTotal = document.getElementById('cuotas_total_abonos');

                    if (elNombre) elNombre.innerText = p.deudor_nombre;
                    if (elResumen) elResumen.innerText = `Préstamo N° ${p.id} - Monto: $${new Intl.NumberFormat('es-CO').format(p.monto_prestado)} - Tasa: ${p.tasa_interes_mensual}%` + (data.interes ? ` - Interés pendiente: $${new Intl.NumberFormat('es-CO').format(Math.round(data.interes.interes_pendiente))} (${data.interes.meses_cobrados} meses cobrados)` : '');
                    if (elBadge) {
                        elBadge.className = `badge bg-${p.estado === 'PAGADO' ? 'success' : 'danger'} font-outfit fs-7`;
                        elBadge.innerText = p.estado;
                    }

                    let totalAbonado = 0;

                    if (!data.abonos || data.abonos.length === 0) {
                        if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Aún no se han registrado abonos a este préstamo.</td></tr>';
                        if (elTotal) elTotal.innerText = '$0 COP';
                        return;
                    }

                    let html = '';
                    data.abonos.forEach(a => {
                        const cap = parseFloat(a.monto_capital_pagado || 0);
                        const intM = parseFloat(a.monto_interes_pagado || 0);
                        totalAbonado += (cap + intM);

                        const capFormatted = new Intl.NumberFormat('es-CO').format(cap);
                        const intFormatted = new Intl.NumberFormat('es-CO').format(intM);
                        const fecha = a.fecha_abono ? a.fecha_abono.substring(0, 16) : '';
                        const usuario = a.registrado_por_nombre || 'Sistema';

                        html += `
                            <tr>
                                <td class="text-nowrap">${fecha}</td>
                                <td class="gd-pos fw-bold gd-num">$${capFormatted}</td>
                                <td class="gd-gold fw-bold gd-num">$${intFormatted}</td>
                                <td>${usuario}</td>
                                ${isCanEdit ? `
                                    <td class="text-end">
                                        <button class="btn btn-outline-primary gd-act me-1 btnEditCuotaItem" aria-label="Editar cuota"
                                                data-id="${a.id}"
                                                data-cap="${cap}"
                                                data-int="${intM}"
                                                data-fecha="${fecha}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form action="/admin/prestamos/abono/eliminar" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta cuota/abono?')">
                                            <input type="hidden" name="abono_id" value="${a.id}">
                                            <input type="hidden" name="search_query" value="${encodeURIComponent(inputBuscarPrestamo ? inputBuscarPrestamo.value.trim() : '')}">
                                            <button type="submit" class="btn btn-outline-danger gd-act" aria-label="Eliminar cuota">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                ` : ''}
                            </tr>
                        `;
                    });

                    if (elTotal) elTotal.innerText = `$${new Intl.NumberFormat('es-CO').format(totalAbonado)} COP`;
                    if (tbody) tbody.innerHTML = html;

                    // Listener para los botones dinámicos de editar cuota
                    document.querySelectorAll('.btnEditCuotaItem').forEach(b => {
                        b.addEventListener('click', () => {
                            document.getElementById('edit_abono_id').value = b.getAttribute('data-id');
                            document.getElementById('edit_monto_capital_pagado').value = b.getAttribute('data-cap');
                            document.getElementById('edit_monto_interes_pagado').value = b.getAttribute('data-int');
                            document.getElementById('edit_fecha_abono').value = b.getAttribute('data-fecha');
                            
                            const modalCuotas = bootstrap.Modal.getInstance(document.getElementById('modalHistorialCuotas'));
                            if (modalCuotas) modalCuotas.hide();

                            const modalEditCuota = new bootstrap.Modal(document.getElementById('modalEditarCuotaItem'));
                            modalEditCuota.show();
                        });
                    });
                })
                .catch(() => {
                    if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-danger">Error de conexión.</td></tr>';
                });
        });
    });

    // Filtro buscador dinámico para selector de socio deudor en modal de préstamos
    document.getElementById('buscarSocioDeudorInput')?.addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        const select = document.getElementById('socio_deudor_id');
        if (!select) return;
        
        let firstMatch = null;
        const options = select.querySelectorAll('option');
        options.forEach(opt => {
            if (!opt.value) return; // Mantener opción por defecto
            const text = (opt.getAttribute('data-search') || opt.textContent).toLowerCase();
            if (text.includes(term)) {
                opt.hidden = false;
                opt.disabled = false;
                if (!firstMatch) firstMatch = opt;
            } else {
                opt.hidden = true;
                opt.disabled = true;
            }
        });

        if (firstMatch && term.length > 0) {
            select.value = firstMatch.value;
        }
    });
});
</script>
<script src="/js/firma_canvas.js"></script>
