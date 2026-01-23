<?php
/**
 * Classe Database - Gerenciamento de Conexões
 * Implementa padrão Singleton para conexão única
 */

// Carrega config se existir; não morrer se estiver ausente
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    $logPath = __DIR__ . '/../logs/php_errors.log';
    if (!file_exists(dirname($logPath))) {
        @mkdir(dirname($logPath), 0755, true);
    }
    error_log('[Database] WARNING: config.php not found at ' . $configPath);
}

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Construtor privado para Singleton
     */
    private function __construct() {
        // Tenta abrir conexão MySQLi (por padrão TCP/host). Não logar senha.
        $this->connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

        // Define charset se a conexão foi estabelecida
        if ($this->connection && !$this->connection->connect_error) {
            $this->connection->set_charset("utf8mb4");
        }

        // Verifica conexão
        if ($this->connection->connect_error) {
            // Preferir mysqli_connect_error para informações da última tentativa
            $connectErr = mysqli_connect_error();
            $connectErrNo = mysqli_connect_errno();

            // Log detalhado para diagnóstico, sem expor senha
            $errDetail = sprintf('[Database] CONNECT ERROR host=%s db=%s user=%s error=%s code=%s', DB_HOST, DB_NAME, DB_USERNAME, $connectErr, $connectErrNo);
            error_log($errDetail);

            // Em modo debug, propagar mensagem completa (ajuda em ambientes de desenvolvimento)
            if (defined('APP_DEBUG') && APP_DEBUG) {
                throw new Exception('Erro de conexão MySQL: ' . $connectErr);
            } else {
                // Lançar mensagem genérica para evitar vazamento
                throw new Exception('Erro ao conectar com o banco de dados. Contate o administrador.');
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
            $error = sprintf('[Database] PREPARE ERROR host=%s db=%s user=%s error=%s', DB_HOST, DB_NAME, DB_USERNAME, $this->connection->error);
            error_log($error);
            throw new Exception(APP_DEBUG ? $this->connection->error : "Erro ao executar operação no banco de dados");
        }
        
        if (!empty($params)) {
            if (empty($types)) {
                // Auto-detecta tipos se não fornecidos
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            $error = sprintf('[Database] EXECUTE ERROR host=%s db=%s user=%s error=%s', DB_HOST, DB_NAME, DB_USERNAME, $stmt->error);
            error_log($error);
            throw new Exception(APP_DEBUG ? $stmt->error : "Erro ao executar operação no banco de dados");
        }
        
        return $stmt->get_result();
    }
    
    /**
     * Executa INSERT e retorna o ID inserido
     */
    public function insert($sql, $params = [], $types = '') {
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            $error = sprintf('[Database] PREPARE INSERT ERROR host=%s db=%s user=%s error=%s', DB_HOST, DB_NAME, DB_USERNAME, $this->connection->error);
            error_log($error);
            throw new Exception(APP_DEBUG ? $this->connection->error : "Erro ao inserir dados");
        }
        
        if (!empty($params)) {
            if (empty($types)) {
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            $error = sprintf('[Database] INSERT EXECUTE ERROR host=%s db=%s user=%s error=%s', DB_HOST, DB_NAME, DB_USERNAME, $stmt->error);
            error_log($error);
            throw new Exception(APP_DEBUG ? $stmt->error : "Erro ao inserir dados");
        }
        
        return $this->connection->insert_id;
    }
    
    /**
     * Executa UPDATE/DELETE e retorna número de linhas afetadas
     */
    public function execute($sql, $params = [], $types = '') {
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            $error = sprintf('[Database] PREPARE COMMAND ERROR host=%s db=%s user=%s error=%s', DB_HOST, DB_NAME, DB_USERNAME, $this->connection->error);
            error_log($error);
            throw new Exception(APP_DEBUG ? $this->connection->error : "Erro ao executar operação");
        }
        
        if (!empty($params)) {
            if (empty($types)) {
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            $error = sprintf('[Database] COMMAND EXECUTE ERROR host=%s db=%s user=%s error=%s', DB_HOST, DB_NAME, DB_USERNAME, $stmt->error);
            error_log($error);
            throw new Exception(APP_DEBUG ? $stmt->error : "Erro ao executar operação");
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
    try {
        return Database::getInstance();
    } catch (Exception $e) {
        // Log details for operators without exposing sensitive data
        error_log(sprintf('[Database] GETDB ERROR host=%s db=%s user=%s error=%s', DB_HOST, DB_NAME, DB_USERNAME, $e->getMessage()));
        if (defined('APP_DEBUG') && APP_DEBUG) {
            // In debug, propagate original exception for diagnostics
            throw $e;
        }
        // In production, throw a generic exception to be handled by controllers
        throw new Exception('Database connection failed');
    }
}
?>
