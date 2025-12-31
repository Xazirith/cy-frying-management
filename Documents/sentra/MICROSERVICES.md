# Sentra Microservices Architecture

## Overview

Sentra is transitioning from a monolithic application to a **microservices architecture** using Docker containers. Each service runs independently and communicates through a service registry.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     SENTRA ECOSYSTEM                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌───────────┐    ┌────────────┐    ┌──────────────┐      │
│  │   Nginx   │───▶│ sentra-dash│───▶│ sentra-core  │      │
│  │  (80/443) │    │  Dashboard │    │  Main API    │      │
│  └───────────┘    └────────────┘    └──────┬───────┘      │
│                                             │               │
│                                             │               │
│                    ┌────────────────────────┴─────────┐     │
│                    │   Service Registry (8081)        │     │
│                    │   - Service discovery            │     │
│                    │   - Health monitoring            │     │
│                    │   - Load balancing               │     │
│                    └──────┬───────────────────────────┘     │
│                           │                                 │
│         ┌─────────────────┼─────────────────┐              │
│         │                 │                 │              │
│    ┌────▼────┐      ┌────▼────┐      ┌────▼────┐          │
│    │  Auth   │      │ Deploy  │      │   DNS   │          │
│    │ Service │      │ Service │      │ Service │          │
│    │  8082   │      │  8083   │      │  8084   │          │
│    └─────────┘      └─────────┘      └─────────┘          │
│                                                             │
│    ┌─────────┐      ┌─────────┐      ┌─────────┐          │
│    │ Metrics │      │   VPN   │      │  Redis  │          │
│    │ Service │      │ Service │      │ (Cache) │          │
│    │  8085   │      │  8086   │      │  6379   │          │
│    └─────────┘      └─────────┘      └─────────┘          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Core Components

### 1. **sentra-core** (Port 8080)
- Main API gateway
- Orchestrates other services
- Handles HTTP routing
- Legacy module support (during transition)

### 2. **sentra-registry** (Port 8081)
- Service discovery and registration
- Health monitoring
- Heartbeat tracking
- Service metadata

### 3. **sentra-dash** (Ports 80/443)
- Web dashboard
- Nginx serving static files
- Proxies API requests to sentra-core

### 4. **Redis** (Port 6379)
- Pub/sub messaging
- Session storage
- Cache layer

## Microservices

### Authentication Service (8082)
- User login/logout
- Session management
- API key generation
- Token verification

**Endpoints:**
```
POST   /api/auth/login       - User login
POST   /api/auth/logout      - User logout
POST   /api/auth/verify      - Verify token
POST   /api/keys/create      - Create API key
GET    /api/keys/list        - List API keys
DELETE /api/keys/:key        - Delete API key
```

### Deployment Service (8083)
- Container management
- Deployment orchestration
- Docker operations

### DNS Service (8084)
- DNS record management
- Service name resolution

### Metrics Service (8085)
- System metrics collection
- Performance monitoring
- Resource tracking

### VPN Service (8086)
- VPN configuration
- Connection management

## Getting Started

### Prerequisites
- Docker 20.10+
- Docker Compose 2.0+

### Quick Start

1. **Clone repository:**
   ```bash
   cd /home/xazirith/Documents/sentra
   ```

2. **Start all services:**
   ```bash
   docker-compose up -d
   ```

3. **View logs:**
   ```bash
   docker-compose logs -f
   ```

4. **Check service status:**
   ```bash
   docker-compose ps
   ```

5. **Access dashboard:**
   ```
   http://localhost
   ```

6. **Access core API:**
   ```
   http://localhost:8080
   ```

### Individual Service Management

**Start specific service:**
```bash
docker-compose up -d sentra-auth
```

**Restart service:**
```bash
docker-compose restart sentra-auth
```

**View service logs:**
```bash
docker-compose logs -f sentra-auth
```

**Stop service:**
```bash
docker-compose stop sentra-auth
```

## Creating a New Service

### 1. Create Service Directory
```bash
mkdir -p services/myservice
cd services/myservice
```

### 2. Create Service File
```python
#!/usr/bin/env python3
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from base_service import SentraService

class MyService(SentraService):
    def __init__(self):
        super().__init__("sentra-myservice", 8090)
    
    def get_metadata(self):
        return {
            "version": "1.0.0",
            "description": "My custom service"
        }
    
    def handle_request(self, method, path, headers, body):
        if path == "/api/myservice/hello":
            return 200, {"ok": True, "message": "Hello!"}
        
        return 404, {"ok": False, "error": "Not found"}

if __name__ == "__main__":
    service = MyService()
    service.start()
```

### 3. Create Dockerfile
```dockerfile
FROM python:3.11-slim

WORKDIR /app

RUN pip install --no-cache-dir requests

COPY ../base_service.py .
COPY myservice.py .

EXPOSE 8090

CMD ["python", "myservice.py"]
```

### 4. Add to docker-compose.yml
```yaml
sentra-myservice:
  container_name: sentra-myservice
  build:
    context: ./services/myservice
    dockerfile: Dockerfile
  networks:
    - sentra-internal
  environment:
    - SERVICE_NAME=myservice
    - SERVICE_PORT=8090
    - CORE_URL=http://sentra-core:8080
    - REGISTRY_URL=http://sentra-registry:8081
  depends_on:
    - sentra-core
    - sentra-registry
  restart: unless-stopped
```

