<div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4 nt-pw">
        <div class="card w-100">
            <div class="card-body p-4 p-sm-5">
                <div class="nt-pw__icon"><i class="fa-solid fa-key"></i></div>
                <h1 class="font-outfit nt-pw__title">Cambio Obligatorio de Contraseña</h1>
                <p class="nt-pw__lead">Por razones de seguridad de la Natillera, debes actualizar tu contraseña inicial antes de continuar.</p>

                <div class="nt-pw__notice" role="note">
                    <i class="fa-solid fa-circle-info mt-1"></i>
                    <span>Este paso es obligatorio: sin guardar tu nueva contraseña no podrás continuar.</span>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger fs-7 mb-3 d-flex align-items-center gap-2" role="alert">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <form action="/cambiar-password-inicial" method="POST">
                    <div class="mb-3">
                        <label for="password_nuevo" class="form-label">Nueva Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" name="password_nuevo" id="password_nuevo" class="form-control border-start-0 ps-0 fw-semibold" placeholder="Mínimo 6 caracteres" minlength="6" autocomplete="new-password" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0"><i class="fa-solid fa-shield-check"></i></span>
                            <input type="password" name="password_confirmar" id="password_confirmar" class="form-control border-start-0 ps-0 fw-semibold" placeholder="Repite tu nueva contraseña" minlength="6" autocomplete="new-password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-cta w-100 fw-bold font-outfit">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Nueva Contraseña e Ingresar
                    </button>
                </form>
            </div>

            <div class="nt-pw__foot">
                Usuario: <strong><?= htmlspecialchars($_SESSION['usuario']['nombre_completo'] ?? '') ?></strong> (C.C. <?= htmlspecialchars($_SESSION['usuario']['cedula'] ?? '') ?>) &bull;
                <a href="/logout" class="ms-1"><i class="fa-solid fa-right-from-bracket me-1"></i>Cerrar Sesión</a>
            </div>
        </div>
    </div>
</div>
