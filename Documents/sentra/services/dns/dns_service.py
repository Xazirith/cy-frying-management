#!/usr/bin/env python3
"""
DNS Service - Cloudflare DNS Management

Handles:
- DNS record creation/update/deletion
- Zone management
- Cloudflare API integration
"""
import sys
import os
import json
import time
import urllib.request
import urllib.error
from typing import Dict, Any, List, Optional

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from base_service import SentraService

class DNSService(SentraService):
    """DNS management microservice with Cloudflare integration"""
    
    def __init__(self):
        super().__init__("sentra-dns", 8084)
        
        # Cloudflare credentials from environment
        self.cf_token = os.getenv('CF_API_TOKEN', '')
        self.cf_zone_id = os.getenv('CF_ZONE_ID', '')
        
        # Cache
        self.records_cache = {}
        self.cache_time = 0
        self.cache_ttl = 300  # 5 minutes
    
    def get_metadata(self) -> Dict[str, Any]:
        return {
            "version": "1.0.0",
            "description": "DNS management with Cloudflare integration",
            "endpoints": [
                "GET  /api/dns/records",
                "POST /api/dns/records",
                "PUT  /api/dns/records/:id",
                "DELETE /api/dns/records/:id",
                "GET  /api/dns/zones"
            ],
            "provider": "Cloudflare"
        }
    
    def handle_request(self, method: str, path: str, headers: dict, body: bytes) -> tuple[int, dict]:
        """Route DNS requests"""
        
        # Parse body
        data = {}
        if body:
            try:
                data = json.loads(body)
            except:
                return 400, {"ok": False, "error": "Invalid JSON"}
        
        # Route handlers
        if path == "/api/dns/records" and method == "GET":
            return self._list_records()
        
        elif path == "/api/dns/records" and method == "POST":
            return self._create_record(data)
        
        elif path.startswith("/api/dns/records/") and method == "PUT":
            record_id = path.split("/")[-1]
            return self._update_record(record_id, data)
        
        elif path.startswith("/api/dns/records/") and method == "DELETE":
            record_id = path.split("/")[-1]
            return self._delete_record(record_id)
        
        elif path == "/api/dns/zones" and method == "GET":
            return self._get_zones()
        
        return 404, {"ok": False, "error": "Not found"}
    
    # ==================== CLOUDFLARE API ====================
    
    def _cf_request(self, endpoint: str, method: str = "GET", data: dict = None) -> Optional[dict]:
        """Make Cloudflare API request"""
        if not self.cf_token:
            return None
        
        url = f"https://api.cloudflare.com/client/v4{endpoint}"
        
        headers = {
            "Authorization": f"Bearer {self.cf_token}",
            "Content-Type": "application/json"
        }
        
        try:
            if method == "GET":
                req = urllib.request.Request(url, headers=headers)
            else:
                body = json.dumps(data).encode() if data else b''
                req = urllib.request.Request(url, data=body, headers=headers, method=method)
            
            with urllib.request.urlopen(req, timeout=10) as response:
                return json.loads(response.read())
        
        except Exception as e:
            print(f"⚠️  Cloudflare API error: {e}")
            return None
    
    # ==================== DNS ENDPOINTS ====================
    
    def _list_records(self) -> tuple[int, dict]:
        """GET /api/dns/records - List all DNS records"""
        
        # Check cache
        if time.time() - self.cache_time < self.cache_ttl and self.records_cache:
            return 200, {
                "ok": True,
                "records": self.records_cache,
                "cached": True
            }
        
        if not self.cf_zone_id:
            return 400, {"ok": False, "error": "Zone ID not configured"}
        
        # Fetch from Cloudflare
        result = self._cf_request(f"/zones/{self.cf_zone_id}/dns_records")
        
        if not result or not result.get("success"):
            return 500, {"ok": False, "error": "Failed to fetch records"}
        
        records = result.get("result", [])
        
        # Cache results
        self.records_cache = records
        self.cache_time = time.time()
        
        return 200, {
            "ok": True,
            "records": records,
            "total": len(records)
        }
    
    def _create_record(self, data: dict) -> tuple[int, dict]:
        """POST /api/dns/records - Create DNS record"""
        
        if not self.cf_zone_id:
            return 400, {"ok": False, "error": "Zone ID not configured"}
        
        # Validate required fields
        record_type = data.get("type")
        name = data.get("name")
        content = data.get("content")
        
        if not record_type or not name or not content:
            return 400, {"ok": False, "error": "Missing required fields: type, name, content"}
        
        # Create record via Cloudflare API
        record_data = {
            "type": record_type,
            "name": name,
            "content": content,
            "ttl": data.get("ttl", 1),  # 1 = auto
            "proxied": data.get("proxied", False)
        }
        
        result = self._cf_request(
            f"/zones/{self.cf_zone_id}/dns_records",
            method="POST",
            data=record_data
        )
        
        if not result or not result.get("success"):
            error = result.get("errors", [{}])[0].get("message", "Unknown error") if result else "API request failed"
            return 500, {"ok": False, "error": error}
        
        # Clear cache
        self.cache_time = 0
        
        return 200, {
            "ok": True,
            "record": result.get("result", {})
        }
    
    def _update_record(self, record_id: str, data: dict) -> tuple[int, dict]:
        """PUT /api/dns/records/:id - Update DNS record"""
        
        if not self.cf_zone_id:
            return 400, {"ok": False, "error": "Zone ID not configured"}
        
        # Build update data
        update_data = {}
        if "type" in data:
            update_data["type"] = data["type"]
        if "name" in data:
            update_data["name"] = data["name"]
        if "content" in data:
            update_data["content"] = data["content"]
        if "ttl" in data:
            update_data["ttl"] = data["ttl"]
        if "proxied" in data:
            update_data["proxied"] = data["proxied"]
        
        if not update_data:
            return 400, {"ok": False, "error": "No update fields provided"}
        
        result = self._cf_request(
            f"/zones/{self.cf_zone_id}/dns_records/{record_id}",
            method="PUT",
            data=update_data
        )
        
        if not result or not result.get("success"):
            error = result.get("errors", [{}])[0].get("message", "Unknown error") if result else "API request failed"
            return 500, {"ok": False, "error": error}
        
        # Clear cache
        self.cache_time = 0
        
        return 200, {
            "ok": True,
            "record": result.get("result", {})
        }
    
    def _delete_record(self, record_id: str) -> tuple[int, dict]:
        """DELETE /api/dns/records/:id - Delete DNS record"""
        
        if not self.cf_zone_id:
            return 400, {"ok": False, "error": "Zone ID not configured"}
        
        result = self._cf_request(
            f"/zones/{self.cf_zone_id}/dns_records/{record_id}",
            method="DELETE"
        )
        
        if not result or not result.get("success"):
            error = result.get("errors", [{}])[0].get("message", "Unknown error") if result else "API request failed"
            return 500, {"ok": False, "error": error}
        
        # Clear cache
        self.cache_time = 0
        
        return 200, {"ok": True, "message": "Record deleted"}
    
    def _get_zones(self) -> tuple[int, dict]:
        """GET /api/dns/zones - List all zones"""
        
        result = self._cf_request("/zones")
        
        if not result or not result.get("success"):
            return 500, {"ok": False, "error": "Failed to fetch zones"}
        
        zones = result.get("result", [])
        
        return 200, {
            "ok": True,
            "zones": zones,
            "total": len(zones)
        }

def main():
    service = DNSService()
    service.start()

if __name__ == "__main__":
    main()