### 5. Build and Run
```bash
docker-compose up -d sentra-myservice
```

## Service Communication

### Registering with Registry

Services automatically register on startup using the `SentraService` base class:

```python
# Automatically handled by base class
service = MyService()
service.start()  # Registers with registry
```

### Calling Core API

```python
# From within a service
response = self.call_core("GET", "/api/core/status")
if response:
    print(f"Core status: {response}")
```

### Calling Another Service

```python
import requests

# Use service name (Docker DNS)
response = requests.get("http://sentra-auth:8082/api/auth/verify")
```

## Service Registry API

### Register Service
```bash
POST http://localhost:8081/register
{
  "name": "myservice",
  "host": "sentra-myservice",
  "port": 8090,
  "health_endpoint": "/health",
  "metadata": {"version": "1.0.0"}
}
```

### List Services
```bash
GET http://localhost:8081/services
GET http://localhost:8081/services?status=healthy
```

### Get Service Details
```bash
GET http://localhost:8081/services/sentra-auth
```

### Send Heartbeat
```bash
POST http://localhost:8081/heartbeat
{
  "name": "myservice"
}
```

## Networking

### External Network (sentra-network)
- Accessible from host
- Dashboard (80/443)
- Core API (8080)
- Service Registry (8081)

### Internal Network (sentra-internal)
- Internal service communication only
- Not accessible from host
- All microservices
- Redis

## Environment Variables

### Common Variables (All Services)
```env
SERVICE_NAME=myservice        # Service identifier
SERVICE_PORT=8090            # Service port
CORE_URL=http://sentra-core:8080
REGISTRY_URL=http://sentra-registry:8081
```

### Service-Specific
```env
REDIS_URL=redis://sentra-redis:6379  # For services using Redis
```

## Health Checks

All services must implement a `/health` endpoint:

```python
def get_health(self):
    return {
        "ok": True,
        "service": self.service_name,
        "status": "healthy",
        "uptime": int(time.time() - self.start_time)
    }
```

Docker health checks are configured in docker-compose.yml:

```yaml
healthcheck:
  test: ["CMD", "curl", "-f", "http://localhost:8082/health"]
  interval: 30s
  timeout: 5s
  retries: 3
  start_period: 40s
```

## Monitoring

### View All Services
```bash
curl http://localhost:8081/services | jq
```

### Check Service Health
```bash
curl http://localhost:8082/health | jq
```

### Container Stats
```bash
docker stats
```

### Resource Usage
```bash
docker-compose top
```

## Troubleshooting

### Service Won't Start
```bash
# Check logs
docker-compose logs sentra-auth

# Check if port is available
netstat -tuln | grep 8082

# Rebuild
docker-compose build sentra-auth
docker-compose up -d sentra-auth
```

### Service Not Registering
```bash
# Check registry logs
docker-compose logs sentra-registry

# Verify network connectivity
docker exec sentra-auth ping sentra-registry

# Check environment variables
docker exec sentra-auth env | grep REGISTRY_URL
```

### Communication Issues
```bash
# Test inter-service connectivity
docker exec sentra-auth curl http://sentra-core:8080/api/ping

# Check network
docker network inspect sentra_sentra-internal
```

## Migration from Modules

Legacy modules in `core/app/modules/` will gradually be converted to microservices:

1. **Module** → **Service** conversion
2. Run both in parallel during transition
3. Update dependencies
4. Deprecate module
5. Remove module code

## Development

### Local Development
```bash
# Run specific service locally
cd services/auth
python auth_service.py
```

### Hot Reload
For development, mount code as volume:

```yaml
volumes:
  - ./services/auth:/app
```

### Debugging
```bash
# Attach to running container
docker exec -it sentra-auth bash

# View environment
docker exec sentra-auth env

# Test endpoints
docker exec sentra-auth curl http://localhost:8082/health
```

## Production Deployment

### Build Images
```bash
docker-compose build
```

### Push to Registry
```bash
docker tag sentra-auth:latest registry.example.com/sentra-auth:1.0.0
docker push registry.example.com/sentra-auth:1.0.0
```

### Deploy
```bash
docker-compose -f docker-compose.prod.yml up -d
```

## Security

- **Internal network isolation** - Services not exposed to host
- **Service authentication** - All inter-service calls authenticated
- **Secrets management** - Use Docker secrets for sensitive data
- **Least privilege** - Services run with minimal permissions

## Performance

- **Horizontal scaling** - Scale services independently
- **Load balancing** - Registry supports multiple instances
- **Caching** - Redis for shared cache layer
- **Connection pooling** - Efficient resource usage

## Next Steps

1. **Convert existing modules to services:**
   - [ ] DNS module → DNS service
   - [ ] Deployment module → Deploy service
   - [ ] Metrics module → Metrics service
   - [ ] VPN module → VPN service

2. **Add new services:**
   - [ ] Logging service
   - [ ] Notification service
   - [ ] Backup service

3. **Infrastructure:**
   - [ ] Service mesh (Istio/Linkerd)
   - [ ] Distributed tracing (Jaeger)
   - [ ] Centralized logging (ELK)
   - [ ] Monitoring (Prometheus/Grafana)

## Resources

- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Microservices Patterns](https://microservices.io/patterns/index.html)
- [Service Discovery](https://www.nginx.com/blog/service-discovery-in-a-microservices-architecture/)
