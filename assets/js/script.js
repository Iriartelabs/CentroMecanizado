/**
 * Script principal para DentalMEC
 */

// Esperar a que el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializar los tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializar los popovers de Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-cerrar las alertas después de 5 segundos
    setTimeout(function() {
        var alertList = [].slice.call(document.querySelectorAll('.alert'));
        alertList.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Inicializar la funcionalidad de tarjetas de selección
    var selectionCards = document.querySelectorAll('.selection-card');
    selectionCards.forEach(function(card) {
        card.addEventListener('click', function() {
            // Quitar la clase 'selected' de todas las tarjetas
            selectionCards.forEach(function(c) {
                c.classList.remove('selected');
            });
            
            // Añadir la clase 'selected' a la tarjeta clicada
            this.classList.add('selected');
            
            // Si hay un input oculto relacionado, actualizarlo
            var input = document.querySelector(this.dataset.input);
            if (input) {
                input.value = this.dataset.value;
            }
        });
    });
    
    // Inicializar la funcionalidad de Dropzone para subida de archivos
    var dropzoneElement = document.querySelector('.dropzone');
    if (dropzoneElement) {
        // Eventos para efectos visuales
        dropzoneElement.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        dropzoneElement.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });
        
        dropzoneElement.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            // Si hay un input file relacionado, actualizar sus archivos
            var fileInput = document.querySelector(this.dataset.input);
            if (fileInput && e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                
                // Disparar el evento change para que se ejecuten los handlers
                var event = new Event('change');
                fileInput.dispatchEvent(event);
            }
        });
        
        // Clic para seleccionar archivos
        dropzoneElement.addEventListener('click', function() {
            var fileInput = document.querySelector(this.dataset.input);
            if (fileInput) {
                fileInput.click();
            }
        });
    }
    
    // Manejador para mostrar nombres de archivos seleccionados
    var fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            var fileList = this.files;
            var fileNames = [];
            
            for (var i = 0; i < fileList.length; i++) {
                fileNames.push(fileList[i].name);
            }
            
            // Si hay un elemento para mostrar los nombres, actualizarlo
            var fileNameElement = document.querySelector(this.dataset.fileList);
            if (fileNameElement) {
                if (fileNames.length > 0) {
                    fileNameElement.innerHTML = fileNames.join('<br>');
                    fileNameElement.style.display = 'block';
                } else {
                    fileNameElement.innerHTML = '';
                    fileNameElement.style.display = 'none';
                }
            }
        });
    });
    
    // Inicializar validación de formularios
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Funcionalidad para los comentarios
    var commentForm = document.getElementById('comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            var commentText = document.getElementById('comment-text').value;
            if (commentText.trim() === '') {
                return;
            }
            
            // Aquí normalmente enviarías los datos vía AJAX
            // Para el prototipo, simplemente limpiamos el campo
            document.getElementById('comment-text').value = '';
        });
    }
});

// Función para confirmar acciones
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Función para mostrar detalles de un pedido
function showOrderDetails(orderId) {
    // Normalmente cargarías los detalles vía AJAX
    // Para el prototipo, podrías redirigir a la página de detalles
    window.location.href = 'order-details.php?id=' + orderId;
}

// Función para cambiar el estado de un pedido
function changeOrderStatus(orderId, status) {
    // Normalmente enviarías una petición AJAX
    confirmAction('¿Estás seguro de que deseas cambiar el estado del pedido?', function() {
        // Aquí iría la llamada AJAX
        console.log('Cambiando estado del pedido ' + orderId + ' a ' + status);
    });
}

// Función para eliminar un pedido
function deleteOrder(orderId) {
    confirmAction('¿Estás seguro de que deseas eliminar este pedido? Esta acción no se puede deshacer.', function() {
        // Aquí iría la llamada AJAX
        console.log('Eliminando pedido ' + orderId);
    });
}
