"""
Signage Module - Multi-tenant digital signage management

Database Schema:
    signage_displays (
        id INTEGER PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        name TEXT NOT NULL,
        location TEXT,
        device_id TEXT UNIQUE,
        status TEXT DEFAULT 'offline',
        orientation TEXT DEFAULT 'landscape',
        resolution TEXT,
        playlist_id INTEGER,
        last_seen TEXT,
        metadata TEXT,
        created_at TEXT,
        updated_at TEXT
    )
    
    signage_playlists (
        id INTEGER PRIMARY KEY,
        tenant_id TEXT NOT NULL,
        name TEXT NOT NULL,
        description TEXT,
        is_default INTEGER DEFAULT 0,
        active INTEGER DEFAULT 1,
        created_at TEXT,
        updated_at TEXT
    )
    
    signage_playlist_items (
        id INTEGER PRIMARY KEY,
        playlist_id INTEGER NOT NULL,
        content_type TEXT NOT NULL,
        content_id TEXT,
        duration INTEGER DEFAULT 10,
        position INTEGER DEFAULT 0,
        transition TEXT DEFAULT 'fade',
        active INTEGER DEFAULT 1,
        created_at TEXT,
        FOREIGN KEY(playlist_id) REFERENCES signage_playlists(id) ON DELETE CASCADE
    )
    
    signage_schedules (
        id INTEGER PRIMARY KEY,
        display_id INTEGER NOT NULL,
        playlist_id INTEGER NOT NULL,
        start_time TEXT,
        end_time TEXT,
        days_of_week TEXT,
        priority INTEGER DEFAULT 0,
        active INTEGER DEFAULT 1,
        FOREIGN KEY(display_id) REFERENCES signage_displays(id) ON DELETE CASCADE,
        FOREIGN KEY(playlist_id) REFERENCES signage_playlists(id) ON DELETE CASCADE
    )
    
    signage_analytics (
        id INTEGER PRIMARY KEY,
        display_id INTEGER NOT NULL,
        playlist_id INTEGER,
        content_type TEXT,
        content_id TEXT,
        event_type TEXT,
        timestamp TEXT,
        metadata TEXT,
        FOREIGN KEY(display_id) REFERENCES signage_displays(id) ON DELETE CASCADE
    )

Content Types:
    - gallery: Pull from gallery_items
    - video: Video file from storage
    - url: Web page/URL
    - text: Static text/announcement
    - weather: Weather widget
    - clock: Clock widget
    - custom: Custom module content

Endpoints (via tenants service):
    Displays:
        GET    /api/tenants/{tenant_id}/signage/displays
        POST   /api/tenants/{tenant_id}/signage/displays
        GET    /api/tenants/{tenant_id}/signage/displays/{id}
        PUT    /api/tenants/{tenant_id}/signage/displays/{id}
        DELETE /api/tenants/{tenant_id}/signage/displays/{id}
        POST   /api/tenants/{tenant_id}/signage/displays/{id}/heartbeat
    
    Playlists:
        GET    /api/tenants/{tenant_id}/signage/playlists
        POST   /api/tenants/{tenant_id}/signage/playlists
        GET    /api/tenants/{tenant_id}/signage/playlists/{id}
        PUT    /api/tenants/{tenant_id}/signage/playlists/{id}
        DELETE /api/tenants/{tenant_id}/signage/playlists/{id}
        GET    /api/tenants/{tenant_id}/signage/playlists/{id}/items
        POST   /api/tenants/{tenant_id}/signage/playlists/{id}/items
        PUT    /api/tenants/{tenant_id}/signage/playlists/{id}/items/{item_id}
        DELETE /api/tenants/{tenant_id}/signage/playlists/{id}/items/{item_id}
    
    Schedules:
        GET    /api/tenants/{tenant_id}/signage/schedules
        POST   /api/tenants/{tenant_id}/signage/schedules
        PUT    /api/tenants/{tenant_id}/signage/schedules/{id}
        DELETE /api/tenants/{tenant_id}/signage/schedules/{id}
    
    Analytics:
        GET    /api/tenants/{tenant_id}/signage/analytics
        POST   /api/tenants/{tenant_id}/signage/analytics
"""

