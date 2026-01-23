<?php
declare(strict_types=1);

use BackEnd\Core\ApiController;

require_once __DIR__ . '/../Core/ApiController.php';

class ConsolidacaoApi extends ApiController
{
    public function handle(): void
    {
        $this->requireAuth();

        try {
            $db = getDb();
        } catch (Throwable) {
            $this->serverError('Banco indisponível');
            return;
        }

        $action = $_REQUEST['action'] ?? '';

        switch ($action) {

            case 'compare_armario':
                verificarCSRF();

                $ciclo_id   = (int)($_POST['ciclo_id'] ?? 0);
                $armario_id = trim((string)($_POST['armario_id'] ?? ''));
                $raw        = $_POST['remessas'] ?? [];

                $remessas = is_array($raw)
                    ? $raw
                    : preg_split('/[\r\n,;]+/', (string)$raw, -1, PREG_SPLIT_NO_EMPTY);

                // Normaliza: trim em todos os itens e remove vazios
                $remessas = array_map('trim', $remessas);
                $remessas = array_values(array_filter($remessas, function($v){ return $v !== ''; }));

                if ($ciclo_id <= 0)  $this->badRequest('ciclo_id inválido');
                if ($armario_id === '') $this->badRequest('armario_id é obrigatório');
                if (count($remessas) === 0) $this->badRequest('Nenhuma remessa informada');
                if (count($remessas) > 500) $this->badRequest('Limite de 500 remessas excedido');

                $results = [];

                foreach ($remessas as $r) {
                    $rem = substr(trim((string)$r), 0, 64);
                    if ($rem === '') continue;

                    $status_banco = 'INEXISTENTE';
                    $resultado    = 'INEXISTENTE';

                    try {
                        // Checa colunas alternativas de remessa (remessa ou codigo_remessa)
                        $row = $db->fetchOne(
                            'SELECT status FROM resumo_geral WHERE remessa = ? OR codigo_remessa = ? LIMIT 1',
                            [$rem, $rem]
                        );

                        if ($row) {
                            $status_banco = $row['status'] ?? 'desconhecido';
                            $resultado = (
                                stripos($status_banco, 'aguardando') !== false ||
                                stripos($status_banco, 'pendente') !== false
                            ) ? 'DIVERGENTE' : 'OK';
                        }
                    } catch (Throwable $e) {
                        $status_banco = 'ERRO';
                    }

                    try {
                        $db->execute(
                            "INSERT INTO inventario_conciliacoes
                             (ciclo_id, armario_id, remessa, status_inventario, status_banco, resultado, criado_em)
                             VALUES (?, ?, ?, 'informado', ?, ?, NOW())
                             ON DUPLICATE KEY UPDATE
                                status_banco = VALUES(status_banco),
                                resultado = VALUES(resultado),
                                updated_em = NOW()",
                            [$ciclo_id, $armario_id, $rem, $status_banco, $resultado]
                        );
                    } catch (Throwable $e) {
                        $this->serverError('Falha ao salvar conciliação');
                        return;
                    }

                    $results[] = [
                        'remessa' => $rem,
                        'status_banco' => $status_banco,
                        'resultado' => $resultado
                    ];
                }

                $this->ok(['data' => $results]);
                return;

            default:
                $this->badRequest('Ação inválida');
        }
    }
}
