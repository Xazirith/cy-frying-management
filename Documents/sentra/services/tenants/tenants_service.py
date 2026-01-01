#!/usr/bin/env python3
"""
Tenants Service - Multi-tenant manager for websites and webapps.

Handles:
- Tenant CRUD (multi-tenant sites)
- App CRUD per tenant
- Domain resolution for tenant/app routing
- Tenant modules (gallery, services, testimonials, etc.)
"""
import sys
import os
import json
import time
import uuid
import sqlite3
import re
from typing import Dict, Any, Optional, List
from urllib.parse import urlparse, parse_qs

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from base_service import SentraService

# Import tenant modules
from modules import (
    gallery, services, testimonials, contact, packages, settings,
    jobs, messaging, clients, quotes, staff, get_tenant_db
)

SCHEMA = """
CREATE TABLE IF NOT EXISTS tenants (
  tenant_id TEXT PRIMARY KEY,
  name TEXT NOT NULL,
  slug TEXT UNIQUE NOT NULL,
  domain TEXT,
  owner_user_id TEXT,
  status TEXT DEFAULT 'active',
  metadata TEXT,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS apps (
  app_id TEXT PRIMARY KEY,
  tenant_id TEXT NOT NULL,
  name TEXT NOT NULL,
  slug TEXT NOT NULL,
  type TEXT,
  domain TEXT,
  status TEXT DEFAULT 'active',
  config TEXT,
  metadata TEXT,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  UNIQUE(tenant_id, slug),
  FOREIGN KEY(tenant_id) REFERENCES tenants(tenant_id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_tenants_domain ON tenants(domain);
CREATE INDEX IF NOT EXISTS idx_apps_tenant ON apps(tenant_id);
CREATE INDEX IF NOT EXISTS idx_apps_domain ON apps(domain);
"""


