// 📚 SidebarKpi Component - Navegação lateral do Dashboard KPI
// Requisitos: grupos colapsáveis, item ativo, scroll interno, desacoplado do conteúdo, navegação baseada em estado.
// Não implementa CSS final; usa classes sem estilo definido. Não implementa roteamento real, apenas emite eventos via callback.

(function () {
    const DEFAULT_GROUPS = [
        {
            id: 'executivo',
            title: 'Executivo',
            items: [
                { id: 'exec-dashboard', label: 'Dashboard Executivo', targetView: 'exec-dashboard' }
            ]
        },
        {
            id: 'tempo',
            title: 'Tempo',
            items: [
                { id: 'tempo-medio', label: 'Tempo Médio Total', targetView: 'tempo-medio' },
                { id: 'sla', label: 'SLA / Lead Time', targetView: 'sla' }
            ]
        },
        {
            id: 'produtividade',
            title: 'Produtividade',
            items: [
                { id: 'backlog', label: 'Backlog', targetView: 'backlog' },
                { id: 'volume', label: 'Volume Processado', targetView: 'volume' }
            ]
        },
        {
            id: 'financeiro',
            title: 'Financeiro',
            items: [
                { id: 'billing', label: 'Faturamento', targetView: 'billing' },
                { id: 'cost', label: 'Custo Médio', targetView: 'cost' }
            ]
        },
        {
            id: 'qualidade',
            title: 'Qualidade',
            items: [
                { id: 'qualidade-geral', label: 'Taxa de Sucesso', targetView: 'qualidade-geral' },
                { id: 'retrabalho', label: 'Retrabalho', targetView: 'retrabalho' }
            ]
        }
    ];

    /**
     * SidebarKpi - navegação baseada em estado (sem acoplamento visual ou de dados)
     * @param {Object} options
     * @param {string} options.containerId - id do container da sidebar
     * @param {Array}  [options.groups] - modelo de dados dos grupos (id, title, items[])
     * @param {string} [options.initialView] - view inicial ativa
     * @param {Function} [options.onNavigate] - callback(viewId) chamado ao clicar em item
     */
    function SidebarKpi({ containerId, groups = DEFAULT_GROUPS, initialView = 'exec-dashboard', onNavigate = null }) {
        this.container = document.getElementById(containerId);
        if (!this.container) throw new Error(`Container '${containerId}' não encontrado`);

        this.groups = groups;
        this.state = {
            activeView: initialView,
            collapsed: {} // { groupId: boolean }
        };
        this.onNavigate = typeof onNavigate === 'function' ? onNavigate : null;

        this._render();
        this._attachEvents();
    }

    SidebarKpi.prototype._render = function () {
        this.container.innerHTML = `
            <nav class="sidebar-kpi" aria-label="Navegação KPI">
                <div class="sidebar-kpi__scroll">
                    ${this.groups.map(group => this._renderGroup(group)).join('')}
                </div>
            </nav>
        `;
    };

    SidebarKpi.prototype._renderGroup = function (group) {
        const isCollapsed = !!this.state.collapsed[group.id];
        return `
            <div class="sidebar-kpi__group" data-group-id="${group.id}">
                <button class="sidebar-kpi__group-header" aria-expanded="${!isCollapsed}" data-action="toggle-group">
                    <span class="sidebar-kpi__group-title">${group.title}</span>
                    <span class="sidebar-kpi__group-chevron">${isCollapsed ? '▶' : '▼'}</span>
                </button>
                <div class="sidebar-kpi__items" ${isCollapsed ? 'hidden' : ''}>
                    ${group.items.map(item => this._renderItem(item)).join('')}
                </div>
            </div>
        `;
    };

    SidebarKpi.prototype._renderItem = function (item) {
        const isActive = item.targetView === this.state.activeView;
        return `
            <button class="sidebar-kpi__item ${isActive ? 'is-active' : ''}" data-target="${item.targetView}" data-action="navigate" aria-current="${isActive ? 'page' : 'false'}">
                <span class="sidebar-kpi__item-label">${item.label}</span>
            </button>
        `;
    };

    SidebarKpi.prototype._attachEvents = function () {
        this.container.addEventListener('click', (event) => {
            const action = event.target.closest('[data-action]');
            if (!action) return;
            const actionType = action.getAttribute('data-action');

            if (actionType === 'toggle-group') {
                const groupEl = action.closest('[data-group-id]');
                const groupId = groupEl.getAttribute('data-group-id');
                this._toggleGroup(groupId);
            }

            if (actionType === 'navigate') {
                const targetView = action.getAttribute('data-target');
                this._setActiveView(targetView);
                if (this.onNavigate) this.onNavigate(targetView);
            }
        });
    };

    SidebarKpi.prototype._toggleGroup = function (groupId) {
        this.state.collapsed[groupId] = !this.state.collapsed[groupId];
        this._render();
    };

    SidebarKpi.prototype._setActiveView = function (viewId) {
        this.state.activeView = viewId;
        this._render();
    };

    SidebarKpi.prototype.destroy = function () {
        this.container.innerHTML = '';
    };

    // Exemplo de integração com Dashboard Executivo (estado simulado)
    // document.addEventListener('DOMContentLoaded', () => {
    //     const sidebar = new SidebarKpi({
    //         containerId: 'sidebar-root',
    //         initialView: 'exec-dashboard',
    //         onNavigate: (viewId) => {
    //             console.log('Navegar para view:', viewId);
    //             // Aqui você pode chamar composeExecutiveDashboard ou trocar de seção
    //         }
    //     });
    // });

    window.SidebarKpi = SidebarKpi;
})();
