"""
Tenant-accessible shared modules

These modules provide common functionality for all tenants like galleries,
services, testimonials, etc. Each module is tenant-scoped via tenant_id.

All modules are accessible through the tenants service endpoints.

Database Modes:
- shared: All tenants use single database with tenant_id scoping (default)
- per_tenant: Each tenant gets separate database file
- custom: Custom path per tenant (set in tenant config)
"""

import os
import sqlite3
from typing import Optional

# Import all tenant modules
from . import gallery
from . import services
from . import testimonials
from . import contact
from . import packages
from . import settings
from . import jobs
from . import messaging
from . import clients
from . import quotes
from . import staff

# All module schemas for initialization
ALL_SCHEMAS = [
    gallery.SCHEMA,
    services.SCHEMA,
    testimonials.SCHEMA,
    contact.SCHEMA,
    packages.SCHEMA,
    settings.SCHEMA,
    jobs.SCHEMA,
    messaging.SCHEMA,
    clients.SCHEMA,
    quotes.SCHEMA,
    staff.SCHEMA,
]

# Export all modules for direct access
__all__ = [
    'gallery',
    'services',
    'testimonials',
    'contact',
    'packages',
    'settings',
    'jobs',
    'messaging',
    'clients',
    'quotes',
    'staff',
    'get_tenant_db',
    'init_tenant_db',
    'ALL_SCHEMAS',
]


def get_tenant_db_path(tenant_id: str, config: Optional[dict] = None) -> str:
    """
    Get database path for tenant based on config mode.
    
    Modes:
    - shared (default): /data/tenants_modules.db (all tenants in one DB)
    - per_tenant: /data/tenants/{tenant_id}.db (separate DB per tenant)
    - custom: config['db_path'] (custom path from tenant config)
    
    Args:
        tenant_id: Tenant identifier
        config: Optional tenant config dict with 'db_mode' and 'db_path'
    
    Returns:
        str: Absolute path to database file
    """
    config = config or {}
    mode = config.get('db_mode', 'shared')
    
    if mode == 'custom' and config.get('db_path'):
        return config['db_path']
    elif mode == 'per_tenant':
        base_dir = os.getenv('TENANTS_DATA_DIR', '/data/tenants')
        os.makedirs(base_dir, exist_ok=True)
        return os.path.join(base_dir, f"{tenant_id}.db")
    else:  # shared mode (default)
        return os.getenv('TENANTS_MODULES_DB', '/data/tenants_modules.db')


def get_tenant_db(tenant_id: str, config: Optional[dict] = None) -> sqlite3.Connection:
    """
    Get database connection for tenant with proper scoping.
    
    Args:
        tenant_id: Tenant identifier
        config: Optional tenant config for db mode/path
    
    Returns:
        sqlite3.Connection: Database connection with row_factory set
    """
    db_path = get_tenant_db_path(tenant_id, config)
    
    # Ensure parent directory exists
    os.makedirs(os.path.dirname(db_path), exist_ok=True)
    
    conn = sqlite3.connect(db_path)
    conn.row_factory = sqlite3.Row
    
    # Initialize schema if needed
    init_tenant_db(conn)
    
    return conn


def init_tenant_db(conn: sqlite3.Connection) -> None:
    """
    Initialize all tenant module schemas in database.
    Safe to call multiple times (uses IF NOT EXISTS).
    
    Args:
        conn: Database connection
    """
    for schema in ALL_SCHEMAS:
        conn.executescript(schema)
    conn.commit()