class TenantsService(SentraService):
    """Multi-tenant service for websites and webapps."""

    def __init__(self):
        super().__init__("sentra-tenants", 8088)
        self.db_path = os.getenv("TENANTS_DB_PATH", "/data/tenants.db")
        self._ensure_schema()
        print("🏢 Sentra Tenants service initialized")

    def get_metadata(self) -> Dict[str, Any]:
        return {
            "version": "1.0.0",
            "description": "Multi-tenant manager for websites and webapps",
            "endpoints": [
                "GET  /api/tenants",
                "POST /api/tenants",
                "GET  /api/tenants/:id",
                "PUT  /api/tenants/:id",
                "DELETE /api/tenants/:id",
                "GET  /api/tenants/:id/apps",
                "POST /api/tenants/:id/apps",
                "GET  /api/apps",
                "GET  /api/apps/:id",
                "PUT  /api/apps/:id",
                "DELETE /api/apps/:id",
                "GET  /api/tenants/resolve?host=example.com",
                "GET  /api/apps/resolve?host=example.com",
                # Module endpoints
                "GET/POST    /api/tenants/:id/gallery",
                "GET/PUT/DEL /api/tenants/:id/gallery/:item_id",
                "GET/POST    /api/tenants/:id/services",
                "GET/PUT/DEL /api/tenants/:id/services/:svc_id",
                "GET/POST    /api/tenants/:id/testimonials",
                "GET/PUT/DEL /api/tenants/:id/testimonials/:test_id",
                "GET/POST    /api/tenants/:id/contact",
                "GET/DEL     /api/tenants/:id/contact/:contact_id",
                "GET/POST    /api/tenants/:id/packages",
                "GET/PUT/DEL /api/tenants/:id/packages/:pkg_id",
                "GET/POST    /api/tenants/:id/settings",
                "PUT/DEL     /api/tenants/:id/settings/:key",
                "GET/POST    /api/tenants/:id/jobs",
                "GET/PUT/DEL /api/tenants/:id/jobs/:job_id",
                "GET/POST    /api/tenants/:id/messages",
                "GET/PUT/DEL /api/tenants/:id/messages/:msg_id",
                "GET/POST    /api/tenants/:id/clients",
                "GET/PUT/DEL /api/tenants/:id/clients/:client_id",
                "GET/POST    /api/tenants/:id/quotes",
                "GET/PUT/DEL /api/tenants/:id/quotes/:quote_id",
                "GET/POST    /api/tenants/:id/staff",
                "GET/PUT/DEL /api/tenants/:id/staff/:staff_id",
            ],
        }

    def handle_request(self, method: str, path: str, headers: dict, body: bytes) -> tuple[int, dict]:
        parsed = urlparse(path)
        route = parsed.path
        query = parse_qs(parsed.query)

        data = {}
        if body:
            try:
                data = json.loads(body)
            except Exception:
                return 400, {"ok": False, "error": "invalid_json"}

        # Resolve endpoints
        if route == "/api/tenants/resolve" and method == "GET":
            return self._resolve_tenant(query)
        if route == "/api/apps/resolve" and method == "GET":
            return self._resolve_app(query)

        # Tenant endpoints
        if route == "/api/tenants" and method == "GET":
            return self._list_tenants(query)
        if route == "/api/tenants" and method == "POST":
            return self._create_tenant(data)

        if route.startswith("/api/tenants/"):
            parts = route.strip("/").split("/")
            if len(parts) == 3:
                tenant_id = parts[2]
                if method == "GET":
                    return self._get_tenant(tenant_id)
                if method == "PUT":
                    return self._update_tenant(tenant_id, data)
                if method == "DELETE":
                    return self._delete_tenant(tenant_id)
            if len(parts) == 4:
                tenant_id = parts[2]
                module_name = parts[3]
                
                # Handle tenant apps
                if module_name == "apps":
                    if method == "GET":
                        return self._list_apps(query, tenant_id=tenant_id)
                    if method == "POST":
                        return self._create_app(tenant_id, data)
                
                # Handle tenant modules
                return self._handle_module_route(tenant_id, module_name, None, method, query, data)
            
            if len(parts) == 5:
                tenant_id = parts[2]
                module_name = parts[3]
                item_id = parts[4]
                return self._handle_module_route(tenant_id, module_name, item_id, method, query, data)

        # App endpoints
        if route == "/api/apps" and method == "GET":
            return self._list_apps(query)

        if route.startswith("/api/apps/"):
            parts = route.strip("/").split("/")
            if len(parts) == 3:
                app_id = parts[2]
                if method == "GET":
                    return self._get_app(app_id)
                if method == "PUT":
                    return self._update_app(app_id, data)
                if method == "DELETE":
                    return self._delete_app(app_id)

        return 404, {"ok": False, "error": "not_found"}

    # ==================== DB HELPERS ====================

    def _ensure_schema(self) -> None:
        with self._db_connect() as conn:
            conn.executescript(SCHEMA)
            conn.commit()

    def _db_connect(self) -> sqlite3.Connection:
        conn = sqlite3.connect(self.db_path)
        conn.row_factory = sqlite3.Row
        conn.execute("PRAGMA foreign_keys = ON")
        return conn

    def _now(self) -> int:
        return int(time.time())

    def _slugify(self, value: str) -> str:
        slug = re.sub(r"[^a-z0-9]+", "-", value.lower()).strip("-")
        return slug

    def _json_dump(self, value: Any) -> str:
        if value is None:
            return "{}"
        return json.dumps(value)

    def _json_load(self, value: Optional[str]) -> Any:
        if not value:
            return {}
        try:
            return json.loads(value)
        except Exception:
            return {}

    def _tenant_row(self, row: sqlite3.Row) -> Dict[str, Any]:
        return {
            "tenant_id": row["tenant_id"],
            "name": row["name"],
            "slug": row["slug"],
            "domain": row["domain"],
            "owner_user_id": row["owner_user_id"],
            "status": row["status"],
            "metadata": self._json_load(row["metadata"]),
            "created_at": row["created_at"],
            "updated_at": row["updated_at"],
        }

    def _app_row(self, row: sqlite3.Row) -> Dict[str, Any]:
        return {
            "app_id": row["app_id"],
            "tenant_id": row["tenant_id"],
            "name": row["name"],
            "slug": row["slug"],
            "type": row["type"],
            "domain": row["domain"],
            "status": row["status"],
            "config": self._json_load(row["config"]),
            "metadata": self._json_load(row["metadata"]),
            "created_at": row["created_at"],
            "updated_at": row["updated_at"],
        }

    # ==================== TENANT ENDPOINTS ====================

    def _list_tenants(self, query: Dict[str, List[str]]) -> tuple[int, dict]:
        status = (query.get("status") or [None])[0]
        with self._db_connect() as conn:
            if status:
                rows = conn.execute("SELECT * FROM tenants WHERE status=? ORDER BY created_at DESC", (status,)).fetchall()
            else:
                rows = conn.execute("SELECT * FROM tenants ORDER BY created_at DESC").fetchall()
        tenants = [self._tenant_row(row) for row in rows]
        return 200, {"ok": True, "tenants": tenants, "total": len(tenants)}

    def _get_tenant(self, tenant_id: str) -> tuple[int, dict]:
        with self._db_connect() as conn:
            row = conn.execute("SELECT * FROM tenants WHERE tenant_id=?", (tenant_id,)).fetchone()
        if not row:
            return 404, {"ok": False, "error": "tenant_not_found"}
        return 200, {"ok": True, "tenant": self._tenant_row(row)}

    def _create_tenant(self, data: dict) -> tuple[int, dict]:
        name = (data.get("name") or "").strip()
        slug = (data.get("slug") or "").strip()
        if not name:
            return 400, {"ok": False, "error": "missing_name"}
        if not slug:
            slug = self._slugify(name)
        if not slug:
            return 400, {"ok": False, "error": "invalid_slug"}

        tenant_id = (data.get("tenant_id") or uuid.uuid4().hex).strip()
        now_ts = self._now()
        tenant = {
            "tenant_id": tenant_id,
            "name": name,
            "slug": slug,
            "domain": (data.get("domain") or "").strip() or None,
            "owner_user_id": (data.get("owner_user_id") or "").strip() or None,
            "status": (data.get("status") or "active").strip(),
            "metadata": data.get("metadata") or {},
            "created_at": now_ts,
            "updated_at": now_ts,
        }

        try:
            with self._db_connect() as conn:
                conn.execute(
                    """INSERT INTO tenants
                       (tenant_id, name, slug, domain, owner_user_id, status, metadata, created_at, updated_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)""",
                    (
                        tenant["tenant_id"],
                        tenant["name"],
                        tenant["slug"],
                        tenant["domain"],
                        tenant["owner_user_id"],
                        tenant["status"],
                        self._json_dump(tenant["metadata"]),
                        tenant["created_at"],
                        tenant["updated_at"],
                    ),
                )
                conn.commit()
        except sqlite3.IntegrityError as e:
            return 409, {"ok": False, "error": "tenant_conflict", "detail": str(e)}

        return 200, {"ok": True, "tenant": tenant}

    def _update_tenant(self, tenant_id: str, data: dict) -> tuple[int, dict]:
        fields = {}
        for key in ("name", "slug", "domain", "owner_user_id", "status", "metadata"):
            if key in data:
                fields[key] = data[key]

        if not fields:
            return 400, {"ok": False, "error": "no_updates"}

        updates = []
        params = []
        for key, value in fields.items():
            if key == "metadata":
                value = self._json_dump(value)
            if key == "slug":
                value = (value or "").strip()
                if not value:
                    return 400, {"ok": False, "error": "invalid_slug"}
            if key == "name":
                value = (value or "").strip()
                if not value:
                    return 400, {"ok": False, "error": "invalid_name"}
            if key in ("domain", "owner_user_id", "status"):
                value = (value or "").strip() or None
            updates.append(f"{key}=?")
            params.append(value)

        updates.append("updated_at=?")
        params.append(self._now())
        params.append(tenant_id)

        try:
            with self._db_connect() as conn:
                cur = conn.execute(f"UPDATE tenants SET {', '.join(updates)} WHERE tenant_id=?", params)
                conn.commit()
                if cur.rowcount == 0:
                    return 404, {"ok": False, "error": "tenant_not_found"}
        except sqlite3.IntegrityError as e:
            return 409, {"ok": False, "error": "tenant_conflict", "detail": str(e)}

        return self._get_tenant(tenant_id)

    def _delete_tenant(self, tenant_id: str) -> tuple[int, dict]:
        with self._db_connect() as conn:
            cur = conn.execute("DELETE FROM tenants WHERE tenant_id=?", (tenant_id,))
            conn.commit()
        if cur.rowcount == 0:
            return 404, {"ok": False, "error": "tenant_not_found"}
        return 200, {"ok": True, "deleted": tenant_id}

    # ==================== APP ENDPOINTS ====================

    def _list_apps(self, query: Dict[str, List[str]], tenant_id: Optional[str] = None) -> tuple[int, dict]:
        if tenant_id is None:
            tenant_id = (query.get("tenant_id") or [None])[0]
        status = (query.get("status") or [None])[0]
        app_type = (query.get("type") or [None])[0]

        conditions = []
        params = []
        if tenant_id:
            conditions.append("tenant_id=?")
            params.append(tenant_id)
        if status:
            conditions.append("status=?")
            params.append(status)
        if app_type:
            conditions.append("type=?")
            params.append(app_type)

        where = f"WHERE {' AND '.join(conditions)}" if conditions else ""
        with self._db_connect() as conn:
            rows = conn.execute(f"SELECT * FROM apps {where} ORDER BY created_at DESC", params).fetchall()
        apps = [self._app_row(row) for row in rows]
        return 200, {"ok": True, "apps": apps, "total": len(apps)}

    def _get_app(self, app_id: str) -> tuple[int, dict]:
        with self._db_connect() as conn:
            row = conn.execute("SELECT * FROM apps WHERE app_id=?", (app_id,)).fetchone()
        if not row:
            return 404, {"ok": False, "error": "app_not_found"}
        return 200, {"ok": True, "app": self._app_row(row)}

    def _create_app(self, tenant_id: str, data: dict) -> tuple[int, dict]:
        name = (data.get("name") or "").strip()
        slug = (data.get("slug") or "").strip()
        if not name:
            return 400, {"ok": False, "error": "missing_name"}
        if not slug:
            slug = self._slugify(name)
        if not slug:
            return 400, {"ok": False, "error": "invalid_slug"}

        with self._db_connect() as conn:
            tenant_row = conn.execute("SELECT tenant_id FROM tenants WHERE tenant_id=?", (tenant_id,)).fetchone()
        if not tenant_row:
            return 404, {"ok": False, "error": "tenant_not_found"}

        app_id = (data.get("app_id") or uuid.uuid4().hex).strip()
        now_ts = self._now()
        app = {
            "app_id": app_id,
            "tenant_id": tenant_id,
            "name": name,
            "slug": slug,
            "type": (data.get("type") or "webapp").strip(),
            "domain": (data.get("domain") or "").strip() or None,
            "status": (data.get("status") or "active").strip(),
            "config": data.get("config") or {},
            "metadata": data.get("metadata") or {},
            "created_at": now_ts,
            "updated_at": now_ts,
        }

        try:
            with self._db_connect() as conn:
                conn.execute(
                    """INSERT INTO apps
                       (app_id, tenant_id, name, slug, type, domain, status, config, metadata, created_at, updated_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)""",
                    (
                        app["app_id"],
                        app["tenant_id"],
                        app["name"],
                        app["slug"],
                        app["type"],
                        app["domain"],
                        app["status"],
                        self._json_dump(app["config"]),
                        self._json_dump(app["metadata"]),
                        app["created_at"],
                        app["updated_at"],
                    ),
                )
                conn.commit()
        except sqlite3.IntegrityError as e:
            return 409, {"ok": False, "error": "app_conflict", "detail": str(e)}

        return 200, {"ok": True, "app": app}

    def _update_app(self, app_id: str, data: dict) -> tuple[int, dict]:
        fields = {}
        for key in ("name", "slug", "type", "domain", "status", "config", "metadata"):
            if key in data:
                fields[key] = data[key]

        if not fields:
            return 400, {"ok": False, "error": "no_updates"}

        updates = []
        params = []
        for key, value in fields.items():
            if key in ("metadata", "config"):
                value = self._json_dump(value)
            if key == "slug":
                value = (value or "").strip()
                if not value:
                    return 400, {"ok": False, "error": "invalid_slug"}
            if key == "name":
                value = (value or "").strip()
                if not value:
                    return 400, {"ok": False, "error": "invalid_name"}
            if key in ("type", "domain", "status"):
                value = (value or "").strip() or None
            updates.append(f"{key}=?")
            params.append(value)

        updates.append("updated_at=?")
        params.append(self._now())
        params.append(app_id)

        try:
            with self._db_connect() as conn:
                cur = conn.execute(f"UPDATE apps SET {', '.join(updates)} WHERE app_id=?", params)
                conn.commit()
                if cur.rowcount == 0:
                    return 404, {"ok": False, "error": "app_not_found"}
        except sqlite3.IntegrityError as e:
            return 409, {"ok": False, "error": "app_conflict", "detail": str(e)}

        return self._get_app(app_id)

    def _delete_app(self, app_id: str) -> tuple[int, dict]:
        with self._db_connect() as conn:
            cur = conn.execute("DELETE FROM apps WHERE app_id=?", (app_id,))
            conn.commit()
        if cur.rowcount == 0:
            return 404, {"ok": False, "error": "app_not_found"}
        return 200, {"ok": True, "deleted": app_id}

    # ==================== RESOLUTION ====================

    def _handle_module_route(self, tenant_id: str, module_name: str, item_id: Optional[str], method: str, query: Dict, data: dict) -> tuple[int, dict]:
        """Handle all tenant module routes dynamically"""
        
        # Verify tenant exists
        with self._db_connect() as conn:
            tenant_row = conn.execute("SELECT * FROM tenants WHERE tenant_id=?", (tenant_id,)).fetchone()
        if not tenant_row:
            return 404, {"ok": False, "error": "tenant_not_found"}
        
        tenant = self._tenant_row(tenant_row)
        tenant_config = tenant.get("metadata", {})
        
        # Get tenant-specific database connection
        db_conn = get_tenant_db(tenant_id, tenant_config)
        
        try:
            # Route to appropriate module handler
            if module_name == "gallery":
                return self._handle_gallery(db_conn, tenant_id, item_id, method, query, data)
            elif module_name == "services":
                return self._handle_services(db_conn, tenant_id, item_id, method, query, data)
            elif module_name == "testimonials":
                return self._handle_testimonials(db_conn, tenant_id, item_id, method, query, data)
            elif module_name == "contact":
                return self._handle_contact(db_conn, tenant_id, item_id, method, query, data)
            elif module_name == "packages":
                return self._handle_packages(db_conn, tenant_id, item_id, method, query, data)
            elif module_name == "settings":
                return self._handle_settings(db_conn, tenant_id, item_id, method, query, data)
            elif module_name == "jobs":
                return self._handle_jobs(db_conn, tenant_id, item_id, method, query, data)
            elif module_name == "messages":
                return self._handle_messages(db_conn, tenant_id, item_id, method, query, data)
            elif module_name == "clients":
                return self._handle_clients(db_conn, tenant_id, item_id, method, query, data)
            elif module_name == "quotes":
                return self._handle_quotes(db_conn, tenant_id, item_id, method, query, data)
            elif module_name == "staff":
                return self._handle_staff(db_conn, tenant_id, item_id, method, query, data)
            else:
                return 404, {"ok": False, "error": "module_not_found"}
        finally:
            db_conn.close()
    
    # ==================== MODULE HANDLERS ====================
    
    def _handle_gallery(self, db_conn, tenant_id: str, item_id: Optional[str], method: str, query: Dict, data: dict) -> tuple[int, dict]:
        """Gallery module endpoints"""
        if item_id is None:
            if method == "GET":
                tag = (query.get("tag") or [None])[0]
                featured = (query.get("featured") or [None])[0]
                page = int((query.get("page") or [1])[0])
                per_page = int((query.get("per_page") or [50])[0])
                result = gallery.get_all(db_conn, tenant_id, tag=tag, featured=featured == 'true', page=page, per_page=per_page)
                return 200, {"ok": True, **result}
            elif method == "POST":
                result = gallery.create_item(db_conn, tenant_id, **data)
                return 200, {"ok": True, "item": result}
        else:
            if method == "GET":
                result = gallery.get_by_id(db_conn, tenant_id, int(item_id))
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "PUT":
                result = gallery.update_item(db_conn, tenant_id, int(item_id), **data)
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "DELETE":
                gallery.delete_item(db_conn, tenant_id, int(item_id))
                return 200, {"ok": True, "deleted": item_id}
        return 405, {"ok": False, "error": "method_not_allowed"}
    
    def _handle_services(self, db_conn, tenant_id: str, item_id: Optional[str], method: str, query: Dict, data: dict) -> tuple[int, dict]:
        """Services module endpoints"""
        if item_id is None:
            if method == "GET":
                active_only = (query.get("active") or ['true'])[0] == 'true'
                result = services.get_all(db_conn, tenant_id, active_only=active_only)
                return 200, {"ok": True, "items": result}
            elif method == "POST":
                result = services.create(db_conn, tenant_id, **data)
                return 200, {"ok": True, "item": result}
        else:
            if method == "GET":
                result = services.get_by_id(db_conn, tenant_id, int(item_id))
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "PUT":
                result = services.update(db_conn, tenant_id, int(item_id), **data)
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "DELETE":
                services.delete(db_conn, tenant_id, int(item_id))
                return 200, {"ok": True, "deleted": item_id}
        return 405, {"ok": False, "error": "method_not_allowed"}
    
    def _handle_testimonials(self, db_conn, tenant_id: str, item_id: Optional[str], method: str, query: Dict, data: dict) -> tuple[int, dict]:
        """Testimonials module endpoints"""
        if item_id is None:
            if method == "GET":
                active_only = (query.get("active") or ['true'])[0] == 'true'
                result = testimonials.get_all(db_conn, tenant_id, active_only=active_only)
                return 200, {"ok": True, "items": result}
            elif method == "POST":
                result = testimonials.create(db_conn, tenant_id, **data)
                return 200, {"ok": True, "item": result}
        else:
            if method == "GET":
                result = testimonials.get_by_id(db_conn, tenant_id, int(item_id))
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "PUT":
                result = testimonials.update(db_conn, tenant_id, int(item_id), **data)
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "DELETE":
                testimonials.delete(db_conn, tenant_id, int(item_id))
                return 200, {"ok": True, "deleted": item_id}
        return 405, {"ok": False, "error": "method_not_allowed"}
    
    def _handle_contact(self, db_conn, tenant_id: str, item_id: Optional[str], method: str, query: Dict, data: dict) -> tuple[int, dict]:
        """Contact submissions module endpoints"""
        if item_id is None:
            if method == "GET":
                page = int((query.get("page") or [1])[0])
                per_page = int((query.get("per_page") or [50])[0])
                result = contact.get_all(db_conn, tenant_id, page=page, per_page=per_page)
                return 200, {"ok": True, **result}
            elif method == "POST":
                result = contact.create(db_conn, tenant_id, **data)
                return 200, {"ok": True, "item": result}
        else:
            if method == "GET":
                result = contact.get_by_id(db_conn, tenant_id, int(item_id))
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "DELETE":
                contact.delete(db_conn, tenant_id, int(item_id))
                return 200, {"ok": True, "deleted": item_id}
        return 405, {"ok": False, "error": "method_not_allowed"}
    
    def _handle_packages(self, db_conn, tenant_id: str, item_id: Optional[str], method: str, query: Dict, data: dict) -> tuple[int, dict]:
        """Packages module endpoints"""
        if item_id is None:
            if method == "GET":
                active_only = (query.get("active") or ['true'])[0] == 'true'
                result = packages.get_all(db_conn, tenant_id, active_only=active_only)
                return 200, {"ok": True, "items": result}
            elif method == "POST":
                result = packages.create(db_conn, tenant_id, **data)
                return 200, {"ok": True, "item": result}
        else:
            if method == "GET":
                result = packages.get_by_id(db_conn, tenant_id, int(item_id))
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "PUT":
                result = packages.update(db_conn, tenant_id, int(item_id), **data)
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "DELETE":
                packages.delete(db_conn, tenant_id, int(item_id))
                return 200, {"ok": True, "deleted": item_id}
        return 405, {"ok": False, "error": "method_not_allowed"}
    
    def _handle_settings(self, db_conn, tenant_id: str, item_id: Optional[str], method: str, query: Dict, data: dict) -> tuple[int, dict]:
        """Settings module endpoints"""
        if item_id is None:
            if method == "GET":
                result = settings.get_all(db_conn, tenant_id)
                return 200, {"ok": True, "settings": result}
            elif method == "POST":
                # Bulk set
                if isinstance(data, dict):
                    settings.bulk_set(db_conn, tenant_id, data)
                    return 200, {"ok": True}
        else:
            # item_id is the setting key
            if method == "GET":
                result = settings.get(db_conn, tenant_id, item_id)
                return 200, {"ok": True, "value": result}
            elif method == "PUT":
                value = data.get("value")
                value_type = data.get("type", "string")
                settings.set(db_conn, tenant_id, item_id, value, value_type)
                return 200, {"ok": True}
            elif method == "DELETE":
                settings.delete(db_conn, tenant_id, item_id)
                return 200, {"ok": True, "deleted": item_id}
        return 405, {"ok": False, "error": "method_not_allowed"}
    
    def _handle_jobs(self, db_conn, tenant_id: str, item_id: Optional[str], method: str, query: Dict, data: dict) -> tuple[int, dict]:
        """Jobs module endpoints"""
        if item_id is None:
            if method == "GET":
                status = (query.get("status") or [None])[0]
                client_id = (query.get("client_id") or [None])[0]
                page = int((query.get("page") or [1])[0])
                per_page = int((query.get("per_page") or [50])[0])
                result = jobs.get_all(db_conn, tenant_id, status=status, client_id=client_id, page=page, per_page=per_page)
                return 200, {"ok": True, **result}
            elif method == "POST":
                result = jobs.create(db_conn, tenant_id, **data)
                return 200, {"ok": True, "item": result}
        else:
            if method == "GET":
                result = jobs.get_by_id(db_conn, tenant_id, int(item_id))
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "PUT":
                result = jobs.update(db_conn, tenant_id, int(item_id), **data)
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "DELETE":
                jobs.delete(db_conn, tenant_id, int(item_id))
                return 200, {"ok": True, "deleted": item_id}
        return 405, {"ok": False, "error": "method_not_allowed"}
    
    def _handle_messages(self, db_conn, tenant_id: str, item_id: Optional[str], method: str, query: Dict, data: dict) -> tuple[int, dict]:
        """Messaging module endpoints"""
        if item_id is None:
            if method == "GET":
                thread_id = (query.get("thread_id") or [None])[0]
                unread_only = (query.get("unread") or ['false'])[0] == 'true'
                page = int((query.get("page") or [1])[0])
                per_page = int((query.get("per_page") or [50])[0])
                result = messaging.get_all(db_conn, tenant_id, thread_id=thread_id, unread_only=unread_only, page=page, per_page=per_page)
                return 200, {"ok": True, **result}
            elif method == "POST":
                result = messaging.create(db_conn, tenant_id, **data)
                return 200, {"ok": True, "item": result}
        else:
            if method == "GET":
                result = messaging.get_by_id(db_conn, tenant_id, int(item_id))
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "PUT":
                # Mark as read
                result = messaging.mark_read(db_conn, tenant_id, int(item_id))
                return 200, {"ok": True, "item": result}
            elif method == "DELETE":
                messaging.delete(db_conn, tenant_id, int(item_id))
                return 200, {"ok": True, "deleted": item_id}
        return 405, {"ok": False, "error": "method_not_allowed"}
    
    def _handle_clients(self, db_conn, tenant_id: str, item_id: Optional[str], method: str, query: Dict, data: dict) -> tuple[int, dict]:
        """Clients module endpoints"""
        if item_id is None:
            if method == "GET":
                search = (query.get("search") or [None])[0]
                page = int((query.get("page") or [1])[0])
                per_page = int((query.get("per_page") or [50])[0])
                result = clients.get_all(db_conn, tenant_id, search=search, page=page, per_page=per_page)
                return 200, {"ok": True, **result}
            elif method == "POST":
                result = clients.create(db_conn, tenant_id, **data)
                return 200, {"ok": True, "item": result}
        else:
            if method == "GET":
                result = clients.get_by_id(db_conn, tenant_id, int(item_id))
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "PUT":
                result = clients.update(db_conn, tenant_id, int(item_id), **data)
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "DELETE":
                clients.delete(db_conn, tenant_id, int(item_id))
                return 200, {"ok": True, "deleted": item_id}
        return 405, {"ok": False, "error": "method_not_allowed"}
    
    def _handle_quotes(self, db_conn, tenant_id: str, item_id: Optional[str], method: str, query: Dict, data: dict) -> tuple[int, dict]:
        """Quotes module endpoints"""
        if item_id is None:
            if method == "GET":
                status = (query.get("status") or [None])[0]
                client_id = (query.get("client_id") or [None])[0]
                page = int((query.get("page") or [1])[0])
                per_page = int((query.get("per_page") or [50])[0])
                result = quotes.get_all(db_conn, tenant_id, status=status, client_id=client_id, page=page, per_page=per_page)
                return 200, {"ok": True, **result}
            elif method == "POST":
                result = quotes.create(db_conn, tenant_id, **data)
                return 200, {"ok": True, "item": result}
        else:
            if method == "GET":
                result = quotes.get_by_id(db_conn, tenant_id, int(item_id))
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "PUT":
                result = quotes.update(db_conn, tenant_id, int(item_id), **data)
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "DELETE":
                quotes.delete(db_conn, tenant_id, int(item_id))
                return 200, {"ok": True, "deleted": item_id}
        return 405, {"ok": False, "error": "method_not_allowed"}
    
    def _handle_staff(self, db_conn, tenant_id: str, item_id: Optional[str], method: str, query: Dict, data: dict) -> tuple[int, dict]:
        """Staff module endpoints"""
        if item_id is None:
            if method == "GET":
                active_only = (query.get("active") or ['true'])[0] == 'true'
                role = (query.get("role") or [None])[0]
                page = int((query.get("page") or [1])[0])
                per_page = int((query.get("per_page") or [50])[0])
                result = staff.get_all(db_conn, tenant_id, active_only=active_only, role=role, page=page, per_page=per_page)
                return 200, {"ok": True, **result}
            elif method == "POST":
                result = staff.create(db_conn, tenant_id, **data)
                return 200, {"ok": True, "item": result}
        else:
            if method == "GET":
                result = staff.get_by_id(db_conn, tenant_id, int(item_id))
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "PUT":
                result = staff.update(db_conn, tenant_id, int(item_id), **data)
                if not result:
                    return 404, {"ok": False, "error": "item_not_found"}
                return 200, {"ok": True, "item": result}
            elif method == "DELETE":
                staff.delete(db_conn, tenant_id, int(item_id))
                return 200, {"ok": True, "deleted": item_id}
        return 405, {"ok": False, "error": "method_not_allowed"}

    # ==================== RESOLUTION ====================

    def _normalize_host(self, host: str) -> str:
        host = (host or "").strip().lower()
        if ":" in host:
            host = host.split(":", 1)[0]
        return host.strip(".")

    def _domain_matches(self, host: str, domain: str) -> bool:
        if not host or not domain:
            return False
        host = host.lower()
        domain = domain.lower().strip(".")
        if host == domain:
            return True
        return host.endswith("." + domain)

    def _resolve_tenant(self, query: Dict[str, List[str]]) -> tuple[int, dict]:
        host = self._normalize_host((query.get("host") or [""])[0])
        include_apps = (query.get("include_apps") or ["false"])[0].lower() in ("1", "true", "yes")
        if not host:
            return 400, {"ok": False, "error": "missing_host"}

        with self._db_connect() as conn:
            tenants = conn.execute("SELECT * FROM tenants WHERE domain IS NOT NULL").fetchall()
            apps = conn.execute("SELECT * FROM apps WHERE domain IS NOT NULL").fetchall()

        for app_row in apps:
            app = self._app_row(app_row)
            if self._domain_matches(host, app.get("domain")):
                tenant = self._get_tenant(app["tenant_id"])[1]["tenant"]
                return 200, {"ok": True, "match": "app", "tenant": tenant, "app": app}

        for tenant_row in tenants:
            tenant = self._tenant_row(tenant_row)
            if self._domain_matches(host, tenant.get("domain")):
                payload = {"ok": True, "match": "tenant", "tenant": tenant}
                if include_apps:
                    payload["apps"] = self._list_apps({}, tenant_id=tenant["tenant_id"])[1]["apps"]
                return 200, payload

        return 404, {"ok": False, "error": "no_match"}

    def _resolve_app(self, query: Dict[str, List[str]]) -> tuple[int, dict]:
        host = self._normalize_host((query.get("host") or [""])[0])
        if not host:
            return 400, {"ok": False, "error": "missing_host"}

        with self._db_connect() as conn:
            apps = conn.execute("SELECT * FROM apps WHERE domain IS NOT NULL").fetchall()

        for app_row in apps:
            app = self._app_row(app_row)
            if self._domain_matches(host, app.get("domain")):
                return 200, {"ok": True, "app": app}

        return 404, {"ok": False, "error": "app_not_found"}


if __name__ == "__main__":
    service = TenantsService()
    service.start()
