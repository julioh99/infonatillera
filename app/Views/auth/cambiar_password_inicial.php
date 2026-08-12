<div class="row justify-content-center align-items-center py-4">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header bg-gradient-navy text-white p-4 text-center border-0">
                <div class="mb-3">
                    <span class="p-3 bg-white bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width:65px; height:65px;">
                        <i class="fa-solid fa-key fs-2 text-warning"></i>
                    </span>
                </div>
                <h3 class="font-outfit fw-bold m-0 text-gradient-gold">Cambio Obligatorio de Contraseña</h3>
                <p class="text-white-50 fs-7 mt-2 mb-0">Por razones de seguridad de la Natillera, debes actualizar tu contraseña inicial antes de continuar.</p>
            </div>
            
            <div class="card-body p-4 bg-white">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger rounded-3 fs-7 mb-3 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <form action="/cambiar-password-inicial" method="POST">
                    <div class="mb-3">
                        <label for="password_nuevo" class="form-label fw-semibold fs-7 text-dark">Nueva Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-primary"></i></span>
                            <input type="password" name="password_nuevo" id="password_nuevo" class="form-control border-start-0 ps-0 fw-bold" placeholder="Mínimo 6 caracteres" minlength="6" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmar" class="form-label fw-semibold fs-7 text-dark">Confirmar Nueva Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-shield-check text-success"></i></span>
                            <input type="password" name="password_confirmar" id="password_confirmar" class="form-control border-start-0 ps-0 fw-bold" placeholder="Repite tu nueva contraseña" minlength="6" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold py-2.5 shadow-sm font-outfit text-dark fs-6">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Nueva Contraseña e Ingresar
                    </button>
                </form>
            </div>
            
            <div class="card-footer bg-light p-3 text-center border-0">
                <small class="text-muted fs-8">
                    Usuario: <strong><?= htmlspecialchars($_SESSION['usuario']['nombre_completo'] ?? '') ?></strong> (C.C. <?= htmlspecialchars($_SESSION['usuario']['cedula'] ?? '') ?>) &bull;
                    <a href="/logout" class="text-danger text-decoration-none ms-1"><i class="fa-solid fa-right-from-bracket me-1"></i>Cerrar Sesión</a>
                </small>
            </div>
        </div>
    </div>
</div>
