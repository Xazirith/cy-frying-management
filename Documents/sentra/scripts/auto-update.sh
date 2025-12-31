#!/bin/bash
# Auto-update service containers when git changes detected

set -e

REPO_PATH="/opt/sentra-core"
SERVICES_PATH="$REPO_PATH/services"
COMPOSE_FILE="$REPO_PATH/docker-compose.yml"

log_info() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Get current git commit
get_commit() {
    cd "$REPO_PATH"
    git rev-parse HEAD
}

# Check for changes in a service
service_changed() {
    local service=$1
    local last_commit=$2
    local current_commit=$3
    
    cd "$REPO_PATH"
    
    # Check if service files changed
    if git diff --name-only "$last_commit" "$current_commit" | grep -q "^services/$service/"; then
        return 0
    fi
    
    return 1
}

# Rebuild and restart service
update_service() {
    local service=$1
    
    log_info "Updating service: $service"
    
    cd "$REPO_PATH"
    
    # Build new image
    docker-compose build "$service"
    
    # Restart service
    docker-compose up -d "$service"
    
    log_info "Service $service updated successfully"
}

# Main update loop
main() {
    local last_commit=$(get_commit)
    
    log_info "Auto-update watcher started"
    log_info "Watching commit: $last_commit"
    
    while true; do
        sleep 30
        
        # Pull latest changes
        cd "$REPO_PATH"
        git fetch origin main >/dev/null 2>&1 || continue
        
        local current_commit=$(git rev-parse origin/main)
        
        if [ "$current_commit" != "$last_commit" ]; then
            log_info "New commits detected: $last_commit -> $current_commit"
            
            # Pull changes
            git pull origin main
            
            # Check each service for changes
            for service_dir in "$SERVICES_PATH"/*/ ; do
                if [ -d "$service_dir" ]; then
                    service_name=$(basename "$service_dir")
                    container_name="sentra-$service_name"
                    
                    if service_changed "$service_name" "$last_commit" "$current_commit"; then
                        update_service "$container_name"
                    fi
                done
            done
            
            # Check if core changed
            if git diff --name-only "$last_commit" "$current_commit" | grep -q "^core/"; then
                update_service "sentra-core"
            fi
            
            # Check if dashboard changed
            if git diff --name-only "$last_commit" "$current_commit" | grep -q "^core/static/"; then
                update_service "sentra-dash"
            fi
            
            last_commit=$current_commit
        fi
    done
}

main
