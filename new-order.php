<?php
/**
 * Página de creación de nuevo pedido
 */

// Incluir el archivo de configuración
require_once 'config.php';

// Verificar si el usuario ha iniciado sesión
if (!isLoggedIn()) {
    $_SESSION['alert_message'] = "Debes iniciar sesión para crear un nuevo pedido.";
    $_SESSION['alert_type'] = 'warning';
    redirect(BASE_URL . 'login.php');
}

// Obtener el ID del usuario actual
$userId = $_SESSION['user_id'];

// Inicializar variables para el flujo de creación de pedido
$currentStep = isset($_SESSION['order_step']) ? $_SESSION['order_step'] : 1;
$orderData = isset($_SESSION['order_data']) ? $_SESSION['order_data'] : [];
$orderId = isset($_SESSION['temp_order_id']) ? $_SESSION['temp_order_id'] : null;

// Procesar formularios según el paso actual
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Paso 1: Selección del tipo de proceso
    if (isset($_POST['step1_submit'])) {
        $processType = clean($_POST['process_type']);
        
        // Validar tipo de proceso
        if (!in_array($processType, [PROCESS_TYPE_MILLING, PROCESS_TYPE_SINTERING, PROCESS_TYPE_PRINTING])) {
            $errors[] = 'Debes seleccionar un tipo de proceso válido.';
        } else {
            // Guardar datos y avanzar al siguiente paso
            $orderData['process_type'] = $processType;
            $_SESSION['order_data'] = $orderData;
            $currentStep = 2;
            $_SESSION['order_step'] = $currentStep;
        }
    }
    
    // Paso 2: Subida de archivos
    elseif (isset($_POST['step2_submit'])) {
        // Verificar si se han subido archivos
        if (empty($_FILES['files']['name'][0])) {
            $errors[] = 'Debes subir al menos un archivo.';
        } else {
            // Si no hay un pedido temporal, crearlo
            if (!$orderId) {
                $orderModel = new Order();
                $orderData['user_id'] = $userId;
                
                $orderId = $orderModel->create($orderData);
                
                if (!$orderId) {
                    $errors[] = 'Ha ocurrido un error al crear el pedido. Inténtalo de nuevo.';
                } else {
                    $_SESSION['temp_order_id'] = $orderId;
                }
            }
            
            // Procesar archivos
            if ($orderId && !isset($errors)) {
                $fileModel = new File();
                $uploadErrors = [];
                
                // Procesar cada archivo
                for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
                    // Crear array con la estructura de $_FILES para un solo archivo
                    $file = [
                        'name' => $_FILES['files']['name'][$i],
                        'type' => $_FILES['files']['type'][$i],
                        'tmp_name' => $_FILES['files']['tmp_name'][$i],
                        'error' => $_FILES['files']['error'][$i],
                        'size' => $_FILES['files']['size'][$i]
                    ];
                    
                    // Subir el archivo
                    if (!$fileModel->uploadFile($file, $orderId)) {
                        $uploadErrors[] = "Error al subir el archivo {$file['name']}.";
                    }
                }
                
                if (!empty($uploadErrors)) {
                    $errors = $uploadErrors;
                } else {
                    // Avanzar al siguiente paso
                    $currentStep = 3;
                    $_SESSION['order_step'] = $currentStep;
                }
            }
        }
    }
    
    // Paso 3: Detalles del pedido
    elseif (isset($_POST['step3_submit'])) {
        $material = clean($_POST['material'] ?? '');
        $color = clean($_POST['color'] ?? '');
        $observations = clean($_POST['observations'] ?? '');
        
        // Guardar datos
        $orderData['material'] = $material;
        $orderData['color'] = $color;
        $orderData['observations'] = $observations;
        $_SESSION['order_data'] = $orderData;
        
        // Actualizar el pedido temporal
        if ($orderId) {
            $orderModel = new Order();
            $updateData = [
                'material' => $material,
                'color' => $color,
                'observations' => $observations
            ];
            
            if ($orderModel->updateOrder($orderId, $updateData)) {
                // Avanzar al siguiente paso
                $currentStep = 4;
                $_SESSION['order_step'] = $currentStep;
            } else {
                $errors[] = 'Ha ocurrido un error al actualizar el pedido. Inténtalo de nuevo.';
            }
        } else {
            $errors[] = 'No se ha podido encontrar el pedido temporal. Inténtalo de nuevo.';
        }
    }
    
    // Paso 4: Confirmación del pedido
    elseif (isset($_POST['step4_submit'])) {
        if ($orderId) {
            $orderModel = new Order();
            // Cambiar el estado del pedido a "pendiente"
            if ($orderModel->updateStatus($orderId, ORDER_STATUS_PENDING, 'Pedido enviado por el cliente', $userId)) {
                // Limpiar datos temporales
                unset($_SESSION['order_step']);
                unset($_SESSION['order_data']);
                unset($_SESSION['temp_order_id']);
                
                // Mostrar mensaje de éxito y redirigir
                $_SESSION['alert_message'] = '¡Pedido creado con éxito! Puedes seguir su estado en la página de pedidos.';
                $_SESSION['alert_type'] = 'success';
                redirect(BASE_URL . 'order-details.php?id=' . $orderId);
            } else {
                $errors[] = 'Ha ocurrido un error al finalizar el pedido. Inténtalo de nuevo.';
            }
        } else {
            $errors[] = 'No se ha podido encontrar el pedido temporal. Inténtalo de nuevo.';
        }
    }
    
    // Retroceder un paso
    elseif (isset($_POST['back_step'])) {
        $currentStep--;
        if ($currentStep < 1) {
            $currentStep = 1;
        }
        $_SESSION['order_step'] = $currentStep;
    }
    
    // Cancelar todo el proceso
    elseif (isset($_POST['cancel_order'])) {
        // Si hay un pedido temporal, eliminarlo
        if ($orderId) {
            $orderModel = new Order();
            $orderModel->deleteOrder($orderId);
        }
        
        // Limpiar datos temporales
        unset($_SESSION['order_step']);
        unset($_SESSION['order_data']);
        unset($_SESSION['temp_order_id']);
        
        // Redirigir al dashboard
        redirect(BASE_URL . 'dashboard.php');
    }
}

