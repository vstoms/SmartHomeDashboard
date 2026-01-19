/**
 * Multi-Switch Card Controller
 * Handles interactions for the multi-switch card component
 * 
 * Features:
 * - Toggle switches (on/off)
 * - Dimmer sliders with percentage display
 * - Power buttons for each device
 * - API integration for device control
 */

export class MultiSwitchCardController {
    constructor(cardElement, options = {}) {
        this.card = cardElement;
        this.options = {
            debounceMs: 300,
            onDeviceChange: null,
            apiEndpoint: '/api/v1/devices',
            ...options
        };
        
        this.debounceTimers = {};
        this.init();
    }
    
    init() {
        this.setupSliders();
        this.setupToggles();
        this.setupPowerButtons();
    }
    
    /**
     * Setup dimmer slider interactions
     */
    setupSliders() {
        const sliders = this.card.querySelectorAll('.multi-switch-range');
        
        sliders.forEach(slider => {
            const deviceId = slider.dataset.deviceId;
            const row = slider.closest('.multi-switch-row');
            const track = row.querySelector('.multi-switch-slider-track');
            const fill = row.querySelector('.multi-switch-slider-fill');
            const thumb = row.querySelector('.multi-switch-slider-thumb');
            const valueDisplay = row.querySelector('.multi-switch-value');
            const powerBtn = row.querySelector('.multi-switch-power-btn');
            
            // Input event for real-time visual feedback
            slider.addEventListener('input', (e) => {
                const value = parseInt(e.target.value);
                this.updateSliderVisuals(fill, thumb, valueDisplay, value);
                
                // Update power button state based on value
                const isOn = value > 0;
                if (powerBtn) {
                    powerBtn.classList.toggle('is-on', isOn);
                    powerBtn.setAttribute('aria-pressed', isOn ? 'true' : 'false');
                }
                if (thumb) {
                    thumb.classList.toggle('is-on', isOn);
                }
            });
            
            // Change event for API call (debounced)
            slider.addEventListener('change', (e) => {
                const value = parseInt(e.target.value);
                this.debouncedSetValue(deviceId, 'dim', value / 100);
            });
        });
    }
    
    /**
     * Update slider visual elements
     */
    updateSliderVisuals(fill, thumb, valueDisplay, value) {
        if (fill) {
            fill.style.width = `${value}%`;
        }
        if (thumb) {
            // Position thumb, keeping it within bounds
            const position = Math.max(0, Math.min(value - 5, 95));
            thumb.style.left = `calc(${position}% - 0px)`;
        }
        if (valueDisplay) {
            valueDisplay.textContent = `${value}%`;
        }
    }
    
    /**
     * Setup simple toggle switches
     */
    setupToggles() {
        const toggles = this.card.querySelectorAll('.multi-switch-toggle');
        
        toggles.forEach(toggle => {
            const deviceId = toggle.dataset.deviceId;
            const row = toggle.closest('.multi-switch-row');
            const powerBtn = row.querySelector('.multi-switch-power-btn');
            
            toggle.addEventListener('click', () => {
                const isCurrentlyOn = toggle.classList.contains('is-on');
                const newValue = !isCurrentlyOn;
                
                // Update toggle state
                toggle.classList.toggle('is-on', newValue);
                toggle.setAttribute('aria-pressed', newValue ? 'true' : 'false');
                
                // Sync power button
                if (powerBtn) {
                    powerBtn.classList.toggle('is-on', newValue);
                    powerBtn.setAttribute('aria-pressed', newValue ? 'true' : 'false');
                }
                
                // Send to API
                this.setDeviceValue(deviceId, 'onoff', newValue);
            });
        });
    }
    
    /**
     * Setup power buttons
     */
    setupPowerButtons() {
        const powerBtns = this.card.querySelectorAll('.multi-switch-power-btn');
        
        powerBtns.forEach(btn => {
            const deviceId = btn.dataset.deviceId;
            const row = btn.closest('.multi-switch-row');
            const deviceType = row.dataset.deviceType;
            const toggle = row.querySelector('.multi-switch-toggle');
            const slider = row.querySelector('.multi-switch-range');
            const thumb = row.querySelector('.multi-switch-slider-thumb');
            
            btn.addEventListener('click', () => {
                const isCurrentlyOn = btn.classList.contains('is-on');
                const newValue = !isCurrentlyOn;
                
                // Update power button state
                btn.classList.toggle('is-on', newValue);
                btn.setAttribute('aria-pressed', newValue ? 'true' : 'false');
                
                // Sync with toggle or slider
                if (deviceType === 'dimmer' && slider) {
                    // For dimmers, set to 100% when turning on, 0% when turning off
                    const newDimValue = newValue ? 100 : 0;
                    slider.value = newDimValue;
                    
                    const fill = row.querySelector('.multi-switch-slider-fill');
                    const valueDisplay = row.querySelector('.multi-switch-value');
                    this.updateSliderVisuals(fill, thumb, valueDisplay, newDimValue);
                    
                    if (thumb) {
                        thumb.classList.toggle('is-on', newValue);
                    }
                    
                    // Send dim value to API
                    this.setDeviceValue(deviceId, 'dim', newDimValue / 100);
                } else if (toggle) {
                    // Sync toggle switch
                    toggle.classList.toggle('is-on', newValue);
                    toggle.setAttribute('aria-pressed', newValue ? 'true' : 'false');
                }
                
                // Send on/off to API
                this.setDeviceValue(deviceId, 'onoff', newValue);
            });
        });
    }
    
