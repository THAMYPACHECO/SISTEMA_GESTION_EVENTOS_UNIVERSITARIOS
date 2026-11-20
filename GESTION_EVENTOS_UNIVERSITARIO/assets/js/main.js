/**
 * SISTEMA DE GESTIÓN DE EVENTOS UNIVERSITARIOS
 * main.js - JavaScript principal del sistema
 */

'use strict';

/* ============================================================
   1. CONFIRMAR ELIMINACIÓN
   ============================================================ */
/**
 * Muestra un diálogo de confirmación antes de eliminar un registro.
 * @param {string} msg - Mensaje personalizado (opcional)
 * @returns {boolean} - true si el usuario confirma
 */
function confirmDelete(msg) {
    var message = msg || '¿Está seguro de que desea eliminar este registro? Esta acción no se puede deshacer.';
    return confirm(message);
}

/* ============================================================
   2. AUTO-CERRAR ALERTAS (5 segundos)
   ============================================================ */
function initAutoCloseAlerts() {
    var alerts = document.querySelectorAll('.alert[data-auto-close]');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity .4s ease, transform .4s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(function() {
                if (alert.parentNode) alert.parentNode.removeChild(alert);
            }, 420);
        }, 5000);
    });

    // También para alertas sin data-auto-close (modo general)
    var allAlerts = document.querySelectorAll('.alert:not([data-no-auto])');
    allAlerts.forEach(function(alert) {
        // Botón de cierre manual
        var closeBtn = alert.querySelector('.alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                alert.style.transition = 'opacity .3s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentNode) alert.parentNode.removeChild(alert);
                }, 320);
            });
        }

        // Auto-cierre a los 5 segundos
        if (!alert.hasAttribute('data-no-auto')) {
            setTimeout(function() {
                alert.style.transition = 'opacity .4s ease, max-height .4s ease, margin .4s ease, padding .4s ease';
                alert.style.opacity = '0';
                alert.style.maxHeight = '0';
                alert.style.marginBottom = '0';
                alert.style.paddingTop = '0';
                alert.style.paddingBottom = '0';
                setTimeout(function() {
                    if (alert.parentNode) alert.parentNode.removeChild(alert);
                }, 440);
            }, 5000);
        }
    });
}

/* ============================================================
   3. TOGGLE DEL SIDEBAR EN MÓVIL
   ============================================================ */
function initSidebarToggle() {
    var toggleBtn = document.querySelector('.navbar-toggle');
    var sidebar   = document.querySelector('.sidebar');
    var overlay   = document.querySelector('.sidebar-overlay');
    var mainContent = document.querySelector('.main-content');
    var footer      = document.querySelector('.site-footer');

    if (!toggleBtn || !sidebar) return;

    toggleBtn.addEventListener('click', function() {
        var isMobile = window.innerWidth <= 768;

        if (isMobile) {
            // En móvil: slide in/out desde la izquierda
            sidebar.classList.toggle('mobile-open');
            if (overlay) overlay.classList.toggle('show');
        } else {
            // En desktop: colapsar/expandir ancho
            sidebar.classList.toggle('collapsed');
            if (mainContent) mainContent.classList.toggle('expanded');
            if (footer)      footer.classList.toggle('expanded');
        }
    });

    // Cerrar sidebar al hacer click en el overlay (móvil)
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('show');
        });
    }

    // Ajustar al redimensionar ventana
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('mobile-open');
            if (overlay) overlay.classList.remove('show');
        }
    });
}

/* ============================================================
   4. VALIDACIÓN BÁSICA DE FORMULARIOS (campos vacíos)
   ============================================================ */
/**
 * Valida que todos los campos requeridos de un formulario estén llenos.
 * @param {HTMLFormElement|string} formOrSelector - Formulario o selector CSS
 * @returns {boolean} - true si es válido
 */
