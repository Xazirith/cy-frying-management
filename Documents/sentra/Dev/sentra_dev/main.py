#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
SENTRA UNIFIED DEVELOPMENT SYSTEM v8.1
=====================================
Drop-in rewrite of `sentra_dev/main.py` that fixes:
- Crypto import hard dependency (now optional; uses guards)
- Forward-ref type-hint NameError (uses postponed annotations)
- Adds a native `heartbeat` command that works without crypto/DB
- Safer DB guards for list/register/verify
- Preserves your existing CLI surface

Created for Xazirith (@sentra)
"""
from __future__ import annotations

import sys
import os
import json
import time
import argparse
from pathlib import Path
from dataclasses import dataclass
from typing import Dict, Any, Optional, List
from urllib.parse import urlparse, urlunparse

# --------------------------------------------------------------------------------------
# Paths & bootstrap
# --------------------------------------------------------------------------------------
VERSION = "8.1"
SENTRA_BRAND = "SENTRA"
CREATOR = "sentra"

DEFAULT_CONFIG_DIR = Path(os.environ.get("SENTRA_CONFIG_DIR", "/etc/sentra-dev"))
DEFAULT_RUNTIME_DIR = Path(os.environ.get("SENTRA_RUNTIME_DIR", "/opt/sentra-dev"))
DEFAULT_DATA_DIR   = Path(os.environ.get("SENTRA_DATA_DIR", "/var/lib/sentra-dev"))
DEFAULT_CORE_URL = os.environ.get("SENTRA_CORE_URL", "https://sentrasys.dev")
DEFAULT_API_KEY_PATH = Path(os.environ.get("SENTRA_DEV_API_KEY", str(DEFAULT_CONFIG_DIR / "api.key")))
DEFAULT_USER_API_KEY_PATH = Path.home() / ".config" / "sentra-dev" / "api.key"
DEFAULT_CORE_API_KEY_PATH = Path(os.environ.get("SENTRA_CORE_API_KEY_PATH", "/etc/sentra-core/api.key"))

def resolve_config_file() -> Path:
    env_path = os.environ.get("SENTRA_DEV_CONFIG")
    if env_path:
        return Path(env_path).expanduser()
    return DEFAULT_CONFIG_DIR / "config.json"

def normalize_core_url(raw: Optional[str]) -> str:
    value = (raw or "").strip()
    if not value:
        value = DEFAULT_CORE_URL
    if "://" not in value:
        scheme = "http" if value.startswith(("localhost", "127.0.0.1", "0.0.0.0")) else "https"
        value = f"{scheme}://{value}"
    parsed = urlparse(value)
    if not parsed.netloc and parsed.path:
        parsed = urlparse("https://" + value.lstrip("/"))
    host = parsed.hostname or ""
    if host == "sentrasys.dev" and parsed.port == 65085:
        parsed = parsed._replace(scheme="https", netloc=host)
    if host == "sentrasys.dev" and parsed.scheme == "http" and parsed.port is None:
        parsed = parsed._replace(scheme="https")
    return urlunparse(parsed).rstrip("/")

def join_core_url(base: str, endpoint: str) -> str:
    base = base.rstrip("/")
    if base.endswith("/api") and endpoint.startswith("/api/"):
        endpoint = endpoint[len("/api"):]
    return base + endpoint

def api_key_paths() -> List[Path]:
    paths: List[Path] = []
    env_path = os.environ.get("SENTRA_DEV_API_KEY")
    if env_path:
        paths.append(Path(env_path))
    paths.extend([
        DEFAULT_API_KEY_PATH,
        DEFAULT_CONFIG_DIR / "api.key",
        DEFAULT_CONFIG_DIR / ".keys" / "api.key",
        DEFAULT_USER_API_KEY_PATH,
    ])
    seen = set()
    unique: List[Path] = []
    for path in paths:
        expanded = path.expanduser()
        key = str(expanded)
        if key not in seen:
            seen.add(key)
            unique.append(expanded)
    return unique

def core_api_key_paths(cfg: Dict[str, Any]) -> List[Path]:
    paths: List[Path] = []
    env_path = os.environ.get("SENTRA_CORE_API_KEY_PATH")
    if env_path:
        paths.append(Path(env_path))
    cfg_path = cfg.get("core_api_key_path")
    if cfg_path:
        paths.append(Path(str(cfg_path)))
    paths.append(DEFAULT_CORE_API_KEY_PATH)
    seen = set()
    unique: List[Path] = []
    for path in paths:
        expanded = path.expanduser()
        key = str(expanded)
        if key not in seen:
            seen.add(key)
            unique.append(expanded)
    return unique

def read_api_key() -> tuple[Optional[str], Optional[str]]:
    env_key = os.environ.get("SENTRA_API_KEY", "").strip()
    if env_key:
        return env_key, "env:SENTRA_API_KEY"
    unreadable: List[Path] = []
    for path in api_key_paths():
        if not path.exists():
            continue
        if not os.access(path, os.R_OK):
            unreadable.append(path)
            continue
        try:
            key = path.read_text().strip()
        except Exception:
            continue
        if key:
            return key, str(path)
    if unreadable:
        return None, "unreadable:" + ", ".join(str(p) for p in unreadable)
    return None, None

def read_core_api_key(cfg: Dict[str, Any]) -> tuple[Optional[str], Optional[str]]:
    env_key = os.environ.get("SENTRA_CORE_API_KEY", "").strip()
    if env_key:
        return env_key, "env:SENTRA_CORE_API_KEY"
    env_shared = os.environ.get("SENTRA_API_KEY", "").strip()
    if env_shared:
        return env_shared, "env:SENTRA_API_KEY"
    unreadable: List[Path] = []
    for path in core_api_key_paths(cfg):
        if not path.exists():
            continue
        if not os.access(path, os.R_OK):
            unreadable.append(path)
            continue
        try:
            key = path.read_text().strip()
        except Exception:
            continue
        if key:
            return key, str(path)
    if unreadable:
        return None, "unreadable:" + ", ".join(str(p) for p in unreadable)
    return None, None

def core_repo_candidates(cfg: Dict[str, Any], root: Path) -> List[Path]:
    paths: List[Path] = []
    env_path = os.environ.get("SENTRA_CORE_REPO")
    if env_path:
        paths.append(Path(env_path))
    cfg_path = cfg.get("core_repo_path")
    if cfg_path:
        paths.append(Path(str(cfg_path)))
    target_path = cfg.get("target_core_path")
    if target_path:
        paths.append(Path(str(target_path)))
    paths.extend([
        root.parent / "core",
        root.parent.parent / "core",
        Path("/opt/sentra-core"),
    ])
    seen = set()
    unique: List[Path] = []
    for path in paths:
        expanded = path.expanduser()
        key = str(expanded)
        if key not in seen:
            seen.add(key)
            unique.append(expanded)
    return unique

# Ensure ./modules is importable like your v8.0 did
sys.path.insert(0, str(Path(__file__).parent / "modules"))

# Optional DB import (independent from crypto)
DB_AVAILABLE = True
try:
    from db import SentraDevDB, SentraProduct
except Exception as e:
    print(f"Warning: DB module not available: {e}")
    DB_AVAILABLE = False
    SentraDevDB = None  # type: ignore
    SentraProduct = None  # type: ignore

# Optional crypto import
CRYPTO_AVAILABLE = True
try:
    from crypto import (
        SentraEntropy, SentraID, SentraBinding,
        SentraConfig, SentraCLI, SENTRA_MAGIC,
    )
except Exception as e:
    print(f"Warning: Crypto modules not available: {e}")
    CRYPTO_AVAILABLE = False
    SentraEntropy = SentraID = SentraBinding = SentraConfig = SentraCLI = None  # type: ignore


# --------------------------------------------------------------------------------------
# Context
# --------------------------------------------------------------------------------------
@dataclass
class SentraContext:
    """Unified context for all Sentra operations."""
    config_dir: Path
    runtime_dir: Path
    data_dir: Path
    root: Path
    config: Dict[str, Any]
    db: Optional["SentraDevDB"] = None
    entropy: Optional[Any] = None
    binding: Optional[Any] = None

    def __post_init__(self):
        # Ensure directories exist
        self.config_dir.mkdir(parents=True, exist_ok=True)
        self.runtime_dir.mkdir(parents=True, exist_ok=True)
        self.data_dir.mkdir(parents=True, exist_ok=True)

        # Initialize DB if available
        if DB_AVAILABLE:
            try:
                self.db = SentraDevDB(self.data_dir / "sentra_dev.db")  # type: ignore[arg-type]
            except Exception as e:
                print(f"Warning: DB init failed: {e}")
                self.db = None

        # Initialize crypto if available
        if CRYPTO_AVAILABLE:
            try:
                entropy_vault = self.runtime_dir / "entropy.vault"
                self.entropy = SentraEntropy(entropy_vault, SentraConfig())  # type: ignore[call-arg]
                self.binding = SentraBinding(self.entropy)  # type: ignore[call-arg]
            except Exception as e:
                print(f"Warning: Crypto init failed: {e}")
                self.entropy = None
                self.binding = None


# --------------------------------------------------------------------------------------
# System
# --------------------------------------------------------------------------------------
class SentraUnified:
    """Unified Sentra development system."""

    def __init__(self):
        self.root = Path(__file__).parent
        self.context = self._init_context()
        self.cli = (SentraCLI() if CRYPTO_AVAILABLE else None)  # noqa: F841 (kept for parity)

    def _init_context(self) -> SentraContext:
        cfg_file = resolve_config_file()
        config_dir = cfg_file.parent
        if cfg_file.exists():
            try:
                config: Dict[str, Any] = json.loads(cfg_file.read_text())
            except Exception:
                config = {}
        else:
            config = {
                "version": VERSION,
                "brand": SENTRA_BRAND,
                "creator": CREATOR,
                "core_url": DEFAULT_CORE_URL,
                "initialized": False,
                "heartbeat_interval": 3600,
                "auto_renew_days": 30,
                "system_fingerprint": "",
                "ssh_default_host": "",
                "ssh_default_user": "root",
                "target_core_path": "/opt/sentra-core",
                "core_repo_path": "",
                "core_git_remote": "origin",
                "core_git_branch": "",
                "core_reload_endpoint": "/api/core/reload",
                "core_git_pull_endpoint": "/api/core/git-pull",
                "core_modules_path": "",
                "core_api_key_path": str(DEFAULT_CORE_API_KEY_PATH),
            }
            config_dir.mkdir(parents=True, exist_ok=True)
            cfg_file.write_text(json.dumps(config, indent=2))
        defaults = {
            "version": VERSION,
            "brand": SENTRA_BRAND,
            "creator": CREATOR,
            "core_url": DEFAULT_CORE_URL,
            "initialized": False,
            "heartbeat_interval": 3600,
            "auto_renew_days": 30,
            "system_fingerprint": "",
            "ssh_default_host": "",
            "ssh_default_user": "root",
            "target_core_path": "/opt/sentra-core",
            "core_repo_path": "",
            "core_git_remote": "origin",
            "core_git_branch": "",
            "core_reload_endpoint": "/api/core/reload",
            "core_git_pull_endpoint": "/api/core/git-pull",
            "core_modules_path": "",
            "core_api_key_path": str(DEFAULT_CORE_API_KEY_PATH),
        }
        for k, v in defaults.items():
            config.setdefault(k, v)

        return SentraContext(
            config_dir=config_dir,
            runtime_dir=DEFAULT_RUNTIME_DIR,
            data_dir=DEFAULT_DATA_DIR,
            root=self.root,
            config=config,
        )

    def save_config(self) -> None:
        (self.context.config_dir / "config.json").write_text(json.dumps(self.context.config, indent=2))

    # ----------------------------------------------------------------------------------
    # Core commands
    # ----------------------------------------------------------------------------------
    def cmd_init(self, args) -> int:
        """Initialize Sentra development environment."""
        print(f"\n{'='*70}")
        print(f"  SENTRA UNIFIED SYSTEM v{VERSION}")
        print("  Initializing Development Environment")
        print(f"{'='*70}\n")

        if not CRYPTO_AVAILABLE:
            print("Error: Crypto modules not available")
            return 1

        if not self.context.entropy or not self.context.binding:
            print("Error: Crypto not initialized (entropy/binding missing)")
            return 1

        if hasattr(self.context.entropy, "verify_integrity"):
            if not self.context.entropy.verify_integrity():
                print("Error: Entropy system integrity check failed")
                return 1
        print("[+] Entropy vault: Verified")

        system_fp = self.context.binding.get_system_fingerprint()
        print(f"[+] System fingerprint: {system_fp[:48]}...")

        self.context.config["system_fingerprint"] = system_fp
        self.context.config["initialized"] = True
        self.context.config["initialized_at"] = int(time.time())
        self.save_config()

        print("\n[SUCCESS] Sentra development environment initialized")
        print(f"Config:   {self.context.config_dir / 'config.json'}")
        print(f"Runtime:  {self.context.runtime_dir}")
        print(f"Database: {self.context.data_dir / 'sentra_dev.db'}")
        return 0

    def cmd_info(self, args) -> int:
        """Display system information."""
        print(f"\n{'='*70}")
        print("  SENTRA SYSTEM INFORMATION")
        print(f"{'='*70}\n")

        print(f"Version: {VERSION}")
        print(f"Brand:   {SENTRA_BRAND}")
        print(f"Creator: @{CREATOR}")
        print("\nDirectories:")
        print(f"  Config:  {self.context.config_dir}")
        print(f"  Runtime: {self.context.runtime_dir}")
        print(f"  Data:    {self.context.data_dir}")
        print(f"  Root:    {self.context.root}")

        print("\nConfiguration:")
        for k, v in self.context.config.items():
            if k != "system_fingerprint":
                print(f"  {k}: {v}")

        if self.context.config.get("system_fingerprint"):
            fp = self.context.config["system_fingerprint"]
            print("\nSystem Fingerprint:")
            print(f"  {fp[:48]}...")

        if self.context.db:
            try:
                products = self.context.db.list_products()
                active = sum(1 for p in products if getattr(p, 'status', '') == "active")
                print("\nProducts:")
                print(f"  Total:  {len(products)}")
                print(f"  Active: {active}")
            except Exception as e:
                print(f"\nProducts: (unavailable) {e}")

        print(f"\n{'='*70}\n")
        return 0

    def cmd_register(self, args) -> int:
        """Register a new Sentra product."""
        if not CRYPTO_AVAILABLE:
            print("Error: Crypto modules not available")
            return 1
        if not self.context.config.get("initialized"):
            print("Error: System not initialized. Run 'sentra init' first")
            return 1
        if not self.context.db:
            print("Error: DB not available")
            return 1

        print(f"\n{'='*70}")
        print("  PRODUCT REGISTRATION")
        print(f"{'='*70}\n")

        try:
            entropy = self.context.entropy.get_entropy()  # type: ignore[union-attr]
            sentra_id = SentraID.generate(args.type.upper(), args.name, entropy)  # type: ignore[call-arg]
        except Exception as e:
            print(f"Error: {e}")
            return 1

        print(f"[+] Generated SENTRA_ID: {sentra_id}")
        components = SentraID.parse(sentra_id)  # type: ignore[attr-defined]
        print(f"    Type:      {components['type']}")
        print(f"    Version:   {components['version']}")
        print(f"    Timestamp: {time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(components['created_at']))}")

        metadata = {
            "description": args.description or "",
            "author": CREATOR,
            "platform": args.platform or "multi",
            "created_by": "sentra-unified",
            "created_at_iso": time.strftime('%Y-%m-%d %H:%M:%S'),
        }

        binding_token = self.context.binding.bind_product(sentra_id, metadata)  # type: ignore[union-attr]
        print(f"\n[+] System binding: {binding_token[:48]}...")

        features: Dict[str, Any] = {}
        if args.features:
            for feat in args.features.split(","):
                if "=" in feat:
                    k, v = feat.split("=", 1)
                    features[k.strip()] = v.strip()
                else:
                    features[feat.strip()] = True

        expires_at = 0
        if args.days and args.days > 0:
            expires_at = int(time.time()) + (args.days * 86400)

        product = SentraProduct(  # type: ignore[call-arg]
            sentra_id=sentra_id,
            product_type=args.type.upper(),
            product_name=args.name,
            version=args.version or "1.0.0",
            binding_token=binding_token,
            registered_at=int(time.time()),
            expires_at=expires_at,
            status="active",
            features=features,
            metadata=metadata,
        )

        if self.context.db.register_product(product):  # type: ignore[union-attr]
            print(f"\n[SUCCESS] Product registered")
            print(f"\n{'='*70}")
            print(f"SENTRA_ID: {sentra_id}")
            print(f"Name:      {args.name}")
            print(f"Type:      {args.type.upper()}")
            print(f"Version:   {product.version}")
            print(f"Status:    {product.status}")
            if expires_at > 0:
                exp_str = time.strftime('%Y-%m-%d', time.localtime(expires_at))
                # `days_remaining()` may not exist if db model differs; guard it
                days = getattr(product, 'days_remaining', lambda: (expires_at - int(time.time()))/86400)()
                print(f"Expires:   {exp_str} ({float(days):.1f} days)")
            else:
                print("Expires:   Never (Perpetual)")
            print("\nBinding Token:")
            print(f"{binding_token}")
            print(f"{'='*70}\n")
            return 0
        else:
            print("\n[FAILED] Product registration failed")
            return 1

    def cmd_list(self, args) -> int:
        """List registered products."""
        if not self.context.db:
            print("DB not available")
            return 1
        products = self.context.db.list_products(  # type: ignore[union-attr]
            status=(args.status if getattr(args, 'status', None) else None),
            product_type=(args.type if getattr(args, 'type', None) else None),
        )
        if not products:
            print("No products registered")
            return 0

        print(f"\n{'='*70}")
        print(f"  REGISTERED PRODUCTS ({len(products)} total)")
        print(f"{'='*70}\n")
        for p in products:
            status_icon = {
                "active": "[ACTIVE]",
                "suspended": "[PAUSED]",
                "revoked": "[REVOKED]",
            }.get(getattr(p, 'status', ''), "[?]")
            print(f"{status_icon} {getattr(p,'product_name','?')} ({getattr(p,'product_type','?')})")
            print(f"  SENTRA_ID:  {getattr(p,'sentra_id','?')}")
            print(f"  Version:    {getattr(p,'version','?')}")
            reg_at = getattr(p, 'registered_at', 0)
            print(f"  Registered: {time.strftime('%Y-%m-%d', time.localtime(reg_at)) if reg_at else '?'}")
            exp_at = getattr(p, 'expires_at', 0)
            if exp_at and exp_at > 0:
                days = getattr(p, 'days_remaining', lambda: (exp_at - int(time.time()))/86400)()
                print(f"  Expires:   {time.strftime('%Y-%m-%d', time.localtime(exp_at))} ({float(days):.1f} days)")
            else:
                print("  Expires:   Never")
            print(f"  Auth Count:{getattr(p,'auth_count',0)}")
            last = getattr(p, 'last_auth', 0)
            if last:
                print(f"  Last Auth: {time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(last))}")
            print()
        return 0

    def cmd_verify(self, args) -> int:
        """Verify a product."""
        if not CRYPTO_AVAILABLE:
            print("Error: Crypto modules not available")
            return 1
        if not self.context.db:
            print("Error: DB not available")
            return 1

        product = self.context.db.get_product(args.sentra_id)  # type: ignore[union-attr]
        if not product:
            print(f"[FAILED] Product not found: {args.sentra_id}")
            return 1

        print(f"\n{'='*70}")
        print(f"  VERIFYING: {getattr(product,'product_name','?')}")
        print(f"{'='*70}\n")

        try:
            if hasattr(SentraID, "validate") and not SentraID.validate(args.sentra_id):  # type: ignore[operator]
                print("[FAILED] Invalid SENTRA_ID format")
                return 1
        except Exception:
            pass

        if hasattr(product, "is_valid") and not product.is_valid():
            reason = f"Product status: {getattr(product,'status','?')}"
            exp = getattr(product, 'expires_at', 0)
            if exp and time.time() > exp:
                reason = "License expired"
            print(f"[FAILED] {reason}")
            return 1

        system_fp = self.context.binding.get_system_fingerprint()  # type: ignore[union-attr]
        if not self.context.binding.verify_binding(args.sentra_id, getattr(product,'binding_token',''), getattr(product,'metadata',{})):  # type: ignore[union-attr]
            print("[FAILED] System binding verification failed")
            return 1

        print("[SUCCESS] Product authenticated")
        print(f"\n  Product:  {getattr(product,'product_name','?')}")
        print(f"  Type:     {getattr(product,'product_type','?')}")
        print(f"  Version:  {getattr(product,'version','?')}")
        print(f"  Status:   {getattr(product,'status','?')}")
        print(f"  System:   {system_fp[:48]}...")
        exp = getattr(product, 'expires_at', 0)
        if exp and exp > 0:
            days = getattr(product, 'days_remaining', lambda: (exp - int(time.time()))/86400)()
            print(f"  Days Remaining: {float(days):.1f}")
        else:
            print("  License:  Perpetual")
        print(f"\n{'='*70}\n")
        return 0

    def cmd_package(self, args) -> int:
        """Create deployment package."""
        import tarfile
        src = Path(args.source) if getattr(args, 'source', None) else self.context.root
        out_dir = self.context.data_dir / "packages"
        out_dir.mkdir(exist_ok=True)
        ts = time.strftime("%Y%m%d-%H%M%S")
        out_file = out_dir / f"sentra-pkg-{ts}.tar.gz"
        print(f"Creating package from: {src}")
        print(f"Output: {out_file}")
        with tarfile.open(out_file, "w:gz") as tar:
            tar.add(str(src), arcname=src.name)
        pkg_info = {
            "source": str(src),
            "output": str(out_file),
            "timestamp": ts,
            "created_at": int(time.time()),
        }
        (self.context.runtime_dir / "last-package.json").write_text(json.dumps(pkg_info, indent=2))
        print(f"[SUCCESS] Package created: {out_file}")
        return 0

    def cmd_sync_core(self, args) -> int:
        """Sync to Sentra Core server."""
        import subprocess
        host = getattr(args, 'host', None) or self.context.config.get("ssh_default_host")
        user = getattr(args, 'user', None) or self.context.config.get("ssh_default_user", "root")
        dest = getattr(args, 'dest', None) or self.context.config.get("target_core_path", "/opt/sentra-core")
        if not host:
            print("Error: No host specified. Use --host or configure ssh_default_host")
            return 1
        src = str(self.context.root)
        remote = f"{user}@{host}:{dest}"
        print(f"Syncing: {src} -> {remote}")
        try:
            subprocess.run([
                "rsync", "-avz", "--no-owner", "--no-group",
                "--exclude", "__pycache__",
                "--exclude", "*.pyc",
                "--exclude", ".git",
                "--exclude", "venv",
                src + "/", remote
            ], check=True)
            print("[SUCCESS] Sync completed")
            return 0
        except subprocess.CalledProcessError as e:
            print(f"[FAILED] Sync failed: {e}")
            return 1
        except FileNotFoundError:
            print("[FAILED] rsync not found. Install rsync to use this feature")
            return 1

    def cmd_push_update(self, args) -> int:
        """Package and push an update to Sentra Core Repo."""
        import base64
        import zipfile
        import urllib.request
        import urllib.error

        core_url = normalize_core_url(self.context.config.get("core_url", DEFAULT_CORE_URL))
        if not core_url:
            print("[FAILED] core_url not configured")
            return 1

        api_key, key_source = read_api_key()
        if not api_key:
            print("[FAILED] API key missing (set SENTRA_API_KEY or api.key)")
            return 1

        src = Path(args.source) if args.source else self.context.root
        if not src.exists():
            print(f"[FAILED] source path not found: {src}")
            return 1

        package_path = src
        if src.is_dir():
            ts = time.strftime("%Y%m%d-%H%M%S")
            package_path = self.context.runtime_dir / f"sentra-update-{ts}.zip"
            exclude_dirs = {".git", "__pycache__", ".venv", "venv", ".pytest_cache"}
            with zipfile.ZipFile(package_path, "w", zipfile.ZIP_DEFLATED) as zf:
                for path in src.rglob("*"):
                    if path.is_dir():
                        continue
                    if path.suffix == ".pyc":
                        continue
                    if any(part in exclude_dirs for part in path.parts):
                        continue
                    zf.write(path, path.relative_to(src))
        elif src.is_file() and src.suffix.lower() != ".zip":
            print("[WARN] source is not a .zip; sending raw file contents")

        payload_data = base64.b64encode(package_path.read_bytes()).decode()

        headers = {
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json",
        }

        try:
            import requests  # optional
        except Exception:
            requests = None  # type: ignore

        def http_json(method: str, url: str, payload: Dict[str, Any]) -> tuple[Optional[int], Dict[str, Any], str]:
            data = json.dumps(payload)
            if requests:
                r = requests.request(method, url, json=payload, headers=headers, timeout=60)
                try:
                    body = r.json()
                except Exception:
                    body = {}
                return r.status_code, body, r.text
            req = urllib.request.Request(url, data=data.encode(), headers=headers, method=method)
            try:
                with urllib.request.urlopen(req, timeout=60) as resp:
                    raw = resp.read().decode()
                    try:
                        body = json.loads(raw) if raw else {}
                    except Exception:
                        body = {}
                    return resp.status, body, raw
            except urllib.error.HTTPError as e:
                raw = e.read().decode()
                try:
                    body = json.loads(raw) if raw else {}
                except Exception:
                    body = {}
                return e.code, body, raw
            except Exception as e:
                print(f"[FAILED] {method} {url}: {e}")
                return None, {}, ""

        app_id = args.app_id
        if not app_id:
            if not args.name or not args.type:
                print("[FAILED] provide --app-id or (--name and --type) to create app")
                return 1
            create_payload = {
                "name": args.name,
                "description": args.description or "",
                "package_type": args.type,
                "author": args.author or CREATOR,
                "homepage": args.homepage or "",
                "repo_url": args.repo_url or "",
                "tags": [t for t in (args.tags or "").split(",") if t.strip()],
            }
            create_url = join_core_url(core_url, "/api/sentra-repo/apps")
            status, body, raw = http_json("POST", create_url, create_payload)
            if status not in (200, 201):
                print(f"[FAILED] app create failed ({status}): {raw}")
                return 1
            app_id = body.get("app_id")
            if not app_id:
                print("[FAILED] app_id missing in response")
                return 1

        upload_payload = {
            "version": args.version,
            "channel": args.channel,
            "source_base64": payload_data,
            "changelog": args.changelog or "",
            "min_core_version": args.min_core_version or "",
            "dependencies": [d for d in (args.dependencies or "").split(",") if d.strip()],
        }
        upload_url = join_core_url(core_url, f"/api/sentra-repo/apps/{app_id}/source")
        status, body, raw = http_json("POST", upload_url, upload_payload)
        if status not in (200, 201):
            print(f"[FAILED] upload failed ({status}): {raw}")
            return 1
        version_id = body.get("version_id")
        if not version_id:
            print("[FAILED] version_id missing in response")
            return 1

        if not args.no_publish:
            actor = args.actor or CREATOR
            approve_url = join_core_url(core_url, f"/api/sentra-repo/versions/{version_id}/approve")
            status, _, raw = http_json("POST", approve_url, {"approved_by": actor})
            if status not in (200, 201):
                print(f"[FAILED] approve failed ({status}): {raw}")
                return 1

            sign_url = join_core_url(core_url, f"/api/sentra-repo/versions/{version_id}/sign-publish")
            status, _, raw = http_json("POST", sign_url, {"signed_by": actor})
            if status not in (200, 201):
                print(f"[FAILED] sign/publish failed ({status}): {raw}")
                return 1

            set_url = join_core_url(core_url, f"/api/sentra-repo/apps/{app_id}/channels/{args.channel}")
            status, _, raw = http_json("POST", set_url, {"version_id": version_id, "set_by": actor})
            if status not in (200, 201):
                print(f"[FAILED] set channel failed ({status}): {raw}")
                return 1

        print(f"[SUCCESS] update pushed: app={app_id} version={args.version} channel={args.channel} version_id={version_id}")
        return 0

    def cmd_update_core(self, args) -> int:
        """Send updated core modules and trigger reload."""
        import base64
        import zipfile
        import urllib.request
        import urllib.error

        core_url = normalize_core_url(self.context.config.get("core_url", DEFAULT_CORE_URL))
        if not core_url:
            print("[FAILED] core_url not configured")
            return 1

        api_key, key_source = read_core_api_key(self.context.config)
        if not api_key:
            if key_source and key_source.startswith("unreadable:"):
                paths = key_source.split("unreadable:", 1)[1].strip()
                print(f"[FAILED] core API key not readable ({paths})")
                print("Set SENTRA_CORE_API_KEY or fix permissions")
            else:
                print("[FAILED] core API key missing (set SENTRA_CORE_API_KEY or core_api_key_path)")
            return 1

        src: Optional[Path]
        if args.source:
            src = Path(args.source)
        else:
            candidates: List[Path] = []
            cfg_path = self.context.config.get("core_modules_path")
            if cfg_path:
                candidates.append(Path(str(cfg_path)))
            env_path = os.environ.get("SENTRA_CORE_MODULES")
            if env_path:
                candidates.append(Path(env_path))
            root = self.context.root
            candidates.extend([
                root.parent / "core" / "app" / "modules",
                root.parent.parent / "core" / "app" / "modules",
                Path("/opt/sentra-core/app/modules"),
                Path("/opt/sentra-core/modules"),
            ])
            src = next((p for p in candidates if p.exists()), None)
            if not src:
                print("[FAILED] --source is required (no core modules path found)")
                print("Set SENTRA_CORE_MODULES or config core_modules_path")
                return 1
            print(f"[INFO] using core modules path: {src}")
        if not src.exists():
            print(f"[FAILED] source path not found: {src}")
            return 1

        archive_path = src
        if not (src.is_file() and src.suffix.lower() == ".zip"):
            ts = time.strftime("%Y%m%d-%H%M%S")
            archive_path = self.context.runtime_dir / f"core-update-{ts}.zip"
            exclude_dirs = {".git", "__pycache__", ".venv", "venv", ".pytest_cache", "node_modules"}
            with zipfile.ZipFile(archive_path, "w", zipfile.ZIP_DEFLATED) as zf:
                if src.is_file():
                    zf.write(src, src.name)
                else:
                    for path in src.rglob("*"):
                        if path.is_dir():
                            continue
                        if path.suffix == ".pyc":
                            continue
                        if any(part in exclude_dirs for part in path.parts):
                            continue
                        zf.write(path, path.relative_to(src))

        payload = {
            "archive_b64": base64.b64encode(archive_path.read_bytes()).decode(),
            "strip_prefix": args.strip_prefix or "",
            "reload": not args.no_reload,
        }

        headers = {
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json",
        }

        try:
            import requests  # optional
        except Exception:
            requests = None  # type: ignore

        url = join_core_url(core_url, "/api/core/update-modules")
        if requests:
            try:
                r = requests.post(url, json=payload, headers=headers, timeout=60)
                print(f"[SUCCESS] core update: {r.status_code} {url}")
                try:
                    body = r.json()
                except Exception:
                    body = {}
                if body:
                    print(json.dumps(body, indent=2))
                return 0 if r.status_code < 400 else 1
            except Exception as e:
                print(f"[FAILED] core update failed: {e}")
                return 1

        req = urllib.request.Request(url, data=json.dumps(payload).encode(), headers=headers, method="POST")
        try:
            with urllib.request.urlopen(req, timeout=60) as resp:
                raw = resp.read().decode()
                print(f"[SUCCESS] core update: {resp.status} {url}")
                if raw:
                    print(raw)
                return 0 if resp.status < 400 else 1
        except urllib.error.HTTPError as e:
            raw = e.read().decode()
            print(f"[FAILED] core update: {e.code} {raw}")
            return 1
        except Exception as e:
            print(f"[FAILED] core update failed: {e}")
            return 1

    def cmd_push_core(self, args) -> int:
        """Git push the core repo and trigger a reload."""
        import subprocess
        import urllib.request
        import urllib.error

        candidates: List[Path]
        if args.repo:
            candidates = [Path(args.repo).expanduser()]
        else:
            candidates = core_repo_candidates(self.context.config, self.context.root)

        repo = next((p for p in candidates if p.exists() and (p / ".git").exists()), None)
        if not repo:
            checked = [str(p) for p in candidates if p.exists()]
            if args.repo:
                print(f"[FAILED] not a git repo: {candidates[0]}")
            else:
                print("[FAILED] no git repo found for core")
                if checked:
                    print("Checked:", ", ".join(checked))
                print("Set core_repo_path, or pass --repo, or init git in the core directory")
            return 1
        repo = repo.resolve()

        try:
            subprocess.run(
                ["git", "-C", str(repo), "rev-parse", "--is-inside-work-tree"],
                check=True,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True,
            )
        except FileNotFoundError:
            print("[FAILED] git not found. Install git to use push-core")
            return 1
        except subprocess.CalledProcessError:
            print(f"[FAILED] not a git repo: {repo}")
            return 1

        try:
            dirty = subprocess.run(
                ["git", "-C", str(repo), "status", "--porcelain"],
                check=True,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True,
            ).stdout.strip()
        except subprocess.CalledProcessError:
            dirty = ""

        if dirty and args.require_clean:
            print("[FAILED] core repo has uncommitted changes (commit/stash or rerun without --require-clean)")
            return 1
        if dirty and not args.no_auto_commit:
            # Auto-commit changes
            print(f"[INFO] auto-committing {len(dirty.splitlines())} changed file(s)")
            try:
                subprocess.run(
                    ["git", "-C", str(repo), "add", "-A"],
                    check=True,
                    stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE,
                )
                commit_msg = args.commit_message or f"Auto-commit: {time.strftime('%Y-%m-%d %H:%M:%S')}"
                subprocess.run(
                    ["git", "-C", str(repo), "commit", "-m", commit_msg],
                    check=True,
                    stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE,
                )
                print(f"[OK] committed: {commit_msg}")
            except subprocess.CalledProcessError as e:
                print(f"[FAILED] auto-commit failed: {e}")
                return 1
        elif dirty:
            print("[WARN] core repo has uncommitted changes; push will not include them")

        remote = args.remote or self.context.config.get("core_git_remote") or "origin"
        branch = args.branch or self.context.config.get("core_git_branch") or ""
        ssh_port = args.ssh_port or self.context.config.get("core_git_ssh_port")
        ssh_key = args.ssh_key or self.context.config.get("core_git_ssh_key")
        ssh_host = args.ssh_host or self.context.config.get("core_git_ssh_host")
        ssh_user = args.ssh_user or self.context.config.get("core_git_ssh_user")
        if ssh_port is not None:
            try:
                ssh_port = int(ssh_port)
            except (TypeError, ValueError):
                ssh_port = None
        if ssh_port is None and (ssh_host or ssh_key):
            ssh_port = 22
        if not branch:
            try:
                branch = subprocess.run(
                    ["git", "-C", str(repo), "rev-parse", "--abbrev-ref", "HEAD"],
                    check=True,
                    stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE,
                    text=True,
                ).stdout.strip()
            except subprocess.CalledProcessError:
                branch = ""
        if not branch or branch == "HEAD":
            print("[FAILED] could not determine branch (use --branch)")
            return 1

        cfg_path = self.context.config_dir / "config.json"
        if cfg_path.exists() and os.access(cfg_path, os.W_OK):
            updated = False
            if not self.context.config.get("core_repo_path"):
                self.context.config["core_repo_path"] = str(repo)
                updated = True
            if not self.context.config.get("core_git_remote") and remote:
                self.context.config["core_git_remote"] = remote
                updated = True
            if not self.context.config.get("core_git_branch") and branch:
                self.context.config["core_git_branch"] = branch
                updated = True
            if args.ssh_port and not self.context.config.get("core_git_ssh_port"):
                self.context.config["core_git_ssh_port"] = int(args.ssh_port)
                updated = True
            if args.ssh_key and not self.context.config.get("core_git_ssh_key"):
                self.context.config["core_git_ssh_key"] = str(Path(args.ssh_key).expanduser())
                updated = True
            if args.ssh_host and not self.context.config.get("core_git_ssh_host"):
                self.context.config["core_git_ssh_host"] = str(args.ssh_host)
                updated = True
            if args.ssh_user and not self.context.config.get("core_git_ssh_user"):
                self.context.config["core_git_ssh_user"] = str(args.ssh_user)
                updated = True
            if updated:
                try:
                    self.save_config()
                except Exception:
                    pass

        ahead_count: Optional[int] = None
        remote_ref = f"refs/remotes/{remote}/{branch}"
        try:
            exists = subprocess.run(
                ["git", "-C", str(repo), "show-ref", "--verify", "--quiet", remote_ref],
                check=False,
            )
            if exists.returncode == 0:
                ahead_raw = subprocess.run(
                    ["git", "-C", str(repo), "rev-list", "--count", f"{remote}/{branch}..{branch}"],
                    check=True,
                    stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE,
                    text=True,
                ).stdout.strip()
                if ahead_raw.isdigit():
                    ahead_count = int(ahead_raw)
        except Exception:
            ahead_count = None

        if ahead_count == 0:
            print("[INFO] no new commits to push; skipping reload")
            return 0

        if not args.push_only:
            api_key, key_source = read_core_api_key(self.context.config)
            if not api_key:
                if key_source and key_source.startswith("unreadable:"):
                    paths = key_source.split("unreadable:", 1)[1].strip()
                    print(f"[FAILED] core API key not readable ({paths})")
                    print("Set SENTRA_CORE_API_KEY or fix permissions")
                else:
                    print("[FAILED] core API key missing (set SENTRA_CORE_API_KEY or core_api_key_path)")
                return 1

        cmd = ["git", "-C", str(repo), "push", "--progress"]
        if args.verbose:
            cmd.append("--verbose")
        if args.set_upstream:
            cmd.append("--set-upstream")
        if args.force:
            cmd.append("--force-with-lease")
        cmd.extend([remote, branch])
        if ahead_count is not None:
            print(f"[INFO] pushing {ahead_count} commit(s) to {remote}/{branch}")
        print(f"[INFO] git push: {' '.join(cmd)}")
        try:
            env = os.environ.copy()
            env.setdefault("GIT_PROGRESS_DELAY", "0")
            env.setdefault("GIT_TERMINAL_PROMPT", "0")
            if ssh_host or ssh_key or ssh_port:
                ssh_cmd = ["ssh", "-o", "BatchMode=yes", "-o", "ConnectTimeout=10"]
                ssh_cmd.extend(["-F", "/dev/null"])
                if ssh_host:
                    ssh_cmd.extend(["-o", f"HostName={ssh_host}"])
                if ssh_user:
                    ssh_cmd.extend(["-l", str(ssh_user)])
                if ssh_key:
                    ssh_cmd.extend(["-i", str(Path(ssh_key).expanduser()), "-o", "IdentitiesOnly=yes"])
                if ssh_port:
                    ssh_cmd.extend(["-p", str(ssh_port)])
                env["GIT_SSH_COMMAND"] = " ".join(ssh_cmd)
                if args.verbose:
                    print(f"[INFO] GIT_SSH_COMMAND={env['GIT_SSH_COMMAND']}")
            elif args.verbose:
                print("[INFO] Using default SSH configuration (from ~/.ssh/config)")
            subprocess.run(cmd, check=True, env=env, stderr=subprocess.STDOUT)
        except subprocess.CalledProcessError as e:
            print(f"[FAILED] git push failed: {e}")
            return 1
        
        # Deploy to live location via SSH if specified
        deploy_to = args.deploy_to or self.context.config.get("core_deploy_path")
        ssh_host = args.deploy_host or self.context.config.get("core_deploy_host") or "sentra-vps"
        
        if deploy_to and not args.push_only:
            from datetime import datetime
            
            # Create backup on server before pulling
            if not args.no_backup:
                timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
                backup_script = f"""
