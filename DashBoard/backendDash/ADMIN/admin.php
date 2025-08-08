<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . "/sistema/KPI_2.0/BackEnd/conexao.php";
header('Content-Type: application/json');

$operadores = ['Vitor Olegario', 'Luan Oliveira', 'ronyrodrigues', 'Ederson Santos', 'Matheus Ferreira'];
$dados = [];

foreach ($operadores as $operador) {
    $ultimoRegistro = null;

    // Tenta buscar o último apontamento na tabela de análise
    $stmt1 = $conn->prepare("
        SELECT quantidade_total, operacao_destino AS status, razao_social, data_registro AS data, 'Análise' AS setor
        FROM analise_parcial
        WHERE operador = ?
        ORDER BY data_registro DESC
        LIMIT 1
    ");
    $stmt1->bind_param("s", $operador);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $registroAnalise = $result1->fetch_assoc();

    // Tenta buscar o último apontamento na tabela de reparo
    $stmt2 = $conn->prepare("
        SELECT quantidade_total, operacao_destino AS status, razao_social, data_registro AS data, 'Reparo' AS setor
        FROM reparo_parcial
        WHERE operador = ?
        ORDER BY data_registro DESC
        LIMIT 1
    ");
    $stmt2->bind_param("s", $operador);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $registroReparo = $result2->fetch_assoc();

    // Verifica qual dos dois é mais recente
    if ($registroAnalise && $registroReparo) {
        $dataAnalise = new DateTime($registroAnalise['data']);
        $dataReparo = new DateTime($registroReparo['data']);
        $ultimoRegistro = $dataAnalise > $dataReparo ? $registroAnalise : $registroReparo;
    } elseif ($registroAnalise) {
        $ultimoRegistro = $registroAnalise;
    } elseif ($registroReparo) {
        $ultimoRegistro = $registroReparo;
    }

    // Dados de resposta
    $info = [
        'operador' => $operador,
        'status' => 'Sem registro',
        'tempo' => '---',
        'setor' => '',
        'razao_social' => '',
        'quantidade' => ''
    ];

    if ($ultimoRegistro) {
        $dataRegistro = new DateTime($ultimoRegistro['data']);
        $agora = new DateTime();
        $diff = $agora->diff($dataRegistro);

        if ($diff->d > 0) {
            $tempo = $diff->d . 'd ' . $diff->h . 'h';
        } elseif ($diff->h > 0) {
            $tempo = $diff->h . 'h ' . $diff->i . 'min';
        } else {
            $tempo = $diff->i . 'min';
        }

        $info['status'] = $ultimoRegistro['status'];
        $info['tempo'] = $tempo;
        $info['setor'] = $ultimoRegistro['setor'];
        $info['razao_social'] = $ultimoRegistro['razao_social'];
        $info['quantidade'] = $ultimoRegistro['quantidade_total'];
    }

    $dados[] = $info;
}

echo json_encode($dados);
$conn->close();
?>
