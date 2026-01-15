// 🔎 Drill-down KPI - Fluxo completo Card → Tabela → Detalhe
// Requisitos: componentes desacoplados, navegação por estado, reutilizável para qualquer KPI, sem duplicação de lógica de período.
// Não implementa CSS final nem backend real. Usa mocks e simulação de estado.

(function () {
    // --- Nível 1: Card KPI (usa KpiCard já existente) ---
    // O clique dispara navegação para a visão detalhada (tabela)

    // --- Nível 2: Tabela Operacional ---
    function KpiTable({ containerId, columns, dataProvider, onRowClick, periodParams }) {
        this.container = document.getElementById(containerId);
        if (!this.container) throw new Error(`Container '${containerId}' não encontrado`);
        this.columns = columns;
        this.dataProvider = dataProvider;
        this.onRowClick = typeof onRowClick === 'function' ? onRowClick : null;
        this.periodParams = periodParams || {};
        this.state = { page: 1, pageSize: 10, sortBy: null, sortDir: 'asc', data: [], total: 0, loading: false };
        this._renderLoading();
        this.refresh();
    }

    KpiTable.prototype.refresh = async function () {
        this.state.loading = true;
        this._renderLoading();
        try {
            const { page, pageSize, sortBy, sortDir } = this.state;
            const result = await this.dataProvider({
                ...this.periodParams,
                page,
                pageSize,
                sortBy,
                sortDir
            });
            this.state.data = result.data;
            this.state.total = result.total;
            this.state.loading = false;
            this._render();
        } catch (e) {
            this.state.loading = false;
            this._renderError(e.message || 'Erro ao carregar dados');
        }
    };

    KpiTable.prototype._renderLoading = function () {
        this.container.innerHTML = `<div class="kpi-table kpi-table--loading">Carregando...</div>`;
    };

    KpiTable.prototype._renderError = function (msg) {
        this.container.innerHTML = `<div class="kpi-table kpi-table--error">${msg}</div>`;
    };

    KpiTable.prototype._render = function () {
        const { data, page, pageSize, total, sortBy, sortDir } = this.state;
        const totalPages = Math.ceil(total / pageSize) || 1;
        this.container.innerHTML = `
            <div class="kpi-table">
                <table>
                    <thead>
                        <tr>
                            ${this.columns.map(col => `
                                <th>
                                    <button type="button" data-col="${col.key}" class="kpi-table__th-btn">
                                        ${col.label}
                                        ${sortBy === col.key ? (sortDir === 'asc' ? '▲' : '▼') : ''}
                                    </button>
                                </th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${data.length === 0 ? `<tr><td colspan="${this.columns.length}">Nenhum dado</td></tr>` :
                            data.map((row, i) => `
                                <tr data-row="${i}">
                                    ${this.columns.map(col => `<td>${row[col.key]}</td>`).join('')}
                                </tr>`).join('')}
                    </tbody>
                </table>
                <div class="kpi-table__pagination">
                    <button type="button" data-action="prev" ${page === 1 ? 'disabled' : ''}>Anterior</button>
                    <span>Página ${page} de ${totalPages}</span>
                    <button type="button" data-action="next" ${page === totalPages ? 'disabled' : ''}>Próxima</button>
                </div>
            </div>
        `;
        this._attachEvents();
    };

    KpiTable.prototype._attachEvents = function () {
        // Ordenação
        this.container.querySelectorAll('.kpi-table__th-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const col = btn.getAttribute('data-col');
                if (this.state.sortBy === col) {
                    this.state.sortDir = this.state.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.state.sortBy = col;
                    this.state.sortDir = 'asc';
                }
                this.refresh();
            });
        });
        // Paginação
        this.container.querySelector('[data-action="prev"]').addEventListener('click', () => {
            if (this.state.page > 1) {
                this.state.page--;
                this.refresh();
            }
        });
        this.container.querySelector('[data-action="next"]').addEventListener('click', () => {
            const totalPages = Math.ceil(this.state.total / this.state.pageSize) || 1;
            if (this.state.page < totalPages) {
                this.state.page++;
                this.refresh();
            }
        });
        // Clique em linha
        this.container.querySelectorAll('tbody tr[data-row]').forEach(tr => {
            tr.addEventListener('click', () => {
                const idx = parseInt(tr.getAttribute('data-row'), 10);
                if (this.onRowClick) this.onRowClick(this.state.data[idx]);
            });
        });
    };

    KpiTable.prototype.destroy = function () {
        this.container.innerHTML = '';
    };

    // --- Nível 3: Detalhe (modal ou painel lateral) ---
    function KpiDetail({ containerId, data, onClose }) {
        this.container = document.getElementById(containerId);
        if (!this.container) throw new Error(`Container '${containerId}' não encontrado`);
        this.data = data;
        this.onClose = typeof onClose === 'function' ? onClose : null;
        this._render();
    }

    KpiDetail.prototype._render = function () {
        this.container.innerHTML = `
            <div class="kpi-detail">
                <div class="kpi-detail__header">
                    <span>Detalhe do Item</span>
                    <button type="button" class="kpi-detail__close" aria-label="Fechar">×</button>
                </div>
                <div class="kpi-detail__body">
                    <pre>${JSON.stringify(this.data, null, 2)}</pre>
                </div>
            </div>
        `;
        this.container.querySelector('.kpi-detail__close').addEventListener('click', () => {
            if (this.onClose) this.onClose();
        });
    };

    KpiDetail.prototype.destroy = function () {
        this.container.innerHTML = '';
    };

    // --- Exemplo de fluxo completo para 1 KPI (mock) ---
    // document.addEventListener('DOMContentLoaded', () => {
    //     let state = { view: 'card', selectedRow: null };
    //     const periodParams = window.globalState?.getApiParams ? window.globalState.getApiParams() : {};
    //     // Nível 1: Card
    //     const card = new window.KpiCard('kpi-card-root', {
    //         kpiKey: 'backlog-atual',
    //         title: 'Backlog Atual',
    //         unit: 'number',
    //         dataProvider: async () => ({
    //             name: 'Backlog Atual', value: 1250, unit: 'number', variation: 5.9, trend: 'up', updatedAt: '2026-01-15T10:30:00Z', context: 'vs. média histórica'
    //         }),
    //         onClick: () => {
    //             state.view = 'table';
    //             render();
    //         }
    //     });
    //     // Nível 2: Tabela
    //     function renderTable() {
    //         new KpiTable({
    //             containerId: 'kpi-table-root',
    //             columns: [
    //                 { key: 'id', label: 'ID' },
    //                 { key: 'descricao', label: 'Descrição' },
    //                 { key: 'status', label: 'Status' }
    //             ],
    //             dataProvider: async ({ page, pageSize, sortBy, sortDir }) => {
    //                 // Mock: 25 itens
    //                 const all = Array.from({ length: 25 }, (_, i) => ({
    //                     id: 1000 + i,
    //                     descricao: `Item ${i + 1}`,
    //                     status: i % 3 === 0 ? 'Pendente' : 'Concluído'
    //                 }));
    //                 let sorted = all;
    //                 if (sortBy) {
    //                     sorted = [...all].sort((a, b) => {
    //                         if (a[sortBy] < b[sortBy]) return sortDir === 'asc' ? -1 : 1;
    //                         if (a[sortBy] > b[sortBy]) return sortDir === 'asc' ? 1 : -1;
    //                         return 0;
    //                     });
    //                 }
    //                 const start = (page - 1) * pageSize;
    //                 const paged = sorted.slice(start, start + pageSize);
    //                 return { data: paged, total: all.length };
    //             },
    //             onRowClick: (row) => {
    //                 state.selectedRow = row;
    //                 state.view = 'detail';
    //                 render();
    //             },
    //             periodParams
    //         });
    //     }
    //     // Nível 3: Detalhe
    //     function renderDetail() {
    //         new KpiDetail({
    //             containerId: 'kpi-detail-root',
    //             data: state.selectedRow,
    //             onClose: () => {
    //                 state.view = 'table';
    //                 render();
    //             }
    //         });
    //     }
    //     // Renderização principal
    //     function render() {
    //         document.getElementById('kpi-card-root').style.display = state.view === 'card' ? '' : 'none';
    //         document.getElementById('kpi-table-root').style.display = state.view === 'table' ? '' : 'none';
    //         document.getElementById('kpi-detail-root').style.display = state.view === 'detail' ? '' : 'none';
    //         if (state.view === 'table') renderTable();
    //         if (state.view === 'detail') renderDetail();
    //     }
    //     render();
    // });

    // Exporta para uso global
    window.KpiTable = KpiTable;
    window.KpiDetail = KpiDetail;
})();