import json
import time
from typing import Dict, Any, List, Optional

SCHEMA = """
-- Digital Signage Displays
CREATE TABLE IF NOT EXISTS signage_displays (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    name TEXT NOT NULL,
    location TEXT,
    device_id TEXT UNIQUE NOT NULL,
    status TEXT DEFAULT 'offline',
    orientation TEXT DEFAULT 'landscape',
    resolution TEXT DEFAULT '1920x1080',
    playlist_id INTEGER,
    last_seen TEXT,
    metadata TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(playlist_id) REFERENCES signage_playlists(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_signage_displays_tenant ON signage_displays(tenant_id);
CREATE INDEX IF NOT EXISTS idx_signage_displays_device ON signage_displays(device_id);
CREATE INDEX IF NOT EXISTS idx_signage_displays_status ON signage_displays(tenant_id, status);

-- Signage Playlists
CREATE TABLE IF NOT EXISTS signage_playlists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    is_default INTEGER DEFAULT 0,
    active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_signage_playlists_tenant ON signage_playlists(tenant_id);
CREATE INDEX IF NOT EXISTS idx_signage_playlists_default ON signage_playlists(tenant_id, is_default);

-- Playlist Items
CREATE TABLE IF NOT EXISTS signage_playlist_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    playlist_id INTEGER NOT NULL,
    content_type TEXT NOT NULL,
    content_id TEXT,
    content_data TEXT,
    duration INTEGER DEFAULT 10,
    position INTEGER DEFAULT 0,
    transition TEXT DEFAULT 'fade',
    active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(playlist_id) REFERENCES signage_playlists(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_signage_playlist_items_playlist ON signage_playlist_items(playlist_id);
CREATE INDEX IF NOT EXISTS idx_signage_playlist_items_position ON signage_playlist_items(playlist_id, position);

-- Schedules
CREATE TABLE IF NOT EXISTS signage_schedules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    display_id INTEGER NOT NULL,
    playlist_id INTEGER NOT NULL,
    start_time TEXT,
    end_time TEXT,
    days_of_week TEXT,
    priority INTEGER DEFAULT 0,
    active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(display_id) REFERENCES signage_displays(id) ON DELETE CASCADE,
    FOREIGN KEY(playlist_id) REFERENCES signage_playlists(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_signage_schedules_display ON signage_schedules(display_id);
CREATE INDEX IF NOT EXISTS idx_signage_schedules_active ON signage_schedules(display_id, active);

-- Analytics
CREATE TABLE IF NOT EXISTS signage_analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    display_id INTEGER NOT NULL,
    playlist_id INTEGER,
    content_type TEXT,
    content_id TEXT,
    event_type TEXT NOT NULL,
    timestamp TEXT DEFAULT CURRENT_TIMESTAMP,
    metadata TEXT,
    FOREIGN KEY(display_id) REFERENCES signage_displays(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_signage_analytics_display ON signage_analytics(display_id);
CREATE INDEX IF NOT EXISTS idx_signage_analytics_timestamp ON signage_analytics(timestamp);
"""


# ============================================================================
# DISPLAYS
# ============================================================================

def get_displays(db_conn, tenant_id: str, status: Optional[str] = None):
    """Get all displays for a tenant"""
    sql = "SELECT * FROM signage_displays WHERE tenant_id = ?"
    params = [tenant_id]
    
    if status:
        sql += " AND status = ?"
        params.append(status)
    
    sql += " ORDER BY name ASC"
    
    cursor = db_conn.execute(sql, params)
    displays = []
    
    for row in cursor.fetchall():
        display = dict(row)
        if display.get('metadata'):
            display['metadata'] = json.loads(display['metadata'])
        displays.append(display)
    
    return displays


def get_display(db_conn, tenant_id: str, display_id: int):
    """Get single display"""
    cursor = db_conn.execute(
        "SELECT * FROM signage_displays WHERE id = ? AND tenant_id = ?",
        [display_id, tenant_id]
    )
    row = cursor.fetchone()
    
    if not row:
        return None
    
    display = dict(row)
    if display.get('metadata'):
        display['metadata'] = json.loads(display['metadata'])
    
    return display


