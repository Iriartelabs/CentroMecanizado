<?php
/**
 * Clase Order
 * Gestiona las operaciones relacionadas con los pedidos
 */

class Order {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crea un nuevo pedido
     * @param array $data Datos del pedido
     * @return int|bool ID del pedido creado o false si falla
     */
    public function create($data) {
        try {
            // Generar número de referencia único
            $referenceNumber = $this->generateReferenceNumber();
            
            // Calcular fecha estimada de finalización basada en el tipo de proceso
            $estimatedCompletionDate = $this->calculateEstimatedCompletionDate($data['process_type']);
            
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Insertar pedido
            $this->db->query("INSERT INTO orders (user_id, reference_number, process_type, status, 
                                                 material, color, observations, estimated_completion_date) 
                             VALUES (:user_id, :reference_number, :process_type, :status, 
                                     :material, :color, :observations, :estimated_completion_date)");
            
            $this->db->bind(':user_id', $data['user_id']);
            $this->db->bind(':reference_number', $referenceNumber);
            $this->db->bind(':process_type', $data['process_type']);
            $this->db->bind(':status', ORDER_STATUS_NEW);
            $this->db->bind(':material', $data['material'] ?? null);
            $this->db->bind(':color', $data['color'] ?? null);
            $this->db->bind(':observations', $data['observations'] ?? null);
            $this->db->bind(':estimated_completion_date', $estimatedCompletionDate);
            
            $this->db->execute();
            
            // Obtener el ID del pedido insertado
            $orderId = $this->db->lastInsertId();
            
            // Registrar el estado inicial en el historial
            $this->addStatusHistory($orderId, ORDER_STATUS_NEW, 'Pedido creado', $data['user_id']);
            
            // Confirmar transacción
            $this->db->commit();
            
            return $orderId;
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            logMessage("Error al crear pedido: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
	 * Genera un número de referencia único para el pedido
	 * @return string Número de referencia
	 */
	private function generateReferenceNumber() {
		// Formato: AAAAMMDD-XXXX (donde XXXX es un número secuencial)
		$date = date('Ymd');
		
		// Buscar el último número secuencial usado para la fecha actual
		$this->db->query("SELECT reference_number FROM orders 
						 WHERE reference_number LIKE :date_prefix 
						 ORDER BY reference_number DESC LIMIT 1");
		$this->db->bind(':date_prefix', $date . '-%');
		$lastOrder = $this->db->single();
		
		// Determinar el siguiente número secuencial
		if ($lastOrder && isset($lastOrder['reference_number'])) {
			// Extraer el número secuencial de la última referencia
			$parts = explode('-', $lastOrder['reference_number']);
			if (count($parts) > 1) {
				$lastSequence = (int)$parts[1];
				$newSequence = $lastSequence + 1;
			} else {
				// En caso de un formato incorrecto, comenzar desde 1
				$newSequence = 1;
			}
		} else {
			// Si no hay pedidos previos para esta fecha, empezar desde 1
			$newSequence = 1;
		}
		
		// Formatear el número secuencial con ceros a la izquierda (0001-9999)
		$sequenceFormatted = str_pad($newSequence, 4, '0', STR_PAD_LEFT);
		
		// Crear el número de referencia completo
		$referenceNumber = "{$date}-{$sequenceFormatted}";
		
		// Verificar la unicidad (por seguridad, aunque es improbable un conflicto)
		$this->db->query("SELECT id FROM orders WHERE reference_number = :reference_number");
		$this->db->bind(':reference_number', $referenceNumber);
		$existing = $this->db->single();
		
		if ($existing) {
			// En el caso extremadamente raro de un conflicto, incrementamos y volvemos a verificar
			logMessage("Conflicto de número de referencia detectado: {$referenceNumber}", 'warning');
			$newSequence++;
			$sequenceFormatted = str_pad($newSequence, 4, '0', STR_PAD_LEFT);
			$referenceNumber = "{$date}-{$sequenceFormatted}";
		}
		
		return $referenceNumber;
	}
    
    /**
     * Calcula la fecha estimada de finalización basada en el tipo de proceso
     * @param string $processType Tipo de proceso
     * @return string Fecha estimada en formato Y-m-d
     */
    private function calculateEstimatedCompletionDate($processType) {
        // Obtener el tiempo de procesamiento en horas
        $processingHours = $GLOBALS['processing_times'][$processType] ?? 24;
        
        // Convertir a días (redondeando hacia arriba)
        $processingDays = ceil($processingHours / 24);
        
        // Calcular la fecha estimada
        $today = new DateTime();
        $today->modify("+{$processingDays} days");
        
        // Verificar si la fecha cae en fin de semana
        while ($today->format('N') >= 6) { // 6 = Sábado, 7 = Domingo
            $today->modify('+1 day');
        }
        
        return $today->format('Y-m-d');
    }
    
    /**
     * Añade un registro al historial de estados
     * @param int $orderId ID del pedido
     * @param string $status Estado del pedido
     * @param string $notes Notas adicionales
     * @param int $userId ID del usuario que realiza el cambio
     * @return bool True si la operación fue exitosa
     */
    public function addStatusHistory($orderId, $status, $notes = '', $userId = null) {
        try {
            $this->db->query("INSERT INTO status_history (order_id, status, notes, created_by) 
                             VALUES (:order_id, :status, :notes, :created_by)");
            
            $this->db->bind(':order_id', $orderId);
            $this->db->bind(':status', $status);
            $this->db->bind(':notes', $notes);
            $this->db->bind(':created_by', $userId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            logMessage("Error al añadir historial de estado: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Actualiza el estado de un pedido
     * @param int $orderId ID del pedido
     * @param string $status Nuevo estado
     * @param string $notes Notas adicionales
     * @param int $userId ID del usuario que realiza el cambio
     * @return bool True si la actualización fue exitosa
     */
    public function updateStatus($orderId, $status, $notes = '', $userId = null) {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Actualizar el estado del pedido
            $this->db->query("UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id");
            $this->db->bind(':status', $status);
            $this->db->bind(':id', $orderId);
            $this->db->execute();
            
            // Si el estado es "completado", establecer la fecha de finalización
            if ($status === ORDER_STATUS_COMPLETED) {
                $this->db->query("UPDATE orders SET completion_date = NOW() WHERE id = :id");
                $this->db->bind(':id', $orderId);
                $this->db->execute();
            }
            
            // Añadir al historial de estados
            $this->addStatusHistory($orderId, $status, $notes, $userId);
            
            // Obtener información del pedido para la notificación
            $order = $this->getOrderById($orderId);
            
            // Crear notificación para el usuario
            if ($order) {
                $userId = $order['user_id'];
                $statusText = getOrderStatusText($status);
                $title = "Pedido #{$order['reference_number']} - Estado actualizado";
                $message = "Tu pedido #{$order['reference_number']} ha sido actualizado a: {$statusText}";
                
                $this->createNotification($userId, $title, $message, 'order', $orderId);
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return true;
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            logMessage("Error al actualizar estado de pedido: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Crea una notificación para un usuario
     * @param int $userId ID del usuario
     * @param string $title Título de la notificación
     * @param string $message Mensaje de la notificación
     * @param string $relatedTo Tipo de relación
     * @param int $relatedId ID relacionado
     * @return bool True si la operación fue exitosa
     */
    private function createNotification($userId, $title, $message, $relatedTo, $relatedId) {
        try {
            $this->db->query("INSERT INTO notifications (user_id, title, message, related_to, related_id) 
                             VALUES (:user_id, :title, :message, :related_to, :related_id)");
            
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':title', $title);
            $this->db->bind(':message', $message);
            $this->db->bind(':related_to', $relatedTo);
            $this->db->bind(':related_id', $relatedId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            logMessage("Error al crear notificación: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Obtiene un pedido por su ID
     * @param int $orderId ID del pedido
     * @return array|false Datos del pedido o false si no se encuentra
     */
    public function getOrderById($orderId) {
        try {
            $this->db->query("SELECT * FROM orders WHERE id = :id");
            $this->db->bind(':id', $orderId);
            return $this->db->single();
        } catch (Exception $e) {
            logMessage("Error al obtener pedido: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Obtiene los pedidos de un usuario
     * @param int $userId ID del usuario
     * @param string $status Estado del pedido (opcional)
     * @param int $limit Límite de resultados (opcional)
     * @param int $offset Desplazamiento (opcional)
     * @param string $processType Tipo de proceso (opcional)
     * @return array Lista de pedidos
     */
    public function getUserOrders($userId, $status = '', $limit = 100, $offset = 0, $processType = '') {
        try {
            $sql = "SELECT o.*, 
                    (SELECT COUNT(*) FROM files WHERE order_id = o.id) AS file_count,
                    (SELECT COUNT(*) FROM comments WHERE order_id = o.id) AS comment_count
                    FROM orders o 
                    WHERE o.user_id = :user_id";
            $params = [':user_id' => $userId];
            
            // Añadir filtro de estado
            if (!empty($status)) {
                $sql .= " AND o.status = :status";
                $params[':status'] = $status;
            }
            
            // Añadir filtro de tipo de proceso
            if (!empty($processType)) {
                $sql .= " AND o.process_type = :process_type";
                $params[':process_type'] = $processType;
            }
            
            // Añadir ordenación y límites
            $sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
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
            logMessage("Error al obtener pedidos de usuario: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Obtiene los detalles completos de un pedido
     * @param int $orderId ID del pedido
     * @return array|false Detalles del pedido o false si no se encuentra
     */
    public function getOrderDetails($orderId) {
        try {
            // Obtener datos del pedido
            $order = $this->getOrderById($orderId);
            
            if (!$order) {
                return false;
            }
            
            // Obtener archivos asociados
            $this->db->query("SELECT * FROM files WHERE order_id = :order_id");
            $this->db->bind(':order_id', $orderId);
            $files = $this->db->resultSet();
            
            // Obtener historial de estados
            $this->db->query("SELECT sh.*, u.name as user_name 
                             FROM status_history sh 
                             LEFT JOIN users u ON sh.created_by = u.id 
                             WHERE sh.order_id = :order_id 
                             ORDER BY sh.created_at DESC");
            $this->db->bind(':order_id', $orderId);
            $statusHistory = $this->db->resultSet();
            
            // Obtener comentarios
            $this->db->query("SELECT c.*, u.name as user_name 
                             FROM comments c 
                             JOIN users u ON c.user_id = u.id 
                             WHERE c.order_id = :order_id 
                             ORDER BY c.created_at DESC");
            $this->db->bind(':order_id', $orderId);
            $comments = $this->db->resultSet();
            
            // Combinar todo en un array
            $orderDetails = [
                'order' => $order,
                'files' => $files,
                'status_history' => $statusHistory,
                'comments' => $comments
            ];
            
            return $orderDetails;
        } catch (Exception $e) {
            logMessage("Error al obtener detalles de pedido: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Añade un comentario a un pedido
     * @param int $orderId ID del pedido
     * @param int $userId ID del usuario que hace el comentario
     * @param string $comment Texto del comentario
     * @return bool True si la operación fue exitosa
     */
    public function addComment($orderId, $userId, $comment) {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Insertar comentario
            $this->db->query("INSERT INTO comments (order_id, user_id, comment) 
                             VALUES (:order_id, :user_id, :comment)");
            
            $this->db->bind(':order_id', $orderId);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':comment', $comment);
            $this->db->execute();
            
            // Obtener información del pedido y el usuario que comenta
            $order = $this->getOrderById($orderId);
            $this->db->query("SELECT name FROM users WHERE id = :id");
            $this->db->bind(':id', $userId);
            $user = $this->db->single();
            
            // Crear notificación para el propietario del pedido (si no es el mismo que comenta)
            if ($order && $order['user_id'] != $userId) {
                $orderUserId = $order['user_id'];
                $title = "Nuevo comentario en pedido #{$order['reference_number']}";
                $message = "{$user['name']} ha añadido un comentario a tu pedido #{$order['reference_number']}";
                
                $this->createNotification($orderUserId, $title, $message, 'comment', $orderId);
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return true;
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            logMessage("Error al añadir comentario: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Obtiene estadísticas de pedidos para un usuario
     * @param int $userId ID del usuario
     * @return array Estadísticas de pedidos
     */
    public function getUserOrderStats($userId) {
        try {
            // Total de pedidos activos
            $this->db->query("SELECT 
                             COUNT(*) as total_active,
                             SUM(CASE WHEN status = :status_new OR status = :status_pending THEN 1 ELSE 0 END) as pending,
                             SUM(CASE WHEN status = :status_processing THEN 1 ELSE 0 END) as processing
                             FROM orders 
                             WHERE user_id = :user_id 
                             AND (status = :status_new OR status = :status_pending OR status = :status_processing)");
            
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':status_new', ORDER_STATUS_NEW);
            $this->db->bind(':status_pending', ORDER_STATUS_PENDING);
            $this->db->bind(':status_processing', ORDER_STATUS_PROCESSING);
            
            $activeStats = $this->db->single();
            
            // Total de pedidos completados este mes
            $this->db->query("SELECT COUNT(*) as monthly_completed
                             FROM orders 
                             WHERE user_id = :user_id 
                             AND status = :status_completed
                             AND MONTH(completion_date) = MONTH(CURRENT_DATE())
                             AND YEAR(completion_date) = YEAR(CURRENT_DATE())");
            
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':status_completed', ORDER_STATUS_COMPLETED);
            
            $completedStats = $this->db->single();
            
            // Tiempo promedio de procesamiento
            $this->db->query("SELECT AVG(DATEDIFF(completion_date, created_at)) as avg_time
                             FROM orders 
                             WHERE user_id = :user_id 
                             AND status = :status_completed
                             AND completion_date IS NOT NULL");
            
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':status_completed', ORDER_STATUS_COMPLETED);
            
            $timeStats = $this->db->single();
            
            // Próxima entrega
            $this->db->query("SELECT id, reference_number, estimated_completion_date
                             FROM orders 
                             WHERE user_id = :user_id 
                             AND (status = :status_processing OR status = :status_pending)
                             ORDER BY estimated_completion_date ASC
                             LIMIT 1");
            
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':status_processing', ORDER_STATUS_PROCESSING);
            $this->db->bind(':status_pending', ORDER_STATUS_PENDING);
            
            $nextDelivery = $this->db->single();
            
            // Combinar todo en un array
            $stats = [
                'active_orders' => $activeStats['total_active'] ?? 0,
                'pending_orders' => $activeStats['pending'] ?? 0,
                'processing_orders' => $activeStats['processing'] ?? 0,
                'monthly_orders' => $completedStats['monthly_completed'] ?? 0,
                'average_time' => round($timeStats['avg_time'] ?? 0, 1),
                'next_delivery' => $nextDelivery ?? null
            ];
            
            return $stats;
        } catch (Exception $e) {
            logMessage("Error al obtener estadísticas de pedidos: " . $e->getMessage(), 'error');
            return [
                'active_orders' => 0,
                'pending_orders' => 0,
                'processing_orders' => 0,
                'monthly_orders' => 0,
                'average_time' => 0,
                'next_delivery' => null
            ];
        }
    }
    
    /**
     * Actualiza los detalles de un pedido
     * @param int $orderId ID del pedido
     * @param array $data Datos a actualizar
     * @return bool True si la actualización fue exitosa
     */
    public function updateOrder($orderId, $data) {
        try {
            $sql = "UPDATE orders SET ";
            $params = [];
            $updates = [];
            
            // Verificar qué campos actualizar
            if (isset($data['process_type'])) {
                $updates[] = "process_type = :process_type";
                $params[':process_type'] = $data['process_type'];
            }
            
            if (isset($data['material'])) {
                $updates[] = "material = :material";
                $params[':material'] = $data['material'];
            }
            
            if (isset($data['color'])) {
                $updates[] = "color = :color";
                $params[':color'] = $data['color'];
            }
            
            if (isset($data['observations'])) {
                $updates[] = "observations = :observations";
                $params[':observations'] = $data['observations'];
            }
            
            if (isset($data['estimated_completion_date'])) {
                $updates[] = "estimated_completion_date = :estimated_completion_date";
                $params[':estimated_completion_date'] = $data['estimated_completion_date'];
            }
            
            if (isset($data['total_price'])) {
                $updates[] = "total_price = :total_price";
                $params[':total_price'] = $data['total_price'];
            }
            
            // Si no hay nada que actualizar, devolver true
            if (empty($updates)) {
                return true;
            }
            
            // Completar la consulta SQL
            $sql .= implode(', ', $updates);
            $sql .= ", updated_at = NOW() WHERE id = :id";
            $params[':id'] = $orderId;
            
            $this->db->query($sql);
            
            // Vincular parámetros
            foreach ($params as $param => $value) {
                $this->db->bind($param, $value);
            }
            
            return $this->db->execute();
        } catch (Exception $e) {
            logMessage("Error al actualizar pedido: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Elimina un pedido
     * @param int $orderId ID del pedido
     * @return bool True si la eliminación fue exitosa
     */
    public function deleteOrder($orderId) {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Eliminar archivos asociados (físicamente)
            $this->db->query("SELECT * FROM files WHERE order_id = :order_id");
            $this->db->bind(':order_id', $orderId);
            $files = $this->db->resultSet();
            
            foreach ($files as $file) {
                if (file_exists($file['file_path'])) {
                    unlink($file['file_path']);
                }
            }
            
            // Eliminar el pedido (las demás tablas se eliminarán por las restricciones de clave externa)
            $this->db->query("DELETE FROM orders WHERE id = :id");
            $this->db->bind(':id', $orderId);
            $result = $this->db->execute();
            
            // Confirmar transacción
            $this->db->commit();
            
            return $result;
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            logMessage("Error al eliminar pedido: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Obtiene todos los pedidos (para administradores)
     * @param string $searchTerm Término de búsqueda (opcional)
     * @param string $status Estado del pedido (opcional)
     * @param string $processType Tipo de proceso (opcional)
     * @param int $limit Límite de resultados (opcional)
     * @param int $offset Desplazamiento (opcional)
     * @return array Lista de pedidos
     */
    public function getAllOrders($searchTerm = '', $status = '', $processType = '', $limit = 100, $offset = 0) {
        try {
            $sql = "SELECT o.*, u.name as user_name, u.email as user_email,
                    (SELECT COUNT(*) FROM files WHERE order_id = o.id) AS file_count,
                    (SELECT COUNT(*) FROM comments WHERE order_id = o.id) AS comment_count
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id
                    WHERE 1=1";
            $params = [];
            
            // Añadir filtro de búsqueda
            if (!empty($searchTerm)) {
                $sql .= " AND (o.reference_number LIKE :search OR u.name LIKE :search OR u.email LIKE :search)";
                $params[':search'] = "%{$searchTerm}%";
            }
            
            // Añadir filtro de estado
            if (!empty($status)) {
                $sql .= " AND o.status = :status";
                $params[':status'] = $status;
            }
            
            // Añadir filtro de tipo de proceso
            if (!empty($processType)) {
                $sql .= " AND o.process_type = :process_type";
                $params[':process_type'] = $processType;
            }
            
            // Añadir ordenación y límites
            $sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
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
            logMessage("Error al obtener todos los pedidos: " . $e->getMessage(), 'error');
            return [];
        }
    }
}