#!/usr/bin/env python3
"""
Service Registry - Central registry for all Sentra microservices

Tracks:
- Service registration and deregistration
- Health status
- Service discovery
- Load balancing endpoints
"""
import json
import time
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
from typing import Dict, Any, List
from dataclasses import dataclass, asdict
from datetime import datetime
import threading

@dataclass
class ServiceRegistration:
    """Service registration information"""
    name: str
    host: str
    port: int
    health_endpoint: str
    registered_at: float
    last_heartbeat: float
    status: str = "healthy"
    metadata: dict = None
    
    def to_dict(self):
        return {
            **asdict(self),
            'registered_at': datetime.fromtimestamp(self.registered_at).isoformat(),
            'last_heartbeat': datetime.fromtimestamp(self.last_heartbeat).isoformat()
        }

class ServiceRegistry:
    """Service registry with health monitoring"""
    
    def __init__(self):
        self.services: Dict[str, ServiceRegistration] = {}
        self.lock = threading.RLock()
        self.heartbeat_timeout = 60  # seconds
        
        # Start health check loop
        self.running = True
        self.health_thread = threading.Thread(target=self._health_check_loop, daemon=True)
        self.health_thread.start()
    
    def register(self, name: str, host: str, port: int, health_endpoint: str = "/health", metadata: dict = None):
        """Register a new service"""
        with self.lock:
            self.services[name] = ServiceRegistration(
                name=name,
                host=host,
                port=port,
                health_endpoint=health_endpoint,
                registered_at=time.time(),
                last_heartbeat=time.time(),
                status="healthy",
                metadata=metadata or {}
            )
            print(f"✅ Registered service: {name} @ {host}:{port}")
    
    def deregister(self, name: str):
        """Deregister a service"""
        with self.lock:
            if name in self.services:
                del self.services[name]
                print(f"⏹️  Deregistered service: {name}")
    
    def heartbeat(self, name: str):
        """Update service heartbeat"""
        with self.lock:
            if name in self.services:
                self.services[name].last_heartbeat = time.time()
                self.services[name].status = "healthy"
    
    def get_service(self, name: str) -> Dict[str, Any]:
        """Get service information"""
        with self.lock:
            service = self.services.get(name)
            return service.to_dict() if service else None
    
    def list_services(self, status: str = None) -> List[Dict[str, Any]]:
        """List all services, optionally filtered by status"""
        with self.lock:
            services = self.services.values()
            if status:
                services = [s for s in services if s.status == status]
            return [s.to_dict() for s in services]
    
    def _health_check_loop(self):
        """Background health monitoring"""
        while self.running:
            time.sleep(10)
            
            with self.lock:
                current_time = time.time()
                for service in self.services.values():
                    age = current_time - service.last_heartbeat
                    
                    if age > self.heartbeat_timeout:
                        service.status = "unhealthy"
                        print(f"⚠️  Service unhealthy: {service.name} (no heartbeat for {age:.0f}s)")

class RegistryHandler(BaseHTTPRequestHandler):
    """HTTP handler for registry endpoints"""
    
    registry: ServiceRegistry = None
    
    def do_GET(self):
        """Handle GET requests"""
        parsed = urlparse(self.path)
        path = parsed.path
        query = parse_qs(parsed.query)
        
        if path == "/health":
            self._send_json(200, {"ok": True, "status": "healthy"})
        
        elif path == "/services":
            status = query.get('status', [None])[0]
            services = self.registry.list_services(status=status)
            self._send_json(200, {"ok": True, "services": services, "total": len(services)})
        
        elif path.startswith("/services/"):
            name = path.split("/")[-1]
            service = self.registry.get_service(name)
            if service:
                self._send_json(200, {"ok": True, "service": service})
            else:
                self._send_json(404, {"ok": False, "error": "Service not found"})
        
        else:
            self._send_json(404, {"ok": False, "error": "Not found"})
    
    def do_POST(self):
        """Handle POST requests"""
        parsed = urlparse(self.path)
        path = parsed.path
        
        # Read body
        content_length = int(self.headers.get('Content-Length', 0))
        body = self.rfile.read(content_length)
        
        try:
            data = json.loads(body) if body else {}
        except:
            self._send_json(400, {"ok": False, "error": "Invalid JSON"})
            return
        
        if path == "/register":
            name = data.get('name')
            host = data.get('host')
            port = data.get('port')
            health_endpoint = data.get('health_endpoint', '/health')
            metadata = data.get('metadata', {})
            
            if not name or not host or not port:
                self._send_json(400, {"ok": False, "error": "Missing required fields"})
                return
            
            self.registry.register(name, host, port, health_endpoint, metadata)
            self._send_json(200, {"ok": True, "message": f"Service {name} registered"})
        
        elif path == "/deregister":
            name = data.get('name')
            if not name:
                self._send_json(400, {"ok": False, "error": "Missing service name"})
                return
            
            self.registry.deregister(name)
            self._send_json(200, {"ok": True, "message": f"Service {name} deregistered"})
        
        elif path == "/heartbeat":
            name = data.get('name')
            if not name:
                self._send_json(400, {"ok": False, "error": "Missing service name"})
                return
            
            self.registry.heartbeat(name)
            self._send_json(200, {"ok": True, "message": "Heartbeat received"})
        
        else:
            self._send_json(404, {"ok": False, "error": "Not found"})
    
    def do_DELETE(self):
        """Handle DELETE requests"""
        parsed = urlparse(self.path)
        path = parsed.path
        
        if path.startswith("/services/"):
            name = path.split("/")[-1]
            self.registry.deregister(name)
            self._send_json(200, {"ok": True, "message": f"Service {name} deregistered"})
        else:
            self._send_json(404, {"ok": False, "error": "Not found"})
    
    def _send_json(self, status: int, data: dict):
        """Send JSON response"""
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.end_headers()
        self.wfile.write(json.dumps(data).encode())
    
    def log_message(self, format, *args):
        """Custom logging"""
        print(f"[{self.log_date_time_string()}] {format % args}")

def main():
    port = 8081
    
    # Create registry
    registry = ServiceRegistry()
    RegistryHandler.registry = registry
    
    # Start server
    server = HTTPServer(('0.0.0.0', port), RegistryHandler)
    print(f"🚀 Service Registry started on port {port}")
    print(f"📋 Endpoints:")
    print(f"   GET  /health")
    print(f"   GET  /services")
    print(f"   GET  /services/:name")
    print(f"   POST /register")
    print(f"   POST /deregister")
    print(f"   POST /heartbeat")
    
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\n⏹️  Shutting down registry...")
        registry.running = False
        server.shutdown()

if __name__ == "__main__":
    main()
