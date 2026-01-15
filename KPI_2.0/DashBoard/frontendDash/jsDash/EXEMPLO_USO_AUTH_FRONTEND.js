/**
 * EXEMPLO DE USO DO MIDDLEWARE DE AUTENTICAÇÃO
 * Frontend JavaScript - Sistema VISTA KPI 2.0
 * 
 * Demonstra como incluir o token de autenticação nas requisições fetch()
 * 
 * @version 1.0
 * @created 15/01/2026
 */

// =============================================================================
// CONFIGURAÇÃO DO TOKEN
// =============================================================================

/**
 * Token de autenticação - DEVE SER ARMAZENADO DE FORMA SEGURA
 * 
 * OPÇÕES:
 * 1. Variável de ambiente no build (Webpack/Vite)
 * 2. Meta tag no HTML (para PHP server-side)
 * 3. localStorage/sessionStorage (menos seguro)
 * 4. Cookie HttpOnly (mais seguro - requer backend)
 */

// Opção 1: Variável de ambiente (Webpack/Vite)
// const API_TOKEN = process.env.VISTA_API_TOKEN;

// Opção 2: Meta tag injetada pelo PHP
function getTokenFromMetaTag() {
    const metaTag = document.querySelector('meta[name="vista-api-token"]');
    return metaTag ? metaTag.getAttribute('content') : null;
}

// Opção 3: localStorage (exemplo - menos seguro)
function getTokenFromStorage() {
    return localStorage.getItem('vista_api_token');
}

// Opção 4: Token hardcoded (APENAS PARA DESENVOLVIMENTO)
const DEV_TOKEN = 'your-token-here'; // ⚠️ NUNCA USE EM PRODUÇÃO!

// Token ativo (escolha uma das opções acima)
const API_TOKEN = getTokenFromMetaTag() || getTokenFromStorage() || DEV_TOKEN;

// =============================================================================
// HELPER: FETCH COM AUTENTICAÇÃO
// =============================================================================

/**
 * Faz requisição fetch() incluindo header de autenticação
 * 
 * @param {string} url - URL do endpoint
 * @param {object} options - Opções do fetch (opcional)
 * @returns {Promise<object>} Response JSON
 * 
 * @example
 * const data = await fetchComAuth('/api/kpi-backlog.php?inicio=01/01/2026&fim=15/01/2026');
 * console.log(data);
 */
async function fetchComAuth(url, options = {}) {
    // 1. Configurar headers padrão
    const defaultHeaders = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };

    // 2. Adicionar token de autenticação
    if (API_TOKEN) {
        defaultHeaders['Authorization'] = `Bearer ${API_TOKEN}`;
    }

    // 3. Mesclar headers customizados
    const mergedOptions = {
        ...options,
        headers: {
            ...defaultHeaders,
            ...(options.headers || {})
        }
    };

    // 4. Fazer requisição
    try {
        const response = await fetch(url, mergedOptions);

        // 5. Verificar erro de autenticação
        if (response.status === 401) {
            handleAuthError();
            throw new Error('Autenticação falhou - token inválido ou ausente');
        }

        // 6. Verificar outros erros HTTP
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error?.message || `HTTP ${response.status}`);
        }

        // 7. Retornar JSON
        return await response.json();

    } catch (error) {
        console.error('❌ Erro na requisição:', error);
        throw error;
    }
}

/**
 * Trata erro de autenticação (401)
 * 
 * Pode redirecionar para login, exibir modal, etc.
 */
function handleAuthError() {
    console.error('🔒 Erro de autenticação - token inválido');
    
    // Opção 1: Exibir alerta
    alert('Sessão expirada. Faça login novamente.');
    
    // Opção 2: Redirecionar para login
    // window.location.href = '/login.php';
    
    // Opção 3: Exibir modal customizado
    // showAuthModal();
}

