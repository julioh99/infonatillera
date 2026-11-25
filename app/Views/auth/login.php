<?php
// Textos del ciclo desde config/natillera.php (no hay nada del ciclo escrito a mano aqui)
$nt_ciclo = htmlspecialchars((string)$this->natillera('web.ciclo_nombre', date('Y')));
$nt_pais = htmlspecialchars((string)$this->natillera('web.ciclo_pais', 'Colombia'));
$nt_cupos = (int)$this->natillera('cupos', 50);
$nt_meta = number_format((float)$this->natillera('prestamo.meta_intereses_anual', 400000), 0, ',', '.');
$nt_metaRaw = (int)$this->natillera('prestamo.meta_intereses_anual', 400000);
?>
<div class="nt-login">
    <div class="row align-items-start g-4">
        <!-- Columna Derecha en Desktop / Primero en Móvil: Formulario de Login -->
        <div class="col-12 col-lg-5 col-xl-4 order-1 order-lg-2 nt-login-sticky">
            <div class="card nt-login-card">
                <div class="card-body p-4 p-sm-5">
                    <div class="nt-login-brand">
                        <div class="nt-login-mark"><i class="fa-solid fa-piggy-bank"></i></div>
                        <h1 class="font-outfit nt-login-title">Natillera Comunitaria</h1>
                        <p class="nt-login-sub">Ingreso al Sistema de Socios y Directiva</p>
                    </div>

                    <form action="/login" method="POST">
                        <div class="mb-3">
                            <label for="cedula" class="form-label">Cédula de Identidad</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0"><i class="fa-solid fa-id-card"></i></span>
                                <input type="text" name="cedula" id="cedula" class="form-control border-start-0 fw-semibold" placeholder="Ej: 1010123456" autocomplete="username" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control border-start-0 fw-semibold" placeholder="••••••••" autocomplete="current-password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-cta w-100 fw-bold font-outfit">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar Sesión
                        </button>
                    </form>

                    <div class="nt-login-foot">
                        <p class="mb-1"><i class="fa-solid fa-shield-halved text-success me-1"></i> Acceso seguro con roles de usuario</p>
                        <span class="nt-chip">Ciclo <?= $nt_ciclo ?> &bull; <?= $nt_pais ?></span>
                        <div class="d-lg-none mt-2">
                            <a href="#reglamento"><i class="fa-solid fa-book-bookmark"></i>Ver reglamento interno</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Izquierda en Desktop / Abajo en Móvil: Landing Page - Reglamento Interno -->
        <div class="col-12 col-lg-7 col-xl-8 order-2 order-lg-1">
            <div class="card nt-rules" id="reglamento">
                <!-- Banner de Cabecera del Reglamento -->
                <div class="nt-hero nt-rules__hero">
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                        <div>
                            <span class="nt-eyebrow"><i class="fa-solid fa-scroll"></i>Normativa Oficial <?= $nt_ciclo ?></span>
                            <h2 class="font-outfit nt-page-title">Requisitos y Normas Natillera</h2>
                            <p>Conoce las reglas financieras y de funcionamiento aprobadas para el ciclo <?= $nt_ciclo ?>.</p>
                        </div>
                        <div>
                            <span class="nt-rules__count">
                                <i class="fa-solid fa-users"></i><?= $nt_cupos ?> Socios Activos
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3 p-sm-4">
                    <!-- Controles de Búsqueda y Filtro Dinámico -->
                    <div class="nt-rules__tools">
                        <div class="input-group">
                            <span class="input-group-text border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="searchNormas" class="form-control border-start-0" placeholder="Buscar norma o regla (ej: préstamo, 5%, cuota)...">
                        </div>
                        <div id="filterBadges">
                            <button type="button" class="btn btn-sm nt-filter btn-dark text-nowrap active filter-btn" data-filter="all">Todas (<span id="totalNormas">12</span>)</button>
                            <button type="button" class="btn btn-sm nt-filter text-nowrap filter-btn" data-filter="cuotas">Cuotas & Préstamos</button>
                            <button type="button" class="btn btn-sm nt-filter text-nowrap filter-btn" data-filter="intereses">Intereses & Inyecciones</button>
                            <button type="button" class="btn btn-sm nt-filter text-nowrap filter-btn" data-filter="administracion">Organización</button>
                        </div>
                    </div>

                    <!-- Rejilla de Tarjetas de Normas Dinámicas -->
                    <div class="row g-3" id="contenedorNormas">

                        <!-- Norma 1 -->
                        <div class="col-12 col-md-6 norma-card" data-category="cuotas" data-search="cuota reunion 55000 semestral variacion quincenal">
                            <div class="nt-norma">
                                <div class="nt-norma__icon"><i class="fa-solid fa-calendar-check"></i></div>
                                <div>
                                    <span class="nt-norma__tag">Cuotas de Reunión</span>
                                    <h3 class="font-outfit nt-norma__title">1. Valor y Variación de Cuota Base</h3>
                                    <p class="nt-norma__text">Traer cuota de reunión de <strong>$55.000 COP</strong> con variación en 4 semanas cada semestre.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 2 -->
                        <div class="col-12 col-md-6 norma-card" data-category="cuotas" data-search="prestamo cuota 10 autoprestamo mora falta">
                            <div class="nt-norma">
                                <div class="nt-norma__icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                                <div>
                                    <span class="nt-norma__tag">Préstamos Automáticos</span>
                                    <h3 class="font-outfit nt-norma__title">2. Cubrimiento de Cuota Faltante</h3>
                                    <p class="nt-norma__text">La natillera prestará a los socios que no tengan la cuota en la fecha de la reunión, cobrando el <strong>10% de interés</strong>.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 3 -->
                        <div class="col-12 col-md-6 norma-card" data-category="intereses" data-search="meta minimo <?= $nt_metaRaw ?> interes ano obligatorio">
                            <div class="nt-norma">
                                <div class="nt-norma__icon"><i class="fa-solid fa-bullseye"></i></div>
                                <div>
                                    <span class="nt-norma__tag">Meta Individual</span>
                                    <h3 class="font-outfit nt-norma__title">3. Mínimo de Interés Anual</h3>
                                    <p class="nt-norma__text">Cada socio deberá generar como mínimo <strong>$<?= $nt_meta ?> COP</strong> de interés al año.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 4 -->
                        <div class="col-12 col-md-6 norma-card" data-category="cuotas" data-search="ahorro extra 2 mayo mitad de ano voluntario">
                            <div class="nt-norma">
                                <div class="nt-norma__icon"><i class="fa-solid fa-seedling"></i></div>
                                <div>
                                    <span class="nt-norma__tag">Incentivo Ahorro Extra</span>
                                    <h3 class="font-outfit nt-norma__title">4. Bonificación Mitad de Año</h3>
                                    <p class="nt-norma__text">Se dará un valor extra del <strong>2%</strong> a los socios que ahorren voluntariamente a mitad de año ➔ <strong>Mayo</strong>.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 5 -->
                        <div class="col-12 col-md-6 norma-card" data-category="cuotas" data-search="monto maximo prestamo 2000000 tope credito">
                            <div class="nt-norma">
                                <div class="nt-norma__icon"><i class="fa-solid fa-shield-cat"></i></div>
                                <div>
                                    <span class="nt-norma__tag">Límite Crediticio</span>
                                    <h3 class="font-outfit nt-norma__title">5. Monto Máximo de Préstamo</h3>
                                    <p class="nt-norma__text">El tope máximo permitido por solicitud de préstamo es de <strong>$2.000.000 COP</strong> por socio.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 6 -->
                        <div class="col-12 col-md-6 norma-card" data-category="intereses" data-search="excedente tope <?= $nt_metaRaw ?> 75 ganancia premio">
                            <div class="nt-norma">
                                <div class="nt-norma__icon"><i class="fa-solid fa-trophy"></i></div>
                                <div>
                                    <span class="nt-norma__tag">Premios & Rendimiento</span>
                                    <h3 class="font-outfit nt-norma__title">6. Bonificación por Excedente de Intereses</h3>
                                    <p class="nt-norma__text">El excedente sobre la meta de $<?= $nt_meta ?> de interés generará el <strong>75% de retribución</strong> para quien lo produzca.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 7 -->
                        <div class="col-12 col-md-6 norma-card" data-category="intereses" data-search="inyecciones 5 capital diciembre mayo 5 meses">
                            <div class="nt-norma">
                                <div class="nt-norma__icon"><i class="fa-solid fa-chart-line"></i></div>
                                <div>
                                    <span class="nt-norma__tag">Inyecciones Capital</span>
                                    <h3 class="font-outfit nt-norma__title">7. Rendimiento por Inyección (5%)</h3>
                                    <p class="nt-norma__text">Quienes realicen inyecciones recibirán el <strong>5% del valor inyectado</strong> por 5 meses (Dic - May).</p>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 8 -->
                        <div class="col-12 col-md-6 norma-card" data-category="intereses" data-search="cupo maximo inyeccion 10000000 socios global">
                            <div class="nt-norma">
                                <div class="nt-norma__icon"><i class="fa-solid fa-vault"></i></div>
                                <div>
                                    <span class="nt-norma__tag">Fondo Global Inyecciones</span>
                                    <h3 class="font-outfit nt-norma__title">8. Cupo Máximo Global</h3>
                                    <p class="nt-norma__text">El cupo global máximo de inyecciones será de <strong>$10.000.000 COP</strong> distribuido entre todos los socios.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 9 -->
                        <div class="col-12 col-md-6 norma-card" data-category="intereses" data-search="no cumpla acumulado interes participacion reparto">
                            <div class="nt-norma">
                                <div class="nt-norma__icon"><i class="fa-solid fa-user-slash"></i></div>
                                <div>
                                    <span class="nt-norma__tag">Restricción Reparto</span>
                                    <h3 class="font-outfit nt-norma__title">9. Exclusión por Incumplimiento de Meta</h3>
                                    <p class="nt-norma__text">El socio que no cumpla con el acumulado de intereses <strong>no participará</strong> del % de repartición de utilidades.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 10 -->
                        <div class="col-12 col-md-6 norma-card" data-category="administracion" data-search="atrasado intereses ronda rifa descuento debito automatico">
                            <div class="nt-norma">
                                <div class="nt-norma__icon"><i class="fa-solid fa-receipt"></i></div>
                                <div>
                                    <span class="nt-norma__tag">Débito Automático</span>
                                    <h3 class="font-outfit nt-norma__title">10. Cobro de Intereses en Rondas/Rifas</h3>
                                    <p class="nt-norma__text">El socio atrasado en intereses a la fecha de la ronda o rifa pequeña se le <strong>debitará automáticamente</strong> y se notificará.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 11 -->
                        <div class="col-12 col-md-6 norma-card" data-category="administracion" data-search="mesa directiva pago 3 intereses generados">
                            <div class="nt-norma">
                                <div class="nt-norma__icon"><i class="fa-solid fa-users-gear"></i></div>
                                <div>
                                    <span class="nt-norma__tag">Mesa Directiva</span>
                                    <h3 class="font-outfit nt-norma__title">11. Remuneración de la Directiva</h3>
                                    <p class="nt-norma__text">El pago para los integrantes de la Mesa Directiva corresponderá al <strong>3% de los intereses totales generados</strong>.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Norma 12 -->
                        <div class="col-12 col-md-6 norma-card" data-category="administracion" data-search="agenda apuntes personal secretaria libro anotaciones">
                            <div class="nt-norma">
                                <div class="nt-norma__icon"><i class="fa-solid fa-book-open"></i></div>
                                <div>
                                    <span class="nt-norma__tag">Control Personal</span>
                                    <h3 class="font-outfit nt-norma__title">12. Agenda de Apuntes Personal</h3>
                                    <p class="nt-norma__text">Se recomienda a los socios llevar su agenda personal; de lo contrario, <strong>se asumirá lo registrado por la Secretaría</strong>.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const inputSearch = document.getElementById('searchNormas');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const cards = document.querySelectorAll('.norma-card');

    // El total de normas se cuenta de las tarjetas (no se escribe a mano)
    const totalNormas = document.getElementById('totalNormas');
    if (totalNormas) totalNormas.textContent = cards.length;

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
