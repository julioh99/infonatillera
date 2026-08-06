document.addEventListener('DOMContentLoaded', () => {
    const chkPagos = document.querySelectorAll('.chk-pago-cuota');
    const inputBuscar = document.getElementById('buscarSocio');
    const btnMarcarTodos = document.getElementById('btnMarcarTodos');
    const btnDesmarcarTodos = document.getElementById('btnDesmarcarTodos');
    const btnGuardarBatch = document.getElementById('btnGuardarBatch');
    const selectReunion = document.getElementById('reunion_id');

    // Sincronizar 'Pagó Cuota' con 'Generar Autopréstamo'
    chkPagos.forEach(chk => {
        chk.addEventListener('change', (e) => {
            const socioId = e.target.getAttribute('data-socio-id');
            const chkAuto = document.getElementById(`auto_${socioId}`);
            
            if (e.target.checked) {
                chkAuto.checked = false;
                chkAuto.disabled = true;
            } else {
                chkAuto.disabled = false;
                chkAuto.checked = true; // Por defecto genera autopréstamo si no pagó
            }
        });
    });

    // Filtro de Búsqueda Rápida en Vivo
    if (inputBuscar) {
        inputBuscar.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            document.querySelectorAll('.socio-row').forEach(row => {
                const searchText = row.getAttribute('data-search');
                row.style.display = searchText.includes(query) ? '' : 'none';
            });
        });
    }

    // Marcar Todos
    if (btnMarcarTodos) {
        btnMarcarTodos.addEventListener('click', () => {
            chkPagos.forEach(chk => {
                chk.checked = true;
                chk.dispatchEvent(new Event('change'));
            });
        });
    }

    // Limpiar Todos
    if (btnDesmarcarTodos) {
        btnDesmarcarTodos.addEventListener('click', () => {
            chkPagos.forEach(chk => {
                chk.checked = false;
                chk.dispatchEvent(new Event('change'));
            });
        });
    }

    // Guardado en Lote vía Fetch con SweetAlert2
    if (btnGuardarBatch) {
        btnGuardarBatch.addEventListener('click', () => {
            const reunionId = selectReunion ? selectReunion.value : 0;
            const rows = document.querySelectorAll('.socio-row');
            const registros = [];

            rows.forEach(row => {
                const socioId = row.getAttribute('data-socio-id');
                const pagouCuota = document.getElementById(`pago_${socioId}`).checked;
                const ahorroExtra = document.getElementById(`ahorro_${socioId}`).value || 0;
                const generarAutoprestamo = document.getElementById(`auto_${socioId}`).checked;

                registros.push({
                    socio_id: socioId,
                    pagou_cuota: pagouCuota,
                    ahorro_extra: ahorroExtra,
                    generar_autoprestamo: generarAutoprestamo
                });
            });

            Swal.fire({
                title: '¿Confirmar Llamado a Lista?',
                text: `Se registrará el estado de cobro para los ${registros.length} socios en la quincena seleccionada.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Sí, Guardar Todo',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.showLoading();

                    fetch('/admin/llamado-lista/guardar-batch', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            reunion_id: reunionId,
                            registros: registros
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Éxito!',
                                text: data.message,
                                icon: 'success'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message || 'No se pudo guardar el llamado a lista.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error de Conexión', 'Ocurrió un error al comunicarse con el servidor.', 'error');
                    });
                }
            });
        });
    }
});
