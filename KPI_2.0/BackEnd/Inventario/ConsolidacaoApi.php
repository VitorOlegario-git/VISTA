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
                            "SELECT status FROM resumo_geral WHERE (remessa = ? OR codigo_remessa = ?) AND cnpj IS NOT NULL AND nota_fiscal IS NOT NULL AND TRIM(nota_fiscal) <> '' LIMIT 1",
                            [$rem, $rem]
                        );

                        if ($row) {
                            // Found a resumo_geral entry: use exact business rule for inventory
                            // Decision: consider 'aguardando_pg' as the canonical OK-for-inventory status.
                            $status_banco = $row['status'] ?? 'desconhecido';
                            if ($status_banco === 'aguardando_pg') {
                                $resultado = 'OK';
                            } elseif (stripos($status_banco, 'inventari') !== false || stripos($status_banco, 'confirm') !== false) {
                                // If status indicates it was already inventoried/confirmed, mark explicitly
                                $resultado = 'JA_INVENTARIADA';
                            } else {
                                // Any other status is considered a divergence that needs operator attention
                                $resultado = 'DIVERGENTE';
                            }
                        } else {
                            // If there exists a resumo_geral record for this remessa but it lacks nota_fiscal/cnpj,
                            // we must ignore it for inventory scope: do not create conciliacao.
                            $existsAny = $db->fetchOne(
                                "SELECT id, nota_fiscal, cnpj FROM resumo_geral WHERE remessa = ? OR codigo_remessa = ? LIMIT 1",
                                [$rem, $rem]
                            );
                            if ($existsAny && (empty($existsAny['cnpj']) || $existsAny['nota_fiscal'] === null || trim((string)$existsAny['nota_fiscal']) === '')) {
                                // Mark as ignored and skip DB insert
                                $status_banco = 'IGNORADO';
                                $resultado = 'IGNORADO';
                                $results[] = [
                                    'remessa' => $rem,
                                    'status_banco' => $status_banco,
                                    'resultado' => $resultado
                                ];
                                continue; // skip persisting conciliacao
                            }
                        }
                    } catch (Throwable $e) {
                        $status_banco = 'ERRO';
                    }

                    try {
                        // Persist conciliation and record operator in `observacao` because schema does not
                        // include an explicit created_by column. We avoid schema changes as requested.
                        $operadorLabel = 'operador_id:' . (getUsuarioId() ?? 0) . ' operador:' . (getUsuarioLogado() ?? '');
                        $db->execute(
                            "INSERT INTO inventario_conciliacoes
                             (ciclo_id, armario_id, remessa, status_inventario, status_banco, resultado, observacao, criado_em)
                             VALUES (?, ?, ?, 'informado', ?, ?, ?, NOW())
                             ON DUPLICATE KEY UPDATE
                                status_banco = VALUES(status_banco),
                                resultado = VALUES(resultado),
                                observacao = CONCAT(IFNULL(observacao, ''), ' | ', VALUES(observacao)),
                                updated_em = NOW()",
                            [$ciclo_id, $armario_id, $rem, $status_banco, $resultado, $operadorLabel]
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
