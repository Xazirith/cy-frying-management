// Debug Panel JavaScript
class DebugPanel {
    constructor() {
        this.isVisible = true;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInitialData();
    }

    bindEvents() {
        // Tab switching
        document.querySelectorAll('.debug-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });

        // Toggle panel
        document.getElementById('debug-toggle').addEventListener('click', () => {
            this.togglePanel();
        });

        // Clear storage
        document.getElementById('debug-clear').addEventListener('click', () => {
            localStorage.removeItem('debug-panel-state');
            location.reload();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                this.togglePanel();
            }
        });
    }

    switchTab(tabName) {
        // Update tabs
        document.querySelectorAll('.debug-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

        // Update content
        document.querySelectorAll('.debug-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`tab-${tabName}`).classList.add('active');

        // Load tab-specific data
        this.loadTabData(tabName);
    }

    togglePanel() {
        const panel = document.getElementById('debug-panel');
        const toggleBtn = document.getElementById('debug-toggle');
        
        this.isVisible = !this.isVisible;
        
        if (this.isVisible) {
            panel.classList.remove('collapsed');
            toggleBtn.textContent = '−';
        } else {
            panel.classList.add('collapsed');
            toggleBtn.textContent = '+';
        }
    }

    loadInitialData() {
        // Load any saved state
        const savedState = localStorage.getItem('debug-panel-state');
        if (savedState) {
            const state = JSON.parse(savedState);
            if (state.activeTab) {
                this.switchTab(state.activeTab);
            }
        }

        // Load performance data
        this.loadPerformanceData();
    }

    loadTabData(tabName) {
        switch(tabName) {
            case 'logs':
                this.loadLogs();
                break;
            case 'performance':
                this.loadPerformanceData();
                break;
        }
    }

    async loadLogs() {
        try {
            const response = await fetch('/api/debug/logs?limit=50');
            const logs = await response.json();
            
            const logContent = document.getElementById('log-content');
            logContent.innerHTML = logs.map(log => `
                <div class="log-entry ${log.level.toLowerCase()}">
                    <strong>[${log.timestamp}]</strong> [${log.level}] ${log.message}
                    ${log.context ? `<br><small>${JSON.stringify(log.context)}</small>` : ''}
                </div>
            `).join('');
        } catch (error) {
            console.error('Failed to load logs:', error);
        }
    }

    async loadPerformanceData() {
        try {
            const response = await fetch('/api/debug/performance');
            const data = await response.json();
            
            this.renderPerformanceChart(data);
        } catch (error) {
            console.error('Failed to load performance data:', error);
        }
    }

    renderPerformanceChart(data) {
        const chart = document.getElementById('performance-chart');
        chart.innerHTML = `
            <div class="performance-metrics">
                ${data.metrics ? data.metrics.map(metric => `
                    <div class="metric">
                        <label>${metric.name}:</label>
                        <div class="performance-bar" style="width: ${Math.min(metric.value, 100)}%"></div>
                        <span>${metric.value}ms</span>
                    </div>
                `).join('') : 'No performance data available'}
            </div>
        `;
    }

    // Utility method to add custom debug messages
    static log(level, message, context = {}) {
        if (!AppConfig.debugEnabled) return;
        
        const timestamp = new Date().toISOString();
        const logEntry = {
            timestamp,
            level: level.toUpperCase(),
            message,
            context
        };
        
        // Send to server log
        fetch('/api/debug/log', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(logEntry)
        }).catch(console.error);
        
        // Also log to console
        console[level](`[DEBUG] ${message}`, context);
    }
}

// Initialize debug panel when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (AppConfig.debugEnabled) {
        window.debugPanel = new DebugPanel();
        
        // Add global debug helper
        window.debug = {
            log: (msg, ctx) => DebugPanel.log('info', msg, ctx),
            error: (msg, ctx) => DebugPanel.log('error', msg, ctx),
            warn: (msg, ctx) => DebugPanel.log('warn', msg, ctx),
            performance: (name, fn) => {
                const start = performance.now();
                const result = fn();
                const duration = performance.now() - start;
                DebugPanel.log('info', `Performance: ${name}`, { duration: Math.round(duration) });
                return result;
            }
        };
        
        // Log page load performance
        debug.log('Page loaded', {
            renderTime: AppConfig.renderTimeMs,
            memory: performance.memory ? {
                used: Math.round(performance.memory.usedJSHeapSize / 1048576),
                total: Math.round(performance.memory.totalJSHeapSize / 1048576)
            } : 'N/A'
        });
    }
});

// Performance monitoring
const originalFetch = window.fetch;
window.fetch = function(...args) {
    const start = performance.now();
    return originalFetch.apply(this, args).then(response => {
        const duration = performance.now() - start;
        
        if (AppConfig.debugEnabled) {
            DebugPanel.log('info', `API Call: ${args[0]}`, {
                method: args[1]?.method || 'GET',
                status: response.status,
                duration: Math.round(duration),
                size: response.headers.get('content-length')
            });
        }
        
        return response;
    });
};
