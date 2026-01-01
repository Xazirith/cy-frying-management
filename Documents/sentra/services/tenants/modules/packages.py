"""
Packages Module - Service bundles/packages

Database Schema:
    packages (
        id INTEGER PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        name TEXT NOT NULL,
        price_label TEXT,
        items TEXT,
        is_active INTEGER DEFAULT 1,
        sort INTEGER DEFAULT 0,
        created_at TEXT,
        updated_at TEXT
    )

Endpoints (via tenants service):
    GET    /api/tenants/{tenant_id}/packages
    GET    /api/tenants/{tenant_id}/packages/{id}
    POST   /api/tenants/{tenant_id}/packages
    PUT    /api/tenants/{tenant_id}/packages/{id}
    DELETE /api/tenants/{tenant_id}/packages/{id}
    POST   /api/tenants/{tenant_id}/packages/{id}/toggle
"""

import json

SCHEMA = """
CREATE TABLE IF NOT EXISTS packages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    name TEXT NOT NULL,
    price_label TEXT,
    items TEXT,
    is_active INTEGER DEFAULT 1,
    sort INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_packages_tenant ON packages(tenant_id);
CREATE INDEX IF NOT EXISTS idx_packages_tenant_active ON packages(tenant_id, is_active, sort);
"""


def get_all(db_conn, tenant_id: str, active_only=True):
    """Get all packages for a tenant"""
    sql = "SELECT * FROM packages WHERE tenant_id = ?"
    params = [tenant_id]
    
    if active_only:
        sql += " AND is_active = 1"
    
    sql += " ORDER BY sort ASC, id ASC"
    
    cursor = db_conn.execute(sql, params)
    items = []
    for row in cursor.fetchall():
        item = dict(row)
        # Parse items JSON
        if item.get('items'):
            try:
                item['items'] = json.loads(item['items'])
            except:
                item['items'] = []
        items.append(item)
    
    return items


def get_by_id(db_conn, tenant_id: str, package_id: int):
    """Get single package"""
    cursor = db_conn.execute(
        "SELECT * FROM packages WHERE id = ? AND tenant_id = ?",
        [package_id, tenant_id]
    )
    row = cursor.fetchone()
    if not row:
        return None
    
    item = dict(row)
    # Parse items JSON
    if item.get('items'):
        try:
            item['items'] = json.loads(item['items'])
        except:
            item['items'] = []
    
    return item


def create(db_conn, tenant_id: str, name: str, price_label=None, items=None, is_active=True, sort=0):
    """Create new package"""
    items_json = json.dumps(items) if items else None
    
    cursor = db_conn.execute("""
        INSERT INTO packages (tenant_id, name, price_label, items, is_active, sort)
        VALUES (?, ?, ?, ?, ?, ?)
    """, [tenant_id, name, price_label, items_json, 1 if is_active else 0, sort])
    
    db_conn.commit()
    return get_by_id(db_conn, tenant_id, cursor.lastrowid)


def update(db_conn, tenant_id: str, package_id: int, **updates):
    """Update package"""
    allowed_fields = ['name', 'price_label', 'items', 'is_active', 'sort']
    fields = []
    values = []
    
    for field, value in updates.items():
        if field in allowed_fields:
            fields.append(f"{field} = ?")
            # Encode items as JSON
            if field == 'items' and isinstance(value, (list, dict)):
                value = json.dumps(value)
            values.append(value)
    
    if not fields:
        return get_by_id(db_conn, tenant_id, package_id)
    
    values.extend([package_id, tenant_id])
    db_conn.execute(
        f"UPDATE packages SET {', '.join(fields)}, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        values
    )
    db_conn.commit()
    
    return get_by_id(db_conn, tenant_id, package_id)


def delete(db_conn, tenant_id: str, package_id: int):
    """Delete package"""
    db_conn.execute("DELETE FROM packages WHERE id = ? AND tenant_id = ?", [package_id, tenant_id])
    db_conn.commit()
    return True


def toggle_active(db_conn, tenant_id: str, package_id: int):
    """Toggle active status"""
    package = get_by_id(db_conn, tenant_id, package_id)
    if not package:
        return None
    
    new_status = 0 if package['is_active'] else 1
    db_conn.execute(
        "UPDATE packages SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        [new_status, package_id, tenant_id]
    )
    db_conn.commit()
    
    return get_by_id(db_conn, tenant_id, package_id)
