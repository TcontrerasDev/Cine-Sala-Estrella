/**
 * Frontend logic for seat selection.
 * Handles interactive map, temporary bookings, and WooCommerce integration.
 *
 * @package SalaEstrellaManager
 */

(function() {
    'use strict';

    // Global state
    const state = {
        asientosSeleccionados: [], // Array of asiento IDs
        timer: null,
        timeLeft: 300, // 5 minutes in seconds
        pollingInterval: null,
        isProcessing: false,
        isCheckoutRedirect: false,
    };

    // DOM Elements
    const elements = {
        mapa: document.getElementById('mapaAsientos'),
        resumen: document.getElementById('resumenSeleccion'),
        total: document.getElementById('totalPrecio'),
        btnContinuar: document.getElementById('btnContinuar'),
        btnCancelar: document.getElementById('btnCancelar'),
        timerContainer: document.getElementById('timerContainer'),
        timerLabel: document.getElementById('timer'),
        modalExpiracion: null, // Initialized later
        modalConfirmarCancelacion: null, // Initialized later
    };

    /**
     * Initialization
     */
    function init() {
        if (!elements.mapa || typeof cnesAsientos === 'undefined') return;

        // Inject custom cancel modal if it doesn't exist
        if (!document.getElementById('modalConfirmarCancelacion')) {
            createCancelModal();
        }

        // Initialize modals with a small delay to ensure Bootstrap is loaded
        setTimeout(initModals, 500);

        renderGrid();
        setupEventListeners();
        startPolling();
    }

    function initModals() {
        const modalExp = document.getElementById('modalExpiracion');
        const modalCan = document.getElementById('modalConfirmarCancelacion');

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            if (modalExp) elements.modalExpiracion = new bootstrap.Modal(modalExp);
            if (modalCan) elements.modalConfirmarCancelacion = new bootstrap.Modal(modalCan);
        } else if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
            // Fallback for older Bootstrap or jQuery-wrapped Bootstrap
            elements.modalExpiracion = { show: () => jQuery(modalExp).modal('show') };
            elements.modalConfirmarCancelacion = { show: () => jQuery(modalCan).modal('show') };
        }
    }

    /**
     * Render the seat grid based on room layout.
     */
    function renderGrid() {
        const { sala, asientos_estado } = cnesAsientos;
        const layout = sala.layout;
        
        if (!layout || !layout.asientos) return;

        elements.mapa.innerHTML = '';
        state.asientosSeleccionados = [];

        const grid = document.createElement('div');
        grid.className = 'asientos-grid';
        grid.style.display = 'grid';
        grid.style.gridTemplateColumns = `repeat(${sala.columnas + 2}, auto)`;
        grid.style.gap = '8px';
        grid.style.justifyContent = 'center';

        const layoutMap = {};
        layout.asientos.forEach(a => {
            if (!layoutMap[a.fila]) layoutMap[a.fila] = {};
            layoutMap[a.fila][a.numero] = a;
        });

        // Map status to IDs
        const statusMap = {};
        asientos_estado.forEach(s => {
            statusMap[`${s.fila}${s.numero}`] = s;
        });

        const filas = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('').slice(0, sala.filas);

        filas.forEach(f => {
            // Row label (Left)
            grid.appendChild(createLabel(f));

            let seatRealNumber = 1;
            for (let c = 1; c <= sala.columnas; c++) {
                const seatData = layoutMap[f] ? layoutMap[f][c] : null;
                const statusData = statusMap[`${f}${c}`];

                if (!seatData || seatData.tipo === 'inactivo') {
                    grid.appendChild(document.createElement('div')); // Empty space
                    continue;
                }

                if (seatData.tipo === 'pasillo') {
                    const pasillo = document.createElement('div');
                    pasillo.className = 'pasillo';
                    pasillo.style.width = '24px';
                    grid.appendChild(pasillo);
                    continue;
                }

                const asientoBtn = document.createElement('button');
                asientoBtn.type = 'button';
                asientoBtn.className = 'asiento';
                asientoBtn.dataset.id = statusData.id;
                asientoBtn.dataset.fila = f;
                asientoBtn.dataset.numero = c;
                asientoBtn.dataset.numeroLabel = seatRealNumber;
                asientoBtn.dataset.tipo = seatData.tipo;
                asientoBtn.title = `Fila ${f} - Asiento ${seatRealNumber} (${seatData.tipo})`;
                asientoBtn.textContent = seatRealNumber;

                if (seatData.tipo === 'vip') {
                    asientoBtn.classList.add('asiento--vip');
                }

                // Status
                if (statusData.estado === 'ocupado' || statusData.estado === 'pagado' || (statusData.estado === 'seleccionado' && !statusData.es_mio)) {
                    asientoBtn.classList.add('ocupado');
                    asientoBtn.disabled = true;
                } else if (statusData.es_mio) {
                    asientoBtn.classList.add('seleccionado');
                }

                asientoBtn.addEventListener('click', () => handleSeatClick(asientoBtn));
                grid.appendChild(asientoBtn);
                
                seatRealNumber++;
            }

            // Row label (Right)
            grid.appendChild(createLabel(f));
        });

        elements.mapa.appendChild(grid);
        updateResumen();
        
        if (state.asientosSeleccionados.length > 0 && !state.timer) {
            startTimer();
        }
    }

    function createLabel(text) {
        const label = document.createElement('div');
        label.className = 'fila-label d-flex align-items-center justify-content-center';
        label.textContent = text;
        return label;
    }

    /**
     * Handle seat click (Toggle selection)
     */
    async function handleSeatClick(btn) {
        if (state.isProcessing) return;

        const asientoId = parseInt(btn.dataset.id);
        const isSelected = btn.classList.contains('seleccionado');

        if (isSelected) {
            // Optimistic UI: Remove
            btn.classList.remove('seleccionado');
            updateResumen();

            try {
                const response = await apiRequest('cnes_liberar_asiento', {
                    funcion_id: cnesAsientos.funcion_id,
                    asiento_id: asientoId
                });

                if (!response.success) {
                    // Revert
                    btn.classList.add('seleccionado');
                    updateResumen();
                    showNotification(response.data.message || 'Error al liberar asiento', 'danger');
                }
            } catch (error) {
                btn.classList.add('seleccionado');
                updateResumen();
                showNotification('Error de conexión', 'danger');
            }
        } else {
            // Check limit (Max 4 tickets)
            if (state.asientosSeleccionados.length >= 4) {
                return;
            }

            // Optimistic UI: Add
            btn.classList.add('seleccionado');
            updateResumen();

            try {
                const response = await apiRequest('cnes_bloquear_asiento', {
                    funcion_id: cnesAsientos.funcion_id,
                    asiento_id: asientoId
                });

                if (response.success) {
                    if (!state.timer) startTimer();
                } else {
                    // Revert
                    btn.classList.remove('seleccionado');
                    btn.classList.add('ocupado');
                    btn.disabled = true;
                    updateResumen();
                    showNotification(response.data.message || 'El asiento ya no está disponible', 'warning');
                }
            } catch (error) {
                btn.classList.remove('seleccionado');
                updateResumen();
                showNotification('Error de conexión', 'danger');
            }
        }
    }

    /**
     * Update the purchase summary panel.
     */
    function updateResumen() {
        const counts = { normal: 0, vip: 0 };
        const selectedButtons = Array.from(elements.mapa.querySelectorAll('.asiento.seleccionado'));
        
        // Sync state
        state.asientosSeleccionados = selectedButtons.map(btn => parseInt(btn.dataset.id));

        // Enforce visual limit
        enforceSeatLimit();

        if (selectedButtons.length === 0) {
            elements.resumen.innerHTML = '<p class="text-muted text-center">No has seleccionado ningún asiento aún.</p>';
            elements.total.textContent = formatPrice(0);
            elements.btnContinuar.disabled = true;
            return;
        }

        let html = '<ul class="resumen-lista m-0 p-0">';
        let total = 0;

        selectedButtons.forEach(btn => {
            const tipo = btn.dataset.tipo;
            const precio = (tipo === 'vip') ? cnesAsientos.precio_vip : cnesAsientos.precio_normal;
            counts[tipo]++;
            total += parseInt(precio);

            html += `
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <div>
                        <span class="badge bg-secondary me-2">${btn.dataset.fila}${btn.dataset.numeroLabel}</span>
                        <small class="text-capitalize">${tipo}</small>
                    </div>
                    <span>${formatPrice(precio)}</span>
                </li>
            `;
        });

        html += '</ul>';

        // Breakdown if mixed
        if (counts.normal > 0 && counts.vip > 0) {
            html += `
                <div class="small text-muted border-top pt-2">
                    <div class="d-flex justify-content-between">
                        <span>${counts.normal} × Normal</span>
                        <span>${formatPrice(counts.normal * cnesAsientos.precio_normal)}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>${counts.vip} × VIP</span>
                        <span>${formatPrice(counts.vip * cnesAsientos.precio_vip)}</span>
                    </div>
                </div>
            `;
        }

        elements.resumen.innerHTML = html;
        elements.total.textContent = formatPrice(total);
        elements.btnContinuar.disabled = false;
    }

    /**
     * Disable/Enable seats based on selection limit.
     */
    function enforceSeatLimit() {
        const count = state.asientosSeleccionados.length;
        const limitReached = count >= 4;
        const allSeats = elements.mapa.querySelectorAll('.asiento');

        allSeats.forEach(btn => {
            // Never touch occupied seats
            if (btn.classList.contains('ocupado')) return;

            if (limitReached && !btn.classList.contains('seleccionado')) {
                btn.disabled = true;
                btn.style.opacity = '0.3';
                btn.style.pointerEvents = 'none';
            } else {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            }
        });
    }

    /**
     * Add to Cart and redirect.
     */
    async function handleAddToCart() {
        if (state.asientosSeleccionados.length === 0 || state.isProcessing) return;

        setLoading(true);

        try {
            const response = await apiRequest('cnes_agregar_al_carrito', {
                funcion_id: cnesAsientos.funcion_id,
                asientos: state.asientosSeleccionados
            });

            if (response.success) {
                state.isCheckoutRedirect = true;
                window.location.href = response.data.redirect_url;
            } else {
                showNotification(response.data.message || 'Error al agregar al carrito', 'danger');
                setLoading(false);
            }
        } catch (error) {
            showNotification('Error de conexión', 'danger');
            setLoading(false);
        }
    }

    /**
     * Polling for seat updates. Uses cnes_verificar_disponibilidad so the
     * server also purges expired rows before returning the seat map.
     */
    function startPolling() {
        state.pollingInterval = setInterval(async () => {
            try {
                const response = await apiRequest('cnes_verificar_disponibilidad', {
                    funcion_id: cnesAsientos.funcion_id
                });

                if (response.success) {
                    updateSeatStatuses(response.data.asientos);
                }
            } catch (e) { /* Silently ignore polling errors */ }
        }, 30000);
    }

    function updateSeatStatuses(asientos) {
        asientos.forEach(s => {
            const btn = elements.mapa.querySelector(`.asiento[data-id="${s.id}"]`);
            if (!btn) return;

            // Don't touch seats selected by the user unless the server says they are NOT mine anymore
            if (btn.classList.contains('seleccionado') && s.es_mio) {
                return;
            }

            if (s.estado === 'ocupado' || s.estado === 'pagado' || (s.estado === 'seleccionado' && !s.es_mio)) {
                btn.classList.add('ocupado');
                btn.classList.remove('seleccionado');
                btn.disabled = true;
            } else if (s.es_mio) {
                // This shouldn't happen if we're careful, but for sync:
                btn.classList.add('seleccionado');
                btn.classList.remove('ocupado');
                btn.disabled = false;
            } else {
                btn.classList.remove('ocupado', 'seleccionado');
                btn.disabled = false;
            }
        });
        updateResumen();
    }

    /**
     * Countdown Timer
     */
    function startTimer() {
        elements.timerContainer.classList.remove('d-none');
        state.timer = setInterval(() => {
            state.timeLeft--;
            
            const minutes = Math.floor(state.timeLeft / 60);
            const seconds = state.timeLeft % 60;
            elements.timerLabel.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            if (state.timeLeft <= 0) {
                clearInterval(state.timer);
                expireSession();
            }
        }, 1000);
    }

    function expireSession() {
        if (elements.modalExpiracion) {
            elements.modalExpiracion.show();
        } else {
            window.location.href = cnesAsientos.home_url + '#cartelera';
        }
    }

    /**
     * Cancelation
     */
    function handleCancel() {
        if (elements.modalConfirmarCancelacion) {
            elements.modalConfirmarCancelacion.show();
        } else {
            // Fallback if modal failed to init, but avoid alert as requested
            window.location.href = cnesAsientos.home_url + '#cartelera';
        }
    }

    /**
     * Create the cancel confirmation modal dynamically.
     */
    function createCancelModal() {
        const modalHtml = `
            <div class="modal fade" id="modalConfirmarCancelacion" tabindex="-1" aria-labelledby="modalConfirmarCancelacionLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title font-display text-white" id="modalConfirmarCancelacionLabel" style="font-size: var(--text-xl); letter-spacing: 0.05em; text-transform: uppercase;">Confirmar Cancelación</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-white mb-0">¿Estás seguro de que deseas cancelar tu selección de asientos? Serás redirigido a la cartelera.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-cine btn-ghost btn-mantener" data-bs-dismiss="modal">Mantener Selección</button>
                            <button type="button" id="confirmarCancelacionBtn" class="btn-cine btn-sede-activa btn-cancelar">Cancelar y Volver</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        const confirmBtn = document.getElementById('confirmarCancelacionBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', async () => {
                try {
                    confirmBtn.disabled = true;
                    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cancelando...';
                    
                    await apiRequest('cnes_cancelar_seleccion', {
                        funcion_id: cnesAsientos.funcion_id
                    });
                } catch (error) {
                    console.error('Error al cancelar la selección:', error);
                } finally {
                    window.location.href = cnesAsientos.home_url + '#cartelera';
                }
            });
        }
    }

    /**
     * Utilities
     */
    async function apiRequest(action, data) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', cnesAsientos.nonce);
        
        for (const key in data) {
            if (Array.isArray(data[key])) {
                data[key].forEach(val => formData.append(`${key}[]`, val));
            } else {
                formData.append(key, data[key]);
            }
        }

        const response = await fetch(cnesAsientos.ajax_url, {
            method: 'POST',
            body: formData
        });

        return await response.json();
    }

    function formatPrice(price) {
        return new Intl.NumberFormat('es-CL', {
            style: 'currency',
            currency: 'CLP',
            minimumFractionDigits: 0
        }).format(price).replace('CLP', '$').trim();
    }

    function setLoading(loading) {
        state.isProcessing = loading;
        const spinner = elements.btnContinuar.querySelector('.spinner-border');
        if (loading) {
            elements.btnContinuar.disabled = true;
            spinner.classList.remove('d-none');
        } else {
            elements.btnContinuar.disabled = false;
            spinner.classList.add('d-none');
        }
    }

    function showNotification(message, type = 'info') {
        const toastId = 'cnes-toast-' + Date.now();
        const bgColor = type === 'danger' ? 'var(--color-rojo)' : (type === 'warning' ? 'var(--color-naranja)' : 'var(--color-verde)');
        
        const toastHtml = `
            <div id="${toastId}" class="cnes-toast" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; background: ${bgColor}; color: white; padding: 12px 24px; border-radius: var(--radius-md); box-shadow: var(--shadow-card); font-family: var(--font-body); font-size: var(--text-sm); animation: cnesFadeIn 0.3s var(--ease-out-strong);">
                ${message}
            </div>
            <style>
                @keyframes cnesFadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
                .cnes-toast-fade-out { opacity: 0; transform: translateY(10px); transition: opacity 0.3s, transform 0.3s; }
            </style>
        `;
        
        document.body.insertAdjacentHTML('beforeend', toastHtml);
        
        setTimeout(() => {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.classList.add('cnes-toast-fade-out');
                setTimeout(() => toast.remove(), 300);
            }
        }, 4000);
    }

    function setupEventListeners() {
        elements.btnContinuar.addEventListener('click', handleAddToCart);
        
        // Use native Bootstrap trigger if possible for better reliability
        if (elements.btnCancelar) {
            elements.btnCancelar.setAttribute('data-bs-toggle', 'modal');
            elements.btnCancelar.setAttribute('data-bs-target', '#modalConfirmarCancelacion');
            
            // Still keep the listener for logic or if data-attributes fail
            elements.btnCancelar.addEventListener('click', (e) => {
                // If modal initialized via JS, we let bootstrap handle it via data-attributes.
                // But if it's not showing (no modal-backdrop), we trigger manually.
                setTimeout(() => {
                    if (!document.querySelector('.modal-backdrop')) {
                        handleCancel();
                    }
                }, 100);
            });
        }

        // Release seats on abandonment
        const releaseSeatsBeacon = () => {
            if (state.isCheckoutRedirect || state.asientosSeleccionados.length === 0) return;
            
            const formData = new FormData();
            formData.append('action', 'cnes_liberar_asientos_sesion');
            formData.append('nonce', cnesAsientos.nonce);

            navigator.sendBeacon(cnesAsientos.ajax_url, formData);
        };

        window.addEventListener('beforeunload', releaseSeatsBeacon);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                releaseSeatsBeacon();
            }
        });
    }

    // Run init
    init();

})();
