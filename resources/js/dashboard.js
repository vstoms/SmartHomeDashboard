import axios from 'axios';
import { GridStack } from 'gridstack';
import 'gridstack/dist/gridstack.min.css';

class DashboardController {
    constructor() {
        this.gridElement = document.getElementById('dashboard-grid');
        this.deviceCards = document.querySelectorAll('.device-card');
        this.flowCards = document.querySelectorAll('.flow-card');
        this.pollInterval = 5000;
        this.pollTimer = null;
        this.grid = null;
        this.isEditMode = false;
        this.originalLayout = null;

        // Search configuration
        this.searchConfig = {
            placeholder: 'Search devices and flows...',
            scope: 'all', // 'all', 'devices', 'flows'
            minChars: 1,
            debounceMs: 150,
            highlightMatches: true,
            caseSensitive: false,
            autoSuggest: {
                enabled: true,
                maxSuggestions: 5,
                showRecent: true,
                recentLimit: 3
            }
        };

        // Search state
        this.searchState = {
            query: '',
            results: { devices: [], flows: [] },
            recentSearches: [],
            allItems: { devices: [], flows: [] }
        };

        if (this.gridElement) {
            this.init();
        }
    }

    init() {
        this.initGridStack();
        this.setupDeviceCards();
        this.setupFlowCards();
        this.setupMultiSwitchCards();
        this.setupEditMode();
        this.setupAddItemsPanel();
        this.setupRemoveButtons();
        this.setupConfigureButtons();
        this.setupConfigureModal();
        this.setupDeviceGroupModal();
        this.setupSearch();
        this.loadRecentSearches();
        this.loadInitialStates();
        this.startPolling();
        this.currentConfigureItemId = null;
        this.currentConfigureGroupId = null;
        this.allDevicesForGroup = [];
    }

    initGridStack() {
        this.grid = GridStack.init({
            column: 6,
            cellHeight: 150,
            margin: 8,
            float: true,
            animate: true,
            staticGrid: true,
        }, this.gridElement);
    }