// =============================================================================
// EXEMPLO 1: CARREGAR KPI COM AUTENTICAÇÃO
// =============================================================================

async function carregarKPIBacklog() {
    try {
        const inicio = '01/01/2026';
        const fim = '15/01/2026';
        const url = `/DashBoard/backendDash/recebimentoPHP/kpi-backlog-atual.php?inicio=${inicio}&fim=${fim}`;
        
        const response = await fetchComAuth(url);
        
        console.log('✅ KPI Backlog:', response);
        // Atualizar UI com response.data...
        
    } catch (error) {
        console.error('Erro ao carregar KPI:', error);
    }
}

// =============================================================================
// EXEMPLO 2: CARREGAR MÚLTIPLOS KPIs EM PARALELO
// =============================================================================

async function carregarTodosKPIs() {
    const periodo = {
        inicio: '01/01/2026',
        fim: '15/01/2026'
    };

    const endpoints = [
        `/DashBoard/backendDash/recebimentoPHP/kpi-backlog-atual.php?inicio=${periodo.inicio}&fim=${periodo.fim}`,
        `/DashBoard/backendDash/kpis/kpi-total-processado.php?inicio=${periodo.inicio}&fim=${periodo.fim}`,
        `/DashBoard/backendDash/kpis/kpi-tempo-medio.php?inicio=${periodo.inicio}&fim=${periodo.fim}`,
        `/DashBoard/backendDash/kpis/kpi-taxa-sucesso.php?inicio=${periodo.inicio}&fim=${periodo.fim}`
    ];

    try {
        // Todas as requisições incluirão o token automaticamente
        const results = await Promise.all(
            endpoints.map(url => fetchComAuth(url))
        );

        console.log('✅ Todos os KPIs carregados:', results);
        // Atualizar UI...

    } catch (error) {
        console.error('Erro ao carregar KPIs:', error);
    }
}

// =============================================================================
// EXEMPLO 3: POST COM AUTENTICAÇÃO
// =============================================================================

async function salvarDados(dados) {
    const url = '/BackEnd/api/salvar.php';
    
    const options = {
        method: 'POST',
        body: JSON.stringify(dados)
    };

    try {
        const response = await fetchComAuth(url, options);
        console.log('✅ Dados salvos:', response);
        return response;
    } catch (error) {
        console.error('Erro ao salvar:', error);
        throw error;
    }
}

// =============================================================================
// EXEMPLO 4: FETCH NATIVO (SEM HELPER)
// =============================================================================

