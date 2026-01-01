"""
Services Module - Service offerings catalog for tenants

Database Schema:
    services (
        id INTEGER PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        title TEXT NOT NULL,
        badge TEXT,
        description TEXT,
        is_active INTEGER DEFAULT 1,
        sort INTEGER DEFAULT 0,
        created_at TEXT,
        updated_at TEXT
    )

Endpoints (via tenants service):
    GET    /api/tenants/{tenant_id}/services
    GET    /api/tenants/{tenant_id}/services/{id}
    POST   /api/tenants/{tenant_id}/services
    PUT    /api/tenants/{tenant_id}/services/{id}
    DELETE /api/tenants/{tenant_id}/services/{id}
    POST   /api/tenants/{tenant_id}/services/{id}/toggle
"""

SCHEMA = """
CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    title TEXT NOT NULL,
    badge TEXT,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    sort INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_services_tenant ON services(tenant_id);
CREATE INDEX IF NOT EXISTS idx_services_tenant_active ON services(tenant_id, is_active, sort);
"""


def get_all(db_conn, tenant_id: str, active_only=True):
    """Get all services for a tenant"""
    sql = "SELECT * FROM services WHERE tenant_id = ?"
    params = [tenant_id]
    
    if active_only:
        sql += " AND is_active = 1"
    
    sql += " ORDER BY sort ASC, id ASC"
    
    cursor = db_conn.execute(sql, params)
    return [dict(row) for row in cursor.fetchall()]


def get_by_id(db_conn, tenant_id: str, service_id: int):
    """Get single service"""
    cursor = db_conn.execute(
        "SELECT * FROM services WHERE id = ? AND tenant_id = ?",
        [service_id, tenant_id]
    )
    row = cursor.fetchone()
    return dict(row) if row else None


def create(db_conn, tenant_id: str, title: str, badge=None, description=None, is_active=True, sort=0):
    """Create new service"""
    cursor = db_conn.execute("""
        INSERT INTO services (tenant_id, title, badge, description, is_active, sort)
        VALUES (?, ?, ?, ?, ?, ?)
    """, [tenant_id, title, badge, description, 1 if is_active else 0, sort])
    
    db_conn.commit()
    return get_by_id(db_conn, tenant_id, cursor.lastrowid)


def update(db_conn, tenant_id: str, service_id: int, **updates):
    """Update service"""
    allowed_fields = ['title', 'badge', 'description', 'is_active', 'sort']
    fields = []
    values = []
    
    for field, value in updates.items():
        if field in allowed_fields:
            fields.append(f"{field} = ?")
            values.append(value)
    
    if not fields:
        return get_by_id(db_conn, tenant_id, service_id)
    
    values.extend([service_id, tenant_id])
    db_conn.execute(
        f"UPDATE services SET {', '.join(fields)}, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        values
    )
    db_conn.commit()
    
    return get_by_id(db_conn, tenant_id, service_id)


def delete(db_conn, tenant_id: str, service_id: int):
    """Delete service"""
    db_conn.execute("DELETE FROM services WHERE id = ? AND tenant_id = ?", [service_id, tenant_id])
    db_conn.commit()
    return True


def toggle_active(db_conn, tenant_id: str, service_id: int):
    """Toggle active status"""
    service = get_by_id(db_conn, tenant_id, service_id)
    if not service:
        return None
    
    new_status = 0 if service['is_active'] else 1
    db_conn.execute(
        "UPDATE services SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        [new_status, service_id, tenant_id]
    )
    db_conn.commit()
    
    return get_by_id(db_conn, tenant_id, service_id)
