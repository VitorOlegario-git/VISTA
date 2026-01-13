/**
 * 🧱 FETCH HELPERS PADRONIZADOS — SUNLAB
 * 
 * Funções utilitárias para consumo defensivo de endpoints.
 * Todos os KPIs, Insights e Gráficos devem usar estas funções.
 * 
 * USO: <script src="jsDash/fetch-helpers.js"></script>
 */

/**
 * 🔹 FETCH PADRONIZADO COM TRATAMENTO DE ERRO
 * 
 * Função única para consumir todos os endpoints do sistema.
 * Garante tratamento consistente de erros e respostas.
 * 
 * @param {string} url URL do endpoint (absoluta ou relativa)
 * @param {Object} options Opções do fetch (opcional)
 * @returns {Promise<Object>} Resposta JSON do endpoint
 * @throws {Error} Se resposta for erro ou JSON inválido
 */
async function fetchKPI(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });

        // Verifica se resposta HTTP está OK
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        // Verifica content-type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Resposta não é JSON válido');
        }

        // Parse do JSON
        const data = await response.json();

        // Verifica se há erro na resposta
        if (data.error) {
            throw new Error(data.message || 'Erro desconhecido no servidor');
        }

        return data;
        
    } catch (error) {
        // Log do erro (pode ser customizado)
        console.error('❌ Erro em fetchKPI:', url, error.message);
        
        // Re-lança o erro para tratamento específico
        throw error;
    }
}

/**
 * 🔹 CONSTRUIR URL COM PARÂMETROS DO FILTRO GLOBAL
 * 
 * Cria URL com parâmetros padrão do filtro global.
 * 
 * @param {string} baseUrl URL base do endpoint
 * @param {Object} filtroGlobal Objeto com inicio, fim, operador
 * @param {Object} extraParams Parâmetros extras opcionais
 * @returns {string} URL completa com query string
 */
function construirURLFiltrada(baseUrl, filtroGlobal = {}, extraParams = {}) {
    const params = new URLSearchParams();

    // Adiciona parâmetros do filtro global
    if (filtroGlobal.inicio) {
        params.set('inicio', formatarDataParaURL(filtroGlobal.inicio));
    }
    
    if (filtroGlobal.fim) {
        params.set('fim', formatarDataParaURL(filtroGlobal.fim));
    }
    
    if (filtroGlobal.operador && filtroGlobal.operador !== 'Todos') {
        params.set('operador', filtroGlobal.operador);
    }

    // Adiciona parâmetros extras
    Object.keys(extraParams).forEach(key => {
        if (extraParams[key] !== null && extraParams[key] !== undefined) {
            params.set(key, extraParams[key]);
        }
    });

    const queryString = params.toString();
    return queryString ? `${baseUrl}?${queryString}` : baseUrl;
}

/**
 * 🔹 FORMATAR DATA PARA URL (dd/mm/yyyy)
 * 
 * Converte data do input (yyyy-mm-dd) para formato esperado pelo backend (dd/mm/yyyy).
 * 
 * @param {string} dataInput Data no formato yyyy-mm-dd
 * @returns {string} Data no formato dd/mm/yyyy
 */
function formatarDataParaURL(dataInput) {
    if (!dataInput) return '';
    
    // Se já está no formato dd/mm/yyyy, retorna direto
    if (dataInput.includes('/')) {
        return dataInput;
    }
    
    // Converte yyyy-mm-dd para dd/mm/yyyy
    const [ano, mes, dia] = dataInput.split('-');
    return `${dia}/${mes}/${ano}`;
}

/**
 * 🔹 FETCH COM RETRY AUTOMÁTICO
 * 
 * Tenta buscar dados com retry em caso de falha temporária.
 * 
 * @param {string} url URL do endpoint
 * @param {number} maxRetries Número máximo de tentativas (default: 3)
 * @param {number} delayMs Delay entre tentativas em ms (default: 1000)
 * @returns {Promise<Object>} Resposta JSON
 */
