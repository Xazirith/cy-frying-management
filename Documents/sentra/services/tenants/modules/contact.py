"""
Contact Module - Contact form submissions

Database Schema:
    contact_submissions (
        id INTEGER PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT,
        message TEXT,
        ip_address TEXT,
        user_agent TEXT,
        created_at TEXT
    )

Endpoints (via tenants service):
    GET    /api/tenants/{tenant_id}/contact - List submissions (admin)
    POST   /api/tenants/{tenant_id}/contact - Submit contact form (public)
    GET    /api/tenants/{tenant_id}/contact/{id} - Get single submission
    DELETE /api/tenants/{tenant_id}/contact/{id} - Delete submission
"""

SCHEMA = """
CREATE TABLE IF NOT EXISTS contact_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT,
    message TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_contact_tenant ON contact_submissions(tenant_id);
CREATE INDEX IF NOT EXISTS idx_contact_created ON contact_submissions(created_at);
"""


def get_all(db_conn, tenant_id: str, page=1, per_page=50):
    """Get all contact submissions for a tenant (admin)"""
    sql = "SELECT * FROM contact_submissions WHERE tenant_id = ? ORDER BY created_at DESC"
    
    # Count total
    count_sql = sql.replace("SELECT *", "SELECT COUNT(*)")
    cursor = db_conn.execute(count_sql, [tenant_id])
    total = cursor.fetchone()[0]
    
    # Paginate
    offset = (page - 1) * per_page
    sql += f" LIMIT {per_page} OFFSET {offset}"
    
    cursor = db_conn.execute(sql, [tenant_id])
    items = [dict(row) for row in cursor.fetchall()]
    
    return {
        'items': items,
        'total': total,
        'page': page,
        'per_page': per_page,
        'pages': (total + per_page - 1) // per_page
    }


def get_by_id(db_conn, tenant_id: str, submission_id: int):
    """Get single contact submission"""
    cursor = db_conn.execute(
        "SELECT * FROM contact_submissions WHERE id = ? AND tenant_id = ?",
        [submission_id, tenant_id]
    )
    row = cursor.fetchone()
    return dict(row) if row else None


def create(db_conn, tenant_id: str, name: str, email: str, phone=None, message=None, ip_address=None, user_agent=None):
    """Create new contact submission"""
    cursor = db_conn.execute("""
        INSERT INTO contact_submissions (tenant_id, name, email, phone, message, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    """, [tenant_id, name, email, phone, message, ip_address, user_agent])
    
    db_conn.commit()
    return get_by_id(db_conn, tenant_id, cursor.lastrowid)


def delete(db_conn, tenant_id: str, submission_id: int):
    """Delete contact submission"""
    db_conn.execute("DELETE FROM contact_submissions WHERE id = ? AND tenant_id = ?", [submission_id, tenant_id])
    db_conn.commit()
    return True
