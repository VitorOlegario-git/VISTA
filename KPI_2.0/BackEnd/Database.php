<?php
/**
 * Classe Database - Gerenciamento de Conexões
 * Implementa padrão Singleton para conexão única
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Construtor privado para Singleton
     */
    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        
        // Define charset
        $this->connection->set_charset("utf8mb4");
        
        // Verifica conexão
        if ($this->connection->connect_error) {
            $errorMsg = "Erro de conexão MySQL: " . $this->connection->connect_error;
            error_log($errorMsg);
            
            if (APP_DEBUG) {
                throw new Exception($errorMsg);
            } else {
                throw new Exception("Erro ao conectar com o banco de dados. Contate o administrador.");
            }
        }
    }
    
    /**
     * Obtém a instância única da classe
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtém a conexão mysqli
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Executa uma query preparada com segurança
     * 
     * @param string $sql SQL com placeholders (?)
     * @param array $params Parâmetros para bind
     * @param string $types Tipos dos parâmetros (s=string, i=int, d=double, b=blob)
     * @return mysqli_result|bool
     */
    public function query($sql, $params = [], $types = '') {
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            $error = "Erro ao preparar query: " . $this->connection->error;
            error_log($error);
            throw new Exception(APP_DEBUG ? $error : "Erro ao executar operação no banco de dados");
        }
        
        if (!empty($params)) {
            if (empty($types)) {
                // Auto-detecta tipos se não fornecidos
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            $error = "Erro ao executar query: " . $stmt->error;
            error_log($error);
            throw new Exception(APP_DEBUG ? $error : "Erro ao executar operação no banco de dados");
        }
        
        return $stmt->get_result();
    }
    
    /**
     * Executa INSERT e retorna o ID inserido
     */
    public function insert($sql, $params = [], $types = '') {
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            $error = "Erro ao preparar INSERT: " . $this->connection->error;
            error_log($error);
            throw new Exception(APP_DEBUG ? $error : "Erro ao inserir dados");
        }
        
        if (!empty($params)) {
            if (empty($types)) {
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            $error = "Erro ao executar INSERT: " . $stmt->error;
            error_log($error);
            throw new Exception(APP_DEBUG ? $error : "Erro ao inserir dados");
        }
        
        return $this->connection->insert_id;
    }
    
    /**
     * Executa UPDATE/DELETE e retorna número de linhas afetadas
     */
    public function execute($sql, $params = [], $types = '') {
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            $error = "Erro ao preparar comando: " . $this->connection->error;
            error_log($error);
            throw new Exception(APP_DEBUG ? $error : "Erro ao executar operação");
        }
        
        if (!empty($params)) {
            if (empty($types)) {
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            $error = "Erro ao executar comando: " . $stmt->error;
            error_log($error);
            throw new Exception(APP_DEBUG ? $error : "Erro ao executar operação");
        }
        
        return $stmt->affected_rows;
    }
    
    /**
     * Busca um único registro
     */
    public function fetchOne($sql, $params = [], $types = '') {
        $result = $this->query($sql, $params, $types);
        return $result ? $result->fetch_assoc() : null;
    }
    
    /**
     * Busca todos os registros
     */
    public function fetchAll($sql, $params = [], $types = '') {
        $result = $this->query($sql, $params, $types);
        if (!$result) return [];
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    /**
     * Inicia uma transação
     */
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }
    
    /**
     * Comita uma transação
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Desfaz uma transação
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Escapa string para uso seguro
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    /**
     * Previne clonagem
     */
    private function __clone() {}
    
    /**
     * Previne unserialize
     */
    public function __wakeup() {
        throw new Exception("Não é possível unserializar singleton");
    }
}

/**
 * Função helper para obter conexão rapidamente
 * Mantém compatibilidade com código legado
 */
function getConnection() {
    return Database::getInstance()->getConnection();
}

/**
 * Função helper para obter instância do Database
 */
function getDb() {
    return Database::getInstance();
}
?>
