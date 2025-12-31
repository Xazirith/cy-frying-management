#!/usr/bin/env python3
"""
Telemetry Service - Event logging and analytics

Handles:
- Event logging
- Analytics tracking
- Activity monitoring
"""
import sys
import os
import json
import time
import sqlite3
from typing import Dict, Any, List
from pathlib import Path

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from base_service import SentraService

class TelemetryService(SentraService):
    """Telemetry and analytics microservice"""
    
    def __init__(self):
        super().__init__("sentra-telemetry", 8085)
        
        # Database path
        self.db_path = Path(os.getenv('DATA_DIR', '/data')) / "telemetry.db"
        self.db_path.parent.mkdir(parents=True, exist_ok=True)
        
        # Initialize database
        self._init_db()
    
    def get_metadata(self) -> Dict[str, Any]:
        return {
            "version": "1.0.0",
            "description": "Event logging and analytics tracking",
            "endpoints": [
                "POST /api/telemetry/log",
                "GET  /api/telemetry/events",
                "GET  /api/telemetry/stats",
                "DELETE /api/telemetry/clear"
            ]
        }
    
    def handle_request(self, method: str, path: str, headers: dict, body: bytes) -> tuple[int, dict]:
        """Route telemetry requests"""
        
        # Parse body
        data = {}
        if body:
            try:
                data = json.loads(body)
            except:
                return 400, {"ok": False, "error": "Invalid JSON"}
        
        # Route handlers
        if path == "/api/telemetry/log" and method == "POST":
            return self._log_event(data)
        
        elif path == "/api/telemetry/events" and method == "GET":
            return self._get_events()
        
        elif path == "/api/telemetry/stats" and method == "GET":
            return self._get_stats()
        
        elif path == "/api/telemetry/clear" and method == "DELETE":
            return self._clear_events()
        
        return 404, {"ok": False, "error": "Not found"}
    
    # ==================== DATABASE ====================
    
    def _init_db(self):
        """Initialize telemetry database"""
        with sqlite3.connect(self.db_path) as conn:
            conn.execute("""
                CREATE TABLE IF NOT EXISTS telemetry (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    ts INTEGER NOT NULL,
                    kind TEXT NOT NULL,
                    src TEXT NOT NULL,
                    payload TEXT NOT NULL
                )
            """)
            conn.execute("CREATE INDEX IF NOT EXISTS idx_ts ON telemetry(ts)")
            conn.execute("CREATE INDEX IF NOT EXISTS idx_kind ON telemetry(kind)")
            conn.commit()
    
    def _get_conn(self):
        """Get database connection"""
        return sqlite3.connect(self.db_path)
    
    # ==================== TELEMETRY ENDPOINTS ====================
    
    def _log_event(self, data: dict) -> tuple[int, dict]:
        """POST /api/telemetry/log - Log an event"""
        
        kind = data.get("kind", "event")
        src = data.get("src", "unknown")
        event_data = data.get("data", {})
        
        # Store in database
        with self._get_conn() as conn:
            conn.execute(
                "INSERT INTO telemetry(ts, kind, src, payload) VALUES(?, ?, ?, ?)",
                (int(time.time()), kind, src, json.dumps(event_data))
            )
            conn.commit()
        
        # Publish to core (if needed)
        # self.call_core("POST", "/api/events/publish", {
        #     "channel": "telemetry/events",
        #     "data": {"kind": kind, "src": src}
        # })
        
        return 200, {"ok": True, "message": "Event logged"}
    
    def _get_events(self) -> tuple[int, dict]:
        """GET /api/telemetry/events - Get recent events"""
        
        limit = 100
        
        with self._get_conn() as conn:
            cursor = conn.execute(
                "SELECT id, ts, kind, src, payload FROM telemetry ORDER BY ts DESC LIMIT ?",
                (limit,)
            )
            
            events = []
            for row in cursor.fetchall():
                events.append({
                    "id": row[0],
                    "timestamp": row[1],
                    "kind": row[2],
                    "source": row[3],
                    "data": json.loads(row[4])
                })
        
        return 200, {
            "ok": True,
            "events": events,
            "total": len(events)
        }
    
    def _get_stats(self) -> tuple[int, dict]:
        """GET /api/telemetry/stats - Get analytics stats"""
        
        with self._get_conn() as conn:
            # Total events
            cursor = conn.execute("SELECT COUNT(*) FROM telemetry")
            total = cursor.fetchone()[0]
            
            # Events by kind
            cursor = conn.execute(
                "SELECT kind, COUNT(*) FROM telemetry GROUP BY kind ORDER BY COUNT(*) DESC"
            )
            by_kind = {row[0]: row[1] for row in cursor.fetchall()}
            
            # Events by source
            cursor = conn.execute(
                "SELECT src, COUNT(*) FROM telemetry GROUP BY src ORDER BY COUNT(*) DESC LIMIT 10"
            )
            by_source = {row[0]: row[1] for row in cursor.fetchall()}
            
            # Recent activity (last 24 hours)
            day_ago = int(time.time()) - 86400
            cursor = conn.execute(
                "SELECT COUNT(*) FROM telemetry WHERE ts > ?",
                (day_ago,)
            )
            last_24h = cursor.fetchone()[0]
        
        return 200, {
            "ok": True,
            "stats": {
                "total_events": total,
                "last_24h": last_24h,
                "by_kind": by_kind,
                "by_source": by_source
            }
        }
    
    def _clear_events(self) -> tuple[int, dict]:
        """DELETE /api/telemetry/clear - Clear old events"""
        
        # Keep last 7 days
        cutoff = int(time.time()) - (7 * 86400)
        
        with self._get_conn() as conn:
            cursor = conn.execute("DELETE FROM telemetry WHERE ts < ?", (cutoff,))
            deleted = cursor.rowcount
            conn.commit()
        
        return 200, {
            "ok": True,
            "message": f"Deleted {deleted} old events"
        }

def main():
    service = TelemetryService()
    service.start()

if __name__ == "__main__":
    main()
