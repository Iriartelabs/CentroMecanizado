<?php
/**
 * Clase User
 * Gestiona las operaciones relacionadas con los usuarios
 */

class User {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Registra un nuevo usuario
     * @param array $data Datos del usuario
     * @return bool|int ID del usuario registrado o false si falla
     */
    public function register($data) {
        try {
            // Verificar si el correo ya está registrado
            if ($this->findUserByEmail($data['email'])) {
                return false;
            }
            
            // Generar el hash de la contraseña
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Insertar usuario
            $this->db->query("INSERT INTO users (name, email, password, phone, address, company) 
                             VALUES (:name, :email, :password, :phone, :address, :company)");
            
            $this->db->bind(':name', $data['name']);
            $this->db->bind(':email', $data['email']);
            $this->db->bind(':password', $data['password']);
            $this->db->bind(':phone', isset($data['phone']) ? $data['phone'] : null);
            $this->db->bind(':address', isset($data['address']) ? $data['address'] : null);
            $this->db->bind(':company', isset($data['company']) ? $data['company'] : null);
            
            $this->db->execute();
            
            // Obtener el ID del usuario insertado
            $userId = $this->db->lastInsertId();
            
            // Crear configuraciones por defecto
            $this->db->query("INSERT INTO user_settings (user_id) VALUES (:user_id)");
            $this->db->bind(':user_id', $userId);
            $this->db->execute();
            
            // Confirmar transacción
            $this->db->commit();
            
            return $userId;
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            logMessage("Error al registrar usuario: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Busca un usuario por su correo electrónico
     * @param string $email Correo electrónico
     * @return array|false Datos del usuario o false si no se encuentra
     */
    public function findUserByEmail($email) {
        $this->db->query("SELECT * FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }
    
    /**
     * Busca un usuario por su ID
     * @param int $id ID del usuario
     * @return array|false Datos del usuario o false si no se encuentra
     */
    public function findUserById($id) {
        $this->db->query("SELECT * FROM users WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    /**
     * Inicia sesión con un usuario
     * @param string $email Correo electrónico
     * @param string $password Contraseña
     * @return array|false Datos del usuario o false si falla
     */
    public function login($email, $password) {
        $user = $this->findUserByEmail($email);
        
        // Verificar si el usuario existe y la contraseña es correcta
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
    
    /**
     * Actualiza los datos de un usuario
     * @param array $data Datos del usuario
     * @return bool True si la actualización fue exitosa
     */
    public function update($data) {
        try {
            $this->db->query("UPDATE users SET name = :name, phone = :phone, address = :address, 
                              company = :company, updated_at = NOW() 
                              WHERE id = :id");
            
            $this->db->bind(':name', $data['name']);
            $this->db->bind(':phone', isset($data['phone']) ? $data['phone'] : null);
            $this->db->bind(':address', isset($data['address']) ? $data['address'] : null);
            $this->db->bind(':company', isset($data['company']) ? $data['company'] : null);
            $this->db->bind(':id', $data['id']);
            
            return $this->db->execute();
        } catch (Exception $e) {
            logMessage("Error al actualizar usuario: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Cambia la contraseña de un usuario
     * @param int $userId ID del usuario
     * @param string $currentPassword Contraseña actual
     * @param string $newPassword Nueva contraseña
     * @return bool True si el cambio fue exitoso
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Obtener el usuario
            $user = $this->findUserById($userId);
            
            // Verificar si la contraseña actual es correcta
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return false;
            }
            
            // Generar el hash de la nueva contraseña
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Actualizar la contraseña
            $this->db->query("UPDATE users SET password = :password, updated_at = NOW() 
                              WHERE id = :id");
            
            $this->db->bind(':password', $passwordHash);
            $this->db->bind(':id', $userId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            logMessage("Error al cambiar contraseña: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Genera un token para restablecer la contraseña
     * @param string $email Correo electrónico
     * @return string|false Token generado o false si falla
     */
    public function generateResetToken($email) {
        try {
            $user = $this->findUserByEmail($email);
            
            if (!$user) {
                return false;
            }
            
            // Generar token aleatorio
            $token = generateRandomString(32);
            
            // Establecer fecha de expiración (24 horas)
            $expiryDate = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Guardar el token en la base de datos
            $this->db->query("UPDATE users SET reset_token = :token, reset_token_expires = :expires 
                              WHERE id = :id");
            
            $this->db->bind(':token', $token);
            $this->db->bind(':expires', $expiryDate);
            $this->db->bind(':id', $user['id']);
            
            if ($this->db->execute()) {
                return $token;
            }
            
            return false;
        } catch (Exception $e) {
            logMessage("Error al generar token de restablecimiento: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Verifica un token de restablecimiento de contraseña
     * @param string $token Token a verificar
     * @return array|false Datos del usuario o false si el token no es válido
     */
    public function verifyResetToken($token) {
        try {
            $this->db->query("SELECT * FROM users WHERE reset_token = :token AND reset_token_expires > NOW()");
            $this->db->bind(':token', $token);
            return $this->db->single();
        } catch (Exception $e) {
            logMessage("Error al verificar token de restablecimiento: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Restablece la contraseña de un usuario
     * @param string $token Token de restablecimiento
     * @param string $newPassword Nueva contraseña
     * @return bool True si el restablecimiento fue exitoso
     */
    public function resetPassword($token, $newPassword) {
        try {
            // Verificar el token
            $user = $this->verifyResetToken($token);
            
            if (!$user) {
                return false;
            }
            
            // Generar el hash de la nueva contraseña
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Actualizar la contraseña y limpiar el token
            $this->db->query("UPDATE users SET password = :password, reset_token = NULL, 
                              reset_token_expires = NULL, updated_at = NOW() 
                              WHERE id = :id");
            
            $this->db->bind(':password', $passwordHash);
            $this->db->bind(':id', $user['id']);
            
            return $this->db->execute();
        } catch (Exception $e) {
            logMessage("Error al restablecer contraseña: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Activa o desactiva un usuario
     * @param int $userId ID del usuario
     * @param bool $active Estado de activación
     * @return bool True si la operación fue exitosa
     */
    public function setActiveStatus($userId, $active) {
        try {
            $this->db->query("UPDATE users SET active = :active, updated_at = NOW() 
                              WHERE id = :id");
            
            $this->db->bind(':active', $active ? 1 : 0);
            $this->db->bind(':id', $userId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            logMessage("Error al cambiar estado de activación: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Obtiene la lista de usuarios (para administradores)
     * @param string $searchTerm Término de búsqueda (opcional)
     * @param string $role Rol de usuario (opcional)
     * @param int $limit Límite de resultados (opcional)
     * @param int $offset Desplazamiento (opcional)
     * @return array Lista de usuarios
     */
    public function getUsers($searchTerm = '', $role = '', $limit = 100, $offset = 0) {
        try {
            $sql = "SELECT id, name, email, phone, company, role, active, created_at 
                    FROM users WHERE 1=1";
            $params = [];
            
            // Añadir filtro de búsqueda
            if (!empty($searchTerm)) {
                $sql .= " AND (name LIKE :search OR email LIKE :search OR company LIKE :search)";
                $params[':search'] = "%{$searchTerm}%";
            }
            
            // Añadir filtro de rol
            if (!empty($role)) {
                $sql .= " AND role = :role";
                $params[':role'] = $role;
            }
            
            // Añadir ordenación y límites
            $sql .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $this->db->query($sql);
            
            // Vincular parámetros
            foreach ($params as $param => $value) {
                if ($param == ':limit' || $param == ':offset') {
                    $this->db->bind($param, $value, PDO::PARAM_INT);
                } else {
                    $this->db->bind($param, $value);
                }
            }
            
            return $this->db->resultSet();
        } catch (Exception $e) {
            logMessage("Error al obtener usuarios: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Obtiene las configuraciones de un usuario
     * @param int $userId ID del usuario
     * @return array|false Configuraciones del usuario o false si no se encuentran
     */
    public function getUserSettings($userId) {
        try {
            $this->db->query("SELECT * FROM user_settings WHERE user_id = :user_id");
            $this->db->bind(':user_id', $userId);
            return $this->db->single();
        } catch (Exception $e) {
            logMessage("Error al obtener configuraciones de usuario: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Actualiza las configuraciones de un usuario
     * @param int $userId ID del usuario
     * @param array $settings Configuraciones a actualizar
     * @return bool True si la actualización fue exitosa
     */
    public function updateUserSettings($userId, $settings) {
        try {
            $this->db->query("UPDATE user_settings SET 
                              email_notifications = :email_notifications, 
                              sms_notifications = :sms_notifications, 
                              language = :language, 
                              updated_at = NOW() 
                              WHERE user_id = :user_id");
            
            $this->db->bind(':email_notifications', isset($settings['email_notifications']) ? 1 : 0);
            $this->db->bind(':sms_notifications', isset($settings['sms_notifications']) ? 1 : 0);
            $this->db->bind(':language', $settings['language'] ?? 'es');
            $this->db->bind(':user_id', $userId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            logMessage("Error al actualizar configuraciones de usuario: " . $e->getMessage(), 'error');
            return false;
        }
    }
}
