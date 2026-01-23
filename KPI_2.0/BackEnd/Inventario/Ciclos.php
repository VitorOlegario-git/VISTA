<?php
declare(strict_types=1);

use BackEnd\Core\ApiController;

require_once __DIR__ . '/../Core/ApiController.php';

class CiclosApi extends ApiController
{
    public function __construct()
    {
        parent::__construct();

        // Middlewares
        require_once __DIR__ . '/../Core/Middleware/AuthMiddleware.php';
        require_once __DIR__ . '/../Core/Middleware/AuditMiddleware.php';

        $this->middleware->add(new \BackEnd\Core\Middleware\AuthMiddleware());
        $this->middleware->add(new \BackEnd\Core\Middleware\AuditMiddleware());
    }

    public function handle(): void
    {
        $this->runWithMiddleware(function () {
            $this->handleRequest();
        });
    }

    /**
     * Core handler
     */
    private function handleRequest(): void
    {
        // =========================================================
        // DB CONNECTION (FAIL FAST)
        // =========================================================
        try {
            $db = getDb();
        } catch (Throwable $e) {
            error_log('[Inventario/Ciclos] DB CONNECTION FAIL: ' . $e->getMessage());
            $this->serverError('Banco indisponível');
            return; // <<< CRÍTICO: interrompe execução
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // =========================================================
        // GET — LISTAR CICLOS
        // =========================================================
        if ($method === 'GET') {
            try {
                $rows = $db->fetchAll(
                    'SELECT 
                        id,
                        mes_ano,
                        aberto_at,
                        encerrado_at,
                        encerrado_por
                     FROM inventario_ciclos
                     ORDER BY id DESC'
                );

                $this->ok([
                    'data' => is_array($rows) ? $rows : []
                ]);
                return;

            } catch (Throwable $e) {
                error_log('[Inventario/Ciclos] GET ERROR: ' . $e->getMessage());
                $this->serverError('Erro ao listar ciclos');
                return;
            }
        }

        // =========================================================
        // POST — CRIAR CICLO
        // =========================================================
        if ($method === 'POST') {

            // -------------------------
            // CSRF
            // -------------------------
            $csrf = $_POST['csrf_token'] ?? '';
            if (
                empty($csrf) ||
                !isset($_SESSION['csrf_token']) ||
                !hash_equals($_SESSION['csrf_token'], $csrf)
            ) {
                $this->forbidden();
                return;
            }

            // -------------------------
            // VALIDAR MÊS
            // -------------------------
            $mes = trim((string)($_POST['mes_ano'] ?? ''));

            if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
                $this->badRequest('Mês inválido (formato YYYY-MM)');
                return;
            }

            $periodoInicio = $mes . '-01';

            // -------------------------
            // TRANSAÇÃO
            // -------------------------
            try {
                $db->beginTransaction();

                // Verifica duplicidade
                $exists = $db->fetchOne(
                    'SELECT COUNT(*) AS total
                     FROM inventario_ciclos
                     WHERE tipo = ? AND periodo_inicio = ?',
                    ['mensal', $periodoInicio]
                );

                if ((int)($exists['total'] ?? 0) > 0) {
                    $db->rollBack();
                    $this->respond(409, [
                        'success' => false,
                        'error'   => 'Ciclo já existe para este mês'
                    ]);
                    return;
                }

                // Insere ciclo
                $db->insert(
                    'INSERT INTO inventario_ciclos (
                        tipo,
                        mes_ano,
                        periodo_inicio,
                        periodo_fim,
                        status,
                        criado_por
                    ) VALUES (?, ?, ?, LAST_DAY(?), ?, ?)',
                    [
                        'mensal',
                        $mes,
                        $periodoInicio,
                        $periodoInicio,
                        'aberto',
                        $_SESSION['username'] ?? 'system'
                    ]
                );

                $db->commit();

                $this->ok([
                    'message' => 'Ciclo criado com sucesso'
                ]);
                return;

            } catch (Throwable $e) {
                try { $db->rollBack(); } catch (Throwable $_) {}
                error_log('[Inventario/Ciclos] POST ERROR: ' . $e->getMessage());
                $this->serverError('Erro ao criar ciclo');
                return;
            }
        }

        // =========================================================
        // MÉTODO NÃO SUPORTADO
        // =========================================================
        $this->respond(405, [
            'success' => false,
            'error'   => 'Método não permitido'
        ]);
    }
}