def get_display_by_device(db_conn, device_id: str):
    """Get display by device_id (for display client auth)"""
    cursor = db_conn.execute(
        "SELECT * FROM signage_displays WHERE device_id = ?",
        [device_id]
    )
    row = cursor.fetchone()
    
    if not row:
        return None
    
    display = dict(row)
    if display.get('metadata'):
        display['metadata'] = json.loads(display['metadata'])
    
    return display


def create_display(db_conn, tenant_id: str, name: str, device_id: str, **kwargs):
    """Create new display"""
    location = kwargs.get('location', '')
    orientation = kwargs.get('orientation', 'landscape')
    resolution = kwargs.get('resolution', '1920x1080')
    playlist_id = kwargs.get('playlist_id')
    metadata = kwargs.get('metadata', {})
    
    cursor = db_conn.execute("""
        INSERT INTO signage_displays 
        (tenant_id, name, device_id, location, orientation, resolution, playlist_id, metadata, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'offline')
    """, [
        tenant_id, name, device_id, location, orientation, resolution, playlist_id,
        json.dumps(metadata) if metadata else None
    ])
    
    db_conn.commit()
    return get_display(db_conn, tenant_id, cursor.lastrowid)


def update_display(db_conn, tenant_id: str, display_id: int, **updates):
    """Update display"""
    allowed_fields = ['name', 'location', 'orientation', 'resolution', 'playlist_id', 'status']
    fields = []
    values = []
    
    for field, value in updates.items():
        if field in allowed_fields:
            fields.append(f"{field} = ?")
            values.append(value)
        elif field == 'metadata':
            fields.append("metadata = ?")
            values.append(json.dumps(value) if value else None)
    
    if not fields:
        return get_display(db_conn, tenant_id, display_id)
    
    values.extend([display_id, tenant_id])
    db_conn.execute(
        f"UPDATE signage_displays SET {', '.join(fields)}, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        values
    )
    db_conn.commit()
    
    return get_display(db_conn, tenant_id, display_id)


def heartbeat_display(db_conn, device_id: str, status: str = 'online'):
    """Update display last_seen and status"""
    db_conn.execute(
        "UPDATE signage_displays SET last_seen = CURRENT_TIMESTAMP, status = ? WHERE device_id = ?",
        [status, device_id]
    )
    db_conn.commit()
    return True


def delete_display(db_conn, tenant_id: str, display_id: int):
    """Delete display"""
    db_conn.execute(
        "DELETE FROM signage_displays WHERE id = ? AND tenant_id = ?",
        [display_id, tenant_id]
    )
    db_conn.commit()
    return True


# ============================================================================
# PLAYLISTS
# ============================================================================

def get_playlists(db_conn, tenant_id: str, active_only: bool = False):
    """Get all playlists for a tenant"""
    sql = "SELECT * FROM signage_playlists WHERE tenant_id = ?"
    params = [tenant_id]
    
    if active_only:
        sql += " AND active = 1"
    
    sql += " ORDER BY is_default DESC, name ASC"
    
    cursor = db_conn.execute(sql, params)
    return [dict(row) for row in cursor.fetchall()]


def get_playlist(db_conn, tenant_id: str, playlist_id: int):
    """Get single playlist"""
    cursor = db_conn.execute(
        "SELECT * FROM signage_playlists WHERE id = ? AND tenant_id = ?",
        [playlist_id, tenant_id]
    )
    row = cursor.fetchone()
    return dict(row) if row else None


def create_playlist(db_conn, tenant_id: str, name: str, **kwargs):
    """Create new playlist"""
    description = kwargs.get('description', '')
    is_default = kwargs.get('is_default', False)
    active = kwargs.get('active', True)
    
    cursor = db_conn.execute("""
        INSERT INTO signage_playlists (tenant_id, name, description, is_default, active)
        VALUES (?, ?, ?, ?, ?)
    """, [tenant_id, name, description, 1 if is_default else 0, 1 if active else 0])
    
    db_conn.commit()
    return get_playlist(db_conn, tenant_id, cursor.lastrowid)


