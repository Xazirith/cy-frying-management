"""
Settings Module - Tenant-specific configuration

Database Schema:
    settings (
        id INTEGER PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        key TEXT NOT NULL,
        value TEXT,
        type TEXT,
        created_at TEXT,
        updated_at TEXT,
        UNIQUE(tenant_id, key)
    )

Endpoints (via tenants service):
    GET    /api/tenants/{tenant_id}/settings
    GET    /api/tenants/{tenant_id}/settings/{key}
    PUT    /api/tenants/{tenant_id}/settings/{key}
    DELETE /api/tenants/{tenant_id}/settings/{key}
"""

import json

SCHEMA = """
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    key TEXT NOT NULL,
    value TEXT,
    type TEXT DEFAULT 'string',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(tenant_id, key)
);

CREATE INDEX IF NOT EXISTS idx_settings_tenant ON settings(tenant_id);
CREATE UNIQUE INDEX IF NOT EXISTS idx_settings_tenant_key ON settings(tenant_id, key);
"""


def get_all(db_conn, tenant_id: str):
    """Get all settings for a tenant"""
    cursor = db_conn.execute(
        "SELECT * FROM settings WHERE tenant_id = ? ORDER BY key ASC",
        [tenant_id]
    )
    
    settings = {}
    for row in cursor.fetchall():
        row_dict = dict(row)
        key = row_dict['key']
        value = row_dict['value']
        value_type = row_dict.get('type', 'string')
        
        # Parse value based on type
        if value_type == 'json' and value:
            try:
                value = json.loads(value)
            except:
                pass
        elif value_type == 'int':
            value = int(value) if value else 0
        elif value_type == 'bool':
            value = value.lower() in ('true', '1', 'yes') if value else False
        
        settings[key] = value
    
    return settings


def get(db_conn, tenant_id: str, key: str, default=None):
    """Get single setting"""
    cursor = db_conn.execute(
        "SELECT * FROM settings WHERE tenant_id = ? AND key = ?",
        [tenant_id, key]
    )
    row = cursor.fetchone()
    
    if not row:
        return default
    
    row_dict = dict(row)
    value = row_dict['value']
    value_type = row_dict.get('type', 'string')
    
    # Parse value
    if value_type == 'json' and value:
        try:
            return json.loads(value)
        except:
            return default
    elif value_type == 'int':
        return int(value) if value else default
    elif value_type == 'bool':
        return value.lower() in ('true', '1', 'yes') if value else default
    
    return value if value else default


def set(db_conn, tenant_id: str, key: str, value, value_type='string'):
    """Set/update setting"""
    # Encode value based on type
    if value_type == 'json':
        value_str = json.dumps(value)
    elif value_type == 'bool':
        value_str = 'true' if value else 'false'
    else:
        value_str = str(value) if value is not None else None
    
    # Upsert
    db_conn.execute("""
        INSERT INTO settings (tenant_id, key, value, type)
        VALUES (?, ?, ?, ?)
        ON CONFLICT(tenant_id, key) DO UPDATE SET
            value = excluded.value,
            type = excluded.type,
            updated_at = CURRENT_TIMESTAMP
    """, [tenant_id, key, value_str, value_type])
    
    db_conn.commit()
    return get(db_conn, tenant_id, key)


def delete(db_conn, tenant_id: str, key: str):
    """Delete setting"""
    db_conn.execute("DELETE FROM settings WHERE tenant_id = ? AND key = ?", [tenant_id, key])
    db_conn.commit()
    return True


def bulk_set(db_conn, tenant_id: str, settings_dict: dict):
    """Set multiple settings at once"""
    for key, value in settings_dict.items():
        # Auto-detect type
        if isinstance(value, bool):
            value_type = 'bool'
        elif isinstance(value, int):
            value_type = 'int'
        elif isinstance(value, (dict, list)):
            value_type = 'json'
        else:
            value_type = 'string'
        
        set(db_conn, tenant_id, key, value, value_type)
    
    return get_all(db_conn, tenant_id)
