<?php
/**
 * Clase File
 * Gestiona las operaciones relacionadas con los archivos
 */

class File {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Sube un archivo y lo registra en la base de datos
     * @param array $file Datos del archivo ($_FILES)
     * @param int $orderId ID del pedido asociado
     * @return bool|int ID del archivo registrado o false si falla
     */
    public function uploadFile($file, $orderId) {
        try {
            // Verificar si hay errores de subida
            if ($file['error'] !== UPLOAD_ERR_OK) {
                logMessage("Error al subir archivo: código " . $file['error'], 'error');
                return false;
            }
            
            // Verificar tamaño máximo
            if ($file['size'] > MAX_FILE_SIZE) {
                logMessage("Error al subir archivo: tamaño excede el límite permitido", 'error');
                return false;
            }
            
            // Verificar tipo de archivo
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!isAllowedFileType($fileExtension)) {
                logMessage("Error al subir archivo: tipo no permitido ({$fileExtension})", 'error');
                return false;
            }
            
            // Usar el nombre original del archivo (manteniendo la relación con constructioninfo)
            $originalName = $file['name'];
            
            // Determinar la ruta de almacenamiento (servidor externo o local)
            $filePath = $this->determineFilePath($originalName, $orderId);
            
            // Si el archivo ya existe, manejar el caso
            if (file_exists($filePath)) {
                $filePath = $this->handleDuplicateFile($filePath);
            }
            
            // Mover el archivo a la ubicación final
            if (!$this->moveUploadedFile($file['tmp_name'], $filePath)) {
                return false;
            }
            
            // Obtener el nombre del archivo después de manejar posibles duplicados
            $storedName = basename($filePath);
            
            // Registrar el archivo en la base de datos
            $this->db->query("INSERT INTO files (order_id, original_name, stored_name, file_path, file_size, file_type) 
                             VALUES (:order_id, :original_name, :stored_name, :file_path, :file_size, :file_type)");
            
            $this->db->bind(':order_id', $orderId);
            $this->db->bind(':original_name', $originalName);
            $this->db->bind(':stored_name', $storedName);
            $this->db->bind(':file_path', $filePath);
            $this->db->bind(':file_size', $file['size']);
            $this->db->bind(':file_type', $fileExtension);
            
            $this->db->execute();
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            logMessage("Error al subir archivo: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Maneja el caso de un archivo duplicado
     * @param string $filePath Ruta del archivo que ya existe
     * @return string Nueva ruta para el archivo
     */
    private function handleDuplicateFile($filePath) {
        $dirName = dirname($filePath);
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        $fileExt = pathinfo($filePath, PATHINFO_EXTENSION);
        
        $counter = 1;
        $newFilePath = $filePath;
        
        // Generar un nuevo nombre mientras exista un archivo con ese nombre
        while (file_exists($newFilePath)) {
            $newFilePath = $dirName . '/' . $fileName . '_' . $counter . '.' . $fileExt;
            $counter++;
        }
        
        return $newFilePath;
    }
    
    /**
     * Determina la ruta de almacenamiento del archivo
     * @param string $fileName Nombre del archivo
     * @param int $orderId ID del pedido asociado
     * @return string Ruta completa del archivo
     */
    private function determineFilePath($fileName, $orderId) {
        // Crear directorio para el pedido si no existe
        $yearMonth = date('Y/m');
        
        // Verificar si debemos usar el servidor de archivos externo
        if (defined('FILE_SERVER_PATH') && !empty(FILE_SERVER_PATH)) {
            $orderPath = FILE_SERVER_PATH . '/' . $yearMonth . '/' . $orderId;
        } else {
            $orderPath = UPLOAD_PATH . $yearMonth . '/' . $orderId;
        }
        
        // Crear estructura de directorios si no existe
        if (!is_dir($orderPath)) {
            mkdir($orderPath, 0755, true);
        }
        
        return $orderPath . '/' . $fileName;
    }
    
    /**
     * Mueve el archivo subido a su ubicación final
     * @param string $tempPath Ruta temporal del archivo
     * @param string $finalPath Ruta final del archivo
     * @return bool True si la operación fue exitosa
     */
    private function moveUploadedFile($tempPath, $finalPath) {
        // Intentar mover el archivo
        if (!move_uploaded_file($tempPath, $finalPath)) {
            logMessage("Error al mover archivo de {$tempPath} a {$finalPath}", 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtiene un archivo por su ID
     * @param int $fileId ID del archivo
     * @return array|false Datos del archivo o false si no se encuentra
     */
    public function getFileById($fileId) {
        try {
            $this->db->query("SELECT * FROM files WHERE id = :id");
            $this->db->bind(':id', $fileId);
            return $this->db->single();
        } catch (Exception $e) {
            logMessage("Error al obtener archivo: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Obtiene los archivos de un pedido
     * @param int $orderId ID del pedido
     * @return array Lista de archivos
     */
    public function getOrderFiles($orderId) {
        try {
            $this->db->query("SELECT * FROM files WHERE order_id = :order_id ORDER BY uploaded_at DESC");
            $this->db->bind(':order_id', $orderId);
            return $this->db->resultSet();
        } catch (Exception $e) {
            logMessage("Error al obtener archivos de pedido: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Elimina un archivo
     * @param int $fileId ID del archivo
     * @return bool True si la eliminación fue exitosa
     */
    public function deleteFile($fileId) {
        try {
            // Obtener información del archivo
            $file = $this->getFileById($fileId);
            
            if (!$file) {
                return false;
            }
            
            // Eliminar el archivo físico
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }
            
            // Eliminar registro de la base de datos
            $this->db->query("DELETE FROM files WHERE id = :id");
            $this->db->bind(':id', $fileId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            logMessage("Error al eliminar archivo: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Crea una versión temporal de un archivo para descarga
     * @param int $fileId ID del archivo
     * @return string|false Ruta del archivo temporal o false si falla
     */
    public function createTemporaryFile($fileId) {
        try {
            // Obtener información del archivo
            $file = $this->getFileById($fileId);
            
            if (!$file || !file_exists($file['file_path'])) {
                return false;
            }
            
            // Crear directorio temporal si no existe
            if (!is_dir(TEMP_UPLOAD_PATH)) {
                mkdir(TEMP_UPLOAD_PATH, 0755, true);
            }
            
            // Usar el nombre original para el archivo temporal
            $tempPath = TEMP_UPLOAD_PATH . $file['original_name'];
            
            // Manejar duplicados si es necesario
            if (file_exists($tempPath)) {
                $tempPath = $this->handleDuplicateFile($tempPath);
            }
            
            // Copiar el archivo a la ubicación temporal
            if (!copy($file['file_path'], $tempPath)) {
                return false;
            }
            
            return $tempPath;
        } catch (Exception $e) {
            logMessage("Error al crear archivo temporal: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Limpia los archivos temporales antiguos
     * @param int $maxAge Edad máxima en segundos (por defecto 1 hora)
     * @return int Número de archivos eliminados
     */
    public function cleanupTemporaryFiles($maxAge = 3600) {
        try {
            $count = 0;
            
            // Verificar si el directorio existe
            if (!is_dir(TEMP_UPLOAD_PATH)) {
                return 0;
            }
            
            // Obtener todos los archivos en el directorio temporal
            $files = glob(TEMP_UPLOAD_PATH . '*');
            $now = time();
            
            foreach ($files as $file) {
                // Verificar si el archivo es más antiguo que la edad máxima
                if (is_file($file) && ($now - filemtime($file) > $maxAge)) {
                    if (unlink($file)) {
                        $count++;
                    }
                }
            }
            
            return $count;
        } catch (Exception $e) {
            logMessage("Error al limpiar archivos temporales: " . $e->getMessage(), 'error');
            return 0;
        }
    }
}