function validateForm(formOrSelector) {
    var form = typeof formOrSelector === 'string'
        ? document.querySelector(formOrSelector)
        : formOrSelector;

    if (!form) return true;

    var isValid = true;
    var firstInvalid = null;

    // Limpiar errores previos
    form.querySelectorAll('.form-control.is-invalid').forEach(function(el) {
        el.classList.remove('is-invalid');
    });
    form.querySelectorAll('.invalid-feedback').forEach(function(el) {
        el.textContent = '';
    });

    // Validar campos requeridos
    var required = form.querySelectorAll('[required]');
    required.forEach(function(field) {
        var value = field.value.trim();
        if (!value) {
            field.classList.add('is-invalid');
            isValid = false;
            if (!firstInvalid) firstInvalid = field;

            var feedback = field.parentNode.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.textContent = 'Este campo es obligatorio.';
            }
        }
    });

    // Validar emails
    form.querySelectorAll('input[type="email"]').forEach(function(field) {
        if (field.value.trim() && !isValidEmail(field.value.trim())) {
            field.classList.add('is-invalid');
            isValid = false;
            if (!firstInvalid) firstInvalid = field;
            var feedback = field.parentNode.querySelector('.invalid-feedback');
            if (feedback) feedback.textContent = 'Ingrese un correo electrónico válido.';
        }
    });

    // Validar confirmación de contraseña
    var passField    = form.querySelector('input[name="password"]');
    var passConfirm  = form.querySelector('input[name="confirmar_password"], input[name="password_confirm"]');
    if (passField && passConfirm && passField.value && passConfirm.value) {
        if (passField.value !== passConfirm.value) {
            passConfirm.classList.add('is-invalid');
            isValid = false;
            if (!firstInvalid) firstInvalid = passConfirm;
            var feedback = passConfirm.parentNode.querySelector('.invalid-feedback');
            if (feedback) feedback.textContent = 'Las contraseñas no coinciden.';
        }
    }

    // Scroll al primer campo inválido
    if (firstInvalid) {
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstInvalid.focus();
    }

    return isValid;
}

/**
 * Verifica formato de email básico.
 * @param {string} email
 * @returns {boolean}
 */
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function initFormValidation() {
    var forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
}

/* ============================================================
   5. FILTRAR TABLAS POR TEXTO EN TIEMPO REAL
   ============================================================ */
/**
 * Filtra las filas de una tabla en base a un texto de búsqueda.
 * @param {string} inputSelector - Selector del input de búsqueda
 * @param {string} tableSelector - Selector de la tabla (tbody)
 */
