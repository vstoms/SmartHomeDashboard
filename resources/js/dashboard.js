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

        if (this.gridElement) {
            this.init();
        }
    }

    init() {
        this.initGridStack();
        this.setupDeviceCards();
        this.setupFlowCards();
        this.setupEditMode();
        this.setupAddItemsPanel();
        this.setupRemoveButtons();
        this.setupConfigureButtons();
        this.setupConfigureModal();
        this.loadInitialStates();
        this.startPolling();
        this.currentConfigureItemId = null;
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
                editToggle.classList.add('bg-blue-600', 'hover:bg-blue-700');
                editToggle.classList.remove('bg-gray-700', 'hover:bg-gray-600');
                editControls.classList.remove('translate-y-full');
            } else {
                this.exitEditMode(false);
                editToggleText.textContent = 'Edit Layout';
                editToggle.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                editToggle.classList.add('bg-gray-700', 'hover:bg-gray-600');
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
                editToggle.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                editToggle.classList.add('bg-gray-700', 'hover:bg-gray-600');
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
        if (panel) {
            panel.classList.add('translate-x-full');
        }
        if (mainContent) {
            mainContent.style.marginRight = '';
        }
    }

    async loadAvailableItems() {
        const uuid = this.gridElement.dataset.dashboardUuid;
        const devicesContainer = document.getElementById('available-devices');
        const flowsContainer = document.getElementById('available-flows');
        const devicesCount = document.getElementById('devices-count');
        const flowsCount = document.getElementById('flows-count');

        try {
            const response = await axios.get(`/api/v1/dashboards/${uuid}/available-items`);
            const { devices, flows } = response.data;

            // Update counts
            if (devicesCount) devicesCount.textContent = devices.length;
            if (flowsCount) flowsCount.textContent = flows.length;

            // Render devices
            if (devicesContainer) {
                if (devices.length === 0) {
                    devicesContainer.innerHTML = '<p class="text-gray-500 text-sm py-2 text-center">All devices added!</p>';
                } else {
                    devicesContainer.innerHTML = devices.map(d => `
                        <button class="add-item-btn w-full text-left p-2 hover:bg-gray-700 rounded-lg flex items-center gap-2 transition-colors"
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
                        <button class="add-item-btn w-full text-left p-2 hover:bg-gray-700 rounded-lg flex items-center gap-2 transition-colors"
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
            content.innerHTML = `<div class="device-card bg-gray-800 rounded-2xl p-4 flex flex-col overflow-hidden transition-all duration-200 hover:bg-gray-750 active:scale-95"
                     data-device-id="${item.homey_id}"
                     data-item-id="${item.id}"
                     data-display-capabilities="[]">
                    <div class="flex justify-between items-start gap-2 flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-base truncate">${this.escapeHtml(item.name)}</h3>
                            <span class="device-status text-sm text-gray-400">--</span>
                        </div>
                        <button class="toggle-btn w-14 h-8 rounded-full bg-gray-600 relative transition-colors flex-shrink-0 touch-manipulation"
                                data-capability="onoff"
                                aria-label="Toggle device">
                            <span class="toggle-indicator absolute top-1 left-1 w-6 h-6 bg-white rounded-full transition-transform duration-200 shadow-md"></span>
                        </button>
                    </div>
                    <div class="flex-1"></div>
                </div>`;
        } else {
            content.innerHTML = `<div class="flow-card bg-purple-900/50 rounded-2xl p-4 flex flex-col items-center justify-center text-center cursor-pointer transition-all duration-200 hover:bg-purple-800/50 active:scale-95"
                     data-flow-id="${item.homey_id}">
                    <svg class="w-8 h-8 text-purple-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <h3 class="font-semibold text-sm">${this.escapeHtml(item.name)}</h3>
                    <span class="flow-status text-xs text-gray-400 mt-1">Tap to run</span>
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
            editToggle.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            editToggle.classList.add('bg-gray-700', 'hover:bg-gray-600');
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
                const indicator = card.querySelector('.toggle-indicator');

                if (toggleBtn && indicator) {
                    toggleBtn.classList.toggle('bg-blue-500', isOn);
                    toggleBtn.classList.toggle('bg-gray-600', !isOn);
                    indicator.style.transform = isOn ? 'translateX(24px)' : 'translateX(0)';
                }

                if (statusEl) {
                    statusEl.textContent = isOn ? 'On' : 'Off';
                }
            }

            if (capabilities.dim !== undefined) {
                const dimSlider = card.querySelector('[data-capability="dim"]');
                const dimValue = card.querySelector('.dimmer-value');

                const percent = Math.round(capabilities.dim.value * 100);
                if (dimSlider) dimSlider.value = percent;
                if (dimValue) dimValue.textContent = `${percent}%`;
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
                    if (dimValue) dimValue.textContent = `${e.target.value}%`;

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
        const isCurrentlyOn = toggleBtn.classList.contains('bg-blue-500');
        const newValue = !isCurrentlyOn;
        const indicator = toggleBtn.querySelector('.toggle-indicator');
        const statusEl = card.querySelector('.device-status');

        toggleBtn.classList.toggle('bg-blue-500', newValue);
        toggleBtn.classList.toggle('bg-gray-600', !newValue);
        if (indicator) indicator.style.transform = newValue ? 'translateX(24px)' : 'translateX(0)';
        if (statusEl) {
            const currentText = statusEl.textContent;
            const tempPart = currentText.includes('•') ? currentText.split('•')[1] : '';
            statusEl.textContent = (newValue ? 'On' : 'Off') + (tempPart ? ' •' + tempPart : '');
        }

        const success = await this.setCapability(deviceId, 'onoff', newValue);
        if (!success) {
            toggleBtn.classList.toggle('bg-blue-500', !newValue);
            toggleBtn.classList.toggle('bg-gray-600', newValue);
            if (indicator) indicator.style.transform = !newValue ? 'translateX(24px)' : 'translateX(0)';
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
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('dashboard-grid')) {
        window.dashboardController = new DashboardController();
    }
});
