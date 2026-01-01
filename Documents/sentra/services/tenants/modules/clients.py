"""
Clients Module - Customer/client management

Base schema for common client management. Can be extended for specialized CRM.

Database Schema:
    clients (
        id INTEGER PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        name TEXT NOT NULL,
        email TEXT,
        phone TEXT,
        notes TEXT,
        custom_fields TEXT,
        created_at TEXT,
        updated_at TEXT
    )

Base client tracking with extensible custom_fields JSON for tenant-specific data.

Endpoints (via tenants service):
    GET    /api/tenants/{tenant_id}/clients
    GET    /api/tenants/{tenant_id}/clients/{id}
    POST   /api/tenants/{tenant_id}/clients
    PUT    /api/tenants/{tenant_id}/clients/{id}
    DELETE /api/tenants/{tenant_id}/clients/{id}

Extensible:
Tenants can override this module with custom client tracking
(e.g., add addresses, companies, custom forms, integrations)
"""

import json

SCHEMA = """
CREATE TABLE IF NOT EXISTS clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    name TEXT NOT NULL,
    email TEXT,
    phone TEXT,
    notes TEXT,
    custom_fields TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_clients_tenant ON clients(tenant_id);
CREATE INDEX IF NOT EXISTS idx_clients_name ON clients(tenant_id, name);
CREATE INDEX IF NOT EXISTS idx_clients_email ON clients(tenant_id, email);
"""


def get_all(db_conn, tenant_id: str, search=None, page=1, per_page=50):
    """Get all clients for a tenant"""
    sql = "SELECT * FROM clients WHERE tenant_id = ?"
    params = [tenant_id]
    
    if search:
        sql += " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)"
        search_term = f"%{search}%"
        params.extend([search_term, search_term, search_term])
    
    sql += " ORDER BY name ASC"
    
    # Count total
    count_sql = sql.replace("SELECT *", "SELECT COUNT(*)")
    cursor = db_conn.execute(count_sql, params)
    total = cursor.fetchone()[0]
    
    # Paginate
    offset = (page - 1) * per_page
    sql += f" LIMIT {per_page} OFFSET {offset}"
    
    cursor = db_conn.execute(sql, params)
    items = []
    for row in cursor.fetchall():
        item = dict(row)
        # Parse custom_fields JSON
        if item.get('custom_fields'):
            try:
                item['custom_fields'] = json.loads(item['custom_fields'])
            except:
                item['custom_fields'] = {}
        items.append(item)
    
    return {
        'items': items,
        'total': total,
        'page': page,
        'per_page': per_page,
        'pages': (total + per_page - 1) // per_page
    }


def get_by_id(db_conn, tenant_id: str, client_id: int):
    """Get single client"""
    cursor = db_conn.execute(
        "SELECT * FROM clients WHERE id = ? AND tenant_id = ?",
        [client_id, tenant_id]
    )
    row = cursor.fetchone()
    if not row:
        return None
    
    item = dict(row)
    # Parse custom_fields JSON
    if item.get('custom_fields'):
        try:
            item['custom_fields'] = json.loads(item['custom_fields'])
        except:
            item['custom_fields'] = {}
    
    return item


def create(db_conn, tenant_id: str, name: str, email=None, phone=None, notes=None, custom_fields=None):
    """Create new client"""
    custom_fields_json = json.dumps(custom_fields) if custom_fields else None
    
    cursor = db_conn.execute("""
        INSERT INTO clients (tenant_id, name, email, phone, notes, custom_fields)
        VALUES (?, ?, ?, ?, ?, ?)
    """, [tenant_id, name, email, phone, notes, custom_fields_json])
    
    db_conn.commit()
    return get_by_id(db_conn, tenant_id, cursor.lastrowid)


def update(db_conn, tenant_id: str, client_id: int, **updates):
    """Update client"""
    allowed_fields = ['name', 'email', 'phone', 'notes', 'custom_fields']
    fields = []
    values = []
    
    for field, value in updates.items():
        if field in allowed_fields:
            fields.append(f"{field} = ?")
            # Encode custom_fields as JSON
            if field == 'custom_fields' and isinstance(value, dict):
                value = json.dumps(value)
            values.append(value)
    
    if not fields:
        return get_by_id(db_conn, tenant_id, client_id)
    
    values.extend([client_id, tenant_id])
    db_conn.execute(
        f"UPDATE clients SET {', '.join(fields)}, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        values
    )
    db_conn.commit()
    
    return get_by_id(db_conn, tenant_id, client_id)


def delete(db_conn, tenant_id: str, client_id: int):
    """Delete client"""
    db_conn.execute("DELETE FROM clients WHERE id = ? AND tenant_id = ?", [client_id, tenant_id])
    db_conn.commit()
    return True


def search(db_conn, tenant_id: str, query: str):
    """Search clients by name, email, or phone"""
    return get_all(db_conn, tenant_id, search=query, page=1, per_page=100)