async function fetchComRetry(url, maxRetries = 3, delayMs = 1000) {
    let lastError;
    
    for (let i = 0; i < maxRetries; i++) {
        try {
            return await fetchKPI(url);
        } catch (error) {
            lastError = error;
            
            // Se for erro 4xx (cliente), não tenta novamente
            if (error.message.includes('HTTP 4')) {
                throw error;
            }
            
            // Se não é a última tentativa, aguarda e tenta novamente
            if (i < maxRetries - 1) {
                await new Promise(resolve => setTimeout(resolve, delayMs));
            }
        }
    }
    
    throw lastError;
}

/**
 * 🔹 FETCH EM LOTE (PARALELO)
 * 
 * Busca múltiplos endpoints em paralelo com Promise.all.
 * Retorna objeto com resultados indexados por chave.
 * 
 * @param {Object} endpoints Objeto com chave: url
 * @returns {Promise<Object>} Objeto com chave: dados
 */
async function fetchLote(endpoints) {
    const chaves = Object.keys(endpoints);
    const urls = Object.values(endpoints);
    
    try {
        const resultados = await Promise.all(
            urls.map(url => fetchKPI(url))
        );
        
        // Monta objeto de retorno
        const resultado = {};
        chaves.forEach((chave, index) => {
            resultado[chave] = resultados[index];
        });
        
        return resultado;
        
    } catch (error) {
        console.error('❌ Erro em fetchLote:', error);
        throw error;
    }
}

/**
 * 🔹 EXTRAIR VALOR DE KPI
 * 
 * Extrai valor do KPI da estrutura padronizada de resposta.
 * 
 * @param {Object} response Resposta do endpoint
 * @returns {*} Valor do KPI
 */
function extrairValorKPI(response) {
    if (!response || !response.data) {
        return null;
    }
    
    // Se data tem valor direto
    if (response.data.valor !== undefined) {
        return response.data.valor;
    }
    
    // Se data é o próprio valor
    return response.data;
}

/**
 * 🔹 VALIDAR RESPOSTA DE KPI
 * 
 * Verifica se resposta segue o contrato padrão de KPI.
 * 
 * @param {Object} response Resposta do endpoint
 * @returns {boolean} True se válido
 */
function validarRespostaKPI(response) {
    if (!response || !response.data) {
        console.warn('⚠️ Resposta sem campo "data"');
        return false;
    }
    
    if (!response.meta) {
        console.warn('⚠️ Resposta sem campo "meta"');
        return false;
    }
    
    // Valida estrutura de KPI se presente
    const data = response.data;
    if (data.valor !== undefined) {
        if (!data.unidade || !data.periodo || !data.contexto) {
            console.warn('⚠️ KPI sem campos obrigatórios (unidade/periodo/contexto)');
            return false;
        }
    }
    
    return true;
}

/**
 * 🔹 MOSTRAR ERRO AMIGÁVEL AO USUÁRIO
 * 
 * Exibe erro de forma amigável em elemento HTML.
 * 
 * @param {HTMLElement} elemento Elemento onde mostrar erro
 * @param {Error} erro Objeto de erro
 */
function mostrarErroAmigavel(elemento, erro) {
    if (!elemento) return;
    
    let mensagem = 'Erro ao carregar dados';
    
    if (erro.message.includes('HTTP 5')) {
        mensagem = 'Servidor temporariamente indisponível';
    } else if (erro.message.includes('HTTP 4')) {
        mensagem = 'Dados não encontrados';
    } else if (erro.message.includes('JSON')) {
        mensagem = 'Erro ao processar resposta';
    } else if (erro.message) {
        mensagem = erro.message;
    }
    
    elemento.innerHTML = `
        <div style="color: #ef4444; padding: 12px; background: #fef2f2; border-radius: 8px; border: 1px solid #fecaca;">
            <i class="fas fa-exclamation-circle"></i>
            <span style="margin-left: 8px;">${mensagem}</span>
        </div>
    `;
}

// 🔹 EXPORT PARA USO GLOBAL
window.fetchKPI = fetchKPI;
window.construirURLFiltrada = construirURLFiltrada;
window.formatarDataParaURL = formatarDataParaURL;
window.fetchComRetry = fetchComRetry;
window.fetchLote = fetchLote;
window.extrairValorKPI = extrairValorKPI;
window.validarRespostaKPI = validarRespostaKPI;
window.mostrarErroAmigavel = mostrarErroAmigavel;
