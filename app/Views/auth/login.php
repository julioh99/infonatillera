<div class="row justify-content-center align-items-center min-vh-75 py-5">
    <div class="col-12 col-sm-9 col-md-7 col-lg-5 col-xl-4">
        <div class="card card-glass shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-gradient-dark text-white text-center py-4 border-0">
                <div class="avatar-icon bg-warning bg-opacity-20 text-warning rounded-circle d-inline-flex p-3 mb-2">
                    <i class="fa-solid fa-piggy-bank fs-1"></i>
                </div>
                <h3 class="font-outfit fw-bold m-0 text-white">Natillera Comunitaria</h3>
                <p class="text-white-50 fs-7 m-0 mt-1">Ingreso al Sistema de Socios y Directiva</p>
            </div>
            <div class="card-body p-4 p-sm-5">
                <form action="/login" method="POST">
                    <div class="mb-4">
                        <label for="cedula" class="form-label text-dark fw-semibold fs-7">Cédula de Identidad</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-id-card"></i></span>
                            <input type="text" name="cedula" id="cedula" class="form-control bg-light border-start-0 py-2" placeholder="Ej: 1010123456" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label text-dark fw-semibold fs-7">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control bg-light border-start-0 py-2" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 py-2.5 fw-bold text-dark font-outfit shadow-sm rounded-3">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar Sesión
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top text-center text-muted fs-7">
                    <p class="mb-1"><i class="fa-solid fa-shield-halved text-success me-1"></i> Acceso seguro con roles de usuario</p>
                    <span class="badge bg-secondary opacity-75">50 Socios &bull; Colombia</span>
                </div>
            </div>
        </div>
    </div>
</div>
