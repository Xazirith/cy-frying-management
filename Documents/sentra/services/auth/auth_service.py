#!/usr/bin/env python3
"""
Authentication Service - Handles user auth, API keys, tokens

Microservice for:
- User authentication
- API key management
- JWT token generation/validation
- Session management
"""
import sys
import os
import json
import time
import hashlib
import secrets
from typing import Dict, Any, Optional

# Add parent directory to path for base_service import
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from base_service import SentraService

class AuthService(SentraService):
    """Authentication microservice"""
    
    def __init__(self):
        super().__init__("sentra-auth", 8082)
        
        # In-memory stores (would use Redis/DB in production)
        self.api_keys: Dict[str, dict] = {}
        self.sessions: Dict[str, dict] = {}
        self.users: Dict[str, dict] = {
            "admin": {
                "username": "admin",
                "password_hash": self._hash_password("admin123"),  # Default password
                "role": "admin",
                "created_at": time.time()
            }
        }
    
    def get_metadata(self) -> Dict[str, Any]:
        return {
            "version": "1.0.0",
            "description": "Authentication and authorization service",
            "endpoints": [
                "POST /api/auth/login",
                "POST /api/auth/logout",
                "POST /api/auth/verify",
                "POST /api/keys/create",
                "GET  /api/keys/list",
                "DELETE /api/keys/:key"
            ]
        }
    
    def handle_request(self, method: str, path: str, headers: dict, body: bytes) -> tuple[int, dict]:
        """Route requests to handlers"""
        
        # Parse body
        data = {}
        if body:
            try:
                data = json.loads(body)
            except:
                return 400, {"ok": False, "error": "Invalid JSON"}
        
        # Route handlers
        if path == "/api/auth/login" and method == "POST":
            return self._login(data)
        
        elif path == "/api/auth/logout" and method == "POST":
            return self._logout(headers)
        
        elif path == "/api/auth/verify" and method == "POST":
            return self._verify(headers)
        
        elif path == "/api/keys/create" and method == "POST":
            return self._create_api_key(data, headers)
        
        elif path == "/api/keys/list" and method == "GET":
            return self._list_api_keys(headers)
        
        elif path.startswith("/api/keys/") and method == "DELETE":
            key = path.split("/")[-1]
            return self._delete_api_key(key, headers)
        
        return 404, {"ok": False, "error": "Not found"}
    
    # ==================== AUTH ENDPOINTS ====================
    
    def _login(self, data: dict) -> tuple[int, dict]:
        """POST /api/auth/login - User login"""
        username = data.get("username")
        password = data.get("password")
        
        if not username or not password:
            return 400, {"ok": False, "error": "Missing username or password"}
        
        # Check credentials
        user = self.users.get(username)
        if not user:
            return 401, {"ok": False, "error": "Invalid credentials"}
        
        if user["password_hash"] != self._hash_password(password):
            return 401, {"ok": False, "error": "Invalid credentials"}
        
        # Create session
        session_token = secrets.token_urlsafe(32)
        self.sessions[session_token] = {
            "username": username,
            "role": user["role"],
            "created_at": time.time(),
            "expires_at": time.time() + 86400  # 24 hours
        }
        
        return 200, {
            "ok": True,
            "token": session_token,
            "user": {
                "username": username,
                "role": user["role"]
            }
        }
    
    def _logout(self, headers: dict) -> tuple[int, dict]:
        """POST /api/auth/logout - User logout"""
        token = self._extract_token(headers)
        
        if token and token in self.sessions:
            del self.sessions[token]
        
        return 200, {"ok": True, "message": "Logged out"}
    
    def _verify(self, headers: dict) -> tuple[int, dict]:
        """POST /api/auth/verify - Verify session token"""
        token = self._extract_token(headers)
        
        if not token:
            return 401, {"ok": False, "error": "No token provided"}
        
        session = self.sessions.get(token)
        if not session:
            return 401, {"ok": False, "error": "Invalid token"}
        
        # Check expiration
        if time.time() > session["expires_at"]:
            del self.sessions[token]
            return 401, {"ok": False, "error": "Token expired"}
        
        return 200, {
            "ok": True,
            "valid": True,
            "user": {
                "username": session["username"],
                "role": session["role"]
            }
        }
    
    # ==================== API KEY ENDPOINTS ====================
    
    def _create_api_key(self, data: dict, headers: dict) -> tuple[int, dict]:
        """POST /api/keys/create - Create new API key"""
        # Verify admin session
        if not self._is_admin(headers):
            return 403, {"ok": False, "error": "Admin access required"}
        
        name = data.get("name", "Unnamed Key")
        permissions = data.get("permissions", ["read"])
        
        # Generate key
        api_key = f"sk_{secrets.token_urlsafe(32)}"
        
        self.api_keys[api_key] = {
            "name": name,
            "permissions": permissions,
            "created_at": time.time(),
            "last_used": None,
            "uses": 0
        }
        
        return 200, {
            "ok": True,
            "api_key": api_key,
            "name": name,
            "permissions": permissions
        }
    
    def _list_api_keys(self, headers: dict) -> tuple[int, dict]:
        """GET /api/keys/list - List all API keys"""
        if not self._is_admin(headers):
            return 403, {"ok": False, "error": "Admin access required"}
        
        keys = []
        for key, info in self.api_keys.items():
            keys.append({
                "key": key[:15] + "...",  # Masked
                "name": info["name"],
                "permissions": info["permissions"],
                "created_at": info["created_at"],
                "uses": info["uses"]
            })
        
        return 200, {"ok": True, "keys": keys, "total": len(keys)}
    
    def _delete_api_key(self, key: str, headers: dict) -> tuple[int, dict]:
        """DELETE /api/keys/:key - Delete API key"""
        if not self._is_admin(headers):
            return 403, {"ok": False, "error": "Admin access required"}
        
        if key in self.api_keys:
            del self.api_keys[key]
            return 200, {"ok": True, "message": "API key deleted"}
        
        return 404, {"ok": False, "error": "API key not found"}
    
    # ==================== HELPERS ====================
    
    def _hash_password(self, password: str) -> str:
        """Hash password with SHA256"""
        return hashlib.sha256(password.encode()).hexdigest()
    
    def _extract_token(self, headers: dict) -> Optional[str]:
        """Extract bearer token from headers"""
        auth = headers.get('Authorization', '') or headers.get('authorization', '')
        if auth.startswith('Bearer '):
            return auth[7:]
        return None
    
    def _is_admin(self, headers: dict) -> bool:
        """Check if request has admin permissions"""
        token = self._extract_token(headers)
        if not token:
            return False
        
        session = self.sessions.get(token)
        if not session:
            return False
        
        return session.get("role") == "admin"

def main():
    service = AuthService()
    service.start()

if __name__ == "__main__":
    main()
