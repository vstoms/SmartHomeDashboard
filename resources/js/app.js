import './bootstrap';
import { initMultiSwitchCards } from './multi-switch-card';

// Initialize multi-switch cards when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize all multi-switch cards on the page
    const controllers = initMultiSwitchCards({
        debounceMs: 300,
        onDeviceChange: (deviceId, capability, value) => {
            console.log(`Device ${deviceId}: ${capability} = ${value}`);
        }
    });
    
    // Store controllers globally for debugging/access
    window.multiSwitchControllers = controllers;
});