    setupEditMode() {
        const editToggle = document.getElementById('edit-toggle');
        const editToggleText = document.getElementById('edit-toggle-text');
        const editControls = document.getElementById('edit-controls');
        const saveBtn = document.getElementById('save-layout');
        const cancelBtn = document.getElementById('cancel-edit');

        if (!editToggle) return;

        editToggle.addEventListener('click', () => {
            if (!this.isEditMode) {
                this.enterEditMode();
                editToggleText.textContent = 'Editing...';
                editToggle.classList.add('lux-button-primary');
                editControls.classList.remove('translate-y-full');
            } else {
                this.exitEditMode(false);
                editToggleText.textContent = 'Edit Layout';
                editToggle.classList.remove('lux-button-primary');
                editControls.classList.add('translate-y-full');
            }
        });

        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveLayout());
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                this.exitEditMode(true);
                editToggleText.textContent = 'Edit Layout';
                editToggle.classList.remove('lux-button-primary');
                editControls.classList.add('translate-y-full');
            });
        }
    }

    enterEditMode() {
        this.isEditMode = true;
        this.originalLayout = this.grid.save(true, true);
        this.grid.setStatic(false);
        this.gridElement.classList.add('edit-mode');
        this.stopPolling();
        this.showAddItemsPanel();
        this.loadAvailableItems();
    }

    exitEditMode(revert = false) {
        this.isEditMode = false;
        this.grid.setStatic(true);
        this.gridElement.classList.remove('edit-mode');

        if (revert && this.originalLayout) {
            this.grid.load(this.originalLayout);
        }

        this.originalLayout = null;
        this.startPolling();
        this.hideAddItemsPanel();
    }

    showAddItemsPanel() {
        const panel = document.getElementById('add-items-panel');
        const mainContent = document.getElementById('main-content');
        if (panel) {
            panel.classList.remove('translate-x-full');
        }
        if (mainContent) {
            mainContent.style.marginRight = '320px';
        }
    }

    hideAddItemsPanel() {
        const panel = document.getElementById('add-items-panel');
        const mainContent = document.getElementById('main-content');
        const searchInput = document.getElementById('items-search');
        
        if (panel) {
            panel.classList.add('translate-x-full');
        }
        if (mainContent) {
            mainContent.style.marginRight = '';
        }
        
        // Clear search when closing panel
        if (searchInput) {
            searchInput.value = '';
            this.clearSearch();
        }
    }

    // ==================== SEARCH FUNCTIONALITY ====================

    setupSearch() {
        const searchInput = document.getElementById('items-search');
        const clearBtn = document.getElementById('clear-search');
        const filterBtns = document.querySelectorAll('.search-filter-btn');
        
        if (!searchInput) return;
        
        // Debounced search handler
        let debounceTimer;
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            
            // Show/hide clear button
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', query.length === 0);
            }
            
            // Debounce search
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                this.performSearch(query);
            }, this.searchConfig.debounceMs);
            
            // Show auto-suggest if enabled
            if (this.searchConfig.autoSuggest.enabled && query.length >= this.searchConfig.minChars) {
                this.showAutoSuggest(query);
            } else {
                this.hideAutoSuggest();
            }
        });
        
        // Clear search
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                clearBtn.classList.add('hidden');
                this.clearSearch();
                searchInput.focus();
            });
        }
        
        // Scope filter buttons
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                this.setSearchScope(btn.dataset.scope);
            });
        });
        
        // Keyboard navigation for auto-suggest
        searchInput.addEventListener('keydown', (e) => {
            this.handleSearchKeyboard(e);
        });
        
        // Close auto-suggest when clicking outside
        document.addEventListener('click', (e) => {
            const suggestContainer = document.getElementById('search-suggestions');
            if (suggestContainer && !searchInput.contains(e.target) && !suggestContainer.contains(e.target)) {
                this.hideAutoSuggest();
            }
        });
    }

    setSearchScope(scope) {
        this.searchConfig.scope = scope;
        
        // Update UI
        document.querySelectorAll('.search-filter-btn').forEach(btn => {
            const isActive = btn.dataset.scope === scope;
            btn.classList.toggle('active', isActive);
            
            if (isActive) {
                btn.classList.remove('bg-gray-700/50', 'text-gray-400', 'border-transparent');
                btn.classList.add('bg-blue-600/20', 'text-blue-400', 'border-blue-500/30');
            } else {
                btn.classList.add('bg-gray-700/50', 'text-gray-400', 'border-transparent');
                btn.classList.remove('bg-blue-600/20', 'text-blue-400', 'border-blue-500/30');
            }
        });
        
        // Update placeholder
        this.updateSearchPlaceholder();
        
        // Re-run search with new scope
        if (this.searchState.query) {
            this.performSearch(this.searchState.query);
        } else {
            this.showAllItems();
        }
    }

    updateSearchPlaceholder() {
        const input = document.getElementById('items-search');
        if (!input) return;
        
        const placeholders = {
            'all': 'Search devices and flows...',
            'devices': 'Search devices...',
            'flows': 'Search flows...'
        };
        
        input.placeholder = placeholders[this.searchConfig.scope] || placeholders.all;
    }

    performSearch(query) {
        this.searchState.query = query;
        
        if (query.length < this.searchConfig.minChars) {
            this.showAllItems();
            return;
        }
        
        const { devices, flows } = this.searchState.allItems;
        const searchLower = this.searchConfig.caseSensitive ? query : query.toLowerCase();
        
        // Filter devices
        const filteredDevices = devices.filter(device => {
            const name = this.searchConfig.caseSensitive ? device.name : device.name.toLowerCase();
            return name.includes(searchLower);
        });
        
        // Filter flows
        const filteredFlows = flows.filter(flow => {
            const name = this.searchConfig.caseSensitive ? flow.name : flow.name.toLowerCase();
            return name.includes(searchLower);
        });
        
        // Apply scope filter
        let results = { devices: [], flows: [] };
        switch (this.searchConfig.scope) {
            case 'devices':
                results.devices = filteredDevices;
                break;
            case 'flows':
                results.flows = filteredFlows;
                break;
            default:
                results = { devices: filteredDevices, flows: filteredFlows };
        }
        
        this.searchState.results = results;
        this.renderSearchResults(results, query);
        
        // Save to recent searches
        if (query.length >= 2) {
            this.addToRecentSearches(query);
        }
    }

    renderSearchResults(results, query) {
        const devicesContainer = document.getElementById('available-devices');
        const flowsContainer = document.getElementById('available-flows');
        const devicesCount = document.getElementById('devices-count');
        const flowsCount = document.getElementById('flows-count');
        
        // Update counts
        if (devicesCount) devicesCount.textContent = results.devices.length;
        if (flowsCount) flowsCount.textContent = results.flows.length;
        
        // Render devices
        if (devicesContainer) {
            if (this.searchConfig.scope === 'flows') {
                // Hide devices section when filtering by flows only
                devicesContainer.closest('div').parentElement.style.display = 'none';
            } else {
                devicesContainer.closest('div').parentElement.style.display = '';
                if (results.devices.length === 0) {
                    devicesContainer.innerHTML = query
                        ? `<p class="search-no-results">No devices match "${this.escapeHtml(query)}"</p>`
                        : '<p class="text-gray-500 text-sm py-2 text-center">All devices added!</p>';
                } else {
                    devicesContainer.innerHTML = results.devices.map(d => this.renderItemButton(d, 'device', query)).join('');
                }
            }
        }
        
        // Render flows
        if (flowsContainer) {
            if (this.searchConfig.scope === 'devices') {
                // Hide flows section when filtering by devices only
                flowsContainer.closest('div').parentElement.style.display = 'none';
            } else {
                flowsContainer.closest('div').parentElement.style.display = '';
                if (results.flows.length === 0) {
                    flowsContainer.innerHTML = query
                        ? `<p class="search-no-results">No flows match "${this.escapeHtml(query)}"</p>`
                        : '<p class="text-gray-500 text-sm py-2 text-center">All flows added!</p>';
                } else {
                    flowsContainer.innerHTML = results.flows.map(f => this.renderItemButton(f, 'flow', query)).join('');
                }
            }
        }
        
        // Re-attach click handlers
        this.setupAddItemButtons();
    }

    renderItemButton(item, type, query) {
        const name = this.searchConfig.highlightMatches && query
            ? this.highlightMatch(item.name, query)
            : this.escapeHtml(item.name);
        
        const colorClass = type === 'device' ? 'bg-blue-500' : 'bg-purple-500';
        
        return `
            <button class="add-item-btn lux-list-item w-full text-left p-2 rounded-lg flex items-center gap-2 transition-colors"
                    data-type="${type}" data-id="${item.id}" data-name="${this.escapeHtml(item.name)}">
                <span class="w-2 h-2 rounded-full ${colorClass} flex-shrink-0"></span>
                <span class="flex-1 truncate">${name}</span>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
        `;
    }

    highlightMatch(text, query) {
        if (!query) return this.escapeHtml(text);
        
        const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${escapedQuery})`, this.searchConfig.caseSensitive ? 'g' : 'gi');
        
        // First escape the text, then apply highlighting
        const escapedText = this.escapeHtml(text);
        const escapedQueryForReplace = this.escapeHtml(query);
        const replaceRegex = new RegExp(`(${escapedQueryForReplace.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        
        return escapedText.replace(replaceRegex, '<span class="search-highlight">$1</span>');
    }

    showAutoSuggest(query) {
        const searchInput = document.getElementById('items-search');
        let suggestContainer = document.getElementById('search-suggestions');
        
        // Create container if it doesn't exist
        if (!suggestContainer) {
            suggestContainer = document.createElement('div');
            suggestContainer.id = 'search-suggestions';
            suggestContainer.className = 'search-suggestions hidden';
            searchInput.parentElement.style.position = 'relative';
            searchInput.parentElement.appendChild(suggestContainer);
        }
        
        const { devices, flows } = this.searchState.allItems;
        const searchLower = query.toLowerCase();
        const maxSuggestions = this.searchConfig.autoSuggest.maxSuggestions;
        
        // Get matching items based on scope
        let suggestions = [];
        
        if (this.searchConfig.scope !== 'flows') {
            devices.forEach(d => {
                if (d.name.toLowerCase().includes(searchLower)) {
                    suggestions.push({ ...d, type: 'device' });
                }
            });
        }
        
        if (this.searchConfig.scope !== 'devices') {
            flows.forEach(f => {
                if (f.name.toLowerCase().includes(searchLower)) {
                    suggestions.push({ ...f, type: 'flow' });
                }
            });
        }
        
        // Limit suggestions
        suggestions = suggestions.slice(0, maxSuggestions);
        
        if (suggestions.length === 0) {
            this.hideAutoSuggest();
            return;
        }
        
        // Build HTML
        let html = '';
        
        // Add recent searches if enabled and query is short
        if (this.searchConfig.autoSuggest.showRecent &&
            this.searchState.recentSearches.length > 0 &&
            query.length <= 2) {
            const matchingRecent = this.searchState.recentSearches
                .filter(term => term.toLowerCase().includes(searchLower) && term.toLowerCase() !== searchLower)
                .slice(0, this.searchConfig.autoSuggest.recentLimit);
            
            if (matchingRecent.length > 0) {
                html += '<div class="search-section-header">Recent</div>';
                html += matchingRecent.map(term => `
                    <div class="search-suggestion-item recent" data-search-term="${this.escapeHtml(term)}">
                        <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>${this.escapeHtml(term)}</span>
                    </div>
                `).join('');
                html += '<div class="search-section-header">Suggestions</div>';
            }
        }
        
        // Render suggestions
        html += suggestions.map((item, index) => `
            <div class="search-suggestion-item" data-index="${index}" data-type="${item.type}" data-id="${item.id}" data-name="${this.escapeHtml(item.name)}">
                <span class="suggestion-type ${item.type}">${item.type}</span>
                <span class="flex-1 truncate">${this.highlightMatch(item.name, query)}</span>
            </div>
        `).join('');
        
        suggestContainer.innerHTML = html;
        suggestContainer.classList.remove('hidden');
        
        // Setup click handlers
        suggestContainer.querySelectorAll('.search-suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                if (item.dataset.searchTerm) {
                    // Recent search clicked
                    searchInput.value = item.dataset.searchTerm;
                    this.performSearch(item.dataset.searchTerm);
                } else {
                    // Item clicked - add directly
                    this.addItemFromSuggestion(item);
                }
                this.hideAutoSuggest();
            });
        });
    }

    hideAutoSuggest() {
        const suggestContainer = document.getElementById('search-suggestions');
        if (suggestContainer) {
            suggestContainer.classList.add('hidden');
        }
    }

    addItemFromSuggestion(suggestionEl) {
        const type = suggestionEl.dataset.type;
        const id = suggestionEl.dataset.id;
        
        // Find the actual button and trigger click
        const btn = document.querySelector(`.add-item-btn[data-id="${id}"][data-type="${type}"]`);
        if (btn) {
            btn.click();
        }
    }

    handleSearchKeyboard(e) {
        const suggestContainer = document.getElementById('search-suggestions');
        if (!suggestContainer || suggestContainer.classList.contains('hidden')) {
            if (e.key === 'Escape') {
                this.clearSearch();
                document.getElementById('items-search').blur();
            }
            return;
        }
        
        const items = suggestContainer.querySelectorAll('.search-suggestion-item');
        const selectedIndex = Array.from(items).findIndex(item => item.classList.contains('selected'));
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                const nextIndex = selectedIndex < items.length - 1 ? selectedIndex + 1 : 0;
                items.forEach((item, i) => item.classList.toggle('selected', i === nextIndex));
                items[nextIndex]?.scrollIntoView({ block: 'nearest' });
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                const prevIndex = selectedIndex > 0 ? selectedIndex - 1 : items.length - 1;
                items.forEach((item, i) => item.classList.toggle('selected', i === prevIndex));
                items[prevIndex]?.scrollIntoView({ block: 'nearest' });
                break;
                
            case 'Enter':
                e.preventDefault();
                const selected = suggestContainer.querySelector('.search-suggestion-item.selected');
                if (selected) {
                    selected.click();
                }
                break;
                
            case 'Escape':
                this.hideAutoSuggest();
                break;
        }
    }

    clearSearch() {
        this.searchState.query = '';
        this.hideAutoSuggest();
        this.showAllItems();
        
        const clearBtn = document.getElementById('clear-search');
        if (clearBtn) {
            clearBtn.classList.add('hidden');
        }
    }

    showAllItems() {
        const { devices, flows } = this.searchState.allItems;
        
        // Reset scope visibility
        const devicesSection = document.getElementById('available-devices')?.closest('div')?.parentElement;
        const flowsSection = document.getElementById('available-flows')?.closest('div')?.parentElement;
        
        if (devicesSection) devicesSection.style.display = '';
        if (flowsSection) flowsSection.style.display = '';
        
        this.renderSearchResults({ devices, flows }, '');
    }

    addToRecentSearches(query) {
        const recent = this.searchState.recentSearches;
        
        // Remove if already exists
        const index = recent.findIndex(term => term.toLowerCase() === query.toLowerCase());
        if (index > -1) {
            recent.splice(index, 1);
        }
        
        // Add to beginning
        recent.unshift(query);
        
        // Limit size
        if (recent.length > 10) {
            recent.pop();
        }
        
        // Persist to localStorage
        try {
            localStorage.setItem('dashboard_recent_searches', JSON.stringify(recent));
        } catch (e) {
            console.warn('Could not save recent searches:', e);
        }
    }

    loadRecentSearches() {
        try {
            const saved = localStorage.getItem('dashboard_recent_searches');
            if (saved) {
                this.searchState.recentSearches = JSON.parse(saved);
            }
        } catch (e) {
            console.warn('Could not load recent searches:', e);
        }
    }

    // ==================== END SEARCH FUNCTIONALITY ====================

    async loadAvailableItems() {
        const uuid = this.gridElement.dataset.dashboardUuid;
        const devicesContainer = document.getElementById('available-devices');
        const flowsContainer = document.getElementById('available-flows');
        const devicesCount = document.getElementById('devices-count');
        const flowsCount = document.getElementById('flows-count');

        try {
            const response = await axios.get(`/api/v1/dashboards/${uuid}/available-items`);
            const { devices, flows } = response.data;

            // Store for search functionality
            this.searchState.allItems = { devices, flows };

            // Check if there's an active search
            const searchInput = document.getElementById('items-search');
            if (searchInput && searchInput.value.trim()) {
                this.performSearch(searchInput.value.trim());
                return;
            }

            // Update counts
            if (devicesCount) devicesCount.textContent = devices.length;
            if (flowsCount) flowsCount.textContent = flows.length;

            // Render devices
            if (devicesContainer) {
                if (devices.length === 0) {
                    devicesContainer.innerHTML = '<p class="text-gray-500 text-sm py-2 text-center">All devices added!</p>';
                } else {
                    devicesContainer.innerHTML = devices.map(d => `
                        <button class="add-item-btn lux-list-item w-full text-left p-2 rounded-lg flex items-center gap-2 transition-colors"
                                data-type="device" data-id="${d.id}" data-name="${this.escapeHtml(d.name)}">
                            <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                            <span class="flex-1 truncate">${this.escapeHtml(d.name)}</span>
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    `).join('');
                }
            }

            // Render flows
            if (flowsContainer) {
                if (flows.length === 0) {
                    flowsContainer.innerHTML = '<p class="text-gray-500 text-sm py-2 text-center">All flows added!</p>';
                } else {
                    flowsContainer.innerHTML = flows.map(f => `
                        <button class="add-item-btn lux-list-item w-full text-left p-2 rounded-lg flex items-center gap-2 transition-colors"
                                data-type="flow" data-id="${f.id}" data-name="${this.escapeHtml(f.name)}">
                            <span class="w-2 h-2 rounded-full bg-purple-500 flex-shrink-0"></span>
                            <span class="flex-1 truncate">${this.escapeHtml(f.name)}</span>
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    `).join('');
                }
            }

            // Setup click handlers
            this.setupAddItemButtons();

        } catch (error) {
            console.error('Failed to load available items:', error);
            if (devicesContainer) devicesContainer.innerHTML = '<p class="text-red-400 text-sm py-2 text-center">Failed to load</p>';
            if (flowsContainer) flowsContainer.innerHTML = '<p class="text-red-400 text-sm py-2 text-center">Failed to load</p>';
        }
    }

    setupAddItemButtons() {
        document.querySelectorAll('.add-item-btn').forEach(btn => {
            btn.addEventListener('click', () => this.addItem(btn));
        });
    }

    async addItem(btn) {
        const type = btn.dataset.type;
        const homeyId = btn.dataset.id;
        const name = btn.dataset.name;
        const uuid = this.gridElement.dataset.dashboardUuid;

        btn.disabled = true;
        btn.classList.add('opacity-50');

        try {
            const response = await axios.post(`/api/v1/dashboards/${uuid}/items`, {
                type,
                homey_id: homeyId,
                name
            });

            if (response.data.success) {
                const item = response.data.item;

                // Create the widget element
                const widgetEl = this.createWidgetElement(item);

                // Add to grid
                this.grid.addWidget(widgetEl, {
                    id: item.id,
                    x: item.grid_x,
                    y: item.grid_y,
                    w: item.grid_w,
                    h: item.grid_h,
                    minW: 1,
                    minH: 1,
                });

                // Setup remove button for the new widget
                const removeBtn = widgetEl.querySelector('.remove-item-btn');
                if (removeBtn) {
                    removeBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.removeItem(removeBtn);
                    });
                }

                // Setup configure button for the new widget
                const configureBtn = widgetEl.querySelector('.configure-item-btn');
                if (configureBtn) {
                    configureBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.openConfigureModal(configureBtn.dataset.itemId);
                    });
                }

                // Remove from available list
                btn.remove();
                this.loadAvailableItems();

                // Hide empty state if visible
                const emptyState = document.getElementById('empty-state');
                if (emptyState) emptyState.style.display = 'none';

                this.showToast(`${name} added!`);

                // Open configure modal for devices
                if (type === 'device') {
                    this.openConfigureModal(item.id);
                }
            }
        } catch (error) {
            console.error('Failed to add item:', error);
            this.showToast('Failed to add item');
            btn.disabled = false;
            btn.classList.remove('opacity-50');
        }
    }

    createWidgetElement(item) {
        const wrapper = document.createElement('div');
        wrapper.className = 'grid-stack-item';
        wrapper.setAttribute('gs-id', item.id);

        const content = document.createElement('div');
        content.className = 'grid-stack-item-content';

        // Create edit buttons container
        const editBtns = document.createElement('div');
        editBtns.className = 'edit-item-buttons absolute top-2 right-2 flex gap-1 z-50';

        // Add configure button for devices
        if (item.type === 'device') {
            const configureBtn = document.createElement('button');
            configureBtn.className = 'configure-item-btn w-6 h-6 bg-blue-500 hover:bg-blue-600 rounded-full flex items-center justify-center transition-colors';
            configureBtn.dataset.itemId = item.id;
            configureBtn.title = 'Configure';
            configureBtn.innerHTML = `<svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>`;
            editBtns.appendChild(configureBtn);
        }

        // Add remove button
        const removeBtn = document.createElement('button');
        removeBtn.className = 'remove-item-btn w-6 h-6 bg-red-500 hover:bg-red-600 rounded-full flex items-center justify-center transition-colors';
        removeBtn.dataset.itemId = item.id;
        removeBtn.title = 'Remove';
        removeBtn.innerHTML = `<svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>`;
        editBtns.appendChild(removeBtn);

        if (item.type === 'device') {
            content.innerHTML = `<div class="device-card lux-card rounded-2xl p-4 flex flex-col overflow-hidden transition-all duration-300 active:scale-95"
                     data-device-id="${item.homey_id}"
                     data-item-id="${item.id}"
                     data-display-capabilities="[]">
                    <div class="flex justify-between items-start gap-2 flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-base truncate">${this.escapeHtml(item.name)}</h3>
                            <div class="device-status-row text-sm text-gray-300">
                                <span class="status-dot" aria-hidden="true"></span>
                                <span class="device-status">--</span>
                            </div>
                        </div>
                        <button class="toggle-btn lux-toggle w-16 h-9 rounded-full relative flex-shrink-0 touch-manipulation"
                                data-capability="onoff"
                                aria-label="Toggle device"
                                aria-pressed="false">
                            <span class="toggle-indicator absolute top-1 left-1 w-7 h-7 rounded-full transition-transform duration-200"></span>
                        </button>
                    </div>
                    <div class="flex-1"></div>
                </div>`;
        } else {
            content.innerHTML = `<div class="flow-card lux-card lux-flow rounded-2xl p-4 flex flex-col items-center justify-center text-center cursor-pointer transition-all duration-300 active:scale-95"
                     data-flow-id="${item.homey_id}">
                    <div class="lux-flow-icon w-12 h-12 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-sm">${this.escapeHtml(item.name)}</h3>
                    <span class="flow-status text-xs text-purple-200 mt-1">Tap to run</span>
                </div>`;
        }

        wrapper.appendChild(content);
        wrapper.appendChild(editBtns);
        return wrapper;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    setupAddItemsPanel() {
        const closeBtn = document.getElementById('close-add-panel');
        const refreshBtn = document.getElementById('refresh-items');

        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hideAddItemsPanel());
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadAvailableItems());
        }
    }

    setupRemoveButtons() {
        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.removeItem(btn);
            });
        });
    }

    async removeItem(btn) {
        const itemId = btn.dataset.itemId;
        const uuid = this.gridElement.dataset.dashboardUuid;
        const gridItem = btn.closest('.grid-stack-item');

        if (!confirm('Remove this item from the dashboard?')) {
            return;
        }

        btn.disabled = true;

        try {
            const response = await axios.delete(`/api/v1/dashboards/${uuid}/items/${itemId}`);

            if (response.data.success) {
                // Remove from grid
                this.grid.removeWidget(gridItem);

                // Refresh available items list
                this.loadAvailableItems();

                this.showToast('Item removed');
            }
        } catch (error) {
            console.error('Failed to remove item:', error);
            this.showToast('Failed to remove item');
            btn.disabled = false;
        }
    }

    setupConfigureButtons() {
        document.querySelectorAll('.configure-item-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.openConfigureModal(btn.dataset.itemId);
            });
        });
    }

    setupConfigureModal() {
        const modal = document.getElementById('configure-modal');
        const closeBtn = document.getElementById('close-configure-modal');
        const cancelBtn = document.getElementById('configure-cancel');
        const saveBtn = document.getElementById('configure-save');

        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeConfigureModal());
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.closeConfigureModal());
        }
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveItemConfig());
        }
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) this.closeConfigureModal();
            });
        }
    }

    async openConfigureModal(itemId) {
        const uuid = this.gridElement.dataset.dashboardUuid;
        const modal = document.getElementById('configure-modal');

        this.currentConfigureItemId = itemId;

        try {
            const response = await axios.get(`/api/v1/dashboards/${uuid}/items/${itemId}`);
            const item = response.data;
            console.log('Configure item data:', item);
            console.log('Capabilities:', item.capabilities);
            console.log('Current settings:', item.settings);

            // Set name
            document.getElementById('configure-name').value = item.name;
            document.getElementById('configure-modal-title').textContent = `Configure: ${item.name}`;

            // Build controls list
            const controlsList = document.getElementById('configure-controls-list');
            const controlCaps = ['onoff', 'dim', 'target_temperature'];
            const settings = item.settings || {};

            let controlsHtml = '';
            if (item.capabilities.onoff) {
                const checked = settings.show_toggle !== false ? 'checked' : '';
                controlsHtml += `<label class="flex items-center gap-3 p-2 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600">
                    <input type="checkbox" name="show_toggle" ${checked} class="rounded border-gray-500 text-blue-500">
                    <span>On/Off Toggle</span>
                </label>`;
            }
            if (item.capabilities.dim) {
                const checked = settings.show_dimmer !== false ? 'checked' : '';
                controlsHtml += `<label class="flex items-center gap-3 p-2 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600">
                    <input type="checkbox" name="show_dimmer" ${checked} class="rounded border-gray-500 text-blue-500">
                    <span>Dimmer Slider</span>
                </label>`;
            }
            if (item.capabilities.target_temperature) {
                const checked = settings.show_thermostat !== false ? 'checked' : '';
                controlsHtml += `<label class="flex items-center gap-3 p-2 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600">
                    <input type="checkbox" name="show_thermostat" ${checked} class="rounded border-gray-500 text-blue-500">
                    <span>Thermostat Controls</span>
                </label>`;
            }
            controlsList.innerHTML = controlsHtml || '<p class="text-gray-500 text-sm">No controls available</p>';

            // Build sensors list
            const sensorsList = document.getElementById('configure-sensors-list');
            const displayCaps = settings.display_capabilities || [];
            const sensorCaps = Object.entries(item.capabilities).filter(([key]) => !controlCaps.includes(key));

            if (sensorCaps.length > 0) {
                sensorsList.innerHTML = sensorCaps.map(([key, cap]) => {
                    const checked = displayCaps.includes(key) ? 'checked' : '';
                    const title = cap.title || key.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    const value = cap.value !== undefined ? ` (${typeof cap.value === 'boolean' ? (cap.value ? 'Yes' : 'No') : cap.value}${cap.units || ''})` : '';
                    return `<label class="flex items-center gap-3 p-2 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600">
                        <input type="checkbox" name="display_cap" value="${key}" ${checked} class="rounded border-gray-500 text-blue-500">
                        <span class="flex-1">${title}</span>
                        <span class="text-gray-400 text-sm">${value}</span>
                    </label>`;
                }).join('');
            } else {
                sensorsList.innerHTML = '<p class="text-gray-500 text-sm">No sensors available</p>';
            }

            // Show modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');

        } catch (error) {
            console.error('Failed to load item config:', error);
            this.showToast('Failed to load configuration');
        }
    }

    closeConfigureModal() {
        const modal = document.getElementById('configure-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        this.currentConfigureItemId = null;
    }

    async saveItemConfig() {
        if (!this.currentConfigureItemId) return;

        const uuid = this.gridElement.dataset.dashboardUuid;
        const itemId = this.currentConfigureItemId;

        const name = document.getElementById('configure-name').value;
        const showToggle = document.querySelector('input[name="show_toggle"]')?.checked ?? true;
        const showDimmer = document.querySelector('input[name="show_dimmer"]')?.checked ?? true;
        const showThermostat = document.querySelector('input[name="show_thermostat"]')?.checked ?? true;
        const displayCapabilities = Array.from(document.querySelectorAll('input[name="display_cap"]:checked'))
            .map(cb => cb.value);

        console.log('Saving config:', { name, showToggle, showDimmer, showThermostat, displayCapabilities });

        try {
            const response = await axios.put(`/api/v1/dashboards/${uuid}/items/${itemId}`, {
                name,
                show_toggle: showToggle,
                show_dimmer: showDimmer,
                show_thermostat: showThermostat,
                display_capabilities: displayCapabilities,
            });

            console.log('Save response:', response.data);

            if (response.data.success) {
                this.closeConfigureModal();
                // Save layout first, then reload to show changes
                await this.saveLayoutQuiet();
                window.location.reload();
            }
        } catch (error) {
            console.error('Failed to save config:', error);
            console.error('Error response:', error.response?.data);
            this.showToast('Failed to save settings');
        }
    }

    async saveLayoutQuiet() {
        const uuid = this.gridElement.dataset.dashboardUuid;
        const gridItems = this.grid.getGridItems();

        const items = gridItems.map(el => {
            const node = el.gridstackNode;
            return {
                id: parseInt(el.getAttribute('gs-id')),
                x: node.x || 0,
                y: node.y || 0,
                w: node.w || 1,
                h: node.h || 1,
            };
        });

        try {
            await axios.post(`/api/v1/dashboards/${uuid}/layout`, { items });
        } catch (error) {
            console.error('Failed to save layout:', error);
        }
    }

    async saveLayout() {
        const uuid = this.gridElement.dataset.dashboardUuid;
        const gridItems = this.grid.getGridItems();

        const items = gridItems.map(el => {
            const node = el.gridstackNode;
            return {
                id: parseInt(el.getAttribute('gs-id')),
                x: node.x || 0,
                y: node.y || 0,
                w: node.w || 1,
                h: node.h || 1,
            };
        });

        console.log('Saving layout:', items);

        try {
            const response = await axios.post(`/api/v1/dashboards/${uuid}/layout`, { items });
            console.log('Save response:', response.data);
            this.showToast('Layout saved!');
            this.exitEditMode(false);

            const editToggle = document.getElementById('edit-toggle');
            const editToggleText = document.getElementById('edit-toggle-text');
            const editControls = document.getElementById('edit-controls');

            editToggleText.textContent = 'Edit Layout';
            editToggle.classList.remove('lux-button-primary');
            editControls.classList.add('translate-y-full');
        } catch (error) {
            console.error('Failed to save layout:', error);
            console.error('Error response:', error.response?.data);
            this.showToast('Failed to save layout: ' + (error.response?.data?.message || error.message));
        }
    }

    showToast(message, duration = 3000) {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        if (toast && toastMessage) {
            toastMessage.textContent = message;
            toast.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0');
            }, duration);
        }
    }

    async loadInitialStates() {
        const deviceIds = Array.from(this.deviceCards).map(card => card.dataset.deviceId);
        if (deviceIds.length === 0) return;

        try {
            const response = await axios.post('/api/v1/devices/states', { devices: deviceIds });
            this.updateDeviceStates(response.data);
        } catch (error) {
            console.error('Failed to load device states:', error);
        }
    }

    updateDeviceStates(states) {
        for (const [deviceId, capabilities] of Object.entries(states)) {
            const card = document.querySelector(`[data-device-id="${deviceId}"]`);
            if (!card) continue;

            const statusEl = card.querySelector('.device-status');

            if (capabilities.onoff !== undefined) {
                const isOn = capabilities.onoff.value;
                const toggleBtn = card.querySelector('.toggle-btn');
                const statusDot = card.querySelector('.status-dot');

                if (toggleBtn) {
                    toggleBtn.classList.toggle('is-on', isOn);
                    toggleBtn.setAttribute('aria-pressed', isOn ? 'true' : 'false');
                }

                if (statusDot) {
                    statusDot.classList.toggle('is-on', isOn);
                }

                if (statusEl) {
                    statusEl.textContent = isOn ? 'On' : 'Off';
                }
            }

            if (capabilities.dim !== undefined) {
                const dimSlider = card.querySelector('[data-capability="dim"]');
                const dimValue = card.querySelector('.dimmer-value');
                const dimmerRing = card.querySelector('.lux-dimmer');

                const percent = Math.round(capabilities.dim.value * 100);
                if (dimSlider) dimSlider.value = percent;
                if (dimValue) dimValue.textContent = `${percent}%`;
                if (dimmerRing) dimmerRing.style.setProperty('--dimmer', percent);
            }

            if (capabilities.target_temperature !== undefined) {
                const tempValue = card.querySelector('.temp-value');

                if (tempValue) {
                    tempValue.textContent = capabilities.target_temperature.value.toFixed(1);
                    card.dataset.currentTemp = capabilities.target_temperature.value;
                }
            }

            // Update all sensor values
            this.updateSensorValues(card, capabilities);
        }
    }

    updateSensorValues(card, capabilities) {
        const sensorItems = card.querySelectorAll('.sensor-item');

        sensorItems.forEach(item => {
            const capId = item.dataset.sensor;
            const valueEl = item.querySelector('.sensor-value');

            if (capabilities[capId] !== undefined && valueEl) {
                const cap = capabilities[capId];
                let value = cap.value;
                const units = cap.units || '';

                // Format value based on type
                if (typeof value === 'boolean') {
                    value = value ? 'Yes' : 'No';
                } else if (typeof value === 'number') {
                    // Format numbers nicely
                    if (capId.includes('temperature')) {
                        value = value.toFixed(1);
                    } else if (capId.includes('humidity') || capId.includes('battery')) {
                        value = Math.round(value);
                    } else if (capId.includes('power') || capId.includes('energy')) {
                        value = value.toFixed(1);
                    } else if (Number.isInteger(value)) {
                        value = value.toString();
                    } else {
                        value = value.toFixed(2);
                    }
                }

                valueEl.textContent = `${value}${units}`;
            }
        });
    }

    setupDeviceCards() {
        this.deviceCards.forEach(card => {
            const deviceId = card.dataset.deviceId;

            const toggleBtn = card.querySelector('.toggle-btn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', (e) => {
                    if (this.isEditMode) return;
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleDevice(deviceId, toggleBtn, card);
                });
            }

            const dimSlider = card.querySelector('[data-capability="dim"]');
            if (dimSlider) {
                let debounceTimer;
                dimSlider.addEventListener('input', (e) => {
                    if (this.isEditMode) return;
                    const dimValue = card.querySelector('.dimmer-value');
                    const dimmerRing = card.querySelector('.lux-dimmer');
                    if (dimValue) dimValue.textContent = `${e.target.value}%`;
                    if (dimmerRing) dimmerRing.style.setProperty('--dimmer', e.target.value);

                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        this.setCapability(deviceId, 'dim', e.target.value / 100);
                    }, 300);
                });
            }

            const tempDown = card.querySelector('.temp-down');
            const tempUp = card.querySelector('.temp-up');
            const tempValue = card.querySelector('.temp-value');

            if (tempDown && tempUp && tempValue) {
                tempDown.addEventListener('click', (e) => {
                    if (this.isEditMode) return;
                    e.preventDefault();
                    e.stopPropagation();
                    const current = parseFloat(card.dataset.currentTemp || tempValue.textContent) || 20;
                    const newTemp = current - 0.5;
                    tempValue.textContent = newTemp.toFixed(1);
                    card.dataset.currentTemp = newTemp;
                    this.setCapability(deviceId, 'target_temperature', newTemp);
                });

                tempUp.addEventListener('click', (e) => {
                    if (this.isEditMode) return;
                    e.preventDefault();
                    e.stopPropagation();
                    const current = parseFloat(card.dataset.currentTemp || tempValue.textContent) || 20;
                    const newTemp = current + 0.5;
                    tempValue.textContent = newTemp.toFixed(1);
                    card.dataset.currentTemp = newTemp;
                    this.setCapability(deviceId, 'target_temperature', newTemp);
                });
            }
        });
    }

    setupFlowCards() {
        this.flowCards.forEach(card => {
            card.addEventListener('click', (e) => {
                if (this.isEditMode) return;
                e.preventDefault();
                this.triggerFlow(card);
            });
        });
    }

    async toggleDevice(deviceId, toggleBtn, card) {
        const isCurrentlyOn = toggleBtn.classList.contains('is-on');
        const newValue = !isCurrentlyOn;
        const statusEl = card.querySelector('.device-status');
        const statusDot = card.querySelector('.status-dot');

        toggleBtn.classList.toggle('is-on', newValue);
        toggleBtn.setAttribute('aria-pressed', newValue ? 'true' : 'false');
        if (statusDot) statusDot.classList.toggle('is-on', newValue);
        if (statusEl) {
            const currentText = statusEl.textContent;
            const tempPart = currentText.includes('•') ? currentText.split('•')[1] : '';
            statusEl.textContent = (newValue ? 'On' : 'Off') + (tempPart ? ' •' + tempPart : '');
        }

        const success = await this.setCapability(deviceId, 'onoff', newValue);
        if (!success) {
            toggleBtn.classList.toggle('is-on', !newValue);
            toggleBtn.setAttribute('aria-pressed', (!newValue) ? 'true' : 'false');
            if (statusDot) statusDot.classList.toggle('is-on', !newValue);
            this.showToast('Failed to control device');
        }
    }

    async setCapability(deviceId, capability, value) {
        try {
            const response = await axios.post(`/api/v1/devices/${deviceId}/control`, { capability, value });
            return response.data.success;
        } catch (error) {
            console.error('Failed to set capability:', error);
            return false;
        }
    }

    async triggerFlow(card) {
        const flowId = card.dataset.flowId;
        const statusEl = card.querySelector('.flow-status');
        const originalText = statusEl ? statusEl.textContent : '';

        if (statusEl) statusEl.textContent = 'Running...';
        card.classList.add('animate-pulse', 'opacity-75');

        try {
            const response = await axios.post(`/api/v1/flows/${flowId}/trigger`);
            if (response.data.success) {
                if (statusEl) statusEl.textContent = 'Done!';
                this.showToast('Flow triggered successfully');
            } else {
                if (statusEl) statusEl.textContent = 'Failed';
                this.showToast('Failed to trigger flow');
            }
        } catch (error) {
            console.error('Failed to trigger flow:', error);
            if (statusEl) statusEl.textContent = 'Error';
            this.showToast('Error triggering flow');
        } finally {
            card.classList.remove('animate-pulse', 'opacity-75');
            setTimeout(() => {
                if (statusEl) statusEl.textContent = originalText || 'Tap to run';
            }, 2000);
        }
    }

    startPolling() {
        if (this.pollTimer) return;
        this.pollTimer = setInterval(() => {
            if (!this.isEditMode) this.loadInitialStates();
        }, this.pollInterval);
    }

    stopPolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    }

    // ==================== MULTI-SWITCH CARD FUNCTIONALITY ====================

    setupMultiSwitchCards() {
        // Initialize multi-switch card controllers
        const multiSwitchCards = document.querySelectorAll('.multi-switch-card');
        multiSwitchCards.forEach(card => {
            this.initMultiSwitchCard(card);
        });

        // Setup group remove buttons
        document.querySelectorAll('.remove-group-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.removeGroup(btn.dataset.groupId);
            });
        });

        // Setup group configure buttons
        document.querySelectorAll('.configure-group-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.openGroupConfigureModal(btn.dataset.groupId);
            });
        });
    }

    initMultiSwitchCard(card) {
        // Setup sliders
        const sliders = card.querySelectorAll('.multi-switch-range');
        sliders.forEach(slider => {
            const deviceId = slider.dataset.deviceId;
            const row = slider.closest('.multi-switch-row');
            
            slider.addEventListener('input', (e) => {
                if (this.isEditMode) return;
                const value = parseInt(e.target.value);
                this.updateMultiSwitchSliderVisuals(row, value);
            });
            
            let debounceTimer;
            slider.addEventListener('change', (e) => {
                if (this.isEditMode) return;
                const value = parseInt(e.target.value);
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    this.setCapability(deviceId, 'dim', value / 100);
                }, 300);
            });
        });

        // Setup toggles
        const toggles = card.querySelectorAll('.multi-switch-toggle');
        toggles.forEach(toggle => {
            toggle.addEventListener('click', () => {
                if (this.isEditMode) return;
                const deviceId = toggle.dataset.deviceId;
                const row = toggle.closest('.multi-switch-row');
                const isOn = toggle.classList.contains('is-on');
                const newValue = !isOn;
                
                toggle.classList.toggle('is-on', newValue);
                toggle.setAttribute('aria-pressed', newValue ? 'true' : 'false');
                
                const powerBtn = row.querySelector('.multi-switch-power-btn');
                if (powerBtn) {
                    powerBtn.classList.toggle('is-on', newValue);
                    powerBtn.setAttribute('aria-pressed', newValue ? 'true' : 'false');
                }
                
                this.setCapability(deviceId, 'onoff', newValue);
            });
        });

        // Setup power buttons
        const powerBtns = card.querySelectorAll('.multi-switch-power-btn');
        powerBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (this.isEditMode) return;
                const deviceId = btn.dataset.deviceId;
                const row = btn.closest('.multi-switch-row');
                const isOn = btn.classList.contains('is-on');
                const newValue = !isOn;
                
                btn.classList.toggle('is-on', newValue);
                btn.setAttribute('aria-pressed', newValue ? 'true' : 'false');
                
                const toggle = row.querySelector('.multi-switch-toggle');
                const slider = row.querySelector('.multi-switch-range');
                const thumb = row.querySelector('.multi-switch-slider-thumb');
                
                if (toggle) {
                    toggle.classList.toggle('is-on', newValue);
                    toggle.setAttribute('aria-pressed', newValue ? 'true' : 'false');
                }
                
                if (slider) {
                    const newDimValue = newValue ? 100 : 0;
                    slider.value = newDimValue;
                    this.updateMultiSwitchSliderVisuals(row, newDimValue);
                    this.setCapability(deviceId, 'dim', newDimValue / 100);
                }
                
                if (thumb) {
                    thumb.classList.toggle('is-on', newValue);
                }
                
                this.setCapability(deviceId, 'onoff', newValue);
            });
        });
    }

    updateMultiSwitchSliderVisuals(row, value) {
        const fill = row.querySelector('.multi-switch-slider-fill');
        const thumb = row.querySelector('.multi-switch-slider-thumb');
        const valueDisplay = row.querySelector('.multi-switch-value');
        const powerBtn = row.querySelector('.multi-switch-power-btn');
        
        if (fill) fill.style.width = `${value}%`;
        if (thumb) {
            const position = Math.max(0, Math.min(value - 5, 95));
            thumb.style.left = `calc(${position}% - 0px)`;
            thumb.classList.toggle('is-on', value > 0);
        }
        if (valueDisplay) valueDisplay.textContent = `${value}%`;
        if (powerBtn) {
            powerBtn.classList.toggle('is-on', value > 0);
            powerBtn.setAttribute('aria-pressed', value > 0 ? 'true' : 'false');
        }
    }

    // ==================== DEVICE GROUP MODAL ====================

    setupDeviceGroupModal() {
        const createBtn = document.getElementById('create-group-btn');
        const modal = document.getElementById('group-modal');
        const closeBtn = document.getElementById('close-group-modal');
        const cancelBtn = document.getElementById('group-cancel');
        const saveBtn = document.getElementById('group-save');
        const searchInput = document.getElementById('group-device-search');
        const selectAllBtn = document.getElementById('group-select-all');

        if (createBtn) {
            createBtn.addEventListener('click', () => this.openGroupModal());
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeGroupModal());
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.closeGroupModal());
        }

        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveGroup());
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) this.closeGroupModal();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterGroupDevices(e.target.value);
            });
        }

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', () => this.toggleSelectAllGroupDevices());
        }
    }

    async openGroupModal(groupId = null) {
        const modal = document.getElementById('group-modal');
        const title = document.getElementById('group-modal-title');
        const nameInput = document.getElementById('group-name');
        const saveText = document.getElementById('group-save-text');
        const devicesList = document.getElementById('group-devices-list');

        this.currentConfigureGroupId = groupId;

        if (groupId) {
            title.textContent = 'Edit Device Group';
            saveText.textContent = 'Save Changes';
        } else {
            title.textContent = 'Create Device Group';
            saveText.textContent = 'Create Group';
            nameInput.value = '';
        }

        // Load all devices
        devicesList.innerHTML = '<p class="text-gray-500 text-sm py-2 text-center">Loading devices...</p>';
        
        try {
            const response = await axios.get('/api/v1/devices');
            
            // Homey API returns devices as an object keyed by device ID
            // Convert to array format
            let devices = [];
            const data = response.data;
            
            if (data && typeof data === 'object' && !Array.isArray(data)) {
                // It's an object - convert to array
                devices = Object.entries(data).map(([id, device]) => ({
                    id,
                    ...device
                }));
            } else if (Array.isArray(data)) {
                devices = data;
            } else if (data && data.devices) {
                devices = Array.isArray(data.devices) ? data.devices : Object.entries(data.devices).map(([id, device]) => ({ id, ...device }));
            }
            
            // Filter to only show devices with onoff or dim capabilities
            this.allDevicesForGroup = devices.filter(d => {
                const caps = d.capabilities || d.capabilitiesObj || {};
                // Check if capabilities is an array (Homey format) or object
                if (Array.isArray(caps)) {
                    return caps.includes('onoff') || caps.includes('dim');
                }
                return caps.onoff !== undefined || caps.dim !== undefined;
            });

            let selectedDeviceIds = [];
            
            // If editing, load existing group data
            if (groupId) {
                const uuid = this.gridElement.dataset.dashboardUuid;
                const groupResponse = await axios.get(`/api/v1/dashboards/${uuid}/groups/${groupId}`);
                const group = groupResponse.data;
                nameInput.value = group.name;
                selectedDeviceIds = group.device_ids || [];
            }

            this.renderGroupDevicesList(this.allDevicesForGroup, selectedDeviceIds);
            
        } catch (error) {
            console.error('Failed to load devices:', error);
            devicesList.innerHTML = '<p class="text-red-400 text-sm py-2 text-center">Failed to load devices</p>';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        nameInput.focus();
    }

    renderGroupDevicesList(devices, selectedIds = []) {
        const devicesList = document.getElementById('group-devices-list');
        
        if (devices.length === 0) {
            devicesList.innerHTML = '<p class="text-gray-500 text-sm py-2 text-center">No compatible devices found</p>';
            return;
        }

        devicesList.innerHTML = devices.map(d => {
            const caps = d.capabilities || d.capabilitiesObj || {};
            // Check if capabilities is an array (Homey format) or object
            let hasDim = false;
            if (Array.isArray(caps)) {
                hasDim = caps.includes('dim');
            } else {
                hasDim = caps.dim !== undefined;
            }
            const deviceType = hasDim ? 'dimmer' : 'switch';
            const checked = selectedIds.includes(d.id) ? 'checked' : '';
            
            return `
                <label class="group-device-item flex items-center gap-3 p-2 bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-600/50 transition-colors"
                       data-device-id="${d.id}" data-device-name="${this.escapeHtml(d.name).toLowerCase()}">
                    <input type="checkbox" name="group_device" value="${d.id}" ${checked}
                           class="rounded border-gray-500 text-amber-500 focus:ring-amber-500">
                    <span class="flex-1 truncate">${this.escapeHtml(d.name)}</span>
                    <span class="text-xs px-2 py-0.5 rounded ${hasDim ? 'bg-amber-500/20 text-amber-400' : 'bg-blue-500/20 text-blue-400'}">${deviceType}</span>
                </label>
            `;
        }).join('');

        // Update selected count
        this.updateGroupSelectedCount();

        // Add change listeners
        devicesList.querySelectorAll('input[name="group_device"]').forEach(cb => {
            cb.addEventListener('change', () => this.updateGroupSelectedCount());
        });
    }

    filterGroupDevices(query) {
        const items = document.querySelectorAll('.group-device-item');
        const searchLower = query.toLowerCase();
        
        items.forEach(item => {
            const name = item.dataset.deviceName || '';
            const matches = name.includes(searchLower);
            item.style.display = matches ? '' : 'none';
        });
    }

    updateGroupSelectedCount() {
        const countEl = document.getElementById('group-selected-count');
        const checkboxes = document.querySelectorAll('input[name="group_device"]:checked');
        if (countEl) {
            countEl.textContent = `${checkboxes.length} device${checkboxes.length !== 1 ? 's' : ''} selected`;
        }
    }

    toggleSelectAllGroupDevices() {
        const checkboxes = document.querySelectorAll('input[name="group_device"]');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        
        checkboxes.forEach(cb => {
            if (cb.closest('.group-device-item').style.display !== 'none') {
                cb.checked = !allChecked;
            }
        });
        
        this.updateGroupSelectedCount();
    }

    closeGroupModal() {
        const modal = document.getElementById('group-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        this.currentConfigureGroupId = null;
        
        // Clear search
        const searchInput = document.getElementById('group-device-search');
        if (searchInput) searchInput.value = '';
    }

    async saveGroup() {
        const uuid = this.gridElement.dataset.dashboardUuid;
        const name = document.getElementById('group-name').value.trim();
        const selectedDevices = Array.from(document.querySelectorAll('input[name="group_device"]:checked'))
            .map(cb => cb.value);

        if (!name) {
            this.showToast('Please enter a group name');
            return;
        }

        if (selectedDevices.length === 0) {
            this.showToast('Please select at least one device');
            return;
        }

        try {
            let response;
            if (this.currentConfigureGroupId) {
                // Update existing group
                response = await axios.put(`/api/v1/dashboards/${uuid}/groups/${this.currentConfigureGroupId}`, {
                    name,
                    device_ids: selectedDevices
                });
            } else {
                // Create new group
                response = await axios.post(`/api/v1/dashboards/${uuid}/groups`, {
                    name,
                    device_ids: selectedDevices
                });
            }

            if (response.data.success) {
                this.closeGroupModal();
                this.showToast(this.currentConfigureGroupId ? 'Group updated!' : 'Group created!');
                
                // Reload page to show new group
                window.location.reload();
            }
        } catch (error) {
            console.error('Failed to save group:', error);
            this.showToast('Failed to save group');
        }
    }

    async openGroupConfigureModal(groupId) {
        await this.openGroupModal(groupId);
    }

    async removeGroup(groupId) {
        if (!confirm('Remove this device group from the dashboard?')) {
            return;
        }

        const uuid = this.gridElement.dataset.dashboardUuid;
        const gridItem = document.querySelector(`[data-group-id="${groupId}"]`);

        try {
            const response = await axios.delete(`/api/v1/dashboards/${uuid}/groups/${groupId}`);

            if (response.data.success) {
                if (gridItem) {
                    this.grid.removeWidget(gridItem);
                }
                this.showToast('Group removed');
            }
        } catch (error) {
            console.error('Failed to remove group:', error);
            this.showToast('Failed to remove group');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('dashboard-grid')) {
        window.dashboardController = new DashboardController();
    }
});
