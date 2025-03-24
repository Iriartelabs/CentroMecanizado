<?php
/**
 * Página de registro
 */

// Incluir el archivo de configuración
require_once 'config.php';

// Si el usuario ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . 'dashboard.php');
}

// Verificar si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $company = clean($_POST['company'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $address = clean($_POST['address'] ?? '');
    
    // Validar datos
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'El nombre es obligatorio';
    }
    
    if (empty($email)) {
        $errors[] = 'El correo electrónico es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del correo electrónico no es válido';
    }
    
    if (empty($password)) {
        $errors[] = 'La contraseña es obligatoria';
    } elseif (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Las contraseñas no coinciden';
    }
    
    // Si no hay errores, proceder con el registro
    if (empty($errors)) {
        // Crear instancia de la clase User
        $userModel = new User();
        
        // Preparar datos para el registro
        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'company' => $company,
            'phone' => $phone,
            'address' => $address
        ];
        
        // Intentar registrar al usuario
        $userId = $userModel->register($userData);
        
        if ($userId) {
            // Registrar en el log
            logMessage("Nuevo usuario registrado: {$name} (ID: {$userId})", 'info');
            
            // Mostrar mensaje de éxito y redirigir al login
            $_SESSION['alert_message'] = '¡Registro exitoso! Ahora puedes iniciar sesión.';
            $_SESSION['alert_type'] = 'success';
            redirect(BASE_URL . 'login.php');
        } else {
            $errors[] = 'El correo electrónico ya está registrado o ha ocurrido un error.';
        }
    }
}

// Establecer el título de la página
$page_title = 'Registro';

// Incluir el encabezado
include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <img src="<?= BASE_URL . $GLOBALS['app_settings']['logo']; ?>" alt="Logo" height="60">
                        <h2 class="mt-3">Crear una cuenta</h2>
                        <p class="text-muted">Regístrate para acceder a nuestros servicios de mecanizado dental</p>
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
                    
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nombre completo *</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= isset($name) ? $name : ''; ?>" required>
                                <div class="invalid-feedback">
                                    Por favor, introduce tu nombre completo.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Correo electrónico *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= isset($email) ? $email : ''; ?>" required>
                                <div class="invalid-feedback">
                                    Por favor, introduce un correo electrónico válido.
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Contraseña *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">
                                    Por favor, introduce una contraseña (mínimo 6 caracteres).
                                </div>
                                <div class="form-text">La contraseña debe tener al menos 6 caracteres.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirmar contraseña *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div class="invalid-feedback">
                                    Por favor, confirma tu contraseña.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="company" class="form-label">Empresa o Clínica</label>
                            <input type="text" class="form-control" id="company" name="company" value="<?= isset($company) ? $company : ''; ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= isset($phone) ? $phone : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?= isset($address) ? $address : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">Acepto los <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">términos y condiciones</a> *</label>
                            <div class="invalid-feedback">
                                Debes aceptar los términos y condiciones para continuar.
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Registrarse</button>
                        </div>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p class="mb-0">¿Ya tienes una cuenta? <a href="<?= BASE_URL; ?>login.php">Inicia sesión aquí</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Términos y Condiciones -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Términos y Condiciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>1. Introducción</h5>
                <p>Bienvenido a DentalMEC. Estos términos y condiciones rigen el uso de nuestros servicios de mecanizado dental.</p>
                
                <h5>2. Servicios</h5>
                <p>Ofrecemos servicios de fresado, sinterizado e impresión 3D para profesionales del sector dental.</p>
                
                <h5>3. Responsabilidades del Usuario</h5>
                <p>El usuario es responsable de proporcionar archivos en formatos compatibles y de verificar la idoneidad de los productos finales para su uso dental.</p>
                
                <h5>4. Privacidad y Protección de Datos</h5>
                <p>Cumplimos con el Reglamento General de Protección de Datos (RGPD). Para más información, consulte nuestra Política de Privacidad.</p>
                
                <h5>5. Propiedad Intelectual</h5>
                <p>Los archivos enviados por los usuarios son considerados propiedad del usuario. No utilizaremos estos archivos para otros fines sin consentimiento explícito.</p>
                
                <h5>6. Limitación de Responsabilidad</h5>
                <p>No nos hacemos responsables del uso final de los productos mecanizados. Es responsabilidad del profesional dental asegurar su idoneidad para el paciente.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir el pie de página
include_once 'includes/footer.php';
?>