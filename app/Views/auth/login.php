<div class="container-fluid px-2 px-md-4 py-3">
    <div class="row align-items-start g-4">
        <!-- Columna Izquierda en Desktop / Abajo en Móvil: Landing Page - Reglamento Interno 2026 -->
        <div class="col-12 col-lg-7 col-xl-8 order-2 order-lg-1">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
                <!-- Banner de Cabecera del Reglamento -->
                <div class="card-header bg-gradient-navy text-white p-4 border-0 position-relative">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <span class="badge bg-warning text-dark font-outfit px-3 py-1 mb-2 rounded-pill fw-bold">
                                <i class="fa-solid fa-scroll me-1"></i>Normativa Oficial 2026
                            </span>
                            <h2 class="font-outfit fw-bold text-white m-0 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-book-bookmark text-warning"></i>
                                Requisitos y Normas Natillera
                            </h2>
                            <p class="text-white-50 m-0 fs-7 mt-1">Conoce las reglas financieras y de funcionamiento aprobadas para el ciclo 2026.</p>
                        </div>
                        <div class="text-end d-none d-sm-block">
                            <span class="badge bg-white bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 rounded-3">
                                <i class="fa-solid fa-users me-1"></i>50 Socios Activos
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Controles de Búsqueda y Filtro Dinámico -->
                    <div class="row g-2 mb-4 align-items-center">
                        <div class="col-12 col-md-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="searchNormas" class="form-control bg-light border-start-0 py-2" placeholder="Buscar norma o regla (ej: préstamo, 5%, cuota)...">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex gap-1 overflow-auto pb-1" id="filterBadges">
                                <button type="button" class="btn btn-sm btn-dark rounded-pill text-nowrap active filter-btn" data-filter="all">Todas (12)</button>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill text-nowrap filter-btn" data-filter="cuotas">Cuotas & Préstamos</button>
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill text-nowrap filter-btn" data-filter="intereses">Intereses & Inyecciones</button>
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-pill text-nowrap filter-btn" data-filter="administracion">Organización</button>
                            </div>
                        </div>
                    </div>

                    <!-- Rejilla de Tarjetas de Normas Dinámicas -->
                    <div class="row g-3" id="contenedorNormas">

                        <!-- Norma 1 -->
                        <div class="col-12 col-md-6 norma-card" data-category="cuotas" data-search="cuota reunion 55000 semestral variacion quincenal">
                            <div class="p-3 rounded-4 h-100 border border-info border-opacity-25 bg-info bg-opacity-10 transition-hover shadow-sm">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 bg-info bg-opacity-20 text-info rounded-3 fs-4 flex-shrink-0">
                                        <i class="fa-solid fa-calendar-check"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-info text-dark font-outfit fs-8 mb-1">Cuotas de Reunión</span>
                                        <h6 class="fw-bold text-dark font-outfit mb-1">1. Valor y Variación de Cuota Base</h6>
                                        <p class="text-secondary fs-7 m-0">Traer cuota de reunión de <strong>$55.000 COP</strong> con variación en 4 semanas cada semestre.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 2 -->
                        <div class="col-12 col-md-6 norma-card" data-category="cuotas" data-search="prestamo cuota 10 autoprestamo mora falta">
                            <div class="p-3 rounded-4 h-100 border border-danger border-opacity-25 bg-danger bg-opacity-10 transition-hover shadow-sm">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 bg-danger bg-opacity-20 text-danger rounded-3 fs-4 flex-shrink-0">
                                        <i class="fa-solid fa-hand-holding-dollar"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-danger text-white font-outfit fs-8 mb-1">Préstamos Automáticos</span>
                                        <h6 class="fw-bold text-dark font-outfit mb-1">2. Cubrimiento de Cuota Faltante</h6>
                                        <p class="text-secondary fs-7 m-0">La natillera prestará a los socios que no tengan la cuota en la fecha de la reunión, cobrando el <strong>10% de interés</strong>.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 3 -->
                        <div class="col-12 col-md-6 norma-card" data-category="intereses" data-search="meta minimo 400000 interes ano obligatorio">
                            <div class="p-3 rounded-4 h-100 border border-warning border-opacity-50 bg-warning bg-opacity-15 transition-hover shadow-sm">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 bg-warning bg-opacity-25 text-dark rounded-3 fs-4 flex-shrink-0">
                                        <i class="fa-solid fa-bullseye"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-warning text-dark font-outfit fs-8 mb-1">Meta Individual</span>
                                        <h6 class="fw-bold text-dark font-outfit mb-1">3. Mínimo de Interés Anual</h6>
                                        <p class="text-secondary fs-7 m-0">Cada socio deberá generar como mínimo <strong>$400.000 COP</strong> de interés al año.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 4 -->
                        <div class="col-12 col-md-6 norma-card" data-category="cuotas" data-search="ahorro extra 2 mayo mitad de ano voluntario">
                            <div class="p-3 rounded-4 h-100 border border-success border-opacity-25 bg-success bg-opacity-10 transition-hover shadow-sm">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 bg-success bg-opacity-20 text-success rounded-3 fs-4 flex-shrink-0">
                                        <i class="fa-solid fa-seedling"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-success text-white font-outfit fs-8 mb-1">Incentivo Ahorro Extra</span>
                                        <h6 class="fw-bold text-dark font-outfit mb-1">4. Bonificación Mitad de Año</h6>
                                        <p class="text-secondary fs-7 m-0">Se dará un valor extra del <strong>2%</strong> a los socios que ahorren voluntariamente a mitad de año ➔ <strong>Mayo</strong>.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 5 -->
                        <div class="col-12 col-md-6 norma-card" data-category="cuotas" data-search="monto maximo prestamo 2000000 tope credito">
                            <div class="p-3 rounded-4 h-100 border border-primary border-opacity-25 bg-primary bg-opacity-10 transition-hover shadow-sm">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 bg-primary bg-opacity-20 text-primary rounded-3 fs-4 flex-shrink-0">
                                        <i class="fa-solid fa-shield-cat"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-primary text-white font-outfit fs-8 mb-1">Límite Crediticio</span>
                                        <h6 class="fw-bold text-dark font-outfit mb-1">5. Monto Máximo de Préstamo</h6>
                                        <p class="text-secondary fs-7 m-0">El tope máximo permitido por solicitud de préstamo es de <strong>$2.000.000 COP</strong> por socio.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 6 -->
                        <div class="col-12 col-md-6 norma-card" data-category="intereses" data-search="excedente tope 400000 75 ganancia premio">
                            <div class="p-3 rounded-4 h-100 border border-secondary border-opacity-25 bg-secondary bg-opacity-10 transition-hover shadow-sm">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 bg-secondary bg-opacity-20 text-dark rounded-3 fs-4 flex-shrink-0">
                                        <i class="fa-solid fa-trophy"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-secondary text-white font-outfit fs-8 mb-1">Premios & Rendimiento</span>
                                        <h6 class="fw-bold text-dark font-outfit mb-1">6. Bonificación por Excedente de Intereses</h6>
                                        <p class="text-secondary fs-7 m-0">El excedente sobre la meta de $400.000 de interés generará el <strong>75% de retribución</strong> para quien lo produzca.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 7 -->
                        <div class="col-12 col-md-6 norma-card" data-category="intereses" data-search="inyecciones 5 capital diciembre mayo 5 meses">
                            <div class="p-3 rounded-4 h-100 border border-success border-opacity-25 bg-success bg-opacity-10 transition-hover shadow-sm">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 bg-success bg-opacity-20 text-success rounded-3 fs-4 flex-shrink-0">
                                        <i class="fa-solid fa-chart-line"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-success text-white font-outfit fs-8 mb-1">Inyecciones Capital</span>
                                        <h6 class="fw-bold text-dark font-outfit mb-1">7. Rendimiento por Inyección (5%)</h6>
                                        <p class="text-secondary fs-7 m-0">Quienes realicen inyecciones recibirán el <strong>5% del valor inyectado</strong> por 5 meses (Dic - May).</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 8 -->
                        <div class="col-12 col-md-6 norma-card" data-category="intereses" data-search="cupo maximo inyeccion 10000000 socios global">
                            <div class="p-3 rounded-4 h-100 border border-info border-opacity-25 bg-info bg-opacity-10 transition-hover shadow-sm">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 bg-info bg-opacity-20 text-info rounded-3 fs-4 flex-shrink-0">
                                        <i class="fa-solid fa-vault"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-info text-dark font-outfit fs-8 mb-1">Fondo Global Inyecciones</span>
                                        <h6 class="fw-bold text-dark font-outfit mb-1">8. Cupo Máximo Global</h6>
                                        <p class="text-secondary fs-7 m-0">El cupo global máximo de inyecciones será de <strong>$10.000.000 COP</strong> distribuido entre todos los socios.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 9 -->
                        <div class="col-12 col-md-6 norma-card" data-category="intereses" data-search="no cumpla acumulado interes participacion reparto">
                            <div class="p-3 rounded-4 h-100 border border-dark border-opacity-25 bg-dark bg-opacity-10 transition-hover shadow-sm">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 bg-dark bg-opacity-20 text-dark rounded-3 fs-4 flex-shrink-0">
                                        <i class="fa-solid fa-user-slash"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-dark text-white font-outfit fs-8 mb-1">Restricción Reparto</span>
                                        <h6 class="fw-bold text-dark font-outfit mb-1">9. Exclusión por Incumplimiento de Meta</h6>
                                        <p class="text-secondary fs-7 m-0">El socio que no cumpla con el acumulado de intereses <strong>no participará</strong> del % de repartición de utilidades.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 10 -->
                        <div class="col-12 col-md-6 norma-card" data-category="administracion" data-search="atrasado intereses ronda rifa descuento debito automatico">
                            <div class="p-3 rounded-4 h-100 border border-danger border-opacity-25 bg-danger bg-opacity-10 transition-hover shadow-sm">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 bg-danger bg-opacity-20 text-danger rounded-3 fs-4 flex-shrink-0">
                                        <i class="fa-solid fa-receipt"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-danger text-white font-outfit fs-8 mb-1">Débito Automático</span>
                                        <h6 class="fw-bold text-dark font-outfit mb-1">10. Cobro de Intereses en Rondas/Rifas</h6>
                                        <p class="text-secondary fs-7 m-0">El socio atrasado en intereses a la fecha de la ronda o rifa pequeña se le <strong>debitará automáticamente</strong> y se notificará.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 11 -->
                        <div class="col-12 col-md-6 norma-card" data-category="administracion" data-search="mesa directiva pago 3 intereses generados">
                            <div class="p-3 rounded-4 h-100 border border-primary border-opacity-25 bg-primary bg-opacity-10 transition-hover shadow-sm">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 bg-primary bg-opacity-20 text-primary rounded-3 fs-4 flex-shrink-0">
                                        <i class="fa-solid fa-users-gear"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-primary text-white font-outfit fs-8 mb-1">Mesa Directiva</span>
                                        <h6 class="fw-bold text-dark font-outfit mb-1">11. Remuneración de la Directiva</h6>
                                        <p class="text-secondary fs-7 m-0">El pago para los integrantes de la Mesa Directiva corresponderá al <strong>3% de los intereses totales generados</strong>.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 12 -->
                        <div class="col-12 col-md-6 norma-card" data-category="administracion" data-search="agenda apuntes personal secretaria libro anotaciones">
                            <div class="p-3 rounded-4 h-100 border border-warning border-opacity-50 bg-warning bg-opacity-15 transition-hover shadow-sm">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 bg-warning bg-opacity-25 text-dark rounded-3 fs-4 flex-shrink-0">
                                        <i class="fa-solid fa-book-open"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-warning text-dark font-outfit fs-8 mb-1">Control Personal</span>
                                        <h6 class="fw-bold text-dark font-outfit mb-1">12. Agenda de Apuntes Personal</h6>
                                        <p class="text-secondary fs-7 m-0">Se recomienda a los socios llevar su agenda personal; de lo contrario, <strong>se asumirá lo registrado por la Secretaría</strong>.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha en Desktop / Arriba en Móvil: Formulario de Login -->
        <div class="col-12 col-lg-5 col-xl-4 order-1 order-lg-2 sticky-lg-top" style="top: 2rem;">
            <div class="card card-glass shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-gradient-dark text-white text-center py-4 border-0">
                    <div class="avatar-icon bg-warning bg-opacity-20 text-warning rounded-circle d-inline-flex p-3 mb-2 shadow-sm">
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
                                <input type="text" name="cedula" id="cedula" class="form-control bg-light border-start-0 py-2 fw-bold text-dark" placeholder="Ej: 1010123456" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label text-dark fw-semibold fs-7">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control bg-light border-start-0 py-2 fw-bold" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 py-3 fw-bold text-dark font-outfit shadow-sm rounded-3 fs-6">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar Sesión
                        </button>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center text-muted fs-7">
                        <p class="mb-1"><i class="fa-solid fa-shield-halved text-success me-1"></i> Acceso seguro con roles de usuario</p>
                        <span class="badge bg-dark text-white rounded-pill px-3 py-1">Ciclo 2026 &bull; Colombia</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.transition-hover {
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}
.transition-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const inputSearch = document.getElementById('searchNormas');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const cards = document.querySelectorAll('.norma-card');

    let currentFilter = 'all';
    let currentSearch = '';

    function filterCards() {
        cards.forEach(card => {
            const category = card.getAttribute('data-category');
            const searchText = card.getAttribute('data-search').toLowerCase();

            const matchesCategory = (currentFilter === 'all' || category === currentFilter);
            const matchesSearch = (!currentSearch || searchText.includes(currentSearch));

            if (matchesCategory && matchesSearch) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    if (inputSearch) {
        inputSearch.addEventListener('input', (e) => {
            currentSearch = e.target.value.toLowerCase().trim();
            filterCards();
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => {
                b.classList.remove('btn-dark', 'active');
                b.classList.add('btn-outline-primary');
            });

            btn.classList.add('btn-dark', 'active');
            currentFilter = btn.getAttribute('data-filter');
            filterCards();
        });
    });
});
</script>
