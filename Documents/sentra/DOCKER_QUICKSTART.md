# Sentra Docker Microservices - Quick Start

## What Changed

Your Sentra system now has a **microservices architecture** using Docker containers instead of a monolithic application. Each service runs independently and can be scaled separately.

## Architecture

```
┌──────────────────────────────────────────────────────┐
│                   YOUR SYSTEM                        │
├──────────────────────────────────────────────────────┤
│                                                      │
│  [Web Browser] → [Nginx:80] → [Dashboard HTML]      │
│                       ↓                              │
│                 [sentra-core:8080]                   │
│                       ↓                              │
│              [Service Registry:8081]                 │
│                       ↓                              │
│     ┌────────┬────────┬────────┬────────┐           │
│     │  Auth  │ Deploy │  DNS   │ Metrics│           │
│     │  8082  │  8083  │  8084  │  8085  │           │
│     └────────┴────────┴────────┴────────┘           │
│                                                      │
└──────────────────────────────────────────────────────┘
```

## Quick Commands

**Start everything:**
```bash
cd /home/xazirith/Documents/sentra
./sentra-services.sh start
```

**Check status:**
```bash
./sentra-services.sh status
```

**View logs:**
```bash
./sentra-services.sh logs                  # All services
./sentra-services.sh logs sentra-auth      # Specific service
```

**Restart a service:**
```bash
docker-compose restart sentra-auth
```

**Stop everything:**
```bash
./sentra-services.sh stop
```

## Access Points

- **Dashboard:** http://localhost
- **Core API:** http://localhost:8080
- **Service Registry:** http://localhost:8081/services
- **Auth Service:** Internal only (via registry)

## What's Included

### 1. Core Components
- **sentra-core** - Main API gateway, routes requests
- **sentra-registry** - Tracks all services, health checks
- **sentra-dash** - Web dashboard (your modular admin panel)
- **Redis** - Fast cache and pub/sub messaging

### 2. Example Services
- **sentra-auth** - User authentication, API keys, sessions

### 3. Management Tools
- `sentra-services.sh` - Main management script
- `docker-compose.yml` - Service orchestration config
- `MICROSERVICES.md` - Full documentation

## Creating New Services

**Easy way - use the generator:**
```bash
./sentra-services.sh create-service myservice 8087
```

This creates:
- `services/myservice/myservice_service.py`
- `services/myservice/Dockerfile`

**Then:**
1. Edit the Python file with your logic
2. Add to `docker-compose.yml`
3. Run: `./sentra-services.sh build myservice`
4. Run: `./sentra-services.sh start`

## Migration Path

Your existing modules in `core/app/modules/` still work! The system supports **both**:
- Old modules (loaded by ModuleLoader)
- New services (Docker containers)

**Gradually migrate:**
1. Pick a module (e.g., DNS)
2. Create equivalent service: `./sentra-services.sh create-service dns 8084`
3. Port the logic
4. Test in parallel
5. Remove old module when ready

## Key Benefits

✅ **Isolation** - Services can't crash each other
✅ **Scaling** - Run multiple instances of any service
✅ **Languages** - Services can be written in any language
✅ **Deployment** - Update one service without touching others
✅ **Monitoring** - Health checks and automatic restarts
✅ **Development** - Work on services independently

## Network Security

- **External network** - Only core, dashboard, registry exposed to host
- **Internal network** - Services talk to each other securely
- **No port conflicts** - Each service has its own isolated container

## Next Steps

1. **Test it out:**
   ```bash
   ./sentra-services.sh start
   ./sentra-services.sh health
   ```

2. **View the dashboard:**
   ```
   http://localhost
   ```

3. **Check service registry:**
   ```bash
   curl http://localhost:8081/services | jq
   ```

4. **Create your first service:**
   ```bash
   ./sentra-services.sh create-service monitoring 8087
   ```

5. **Read full docs:**
   ```bash
   cat MICROSERVICES.md
   ```

## Troubleshooting

**Services won't start:**
```bash
./sentra-services.sh logs
docker ps -a
```

**Port conflicts:**
```bash
# Change ports in docker-compose.yml
# Then rebuild
./sentra-services.sh rebuild
```

**Service won't register:**
```bash
# Check registry
docker logs sentra-registry

# Check service logs
docker logs sentra-auth
```

**Clean slate:**
```bash
./sentra-services.sh clean
./sentra-services.sh start
```

## Files Overview

```
sentra/
├── docker-compose.yml          # Service orchestration
├── sentra-services.sh          # Management script ⭐
├── MICROSERVICES.md            # Full documentation
│
├── services/                   # Microservices ⭐
│   ├── base_service.py        # Base class for services
│   ├── registry/              # Service registry
│   │   ├── registry.py
│   │   └── Dockerfile
│   └── auth/                  # Auth service example
│       ├── auth_service.py
│       └── Dockerfile
│
└── core/                       # Core application
    ├── main.py                # Now supports both modules + services
    └── app/
        ├── modules/           # Legacy modules (still work)
        └── services/          # In-process services
```

## Philosophy

**Containers vs Modules:**
- **Modules** = Code loaded into main process
- **Services** = Separate Docker containers

**When to use each:**
- **Modules:** Tight integration, needs core internals
- **Services:** Independent functionality, can scale, different language

**Both work together!** The system is hybrid during migration.

---

**Questions?** Check `MICROSERVICES.md` or run `./sentra-services.sh help`
