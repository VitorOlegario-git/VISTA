<?php
/**
 * 🧱 HELPERS PADRÃO PARA ENDPOINTS — SUNLAB
 * 
 * Funções utilitárias para garantir padronização completa
 * de todos os endpoints do sistema.
 * 
 * USO: require_once __DIR__ . '/endpoint-helpers.php';
 */

/**
 * 🔹 RESOLUÇÃO INTELIGENTE DE PERÍODO (NOVA - 15/01/2026)
 * 
 * Resolve períodos de data de forma padronizada e flexível.
 * Aceita múltiplos formatos de entrada e sempre retorna datas normalizadas.
 * 
 * @param array $params Array de parâmetros ($_GET tipicamente)
 * @return array ['inicio' => 'Y-m-d', 'fim' => 'Y-m-d', 'tipo' => string, 'descricao' => string]
 * @throws Exception Se o período for inválido
 * 
 * Formatos aceitos:
 * 1. Período pré-definido: ?period=today|last_7_days|last_30_days|last_90_days
 * 2. Datas customizadas: ?inicio=DD/MM/YYYY&fim=DD/MM/YYYY
 * 3. Fallback: Últimos 7 dias se nenhum parâmetro fornecido
 * 
 * Exemplo de uso:
 * $periodo = resolvePeriod($_GET);
 * // Retorna: ['inicio' => '2026-01-08', 'fim' => '2026-01-15', 'tipo' => 'last_7_days', 'descricao' => 'Últimos 7 dias']
 */
function resolvePeriod(array $params = []): array {
    $period = $params['period'] ?? null;
    $inicio = $params['inicio'] ?? null;
    $fim = $params['fim'] ?? null;
    
    // ============================================
    // MODO 1: PERÍODO PRÉ-DEFINIDO
    // ============================================
    if ($period) {
        $hoje = new DateTime();
        $dataFim = $hoje->format('Y-m-d');
        
        switch ($period) {
            case 'today':
                $dataInicio = $dataFim;
                $tipo = 'today';
                $descricao = 'Hoje';
                break;
                
            case 'yesterday':
                $ontem = (clone $hoje)->modify('-1 day');
                $dataInicio = $ontem->format('Y-m-d');
                $dataFim = $ontem->format('Y-m-d');
                $tipo = 'yesterday';
                $descricao = 'Ontem';
                break;
                
            case 'last_7_days':
                $dataInicio = (clone $hoje)->modify('-7 days')->format('Y-m-d');
                $tipo = 'last_7_days';
                $descricao = 'Últimos 7 dias';
                break;
                
            case 'last_30_days':
                $dataInicio = (clone $hoje)->modify('-30 days')->format('Y-m-d');
                $tipo = 'last_30_days';
                $descricao = 'Últimos 30 dias';
                break;
                
            case 'last_90_days':
                $dataInicio = (clone $hoje)->modify('-90 days')->format('Y-m-d');
                $tipo = 'last_90_days';
                $descricao = 'Últimos 90 dias';
                break;
                
            case 'current_week':
                $dataInicio = (clone $hoje)->modify('monday this week')->format('Y-m-d');
                $tipo = 'current_week';
                $descricao = 'Semana atual';
                break;
                
            case 'current_month':
                $dataInicio = (clone $hoje)->modify('first day of this month')->format('Y-m-d');
                $tipo = 'current_month';
                $descricao = 'Mês atual';
                break;
                
            case 'last_month':
                $dataInicio = (clone $hoje)->modify('first day of last month')->format('Y-m-d');
                $dataFim = (clone $hoje)->modify('last day of last month')->format('Y-m-d');
                $tipo = 'last_month';
                $descricao = 'Mês anterior';
                break;
                
            default:
                throw new Exception("Período inválido: '$period'. Valores aceitos: today, yesterday, last_7_days, last_30_days, last_90_days, current_week, current_month, last_month");
        }
        
        return [
            'inicio' => $dataInicio,
            'fim' => $dataFim,
            'tipo' => $tipo,
            'descricao' => $descricao,
            'dias' => (int)((strtotime($dataFim) - strtotime($dataInicio)) / 86400) + 1
        ];
    }
    
    // ============================================
    // MODO 2: DATAS CUSTOMIZADAS (dd/mm/yyyy)
    // ============================================
    if ($inicio && $fim) {
        // Converte dd/mm/yyyy para Y-m-d
        $dataInicioObj = DateTime::createFromFormat('d/m/Y', $inicio);
        $dataFimObj = DateTime::createFromFormat('d/m/Y', $fim);
        
        if (!$dataInicioObj || !$dataFimObj) {
            throw new Exception('Formato de data inválido. Use dd/mm/yyyy ou utilize o parâmetro period');
        }
        
        // Valida ordem das datas
        if ($dataFimObj < $dataInicioObj) {
            throw new Exception('Data final deve ser posterior ou igual à data inicial');
        }
        
        $dataInicio = $dataInicioObj->format('Y-m-d');
        $dataFim = $dataFimObj->format('Y-m-d');
        
        $dias = (int)((strtotime($dataFim) - strtotime($dataInicio)) / 86400) + 1;
        
        return [
            'inicio' => $dataInicio,
            'fim' => $dataFim,
            'tipo' => 'custom',
            'descricao' => $dataInicioObj->format('d/m/Y') . ' a ' . $dataFimObj->format('d/m/Y'),
            'dias' => $dias
        ];
    }
    
    // ============================================
    // MODO 3: FALLBACK - ÚLTIMOS 7 DIAS
    // ============================================
    $hoje = new DateTime();
    $dataFim = $hoje->format('Y-m-d');
    $dataInicio = (clone $hoje)->modify('-7 days')->format('Y-m-d');
    
    return [
        'inicio' => $dataInicio,
        'fim' => $dataFim,
        'tipo' => 'default_7_days',
        'descricao' => 'Últimos 7 dias (padrão)',
        'dias' => 8
    ];
}

