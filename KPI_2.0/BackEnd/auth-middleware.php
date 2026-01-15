<?php
/**
 * MIDDLEWARE DE AUTENTICAÇÃO - SISTEMA VISTA KPI 2.0
 * 
 * Middleware simples para proteger endpoints de KPIs via token estático.
 * 
 * @version 1.0
 * @created 15/01/2026
 * @author Sistema VISTA
 */

// Prevenir acesso direto
if (basename($_SERVER['PHP_SELF']) === 'auth-middleware.php') {
    http_response_code(403);
    die(json_encode(['error' => 'Acesso direto não permitido']));
}

/**
 * Valida token de autenticação via header Authorization
 * 
 * Suporta dois formatos:
 * - Authorization: Bearer TOKEN_AQUI
 * - Authorization: TOKEN_AQUI
 * 
 * @param bool $required Se true, retorna 401 e encerra execução. Se false, apenas retorna bool.
 * @return bool True se autenticado, false caso contrário
 * 
 * @example
 * // Uso obrigatório (retorna 401 se falhar)
 * require_once __DIR__ . '/auth-middleware.php';
 * validarAutenticacao();
 * 
 * // Uso opcional (permite continuar sem autenticação)
 * $autenticado = validarAutenticacao(false);
 * if (!$autenticado) {
 *     // Aplicar limitações (ex: cache mais curto)
 * }
 */
function validarAutenticacao(bool $required = true): bool {
    // 1. Carregar token do ambiente
    $tokenEsperado = getTokenFromEnvironment();
    
    // 2. Se token não configurado, permitir acesso (modo desenvolvimento)
    if (empty($tokenEsperado)) {
        logAuthEvent('warning', 'Token não configurado - modo desenvolvimento ativo');
        return true;
    }
    
    // 3. Extrair token do header Authorization
    $tokenRecebido = getAuthorizationToken();
    
    // 4. Validar token
    $isValid = validarToken($tokenRecebido, $tokenEsperado);
    
    // 5. Se válido, registrar sucesso e retornar
    if ($isValid) {
        logAuthEvent('success', 'Autenticação bem-sucedida', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
        return true;
    }
    
    // 6. Se inválido e obrigatório, retornar 401
    if ($required) {
        logAuthEvent('error', 'Autenticação falhou - token inválido', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
        enviarErroAutenticacao();
    }
    
    return false;
}

/**
 * Carrega token do ambiente (.env ou variável de ambiente)
 * 
 * Ordem de prioridade:
 * 1. Variável de ambiente VISTA_API_TOKEN
 * 2. Arquivo .env na raiz do projeto
 * 3. Arquivo config.php (fallback)
 * 
 * @return string|null Token configurado ou null
 */
function getTokenFromEnvironment(): ?string {
    // 1. Verificar variável de ambiente
    $token = getenv('VISTA_API_TOKEN');
    if ($token !== false && !empty($token)) {
        return trim($token);
    }
    
    // 2. Verificar arquivo .env
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        if (preg_match('/^VISTA_API_TOKEN\s*=\s*(.+)$/m', $envContent, $matches)) {
            $token = trim($matches[1]);
            // Remover aspas se presentes
            $token = trim($token, '"\'');
            if (!empty($token)) {
                return $token;
            }
        }
    }
    
    // 3. Verificar config.php (fallback)
    $configFile = __DIR__ . '/config.php';
    if (file_exists($configFile)) {
        require_once $configFile;
        if (defined('VISTA_API_TOKEN') && !empty(VISTA_API_TOKEN)) {
            return VISTA_API_TOKEN;
        }
    }
    
    return null;
}

/**
 * Extrai token do header Authorization
 * 
 * Suporta formatos:
 * - Authorization: Bearer abc123
 * - Authorization: abc123
 * 
 * @return string|null Token extraído ou null
 */
function getAuthorizationToken(): ?string {
    // 1. Tentar getallheaders() (Apache/Nginx)
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            return extrairTokenDoHeader($headers['Authorization']);
        }
    }
    
    // 2. Tentar $_SERVER['HTTP_AUTHORIZATION'] (FastCGI/IIS)
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return extrairTokenDoHeader($_SERVER['HTTP_AUTHORIZATION']);
    }
    
    // 3. Tentar apache_request_headers() (fallback)
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            return extrairTokenDoHeader($headers['Authorization']);
        }
    }
    
    return null;
}

/**
 * Extrai token da string do header Authorization
 * 
 * @param string $headerValue Valor do header (ex: "Bearer abc123")
 * @return string|null Token extraído
 */
function extrairTokenDoHeader(string $headerValue): ?string {
    $headerValue = trim($headerValue);
    
    // Formato: Bearer TOKEN
    if (stripos($headerValue, 'Bearer ') === 0) {
        $token = substr($headerValue, 7); // Remove "Bearer "
        return trim($token);
    }
    
    // Formato: TOKEN (direto)
    return !empty($headerValue) ? $headerValue : null;
}