cd {deploy_to} && \\
HASH=$(git rev-parse --short HEAD 2>/dev/null || echo 'unknown') && \\
BACKUP_DIR="{deploy_to}/backups" && \\
BACKUP_NAME="core_backup_{timestamp}_$HASH" && \\
mkdir -p "$BACKUP_DIR/$BACKUP_NAME" && \\
for item in app main.py sentra_server.py state.json; do
    if [ -e "$item" ]; then
        if [ -d "$item" ]; then
            cp -r "$item" "$BACKUP_DIR/$BACKUP_NAME/" 2>/dev/null || true
        else
            cp "$item" "$BACKUP_DIR/$BACKUP_NAME/" 2>/dev/null || true
        fi
    fi
done && \\
cd "$BACKUP_DIR" && \\
ls -dt core_backup_* 2>/dev/null | tail -n +11 | xargs rm -rf 2>/dev/null || true && \\
echo "Backup created: $BACKUP_NAME"
"""
                backup_cmd = ["ssh", ssh_host, backup_script]
                try:
                    backup_result = subprocess.run(backup_cmd, capture_output=True, text=True, timeout=30)
                    if backup_result.returncode == 0:
                        print(f"[INFO] Server backup: {backup_result.stdout.strip()}")
                    else:
                        print(f"[WARN] Server backup failed: {backup_result.stderr.strip()}")
                except Exception as e:
                    print(f"[WARN] Server backup failed: {e}")
            
            print(f"[INFO] Deploying to {ssh_host}:{deploy_to}")
            try:
                # SSH into the server and pull the latest changes
                pull_cmd = f"cd {deploy_to} && git fetch origin {branch} && git reset --hard origin/{branch} && echo 'Deployment successful'"
                ssh_pull_cmd = ["ssh", ssh_host, pull_cmd]
                result = subprocess.run(ssh_pull_cmd, capture_output=True, text=True)
                if result.returncode == 0:
                    print(f"[SUCCESS] Deployed to {ssh_host}:{deploy_to}")
                    if args.verbose and result.stdout:
                        print(result.stdout)
                    
                    # Hot reload instead of full restart (much faster!)
                    if args.restart_service:
                        service_name = args.restart_service if isinstance(args.restart_service, str) else "sentra-core"
                        
                        # Try hot-reload first (no downtime) unless --force-restart
                        if not args.force_restart:
                            print(f"[INFO] Attempting hot-reload (no downtime)...")
                            try:
                                import urllib.request
                                import urllib.error
                                hot_reload_url = f"http://localhost:18085/api/core/hot-reload"
                                hot_reload_cmd = ["ssh", ssh_host, f"curl -X POST {hot_reload_url} -s"]
                                hot_reload_result = subprocess.run(hot_reload_cmd, capture_output=True, text=True, timeout=5)
                                
                                if hot_reload_result.returncode == 0 and '"ok":true' in hot_reload_result.stdout.lower():
                                    print(f"[SUCCESS] ✨ Hot-reload successful (0s downtime)")
                                else:
                                    raise Exception("Hot-reload failed, falling back to restart")
                            except Exception as e:
                                # Fall back to full restart
                                print(f"[INFO] Hot-reload unavailable, restarting service: {service_name}")
                                restart_cmd = ["ssh", ssh_host, f"sudo systemctl restart {service_name}"]
                                restart_result = subprocess.run(restart_cmd, capture_output=True, text=True)
                                if restart_result.returncode == 0:
                                    print(f"[SUCCESS] Service {service_name} restarted (~2-3s downtime)")
                                else:
                                    print(f"[WARN] Service restart failed: {restart_result.stderr}")
                        else:
                            # Force full restart
                            print(f"[INFO] Force-restarting service: {service_name}")
                            restart_cmd = ["ssh", ssh_host, f"sudo systemctl restart {service_name}"]
                            restart_result = subprocess.run(restart_cmd, capture_output=True, text=True)
                            if restart_result.returncode == 0:
                                print(f"[SUCCESS] Service {service_name} restarted")
                            else:
                                print(f"[WARN] Service restart failed: {restart_result.stderr}")
                else:
                    print(f"[FAILED] Deployment failed: {result.stderr}")
                    return 1
            except Exception as e:
                print(f"[FAILED] Deployment failed: {e}")
                return 1
        
        if args.push_only:
            print("[INFO] push-only: skipping reload")
            return 0

        print("[SUCCESS] Push and deployment completed")
        return 0

    def cmd_set_core(self, args) -> int:
        self.context.config["core_url"] = args.url
        self.save_config()
        print(f"[SUCCESS] Core URL set to: {args.url}")
        return 0

    def cmd_config(self, args) -> int:
        if args.config_cmd == "set":
            self.context.config[args.key] = args.value
            self.save_config()
            print(f"[SUCCESS] {args.key} = {args.value}")
            return 0
        elif args.config_cmd == "get":
            value = self.context.config.get(args.key)
            if value is not None:
                print(f"{args.key} = {value}")
                return 0
            else:
                print(f"Key not found: {args.key}")
                return 1
        elif args.config_cmd == "list":
            print("\nConfiguration:")
            for k, v in sorted(self.context.config.items()):
                print(f"  {k} = {v}")
            print()
            return 0
        return 0

    # NEW: heartbeat that works without crypto/DB
    def cmd_heartbeat(self, args) -> int:
        import socket
        ts = int(time.time())
        host = socket.gethostname()
        print(f"\n{'='*70}")
        print("  SENTRA :: HEARTBEAT")
        print(f"{'='*70}\n")
        print(f"Timestamp : {ts}")
        print(f"Host      : {host}")
        fp = None
        if CRYPTO_AVAILABLE and self.context.binding:
            try:
                fp = self.context.binding.get_system_fingerprint()
            except Exception:
                fp = None
        if fp:
            print(f"System FP : {fp[:48]}...")
        core_url = normalize_core_url(self.context.config.get("core_url", DEFAULT_CORE_URL))
        if core_url:
            try:
                try:
                    import requests  # optional
                except Exception:
                    requests = None  # type: ignore
                if requests:
                    url = join_core_url(core_url, "/api/admin/heartbeat")
                    headers = {}
                    api_key, key_source = read_api_key()
                    if api_key:
                        headers["Authorization"] = f"Bearer {api_key}"
                    else:
                        if key_source and key_source.startswith("unreadable:"):
                            paths = key_source.split("unreadable:", 1)[1].strip()
                            print(f"\nCore ping : API key not readable ({paths})")
                            print("Core ping : set SENTRA_API_KEY or fix permissions")
                        else:
                            print(f"\nCore ping : missing API key (set SENTRA_API_KEY or {DEFAULT_CONFIG_DIR}/api.key)")
                            print(f"Core ping : also checks {DEFAULT_CONFIG_DIR}/.keys/api.key")
                    cfg = self.context.config
                    cfg_fp = cfg.get("system_fingerprint", "")
                    system_fp = fp or cfg_fp or ""
                    payload = {
                        "ts": ts,
                        "host": host,
                        "dev_fingerprint": system_fp,
                        "system_fp": system_fp,
                        "brand": cfg.get("brand", SENTRA_BRAND),
                        "creator": cfg.get("creator", CREATOR),
                        "version": VERSION,
                        "agent": "sentra-unified",
                        "core_url": cfg.get("core_url", DEFAULT_CORE_URL),
                        "heartbeat_interval": cfg.get("heartbeat_interval"),
                        "auto_renew_days": cfg.get("auto_renew_days"),
                        "initialized": bool(cfg.get("initialized")),
                        "initialized_at": cfg.get("initialized_at"),
                    }
                    r = requests.post(url, json=payload, timeout=3, headers=headers)
                    print(f"\nCore ping : {r.status_code} {url}")
                else:
                    print("\nCore ping : skipped (python-requests not installed)")
            except Exception as e:
                print(f"\nCore ping : failed ({e})")
        print("\n[SUCCESS] Local heartbeat recorded\n")
        return 0


# --------------------------------------------------------------------------------------
# CLI
# --------------------------------------------------------------------------------------

def main() -> int:
    parser = argparse.ArgumentParser(
        prog="sentra",
        description="Sentra Unified Development System v" + VERSION,
        epilog=f"Created by @{CREATOR} | https://sentra.dev",
    )
    subparsers = parser.add_subparsers(dest="cmd", help="Commands")

    # Init / Info
    subparsers.add_parser("init", help="Initialize Sentra environment")
    subparsers.add_parser("info", help="Display system information")

    # Register product
    p_reg = subparsers.add_parser("register", help="Register a product")
    p_reg.add_argument("name", help="Product name")
    p_reg.add_argument("type", help="Product type (APP, WEB, OS, etc)")
    p_reg.add_argument("--version", default="1.0.0")
    p_reg.add_argument("--description", help="Description")
    p_reg.add_argument("--platform", help="Platform")
    p_reg.add_argument("--days", type=int, help="License days (0=perpetual)")
    p_reg.add_argument("--features", help="Comma-separated features")

    # List products
    p_list = subparsers.add_parser("list", help="List products")
    p_list.add_argument("--status", help="Filter by status")
    p_list.add_argument("--type", help="Filter by type")

    # Verify product
    p_verify = subparsers.add_parser("verify", help="Verify product")
    p_verify.add_argument("sentra_id", help="SENTRA_ID to verify")

    # Package
    p_pkg = subparsers.add_parser("package", help="Create deployment package")
    p_pkg.add_argument("--source", help="Source directory")

    # Sync to core
    p_sync = subparsers.add_parser("sync-core", help="Sync to Sentra Core server")
    p_sync.add_argument("--host", help="SSH host")
    p_sync.add_argument("--user", help="SSH user")
    p_sync.add_argument("--dest", help="Destination path")

    # Update core modules (manual override)
    p_update = subparsers.add_parser("update-core", help="Manual override: upload core modules and reload")
    p_update.add_argument("--source", help="File or directory to package (defaults to core modules path)")
    p_update.add_argument("--strip-prefix", help="Strip prefix from archive paths")
    p_update.add_argument("--no-reload", action="store_true", help="Upload only (skip reload)")

    # Git push core repo + reload
    p_git = subparsers.add_parser("push-core", help="Git push core repo and reload modules")
    p_git.add_argument("--repo", help="Path to core git repo")
    p_git.add_argument("--remote", help="Git remote (default: origin)")
    p_git.add_argument("--branch", help="Branch to push (default: current)")
    p_git.add_argument("--ssh-port", type=int, help="SSH port for git push (default: core_git_ssh_port)")
    p_git.add_argument("--ssh-key", help="SSH identity file for git push (default: core_git_ssh_key)")
    p_git.add_argument("--ssh-host", help="SSH host override for git push (default: core_git_ssh_host)")
    p_git.add_argument("--ssh-user", help="SSH user override for git push (default: core_git_ssh_user)")
    p_git.add_argument("--set-upstream", action="store_true", help="Set upstream on push")
    p_git.add_argument("--force", action="store_true", help="Force push (with lease)")
    p_git.add_argument("--require-clean", action="store_true", help="Fail if repo has uncommitted changes")
    p_git.add_argument("--no-auto-commit", action="store_true", help="Disable auto-commit of changes")
    p_git.add_argument("--commit-message", "-m", help="Custom commit message for auto-commit")
    p_git.add_argument("--push-only", action="store_true", help="Only git push; skip deployment")
    p_git.add_argument("--verbose", action="store_true", help="Enable verbose git push output")
    p_git.add_argument("--no-backup", action="store_true", help="Skip creating backup before git push")
    p_git.add_argument("--deploy-to", help="Deploy to this path on remote server (default: core_deploy_path)")
    p_git.add_argument("--deploy-host", help="SSH host for deployment (default: sentra-vps)")
    p_git.add_argument("--restart-service", nargs='?', const="sentra-core", help="Restart service after deployment (default: sentra-core)")
    p_git.add_argument("--force-restart", action="store_true", help="Force full restart instead of hot-reload")


    # Push update to core repo
    p_push = subparsers.add_parser("push-update", help="Push update to Sentra Core repo")
    p_push.add_argument("--source", help="File or directory to package")
    p_push.add_argument("--app-id", help="Existing app id")
    p_push.add_argument("--name", help="Create app name")
    p_push.add_argument("--type", help="Package type (wordpress_theme, wordpress_plugin, python_module, python_package, docker_image, static_site, firmware, config)")
    p_push.add_argument("--description", help="App description")
    p_push.add_argument("--version", required=True, help="Version string")
    p_push.add_argument("--channel", default="dev", help="Release channel")
    p_push.add_argument("--changelog", help="Changelog text")
    p_push.add_argument("--min-core-version", help="Minimum core version")
    p_push.add_argument("--dependencies", help="Comma-separated dependencies")
    p_push.add_argument("--tags", help="Comma-separated tags")
    p_push.add_argument("--author", help="Author name")
    p_push.add_argument("--homepage", help="Homepage URL")
    p_push.add_argument("--repo-url", help="Repository URL")
    p_push.add_argument("--actor", help="Actor name for approve/sign/set")
    p_push.add_argument("--no-publish", action="store_true", help="Upload only (skip approve/sign/publish)")

    # Set core URL
    p_core = subparsers.add_parser("set-core", help="Set Core URL")
    p_core.add_argument("url", help="Core URL")

    # Config management
    p_cfg = subparsers.add_parser("config", help="Manage configuration")
    cfg_sub = p_cfg.add_subparsers(dest="config_cmd")
    s_set = cfg_sub.add_parser("set", help="Set config value")
    s_set.add_argument("key")
    s_set.add_argument("value")
    s_get = cfg_sub.add_parser("get", help="Get config value")
    s_get.add_argument("key")
    cfg_sub.add_parser("list", help="List all config")

    # Heartbeat (new; safe without crypto/DB)
    subparsers.add_parser("heartbeat", help="Send a heartbeat (optional Core ping)")

    args = parser.parse_args()
    if not args.cmd:
        parser.print_help()
        return 0

    system = SentraUnified()

    try:
        if args.cmd == "init":
            return system.cmd_init(args)
        elif args.cmd == "info":
            return system.cmd_info(args)
        elif args.cmd == "register":
            return system.cmd_register(args)
        elif args.cmd == "list":
            return system.cmd_list(args)
        elif args.cmd == "verify":
            return system.cmd_verify(args)
        elif args.cmd == "package":
            return system.cmd_package(args)
        elif args.cmd == "sync-core":
            return system.cmd_sync_core(args)
        elif args.cmd == "update-core":
            return system.cmd_update_core(args)
        elif args.cmd == "push-core":
            return system.cmd_push_core(args)
        elif args.cmd == "push-update":
            return system.cmd_push_update(args)
        elif args.cmd == "set-core":
            return system.cmd_set_core(args)
        elif args.cmd == "config":
            return system.cmd_config(args)
        elif args.cmd == "heartbeat":
            return system.cmd_heartbeat(args)
        else:
            print(f"Unknown command: {args.cmd}")
            return 1
    except KeyboardInterrupt:
        print("\n\nInterrupted by user")
        return 130
    except Exception as e:
        print(f"\n[FATAL ERROR] {e}")
        import traceback
        traceback.print_exc()
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
