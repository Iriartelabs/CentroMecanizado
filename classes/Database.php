
<?php
/**
 * Clase Database
 * Maneja la conexión a la base de datos MySQL utilizando el patrón Singleton
 */

class Database {
    private static $instance = null;
    private $connection;
    private $statement;
    
    /**
     * Constructor privado para prevenir instanciación directa
     */
    private function __construct() {
        try {
            // Creamos un objeto PDO para la conexión
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Registramos el error y mostramos un mensaje amigable
            error_log("Error de conexión: " . $e->getMessage(), 0);
            die("Ha ocurrido un error al conectar con la base de datos. Por favor, contacte al administrador.");
        }
    }
    
    /**
     * Método para obtener la instancia de la clase (Singleton)
     * @return Database Instancia de la clase Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Prepara una consulta SQL
     * @param string $sql Consulta SQL
     * @return Database Instancia actual para encadenamiento
     */
    public function query($sql) {
        $this->statement = $this->connection->prepare($sql);
        return $this;
    }
    
    /**
     * Vincula un valor a un parámetro
     * @param string $param Nombre del parámetro
     * @param mixed $value Valor a vincular
     * @param mixed $type Tipo de dato (opcional)
     * @return Database Instancia actual para encadenamiento
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->statement->bindValue($param, $value, $type);
        return $this;
    }
    
    /**
     * Ejecuta la consulta preparada
     * @return bool True si la ejecución fue exitosa
     */
    public function execute() {
        return $this->statement->execute();
    }
    
    /**
     * Obtiene un solo registro como un objeto
     * @return object Registro obtenido
     */
    public function single() {
        $this->execute();
        return $this->statement->fetch();
    }
    
    /**
     * Obtiene todos los registros como un array de objetos
     * @return array Array de registros
     */
    public function resultSet() {
        $this->execute();
        return $this->statement->fetchAll();
    }
    
    /**
     * Obtiene el número de filas afectadas
     * @return int Número de filas
     */
    public function rowCount() {
        return $this->statement->rowCount();
    }
    
    /**
     * Obtiene el último ID insertado
     * @return string Último ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Inicia una transacción
     * @return bool True si la transacción se inició correctamente
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirma una transacción
     * @return bool True si la transacción se confirmó correctamente
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Revierte una transacción
     * @return bool True si la transacción se revirtió correctamente
     */
    public function rollBack() {
        return $this->connection->rollBack();
    }
}