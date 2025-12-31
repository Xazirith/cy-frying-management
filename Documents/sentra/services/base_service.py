#!/usr/bin/env python3
"""
Base Sentra Service - Template for creating microservices

All Sentra microservices should inherit from this base class.
Provides:
- Service registration with registry
- Health checks
- Heartbeat to registry
- Communication with core
"""
import os
import time
import json
import requests
import threading
from abc import ABC, abstractmethod
from http.server import HTTPServer, BaseHTTPRequestHandler
from typing import Dict, Any, Optional

class SentraService(ABC):
    """
    Base class for all Sentra microservices.
    
    Handles:
    - Service registration
    - Heartbeat
    - Health checks
    - HTTP server
    """
    
    def __init__(self, service_name: str, port: int):
        self.service_name = service_name
        self.port = port
        self.host = os.getenv('SERVICE_HOST', '0.0.0.0')
        
        # External services
        self.core_url = os.getenv('CORE_URL', 'http://sentra-core:8080')
        self.registry_url = os.getenv('REGISTRY_URL', 'http://sentra-registry:8081')
        
        # State
        self.running = False
        self.registered = False
        self.start_time = time.time()
        
        # Threads
        self.heartbeat_thread: Optional[threading.Thread] = None
        self.server: Optional[HTTPServer] = None
    
    @abstractmethod
    def get_metadata(self) -> Dict[str, Any]:
        """Return service metadata"""
        return {
            "version": "1.0.0",
            "description": "Base service"
        }
    
    @abstractmethod
    def handle_request(self, method: str, path: str, headers: dict, body: bytes) -> tuple[int, dict]:
        """
        Handle HTTP request
        
        Args:
            method: HTTP method (GET, POST, etc.)
            path: Request path
            headers: Request headers
            body: Request body bytes
        
        Returns:
            Tuple of (status_code, response_dict)
        """
        return 404, {"ok": False, "error": "Not implemented"}
    
    def start(self):
        """Start the service"""
        print(f"🚀 Starting {self.service_name} service on port {self.port}...")
        
        self.running = True
        
        # Register with registry
        self._register()
        
        # Start heartbeat
        self.heartbeat_thread = threading.Thread(target=self._heartbeat_loop, daemon=True)
        self.heartbeat_thread.start()
        
        # Start HTTP server
        handler = self._create_handler()
        self.server = HTTPServer((self.host, self.port), handler)
        
        print(f"✅ {self.service_name} service ready on port {self.port}")
        print(f"📡 Registered with: {self.registry_url}")
        print(f"🔗 Core API: {self.core_url}")
        
        try:
            self.server.serve_forever()
        except KeyboardInterrupt:
            self.stop()
    
    def stop(self):
        """Stop the service"""
        print(f"\n⏹️  Stopping {self.service_name} service...")
        
        self.running = False
        
        # Deregister
        self._deregister()
        
        # Shutdown server
        if self.server:
            self.server.shutdown()
        
        print(f"✅ {self.service_name} service stopped")
    
    def _register(self):
        """Register with service registry"""
        try:
            response = requests.post(
                f"{self.registry_url}/register",
                json={
                    "name": self.service_name,
                    "host": self.service_name,  # Use service name for inter-container DNS
                    "port": self.port,
                    "health_endpoint": "/health",
                    "metadata": self.get_metadata()
                },
                timeout=5
            )
            
            if response.status_code == 200:
                self.registered = True
                print(f"✅ Registered with service registry")
            else:
                print(f"⚠️  Registration failed: {response.status_code}")
        
        except Exception as e:
            print(f"❌ Failed to register: {e}")
    
    def _deregister(self):
        """Deregister from service registry"""
        if not self.registered:
            return
        
        try:
            requests.post(
                f"{self.registry_url}/deregister",
                json={"name": self.service_name},
                timeout=5
            )
            print(f"✅ Deregistered from service registry")
        
        except Exception as e:
            print(f"⚠️  Deregistration failed: {e}")
    
    def _heartbeat_loop(self):
        """Send periodic heartbeat to registry"""
        while self.running:
            time.sleep(30)
            
            if not self.registered:
                continue
            
            try:
                requests.post(
                    f"{self.registry_url}/heartbeat",
                    json={"name": self.service_name},
                    timeout=5
                )
            except Exception as e:
                print(f"⚠️  Heartbeat failed: {e}")
    
    def get_health(self) -> Dict[str, Any]:
        """Get service health status"""
        return {
            "ok": True,
            "service": self.service_name,
            "status": "healthy",
            "uptime": int(time.time() - self.start_time),
            "registered": self.registered
        }
    
    def call_core(self, method: str, endpoint: str, data: dict = None) -> Optional[Dict[str, Any]]:
        """
        Make request to core API
        
        Args:
            method: HTTP method
            endpoint: API endpoint path
            data: Request data
        
        Returns:
            Response JSON or None
        """
        try:
            url = f"{self.core_url}{endpoint}"
            response = requests.request(method, url, json=data, timeout=10)
            return response.json() if response.content else None
        
        except Exception as e:
            print(f"⚠️  Core API call failed: {e}")
            return None
    
    def _create_handler(self):
        """Create HTTP request handler"""
        service = self
        
        class ServiceHandler(BaseHTTPRequestHandler):
            def do_GET(self):
                self._handle_request()
            
            def do_POST(self):
                self._handle_request()
            
            def do_PUT(self):
                self._handle_request()
            
            def do_DELETE(self):
                self._handle_request()
            
            def _handle_request(self):
                # Health check endpoint
                if self.path == "/health":
                    self._send_json(200, service.get_health())
                    return
                
                # Read body
                content_length = int(self.headers.get('Content-Length', 0))
                body = self.rfile.read(content_length) if content_length > 0 else b''
                
                # Call service handler
                try:
                    status, response = service.handle_request(
                        self.command,
                        self.path,
                        dict(self.headers),
                        body
                    )
                    self._send_json(status, response)
                
                except Exception as e:
                    print(f"❌ Request handler error: {e}")
                    self._send_json(500, {"ok": False, "error": str(e)})
            
            def _send_json(self, status: int, data: dict):
                self.send_response(status)
                self.send_header('Content-Type', 'application/json')
                self.end_headers()
                self.wfile.write(json.dumps(data).encode())
            
            def log_message(self, format, *args):
                print(f"[{service.service_name}] {format % args}")
        
        return ServiceHandler

# Example usage
if __name__ == "__main__":
    class ExampleService(SentraService):
        def get_metadata(self):
            return {
                "version": "1.0.0",
                "description": "Example service"
            }
        
        def handle_request(self, method, path, headers, body):
            if path == "/api/example":
                return 200, {"ok": True, "message": "Hello from example service"}
            
            return 404, {"ok": False, "error": "Not found"}
    
    service = ExampleService("example", 8080)
    service.start()
