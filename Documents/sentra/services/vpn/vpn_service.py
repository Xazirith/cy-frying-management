#!/usr/bin/env python3
"""
VPN Service - WireGuard VPN Management

Handles:
- VPN peer management
- WireGuard configuration
- Connection monitoring
"""
import sys
import os
import json
import time
import subprocess
from typing import Dict, Any, List

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from base_service import SentraService

class VPNService(SentraService):
    """VPN management microservice"""
    
    def __init__(self):
        super().__init__("sentra-vpn", 8086)
        
        # In-memory peer storage (would use DB in production)
        self.peers = {}
        self.status = "idle"
    
    def get_metadata(self) -> Dict[str, Any]:
        return {
            "version": "1.0.0",
            "description": "WireGuard VPN management",
            "endpoints": [
                "GET  /api/vpn/status",
                "GET  /api/vpn/peers",
                "POST /api/vpn/peers",
                "DELETE /api/vpn/peers/:name",
                "POST /api/vpn/start",
                "POST /api/vpn/stop"
            ],
            "provider": "WireGuard"
        }
    
    def handle_request(self, method: str, path: str, headers: dict, body: bytes) -> tuple[int, dict]:
        """Route VPN requests"""
        
        # Parse body
        data = {}
        if body:
            try:
                data = json.loads(body)
            except:
                return 400, {"ok": False, "error": "Invalid JSON"}
        
        # Route handlers
        if path == "/api/vpn/status" and method == "GET":
            return self._get_status()
        
        elif path == "/api/vpn/peers" and method == "GET":
            return self._list_peers()
        
        elif path == "/api/vpn/peers" and method == "POST":
            return self._add_peer(data)
        
        elif path.startswith("/api/vpn/peers/") and method == "DELETE":
            peer_name = path.split("/")[-1]
            return self._remove_peer(peer_name)
        
        elif path == "/api/vpn/start" and method == "POST":
            return self._start_vpn()
        
        elif path == "/api/vpn/stop" and method == "POST":
            return self._stop_vpn()
        
        return 404, {"ok": False, "error": "Not found"}
    
    # ==================== VPN ENDPOINTS ====================
    
    def _get_status(self) -> tuple[int, dict]:
        """GET /api/vpn/status - Get VPN status"""
        
        return 200, {
            "ok": True,
            "status": self.status,
            "peers": len(self.peers),
            "peer_list": list(self.peers.keys())
        }
    
    def _list_peers(self) -> tuple[int, dict]:
        """GET /api/vpn/peers - List all peers"""
        
        peers = []
        for name, info in self.peers.items():
            peers.append({
                "name": name,
                "pubkey": info.get("pubkey", ""),
                "endpoint": info.get("endpoint", ""),
                "allowed_ips": info.get("allowed_ips", []),
                "added_at": info.get("added_at", 0)
            })
        
        return 200, {
            "ok": True,
            "peers": peers,
            "total": len(peers)
        }
    
    def _add_peer(self, data: dict) -> tuple[int, dict]:
        """POST /api/vpn/peers - Add new peer"""
        
        name = data.get("name")
        pubkey = data.get("pubkey")
        
        if not name or not pubkey:
            return 400, {"ok": False, "error": "Missing name or pubkey"}
        
        if name in self.peers:
            return 409, {"ok": False, "error": "Peer already exists"}
        
        # Store peer
        self.peers[name] = {
            "pubkey": pubkey,
            "endpoint": data.get("endpoint", ""),
            "allowed_ips": data.get("allowed_ips", []),
            "added_at": int(time.time())
        }
        
        # TODO: Apply WireGuard configuration
        # self._apply_wg_config()
        
        return 200, {
            "ok": True,
            "message": f"Peer {name} added",
            "peer": self.peers[name]
        }
    
    def _remove_peer(self, peer_name: str) -> tuple[int, dict]:
        """DELETE /api/vpn/peers/:name - Remove peer"""
        
        if peer_name not in self.peers:
            return 404, {"ok": False, "error": "Peer not found"}
        
        del self.peers[peer_name]
        
        # TODO: Update WireGuard configuration
        # self._apply_wg_config()
        
        return 200, {
            "ok": True,
            "message": f"Peer {peer_name} removed"
        }
    
    def _start_vpn(self) -> tuple[int, dict]:
        """POST /api/vpn/start - Start VPN service"""
        
        if self.status == "running":
            return 200, {"ok": True, "message": "VPN already running"}
        
        # TODO: Start WireGuard interface
        # subprocess.run(["wg-quick", "up", "wg0"], check=True)
        
        self.status = "running"
        
        return 200, {
            "ok": True,
            "message": "VPN started",
            "status": self.status
        }
    
    def _stop_vpn(self) -> tuple[int, dict]:
        """POST /api/vpn/stop - Stop VPN service"""
        
        if self.status == "idle":
            return 200, {"ok": True, "message": "VPN already stopped"}
        
        # TODO: Stop WireGuard interface
        # subprocess.run(["wg-quick", "down", "wg0"], check=True)
        
        self.status = "idle"
        
        return 200, {
            "ok": True,
            "message": "VPN stopped",
            "status": self.status
        }
    
    def _apply_wg_config(self):
        """Apply WireGuard configuration (stub)"""
        # TODO: Generate and apply WireGuard config
        pass

def main():
    service = VPNService()
    service.start()

if __name__ == "__main__":
    main()