/**
 * 🔹 VALIDAÇÃO E PARSING DE PARÂMETROS DE ENTRADA (LEGACY - mantida para retrocompatibilidade)
 * 
 * @deprecated Use resolvePeriod() para novo código
 * 
 * Retorna array com parâmetros validados:
 * - dataInicio (Y-m-d ou null)
 * - dataFim (Y-m-d ou null)
 * - operador (string ou null)
 * - setor (string ou null)
 * 
 * Se houver erro, envia resposta 400 e encerra execução.
 */
function validarParametrosPadrao(): array {
    $dataInicio = $_GET['inicio'] ?? null;
    $dataFim    = $_GET['fim'] ?? null;
    $operador   = $_GET['operador'] ?? null;
    $setor      = $_GET['setor'] ?? null;

    try {
        if ($dataInicio && $dataFim) {
            $dataInicioObj = DateTime::createFromFormat('d/m/Y', $dataInicio);
            $dataFimObj    = DateTime::createFromFormat('d/m/Y', $dataFim);

            if (!$dataInicioObj || !$dataFimObj) {
                throw new Exception('Formato de data inválido. Use dd/mm/yyyy');
            }

            // Valida que data fim é posterior à data início
            if ($dataFimObj < $dataInicioObj) {
                throw new Exception('Data final deve ser posterior à data inicial');
            }

            $dataInicio = $dataInicioObj->format('Y-m-d');
            $dataFim    = $dataFimObj->format('Y-m-d');
        }
    } catch (Throwable $e) {
        enviarErro(400, $e->getMessage());
    }

    return [
        'dataInicio' => $dataInicio,
        'dataFim' => $dataFim,
        'operador' => $operador,
        'setor' => $setor
    ];
}

/**
 * 🔹 CONSTRUTOR DE WHERE CLAUSE PADRONIZADO
 * 
 * Gera WHERE clause e array de parâmetros para prepared statements.
 * 
 * @param string $dataInicio Data no formato Y-m-d
 * @param string $dataFim Data no formato Y-m-d
 * @param string $operador Nome do operador ou null
 * @param string $campoData Nome do campo de data na tabela (default: 'data_evento')
 * @param string $campoOperador Nome do campo operador na tabela (default: 'operador')
 * @param string $setor Nome do setor ou null
 * @param string $campoSetor Nome do campo setor na tabela (default: 'setor')
 * @return array ['where' => string SQL, 'params' => array, 'types' => string]
 */
