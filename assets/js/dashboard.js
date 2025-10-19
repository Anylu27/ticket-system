/**
 * Sistema de Tickets - Hospital Santa Fe
 * JavaScript principal del dashboard
 */

// Configuración global
const CONFIG = {
    baseUrl: window.location.origin,
    apiUrl: './api/',
    dateFormat: 'DD/MM/YYYY',
    timeFormat: 'HH:mm:ss'
};

// Utilidades globales
const Utils = {
    // Formatear fecha
    formatDate: function(date, format = CONFIG.dateFormat) {
        if (!date) return '';
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');
        
        return format
            .replace('DD', day)
            .replace('MM', month)
            .replace('YYYY', year)
            .replace('HH', hours)
            .replace('mm', minutes)
            .replace('ss', seconds);
    },

    // Mostrar notificación
    showNotification: function(message, type = 'info', duration = 5000) {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Agregar al DOM
        let container = document.getElementById('notifications-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notifications-container';
            container.className = 'notifications-container';
            document.body.appendChild(container);
        }
        
        container.appendChild(notification);
        
        // Auto-ocultar después del tiempo especificado
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.add('notification-fadeout');
                setTimeout(() => notification.remove(), 300);
            }
        }, duration);
    },

    // Obtener icono para notificación
    getNotificationIcon: function(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-circle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    },

    // Confirmar acción
    confirm: function(message, title = 'Confirmar acción') {
        return new Promise((resolve) => {
            if (window.confirm(message)) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    },

    // Debounce function
    debounce: function(func, wait, immediate) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    },

    // Escapar HTML
    escapeHtml: function(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
};

// Gestión de formularios
const FormManager = {
    // Validar formulario
    validate: function(form) {
        const errors = [];
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                errors.push(`El campo "${this.getFieldLabel(field)}" es requerido`);
                this.highlightError(field);
            } else {
                this.removeError(field);
            }
        });
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    },

    // Obtener etiqueta del campo
    getFieldLabel: function(field) {
        const label = field.closest('.form-group').querySelector('label');
        return label ? label.textContent.replace('*', '').trim() : field.name;
    },

    // Resaltar error en campo
    highlightError: function(field) {
        field.classList.add('error');
        field.style.borderColor = '#dc3545';
    },

    // Remover error de campo
    removeError: function(field) {
        field.classList.remove('error');
        field.style.borderColor = '';
    }
};

// Gestión de modales
const ModalManager = {
    // Abrir modal
    open: function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        
        // Enfocar primer input
        const firstInput = modal.querySelector('input, select, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    },

    // Cerrar modal
    close: function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    },

    // Cerrar todos los modales
    closeAll: function() {
        const modales = document.querySelectorAll('.modal');
        modales.forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.classList.remove('modal-open');
    }
};

// Funciones globales para el sistema
function nuevoCorrectivo() {
    Utils.showNotification('Redirigiendo a nuevo correctivo...', 'info');
    window.open('nuevo_correctivo.php', '_blank');
}


function nuevoPreventivo() {
    Utils.showNotification('Redirigiendo a nuevo preventivo...', 'info');
    // window.location.href = 'preventivos.php?action=new';
}

function editarRegistro(id, tipo) {
    Utils.showNotification(`Editando ${tipo} #${id}...`, 'info');
    // Implementar lógica de edición
}

function cambiarEstado(id, tipo, nuevoEstado) {
    Utils.confirm(`¿Está seguro que desea cambiar el estado a "${nuevoEstado}"?`)
        .then(confirmed => {
            if (confirmed) {
                Utils.showNotification(`Estado cambiado a ${nuevoEstado}`, 'success');
                // Implementar lógica de cambio de estado
            }
        });
}

// Inicialización del sistema
document.addEventListener('DOMContentLoaded', function() {
    // Configurar cierre de modales con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ModalManager.closeAll();
        }
    });

    // Configurar cierre de modales al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            ModalManager.close(e.target.id);
        }
    });

    // Configurar confirmaciones para enlaces de eliminación
    document.querySelectorAll('a[data-confirm]').forEach(link => {
        link.addEventListener('click', async function(e) {
            e.preventDefault();
            const message = this.dataset.confirm;
            const confirmed = await Utils.confirm(message);
            if (confirmed) {
                window.location.href = this.href;
            }
        });
    });

    console.log('Sistema de Tickets inicializado correctamente');
});

// Exponer funciones globales
window.Utils = Utils;
window.FormManager = FormManager;
window.ModalManager = ModalManager;