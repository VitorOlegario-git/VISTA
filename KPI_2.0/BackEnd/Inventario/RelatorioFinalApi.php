<?php
declare(strict_types=1);

use BackEnd\Core\ApiController;

require_once __DIR__ . '/../Core/ApiController.php';

class RelatorioFinalApi extends ApiController
{
    public function __construct()
    {
        parent::__construct();
        require_once __DIR__ . '/../Core/Middleware/AuthMiddleware.php';
        require_once __DIR__ . '/../Core/Middleware/AuditMiddleware.php';
        $this->middleware->add(new \BackEnd\Core\Middleware\AuthMiddleware());
        $this->middleware->add(new \BackEnd\Core\Middleware\AuditMiddleware());
    }

    public function handle(): void
    {
        $this->runWithMiddleware(function () { $this->handleRequest(); });
    }

    private function handleRequest(): void
    {
        $action = $_GET['action'] ?? '';

        try {
            $db = getDb();
        } catch (Throwable $e) {
            $this->serverError('Banco indisponível');
        }

        if ($action === 'gerar_relatorio_ciclo') {
            $ciclo_id = isset($_GET['ciclo_id']) ? (int)$_GET['ciclo_id'] : 0;
            if ($ciclo_id <= 0) $this->badRequest('ciclo_id é obrigatório');

            try {
                $ciclo = $db->fetchOne('SELECT id, mes_ano, status, aberto_at, encerrado_at, encerrado_por FROM inventario_ciclos WHERE id = ? LIMIT 1', [$ciclo_id]);
                if (!$ciclo) $this->notFound('Ciclo não encontrado');

                $agg = $db->fetchOne(
                    "SELECT
                        COUNT(*) AS total,
                        SUM(resultado = 'OK') AS ok,
                        SUM(resultado = 'DIVERGENTE') AS divergente,
                        SUM(resultado = 'INEXISTENTE') AS inexistente
                    FROM inventario_conciliacoes
                    WHERE ciclo_id = ?",
                    [$ciclo_id]
                );

                $total = (int)($agg['total'] ?? 0);
                $ok = (int)($agg['ok'] ?? 0);
                $div = (int)($agg['divergente'] ?? 0);
                $inex = (int)($agg['inexistente'] ?? 0);
                $percent_ok = $total > 0 ? round(($ok / $total) * 100, 1) : 0.0;

                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $per = isset($_GET['per']) ? min(1000, max(10, (int)$_GET['per'])) : 200;

                $countRow = $db->fetchOne('SELECT COUNT(*) AS total FROM inventario_conciliacoes WHERE ciclo_id = ?', [$ciclo_id]);
                $totalItems = (int)($countRow['total'] ?? 0);
                $totalPages = $per > 0 ? (int)ceil($totalItems / $per) : 1;
                $offset = ($page - 1) * $per;

                $items = $db->fetchAll(
                    'SELECT ciclo_id, armario_id, remessa, status_inventario, status_banco, resultado, observacao, criado_em FROM inventario_conciliacoes WHERE ciclo_id = ? ORDER BY criado_em DESC LIMIT ? OFFSET ?',
                    [$ciclo_id, $per, $offset]
                );

                $resumo = [
                    'total' => $total,
                    'ok' => $ok,
                    'divergente' => $div,
                    'inexistente' => $inex,
                    'percentual_ok' => $percent_ok
                ];
                $conclusao = sprintf(
                    'Relatório gerado em %s: %d itens no ciclo %s — %s%% OK. Divergentes: %d; Inexistentes: %d.',
                    date('Y-m-d H:i:s'),
                    $total,
                    $ciclo['mes_ano'] ?? $ciclo_id,
                    number_format($percent_ok, 1, ',', '.'),
                    $div,
                    $inex
                );

                $meta = [
                    'page' => $page,
                    'per' => $per,
                    'total_items' => $totalItems,
                    'total_pages' => $totalPages,
                ];

                $this->ok(['ciclo' => $ciclo, 'resumo' => $resumo, 'itens' => $items, 'meta' => $meta, 'conclusao' => $conclusao]);
            } catch (Throwable $e) {
                error_log('[Inventario/RelatorioFinal] ERROR: ' . $e->getMessage());
                $this->serverError('Erro ao gerar relatório');
            }
            return;
        }

        if ($action === 'export_csv') {
            $ciclo_id = isset($_GET['ciclo_id']) ? (int)$_GET['ciclo_id'] : 0;
            if ($ciclo_id <= 0) $this->badRequest('ciclo_id é obrigatório');

            try {
                $rows = $db->fetchAll('SELECT ciclo_id, armario_id, remessa, status_inventario, status_banco, resultado, observacao, criado_em FROM inventario_conciliacoes WHERE ciclo_id = ? ORDER BY criado_em DESC', [$ciclo_id]);
            } catch (Throwable $e) {
                error_log('[Inventario/RelatorioFinal] CSV ERROR: ' . $e->getMessage());
                $this->serverError('Erro ao gerar CSV');
            }

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="inventario_relatorio_ciclo_' . $ciclo_id . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ciclo_id','armario_id','remessa','status_inventario','status_banco','resultado','observacao','criado_em']);
            foreach ($rows as $r) {
                fputcsv($out, [$r['ciclo_id'],$r['armario_id'],$r['remessa'],$r['status_inventario'],$r['status_banco'],$r['resultado'],$r['observacao'],$r['criado_em']]);
            }
            fclose($out);
            exit();
        }

        $this->badRequest('Ação inválida');
    }
}