/**
 * Valida token usando comparação segura (timing-safe)
 * 
 * @param string|null $tokenRecebido Token enviado pelo cliente
 * @param string $tokenEsperado Token configurado no servidor
 * @return bool True se válido
 */
function validarToken(?string $tokenRecebido, string $tokenEsperado): bool {
    if ($tokenRecebido === null || empty($tokenRecebido)) {
        return false;
    }
    
    // Comparação timing-safe (previne timing attacks)
    return hash_equals($tokenEsperado, $tokenRecebido);
}

/**
 * Envia resposta de erro de autenticação (HTTP 401)
 * 
 * Termina a execução do script após enviar a resposta.
 */
function enviarErroAutenticacao(): void {
    header('Content-Type: application/json; charset=utf-8');
    header('WWW-Authenticate: Bearer realm="VISTA KPI API"');
    http_response_code(401);
    
    $response = [
        'status' => 'error',
        'error' => [
            'code' => 'AUTH_REQUIRED',
            'message' => 'Autenticação necessária',
            'details' => 'Token inválido ou ausente. Inclua o header: Authorization: Bearer SEU_TOKEN',
            'httpCode' => 401
        ],
        'meta' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Registra eventos de autenticação (opcional - pode ser desabilitado)
 * 
 * @param string $level Nível do log (success, warning, error)
 * @param string $message Mensagem
 * @param array $context Contexto adicional
 */
function logAuthEvent(string $level, string $message, array $context = []): void {
    // Verificar se logging está habilitado
    $loggingEnabled = getenv('VISTA_AUTH_LOGGING') !== 'false';
    if (!$loggingEnabled) {
        return;
    }
    
    $logDir = __DIR__ . '/../logs';
    $logFile = $logDir . '/auth.log';
    
    // Criar diretório se não existir
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    // Evitar logs muito grandes (rotação simples)
    if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) { // 5MB
        @rename($logFile, $logFile . '.' . date('Y-m-d-His') . '.old');
    }
    
    // Formatar log entry
    $timestamp = date('Y-m-d H:i:s');
    $contextJson = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextJson}\n";
    
    // Escrever log (silencioso se falhar)
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * HELPER: Verificar se a requisição está autenticada
 * 
 * Útil para lógica condicional sem forçar autenticação
 * 
 * @return bool True se autenticado
 */
function isAuthenticated(): bool {
    return validarAutenticacao(false);
}

/**
 * HELPER: Obter nível de acesso baseado em autenticação
 * 
 * @return string 'authenticated' ou 'public'
 */
function getAccessLevel(): string {
    return isAuthenticated() ? 'authenticated' : 'public';
}

/**
 * HELPER: Aplicar rate limiting baseado em autenticação
 * 
 * Exemplo de uso futuro (não implementado ainda)
 * 
 * @param int $limitPublic Requisições/min para público
 * @param int $limitAuth Requisições/min para autenticados
 * @return bool True se dentro do limite
 */
function checkRateLimit(int $limitPublic = 10, int $limitAuth = 100): bool {
    // TODO: Implementar rate limiting baseado em IP/Token
    // Por enquanto, sempre permite
    return true;
}

// =============================================================================
// MODO DE COMPATIBILIDADE (OPCIONAL)
// =============================================================================

/**
 * Permite acesso sem autenticação para IPs específicos (whitelist)
 * 
 * Útil para desenvolvimento local ou integrações internas
 * 
 * @return bool True se IP está na whitelist
 */
function isWhitelistedIP(): bool {
    $whitelist = getenv('VISTA_IP_WHITELIST');
    if ($whitelist === false || empty($whitelist)) {
        return false;
    }
    
    $allowedIPs = array_map('trim', explode(',', $whitelist));
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
    
    return in_array($clientIP, $allowedIPs, true);
}

/**
 * Valida autenticação com suporte a whitelist de IPs
 * 
 * @param bool $required Se true, força autenticação
 * @return bool True se autenticado ou whitelisted
 */
function validarAutenticacaoComWhitelist(bool $required = true): bool {
    // Verificar whitelist primeiro
    if (isWhitelistedIP()) {
        logAuthEvent('info', 'Acesso permitido via IP whitelist', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        return true;
    }
    
    // Validação normal
    return validarAutenticacao($required);
}

// =============================================================================
// AUTO-CONFIGURAÇÃO (APENAS EM DESENVOLVIMENTO)
// =============================================================================

/**
 * Gera token aleatório para desenvolvimento
 * 
 * NUNCA USE EM PRODUÇÃO!
 * 
 * @return string Token aleatório (64 caracteres)
 */
function gerarTokenDesenvolvimento(): string {
    return bin2hex(random_bytes(32));
}

/**
 * Verifica se está em modo de desenvolvimento
 * 
 * @return bool True se desenvolvimento
 */
function isDevelopmentMode(): bool {
    $env = getenv('VISTA_ENVIRONMENT');
    return $env === 'development' || $env === 'dev' || empty($env);
}
