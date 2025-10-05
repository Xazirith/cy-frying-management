/* CY Frying — Console Telemetry (pretty logs + lightweight beacons)
   - Fancy, colorized console output
   - Global error + unhandled rejection capture
   - Performance & network timing (basic)
   - Sends sampled events to /api/telemetry.php via sendBeacon/fetch
   - Toggle with localStorage: cfm.telemetry = "on" | "off"
*/

(function () {
  const cfg = {
    endpoint: "/api/telemetry.php",
    sampleRate: 0.2,                 // 20% of events beacons
    flushIntervalMs: 5000,           // batch window
    maxBatch: 20,
    maxBytes: 60_000,                // keep payloads small
    enabled: (localStorage.getItem("cfm.telemetry") === "on") ||
             (window.AppConfig && AppConfig.debugEnabled === true)
  };

  // Style helpers
  const styles = {
    base: "padding:2px 6px;border-radius:6px;font-weight:600;",
    tag: "background:#111;color:#fff",
    info: "background:#2563eb;color:#fff",
    ok: "background:#16a34a;color:#fff",
    warn: "background:#f59e0b;color:#111",
    err: "background:#dc2626;color:#fff",
    dim: "color:#64748b"
  };

  function nowIso() { return new Date().toISOString(); }
  function uid(n=6){ return [...crypto.getRandomValues(new Uint8Array(n))].map(x=>x.toString(16).padStart(2,"0")).join(""); }

  // Queue for beacons
  const q = [];
  let flushTimer = null;

  function scheduleFlush() {
    if (flushTimer) return;
    flushTimer = setTimeout(() => { flushTimer = null; flush(); }, cfg.flushIntervalMs);
  }

  function shouldSend() {
    return Math.random() < cfg.sampleRate;
  }

  function enqueue(ev) {
    try {
      const json = JSON.stringify(ev);
      if (json.length > cfg.maxBytes) return; // skip massive payloads
      q.push(ev);
      if (q.length >= cfg.maxBatch) flush();
      else scheduleFlush();
    } catch {}
  }

  function flush() {
    if (!cfg.enabled || q.length === 0) return;
    const batch = q.splice(0, cfg.maxBatch);
    const payload = JSON.stringify({ t: nowIso(), batch });

    const blob = new Blob([payload], { type: "application/json" });
    // Prefer sendBeacon; fall back to fetch
    if (navigator.sendBeacon && navigator.sendBeacon(cfg.endpoint, blob)) return;

    fetch(cfg.endpoint, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: payload,
      keepalive: true,
      credentials: "same-origin"
    }).catch(()=>{});
  }

  // Pretty console core
  function fmtTag(kind) {
    switch (kind) {
      case "info": return styles.info;
      case "ok":   return styles.ok;
      case "warn": return styles.warn;
      case "err":  return styles.err;
      default:     return styles.tag;
    }
  }

  function line(kind, title, details) {
    const tag = `%cCY%c ${title}%c ${details ?? ""}`;
    const s1 = styles.base + " " + fmtTag(kind);
    const s2 = styles.base + " " + styles.tag;
    const s3 = styles.dim;
    return [tag, s1, s2, s3];
  }

  const meta = {
    reqId: (window.AppConfig && AppConfig.reqId) || uid(6),
    user: (window.AppConfig && AppConfig.currentUser && (AppConfig.currentUser.username || AppConfig.currentUser.id)) || "guest",
    isAdmin: !!(window.AppConfig && AppConfig.isAdmin),
    ua: navigator.userAgent,
    path: location.pathname + location.search
  };

  // Public API
  const Telemetry = {
    enable()  { cfg.enabled = true; localStorage.setItem("cfm.telemetry","on"); this.info("telemetry", "enabled"); },
    disable() { cfg.enabled = false; localStorage.setItem("cfm.telemetry","off"); this.warn("telemetry", "disabled"); },

    log(title, details) {
      console.log(...line("info", title, details));
      if (cfg.enabled && shouldSend()) enqueue({ t: nowIso(), kind: "log", title, details, meta });
    },
    ok(title, details) {
      console.log(...line("ok", title, details));
      if (cfg.enabled && shouldSend()) enqueue({ t: nowIso(), kind: "ok", title, details, meta });
    },
    info(title, details) {
      console.info(...line("info", title, details));
      if (cfg.enabled && shouldSend()) enqueue({ t: nowIso(), kind: "info", title, details, meta });
    },
    warn(title, details) {
      console.warn(...line("warn", title, details));
      if (cfg.enabled && shouldSend()) enqueue({ t: nowIso(), kind: "warn", title, details, meta });
    },
    error(title, details) {
      console.error(...line("err", title, details));
      if (cfg.enabled) enqueue({ t: nowIso(), kind: "error", title, details, meta });
    },
    event(name, data) {
      console.groupCollapsed(...line("info", "event", name));
      if (data) console.log(data);
      console.groupEnd();
      if (cfg.enabled && shouldSend()) enqueue({ t: nowIso(), kind: "event", name, data, meta });
    },
    metric(name, value, unit="") {
      console.log(...line("ok", "metric", `${name}: ${value}${unit}`));
      if (cfg.enabled && shouldSend()) enqueue({ t: nowIso(), kind: "metric", name, value, unit, meta });
    }
  };

  // Global error hooks
  window.addEventListener("error", (e) => {
    const d = { message: e.message, file: e.filename, line: e.lineno, col: e.colno, stack: e.error && e.error.stack };
    Telemetry.error("window.error", d);
  });
  window.addEventListener("unhandledrejection", (e) => {
    const r = e.reason || {};
    const d = { reason: (r && (r.message || r.toString && r.toString())) || String(r), stack: r && r.stack };
    Telemetry.error("unhandledrejection", d);
  });

  // Basic performance marks
  if ("performance" in window) {
    setTimeout(() => {
      try {
        const nav = performance.getEntriesByType("navigation")[0];
        if (nav) {
          Telemetry.metric("ttfb", Math.round(nav.responseStart), "ms");
          Telemetry.metric("domContentLoaded", Math.round(nav.domContentLoadedEventEnd), "ms");
          Telemetry.metric("loadEvent", Math.round(nav.loadEventEnd), "ms");
        }
      } catch {}
    }, 0);
  }

  // Expose
  window.Telemetry = Telemetry;

  // Nice boot line
  Telemetry.info("telemetry", `req:${meta.reqId} user:${meta.user} path:${meta.path}`);
})();
