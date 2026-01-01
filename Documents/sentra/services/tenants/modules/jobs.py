"""
Jobs Module - Work/project tracking system

Base schema for common job tracking. Can be extended for specialized workflows.

Database Schema:
    jobs (
        id INTEGER PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        client_id TEXT,
        title TEXT NOT NULL,
        status TEXT DEFAULT 'new',
        due_date TEXT,
        estimated_hours REAL,
        actual_hours REAL DEFAULT 0,
        tags TEXT,
        notes TEXT,
        created_at TEXT,
        updated_at TEXT
    )

Common job statuses:
- new: Just created
- in_progress: Currently being worked on
- on_hold: Paused/waiting
- awaiting_client: Waiting for client input
- done: Completed
- invoiced: Billed

Endpoints (via tenants service):
    GET    /api/tenants/{tenant_id}/jobs
    GET    /api/tenants/{tenant_id}/jobs/{id}
    POST   /api/tenants/{tenant_id}/jobs
    PUT    /api/tenants/{tenant_id}/jobs/{id}
    DELETE /api/tenants/{tenant_id}/jobs/{id}
    PUT    /api/tenants/{tenant_id}/jobs/{id}/status

Extensible:
Tenants can override this module with custom job tracking
(e.g., add stages, checklists, custom fields)
"""

import json

SCHEMA = """
CREATE TABLE IF NOT EXISTS jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    client_id TEXT,
    title TEXT NOT NULL,
    status TEXT DEFAULT 'new',
    due_date TEXT,
    estimated_hours REAL,
    actual_hours REAL DEFAULT 0,
    tags TEXT,
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_jobs_tenant ON jobs(tenant_id);
CREATE INDEX IF NOT EXISTS idx_jobs_tenant_status ON jobs(tenant_id, status);
CREATE INDEX IF NOT EXISTS idx_jobs_tenant_client ON jobs(tenant_id, client_id);
CREATE INDEX IF NOT EXISTS idx_jobs_due_date ON jobs(due_date);
"""


def get_all(db_conn, tenant_id: str, status=None, client_id=None, page=1, per_page=50):
    """Get all jobs for a tenant"""
    sql = "SELECT * FROM jobs WHERE tenant_id = ?"
    params = [tenant_id]
    
    if status:
        sql += " AND status = ?"
        params.append(status)
    
    if client_id:
        sql += " AND client_id = ?"
        params.append(client_id)
    
    sql += " ORDER BY due_date ASC, created_at DESC"
    
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
        # Parse tags JSON
        if item.get('tags'):
            try:
                item['tags'] = json.loads(item['tags'])
            except:
                item['tags'] = []
        items.append(item)
    
    return {
        'items': items,
        'total': total,
        'page': page,
        'per_page': per_page,
        'pages': (total + per_page - 1) // per_page
    }


def get_by_id(db_conn, tenant_id: str, job_id: int):
    """Get single job"""
    cursor = db_conn.execute(
        "SELECT * FROM jobs WHERE id = ? AND tenant_id = ?",
        [job_id, tenant_id]
    )
    row = cursor.fetchone()
    if not row:
        return None
    
    item = dict(row)
    # Parse tags JSON
    if item.get('tags'):
        try:
            item['tags'] = json.loads(item['tags'])
        except:
            item['tags'] = []
    
    return item


def create(db_conn, tenant_id: str, title: str, client_id=None, status='new', due_date=None, estimated_hours=None, tags=None, notes=None):
    """Create new job"""
    tags_json = json.dumps(tags) if tags else None
    
    cursor = db_conn.execute("""
        INSERT INTO jobs (tenant_id, client_id, title, status, due_date, estimated_hours, tags, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    """, [tenant_id, client_id, title, status, due_date, estimated_hours, tags_json, notes])
    
    db_conn.commit()
    return get_by_id(db_conn, tenant_id, cursor.lastrowid)


def update(db_conn, tenant_id: str, job_id: int, **updates):
    """Update job"""
    allowed_fields = ['title', 'client_id', 'status', 'due_date', 'estimated_hours', 'actual_hours', 'tags', 'notes']
    fields = []
    values = []
    
    for field, value in updates.items():
        if field in allowed_fields:
            fields.append(f"{field} = ?")
            # Encode tags as JSON
            if field == 'tags' and isinstance(value, list):
                value = json.dumps(value)
            values.append(value)
    
    if not fields:
        return get_by_id(db_conn, tenant_id, job_id)
    
    values.extend([job_id, tenant_id])
    db_conn.execute(
        f"UPDATE jobs SET {', '.join(fields)}, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        values
    )
    db_conn.commit()
    
    return get_by_id(db_conn, tenant_id, job_id)


def update_status(db_conn, tenant_id: str, job_id: int, status: str):
    """Update job status"""
    return update(db_conn, tenant_id, job_id, status=status)


def delete(db_conn, tenant_id: str, job_id: int):
    """Delete job"""
    db_conn.execute("DELETE FROM jobs WHERE id = ? AND tenant_id = ?", [job_id, tenant_id])
    db_conn.commit()
    return True


def get_by_status(db_conn, tenant_id: str, status: str):
    """Get all jobs with specific status"""
    return get_all(db_conn, tenant_id, status=status, page=1, per_page=1000)


def log_hours(db_conn, tenant_id: str, job_id: int, hours: float):
    """Add hours to job"""
    job = get_by_id(db_conn, tenant_id, job_id)
    if not job:
        return None
    
    new_hours = (job.get('actual_hours') or 0) + hours
    return update(db_conn, tenant_id, job_id, actual_hours=new_hours)
