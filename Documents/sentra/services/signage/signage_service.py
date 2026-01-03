#!/usr/bin/env python3
"""
Signage Service - Digital signage display and playback service

This service provides:
- Display client endpoints for signage players
- Content delivery for playlists
- Real-time display status monitoring
- Integration with gallery and other content sources
- Multi-tenant support

Display endpoints (no auth required for registered devices):
- GET /display/config?device_id={id} - Get display configuration
- GET /display/playlist?device_id={id} - Get current playlist with content
- POST /display/heartbeat - Send heartbeat and status
- POST /display/analytics - Log playback analytics

Admin endpoints (via tenants service):
- See tenants/modules/signage.py for management endpoints
"""

import sys
import os
import json
import time
import sqlite3
from pathlib import Path
from typing import Dict, Any, Optional, List
from urllib.parse import parse_qs, urlparse
from datetime import datetime, timedelta

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from base_service import SentraService


class SignageService(SentraService):
    """
    Digital Signage Service
    
    Provides display endpoints for signage players and content delivery.
    Management is done through the tenants service.
    """
    
    def __init__(self, port=8095):
        super().__init__("Signage Service", port)
        self.db_path = os.getenv('TENANTS_MODULES_DB', '/data/tenants_modules.db')
        self.storage_base = Path("/opt/sentra-storage")
        self.gallery_storage = self.storage_base / "gallery"
        self.media_storage = self.storage_base / "signage"
        self.media_storage.mkdir(parents=True, exist_ok=True)
        
    def get_metadata(self) -> Dict[str, Any]:
        return {
            "version": "1.0.0",
            "description": "Digital signage display and content delivery",
            "endpoints": [
                "GET  /display/config?device_id={id}",
                "GET  /display/playlist?device_id={id}",
                "POST /display/heartbeat",
                "POST /display/analytics",
                "GET  /display/content/{type}/{id}",
            ]
        }
    
    def _get_db(self):
        """Get database connection"""
        conn = sqlite3.connect(self.db_path)
        conn.row_factory = sqlite3.Row
        return conn
    
    def _get_display_by_device(self, device_id: str) -> Optional[Dict[str, Any]]:
        """Get display configuration by device_id"""
        conn = self._get_db()
        cursor = conn.execute(
            "SELECT * FROM signage_displays WHERE device_id = ?",
            [device_id]
        )
        row = cursor.fetchone()
        conn.close()
        
        if not row:
            return None
        
        display = dict(row)
        if display.get('metadata'):
            display['metadata'] = json.loads(display['metadata'])
        
        return display
    
    def _get_playlist_with_items(self, playlist_id: int, tenant_id: str) -> Optional[Dict[str, Any]]:
        """Get playlist with all its items and resolved content"""
        conn = self._get_db()
        
        # Get playlist
        cursor = conn.execute(
            "SELECT * FROM signage_playlists WHERE id = ? AND tenant_id = ?",
            [playlist_id, tenant_id]
        )
        row = cursor.fetchone()
        
        if not row:
            conn.close()
            return None
        
        playlist = dict(row)
        
        # Get playlist items
        cursor = conn.execute(
            """SELECT * FROM signage_playlist_items 
               WHERE playlist_id = ? AND active = 1 
               ORDER BY position ASC""",
            [playlist_id]
        )
        
        items = []
        for item_row in cursor.fetchall():
            item = dict(item_row)
            if item.get('content_data'):
                item['content_data'] = json.loads(item['content_data'])
            
            # Resolve content based on type
            item['resolved_content'] = self._resolve_content(conn, tenant_id, item)
            items.append(item)
        
        conn.close()
        
        playlist['items'] = items
        return playlist
    
    def _resolve_content(self, conn, tenant_id: str, item: Dict[str, Any]) -> Dict[str, Any]:
        """Resolve content based on content_type"""
        content_type = item['content_type']
        content_id = item.get('content_id')
        content_data = item.get('content_data', {})
        
        if content_type == 'gallery':
            # Get gallery item(s)
            if content_id:
                # Specific gallery item
                cursor = conn.execute(
                    "SELECT * FROM gallery_items WHERE id = ? AND tenant_id = ? AND active = 1",
                    [content_id, tenant_id]
                )
                row = cursor.fetchone()
                if row:
                    return {'type': 'image', 'data': dict(row)}
            else:
                # Tag-based gallery (show multiple)
                tag = content_data.get('tag')
                limit = content_data.get('limit', 10)
                
                if tag:
                    cursor = conn.execute(
                        """SELECT * FROM gallery_items 
                           WHERE tenant_id = ? AND tag = ? AND active = 1 
                           ORDER BY position ASC, created_at DESC 
                           LIMIT ?""",
                        [tenant_id, tag, limit]
                    )
                else:
                    cursor = conn.execute(
                        """SELECT * FROM gallery_items 
                           WHERE tenant_id = ? AND active = 1 
                           ORDER BY position ASC, created_at DESC 
                           LIMIT ?""",
                        [tenant_id, limit]
                    )
                
                items = [dict(row) for row in cursor.fetchall()]
                return {'type': 'gallery', 'data': items}
        
        elif content_type == 'video':
            return {
                'type': 'video',
                'data': {
                    'url': content_data.get('url'),
                    'path': content_data.get('path'),
                }
            }
        
        elif content_type == 'url':
            return {
                'type': 'url',
                'data': {
                    'url': content_data.get('url'),
                    'refresh_interval': content_data.get('refresh_interval', 0)
                }
            }
        
        elif content_type == 'text':
            return {
                'type': 'text',
                'data': {
                    'content': content_data.get('content', ''),
                    'style': content_data.get('style', {}),
                    'animation': content_data.get('animation', 'none')
                }
            }
        
        elif content_type == 'weather':
            return {
                'type': 'weather',
                'data': {
                    'location': content_data.get('location'),
                    'units': content_data.get('units', 'metric'),
                    'style': content_data.get('style', 'modern')
                }
            }
        
        elif content_type == 'clock':
            return {
                'type': 'clock',
                'data': {
                    'format': content_data.get('format', '24h'),
                    'timezone': content_data.get('timezone', 'UTC'),
                    'style': content_data.get('style', 'digital')
                }
            }
        
        elif content_type == 'custom':
            return {
                'type': 'custom',
                'data': content_data
            }
        
        return {'type': 'unknown', 'data': {}}
    
    def _get_active_playlist(self, display: Dict[str, Any]) -> Optional[int]:
        """Determine active playlist based on schedules or default"""
        display_id = display['id']
        tenant_id = display['tenant_id']
        
        conn = self._get_db()
        
        # Check for active schedules
        now = datetime.now()
        current_time = now.strftime('%H:%M')
        current_day = now.weekday() + 1  # 1=Monday, 7=Sunday
        
        cursor = conn.execute(
            """SELECT playlist_id FROM signage_schedules 
               WHERE display_id = ? AND active = 1 
               ORDER BY priority DESC""",
            [display_id]
        )
        
        for row in cursor.fetchall():
            schedule_id = row[0]
            
            # Get full schedule
            cursor2 = conn.execute(
                "SELECT * FROM signage_schedules WHERE id = ?",
                [schedule_id]
            )
            schedule = dict(cursor2.fetchone())
            
            # Check time range
            if schedule.get('start_time') and schedule.get('end_time'):
                if not (schedule['start_time'] <= current_time <= schedule['end_time']):
                    continue
            
            # Check days of week
            if schedule.get('days_of_week'):
                days = [int(d) for d in schedule['days_of_week'].split(',')]
                if current_day not in days:
                    continue
            
            # Schedule matches!
            conn.close()
            return schedule['playlist_id']
        
        conn.close()
        
        # No matching schedule, return display's default playlist
        return display.get('playlist_id')
    
    # ========================================================================
    # DISPLAY ENDPOINTS
    # ========================================================================
    
    def handle_display_config(self, path: str, query: Dict[str, Any], headers: Dict[str, str]) -> tuple:
        """GET /display/config?device_id={id}"""
        device_id = query.get('device_id', [None])[0]
        
        if not device_id:
            return 400, {'error': 'device_id required'}
        
        display = self._get_display_by_device(device_id)
        
        if not display:
            return 404, {'error': 'Display not found'}
        
        # Update heartbeat
        conn = self._get_db()
        conn.execute(
            "UPDATE signage_displays SET last_seen = CURRENT_TIMESTAMP, status = 'online' WHERE device_id = ?",
            [device_id]
        )
        conn.commit()
        conn.close()
        
        # Get active playlist
        playlist_id = self._get_active_playlist(display)
        
        return 200, {
            'display': {
                'id': display['id'],
                'name': display['name'],
                'orientation': display['orientation'],
                'resolution': display['resolution'],
                'tenant_id': display['tenant_id'],
                'metadata': display.get('metadata', {})
            },
            'playlist_id': playlist_id
        }
    
    def handle_display_playlist(self, path: str, query: Dict[str, Any], headers: Dict[str, str]) -> tuple:
        """GET /display/playlist?device_id={id}"""
        device_id = query.get('device_id', [None])[0]
        
        if not device_id:
            return 400, {'error': 'device_id required'}
        
        display = self._get_display_by_device(device_id)
        
        if not display:
            return 404, {'error': 'Display not found'}
        
        # Get active playlist
        playlist_id = self._get_active_playlist(display)
        
        if not playlist_id:
            return 200, {
                'playlist': None,
                'message': 'No active playlist'
            }
        
        # Get playlist with items
        playlist = self._get_playlist_with_items(playlist_id, display['tenant_id'])
        
        if not playlist:
            return 404, {'error': 'Playlist not found'}
        
        return 200, {
            'playlist': playlist,
            'display_id': display['id']
        }
    
    def handle_display_heartbeat(self, path: str, data: Dict[str, Any], headers: Dict[str, str]) -> tuple:
        """POST /display/heartbeat"""
        device_id = data.get('device_id')
        status = data.get('status', 'online')
        
        if not device_id:
            return 400, {'error': 'device_id required'}
        
        display = self._get_display_by_device(device_id)
        
        if not display:
            return 404, {'error': 'Display not found'}
        
        # Update heartbeat
        conn = self._get_db()
        conn.execute(
            "UPDATE signage_displays SET last_seen = CURRENT_TIMESTAMP, status = ? WHERE device_id = ?",
            [status, device_id]
        )
        conn.commit()
        
        # Log analytics
        metadata = data.get('metadata', {})
        conn.execute("""
            INSERT INTO signage_analytics 
            (display_id, event_type, metadata)
            VALUES (?, 'heartbeat', ?)
        """, [display['id'], json.dumps(metadata)])
        conn.commit()
        conn.close()
        
        return 200, {'status': 'ok'}
    
    def handle_display_analytics(self, path: str, data: Dict[str, Any], headers: Dict[str, str]) -> tuple:
        """POST /display/analytics"""
        device_id = data.get('device_id')
        
        if not device_id:
            return 400, {'error': 'device_id required'}
        
        display = self._get_display_by_device(device_id)
        
        if not display:
            return 404, {'error': 'Display not found'}
        
        event_type = data.get('event_type', 'playback')
        playlist_id = data.get('playlist_id')
        content_type = data.get('content_type')
        content_id = data.get('content_id')
        metadata = data.get('metadata', {})
        
        # Log analytics
        conn = self._get_db()
        conn.execute("""
            INSERT INTO signage_analytics 
            (display_id, playlist_id, content_type, content_id, event_type, metadata)
            VALUES (?, ?, ?, ?, ?, ?)
        """, [
            display['id'], playlist_id, content_type, content_id, event_type,
            json.dumps(metadata)
        ])
        conn.commit()
        conn.close()
        
        return 200, {'status': 'logged'}
    
    # ========================================================================
    # REQUEST ROUTING
    # ========================================================================
    
    def handle_request(self, method: str, path: str, query: Dict[str, Any], 
                      headers: Dict[str, str], body: bytes) -> tuple:
        """Route requests to appropriate handlers"""
        
        # Display endpoints
        if path == '/display/config' and method == 'GET':
            return self.handle_display_config(path, query, headers)
        
        elif path == '/display/playlist' and method == 'GET':
            return self.handle_display_playlist(path, query, headers)
        
        elif path == '/display/heartbeat' and method == 'POST':
            data = json.loads(body) if body else {}
            return self.handle_display_heartbeat(path, data, headers)
        
        elif path == '/display/analytics' and method == 'POST':
            data = json.loads(body) if body else {}
            return self.handle_display_analytics(path, data, headers)
        
        return 404, {'error': 'Not found'}


if __name__ == "__main__":
    service = SignageService()
    service.start()
