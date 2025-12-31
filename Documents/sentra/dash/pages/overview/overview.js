// API Configuration
const API_BASE = window.location.origin;

// Load live system data
async function loadSystemData() {
    try {
        const response = await fetch(`${API_BASE}/api/core/status`);
        const data = await response.json();
        
        if (data.ok) {
            // Update module count
            const moduleCountEl = document.getElementById('module-count');
            if (moduleCountEl) {
                moduleCountEl.textContent = data.module_count || 0;
            }
            
            const moduleInfoEl = document.getElementById('module-info');
            if (moduleInfoEl && data.modules) {
                const simpleNames = data.modules.map(m => m.replace('app.modules.', ''));
                moduleInfoEl.textContent = simpleNames.slice(0, 3).join(', ') + '...';
            }
            
            // Update uptime
            const uptimeEl = document.getElementById('uptime-info');
            const statusEl = document.getElementById('system-status');
            if (data.uptime_seconds !== undefined) {
                if (uptimeEl) uptimeEl.textContent = `Uptime: ${formatUptime(data.uptime_seconds)}`;
                if (statusEl) statusEl.textContent = '✓ Online';
            }
            
            // Update version info
            const versionEl = document.getElementById('version-number');
            const gitInfoEl = document.getElementById('git-info');
            if (versionEl && data.version) {
                versionEl.textContent = `v${data.version}`;
            }
            if (gitInfoEl) {
                if (data.git_commit !== 'unknown') {
                    gitInfoEl.textContent = `${data.git_branch || 'main'} @ ${data.git_commit}`;
                } else {
                    gitInfoEl.textContent = 'Local development';
                }
            }
            
            // Update health status
            const healthStatusEl = document.getElementById('health-status');
            const healthInfoEl = document.getElementById('health-info');
            if (healthStatusEl) healthStatusEl.textContent = '✓ Healthy';
            if (healthInfoEl) {
                healthInfoEl.textContent = 'All systems operational';
                healthInfoEl.className = 'kpi-change positive';
            }
            
            // Update modules list
            const modulesListEl = document.getElementById('modules-list');
            if (modulesListEl && data.modules) {
                modulesListEl.innerHTML = data.modules.map(m => `
                    <div style="padding: 0.5rem 0.75rem; background: rgba(0, 255, 65, 0.05); border: 1px solid rgba(0, 255, 65, 0.15); border-radius: 6px; font-size: 0.85rem;">
                        ${m.replace('app.modules.', '')}
                    </div>
                `).join('');
            }
        }
    } catch (err) {
        console.error('Failed to load system data:', err);
        
        // Update UI to show offline status
        const statusEl = document.getElementById('system-status');
        const healthStatusEl = document.getElementById('health-status');
        const healthInfoEl = document.getElementById('health-info');
        
        if (statusEl) statusEl.textContent = '⚠ Offline';
        if (healthStatusEl) healthStatusEl.textContent = '⚠ Error';
        if (healthInfoEl) {
            healthInfoEl.textContent = 'Unable to connect';
            healthInfoEl.className = 'kpi-change negative';
        }
    }
}

function formatUptime(seconds) {
    if (seconds < 60) return `${seconds}s`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ${Math.floor((seconds % 3600) / 60)}m`;
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    return `${days}d ${hours}h`;
}

// Initialize on page load
loadSystemData();
setInterval(loadSystemData, 30000);