def update_playlist(db_conn, tenant_id: str, playlist_id: int, **updates):
    """Update playlist"""
    allowed_fields = ['name', 'description', 'is_default', 'active']
    fields = []
    values = []
    
    for field, value in updates.items():
        if field in allowed_fields:
            fields.append(f"{field} = ?")
            if field in ['is_default', 'active']:
                values.append(1 if value else 0)
            else:
                values.append(value)
    
    if not fields:
        return get_playlist(db_conn, tenant_id, playlist_id)
    
    values.extend([playlist_id, tenant_id])
    db_conn.execute(
        f"UPDATE signage_playlists SET {', '.join(fields)}, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND tenant_id = ?",
        values
    )
    db_conn.commit()
    
    return get_playlist(db_conn, tenant_id, playlist_id)


def delete_playlist(db_conn, tenant_id: str, playlist_id: int):
    """Delete playlist"""
    db_conn.execute(
        "DELETE FROM signage_playlists WHERE id = ? AND tenant_id = ?",
        [playlist_id, tenant_id]
    )
    db_conn.commit()
    return True


# ============================================================================
# PLAYLIST ITEMS
# ============================================================================

def get_playlist_items(db_conn, playlist_id: int, active_only: bool = True):
    """Get all items in a playlist"""
    sql = "SELECT * FROM signage_playlist_items WHERE playlist_id = ?"
    params = [playlist_id]
    
    if active_only:
        sql += " AND active = 1"
    
    sql += " ORDER BY position ASC"
    
    cursor = db_conn.execute(sql, params)
    items = []
    
    for row in cursor.fetchall():
        item = dict(row)
        if item.get('content_data'):
            item['content_data'] = json.loads(item['content_data'])
        items.append(item)
    
    return items


def create_playlist_item(db_conn, playlist_id: int, content_type: str, **kwargs):
    """Create new playlist item"""
    content_id = kwargs.get('content_id')
    content_data = kwargs.get('content_data', {})
    duration = kwargs.get('duration', 10)
    position = kwargs.get('position', 0)
    transition = kwargs.get('transition', 'fade')
    active = kwargs.get('active', True)
    
    cursor = db_conn.execute("""
        INSERT INTO signage_playlist_items 
        (playlist_id, content_type, content_id, content_data, duration, position, transition, active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    """, [
        playlist_id, content_type, content_id,
        json.dumps(content_data) if content_data else None,
        duration, position, transition, 1 if active else 0
    ])
    
    db_conn.commit()
    
    # Return created item
    cursor = db_conn.execute(
        "SELECT * FROM signage_playlist_items WHERE id = ?",
        [cursor.lastrowid]
    )
    row = cursor.fetchone()
    item = dict(row)
    if item.get('content_data'):
        item['content_data'] = json.loads(item['content_data'])
    
    return item


def update_playlist_item(db_conn, item_id: int, **updates):
    """Update playlist item"""
    allowed_fields = ['content_type', 'content_id', 'duration', 'position', 'transition', 'active']
    fields = []
    values = []
    
    for field, value in updates.items():
        if field in allowed_fields:
            fields.append(f"{field} = ?")
            if field == 'active':
                values.append(1 if value else 0)
            else:
                values.append(value)
        elif field == 'content_data':
            fields.append("content_data = ?")
            values.append(json.dumps(value) if value else None)
    
    if not fields:
        return None
    
    values.append(item_id)
    db_conn.execute(
        f"UPDATE signage_playlist_items SET {', '.join(fields)} WHERE id = ?",
        values
    )
    db_conn.commit()
    
    cursor = db_conn.execute("SELECT * FROM signage_playlist_items WHERE id = ?", [item_id])
    row = cursor.fetchone()
    if row:
        item = dict(row)
        if item.get('content_data'):
            item['content_data'] = json.loads(item['content_data'])
        return item
    return None


def delete_playlist_item(db_conn, item_id: int):
    """Delete playlist item"""
    db_conn.execute("DELETE FROM signage_playlist_items WHERE id = ?", [item_id])
    db_conn.commit()
    return True


# ============================================================================
# SCHEDULES
# ============================================================================

