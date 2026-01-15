<?php
/**
 * 🧪 TESTE UNITÁRIO - FUNÇÕES kpiResponse() e kpiError()
 * 
 * Este arquivo testa as novas funções do contrato padronizado.
 * 
 * INSTRUÇÕES DE USO:
 * 1. Acesse: http://kpi.stbextrema.com.br/DashBoard/backendDash/kpis/TESTE_KPI_RESPONSE.php
 * 2. Adicione ?teste=success para testar resposta de sucesso
 * 3. Adicione ?teste=error para testar resposta de erro
 * 4. Sem parâmetros, executa ambos os testes
 */

require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';

// ============================================
// 🎯 SELETOR DE TESTE
// ============================================
$tipoTeste = $_GET['teste'] ?? 'menu';

// ============================================
// 📋 MENU DE TESTES
// ============================================
if ($tipoTeste === 'menu') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Teste KPI Response - VISTA</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 20px;
                padding: 40px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 800px;
                width: 100%;
            }
            h1 {
                color: #667eea;
                margin-bottom: 10px;
                font-size: 2rem;
            }
            .subtitle {
                color: #666;
                margin-bottom: 30px;
                font-size: 0.9rem;
            }
            .test-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-top: 30px;
            }
            .test-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 30px;
                border-radius: 15px;
                text-decoration: none;
                color: white;
                transition: transform 0.3s, box-shadow 0.3s;
                cursor: pointer;
            }
            .test-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            }
            .test-card h2 {
                font-size: 1.3rem;
                margin-bottom: 10px;
            }
            .test-card p {
                font-size: 0.9rem;
                opacity: 0.9;
            }
            .icon {
                font-size: 3rem;
                margin-bottom: 15px;
            }
            .info-box {
                background: #f8f9fa;
                border-left: 4px solid #667eea;
                padding: 20px;
                margin: 30px 0;
                border-radius: 8px;
            }
            .info-box h3 {
                color: #667eea;
                margin-bottom: 10px;
            }
            .info-box code {
                background: #e9ecef;
                padding: 2px 6px;
                border-radius: 4px;
                font-family: 'Courier New', monospace;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🧪 Teste de Contrato KPI</h1>
            <p class="subtitle">Sistema VISTA - Validação de Endpoints Padronizados</p>
            
            <div class="info-box">
                <h3>📋 Sobre Este Teste</h3>
                <p>Este módulo testa as funções <code>kpiResponse()</code> e <code>kpiError()</code> implementadas no arquivo <code>endpoint-helpers.php</code>.</p>
                <p style="margin-top: 10px;">Selecione um dos testes abaixo para validar o contrato JSON padronizado.</p>
            </div>

            <div class="test-grid">
                <a href="?teste=success" class="test-card">
                    <div class="icon">✅</div>
                    <h2>Teste de Sucesso</h2>
                    <p>Valida resposta padronizada com status "success" e estrutura completa de dados.</p>
                </a>

                <a href="?teste=error" class="test-card">
                    <div class="icon">❌</div>
                    <h2>Teste de Erro</h2>
                    <p>Valida resposta padronizada de erro com status "error" e mensagem descritiva.</p>
                </a>
            </div>

            <div class="info-box" style="margin-top: 30px;">
                <h3>🔧 Como Interpretar</h3>
                <ul style="margin-left: 20px; color: #666;">
                    <li style="margin: 8px 0;">Respostas devem ser JSON válido</li>
                    <li style="margin: 8px 0;">HTTP Status Code deve ser apropriado (200, 400, 500)</li>
                    <li style="margin: 8px 0;">Todos os campos obrigatórios devem estar presentes</li>
                    <li style="margin: 8px 0;">Timestamps devem estar no formato ISO 8601</li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================
// ✅ TESTE DE SUCESSO
// ============================================
if ($tipoTeste === 'success') {
    $startTime = microtime(true);
    
    // Simula processamento
    usleep(50000); // 50ms
    
    $data = [
        'valor' => 1250,
        'valor_formatado' => '1.250',
        'unidade' => 'equipamentos',
        'contexto' => 'Volume processado no período de teste',
        'detalhes' => [
            'quantidade_total' => 3750,
            'media_por_recebimento' => 3.0,
            'fonte' => 'Teste unitário'
        ],
        'referencia' => [
            'tipo' => 'media_30d',
            'valor' => 1180,
            'descricao' => 'Média dos últimos 30 dias'
        ],
        'variacao' => [
            'percentual' => 5.93,
            'tendencia' => 'alta',
            'estado' => 'success'
        ],
        'filtros_aplicados' => [
            'data_inicio' => '2026-01-07',
            'data_fim' => '2026-01-14',
            'operador' => 'Teste',
            'setor' => 'Teste'
        ],
        'observacoes' => [
            'Este é um teste do contrato padronizado.',
            'Se você está vendo isto, a função kpiResponse() está funcionando corretamente.',
            'Verifique se todos os campos estão presentes e bem formatados.'
        ]
    ];
    
    $executionTime = (microtime(true) - $startTime) * 1000;
    
    kpiResponse(
        'volume-processado-teste',
        '2026-01-07 / 2026-01-14',
        $data,
        $executionTime,
        200
    );
}

// ============================================
// ❌ TESTE DE ERRO
// ============================================
if ($tipoTeste === 'error') {
    kpiError(
        'volume-processado-teste',
        'Este é um erro de teste para validar o contrato padronizado. Se você está vendo isto, a função kpiError() está funcionando corretamente.',
        400
    );
}

// ============================================
// 🚫 TESTE INVÁLIDO
// ============================================
kpiError(
    'teste-desconhecido',
    'Tipo de teste inválido. Use ?teste=success ou ?teste=error',
    400
);
?>