function construirWherePadrao(
    ?string $dataInicio,
    ?string $dataFim,
    ?string $operador,
    string $campoData = 'data_evento',
    ?string $campoOperador = 'operador',
    ?string $setor = null,
    ?string $campoSetor = 'setor'
): array {
    $where = [];
    $params = [];
    $types = '';

    if ($dataInicio && $dataFim) {
        $where[] = "$campoData BETWEEN ? AND ?";
        $params[] = $dataInicio;
        $params[] = $dataFim;
        $types .= 'ss';
    }

    if ($operador && $operador !== 'Todos' && $campoOperador) {
        $where[] = "$campoOperador = ?";
        $params[] = $operador;
        $types .= 's';
    }

    if ($setor && $setor !== 'Todos' && $campoSetor) {
        $where[] = "$campoSetor = ?";
        $params[] = $setor;
        $types .= 's';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    return [
        'where' => $whereSql,
        'params' => $params,
        'types' => $types
    ];
}

/**
 * 🔹 RESPOSTA PADRONIZADA DE KPI (CONTRATO VISTA)
 * 
 * Função reutilizável que retorna JSON padronizado para todos os KPIs.
 * Segue contrato único do sistema VISTA.
 * 
 * @param string $kpi Nome/identificador do KPI (ex: 'volume-processado', 'tempo-medio')
 * @param string $period Período no formato 'YYYY-MM-DD' ou 'YYYY-MM'
 * @param array $data Dados do KPI (estrutura livre conforme necessidade)
 * @param float $executionTimeMs Tempo de execução em milissegundos
 * @param int $httpCode Código HTTP (default: 200)
 * 
 * Contrato de saída:
 * {
 *   "status": "success",
 *   "kpi": "nome-do-kpi",
 *   "period": "YYYY-MM-DD / YYYY-MM",
 *   "data": {...},
 *   "meta": {
 *     "generatedAt": "ISO_DATE",
 *     "executionTimeMs": number,
 *     "source": "vista-kpi"
 *   }
 * }
 */
function kpiResponse(
    string $kpi,
    string $period,
    array $data,
    float $executionTimeMs,
    int $httpCode = 200
): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    
    $response = [
        'status' => 'success',
        'kpi' => $kpi,
        'period' => $period,
        'data' => $data,
        'meta' => [
            'generatedAt' => date('c'), // ISO 8601 format
            'executionTimeMs' => round($executionTimeMs, 2),
            'source' => 'vista-kpi'
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 🔹 RESPOSTA PADRONIZADA DE KPI - ERRO
 * 
 * Retorna resposta de erro seguindo contrato VISTA.
 * 
 * @param string $kpi Nome/identificador do KPI
 * @param string $message Mensagem de erro descritiva
 * @param int $httpCode Código HTTP de erro (default: 500)
 * 
 * Contrato de saída:
 * {
 *   "status": "error",
 *   "kpi": "nome-do-kpi",
 *   "message": "Descrição do erro",
 *   "meta": {
 *     "generatedAt": "ISO_DATE",
 *     "source": "vista-kpi"
 *   }
 * }
 */
function kpiError(
    string $kpi,
    string $message,
    int $httpCode = 500
): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    
    $response = [
        'status' => 'error',
        'kpi' => $kpi,
        'message' => $message,
        'meta' => [
            'generatedAt' => date('c'), // ISO 8601 format
            'source' => 'vista-kpi'
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 🔹 RESPOSTA JSON PADRONIZADA DE SUCESSO (LEGACY - mantido para retrocompatibilidade)
 * 
 * Envia resposta JSON com estrutura padrão e encerra execução.
 * 
 * @param mixed $data Dados a retornar (array/object)
 * @param string $dataInicio Data início para meta
 * @param string $dataFim Data fim para meta
 * @param string $operador Operador para meta
 * @param string $setor Setor para meta
 * @param int $httpCode Código HTTP (default: 200)
 */
function enviarSucesso(
    $data,
    ?string $dataInicio = null,
    ?string $dataFim = null,
    ?string $operador = null,
    ?string $setor = null,
    int $httpCode = 200
): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'meta' => [
            'inicio' => $dataInicio,
            'fim' => $dataFim,
            'operador' => $operador ?? 'Todos',
            'setor' => $setor ?? 'Todos',
            'timestamp' => date('Y-m-d H:i:s')
        ],
        'data' => $data
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 🔹 RESPOSTA JSON PADRONIZADA DE ERRO
 * 
 * Envia resposta de erro e encerra execução.
 * 
 * @param int $httpCode Código HTTP de erro
 * @param string $message Mensagem descritiva do erro
 */
function enviarErro(int $httpCode, string $message): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'error' => true,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    exit;
}

/**
 * 🔹 FORMATAR PERÍODO LEGÍVEL PARA META
 * 
 * Converte datas em texto legível para exibição.
 * 
 * @param string $dataInicio Data no formato Y-m-d
 * @param string $dataFim Data no formato Y-m-d
 * @return string Período formatado
 */
function formatarPeriodoMeta(?string $dataInicio, ?string $dataFim): string {
    if (!$dataInicio || !$dataFim) {
        return 'Histórico completo';
    }

    try {
        $inicio = DateTime::createFromFormat('Y-m-d', $dataInicio);
        $fim = DateTime::createFromFormat('Y-m-d', $dataFim);
        
        if (!$inicio || !$fim) {
            return 'Período indefinido';
        }

        $diff = $inicio->diff($fim);
        
        if ($diff->days == 0) {
            return $inicio->format('d/m/Y');
        } elseif ($diff->days <= 7) {
            return 'Últimos 7 dias';
        } elseif ($diff->days <= 30) {
            return 'Últimos 30 dias';
        } elseif ($diff->days <= 90) {
            return 'Últimos 3 meses';
        } else {
            return $inicio->format('d/m/Y') . ' a ' . $fim->format('d/m/Y');
        }
    } catch (Exception $e) {
        return 'Período indefinido';
    }
}

/**
 * 🔹 ESTRUTURA PADRÃO DE KPI
 * 
 * Formata dados de KPI seguindo contrato visual.
 * 
 * @param mixed $valor Valor do KPI
 * @param string $unidade Unidade (ex: 'equipamentos', 'minutos', 'R$')
 * @param string $periodo Período textual
 * @param string $contexto Contexto do KPI
 * @param array $extra Campos extras opcionais
 * @return array KPI formatado
 */
function formatarKPI(
    $valor,
    string $unidade,
    string $periodo,
    string $contexto,
    array $extra = []
): array {
    return array_merge([
        'valor' => $valor,
        'unidade' => $unidade,
        'periodo' => $periodo,
        'contexto' => $contexto
    ], $extra);
}

/**
 * 🔹 VALIDAÇÃO DE CONEXÃO COM BANCO
 * 
 * Verifica se conexão está disponível e válida.
 * Se não estiver, envia erro 503 e encerra.
 * 
 * @param mysqli $conn Conexão mysqli
 */
function validarConexao($conn): void {
    if (!isset($conn) || !$conn || $conn->connect_error) {
        enviarErro(503, 'Banco de dados indisponível');
    }
}

/**
 * 🔹 EXECUTAR QUERY COM TRATAMENTO DE ERRO
 * 
 * Executa query preparada com tratamento automático de erros.
 * 
 * @param mysqli $conn Conexão
 * @param string $sql Query SQL
 * @param array $params Parâmetros
 * @param string $types Tipos dos parâmetros (s/i/d)
 * @return mysqli_result|bool Resultado da query
 */
function executarQuery(
    mysqli $conn,
    string $sql,
    array $params = [],
    string $types = ''
): mysqli_result|bool {
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Erro ao preparar query: " . $conn->error);
        enviarErro(500, 'Erro ao processar consulta');
    }

    if (!empty($params)) {
        if (empty($types)) {
            // Auto-detecta tipos se não fornecidos
            $types = str_repeat('s', count($params));
        }
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Erro ao executar query: " . $stmt->error);
        enviarErro(500, 'Erro ao executar consulta');
    }

    $result = $stmt->get_result();
    $stmt->close();

    return $result;
}

/**
 * 🔹 BUSCAR UM ÚNICO REGISTRO
 * 
 * Executa query e retorna primeiro resultado como array associativo.
 * 
 * @param mysqli $conn Conexão
 * @param string $sql Query SQL
 * @param array $params Parâmetros
 * @param string $types Tipos dos parâmetros
 * @return array|null Registro encontrado ou null
 */
function buscarUm(
    mysqli $conn,
    string $sql,
    array $params = [],
    string $types = ''
): ?array {
    $result = executarQuery($conn, $sql, $params, $types);
    return $result ? $result->fetch_assoc() : null;
}

/**
 * 🔹 BUSCAR MÚLTIPLOS REGISTROS
 * 
 * Executa query e retorna todos resultados como array de arrays.
 * 
 * @param mysqli $conn Conexão
 * @param string $sql Query SQL
 * @param array $params Parâmetros
 * @param string $types Tipos dos parâmetros
 * @return array Array de registros
 */
function buscarTodos(
    mysqli $conn,
    string $sql,
    array $params = [],
    string $types = ''
): array {
    $result = executarQuery($conn, $sql, $params, $types);
    
    if (!$result) {
        return [];
    }

    $registros = [];
    while ($row = $result->fetch_assoc()) {
        $registros[] = $row;
    }

    return $registros;
}

// ========================================
// 🎯 KPI 3.0 - FUNÇÕES DE REFINAMENTO
// ========================================

/**
 * 🔹 CALCULAR VARIAÇÃO PERCENTUAL
 * 
 * Calcula a variação percentual entre valor atual e referência.
 * 
 * @param float $valorAtual Valor atual
 * @param float $valorReferencia Valor de referência (média/meta/anterior)
 * @return float Variação percentual (ex: 13.4)
 */
function calcularVariacao($valorAtual, $valorReferencia) {
    if ($valorReferencia == 0) return 0;
    return round((($valorAtual - $valorReferencia) / $valorReferencia) * 100, 1);
}

/**
 * 🔹 DEFINIR DIREÇÃO DA VARIAÇÃO
 * 
 * @param float $variacao Variação percentual
 * @return string 'up' | 'down' | 'stable'
 */
function definirDirecao($variacao) {
    if ($variacao > 0) return 'up';
    if ($variacao < 0) return 'down';
    return 'stable';
}

/**
 * 🔹 DEFINIR ESTADO DO KPI
 * 
 * Define o estado baseado em limites de variação.
 * 
 * @param float $variacao Variação percentual
 * @param array $limites [limite_success, limite_warning] default: [10, 25]
 * @return string 'success' | 'warning' | 'critical'
 */
function definirEstado($variacao, $limites = [10, 25]) {
    if (abs($variacao) <= $limites[0]) return 'success';
    if (abs($variacao) <= $limites[1]) return 'warning';
    return 'critical';
}

/**
 * 🔹 DEFINIR ESTADO INVERTIDO (para métricas negativas)
 * 
 * Para KPIs onde aumento é ruim (tempo médio, sem conserto).
 * 
 * @param float $variacao Variação percentual
 * @param array $limites [limite_success, limite_warning]
 * @return string 'success' | 'warning' | 'critical'
 */
function definirEstadoInvertido($variacao, $limites = [10, 25]) {
    // Variação negativa é boa (diminuição)
    if ($variacao <= -$limites[1]) return 'success';
    if ($variacao <= -$limites[0]) return 'success';
    if ($variacao <= $limites[0]) return 'warning';
    return 'critical';
}

/**
 * 🔹 MONTAR ESTRUTURA DE KPI REFINADO
 * 
 * Retorna estrutura completa de KPI 3.0.
 * 
 * @param float $valorAtual Valor atual do KPI
 * @param float $valorReferencia Valor de referência
 * @param string $unidade Unidade do KPI (ex: 'equipamentos', 'dias', '%')
 * @param string $contexto Descrição do KPI
 * @param string $tipoReferencia 'media_30d' | 'meta' | 'periodo_anterior'
 * @param string $estado 'success' | 'warning' | 'critical'
 * @return array Estrutura de KPI refinado
 */
function montarKpiRefinado(
    $valorAtual,
    $valorReferencia,
    string $unidade,
    string $contexto,
    string $tipoReferencia = 'media_30d',
    ?string $estado = null
): array {
    $variacao = calcularVariacao($valorAtual, $valorReferencia);
    $direcao = definirDirecao($variacao);
    
    // Se estado não fornecido, calcula automaticamente
    if ($estado === null) {
        $estado = definirEstado($variacao);
    }
    
    return [
        'valor' => $valorAtual,
        'unidade' => $unidade,
        'periodo' => 'Período selecionado',
        'contexto' => $contexto,
        'referencia' => [
            'tipo' => $tipoReferencia,
            'valor' => $valorReferencia
        ],
        'variacao' => [
            'percentual' => $variacao,
            'direcao' => $direcao
        ],
        'estado' => $estado
    ];
}

// 🔹 INICIALIZAÇÃO AUTOMÁTICA
// Define header JSON padrão quando arquivo é incluído
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}
?>
