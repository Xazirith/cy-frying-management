"""
Staff Module - Internal user/staff management

Base schema for tenant staff accounts. Can be extended for specialized role management.

Note: This is separate from the main auth service which handles system-wide authentication.
This module tracks tenant-specific staff with roles/permissions within that tenant.

Database Schema:
    staff (
        id INTEGER PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        role TEXT DEFAULT 'staff',
        permissions TEXT,
        active INTEGER DEFAULT 1,
        created_at TEXT,
        updated_at TEXT
    )

Common roles:
- admin: Full tenant access
- manager: Elevated permissions
- staff: Standard access
- viewer: Read-only access

Endpoints (via tenants service):
    GET    /api/tenants/{tenant_id}/staff
    GET    /api/tenants/{tenant_id}/staff/{id}
    POST   /api/tenants/{tenant_id}/staff
    PUT    /api/tenants/{tenant_id}/staff/{id}
    DELETE /api/tenants/{tenant_id}/staff/{id}
    PUT    /api/tenants/{tenant_id}/staff/{id}/toggle

Extensible:
Tenants can override this module with custom role/permission systems
(e.g., add departments, hierarchies, custom permissions)

Note: Actual authentication/passwords handled by auth service.
This module only tracks tenant-level staff metadata and roles.
"""

import json

SCHEMA = """
CREATE TABLE IF NOT EXISTS staff (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    user_id TEXT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    role TEXT DEFAULT 'staff',
    permissions TEXT,
    active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_staff_tenant ON staff(tenant_id);
CREATE INDEX IF NOT EXISTS idx_staff_user ON staff(user_id);
CREATE INDEX IF NOT EXISTS idx_staff_email ON staff(tenant_id, email);
CREATE INDEX IF NOT EXISTS idx_staff_active ON staff(tenant_id, active);
CREATE UNIQUE INDEX IF NOT EXISTS idx_staff_tenant_email ON staff(tenant_id, email);
"""


def get_all(db_conn, tenant_id: str, active_only=True, role=None, page=1, per_page=50):
    """Get all staff for a tenant"""
    sql = "SELECT * FROM staff WHERE tenant_id = ?"
    params = [tenant_id]
    
    if active_only:
        sql += " AND active = 1"
    
    if role:
        sql += " AND role = ?"
        params.append(role)
    
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
        # Parse permissions JSON
        if item.get('permissions'):
            try:
                item['permissions'] = json.loads(item['permissions'])
            except:
                item['permissions'] = []
        items.append(item)
    
    return {
        'items': items,
        'total': total,
        'page': page,
        'per_page': per_page,
        'pages': (total + per_page - 1) // per_page
    }


def get_by_id(db_conn, tenant_id: str, staff_id: int):
    """Get single staff member"""
    cursor = db_conn.execute(
        "SELECT * FROM staff WHERE id = ? AND tenant_id = ?",
        [staff_id, tenant_id]
    )
    row = cursor.fetchone()
    if not row:
        return None
    
    item = dict(row)
    # Parse permissions JSON
    if item.get('permissions'):
        try:
            item['permissions'] = json.loads(item['permissions'])
        except:
            item['permissions'] = []
    
    return item


def get_by_email(db_conn, tenant_id: str, email: str):
    """Get staff by email"""
    cursor = db_conn.execute(
        "SELECT * FROM staff WHERE tenant_id = ? AND email = ?",
        [tenant_id, email]
    )
    row = cursor.fetchone()
    if not row:
        return None
    
    item = dict(row)
    if item.get('permissions'):
        try:
            item['permissions'] = json.loads(item['permissions'])
        except:
            item['permissions'] = []
    
    return item


def create(db_conn, tenant_id: str, name: str, email: str, role='staff', permissions=None, user_id=None):
    """Create new staff member"""
    permissions_json = json.dumps(permissions) if permissions else None
    
    cursor = db_conn.execute("""
        INSERT INTO staff (tenant_id, user_id, name, email, role, permissions)
        VALUES (?, ?, ?, ?, ?, ?)
    """, [tenant_id, user_id, name, email, role, permissions_json])
    
    db_conn.commit()
    return get_by_id(db_conn, tenant_id, cursor.lastrowid)


def update(db_conn, tenant_id: str, staff_id: int, **updates):
    """Update staff member"""
    allowed_fields = ['name', 'email', 'role', 'permissions', 'user_id', 'active']
    fields = []
    values = []
    
    for field, value in updates.items():
        if field in allowed_fields:
            fields.append(f"{field} = ?")
            # Encode permissions as JSON
            if field == 'permissions' and isinstance(value, list):
                value = json.dumps(value)
            values.append(value)
    
    if not fields:
        return get_by_id(db_conn, tenant_id, staff_id)
    
    values.extend([staff_id, tenant_id])
    db_conn.execute(
        f"UPDATE staff SET {', '.join(fields)}, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        values
    )
    db_conn.commit()
    
    return get_by_id(db_conn, tenant_id, staff_id)


def toggle_active(db_conn, tenant_id: str, staff_id: int):
    """Toggle staff active status"""
    staff = get_by_id(db_conn, tenant_id, staff_id)
    if not staff:
        return None
    
    new_status = 0 if staff['active'] else 1
    return update(db_conn, tenant_id, staff_id, active=new_status)


def delete(db_conn, tenant_id: str, staff_id: int):
    """Delete staff member"""
    db_conn.execute("DELETE FROM staff WHERE id = ? AND tenant_id = ?", [staff_id, tenant_id])
    db_conn.commit()
    return True


def add_permission(db_conn, tenant_id: str, staff_id: int, permission: str):
    """Add permission to staff member"""
    staff = get_by_id(db_conn, tenant_id, staff_id)
    if not staff:
        return None
    
    permissions = staff.get('permissions', [])
    if permission not in permissions:
        permissions.append(permission)
    
    return update(db_conn, tenant_id, staff_id, permissions=permissions)


def remove_permission(db_conn, tenant_id: str, staff_id: int, permission: str):
    """Remove permission from staff member"""
    staff = get_by_id(db_conn, tenant_id, staff_id)
    if not staff:
        return None
    
    permissions = staff.get('permissions', [])
    if permission in permissions:
        permissions.remove(permission)
    
    return update(db_conn, tenant_id, staff_id, permissions=permissions)
