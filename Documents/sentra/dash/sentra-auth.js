/**
 * Sentra Auth Client Library
 * Provides authentication utilities for Sentra web applications
 */

const SentraAuth = (function() {
    'use strict';

    const API_BASE = '';
    const STORAGE_KEY = 'sentra_auth';
    const REMEMBER_KEY = 'sentra_remember';

    // Get stored authentication data
    function getAuthData() {
        const remembered = localStorage.getItem(REMEMBER_KEY) === 'true';
        const storage = remembered ? localStorage : sessionStorage;
        const data = storage.getItem(STORAGE_KEY);
        
        if (!data) return null;
        
        try {
            return JSON.parse(data);
        } catch (e) {
            clearAuthData();
            return null;
        }
    }

    // Store authentication data
    function setAuthData(authPacket, remember = false) {
        const data = JSON.stringify(authPacket);
        
        if (remember) {
            localStorage.setItem(STORAGE_KEY, data);
            localStorage.setItem(REMEMBER_KEY, 'true');
        } else {
            sessionStorage.setItem(STORAGE_KEY, data);
            localStorage.removeItem(REMEMBER_KEY);
        }
    }

    // Clear authentication data
    function clearAuthData() {
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem(REMEMBER_KEY);
        sessionStorage.removeItem(STORAGE_KEY);
    }

    // Check if user is authenticated
    function isAuthenticated() {
        const auth = getAuthData();
        if (!auth || !auth.access_token) return false;
        
        // Check if token is expired
        const now = Math.floor(Date.now() / 1000);
        if (auth.expires_at <= now) {
            // Try to refresh
            return false;
        }
        
        return true;
    }

    // Get access token
    function getAccessToken() {
        const auth = getAuthData();
        return auth ? auth.access_token : null;
    }

    // Get refresh token
    function getRefreshToken() {
        const auth = getAuthData();
        return auth ? auth.refresh_token : null;
    }

    // Get user info
    function getUserInfo() {
        const auth = getAuthData();
        if (!auth) return null;
        
        return {
            user_id: auth.user_id,
            username: auth.username,
            email: auth.email,
            global_roles: auth.global_roles || [],
            site_roles: auth.site_roles || {},
            site_permissions: auth.site_permissions || {}
        };
    }

    // Check if user has role
    function hasRole(role, siteContext = null) {
        const auth = getAuthData();
        if (!auth) return false;
        
        // Check global roles
        if (auth.global_roles && auth.global_roles.includes(role)) {
            return true;
        }
        
        // Check site-specific roles
        if (siteContext && auth.site_roles && auth.site_roles[siteContext]) {
            return auth.site_roles[siteContext].includes(role);
        }
        
        return false;
    }

    // Check if user has permission
    function hasPermission(permission, siteContext = null) {
        const auth = getAuthData();
        if (!auth) return false;
        
        // Admin has all permissions
        if (hasRole('admin') || hasRole('superadmin')) {
            return true;
        }
        
        // Check site-specific permissions
        if (siteContext && auth.site_permissions && auth.site_permissions[siteContext]) {
            return auth.site_permissions[siteContext].includes(permission);
        }
        
        return false;
    }

    // Refresh access token
    async function refreshToken() {
        const refreshToken = getRefreshToken();
        if (!refreshToken) {
            throw new Error('No refresh token available');
        }

        try {
            const response = await fetch(`${API_BASE}/api/auth/refresh`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ refresh_token: refreshToken })
            });

            const data = await response.json();

            if (response.ok && data.ok && data.auth_packet) {
                const remember = localStorage.getItem(REMEMBER_KEY) === 'true';
                setAuthData(data.auth_packet, remember);
                return data.auth_packet;
            } else {
                throw new Error(data.error || 'Token refresh failed');
            }
        } catch (error) {
            clearAuthData();
            throw error;
        }
    }

    // Make authenticated API request
    async function apiRequest(url, options = {}) {
        const token = getAccessToken();
        
        if (!token) {
            throw new Error('Not authenticated');
        }

        const headers = {
            ...options.headers,
            'Authorization': `Bearer ${token}`
        };

        try {
            const response = await fetch(url, {
                ...options,
                headers
            });

            // If unauthorized, try to refresh token once
            if (response.status === 401) {
                try {
                    await refreshToken();
                    const newToken = getAccessToken();
                    headers['Authorization'] = `Bearer ${newToken}`;
                    
                    // Retry request with new token
                    return await fetch(url, {
                        ...options,
                        headers
                    });
                } catch (refreshError) {
                    // Refresh failed, redirect to login
                    redirectToLogin();
                    throw refreshError;
                }
            }

            return response;
        } catch (error) {
            throw error;
        }
    }

    // Logout
    async function logout(redirectToLoginPage = true) {
        const auth = getAuthData();
        
        if (auth && auth.session_id) {
            try {
                await apiRequest(`${API_BASE}/api/auth/logout`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ session_id: auth.session_id })
                });
            } catch (error) {
                console.error('Logout error:', error);
            }
        }

        clearAuthData();
        
        if (redirectToLoginPage) {
            window.location.href = '/login?logout=success';
        }
    }

    // Redirect to login
    function redirectToLogin(returnUrl = null) {
        const url = returnUrl ? `/login?return=${encodeURIComponent(returnUrl)}` : '/login';
        window.location.href = url;
    }

    // Require authentication (use in protected pages)
    function requireAuth() {
        if (!isAuthenticated()) {
            redirectToLogin(window.location.pathname + window.location.search);
            return false;
        }
        return true;
    }

    // Route based on permissions
    function routeByPermissions() {
        const auth = getAuthData();
        if (!auth) {
            redirectToLogin();
            return;
        }

        const { global_roles, site_roles, site_permissions } = auth;

        // Check for admin access
        if (global_roles.includes('admin') || global_roles.includes('superadmin')) {
            window.location.href = '/dev';
            return;
        }

        // Check for services management
        if (site_permissions['sentra-core']?.includes('manage_services') || 
            global_roles.includes('operator')) {
            window.location.href = '/static/services.html';
            return;
        }

        // Check for developer access
        if (global_roles.includes('developer') || site_roles['sentra-core']?.includes('developer')) {
            window.location.href = '/dev';
            return;
        }

        // Default to status page for regular users
        window.location.href = '/status';
    }

    // Public API
    return {
        getAuthData,
        setAuthData,
        clearAuthData,
        isAuthenticated,
        getAccessToken,
        getRefreshToken,
        getUserInfo,
        hasRole,
        hasPermission,
        refreshToken,
        apiRequest,
        logout,
        redirectToLogin,
        requireAuth,
        routeByPermissions
    };
})();

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SentraAuth;
}
