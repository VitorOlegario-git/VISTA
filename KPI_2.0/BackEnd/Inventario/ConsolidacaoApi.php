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

                // Consolidação compara estado real derivado, não status salvo
                // Fonte do status: vw_resumo_estado_real.status_real

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
                        // Use vw_resumo_estado_real.status_real as authoritative status
                        $row = $db->fetchOne(
                            "SELECT v.status_real AS status_real
                             FROM vw_resumo_estado_real v
                             JOIN resumo_geral r ON r.id = v.resumo_id
                             WHERE (r.remessa = ? OR r.codigo_remessa = ?)
                               AND r.cnpj IS NOT NULL AND r.nota_fiscal IS NOT NULL AND TRIM(r.nota_fiscal) <> ''
                             LIMIT 1",
                            [$rem, $rem]
                        );

                        if ($row) {
                            // Found a derived state: use business rules against status_real
                            $status_banco = $row['status_real'] ?? 'desconhecido';
                            if ($status_banco === 'aguardando_pg') {
                                $resultado = 'OK';
                            } elseif (stripos($status_banco, 'inventari') !== false || stripos($status_banco, 'confirm') !== false) {
                                $resultado = 'JA_INVENTARIADA';
                            } else {
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