    /**
     * Debounced value setter for sliders
     */
    debouncedSetValue(deviceId, capability, value) {
        const key = `${deviceId}-${capability}`;
        
        if (this.debounceTimers[key]) {
            clearTimeout(this.debounceTimers[key]);
        }
        
        this.debounceTimers[key] = setTimeout(() => {
            this.setDeviceValue(deviceId, capability, value);
            delete this.debounceTimers[key];
        }, this.options.debounceMs);
    }
    
    /**
     * Send device value to API
     */
    async setDeviceValue(deviceId, capability, value) {
        try {
            // Call custom handler if provided
            if (this.options.onDeviceChange) {
                this.options.onDeviceChange(deviceId, capability, value);
            }
            
            // Make API call
            const response = await fetch(`${this.options.apiEndpoint}/${deviceId}/control`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ capability, value })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                console.error('Failed to set device value:', data);
                this.revertState(deviceId, capability);
            }
            
            return data.success;
        } catch (error) {
            console.error('Error setting device value:', error);
            this.revertState(deviceId, capability);
            return false;
        }
    }
    
    /**
     * Revert state on error (basic implementation)
     */
    revertState(deviceId, capability) {
        // This could be enhanced to store previous state and revert
        console.warn(`Failed to update ${capability} for device ${deviceId}`);
    }
    
    /**
     * Update device state from external source (e.g., polling)
     */
    updateDeviceState(deviceId, capabilities) {
        const row = this.card.querySelector(`[data-device-id="${deviceId}"]`);
        if (!row) return;
        
        const powerBtn = row.querySelector('.multi-switch-power-btn');
        const toggle = row.querySelector('.multi-switch-toggle');
        const slider = row.querySelector('.multi-switch-range');
        const thumb = row.querySelector('.multi-switch-slider-thumb');
        
        // Update on/off state
        if (capabilities.onoff !== undefined) {
            const isOn = capabilities.onoff.value;
            
            if (powerBtn) {
                powerBtn.classList.toggle('is-on', isOn);
                powerBtn.setAttribute('aria-pressed', isOn ? 'true' : 'false');
            }
            
            if (toggle) {
                toggle.classList.toggle('is-on', isOn);
                toggle.setAttribute('aria-pressed', isOn ? 'true' : 'false');
            }
            
            if (thumb) {
                thumb.classList.toggle('is-on', isOn);
            }
        }
        
        // Update dim value
        if (capabilities.dim !== undefined && slider) {
            const value = Math.round(capabilities.dim.value * 100);
            slider.value = value;
            
            const fill = row.querySelector('.multi-switch-slider-fill');
            const valueDisplay = row.querySelector('.multi-switch-value');
            this.updateSliderVisuals(fill, thumb, valueDisplay, value);
        }
    }
    
    /**
     * Get all device IDs in this card
     */
    getDeviceIds() {
        const rows = this.card.querySelectorAll('.multi-switch-row');
        return Array.from(rows).map(row => row.dataset.deviceId);
    }
    
    /**
     * Destroy the controller and clean up
     */
    destroy() {
        // Clear all debounce timers
        Object.values(this.debounceTimers).forEach(timer => clearTimeout(timer));
        this.debounceTimers = {};
    }
}

/**
 * Initialize all multi-switch cards on the page
 */
export function initMultiSwitchCards(options = {}) {
    const cards = document.querySelectorAll('.multi-switch-card');
    const controllers = [];
    
    cards.forEach(card => {
        const controller = new MultiSwitchCardController(card, options);
        controllers.push(controller);
        
        // Store reference on element for external access
        card._multiSwitchController = controller;
    });
    
    return controllers;
}

/**
 * Get controller for a specific card element
 */
export function getMultiSwitchController(cardElement) {
    return cardElement._multiSwitchController || null;
}

// Auto-initialize if not using as module
if (typeof window !== 'undefined') {
    window.MultiSwitchCardController = MultiSwitchCardController;
    window.initMultiSwitchCards = initMultiSwitchCards;
    window.getMultiSwitchController = getMultiSwitchController;
}
