"""
Quotes Module - Price quotes and estimates

Base schema for common quote/estimate management. Can be extended for specialized pricing.

Database Schema:
    quotes (
        id INTEGER PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        client_id TEXT,
        job_id TEXT,
        subtotal REAL DEFAULT 0,
        tax REAL DEFAULT 0,
        total REAL DEFAULT 0,
        status TEXT DEFAULT 'draft',
        line_items TEXT,
        notes TEXT,
        valid_until TEXT,
        created_at TEXT,
        updated_at TEXT
    )

Common quote statuses:
- draft: Being prepared
- sent: Sent to client
- accepted: Client accepted
- rejected: Client declined

Endpoints (via tenants service):
    GET    /api/tenants/{tenant_id}/quotes
    GET    /api/tenants/{tenant_id}/quotes/{id}
    POST   /api/tenants/{tenant_id}/quotes
    PUT    /api/tenants/{tenant_id}/quotes/{id}
    DELETE /api/tenants/{tenant_id}/quotes/{id}
    PUT    /api/tenants/{tenant_id}/quotes/{id}/status

Extensible:
Tenants can override this module with custom quoting
(e.g., add approval workflows, templates, PDF generation, esignature)
"""

import json
from datetime import datetime, timedelta

SCHEMA = """
CREATE TABLE IF NOT EXISTS quotes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    client_id TEXT,
    job_id TEXT,
    subtotal REAL DEFAULT 0,
    tax REAL DEFAULT 0,
    total REAL DEFAULT 0,
    status TEXT DEFAULT 'draft',
    line_items TEXT,
    notes TEXT,
    valid_until TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_quotes_tenant ON quotes(tenant_id);
CREATE INDEX IF NOT EXISTS idx_quotes_client ON quotes(tenant_id, client_id);
CREATE INDEX IF NOT EXISTS idx_quotes_status ON quotes(tenant_id, status);
CREATE INDEX IF NOT EXISTS idx_quotes_job ON quotes(tenant_id, job_id);
"""


def get_all(db_conn, tenant_id: str, status=None, client_id=None, page=1, per_page=50):
    """Get all quotes for a tenant"""
    sql = "SELECT * FROM quotes WHERE tenant_id = ?"
    params = [tenant_id]
    
    if status:
        sql += " AND status = ?"
        params.append(status)
    
    if client_id:
        sql += " AND client_id = ?"
        params.append(client_id)
    
    sql += " ORDER BY created_at DESC"
    
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
        # Parse line_items JSON
        if item.get('line_items'):
            try:
                item['line_items'] = json.loads(item['line_items'])
            except:
                item['line_items'] = []
        items.append(item)
    
    return {
        'items': items,
        'total': total,
        'page': page,
        'per_page': per_page,
        'pages': (total + per_page - 1) // per_page
    }


def get_by_id(db_conn, tenant_id: str, quote_id: int):
    """Get single quote"""
    cursor = db_conn.execute(
        "SELECT * FROM quotes WHERE id = ? AND tenant_id = ?",
        [quote_id, tenant_id]
    )
    row = cursor.fetchone()
    if not row:
        return None
    
    item = dict(row)
    # Parse line_items JSON
    if item.get('line_items'):
        try:
            item['line_items'] = json.loads(item['line_items'])
        except:
            item['line_items'] = []
    
    return item


def create(db_conn, tenant_id: str, client_id=None, job_id=None, line_items=None, notes=None, valid_days=30):
    """Create new quote"""
    line_items = line_items or []
    line_items_json = json.dumps(line_items)
    
    # Calculate totals from line_items
    subtotal = sum(item.get('price', 0) * item.get('quantity', 1) for item in line_items)
    tax = subtotal * 0.0  # Default 0%, can be customized
    total = subtotal + tax
    
    # Set valid_until date
    valid_until = (datetime.now() + timedelta(days=valid_days)).isoformat()
    
    cursor = db_conn.execute("""
        INSERT INTO quotes (tenant_id, client_id, job_id, subtotal, tax, total, line_items, notes, valid_until)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    """, [tenant_id, client_id, job_id, subtotal, tax, total, line_items_json, notes, valid_until])
    
    db_conn.commit()
    return get_by_id(db_conn, tenant_id, cursor.lastrowid)


def update(db_conn, tenant_id: str, quote_id: int, **updates):
    """Update quote"""
    allowed_fields = ['client_id', 'job_id', 'status', 'line_items', 'notes', 'valid_until']
    fields = []
    values = []
    
    # If line_items updated, recalculate totals
    if 'line_items' in updates:
        line_items = updates['line_items']
        if isinstance(line_items, list):
            updates['line_items'] = json.dumps(line_items)
            subtotal = sum(item.get('price', 0) * item.get('quantity', 1) for item in line_items)
            tax = subtotal * 0.0  # Default 0%
            total = subtotal + tax
            updates['subtotal'] = subtotal
            updates['tax'] = tax
            updates['total'] = total
            allowed_fields.extend(['subtotal', 'tax', 'total'])
    
    for field, value in updates.items():
        if field in allowed_fields:
            fields.append(f"{field} = ?")
            values.append(value)
    
    if not fields:
        return get_by_id(db_conn, tenant_id, quote_id)
    
    values.extend([quote_id, tenant_id])
    db_conn.execute(
        f"UPDATE quotes SET {', '.join(fields)}, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        values
    )
    db_conn.commit()
    
    return get_by_id(db_conn, tenant_id, quote_id)


def update_status(db_conn, tenant_id: str, quote_id: int, status: str):
    """Update quote status"""
    return update(db_conn, tenant_id, quote_id, status=status)


def delete(db_conn, tenant_id: str, quote_id: int):
    """Delete quote"""
    db_conn.execute("DELETE FROM quotes WHERE id = ? AND tenant_id = ?", [quote_id, tenant_id])
    db_conn.commit()
    return True


def add_line_item(db_conn, tenant_id: str, quote_id: int, description: str, price: float, quantity=1):
    """Add line item to quote"""
    quote = get_by_id(db_conn, tenant_id, quote_id)
    if not quote:
        return None
    
    line_items = quote.get('line_items', [])
    line_items.append({
        'description': description,
        'price': price,
        'quantity': quantity
    })
    
    return update(db_conn, tenant_id, quote_id, line_items=line_items)
