"""
Messaging Module - Thread-based communication system

Base schema for common messaging. Can be extended for specialized chat/notification systems.

Database Schema:
    messages (
        id INTEGER PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        thread_id TEXT NOT NULL,
        sender_id TEXT,
        sender_type TEXT,
        body TEXT NOT NULL,
        read INTEGER DEFAULT 0,
        created_at TEXT,
        updated_at TEXT
    )

Thread-based messaging:
- thread_id: Groups related messages (e.g., job_123, client_456, support_789)
- sender_type: 'staff', 'client', 'system', 'bot'
- read: 0 = unread, 1 = read

Endpoints (via tenants service):
    GET    /api/tenants/{tenant_id}/messages?thread_id={id}
    GET    /api/tenants/{tenant_id}/messages/{id}
    POST   /api/tenants/{tenant_id}/messages
    PUT    /api/tenants/{tenant_id}/messages/{id}/read
    DELETE /api/tenants/{tenant_id}/messages/{id}

Extensible:
Tenants can override this module with custom messaging
(e.g., add attachments, reactions, typing indicators, notifications)
"""

SCHEMA = """
CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    thread_id TEXT NOT NULL,
    sender_id TEXT,
    sender_type TEXT DEFAULT 'staff',
    body TEXT NOT NULL,
    read INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_messages_tenant ON messages(tenant_id);
CREATE INDEX IF NOT EXISTS idx_messages_thread ON messages(tenant_id, thread_id, created_at);
CREATE INDEX IF NOT EXISTS idx_messages_unread ON messages(tenant_id, read, created_at);
"""


def get_all(db_conn, tenant_id: str, thread_id=None, unread_only=False, page=1, per_page=50):
    """Get all messages for a tenant"""
    sql = "SELECT * FROM messages WHERE tenant_id = ?"
    params = [tenant_id]
    
    if thread_id:
        sql += " AND thread_id = ?"
        params.append(thread_id)
    
    if unread_only:
        sql += " AND read = 0"
    
    sql += " ORDER BY created_at DESC"
    
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


def get_by_id(db_conn, tenant_id: str, message_id: int):
    """Get single message"""
    cursor = db_conn.execute(
        "SELECT * FROM messages WHERE id = ? AND tenant_id = ?",
        [message_id, tenant_id]
    )
    row = cursor.fetchone()
    return dict(row) if row else None


def create(db_conn, tenant_id: str, thread_id: str, body: str, sender_id=None, sender_type='staff'):
    """Create new message"""
    cursor = db_conn.execute("""
        INSERT INTO messages (tenant_id, thread_id, sender_id, sender_type, body)
        VALUES (?, ?, ?, ?, ?)
    """, [tenant_id, thread_id, sender_id, sender_type, body])
    
    db_conn.commit()
    return get_by_id(db_conn, tenant_id, cursor.lastrowid)


def mark_read(db_conn, tenant_id: str, message_id: int):
    """Mark message as read"""
    db_conn.execute(
        "UPDATE messages SET read = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        [message_id, tenant_id]
    )
    db_conn.commit()
    return get_by_id(db_conn, tenant_id, message_id)


def mark_thread_read(db_conn, tenant_id: str, thread_id: str):
    """Mark all messages in thread as read"""
    db_conn.execute(
        "UPDATE messages SET read = 1, updated_at = CURRENT_TIMESTAMP WHERE thread_id = ? AND tenant_id = ?",
        [thread_id, tenant_id]
    )
    db_conn.commit()
    return True


def delete(db_conn, tenant_id: str, message_id: int):
    """Delete message"""
    db_conn.execute("DELETE FROM messages WHERE id = ? AND tenant_id = ?", [message_id, tenant_id])
    db_conn.commit()
    return True


def get_threads(db_conn, tenant_id: str):
    """Get all unique threads with latest message preview"""
    cursor = db_conn.execute("""
        SELECT 
            thread_id,
            MAX(created_at) as last_message_at,
            COUNT(*) as message_count,
            SUM(CASE WHEN read = 0 THEN 1 ELSE 0 END) as unread_count,
            (SELECT body FROM messages m2 
             WHERE m2.tenant_id = messages.tenant_id 
             AND m2.thread_id = messages.thread_id 
             ORDER BY created_at DESC LIMIT 1) as last_message
        FROM messages
        WHERE tenant_id = ?
        GROUP BY thread_id
        ORDER BY last_message_at DESC
    """, [tenant_id])
    
    return [dict(row) for row in cursor.fetchall()]


def get_unread_count(db_conn, tenant_id: str, thread_id=None):
    """Get unread message count"""
    if thread_id:
        cursor = db_conn.execute(
            "SELECT COUNT(*) FROM messages WHERE tenant_id = ? AND thread_id = ? AND read = 0",
            [tenant_id, thread_id]
        )
    else:
        cursor = db_conn.execute(
            "SELECT COUNT(*) FROM messages WHERE tenant_id = ? AND read = 0",
            [tenant_id]
        )
    
    return cursor.fetchone()[0]