function filterTable(inputSelector, tableSelector) {
    var input = document.querySelector(inputSelector);
    var tbody = document.querySelector(tableSelector + ' tbody') ||
                document.querySelector(tableSelector);

    if (!input || !tbody) return;

    input.addEventListener('input', function() {
        var query = this.value.toLowerCase().trim();
        var rows  = tbody.querySelectorAll('tr');
        var visibleCount = 0;

        rows.forEach(function(row) {
            var text = row.textContent.toLowerCase();
            if (text.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Mostrar estado vacío si no hay resultados
        var emptyMsg = document.querySelector(tableSelector + '-empty');
        if (emptyMsg) {
            emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    });
}

function initTableFilters() {
    // Auto-inicializar filtros con data attributes
    var filterInputs = document.querySelectorAll('[data-filter-table]');
    filterInputs.forEach(function(input) {
        var tableSelector = input.getAttribute('data-filter-table');
        var tbody = document.querySelector(tableSelector + ' tbody') ||
                    document.querySelector(tableSelector);

        if (!tbody) return;

        input.addEventListener('input', function() {
            var query = this.value.toLowerCase().trim();
            var rows  = tbody.querySelectorAll('tr');

            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });
}

/* ============================================================
   6. INICIALIZAR CHART.JS
   ============================================================ */
function initCharts() {
    // Verificar si Chart.js está cargado
    if (typeof Chart === 'undefined') return;

    // Configuración global de Chart.js
    Chart.defaults.font.family = "'Roboto', sans-serif";
    Chart.defaults.color = '#757575';
    Chart.defaults.plugins.legend.position = 'bottom';

    // Paleta de colores institucional
    var COLORS = {
        primary:  '#1a237e',
        light:    '#283593',
        accent:   '#f9a825',
        success:  '#2e7d32',
        danger:   '#c62828',
        info:     '#01579b',
        muted:    '#9e9e9e'
    };

    // ----- Gráfico de eventos por estado -----
    var canvasEstados = document.getElementById('chartEventosEstado');
    if (canvasEstados) {
        var ctxE = canvasEstados.getContext('2d');
        var labelsE  = JSON.parse(canvasEstados.dataset.labels  || '["Programado","En Curso","Finalizado","Cancelado"]');
        var valuesE  = JSON.parse(canvasEstados.dataset.values  || '[0,0,0,0]');

        new Chart(ctxE, {
            type: 'doughnut',
            data: {
                labels: labelsE,
                datasets: [{
                    data: valuesE,
                    backgroundColor: [COLORS.info, COLORS.success, COLORS.muted, COLORS.danger],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // ----- Gráfico de inscripciones por mes -----
    var canvasInscr = document.getElementById('chartInscripcionesMes');
    if (canvasInscr) {
        var ctxI = canvasInscr.getContext('2d');
        var labelsI = JSON.parse(canvasInscr.dataset.labels || '[]');
        var valuesI = JSON.parse(canvasInscr.dataset.values || '[]');

        new Chart(ctxI, {
            type: 'bar',
            data: {
                labels: labelsI,
                datasets: [{
                    label: 'Inscripciones',
                    data: valuesI,
                    backgroundColor: COLORS.primary,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // ----- Gráfico de asistencia -----
    var canvasAsist = document.getElementById('chartAsistencia');
    if (canvasAsist) {
        var ctxA = canvasAsist.getContext('2d');
        var labelsA = JSON.parse(canvasAsist.dataset.labels || '["Presentes","Ausentes"]');
        var valuesA = JSON.parse(canvasAsist.dataset.values || '[0,0]');

        new Chart(ctxA, {
            type: 'pie',
            data: {
                labels: labelsA,
                datasets: [{
                    data: valuesA,
                    backgroundColor: [COLORS.success, COLORS.muted],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
    }

    // ----- Gráfico genérico: buscar todos los canvas con data-chart-type -----
    document.querySelectorAll('canvas[data-chart-type]').forEach(function(canvas) {
        if (canvas._chartInitialized) return;
        canvas._chartInitialized = true;

        var type   = canvas.dataset.chartType   || 'bar';
        var labels = JSON.parse(canvas.dataset.labels || '[]');
        var values = JSON.parse(canvas.dataset.values || '[]');
        var label  = canvas.dataset.label || 'Datos';

        new Chart(canvas.getContext('2d'), {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: values,
                    backgroundColor: [
                        COLORS.primary, COLORS.accent, COLORS.success,
                        COLORS.danger,  COLORS.info,   COLORS.muted
                    ],
                    borderRadius: type === 'bar' ? 5 : 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'bottom' } },
                scales: type === 'pie' || type === 'doughnut'
                    ? undefined
                    : { y: { beginAtZero: true } }
            }
        });
    });
}

/* ============================================================
   7. MODALES (helpers)
   ============================================================ */
function openModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) modal.classList.add('open');
}

function closeModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('open');
}

function initModals() {
    // Cerrar al hacer click fuera del modal-box
    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });

    // Botones de cierre
    document.querySelectorAll('.modal-close, [data-dismiss="modal"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var overlay = this.closest('.modal-overlay');
            if (overlay) overlay.classList.remove('open');
        });
    });

    // Tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(function(m) {
                m.classList.remove('open');
            });
        }
    });

    // Triggers [data-toggle="modal"]
    document.querySelectorAll('[data-toggle="modal"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var target = this.getAttribute('data-target');
            if (target) openModal(target.replace('#', ''));
        });
    });
}

/* ============================================================
   8. INICIALIZACIÓN GENERAL
   ============================================================ */
document.addEventListener('DOMContentLoaded', function() {
    initAutoCloseAlerts();
    initSidebarToggle();
    initFormValidation();
    initTableFilters();
    initCharts();
    initModals();
});

/* ============================================================
   9. MOSTRAR / OCULTAR CONTRASEÑA
   ============================================================ */
function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    var icon = btn ? btn.querySelector('i') : null;
    if (icon) {
        if (isPassword) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}
window.togglePasswordVisibility = togglePasswordVisibility;