def get_schedules(db_conn, display_id: Optional[int] = None):
    """Get schedules for a display or all schedules"""
    if display_id:
        cursor = db_conn.execute(
            "SELECT * FROM signage_schedules WHERE display_id = ? ORDER BY priority DESC",
            [display_id]
        )
    else:
        cursor = db_conn.execute("SELECT * FROM signage_schedules ORDER BY display_id, priority DESC")
    
    return [dict(row) for row in cursor.fetchall()]


def create_schedule(db_conn, display_id: int, playlist_id: int, **kwargs):
    """Create new schedule"""
    start_time = kwargs.get('start_time')
    end_time = kwargs.get('end_time')
    days_of_week = kwargs.get('days_of_week')  # e.g., "1,2,3,4,5" for Mon-Fri
    priority = kwargs.get('priority', 0)
    active = kwargs.get('active', True)
    
    cursor = db_conn.execute("""
        INSERT INTO signage_schedules 
        (display_id, playlist_id, start_time, end_time, days_of_week, priority, active)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    """, [display_id, playlist_id, start_time, end_time, days_of_week, priority, 1 if active else 0])
    
    db_conn.commit()
    
    cursor = db_conn.execute("SELECT * FROM signage_schedules WHERE id = ?", [cursor.lastrowid])
    row = cursor.fetchone()
    return dict(row) if row else None


def update_schedule(db_conn, schedule_id: int, **updates):
    """Update schedule"""
    allowed_fields = ['playlist_id', 'start_time', 'end_time', 'days_of_week', 'priority', 'active']
    fields = []
    values = []
    
    for field, value in updates.items():
        if field in allowed_fields:
            fields.append(f"{field} = ?")
            if field == 'active':
                values.append(1 if value else 0)
            else:
                values.append(value)
    
    if not fields:
        return None
    
    values.append(schedule_id)
    db_conn.execute(
        f"UPDATE signage_schedules SET {', '.join(fields)} WHERE id = ?",
        values
    )
    db_conn.commit()
    
    cursor = db_conn.execute("SELECT * FROM signage_schedules WHERE id = ?", [schedule_id])
    row = cursor.fetchone()
    return dict(row) if row else None


def delete_schedule(db_conn, schedule_id: int):
    """Delete schedule"""
    db_conn.execute("DELETE FROM signage_schedules WHERE id = ?", [schedule_id])
    db_conn.commit()
    return True


# ============================================================================
# ANALYTICS
# ============================================================================

def log_analytics(db_conn, display_id: int, event_type: str, **kwargs):
    """Log analytics event"""
    playlist_id = kwargs.get('playlist_id')
    content_type = kwargs.get('content_type')
    content_id = kwargs.get('content_id')
    metadata = kwargs.get('metadata', {})
    
    db_conn.execute("""
        INSERT INTO signage_analytics 
        (display_id, playlist_id, content_type, content_id, event_type, metadata)
        VALUES (?, ?, ?, ?, ?, ?)
    """, [
        display_id, playlist_id, content_type, content_id, event_type,
        json.dumps(metadata) if metadata else None
    ])
    
    db_conn.commit()
    return True


def get_analytics(db_conn, tenant_id: str, display_id: Optional[int] = None, 
                  start_date: Optional[str] = None, end_date: Optional[str] = None,
                  limit: int = 100):
    """Get analytics data"""
    # First get display IDs for this tenant
    if display_id:
        display_ids = [display_id]
    else:
        cursor = db_conn.execute(
            "SELECT id FROM signage_displays WHERE tenant_id = ?",
            [tenant_id]
        )
        display_ids = [row[0] for row in cursor.fetchall()]
    
    if not display_ids:
        return []
    
    # Build analytics query
    placeholders = ','.join('?' * len(display_ids))
    sql = f"SELECT * FROM signage_analytics WHERE display_id IN ({placeholders})"
    params = display_ids
    
    if start_date:
        sql += " AND timestamp >= ?"
        params.append(start_date)
    
    if end_date:
        sql += " AND timestamp <= ?"
        params.append(end_date)
    
    sql += f" ORDER BY timestamp DESC LIMIT {limit}"
    
    cursor = db_conn.execute(sql, params)
    
    analytics = []
    for row in cursor.fetchall():
        event = dict(row)
        if event.get('metadata'):
            event['metadata'] = json.loads(event['metadata'])
        analytics.append(event)
    
    return analytics
