#!/usr/bin/env python3
"""
Metrics Service - System metrics and monitoring

Handles:
- CPU, memory, disk metrics
- Service health monitoring
- Performance tracking
"""
import sys
import os
import json
import time
import psutil
from typing import Dict, Any

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from base_service import SentraService

class MetricsService(SentraService):
    """System metrics and monitoring microservice"""
    
    def __init__(self):
        super().__init__("sentra-metrics", 8087)
        self.metrics_history = []
        self.max_history = 1000
    
    def get_metadata(self) -> Dict[str, Any]:
        return {
            "version": "1.0.0",
            "description": "System metrics and performance monitoring",
            "endpoints": [
                "GET /api/metrics/system",
                "GET /api/metrics/cpu",
                "GET /api/metrics/memory",
                "GET /api/metrics/disk",
                "GET /api/metrics/network",
                "GET /api/metrics/history"
            ]
        }
    
    def handle_request(self, method: str, path: str, headers: dict, body: bytes) -> tuple[int, dict]:
        """Route metrics requests"""
        
        if path == "/api/metrics/system" and method == "GET":
            return self._get_system_metrics()
        
        elif path == "/api/metrics/cpu" and method == "GET":
            return self._get_cpu_metrics()
        
        elif path == "/api/metrics/memory" and method == "GET":
            return self._get_memory_metrics()
        
        elif path == "/api/metrics/disk" and method == "GET":
            return self._get_disk_metrics()
        
        elif path == "/api/metrics/network" and method == "GET":
            return self._get_network_metrics()
        
        elif path == "/api/metrics/history" and method == "GET":
            return self._get_history()
        
        return 404, {"ok": False, "error": "Not found"}
    
    # ==================== METRICS ENDPOINTS ====================
    
    def _get_system_metrics(self) -> tuple[int, dict]:
        """GET /api/metrics/system - All system metrics"""
        
        cpu_percent = psutil.cpu_percent(interval=0.1)
        memory = psutil.virtual_memory()
        disk = psutil.disk_usage('/')
        
        metrics = {
            "timestamp": int(time.time()),
            "cpu": {
                "usage_percent": cpu_percent,
                "count": psutil.cpu_count(),
                "count_logical": psutil.cpu_count(logical=True)
            },
            "memory": {
                "total": memory.total,
                "available": memory.available,
                "used": memory.used,
                "percent": memory.percent
            },
            "disk": {
                "total": disk.total,
                "used": disk.used,
                "free": disk.free,
                "percent": disk.percent
            }
        }
        
        # Store in history
        self.metrics_history.append(metrics)
        if len(self.metrics_history) > self.max_history:
            self.metrics_history.pop(0)
        
        return 200, {"ok": True, "metrics": metrics}
    
    def _get_cpu_metrics(self) -> tuple[int, dict]:
        """GET /api/metrics/cpu - CPU metrics"""
        
        cpu_percent = psutil.cpu_percent(interval=0.1)
        cpu_per_core = psutil.cpu_percent(interval=0.1, percpu=True)
        cpu_times = psutil.cpu_times()
        
        return 200, {
            "ok": True,
            "cpu": {
                "usage_percent": cpu_percent,
                "per_core": cpu_per_core,
                "count": psutil.cpu_count(),
                "count_logical": psutil.cpu_count(logical=True),
                "times": {
                    "user": cpu_times.user,
                    "system": cpu_times.system,
                    "idle": cpu_times.idle
                }
            }
        }
    
    def _get_memory_metrics(self) -> tuple[int, dict]:
        """GET /api/metrics/memory - Memory metrics"""
        
        memory = psutil.virtual_memory()
        swap = psutil.swap_memory()
        
        return 200, {
            "ok": True,
            "memory": {
                "total": memory.total,
                "available": memory.available,
                "used": memory.used,
                "free": memory.free,
                "percent": memory.percent,
                "buffers": getattr(memory, 'buffers', 0),
                "cached": getattr(memory, 'cached', 0)
            },
            "swap": {
                "total": swap.total,
                "used": swap.used,
                "free": swap.free,
                "percent": swap.percent
            }
        }
    
    def _get_disk_metrics(self) -> tuple[int, dict]:
        """GET /api/metrics/disk - Disk metrics"""
        
        partitions = []
        for partition in psutil.disk_partitions():
            try:
                usage = psutil.disk_usage(partition.mountpoint)
                partitions.append({
                    "device": partition.device,
                    "mountpoint": partition.mountpoint,
                    "fstype": partition.fstype,
                    "total": usage.total,
                    "used": usage.used,
                    "free": usage.free,
                    "percent": usage.percent
                })
            except:
                pass
        
        disk_io = psutil.disk_io_counters()
        
        return 200, {
            "ok": True,
            "partitions": partitions,
            "io": {
                "read_count": disk_io.read_count,
                "write_count": disk_io.write_count,
                "read_bytes": disk_io.read_bytes,
                "write_bytes": disk_io.write_bytes
            } if disk_io else {}
        }
    
    def _get_network_metrics(self) -> tuple[int, dict]:
        """GET /api/metrics/network - Network metrics"""
        
        net_io = psutil.net_io_counters()
        connections = len(psutil.net_connections())
        
        return 200, {
            "ok": True,
            "network": {
                "bytes_sent": net_io.bytes_sent,
                "bytes_recv": net_io.bytes_recv,
                "packets_sent": net_io.packets_sent,
                "packets_recv": net_io.packets_recv,
                "errors_in": net_io.errin,
                "errors_out": net_io.errout,
                "connections": connections
            }
        }
    
    def _get_history(self) -> tuple[int, dict]:
        """GET /api/metrics/history - Metrics history"""
        
        return 200, {
            "ok": True,
            "history": self.metrics_history[-100:],  # Last 100 entries
            "total": len(self.metrics_history)
        }

def main():
    service = MetricsService()
    service.start()

if __name__ == "__main__":
    main()
