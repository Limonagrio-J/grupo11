document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initFormValidation();
    initConfirmations();
    initTutorMateriaSelector();
    initTutoriasCalendar();
    initFilterDates();
    initReviewStars();
    autoHideAlerts();
});

function initMobileMenu() {
    const toggle = document.querySelector('[data-menu-toggle]');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('[data-sidebar-overlay]');

    if (!toggle || !sidebar) {
        return;
    }

    const closeMenu = () => {
        sidebar.classList.remove('open');
        overlay?.classList.remove('show');
    };

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay?.classList.toggle('show');
    });

    overlay?.addEventListener('click', closeMenu);

    document.querySelectorAll('.sidebar .nav-link').forEach((link) => {
        link.addEventListener('click', closeMenu);
    });
}

function initFormValidation() {
    document.querySelectorAll('form').forEach((form) => {
        form.querySelectorAll('[required]').forEach((field) => {
            field.addEventListener('input', () => {
                field.setCustomValidity('');
            });

            field.addEventListener('change', () => {
                field.setCustomValidity('');
            });
        });

        form.addEventListener('submit', (event) => {
            form.querySelectorAll('[required]').forEach((field) => {
                if (typeof field.value === 'string' && field.value.trim() === '') {
                    field.setCustomValidity('Este campo es obligatorio.');
                } else {
                    field.setCustomValidity('');
                }
            });

            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
            }
        });
    });
}

function initConfirmations() {
    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            const message = element.dataset.confirm || '¿Confirmar acción?';

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
}

function initTutorMateriaSelector() {
    const tutorSelector = document.querySelector('[data-tutor-selector]');
    const materiaSelector = document.querySelector('[data-materia-selector]');

    if (!tutorSelector || !materiaSelector) {
        return;
    }

    const updateMaterias = () => {
        const tutorId = tutorSelector.value;
        let selectedVisible = false;

        [...materiaSelector.options].forEach((option) => {
            if (!option.dataset.tutor) {
                option.hidden = false;
                return;
            }

            const visible = option.dataset.tutor === tutorId;
            option.hidden = !visible;

            if (visible && option.selected) {
                selectedVisible = true;
            }
        });

        if (!selectedVisible && tutorId) {
            materiaSelector.value = '';
        }
    };

    tutorSelector.addEventListener('change', updateMaterias);
    updateMaterias();
}

function initTutoriasCalendar() {
    const calendarElement = document.getElementById('calendar');

    if (!calendarElement || typeof FullCalendar === 'undefined') {
        return;
    }

    const canCreate = calendarElement.dataset.canCreate === '1';
    const endpoint = calendarElement.dataset.calendarUrl;

    const calendar = new FullCalendar.Calendar(calendarElement, {
        locale: 'es',
        firstDay: 1,
        height: 'auto',
        dayMaxEvents: true,
        nowIndicator: true,
        editable: false,
        selectable: false,
        eventDisplay: 'block',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek',
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            list: 'Lista',
        },
        events: {
            url: endpoint,
            method: 'GET',
            failure: () => {
                window.alert('No se pudo cargar el calendario de tutorías.');
            },
        },
        eventClick: (info) => {
            info.jsEvent.preventDefault();

            if (info.event.url) {
                window.location.href = info.event.url;
            }
        },
        eventDidMount: (info) => {
            const props = info.event.extendedProps;

            info.el.title = [
                props.materia || info.event.title,
                props.tutor ? `Tutor: ${props.tutor}` : '',
                props.estudiante ? `Estudiante: ${props.estudiante}` : '',
                props.modalidad ? `Modalidad: ${props.modalidad}` : '',
                props.estado ? `Estado: ${props.estado}` : '',
            ]
                .filter(Boolean)
                .join('\n');
        },
        dateClick: (info) => {
            if (!canCreate) {
                return;
            }

            const partes = info.dateStr.split('T');
            const params = new URLSearchParams({
                accion: 'crear',
                fecha: partes[0],
            });

            if (partes[1]) {
                params.set('hora_inicio', partes[1].slice(0, 5));
            }

            window.location.href = `/controllers/tutorias.php?${params.toString()}`;
        },
    });

    calendar.render();
}

function initFilterDates() {
    const desde = document.getElementById('fecha_desde');
    const hasta = document.getElementById('fecha_hasta');

    if (!desde || !hasta) {
        return;
    }

    const updateRange = () => {
        hasta.min = desde.value || '';
    };

    desde.addEventListener('change', updateRange);
    updateRange();
}

function initReviewStars() {
    const container = document.querySelector('[data-star-input]');

    if (!container) {
        return;
    }

    container.addEventListener('change', () => {
        container.classList.add('selected');
    });
}

function autoHideAlerts() {
    document.querySelectorAll('.alert').forEach((alert) => {
        window.setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-4px)';

            window.setTimeout(() => {
                alert.remove();
            }, 250);
        }, 6000);
    });
}