// Obtener datos adicionales según el paso actual
$filesList = [];

if ($currentStep >= 3 && $orderId) {
    // Obtener lista de archivos subidos
    $fileModel = new File();
    $filesList = $fileModel->getOrderFiles($orderId);
}

// Establecer el título de la página
$page_title = 'Nuevo Pedido';

// Incluir el encabezado
include_once 'includes/header.php';
?>

<!-- Título de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Nuevo Pedido</h1>
</div>

<?php if (isset($errors) && !empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?= $error; ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Indicador de pasos -->
<div class="process-steps mb-4">
    <div class="process-step <?= $currentStep >= 1 ? 'active' : ''; ?> <?= $currentStep > 1 ? 'completed' : ''; ?>">
        <div class="process-step-number"><?= $currentStep > 1 ? '✓' : '1'; ?></div>
        <div class="process-step-title">Tipo de Proceso</div>
    </div>
    <div class="process-step <?= $currentStep >= 2 ? 'active' : ''; ?> <?= $currentStep > 2 ? 'completed' : ''; ?>">
        <div class="process-step-number"><?= $currentStep > 2 ? '✓' : '2'; ?></div>
        <div class="process-step-title">Subir Archivos</div>
    </div>
    <div class="process-step <?= $currentStep >= 3 ? 'active' : ''; ?> <?= $currentStep > 3 ? 'completed' : ''; ?>">
        <div class="process-step-number"><?= $currentStep > 3 ? '✓' : '3'; ?></div>
        <div class="process-step-title">Detalles</div>
    </div>
    <div class="process-step <?= $currentStep >= 4 ? 'active' : ''; ?>">
        <div class="process-step-number">4</div>
        <div class="process-step-title">Confirmar</div>
    </div>
</div>

<!-- Contenido según el paso actual -->
<div class="card">
    <div class="card-body p-4">
        
        <?php if ($currentStep == 1): ?>
        <!-- Paso 1: Selección de tipo de proceso -->
        <h2 class="mb-3">Selecciona el tipo de proceso</h2>
        <p class="text-muted mb-4">Elige el proceso que necesitas para tu trabajo dental.</p>
        
        <form method="POST" action="" id="step1Form">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="selection-card <?= isset($orderData['process_type']) && $orderData['process_type'] === PROCESS_TYPE_MILLING ? 'selected' : ''; ?>" data-value="<?= PROCESS_TYPE_MILLING; ?>" data-input="#process_type">
                        <div class="selection-icon">
                            <i class="fas fa-cog fa-3x"></i>
                        </div>
                        <h3 class="selection-title">Fresado</h3>
                        <p class="selection-description">Precisión máxima para coronas, puentes e inlays. Compatible con zirconio, disilicato y PMMA.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="selection-card <?= isset($orderData['process_type']) && $orderData['process_type'] === PROCESS_TYPE_SINTERING ? 'selected' : ''; ?>" data-value="<?= PROCESS_TYPE_SINTERING; ?>" data-input="#process_type">
                        <div class="selection-icon">
                            <i class="fas fa-fire fa-3x"></i>
                        </div>
                        <h3 class="selection-title">Sinterizado</h3>
                        <p class="selection-description">Ideal para estructuras metálicas y prótesis. Acabados de alta calidad y precisión.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="selection-card <?= isset($orderData['process_type']) && $orderData['process_type'] === PROCESS_TYPE_PRINTING ? 'selected' : ''; ?>" data-value="<?= PROCESS_TYPE_PRINTING; ?>" data-input="#process_type">
                        <div class="selection-icon">
                            <i class="fas fa-print fa-3x"></i>
                        </div>
                        <h3 class="selection-title">Impresión 3D</h3>
                        <p class="selection-description">Para modelos, guías quirúrgicas, férulas y más. Alta precisión para aplicaciones dentales.</p>
                    </div>
                </div>
            </div>
            
            <input type="hidden" id="process_type" name="process_type" value="<?= isset($orderData['process_type']) ? $orderData['process_type'] : ''; ?>">
            
            <div class="d-flex justify-content-between mt-4">
                <button type="submit" name="cancel_order" class="btn btn-outline-secondary">Cancelar</button>
                <button type="submit" name="step1_submit" class="btn btn-primary">Continuar</button>
            </div>
        </form>
        
        <?php elseif ($currentStep == 2): ?>
        <!-- Paso 2: Subida de archivos -->
        <h2 class="mb-3">Sube tus archivos</h2>
        <p class="text-muted mb-4">Arrastra y suelta tus archivos o haz clic para seleccionarlos.</p>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <!-- Botón tradicional para subir archivos -->
            <div class="mb-3">
                <label for="file-input" class="form-label">Selecciona archivos</label>
                <input type="file" class="form-control" id="file-input" name="files[]" multiple>
                <div class="form-text">Formatos aceptados: <?= implode(', ', ALLOWED_FILE_TYPES); ?> (máximo <?= formatSize(MAX_FILE_SIZE); ?> por archivo)</div>
            </div>

            <!-- Área para arrastrar archivos (estilo visual solamente) -->
            <div class="dropzone mt-4 mb-4">
                <div class="dropzone-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="dropzone-text">Arrastra y suelta tus archivos aquí</div>
                <div class="dropzone-hint">O haz clic para seleccionar archivos usando el botón de arriba</div>
            </div>
            
            <div id="file-list" class="mt-3"></div>
            
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <button type="submit" name="back_step" class="btn btn-outline-secondary me-2">Atrás</button>
                    <button type="submit" name="cancel_order" class="btn btn-outline-danger">Cancelar</button>
                </div>
                <button type="submit" name="step2_submit" class="btn btn-primary">Continuar</button>
            </div>
        </form>
        
        <?php elseif ($currentStep == 3): ?>
        <!-- Paso 3: Detalles del pedido -->
        <h2 class="mb-3">Detalles del pedido</h2>
        <p class="text-muted mb-4">Proporciona información adicional para completar tu pedido.</p>
        
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="material" class="form-label">Material</label>
                        <select class="form-select" id="material" name="material">
                            <option value="">Seleccionar material...</option>
                            <?php if ($orderData['process_type'] === PROCESS_TYPE_MILLING): ?>
                                <option value="zirconio" <?= isset($orderData['material']) && $orderData['material'] === 'zirconio' ? 'selected' : ''; ?>>Zirconio</option>
                                <option value="disilicato" <?= isset($orderData['material']) && $orderData['material'] === 'disilicato' ? 'selected' : ''; ?>>Disilicato de litio</option>
                                <option value="pmma" <?= isset($orderData['material']) && $orderData['material'] === 'pmma' ? 'selected' : ''; ?>>PMMA</option>
                                <option value="coCr" <?= isset($orderData['material']) && $orderData['material'] === 'coCr' ? 'selected' : ''; ?>>CoCr</option>
                            <?php elseif ($orderData['process_type'] === PROCESS_TYPE_SINTERING): ?>
                                <option value="cromo" <?= isset($orderData['material']) && $orderData['material'] === 'cromo' ? 'selected' : ''; ?>>Cromo Cobalto</option>
                                <option value="titanio" <?= isset($orderData['material']) && $orderData['material'] === 'titanio' ? 'selected' : ''; ?>>Titanio</option>
                            <?php elseif ($orderData['process_type'] === PROCESS_TYPE_PRINTING): ?>
                                <option value="resina" <?= isset($orderData['material']) && $orderData['material'] === 'resina' ? 'selected' : ''; ?>>Resina</option>
                                <option value="biocompatible" <?= isset($orderData['material']) && $orderData['material'] === 'biocompatible' ? 'selected' : ''; ?>>Material Biocompatible</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="color" class="form-label">Color</label>
                        <select class="form-select" id="color" name="color">
                            <option value="">Seleccionar color...</option>
                            <?php if ($orderData['process_type'] === PROCESS_TYPE_MILLING): ?>
                                <option value="A1" <?= isset($orderData['color']) && $orderData['color'] === 'A1' ? 'selected' : ''; ?>>A1</option>
                                <option value="A2" <?= isset($orderData['color']) && $orderData['color'] === 'A2' ? 'selected' : ''; ?>>A2</option>
                                <option value="A3" <?= isset($orderData['color']) && $orderData['color'] === 'A3' ? 'selected' : ''; ?>>A3</option>
                                <option value="A3.5" <?= isset($orderData['color']) && $orderData['color'] === 'A3.5' ? 'selected' : ''; ?>>A3.5</option>
                                <option value="A4" <?= isset($orderData['color']) && $orderData['color'] === 'A4' ? 'selected' : ''; ?>>A4</option>
                                <option value="B1" <?= isset($orderData['color']) && $orderData['color'] === 'B1' ? 'selected' : ''; ?>>B1</option>
                                <option value="B2" <?= isset($orderData['color']) && $orderData['color'] === 'B2' ? 'selected' : ''; ?>>B2</option>
                                <option value="B3" <?= isset($orderData['color']) && $orderData['color'] === 'B3' ? 'selected' : ''; ?>>B3</option>
                                <option value="B4" <?= isset($orderData['color']) && $orderData['color'] === 'B4' ? 'selected' : ''; ?>>B4</option>
                                <option value="C1" <?= isset($orderData['color']) && $orderData['color'] === 'C1' ? 'selected' : ''; ?>>C1</option>
                                <option value="C2" <?= isset($orderData['color']) && $orderData['color'] === 'C2' ? 'selected' : ''; ?>>C2</option>
                                <option value="C3" <?= isset($orderData['color']) && $orderData['color'] === 'C3' ? 'selected' : ''; ?>>C3</option>
                                <option value="C4" <?= isset($orderData['color']) && $orderData['color'] === 'C4' ? 'selected' : ''; ?>>C4</option>
                                <option value="D2" <?= isset($orderData['color']) && $orderData['color'] === 'D2' ? 'selected' : ''; ?>>D2</option>
                                <option value="D3" <?= isset($orderData['color']) && $orderData['color'] === 'D3' ? 'selected' : ''; ?>>D3</option>
                                <option value="D4" <?= isset($orderData['color']) && $orderData['color'] === 'D4' ? 'selected' : ''; ?>>D4</option>
                            <?php elseif ($orderData['process_type'] === PROCESS_TYPE_PRINTING): ?>
                                <option value="transparente" <?= isset($orderData['color']) && $orderData['color'] === 'transparente' ? 'selected' : ''; ?>>Transparente</option>
                                <option value="modelo" <?= isset($orderData['color']) && $orderData['color'] === 'modelo' ? 'selected' : ''; ?>>Color Modelo</option>
                                <option value="encia" <?= isset($orderData['color']) && $orderData['color'] === 'encia' ? 'selected' : ''; ?>>Color Encía</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="observations" class="form-label">Observaciones</label>
                <textarea class="form-control" id="observations" name="observations" rows="4"><?= isset($orderData['observations']) ? $orderData['observations'] : ''; ?></textarea>
                <small class="text-muted">Añade cualquier instrucción o detalle adicional para el procesamiento.</small>
            </div>
            
            <h4 class="mt-4">Archivos subidos</h4>
            <?php if (empty($filesList)): ?>
                <p class="text-muted">No se han subido archivos.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tamaño</th>
                                <th>Tipo</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filesList as $file): ?>
                            <tr>
                                <td><?= $file['original_name']; ?></td>
                                <td><?= formatSize($file['file_size']); ?></td>
                                <td><?= strtoupper($file['file_type']); ?></td>
                                <td><?= formatDateTime($file['uploaded_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <button type="submit" name="back_step" class="btn btn-outline-secondary me-2">Atrás</button>
                    <button type="submit" name="cancel_order" class="btn btn-outline-danger">Cancelar</button>
                </div>
                <button type="submit" name="step3_submit" class="btn btn-primary">Continuar</button>
            </div>
        </form>
        
        <?php elseif ($currentStep == 4): ?>
        <!-- Paso 4: Confirmación -->
        <h2 class="mb-3">Confirmar pedido</h2>
        <p class="text-muted mb-4">Revisa los detalles de tu pedido antes de enviarlo.</p>
        
        <?php
        // Obtener detalles del pedido
        $orderModel = new Order();
        $fileModel = new File();
        $order = $orderModel->getOrderById($orderId);
        $files = $fileModel->getOrderFiles($orderId);
        
        if ($order):
        ?>
        <div class="row">
            <div class="col-md-6">
                <h4>Detalles del pedido</h4>
                <ul class="list-group mb-4">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
						<span>Número de referencia:</span>
						<span class="badge bg-primary"><?= htmlspecialchars($order['reference_number']); ?></span>
					</li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Tipo de proceso:</span>
                        <span><?= getProcessTypeText($order['process_type']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Material:</span>
                        <span><?= $order['material'] ?: 'No especificado'; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Color:</span>
                        <span><?= $order['color'] ?: 'No especificado'; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Fecha estimada:</span>
                        <span><?= formatDateTime($order['estimated_completion_date'], 'd/m/Y'); ?></span>
                    </li>
                </ul>
                
                <?php if (!empty($order['observations'])): ?>
                <h5>Observaciones</h5>
                <div class="card mb-4">
                    <div class="card-body">
                        <?= nl2br($order['observations']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <h4>Archivos subidos</h4>
                <?php if (empty($files)): ?>
                    <p class="text-muted">No se han subido archivos.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tamaño</th>
                                    <th>Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($files as $file): ?>
                                <tr>
                                    <td><?= $file['original_name']; ?></td>
                                    <td><?= formatSize($file['file_size']); ?></td>
                                    <td><?= strtoupper($file['file_type']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <form method="POST" action="">
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <button type="submit" name="back_step" class="btn btn-outline-secondary me-2">Atrás</button>
                    <button type="submit" name="cancel_order" class="btn btn-outline-danger">Cancelar</button>
                </div>
                <button type="submit" name="step4_submit" class="btn btn-success">Enviar Pedido</button>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-danger">
                No se pudo obtener información del pedido. Por favor, inténtalo de nuevo.
            </div>
            <div class="text-center mt-4">
                <a href="<?= BASE_URL; ?>dashboard.php" class="btn btn-primary">Volver al Dashboard</a>
            </div>
        <?php endif; ?>
        
        <?php endif; ?>
        
    </div>
</div>

<!-- Script específico para la funcionalidad de la página -->
<script>
// Ejecutar una vez que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    // 1. FUNCIONALIDAD DE SELECCIÓN DE TARJETAS (Paso 1)
    var selectionCards = document.querySelectorAll('.selection-card');
    selectionCards.forEach(function(card) {
        card.addEventListener('click', function() {
            // Quitar la clase 'selected' de todas las tarjetas
            selectionCards.forEach(function(c) {
                c.classList.remove('selected');
            });
            
            // Añadir la clase 'selected' a la tarjeta clicada
            this.classList.add('selected');
            
            // Actualizar el input oculto con el valor seleccionado
            var inputId = this.dataset.input;
            var input = document.querySelector(inputId);
            if (input) {
                input.value = this.dataset.value;
                console.log('Input actualizado:', inputId, 'con valor:', this.dataset.value);
            }
        });
    });
    
    // 2. FUNCIONALIDAD DE SUBIDA DE ARCHIVOS (Paso 2)
    var fileInput = document.getElementById('file-input');
    var fileList = document.getElementById('file-list');
    var dropzone = document.querySelector('.dropzone');
    
    // Mostrar archivos seleccionados
    if (fileInput && fileList) {
        fileInput.addEventListener('change', function() {
            fileList.innerHTML = '';
            
            if (this.files.length > 0) {
                var heading = document.createElement('h5');
                heading.textContent = 'Archivos seleccionados:';
                fileList.appendChild(heading);
                
                var list = document.createElement('ul');
                list.className = 'list-group mt-2';
                
                for (var i = 0; i < this.files.length; i++) {
                    var file = this.files[i];var file = this.files[i];
                    var size = (file.size / 1024).toFixed(2) + ' KB';
                    
                    var item = document.createElement('li');
                    item.className = 'list-group-item d-flex justify-content-between align-items-center';
                    
                    var fileName = document.createElement('span');
                    fileName.textContent = file.name;
                    
                    var fileSize = document.createElement('span');
                    fileSize.className = 'badge bg-primary rounded-pill';
                    fileSize.textContent = size;
                    
                    item.appendChild(fileName);
                    item.appendChild(fileSize);
                    list.appendChild(item);
                }
                
                fileList.appendChild(list);
                fileList.style.display = 'block';
            } else {
                fileList.style.display = 'none';
            }
        });
    }
    
    // Funcionalidad Drag & Drop
    if (dropzone && fileInput) {
        // Prevenir comportamiento por defecto para evitar que el navegador abra los archivos
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Resaltar la dropzone cuando se arrastra un archivo sobre ella
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropzone.classList.add('dragover');
        }
        
        function unhighlight() {
            dropzone.classList.remove('dragover');
        }
        
        // Manejar archivos soltados
        dropzone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            var dt = e.dataTransfer;
            var files = dt.files;
            
            // Transferir archivos al input de tipo file
            fileInput.files = files;
            
            // Disparar el evento change para actualizar la UI
            var event = new Event('change');
            fileInput.dispatchEvent(event);
        }
        
        // Hacer que el dropzone sea clickeable para abrir el selector de archivos
        dropzone.addEventListener('click', function() {
            fileInput.click();
        }, false);
    }
});
</script>

<?php
// Función para formatear el tamaño de archivo
function formatSize($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $size > 1024; $i++) {
        $size /= 1024;
    }
    
    return round($size, 2) . ' ' . $units[$i];
}

// Incluir el pie de página
include_once 'includes/footer.php';
?>