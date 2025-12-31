#!/usr/bin/env python3
"""
Deployment Service - Docker container management

Handles:
- Container deployment
- Image management
- Service orchestration
"""
import sys
import os
import json
import subprocess
from typing import Dict, Any, List

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from base_service import SentraService

class DeployService(SentraService):
    """Deployment and container management microservice"""
    
    def __init__(self):
        super().__init__("sentra-deploy", 8083)
    
    def get_metadata(self) -> Dict[str, Any]:
        return {
            "version": "1.0.0",
            "description": "Docker container deployment and management",
            "endpoints": [
                "GET  /api/deploy/containers",
                "POST /api/deploy/containers",
                "DELETE /api/deploy/containers/:id",
                "POST /api/deploy/containers/:id/restart",
                "GET  /api/deploy/images",
                "POST /api/deploy/images/pull"
            ],
            "provider": "Docker"
        }
    
    def handle_request(self, method: str, path: str, headers: dict, body: bytes) -> tuple[int, dict]:
        """Route deployment requests"""
        
        # Parse body
        data = {}
        if body:
            try:
                data = json.loads(body)
            except:
                return 400, {"ok": False, "error": "Invalid JSON"}
        
        # Route handlers
        if path == "/api/deploy/containers" and method == "GET":
            return self._list_containers()
        
        elif path == "/api/deploy/containers" and method == "POST":
            return self._create_container(data)
        
        elif path.startswith("/api/deploy/containers/") and "/restart" in path and method == "POST":
            container_id = path.split("/")[4]
            return self._restart_container(container_id)
        
        elif path.startswith("/api/deploy/containers/") and method == "DELETE":
            container_id = path.split("/")[-1]
            return self._remove_container(container_id)
        
        elif path == "/api/deploy/images" and method == "GET":
            return self._list_images()
        
        elif path == "/api/deploy/images/pull" and method == "POST":
            return self._pull_image(data)
        
        return 404, {"ok": False, "error": "Not found"}
    
    # ==================== DOCKER OPERATIONS ====================
    
    def _run_docker(self, args: List[str]) -> tuple[int, str]:
        """Run docker command"""
        try:
            result = subprocess.run(
                ["docker"] + args,
                capture_output=True,
                text=True,
                timeout=30
            )
            return result.returncode, result.stdout
        except Exception as e:
            return 1, str(e)
    
    # ==================== DEPLOYMENT ENDPOINTS ====================
    
    def _list_containers(self) -> tuple[int, dict]:
        """GET /api/deploy/containers - List all containers"""
        
        code, output = self._run_docker(["ps", "-a", "--format", "{{json .}}"])
        
        if code != 0:
            return 500, {"ok": False, "error": "Failed to list containers"}
        
        containers = []
        for line in output.strip().split("\n"):
            if line:
                try:
                    containers.append(json.loads(line))
                except:
                    pass
        
        return 200, {
            "ok": True,
            "containers": containers,
            "total": len(containers)
        }
    
    def _create_container(self, data: dict) -> tuple[int, dict]:
        """POST /api/deploy/containers - Create container"""
        
        image = data.get("image")
        name = data.get("name")
        
        if not image:
            return 400, {"ok": False, "error": "Image required"}
        
        # Build docker run command
        args = ["run", "-d"]
        
        if name:
            args.extend(["--name", name])
        
        # Ports
        for port_mapping in data.get("ports", []):
            args.extend(["-p", port_mapping])
        
        # Environment variables
        for env_var in data.get("env", []):
            args.extend(["-e", env_var])
        
        # Volumes
        for volume in data.get("volumes", []):
            args.extend(["-v", volume])
        
        # Network
        if "network" in data:
            args.extend(["--network", data["network"]])
        
        # Image
        args.append(image)
        
        # Command
        if "command" in data:
            args.extend(data["command"].split())
        
        code, output = self._run_docker(args)
        
        if code != 0:
            return 500, {"ok": False, "error": f"Failed to create container: {output}"}
        
        container_id = output.strip()
        
        return 200, {
            "ok": True,
            "container_id": container_id,
            "message": "Container created"
        }
    
    def _restart_container(self, container_id: str) -> tuple[int, dict]:
        """POST /api/deploy/containers/:id/restart - Restart container"""
        
        code, output = self._run_docker(["restart", container_id])
        
        if code != 0:
            return 500, {"ok": False, "error": f"Failed to restart: {output}"}
        
        return 200, {
            "ok": True,
            "message": f"Container {container_id} restarted"
        }
    
    def _remove_container(self, container_id: str) -> tuple[int, dict]:
        """DELETE /api/deploy/containers/:id - Remove container"""
        
        code, output = self._run_docker(["rm", "-f", container_id])
        
        if code != 0:
            return 500, {"ok": False, "error": f"Failed to remove: {output}"}
        
        return 200, {
            "ok": True,
            "message": f"Container {container_id} removed"
        }
    
    def _list_images(self) -> tuple[int, dict]:
        """GET /api/deploy/images - List images"""
        
        code, output = self._run_docker(["images", "--format", "{{json .}}"])
        
        if code != 0:
            return 500, {"ok": False, "error": "Failed to list images"}
        
        images = []
        for line in output.strip().split("\n"):
            if line:
                try:
                    images.append(json.loads(line))
                except:
                    pass
        
        return 200, {
            "ok": True,
            "images": images,
            "total": len(images)
        }
    
    def _pull_image(self, data: dict) -> tuple[int, dict]:
        """POST /api/deploy/images/pull - Pull image"""
        
        image = data.get("image")
        
        if not image:
            return 400, {"ok": False, "error": "Image required"}
        
        code, output = self._run_docker(["pull", image])
        
        if code != 0:
            return 500, {"ok": False, "error": f"Failed to pull: {output}"}
        
        return 200, {
            "ok": True,
            "message": f"Image {image} pulled"
        }

def main():
    service = DeployService()
    service.start()

if __name__ == "__main__":
    main()
