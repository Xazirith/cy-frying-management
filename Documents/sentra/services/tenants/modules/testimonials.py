"""
Testimonials Module - Customer reviews and ratings

Database Schema:
    testimonials (
        id INTEGER PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        name TEXT NOT NULL,
        rating INTEGER DEFAULT 5,
        quote TEXT NOT NULL,
        is_active INTEGER DEFAULT 1,
        sort INTEGER DEFAULT 0,
        created_at TEXT,
        updated_at TEXT
    )

Endpoints (via tenants service):
    GET    /api/tenants/{tenant_id}/testimonials
    GET    /api/tenants/{tenant_id}/testimonials/{id}
    POST   /api/tenants/{tenant_id}/testimonials
    PUT    /api/tenants/{tenant_id}/testimonials/{id}
    DELETE /api/tenants/{tenant_id}/testimonials/{id}
    POST   /api/tenants/{tenant_id}/testimonials/{id}/toggle
"""

SCHEMA = """
CREATE TABLE IF NOT EXISTS testimonials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    name TEXT NOT NULL,
    rating INTEGER DEFAULT 5,
    quote TEXT NOT NULL,
    is_active INTEGER DEFAULT 1,
    sort INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_testimonials_tenant ON testimonials(tenant_id);
CREATE INDEX IF NOT EXISTS idx_testimonials_tenant_active ON testimonials(tenant_id, is_active, sort);
"""


def get_all(db_conn, tenant_id: str, active_only=True):
    """Get all testimonials for a tenant"""
    sql = "SELECT * FROM testimonials WHERE tenant_id = ?"
    params = [tenant_id]
    
    if active_only:
        sql += " AND is_active = 1"
    
    sql += " ORDER BY sort ASC, id DESC"
    
    cursor = db_conn.execute(sql, params)
    return [dict(row) for row in cursor.fetchall()]


def get_by_id(db_conn, tenant_id: str, testimonial_id: int):
    """Get single testimonial"""
    cursor = db_conn.execute(
        "SELECT * FROM testimonials WHERE id = ? AND tenant_id = ?",
        [testimonial_id, tenant_id]
    )
    row = cursor.fetchone()
    return dict(row) if row else None


def create(db_conn, tenant_id: str, name: str, quote: str, rating=5, is_active=True, sort=0):
    """Create new testimonial"""
    cursor = db_conn.execute("""
        INSERT INTO testimonials (tenant_id, name, quote, rating, is_active, sort)
        VALUES (?, ?, ?, ?, ?, ?)
    """, [tenant_id, name, quote, rating, 1 if is_active else 0, sort])
    
    db_conn.commit()
    return get_by_id(db_conn, tenant_id, cursor.lastrowid)


def update(db_conn, tenant_id: str, testimonial_id: int, **updates):
    """Update testimonial"""
    allowed_fields = ['name', 'quote', 'rating', 'is_active', 'sort']
    fields = []
    values = []
    
    for field, value in updates.items():
        if field in allowed_fields:
            fields.append(f"{field} = ?")
            values.append(value)
    
    if not fields:
        return get_by_id(db_conn, tenant_id, testimonial_id)
    
    values.extend([testimonial_id, tenant_id])
    db_conn.execute(
        f"UPDATE testimonials SET {', '.join(fields)}, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        values
    )
    db_conn.commit()
    
    return get_by_id(db_conn, tenant_id, testimonial_id)


def delete(db_conn, tenant_id: str, testimonial_id: int):
    """Delete testimonial"""
    db_conn.execute("DELETE FROM testimonials WHERE id = ? AND tenant_id = ?", [testimonial_id, tenant_id])
    db_conn.commit()
    return True


def toggle_active(db_conn, tenant_id: str, testimonial_id: int):
    """Toggle active status"""
    testimonial = get_by_id(db_conn, tenant_id, testimonial_id)
    if not testimonial:
        return None
    
    new_status = 0 if testimonial['is_active'] else 1
    db_conn.execute(
        "UPDATE testimonials SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        [new_status, testimonial_id, tenant_id]
    )
    db_conn.commit()
    
    return get_by_id(db_conn, tenant_id, testimonial_id)
