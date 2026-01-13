<?php
ini_set('display_errors', 0); // Desabilitar display de erros em produção
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

session_start();

// Tratamento robusto de erros
try {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(503);
    echo json_encode(['error' => 'Erro de conexão com banco de dados'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Verificar se a conexão está disponível
if (!isset($conn) || !$conn || $conn->connect_error) {
    http_response_code(503);
    echo json_encode(['error' => 'Banco de dados indisponível'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Se seus nomes de colunas diferirem, ajuste nos SELECTs abaixo:
 * - analise_parcial/reparo_parcial: quantidade_parcial, operacao_destino, data_registro, data_envio_orcamento, data_solicitacao_nf, data_saida
 * - qualidade_registro: quantidade, operacao_destino, data_cadastro
 */

$operadores = ['Vitor Olegario', 'Luan Oliveira', 'Rony Rodrigues', 'Ederson Santos', 'Matheus Ferreira'];

/** Normalizador de nomes vindos de outras fontes */
function normalizarOperador($nome) {
    $map = [
        'ronyrodrigues' => 'Rony Rodrigues',
        'RonyRodrigues' => 'Rony Rodrigues',
    ];
    return $map[$nome] ?? $nome;
}

/** Verifica se uma tabela existe no banco de dados */
function tabelaExiste(mysqli $conn, string $nomeTabela): bool {
    $stmt = $conn->prepare("SHOW TABLES LIKE ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("s", $nomeTabela);
    $stmt->execute();
    $result = $stmt->get_result();
    $existe = $result->num_rows > 0;
    $stmt->close();
    return $existe;
}

/** Busca o último evento (mais recente) entre Análise, Reparo e Qualidade */
function buscarUltimoEvento(mysqli $conn, string $operador): ?array {
    // Verifica se as tabelas necessárias existem
    $tabelasNecessarias = ['analise_parcial', 'reparo_parcial', 'qualidade_registro'];
    $tabelasExistentes = [];
    
    foreach ($tabelasNecessarias as $tabela) {
        if (tabelaExiste($conn, $tabela)) {
            $tabelasExistentes[] = $tabela;
        }
    }
    
    // Se nenhuma tabela existe, retorna null
    if (empty($tabelasExistentes)) {
        error_log("Nenhuma tabela necessária existe (analise_parcial, reparo_parcial, qualidade_registro)");
        return null;
    }
    
    // Constrói a query dinamicamente apenas com as tabelas que existem
    $queries = [];
    
    if (in_array('analise_parcial', $tabelasExistentes)) {
        $queries[] = "
            /* Análise - Início (data_registro) */
            SELECT 'Análise' AS setor, 'Início' AS status, ap.razao_social,
                   ap.quantidade_parcial AS quantidade, ap.data_registro AS data_evento
            FROM analise_parcial ap
            WHERE ap.operador = ? AND ap.data_registro IS NOT NULL

            UNION ALL
            /* Análise - Envio Orçamento */
            SELECT 'Análise', 'Envio Orçamento', ap.razao_social,
                   ap.quantidade_parcial, ap.data_envio_orcamento
            FROM analise_parcial ap
            WHERE ap.operador = ? AND ap.data_envio_orcamento IS NOT NULL

            UNION ALL
            /* Análise - Solicitação NF */
            SELECT 'Análise', 'Solicitação NF', ap.razao_social,
                   ap.quantidade_parcial, ap.data_solicitacao_nf
            FROM analise_parcial ap
            WHERE ap.operador = ? AND ap.data_solicitacao_nf IS NOT NULL

            UNION ALL
            /* Análise - Finalização */
            SELECT 'Análise', 'Finalização', ap.razao_social,
                   ap.quantidade_parcial, ap.data_saida
            FROM analise_parcial ap
            WHERE ap.operador = ? AND ap.data_saida IS NOT NULL
        ";
    }
    
    if (in_array('reparo_parcial', $tabelasExistentes)) {
        $queries[] = "
            /* Reparo - Início (data_registro) */
            SELECT 'Reparo' AS setor, 'Início' AS status, rp.razao_social,
                   rp.quantidade_parcial AS quantidade, rp.data_registro AS data_evento
            FROM reparo_parcial rp
            WHERE rp.operador = ? AND rp.data_registro IS NOT NULL

            UNION ALL
            /* Reparo - Solicitação NF */
            SELECT 'Reparo', 'Solicitação NF', rp.razao_social,
                   rp.quantidade_parcial, rp.data_solicitacao_nf
            FROM reparo_parcial rp
            WHERE rp.operador = ? AND rp.data_solicitacao_nf IS NOT NULL

            UNION ALL
            /* Reparo - Finalização */
            SELECT 'Reparo', 'Finalização', rp.razao_social,
                   rp.quantidade_parcial, rp.data_saida
            FROM reparo_parcial rp
            WHERE rp.operador = ? AND rp.data_saida IS NOT NULL
        ";
    }
    
    if (in_array('qualidade_registro', $tabelasExistentes)) {
        $queries[] = "
            /* Qualidade - Registro (data_cadastro) */
            SELECT 'Qualidade' AS setor, qr.operacao_destino AS status, qr.razao_social,
                   qr.quantidade, qr.data_cadastro AS data_evento
            FROM qualidade_registro qr
            WHERE qr.operador = ? AND qr.data_cadastro IS NOT NULL
        ";
    }
    
    // Se não há queries, retorna null
    if (empty($queries)) {
        return null;
    }
    
    // Monta a query final
    $sql = "
        SELECT setor, status, razao_social, quantidade, data_evento
        FROM (
            " . implode(" UNION ALL ", $queries) . "
        ) eventos
        WHERE data_evento IS NOT NULL
        ORDER BY data_evento DESC
        LIMIT 1
    ";
    
    // Conta quantos operadores são necessários para bind_param
    $numParams = substr_count($sql, '?');
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Erro ao preparar query: " . $conn->error);
        return null;
    }

    // Cria array de operadores para bind_param
    $params = array_fill(0, $numParams, $operador);
    $types = str_repeat('s', $numParams);
    
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        error_log("Erro ao executar query: " . $stmt->error);
        $stmt->close();
        return null;
    }
    
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/** Formata diferença de tempo tipo “2h 10min”, “1d 3h”, “12min” */
function formatarTempoDecorrido(DateTime $inicio, DateTime $fim): string {
    $diff = $fim->diff($inicio);
    if ($diff->d > 0) {
        return $diff->d . 'd ' . $diff->h . 'h';
    } elseif ($diff->h > 0) {
        return $diff->h . 'h ' . $diff->i . 'min';
    }
    return $diff->i . 'min';
}

$dados = [];
$agora = new DateTime();

try {
    // Verifica se pelo menos uma tabela necessária existe antes de processar
    $tabelasNecessarias = ['analise_parcial', 'reparo_parcial', 'qualidade_registro'];
    $algumaTabelaExiste = false;
    
    foreach ($tabelasNecessarias as $tabela) {
        if (tabelaExiste($conn, $tabela)) {
            $algumaTabelaExiste = true;
            break;
        }
    }
    
    // Se nenhuma tabela existe, retorna array com operadores sem registro
    if (!$algumaTabelaExiste) {
        foreach ($operadores as $op) {
            $dados[] = [
                'operador'     => normalizarOperador($op),
                'status'       => 'Sem registro',
                'tempo'        => '---',
                'setor'        => '',
                'razao_social' => '',
                'quantidade'   => ''
            ];
        }
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    foreach ($operadores as $op) {
        $operador = normalizarOperador($op);
        $info = [
            'operador'     => $operador,
            'status'       => 'Sem registro',
            'tempo'        => '---',
            'setor'        => '',
            'razao_social' => '',
            'quantidade'   => ''
        ];

        $ultimo = buscarUltimoEvento($conn, $operador);

        if ($ultimo) {
            $info['status']       = $ultimo['status'] ?? '---';
            $info['setor']        = $ultimo['setor'] ?? '';
            $info['razao_social'] = $ultimo['razao_social'] ?? '';
            $info['quantidade']   = $ultimo['quantidade'] ?? '';

            // tempo desde o último evento
            try {
                $dataEvento = DateTime::createFromFormat('Y-m-d H:i:s', $ultimo['data_evento'])
                              ?: new DateTime($ultimo['data_evento']); // fallback
                $info['tempo'] = formatarTempoDecorrido($dataEvento, $agora);
            } catch (Exception $e) {
                $info['tempo'] = '---';
                error_log("Erro ao formatar data: " . $e->getMessage());
            }
        }

        $dados[] = $info;
    }

    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Erro geral no admin.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor'], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>