async function fetchNativoComAuth() {
    const url = '/DashBoard/backendDash/kpis/kpi-backlog-atual.php?inicio=01/01/2026&fim=15/01/2026';
    
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${API_TOKEN}`,
            'Content-Type': 'application/json'
        }
    });

    if (response.status === 401) {
        throw new Error('Não autorizado');
    }

    return await response.json();
}

// =============================================================================
// EXEMPLO 5: AXIOS (SE ESTIVER USANDO)
// =============================================================================

/**
 * Configuração do Axios com interceptor de autenticação
 */
if (typeof axios !== 'undefined') {
    // Configurar token globalmente
    axios.defaults.headers.common['Authorization'] = `Bearer ${API_TOKEN}`;

    // Interceptor para erros de autenticação
    axios.interceptors.response.use(
        response => response,
        error => {
            if (error.response && error.response.status === 401) {
                handleAuthError();
            }
            return Promise.reject(error);
        }
    );

    // Exemplo de uso
    async function carregarComAxios() {
        try {
            const response = await axios.get('/api/kpi-backlog.php', {
                params: {
                    inicio: '01/01/2026',
                    fim: '15/01/2026'
                }
            });
            console.log('✅ Dados (Axios):', response.data);
        } catch (error) {
            console.error('Erro (Axios):', error);
        }
    }
}

// =============================================================================
// EXEMPLO 6: JQUERY AJAX (SE ESTIVER USANDO)
// =============================================================================

/**
 * Configuração do jQuery Ajax com autenticação
 */
if (typeof $ !== 'undefined') {
    // Configurar header global
    $.ajaxSetup({
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', `Bearer ${API_TOKEN}`);
        }
    });

    // Exemplo de uso
    function carregarComJQuery() {
        $.ajax({
            url: '/api/kpi-backlog.php',
            method: 'GET',
            data: {
                inicio: '01/01/2026',
                fim: '15/01/2026'
            },
            success: function(response) {
                console.log('✅ Dados (jQuery):', response);
            },
            error: function(xhr, status, error) {
                if (xhr.status === 401) {
                    handleAuthError();
                }
                console.error('Erro (jQuery):', error);
            }
        });
    }
}

// =============================================================================
// GERENCIAMENTO DE TOKEN
// =============================================================================

/**
 * Salva token no localStorage
 * 
 * @param {string} token - Token de autenticação
 */
function salvarToken(token) {
    localStorage.setItem('vista_api_token', token);
    console.log('✅ Token salvo com sucesso');
}

/**
 * Remove token do localStorage
 */
function removerToken() {
    localStorage.removeItem('vista_api_token');
    console.log('✅ Token removido');
}

/**
 * Verifica se token está presente
 * 
 * @returns {boolean}
 */
function temToken() {
    return !!getTokenFromStorage();
}

// =============================================================================
// INTEGRAÇÃO COM SISTEMA EXISTENTE
// =============================================================================

/**
 * Adaptação do fetch-helpers.js existente
 * 
 * Adicione esta função no arquivo: DashBoard/frontendDash/jsDash/fetch-helpers.js
 */

// ANTES (sem autenticação):
/*
async function fetchKPI(url) {
    const response = await fetch(url);
    return await response.json();
}
*/

// DEPOIS (com autenticação):
async function fetchKPI(url) {
    const token = getTokenFromMetaTag() || getTokenFromStorage();
    
    const headers = {
        'Content-Type': 'application/json'
    };
    
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    
    const response = await fetch(url, { headers });
    
    if (response.status === 401) {
        console.error('Token inválido ou ausente');
        // Tratar erro...
    }
    
    return await response.json();
}

// =============================================================================
// INJEÇÃO DO TOKEN VIA PHP (RECOMENDADO)
// =============================================================================

/**
 * No arquivo DashboardExecutivo.php, adicione:
 * 
 * <head>
 *     ...
 *     <meta name="vista-api-token" content="<?php echo getenv('VISTA_API_TOKEN'); ?>">
 * </head>
 * 
 * ⚠️ IMPORTANTE: Só injete o token se o usuário estiver autenticado no sistema!
 * 
 * Exemplo completo:
 * 
 * <?php
 * session_start();
 * $apiToken = null;
 * if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
 *     $apiToken = getenv('VISTA_API_TOKEN');
 * }
 * ?>
 * 
 * <meta name="vista-api-token" content="<?php echo htmlspecialchars($apiToken ?? ''); ?>">
 */

// =============================================================================
// TESTE DE AUTENTICAÇÃO
// =============================================================================

/**
 * Testa se o token está funcionando
 */
async function testarAutenticacao() {
    console.log('🧪 Testando autenticação...');
    
    try {
        const response = await fetchComAuth(
            '/DashBoard/backendDash/recebimentoPHP/kpi-backlog-atual.php?inicio=01/01/2026&fim=15/01/2026'
        );
        
        console.log('✅ Autenticação OK!');
        console.log('📊 Resposta:', response);
        
    } catch (error) {
        console.error('❌ Autenticação falhou:', error.message);
    }
}

// Executar teste automaticamente (remover em produção)
// testarAutenticacao();

// =============================================================================
// EXPORT (se estiver usando módulos ES6)
// =============================================================================

// export { fetchComAuth, salvarToken, removerToken, temToken, testarAutenticacao };
