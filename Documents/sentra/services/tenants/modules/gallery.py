"""
Gallery Module - Multi-tenant photo gallery management

Database Schema:
    gallery_items (
        id INTEGER PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        image_path TEXT NOT NULL,
        tag TEXT,
        caption TEXT,
        is_featured INTEGER DEFAULT 0,
        position INTEGER DEFAULT 0,
        active INTEGER DEFAULT 1,
        created_at TEXT,
        updated_at TEXT
    )

Endpoints (via tenants service):
    GET    /api/tenants/{tenant_id}/gallery
    GET    /api/tenants/{tenant_id}/gallery/tags
    GET    /api/tenants/{tenant_id}/gallery/{id}
    POST   /api/tenants/{tenant_id}/gallery
    PUT    /api/tenants/{tenant_id}/gallery/{id}
    DELETE /api/tenants/{tenant_id}/gallery/{id}
"""

SCHEMA = """
CREATE TABLE IF NOT EXISTS gallery_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    image_path TEXT NOT NULL,
    tag TEXT,
    caption TEXT,
    is_featured INTEGER DEFAULT 0,
    position INTEGER DEFAULT 0,
    active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_gallery_tenant ON gallery_items(tenant_id);
CREATE INDEX IF NOT EXISTS idx_gallery_tenant_featured ON gallery_items(tenant_id, is_featured, position);
CREATE INDEX IF NOT EXISTS idx_gallery_tenant_active ON gallery_items(tenant_id, active, position);
CREATE INDEX IF NOT EXISTS idx_gallery_tenant_tag ON gallery_items(tenant_id, tag);
"""


def get_gallery(db_conn, tenant_id: str, tag=None, featured=None, page=1, per_page=12):
    """Get paginated gallery items for a tenant"""
    sql = "SELECT * FROM gallery_items WHERE tenant_id = ? AND active = 1"
    params = [tenant_id]
    
    if tag:
        sql += " AND tag = ?"
        params.append(tag)
    
    if featured is not None:
        sql += " AND is_featured = ?"
        params.append(1 if featured else 0)
    
    sql += " ORDER BY position ASC, created_at DESC"
    
    # Count total
    count_sql = sql.replace("SELECT *", "SELECT COUNT(*)")
    cursor = db_conn.execute(count_sql, params)
    total = cursor.fetchone()[0]
    
    # Paginate
    offset = (page - 1) * per_page
    sql += f" LIMIT {per_page} OFFSET {offset}"
    
    cursor = db_conn.execute(sql, params)
    items = [dict(row) for row in cursor.fetchall()]
    
    return {
        'items': items,
        'total': total,
        'page': page,
        'per_page': per_page,
        'pages': (total + per_page - 1) // per_page
    }


def get_tags(db_conn, tenant_id: str):
    """Get unique tags for a tenant's gallery"""
    cursor = db_conn.execute(
        "SELECT DISTINCT tag FROM gallery_items WHERE tenant_id = ? AND active = 1 AND tag IS NOT NULL ORDER BY tag",
        [tenant_id]
    )
    return [row[0] for row in cursor.fetchall()]


def get_item(db_conn, tenant_id: str, item_id: int):
    """Get single gallery item"""
    cursor = db_conn.execute(
        "SELECT * FROM gallery_items WHERE id = ? AND tenant_id = ?",
        [item_id, tenant_id]
    )
    row = cursor.fetchone()
    return dict(row) if row else None


def create_item(db_conn, tenant_id: str, image_path: str, caption=None, tag=None, is_featured=False):
    """Create new gallery item"""
    cursor = db_conn.execute("""
        INSERT INTO gallery_items (tenant_id, image_path, caption, tag, is_featured)
        VALUES (?, ?, ?, ?, ?)
    """, [tenant_id, image_path, caption, tag, 1 if is_featured else 0])
    
    db_conn.commit()
    return get_item(db_conn, tenant_id, cursor.lastrowid)


def update_item(db_conn, tenant_id: str, item_id: int, **updates):
    """Update gallery item"""
    allowed_fields = ['caption', 'tag', 'position', 'active', 'is_featured']
    fields = []
    values = []
    
    for field, value in updates.items():
        if field in allowed_fields:
            fields.append(f"{field} = ?")
            values.append(value)
    
    if not fields:
        return get_item(db_conn, tenant_id, item_id)
    
    values.extend([item_id, tenant_id])
    db_conn.execute(
        f"UPDATE gallery_items SET {', '.join(fields)}, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        values
    )
    db_conn.commit()
    
    return get_item(db_conn, tenant_id, item_id)


def delete_item(db_conn, tenant_id: str, item_id: int):
    """Delete gallery item"""
    # Get item first to get image path
    item = get_item(db_conn, tenant_id, item_id)
    if not item:
        return False
    
    db_conn.execute("DELETE FROM gallery_items WHERE id = ? AND tenant_id = ?", [item_id, tenant_id])
    db_conn.commit()
    
    # TODO: Delete file from storage
    return True
