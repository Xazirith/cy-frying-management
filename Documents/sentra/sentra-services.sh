#!/bin/bash
# Sentra Microservices Management Script

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Project root
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_ROOT"

# Helper functions
log_info() {
    echo -e "${BLUE}ℹ ${NC}$1"
}

log_success() {
    echo -e "${GREEN}✓${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
}

# Commands
cmd_start() {
    log_info "Starting Sentra microservices..."
    docker-compose up -d
    log_success "All services started"
    cmd_status
}

cmd_stop() {
    log_info "Stopping Sentra microservices..."
    docker-compose down
    log_success "All services stopped"
}

cmd_restart() {
    log_info "Restarting Sentra microservices..."
    docker-compose restart
    log_success "All services restarted"
}

cmd_status() {
    echo ""
    log_info "Service Status:"
    docker-compose ps
    echo ""
    
    log_info "Service Registry:"
    if curl -s http://localhost:8081/services > /dev/null 2>&1; then
        curl -s http://localhost:8081/services | jq -r '.services[] | "  \(.name): \(.status)"'
    else
        log_warning "Service registry not accessible"
    fi
}

cmd_logs() {
    service="${1:-}"
    if [ -z "$service" ]; then
        log_info "Showing logs for all services (Ctrl+C to exit)..."
        docker-compose logs -f
    else
        log_info "Showing logs for $service (Ctrl+C to exit)..."
        docker-compose logs -f "$service"
    fi
}

cmd_build() {
    service="${1:-}"
    if [ -z "$service" ]; then
        log_info "Building all services..."
        docker-compose build
    else
        log_info "Building $service..."
        docker-compose build "$service"
    fi
    log_success "Build complete"
}

cmd_rebuild() {
    service="${1:-}"
    if [ -z "$service" ]; then
        log_info "Rebuilding all services..."
        docker-compose down
        docker-compose build --no-cache
        docker-compose up -d
    else
        log_info "Rebuilding $service..."
        docker-compose stop "$service"
        docker-compose build --no-cache "$service"
        docker-compose up -d "$service"
    fi
    log_success "Rebuild complete"
}

cmd_health() {
    log_info "Checking service health..."
    echo ""
    
    # Check core
    echo -n "sentra-core (8080): "
    if curl -sf http://localhost:8080/api/ping > /dev/null; then
        echo -e "${GREEN}✓ Healthy${NC}"
    else
        echo -e "${RED}✗ Unhealthy${NC}"
    fi
    
    # Check registry
    echo -n "sentra-registry (8081): "
    if curl -sf http://localhost:8081/health > /dev/null; then
        echo -e "${GREEN}✓ Healthy${NC}"
    else
        echo -e "${RED}✗ Unhealthy${NC}"
    fi
    
    # Check auth
    echo -n "sentra-auth (8082): "
    if docker exec sentra-auth curl -sf http://localhost:8082/health > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Healthy${NC}"
    else
        echo -e "${RED}✗ Unhealthy${NC}"
    fi
    
    # Check dashboard
    echo -n "sentra-dash (80): "
    if curl -sf http://localhost > /dev/null; then
        echo -e "${GREEN}✓ Healthy${NC}"
    else
        echo -e "${RED}✗ Unhealthy${NC}"
    fi
}

cmd_shell() {
    service="${1:-sentra-core}"
    log_info "Opening shell in $service..."
    docker exec -it "$service" /bin/bash || docker exec -it "$service" /bin/sh
}

cmd_clean() {
    log_warning "This will remove all containers, volumes, and networks"
    read -p "Are you sure? (yes/no): " confirm
    
    if [ "$confirm" = "yes" ]; then
        log_info "Cleaning up..."
        docker-compose down -v
        docker system prune -f
        log_success "Cleanup complete"
    else
        log_info "Cancelled"
    fi
}

cmd_create_service() {
    service_name="$1"
    port="$2"
    
    if [ -z "$service_name" ] || [ -z "$port" ]; then
        log_error "Usage: $0 create-service <name> <port>"
        exit 1
    fi
    
    log_info "Creating new service: $service_name"
    
    # Create directory
    mkdir -p "services/$service_name"
    
    # Create service file
    cat > "services/$service_name/${service_name}_service.py" << EOF
#!/usr/bin/env python3
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from base_service import SentraService

class ${service_name^}Service(SentraService):
    def __init__(self):
        super().__init__("sentra-$service_name", $port)
    
    def get_metadata(self):
        return {
            "version": "1.0.0",
            "description": "$service_name service"
        }
    
    def handle_request(self, method, path, headers, body):
        if path == "/api/$service_name/hello":
            return 200, {"ok": True, "message": "Hello from $service_name"}
        
        return 404, {"ok": False, "error": "Not found"}

if __name__ == "__main__":
    service = ${service_name^}Service()
    service.start()
EOF
    
    # Create Dockerfile
    cat > "services/$service_name/Dockerfile" << EOF
FROM python:3.11-slim

WORKDIR /app

RUN pip install --no-cache-dir requests

COPY ../base_service.py .
COPY ${service_name}_service.py .

EXPOSE $port

CMD ["python", "${service_name}_service.py"]
EOF
    
    log_success "Service created at services/$service_name/"
    log_info "Next steps:"
    echo "  1. Edit services/$service_name/${service_name}_service.py"
    echo "  2. Add service to docker-compose.yml"
    echo "  3. Run: ./sentra-services.sh build $service_name"
    echo "  4. Run: ./sentra-services.sh start"
}

cmd_help() {
    cat << EOF
Sentra Microservices Management

Usage: $0 <command> [options]

Commands:
  start                 Start all services
  stop                  Stop all services
  restart               Restart all services
  status                Show service status
  logs [service]        Show logs (all or specific service)
  build [service]       Build services (all or specific)
  rebuild [service]     Rebuild services from scratch
  health                Check health of all services
  shell [service]       Open shell in service container
  clean                 Remove all containers and volumes
  create-service <name> <port>  Create new service template
  
Examples:
  $0 start
  $0 logs sentra-auth
  $0 build sentra-core
  $0 shell sentra-registry
  $0 create-service monitoring 8087

EOF
}

# Main
command="${1:-help}"
shift || true

case "$command" in
    start)
        cmd_start
        ;;
    stop)
        cmd_stop
        ;;
    restart)
        cmd_restart
        ;;
    status)
        cmd_status
        ;;
    logs)
        cmd_logs "$@"
        ;;
    build)
        cmd_build "$@"
        ;;
    rebuild)
        cmd_rebuild "$@"
        ;;
    health)
        cmd_health
        ;;
    shell)
        cmd_shell "$@"
        ;;
    clean)
        cmd_clean
        ;;
    create-service)
        cmd_create_service "$@"
        ;;
    help|--help|-h)
        cmd_help
        ;;
    *)
        log_error "Unknown command: $command"
        cmd_help
        exit 1
        ;;
esac
