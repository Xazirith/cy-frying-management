<?php
/**
 * Template Name: Employee Portal
 * Description: Internal Moore's staff portal (Sentra-auth gated).
 */
get_header();
$sentra_cfg = function_exists('sentra_config')
	? sentra_config()
	: (function_exists('moores_sentra_config') ? moores_sentra_config() : []);
$api_base = isset($sentra_cfg['media_base']) && $sentra_cfg['media_base'] ? $sentra_cfg['media_base'] : ($sentra_cfg['base'] ?? '');
$tenant_id = $sentra_cfg['tenant_id'] ?? '';
$signage_manager_url = $sentra_cfg['signage_manager_url'] ?? '';
$signage_player_base = $sentra_cfg['signage_player_base'] ?? '';
$signage_device_id = $sentra_cfg['signage_device_id'] ?? '';
$signage_device_token = $sentra_cfg['signage_device_token'] ?? '';
?>

<section class="section" id="employee-portal" data-employee-portal data-api-base="<?php echo esc_attr($api_base); ?>" data-tenant-id="<?php echo esc_attr($tenant_id); ?>" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-signage-manager-url="<?php echo esc_url($signage_manager_url); ?>" data-signage-player-base="<?php echo esc_attr($signage_player_base); ?>" data-signage-device-id="<?php echo esc_attr($signage_device_id); ?>" data-signage-device-token="<?php echo esc_attr($signage_device_token); ?>">
	<div class="container-wide">
		<div class="kicker">Internal</div>
		<h2>Moore's Employee Portal</h2>
		<p class="lead">Internal tools and tenant data for staff only.</p>

		<div class="panel edge pad" data-portal-gate>
			<p class="lead" style="margin:0;">Checking staff access…</p>
		</div>

		<div class="panel edge pad" data-portal-denied hidden style="margin-top: var(--sp-30);">
			<h3>Access restricted</h3>
			<p class="lead">This portal is for Moore's staff only. Please sign in with a staff account.</p>
			<div style="margin-top: var(--sp-20); display:flex; gap:12px; flex-wrap:wrap;">
				<button class="btn btn-primary" type="button" data-auth-open>Sign In</button>
				<a class="btn btn-outline" href="<?php echo esc_url(home_url('/')); ?>">Back to site</a>
			</div>
		</div>

		<div data-portal-content hidden style="margin-top: var(--sp-30);">
			<div class="panel edge pad">
				<div class="kicker">Moore's Staff</div>
				<h2>Employee Control Center</h2>
				<p class="lead">Everything here is scoped to the Moore's tenant. Use this to monitor, update, and publish.</p>
				<div class="strip" style="margin-top: var(--sp-20);">
					<div class="tile">
						<div class="label">Status</div>
						<div class="value">Live</div>
					</div>
					<div class="tile">
						<div class="label">Tenant</div>
						<div class="value">Moore's</div>
					</div>
					<div class="tile">
						<div class="label">Access</div>
						<div class="value">Staff</div>
					</div>
				</div>
			</div>

			<div class="panel edge pad" style="margin-top: var(--sp-40);">
				<div class="kicker">Live Data</div>
				<h3>Tenant Feeds</h3>
				<p class="lead">Real-time snapshots pulled from Sentra tenant services.</p>
				<div class="strip" style="margin-top: var(--sp-20);">
					<div class="tile">
						<div class="label">Active Jobs</div>
						<div class="value" data-employee-metric="jobs">—</div>
					</div>
					<div class="tile">
						<div class="label">Gallery Items</div>
						<div class="value" data-employee-metric="gallery">—</div>
					</div>
					<div class="tile">
						<div class="label">Partner Shops</div>
						<div class="value" data-employee-metric="partners">—</div>
					</div>
					<div class="tile">
						<div class="label">Services</div>
						<div class="value" data-employee-metric="services">—</div>
					</div>
					<div class="tile">
						<div class="label">Staff</div>
						<div class="value" data-employee-metric="staff">—</div>
					</div>
				</div>
				<div class="badge" style="margin-top: var(--sp-20);" data-employee-status>Awaiting live data…</div>
			</div>

			<div class="cards" style="margin-top: var(--sp-40);">
				<div class="card">
					<div class="kicker">Jobs</div>
					<h3>Job Management</h3>
					<p>Track builds, update stages, attach images, and push status updates.</p>
					<div class="mc-auth-actions">
						<a class="btn btn-primary" href="#" data-employee-action="jobs">Open Jobs</a>
						<a class="btn btn-outline" href="#" data-employee-action="new-job">Create Job</a>
					</div>
				</div>
				<div class="card">
					<div class="kicker">Gallery</div>
					<h3>Gallery Control</h3>
					<p>Upload new work, tag featured builds, and curate the homepage.</p>
					<div class="mc-auth-actions">
						<a class="btn btn-primary" href="#" data-employee-action="gallery">Manage Gallery</a>
						<a class="btn btn-outline" href="#" data-employee-action="upload">Upload Media</a>
					</div>
				</div>
				<div class="card">
					<div class="kicker">Partners</div>
					<h3>Trusted Network</h3>
					<p>Add or edit partner shops, contact info, and specialties.</p>
					<div class="mc-auth-actions">
						<a class="btn btn-primary" href="#" data-employee-action="partners">Edit Partners</a>
						<a class="btn btn-outline" href="#" data-employee-action="invite">Invite Partner</a>
					</div>
				</div>
				<div class="card">
					<div class="kicker">Services</div>
					<h3>Service Catalog</h3>
					<p>Keep pricing, descriptions, and featured services up to date.</p>
					<div class="mc-auth-actions">
						<a class="btn btn-primary" href="#" data-employee-action="services">Edit Services</a>
						<a class="btn btn-outline" href="#" data-employee-action="pricing">Update Pricing</a>
					</div>
				</div>
				<div class="card">
					<div class="kicker">Staff</div>
					<h3>Employee Badges</h3>
					<p>Manage staff access, roles, and permissions for Moore's.</p>
					<div class="mc-auth-actions">
						<a class="btn btn-primary" href="#" data-employee-action="staff">Manage Staff</a>
						<a class="btn btn-outline" href="#" data-employee-action="staff-new">Add Staff</a>
					</div>
				</div>
			</div>

			<div class="panel edge pad" style="margin-top: var(--sp-40);" data-employee-feed hidden>
				<div class="kicker">Live View</div>
				<h3 data-employee-feed-title>Loading…</h3>
				<div data-employee-feed-body style="margin-top: var(--sp-20);"></div>
			</div>

			<div class="mc-manager-modal" data-employee-modal hidden>
				<div class="mc-manager-backdrop" data-employee-modal-close></div>
				<div class="mc-manager-panel panel edge pad" data-employee-manager>
					<div class="mc-manager-header">
						<div>
							<div class="kicker">Moore's Staff</div>
							<h3 data-employee-manager-title>Employee Control Center</h3>
							<p class="lead" data-employee-manager-subtitle>Manage jobs, gallery, partners, and services in one place.</p>
						</div>
						<button class="mc-manager-close" type="button" aria-label="Close control panel" data-employee-modal-close>×</button>
					</div>
					<div class="mc-manager-tabs" role="tablist">
						<button type="button" class="mc-manager-tab" data-manager-tab="jobs">Jobs</button>
						<button type="button" class="mc-manager-tab" data-manager-tab="gallery">Gallery</button>
						<button type="button" class="mc-manager-tab" data-manager-tab="partners">Partners</button>
						<button type="button" class="mc-manager-tab" data-manager-tab="services">Services</button>
						<button type="button" class="mc-manager-tab" data-manager-tab="staff">Staff</button>
						<button type="button" class="mc-manager-tab" data-manager-tab="signage">Signage</button>
					</div>
					<div class="mc-manager-actions" data-employee-manager-actions></div>
					<div class="mc-manager-content">
						<div class="mc-manager-pane mc-manager-pane-list" data-employee-manager-list></div>
					</div>
					<div class="mc-manager-form-modal" data-employee-form-modal hidden>
						<div class="mc-manager-form-backdrop" data-employee-form-close></div>
						<div class="mc-manager-form-panel panel edge pad">
							<div class="mc-manager-form-header">
								<div>
									<div class="kicker">Add</div>
									<h3 data-employee-form-title>Add Item</h3>
									<p class="lead" data-employee-form-subtitle>Fill out the fields below.</p>
								</div>
								<button class="mc-manager-close" type="button" aria-label="Close add form" data-employee-form-close>×</button>
							</div>
							<div data-employee-manager-form></div>
						</div>
					</div>
				</div>
			</div>
			<div class="mc-job-modal" data-job-modal hidden>
				<div class="mc-job-modal-backdrop" data-job-modal-close></div>
				<div class="mc-job-modal-panel panel edge pad" data-job-modal-panel>
					<div class="mc-job-modal-header">
						<div>
							<div class="kicker">Moore's Jobs</div>
							<h3>Job Detail</h3>
							<p class="lead">Full job context, messaging, and media in one focused view.</p>
						</div>
						<button class="mc-manager-close" type="button" aria-label="Close job panel" data-job-modal-close>×</button>
					</div>
					<div data-job-modal-body></div>
				</div>
			</div>

			<div class="panel edge pad" style="margin-top: var(--sp-40);" data-signage-panel>
				<div class="kicker">Signage</div>
				<h3>Showroom Display</h3>
				<p class="lead">Control the showroom playlist and display state.</p>
				<p class="meta" data-signage-locked hidden>Signage controls are currently restricted to Sentra staff.</p>
				<div class="strip" style="grid-template-columns: repeat(3, 1fr); margin-top: var(--sp-20);">
					<div class="tile">
						<div class="label">Display</div>
						<div class="value" data-signage-display>—</div>
						<div class="meta" data-signage-status style="margin-top:4px; opacity:0.7;">—</div>
					</div>
					<div class="tile">
						<div class="label">Playlist</div>
						<div class="value" data-signage-playlist>—</div>
						<div class="meta" data-signage-items style="margin-top:4px; opacity:0.7;">—</div>
					</div>
					<div class="tile">
						<div class="label">Items</div>
						<div class="value" data-signage-count>—</div>
					</div>
				</div>
				<div class="mc-auth-actions" style="margin-top: var(--sp-20);">
					<button class="btn btn-primary" type="button" data-employee-action="signage">Manage Signage</button>
					<a class="btn btn-outline" href="#" target="_blank" rel="noopener" data-signage-manager-link>Open Signage Control</a>
					<a class="btn btn-outline" href="#" target="_blank" rel="noopener" data-signage-view-link>View Display</a>
				</div>
			</div>

			<div class="panel edge pad" style="margin-top: var(--sp-40);" data-staff-comms>
				<div class="kicker">Staff Comms</div>
				<h3>Staff Messaging & Activity</h3>
				<p class="lead">Persistent staff-only threads for team updates and activity notes.</p>
				<div class="strip mc-staff-status" style="margin-top: var(--sp-20);">
					<div class="tile">
						<div class="label">Signed in as</div>
						<div class="value" data-staff-active-name>—</div>
					</div>
					<div class="tile">
						<div class="label">Status</div>
						<div class="value" data-staff-active-status>Active</div>
					</div>
				</div>
				<div class="mc-staff-comm-grid" style="margin-top: var(--sp-20);">
					<div class="mc-job-chat" data-staff-thread-panel="messages">
						<div class="mc-job-chat-head">
							<div>
								<div class="mc-job-chat-meta">Team messages</div>
								<div class="mc-job-chat-meta">Thread: staff_messages</div>
							</div>
							<button class="btn btn-outline" type="button" data-staff-refresh="messages">Refresh</button>
						</div>
						<div class="mc-job-chat-thread" data-staff-thread="messages">
							<p class="lead">Loading messages…</p>
						</div>
						<form class="mc-job-chat-compose" data-staff-compose="messages">
							<textarea name="message" rows="3" placeholder="Share a team update…"></textarea>
							<button class="btn btn-primary" type="submit">Send message</button>
						</form>
					</div>
					<div class="mc-job-chat" data-staff-thread-panel="activity">
						<div class="mc-job-chat-head">
							<div>
								<div class="mc-job-chat-meta">Staff activity log</div>
								<div class="mc-job-chat-meta">Thread: staff_activity</div>
							</div>
							<button class="btn btn-outline" type="button" data-staff-refresh="activity">Refresh</button>
						</div>
						<div class="mc-job-chat-thread" data-staff-thread="activity">
							<p class="lead">Loading activity…</p>
						</div>
						<form class="mc-job-chat-compose" data-staff-compose="activity">
							<textarea name="message" rows="2" placeholder="Log an internal activity note…"></textarea>
							<button class="btn btn-primary" type="submit">Add activity</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<script>
(() => {
	const portal = document.querySelector('[data-employee-portal]');
	const gate = document.querySelector('[data-portal-gate]');
	const denied = document.querySelector('[data-portal-denied]');
	const content = document.querySelector('[data-portal-content]');
	if (!gate || !denied || !content || !portal) return;

	const debugEnabled = new URLSearchParams(window.location.search).has('debug');
	const debugPanel = (() => {
		if (!debugEnabled) return null;
		const panel = document.createElement('div');
		panel.style.position = 'fixed';
		panel.style.bottom = '16px';
		panel.style.left = '16px';
		panel.style.zIndex = '9999';
		panel.style.maxWidth = '420px';
		panel.style.maxHeight = '50vh';
		panel.style.overflow = 'auto';
		panel.style.background = 'rgba(10,12,18,0.92)';
		panel.style.border = '1px solid rgba(255,255,255,0.1)';
		panel.style.borderRadius = '12px';
		panel.style.padding = '12px';
		panel.style.fontSize = '12px';
		panel.style.color = '#e9e9ff';
		panel.innerHTML = '<strong style="display:block;margin-bottom:6px;">Employee Portal Debug</strong><pre style="white-space:pre-wrap;margin:0;"></pre>';
		document.body.appendChild(panel);
		return panel.querySelector('pre');
	})();

	const debugLog = (label, data) => {
		if (!debugPanel) return;
		const stamp = new Date().toLocaleTimeString();
		let payload = data;
		try {
			if (typeof data !== 'string') payload = JSON.stringify(data, null, 2);
		} catch (e) {}
		debugPanel.textContent += `\n[${stamp}] ${label}\n${payload || ''}\n`;
	};

	debugLog('boot', {
		ajax: portal.dataset.ajaxUrl,
		tenant: portal.dataset.tenantId,
		apiBase: portal.dataset.apiBase
	});

	const getPacket = () => {
		try {
			const raw = localStorage.getItem('sentra_auth_packet');
			if (!raw) return null;
			return JSON.parse(raw);
		} catch (e) {
			return null;
		}
	};

	const showDenied = () => {
		gate.hidden = true;
		denied.hidden = false;
		content.hidden = true;
		debugLog('state', 'denied');
	};

	const showContent = () => {
		gate.hidden = true;
		denied.hidden = true;
		content.hidden = false;
		debugLog('state', 'content');
	};

	const packet = getPacket();
	if (!packet || !packet.access_token) {
		debugLog('auth_packet', packet || 'missing');
		showDenied();
		return;
	}

	const inferStaffFromPacket = (authPacket) => {
		if (!authPacket) return false;
		const roles = [];
		if (authPacket.role) roles.push(authPacket.role);
		if (Array.isArray(authPacket.roles)) roles.push(...authPacket.roles);
		if (Array.isArray(authPacket.global_roles)) roles.push(...authPacket.global_roles);
		if (authPacket.site_roles && typeof authPacket.site_roles === 'object') {
			Object.values(authPacket.site_roles).forEach((list) => {
				if (Array.isArray(list)) roles.push(...list);
			});
		}
		const normalized = roles.map((role) => String(role || '').toLowerCase().trim()).filter(Boolean);
		const staffHints = ['staff', 'admin', 'owner', 'manager', 'employee', 'sentra'];
		if (normalized.some((role) => staffHints.includes(role))) return true;
		const email = String(authPacket.email || authPacket.user?.email || '').toLowerCase();
		if (email.includes('@') && email.split('@')[1].includes('sentra')) return true;
		const username = String(authPacket.username || authPacket.user?.username || '').toLowerCase();
		if (username.includes('sentra')) return true;
		return false;
	};

	const payload = new URLSearchParams();
	payload.set('action', 'moores_sentra_verify');
	payload.set('nonce', '<?php echo esc_js(wp_create_nonce('moores_auth')); ?>');
	payload.set('access_token', packet.access_token);

	fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
		body: payload.toString()
	})
		.then(res => res.json())
		.then(data => {
			debugLog('verify_response', data);
			const authPacket = data?.data?.auth_packet;
			const staff = authPacket?.is_staff || inferStaffFromPacket(authPacket);
			if (data?.success && authPacket && staff) {
				authPacket.is_staff = true;
				localStorage.setItem('sentra_auth_packet', JSON.stringify(authPacket));
				showContent();
				wireEmployeePanel(authPacket);
			} else {
				showDenied();
			}
		})
		.catch((err) => {
			debugLog('verify_error', err?.message || err);
			showDenied();
		});

	const wireEmployeePanel = (authPacket) => {
		const apiBase = (portal.dataset.apiBase || '').replace(/\/+$/, '');
		const tenantId = (portal.dataset.tenantId || '').toString();
		const statusBadge = portal.querySelector('[data-employee-status]');
		const feedPanel = portal.querySelector('[data-employee-feed]');
		const feedTitle = portal.querySelector('[data-employee-feed-title]');
		const feedBody = portal.querySelector('[data-employee-feed-body]');
		const managerModal = portal.querySelector('[data-employee-modal]');
		const managerPanel = portal.querySelector('[data-employee-manager]');
		const managerTitle = portal.querySelector('[data-employee-manager-title]');
		const managerSubtitle = portal.querySelector('[data-employee-manager-subtitle]');
		const managerActions = portal.querySelector('[data-employee-manager-actions]');
		const managerForm = portal.querySelector('[data-employee-manager-form]');
		const managerList = portal.querySelector('[data-employee-manager-list]');
		const formModal = portal.querySelector('[data-employee-form-modal]');
		const formTitle = portal.querySelector('[data-employee-form-title]');
		const formSubtitle = portal.querySelector('[data-employee-form-subtitle]');
		const formClosers = portal.querySelectorAll('[data-employee-form-close]');
		const modalClosers = portal.querySelectorAll('[data-employee-modal-close]');
		const managerTabs = portal.querySelectorAll('[data-manager-tab]');
		const jobModal = portal.querySelector('[data-job-modal]');
		const jobModalPanel = portal.querySelector('[data-job-modal-panel]');
		const jobModalBody = portal.querySelector('[data-job-modal-body]');
		const jobModalClosers = portal.querySelectorAll('[data-job-modal-close]');
		const signageManagerUrl = portal.dataset.signageManagerUrl || '';
		const signagePlayerBase = portal.dataset.signagePlayerBase || '';
		const signageDeviceId = portal.dataset.signageDeviceId || '';
		const signageDeviceToken = portal.dataset.signageDeviceToken || '';
		const signageDisplayValue = portal.querySelector('[data-signage-display]');
		const signageStatusValue = portal.querySelector('[data-signage-status]');
		const signagePlaylistValue = portal.querySelector('[data-signage-playlist]');
		const signageItemsValue = portal.querySelector('[data-signage-items]');
		const signageCountValue = portal.querySelector('[data-signage-count]');
		const signageManagerLink = portal.querySelector('[data-signage-manager-link]');
		const signageViewLink = portal.querySelector('[data-signage-view-link]');
		const signagePanel = portal.querySelector('[data-signage-panel]');
		const signageLockedNote = portal.querySelector('[data-signage-locked]');
		const signageActionButtons = portal.querySelectorAll('[data-employee-action="signage"]');
		const signageTabs = portal.querySelectorAll('[data-manager-tab="signage"]');

		if (!apiBase || !tenantId) {
			if (statusBadge) statusBadge.textContent = 'Missing tenant API configuration.';
			return;
		}

		const ajaxUrl = portal.dataset.ajaxUrl || window.ajaxurl || '';
		const endpointMap = {
			jobs: 'jobs',
			gallery: 'gallery',
			partners: 'partners',
			services: 'services',
			staff: 'staff',
			messages: 'messages',
		};

		const escapeHtml = (value) => {
			return String(value ?? '')
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/\"/g, '&quot;')
				.replace(/'/g, '&#39;');
		};

		const resolveMediaUrl = (url) => {
			if (!url) return '';
			const raw = String(url);
			if (raw.startsWith('http://') || raw.startsWith('https://')) return raw;
			return `${apiBase}/${raw.replace(/^\/+/, '')}`;
		};

		const buildPlayerUrl = () => {
			if (!signagePlayerBase) return '';
			const base = signagePlayerBase.replace(/\/+$/, '');
			const url = `${base}/player.html`;
			if (signageDeviceId) {
				return `${url}?device_id=${encodeURIComponent(signageDeviceId)}`;
			}
			return url;
		};

		if (signageManagerLink) {
			signageManagerLink.href = signageManagerUrl || '#';
		}
		if (signageViewLink) {
			const playerUrl = buildPlayerUrl();
			signageViewLink.href = playerUrl || '#';
		}

		const toDateInput = (value) => {
			if (!value) return '';
			const raw = String(value);
			if (/^\d{4}-\d{2}-\d{2}/.test(raw)) return raw.slice(0, 10);
			const parsed = new Date(raw);
			if (!isNaN(parsed.getTime())) return parsed.toISOString().slice(0, 10);
			return '';
		};

		const parseTags = (tags) => {
			if (!tags) return [];
			if (Array.isArray(tags)) return tags;
			if (typeof tags === 'string') {
				try {
					const parsed = JSON.parse(tags);
					if (Array.isArray(parsed)) return parsed;
				} catch (e) {}
				return tags.split(',').map((tag) => tag.trim()).filter(Boolean);
			}
			return [];
		};

		const collectPrefixed = (tags, prefix) => {
			return parseTags(tags)
				.filter((tag) => typeof tag === 'string' && tag.startsWith(prefix))
				.map((tag) => tag.replace(prefix, '').trim())
				.filter(Boolean);
		};

		const stripPrefixed = (tags, prefixes) => {
			const list = parseTags(tags);
			if (!Array.isArray(prefixes)) return list;
			return list.filter((tag) => !prefixes.some((prefix) => tag.startsWith(prefix)));
		};

		const normalizeCommaList = (value) => {
			if (!value) return [];
			if (Array.isArray(value)) {
				return value.map((entry) => String(entry).trim()).filter(Boolean);
			}
			return String(value)
				.split(',')
				.map((entry) => entry.trim())
				.filter(Boolean);
		};

		const isMooresEmail = (email) => {
			if (!email) return false;
			const parts = String(email).toLowerCase().split('@');
			if (parts.length < 2) return false;
			return parts[1].includes('moores');
		};

		const isSentraUser = (packet) => {
			if (!packet) return false;
			const email = String(packet.email || packet.user?.email || '').toLowerCase();
			if (email && email.includes('@') && email.split('@')[1].includes('sentra')) return true;
			const username = String(packet.username || packet.user?.username || '').toLowerCase();
			return username.includes('sentra');
		};

		const signageLocked = !isSentraUser(authPacket);

		const applySignageLock = () => {
			if (!signageLocked) {
				if (signagePanel) signagePanel.classList.remove('mc-signage-disabled');
				if (signageLockedNote) signageLockedNote.hidden = true;
				signageTabs.forEach((tab) => {
					tab.classList.remove('is-disabled');
					tab.removeAttribute('aria-disabled');
					tab.removeAttribute('tabindex');
				});
				signageActionButtons.forEach((btn) => {
					btn.disabled = false;
					btn.classList.remove('is-disabled');
				});
				[signageManagerLink, signageViewLink].forEach((link) => {
					if (!link) return;
					link.classList.remove('is-disabled');
					link.removeAttribute('aria-disabled');
					link.removeAttribute('tabindex');
				});
				return;
			}

			if (signagePanel) signagePanel.classList.add('mc-signage-disabled');
			if (signageLockedNote) signageLockedNote.hidden = false;
			signageTabs.forEach((tab) => {
				tab.classList.add('is-disabled');
				tab.setAttribute('aria-disabled', 'true');
				tab.setAttribute('tabindex', '-1');
			});
			signageActionButtons.forEach((btn) => {
				btn.disabled = true;
				btn.classList.add('is-disabled');
			});
			[signageManagerLink, signageViewLink].forEach((link) => {
				if (!link) return;
				link.classList.add('is-disabled');
				link.setAttribute('aria-disabled', 'true');
				link.setAttribute('tabindex', '-1');
				link.href = '#';
			});
			if (signageDisplayValue) signageDisplayValue.textContent = 'Restricted';
			if (signageStatusValue) signageStatusValue.textContent = 'Sentra staff only';
			if (signagePlaylistValue) signagePlaylistValue.textContent = '—';
			if (signageItemsValue) signageItemsValue.textContent = '—';
			if (signageCountValue) signageCountValue.textContent = '—';
		};

		applySignageLock();

		const autoNameFromEmail = (email) => {
			if (!email) return '';
			const local = String(email).split('@')[0] || '';
			const parts = local.split(/[._-]+/).filter(Boolean);
			if (!parts.length) return '';
			return parts.map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join(' ');
		};

		const parseMetadata = (value) => {
			if (!value) return {};
			if (typeof value === 'object') return value;
			if (typeof value === 'string') {
				try {
					const parsed = JSON.parse(value);
					if (parsed && typeof parsed === 'object') return parsed;
				} catch (e) {}
			}
			return {};
		};

		const setMetaField = (meta, key, value) => {
			if (!meta || typeof meta !== 'object') return;
			if (value === null || value === undefined || value === '') {
				delete meta[key];
			} else {
				meta[key] = value;
			}
		};

		const normalizeFinalProcess = (process, isFinal) => {
			const raw = String(process || '').trim();
			if (raw) return raw;
			return isFinal ? 'final' : '';
		};

		const isFinalProcess = (process, meta) => {
			if (meta && typeof meta === 'object') {
				if (meta.final || meta.is_final || meta.isFinal) return true;
			}
			const raw = String(process || '').toLowerCase();
			if (!raw) return false;
			return ['final', 'finish', 'finished', 'complete', 'completed', 'delivery', 'delivered', 'done'].some((token) => raw.includes(token));
		};

		const extractGalleryJobId = (item) => {
			if (!item) return '';
			if (item.job_id) return String(item.job_id);
			const meta = parseMetadata(item.metadata);
			const raw = meta.job_id || meta.jobId || meta.job || meta.jobID || '';
			if (raw) return String(raw);
			const tags = extractGalleryTags(item);
			for (const tag of tags) {
				const rawTag = String(tag || '');
				if (!rawTag) continue;
				if (rawTag.toLowerCase().startsWith('job:')) {
					return rawTag.slice(4).trim();
				}
			}
			return '';
		};

		const extractGalleryTags = (item) => {
			const tags = new Set();
			if (item?.tag) {
				parseTags(item.tag).forEach((tag) => {
					if (tag) tags.add(String(tag));
				});
			}
			const meta = parseMetadata(item?.metadata);
			if (Array.isArray(meta.tags)) {
				meta.tags.forEach((tag) => {
					if (tag) tags.add(String(tag));
				});
			}
			return Array.from(tags);
		};

		const extractGalleryTagValue = (item, prefixes) => {
			if (!Array.isArray(prefixes)) return '';
			const tags = extractGalleryTags(item);
			for (const prefix of prefixes) {
				const needle = String(prefix).toLowerCase();
				for (const tag of tags) {
					const rawTag = String(tag || '');
					if (!rawTag) continue;
					if (rawTag.toLowerCase().startsWith(needle)) {
						return rawTag.slice(needle.length).trim();
					}
				}
			}
			return '';
		};

		const extractGalleryPart = (item) => {
			if (!item) return '';
			const meta = parseMetadata(item.metadata);
			const raw = meta.part || meta.part_name || meta.partName || meta.panel || meta.component || meta.parts;
			if (raw) return String(raw);
			return extractGalleryTagValue(item, ['part:', 'parts:', 'panel:', 'component:']);
		};

		const extractGalleryProcess = (item) => {
			if (!item) return '';
			const meta = parseMetadata(item.metadata);
			const raw = meta.process || meta.stage || meta.step || meta.workflow || meta.status;
			if (raw) return String(raw);
			return extractGalleryTagValue(item, ['process:', 'stage:', 'step:', 'workflow:']);
		};

		const parseSortToken = (value) => {
			if (value === null || value === undefined) return { num: null, text: '' };
			const raw = String(value).trim();
			if (!raw) return { num: null, text: '' };
			const match = raw.match(/^(\d+)[\s._-]*(.*)$/);
			if (match) {
				const num = Number.parseInt(match[1], 10);
				const text = (match[2] || raw).trim();
				return { num: Number.isFinite(num) ? num : null, text: text || raw };
			}
			return { num: null, text: raw };
		};

		const compareSortValue = (a, b) => {
			const aVal = String(a || '').trim();
			const bVal = String(b || '').trim();
			if (!aVal && !bVal) return 0;
			if (!aVal) return 1;
			if (!bVal) return -1;
			const aToken = parseSortToken(aVal);
			const bToken = parseSortToken(bVal);
			if (aToken.num !== null && bToken.num !== null && aToken.num !== bToken.num) {
				return aToken.num - bToken.num;
			}
			if (aToken.num !== null && bToken.num === null) return -1;
			if (aToken.num === null && bToken.num !== null) return 1;
			return aToken.text.localeCompare(bToken.text, undefined, { numeric: true, sensitivity: 'base' });
		};

		const sortGalleryItems = (items) => {
			if (!Array.isArray(items)) return [];
			return items.slice().sort((a, b) => {
				const jobA = extractGalleryJobId(a);
				const jobB = extractGalleryJobId(b);
				const jobSort = compareSortValue(jobA, jobB);
				if (jobSort !== 0) return jobSort;
				const partA = extractGalleryPart(a);
				const partB = extractGalleryPart(b);
				const partSort = compareSortValue(partA, partB);
				if (partSort !== 0) return partSort;
				const processA = extractGalleryProcess(a);
				const processB = extractGalleryProcess(b);
				const processSort = compareSortValue(processA, processB);
				if (processSort !== 0) return processSort;
				const timeA = a?.created_at || a?.updated_at || '';
				const timeB = b?.created_at || b?.updated_at || '';
				const timeSort = compareSortValue(timeA, timeB);
				if (timeSort !== 0) return timeSort;
				return compareSortValue(a?.id || '', b?.id || '');
			});
		};

		const deriveFolderTag = (tags) => {
			const list = parseTags(tags);
			const folderTag = list.find((tag) => typeof tag === 'string' && tag.startsWith('folder:'));
			return folderTag ? folderTag.replace('folder:', '') : '';
		};

		const buildTagList = (existingTags, folderValue) => {
			const list = parseTags(existingTags).filter((tag) => typeof tag === 'string' && !tag.startsWith('folder:'));
			if (folderValue) list.push(`folder:${folderValue}`);
			return list;
		};

		const buildGalleryMetadata = (currentMeta, { job_id, part, process, is_final, job_featured } = {}) => {
			const meta = parseMetadata(currentMeta);
			const normalizedProcess = normalizeFinalProcess(process, is_final);
			setMetaField(meta, 'job_id', job_id ? String(job_id).trim() : '');
			setMetaField(meta, 'part', part ? String(part).trim() : '');
			setMetaField(meta, 'process', normalizedProcess ? String(normalizedProcess).trim() : '');
			if (is_final !== undefined) {
				setMetaField(meta, 'final', !!is_final);
			}
			if (job_featured !== undefined) {
				setMetaField(meta, 'job_featured', !!job_featured);
			}
			return meta;
		};

		const getStartDateFromTags = (tags, fallback) => {
			const start = collectPrefixed(tags, 'start:')[0] || '';
			if (start) return start;
			return fallback ? toDateInput(fallback) : '';
		};

		const extractJobClientName = (tags) => collectPrefixed(tags, 'client_name:')[0] || '';
		const extractJobClientEmail = (tags) => collectPrefixed(tags, 'client_email:')[0] || '';
		const normalizeTagValue = (value) => (value === undefined || value === null ? '' : String(value).trim());

		const composeJobTags = (existingTags, { folder, services, sales, lead, techs, start, clientName, clientEmail } = {}) => {
			const base = stripPrefixed(existingTags, ['folder:', 'service:', 'sales:', 'lead:', 'tech:', 'start:', 'client_name:', 'client_email:']);
			const next = [...base];
			if (folder) next.push(`folder:${folder}`);
			normalizeCommaList(services).forEach((service) => next.push(`service:${service}`));
			if (sales) next.push(`sales:${sales}`);
			if (lead) next.push(`lead:${lead}`);
			normalizeCommaList(techs).forEach((tech) => next.push(`tech:${tech}`));
			if (start) next.push(`start:${start}`);
			const clientNameValue = normalizeTagValue(clientName);
			const clientEmailValue = normalizeTagValue(clientEmail);
			if (clientNameValue) next.push(`client_name:${clientNameValue}`);
			if (clientEmailValue) next.push(`client_email:${clientEmailValue}`);
			return next;
		};

		const fileToBase64 = (file) => new Promise((resolve, reject) => {
			const reader = new FileReader();
			reader.onload = () => {
				const result = String(reader.result || '');
				const base64 = result.includes(',') ? result.split(',')[1] : result;
				resolve(base64);
			};
			reader.onerror = () => reject(reader.error || new Error('File read failed'));
			reader.readAsDataURL(file);
		});

		const threadIdForJob = (jobId) => `job_${jobId}`;

		const normalizeCount = (data) => {
			if (!data || typeof data !== 'object') return 0;
			if (typeof data.total === 'number') return data.total;
			if (Array.isArray(data.items)) return data.items.length;
			if (Array.isArray(data.results)) return data.results.length;
			if (Array.isArray(data.data)) return data.data.length;
			return 0;
		};

		const proxyRequest = async (resource, method, payload = null, itemId = null, query = null, options = null) => {
			if (!ajaxUrl) throw new Error('Proxy unavailable.');
			const body = new FormData();
			body.set('action', 'moores_sentra_proxy');
			body.set('resource', resource);
			body.set('method', method);
			if (itemId !== null && itemId !== undefined && itemId !== '') {
				body.set('item', String(itemId));
			}
			if (query && typeof query === 'object') {
				body.set('query', JSON.stringify(query));
			}
			if (options && options.nocache) {
				body.set('nocache', '1');
			}
			if (options && options.cache === false) {
				body.set('cache', '0');
			}
			if (authPacket?.access_token) {
				body.set('token', authPacket.access_token);
			}
			if (payload) {
				body.set('payload', JSON.stringify(payload));
			}
			const res = await fetch(ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body
			});
			const data = await res.json();
			if (!res.ok) {
				const message = data?.data?.message || data?.message || 'Request failed';
				debugLog('proxy_error', { resource, status: res.status, message });
				throw new Error(message);
			}
			if (data && data.success === false) {
				const message = data?.data?.message || data?.message || 'Request failed';
				debugLog('proxy_error', { resource, message });
				throw new Error(message);
			}
			return data?.data ?? data;
		};

		const signageRequest = async (path, method = 'GET', payload = null, query = null) => {
			if (!ajaxUrl) throw new Error('Proxy unavailable.');
			const body = new FormData();
			body.set('action', 'moores_sentra_signage_proxy');
			body.set('path', path);
			body.set('method', method);
			if (query && typeof query === 'object') {
				body.set('query', JSON.stringify(query));
			}
			const token = signageDeviceToken || authPacket?.access_token;
			if (token) {
				body.set('token', token);
			}
			if (payload) {
				body.set('payload', JSON.stringify(payload));
			}
			const res = await fetch(ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body
			});
			const data = await res.json();
			if (!res.ok) {
				const message = data?.data?.message || data?.message || 'Request failed';
				debugLog('signage_error', { path, status: res.status, message });
				throw new Error(message);
			}
			if (data && data.success === false) {
				const message = data?.data?.message || data?.message || 'Request failed';
				debugLog('signage_error', { path, message });
				throw new Error(message);
			}
			return data?.data ?? data;
		};

		const normalizeSignageList = (data, key) => {
			if (!data || typeof data !== 'object') return [];
			if (Array.isArray(data[key])) return data[key];
			if (Array.isArray(data.items)) return data.items;
			if (Array.isArray(data.results)) return data.results;
			return [];
		};

		const parseContentData = (item) => {
			if (!item) return {};
			if (item.content_data && typeof item.content_data === 'object') return item.content_data;
			if (typeof item.content_data === 'string') {
				try {
					const parsed = JSON.parse(item.content_data);
					if (parsed && typeof parsed === 'object') return parsed;
				} catch (e) {}
			}
			return {};
		};

		const resolveClientRecord = async (name, email) => {
			const cleanedName = (name || '').trim();
			const cleanedEmail = (email || '').trim();
			if (!cleanedEmail) return null;
			const searchTerm = cleanedEmail;
			let matches = [];
			try {
				const data = await proxyRequest('clients', 'GET', null, null, { search: searchTerm, per_page: 50 });
				matches = Array.isArray(data?.items) ? data.items : [];
			} catch (e) {
				matches = [];
			}
			const emailMatch = cleanedEmail
				? matches.find((item) => (item.email || '').toLowerCase() === cleanedEmail.toLowerCase())
				: null;
			if (emailMatch?.id) {
				return {
					id: emailMatch.id,
					name: emailMatch.name || cleanedName,
					email: emailMatch.email || cleanedEmail,
					matched: 'email',
				};
			}
			return null;
		};

		const fetchEndpoint = async (key, options = null) => {
			const resource = endpointMap[key];
			if (!resource) return null;
			const query = options && options.query ? options.query : null;
			const requestOptions = options
				? { nocache: options.nocache, cache: options.cache }
				: null;
			return proxyRequest(resource, 'GET', null, null, query, requestOptions);
		};

		const fetchStaffList = async (options = null) => {
			const baseQuery = { per_page: 200, active: 'false' };
			const mergedQuery = options && options.query
				? { ...baseQuery, ...options.query }
				: baseQuery;
			return fetchEndpoint('staff', { ...(options || {}), query: mergedQuery });
		};

		const requestPasswordReset = async (email) => {
			const cleaned = (email || '').trim();
			if (!cleaned) throw new Error('Email is required.');
			if (!ajaxUrl) throw new Error('Proxy unavailable.');
			const body = new FormData();
			body.set('action', 'moores_sentra_password_reset');
			body.set('email', cleaned);
			const res = await fetch(ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body
			});
			const data = await res.json();
			if (!res.ok || data?.success === false) {
				const message = data?.data?.message || data?.message || 'Reset failed';
				throw new Error(message);
			}
			return data?.data ?? data;
		};

		let staffCache = null;
		let servicesCache = null;
		let foldersCache = null;
		let jobsCache = null;
		const getStaffCache = async () => {
			if (staffCache) return staffCache;
			try {
				const data = await fetchStaffList();
				staffCache = Array.isArray(data?.items) ? data.items : [];
			} catch (e) {
				staffCache = [];
			}
			return staffCache;
		};

		const lookupStaffName = async (email) => {
			if (!email) return '';
			const list = await getStaffCache();
			const match = list.find((member) => (member.email || '').toLowerCase() === String(email).toLowerCase());
			return match?.name || '';
		};

		const getServicesCache = async () => {
			if (servicesCache) return servicesCache;
			try {
				const data = await fetchEndpoint('services');
				servicesCache = Array.isArray(data?.items) ? data.items : [];
			} catch (e) {
				servicesCache = [];
			}
			return servicesCache;
		};

		const getFoldersCache = async () => {
			if (foldersCache) return foldersCache;
			try {
				const albums = await fetchAlbums();
				foldersCache = Array.isArray(albums) ? albums : [];
			} catch (e) {
				foldersCache = [];
			}
			return foldersCache;
		};

		const getJobsCache = async () => {
			if (jobsCache) return jobsCache;
			try {
				const data = await fetchEndpoint('jobs');
				jobsCache = Array.isArray(data?.items) ? data.items : [];
			} catch (e) {
				jobsCache = [];
			}
			return jobsCache;
		};

		const populateJobSelect = async (select, selectedValue) => {
			if (!select) return;
			const jobs = await getJobsCache();
			select.innerHTML = '<option value="">No job</option>';
			jobs.forEach((job) => {
				const id = job?.id ?? job?.job_id ?? '';
				if (!id) return;
				const option = document.createElement('option');
				option.value = String(id);
				option.textContent = job?.title ? `#${id} — ${job.title}` : `Job #${id}`;
				select.appendChild(option);
			});
			if (selectedValue) {
				const selected = String(selectedValue);
				const hasMatch = Array.from(select.options).some((option) => option.value === selected);
				if (!hasMatch) {
					const fallback = document.createElement('option');
					fallback.value = selected;
					fallback.textContent = `Job #${selected}`;
					select.appendChild(fallback);
				}
				select.value = selected;
			}
		};

		const sendEndpoint = async (resource, method, payload = null, itemId = null) => {
			return proxyRequest(resource, method, payload, itemId);
		};

		const fetchAlbums = async () => {
			const data = await proxyRequest('gallery-albums', 'GET');
			return data?.albums || data?.items || [];
		};

		const pickDisplay = (displays) => {
			if (!Array.isArray(displays) || !displays.length) return null;
			if (signageDeviceId) {
				const match = displays.find((display) => String(display.device_id || '').toLowerCase() === String(signageDeviceId).toLowerCase());
				if (match) return match;
			}
			return displays[0];
		};

		const pickPlaylist = (playlists, display) => {
			if (!Array.isArray(playlists) || !playlists.length) return null;
			if (display?.playlist_id) {
				const match = playlists.find((playlist) => String(playlist.id) === String(display.playlist_id));
				if (match) return match;
			}
			const fallback = playlists.find((playlist) => playlist.is_default || playlist.is_default === 1);
			return fallback || playlists[0];
		};

		const fetchSignageState = async () => {
			const [displayRes, playlistRes] = await Promise.allSettled([
				signageRequest('displays', 'GET'),
				signageRequest('playlists', 'GET')
			]);
			const displays = displayRes.status === 'fulfilled'
				? normalizeSignageList(displayRes.value, 'displays')
				: [];
			const playlists = playlistRes.status === 'fulfilled'
				? normalizeSignageList(playlistRes.value, 'playlists')
				: [];
			const display = pickDisplay(displays);
			const playlist = pickPlaylist(playlists, display);
			let items = [];
			if (playlist?.id) {
				try {
					const itemsRes = await signageRequest(`playlists/${playlist.id}`, 'GET', null, { _sub_resource: 'items' });
					items = normalizeSignageList(itemsRes, 'items');
				} catch (e) {
					items = [];
				}
			}
			return { displays, playlists, display, playlist, items };
		};

		const updateSignagePanel = (state) => {
			const display = state?.display || null;
			const playlist = state?.playlist || null;
			const items = Array.isArray(state?.items) ? state.items : [];
			if (signageDisplayValue) {
				signageDisplayValue.textContent = display?.name || display?.device_id || (signageDeviceId ? `Device ${signageDeviceId}` : 'Unassigned');
			}
			if (signageStatusValue) {
				const statusText = display?.status || (display?.active === 0 ? 'inactive' : 'unknown');
				signageStatusValue.textContent = statusText;
			}
			if (signagePlaylistValue) {
				signagePlaylistValue.textContent = playlist?.name || (playlist?.id ? `Playlist ${playlist.id}` : 'No playlist');
			}
			if (signageItemsValue) {
				signageItemsValue.textContent = items.length ? `${items.length} items` : 'No items';
			}
			if (signageCountValue) {
				signageCountValue.textContent = String(items.length || 0);
			}
		};

		const refreshSignagePanel = async () => {
			if (signageLocked) {
				applySignageLock();
				return null;
			}
			try {
				const state = await fetchSignageState();
				updateSignagePanel(state);
				return state;
			} catch (err) {
				if (signageStatusValue) signageStatusValue.textContent = 'Connection error';
				if (signageItemsValue) signageItemsValue.textContent = 'Unable to load';
				return null;
			}
		};

		const metrics = portal.querySelectorAll('[data-employee-metric]');
		const setMetric = (key, value) => {
			const node = portal.querySelector(`[data-employee-metric="${key}"]`);
			if (node) node.textContent = value;
		};

		Promise.allSettled(Object.keys(endpointMap).map(async (key) => {
			try {
				const data = await fetchEndpoint(key);
				setMetric(key, normalizeCount(data));
			} catch (err) {
				setMetric(key, '—');
			}
		})).finally(() => {
			if (statusBadge) statusBadge.textContent = 'Live data connected.';
			if (signageLocked) {
				applySignageLock();
			} else {
				refreshSignagePanel();
			}
		});

		const renderList = (key, data) => {
			if (!feedPanel || !feedTitle || !feedBody) return;
			const items = Array.isArray(data?.items) ? data.items : (Array.isArray(data?.results) ? data.results : []);
			const titleMap = {
				jobs: 'Jobs',
				gallery: 'Gallery',
				partners: 'Partners',
				services: 'Services',
				staff: 'Staff'
			};
			feedTitle.textContent = `${titleMap[key] || 'Results'} Snapshot`;
			if (!items.length) {
				feedBody.innerHTML = '<p class="lead">No records available.</p>';
				feedPanel.hidden = false;
				return;
			}
			const list = document.createElement('div');
			list.className = 'strip';
			list.style.gridTemplateColumns = 'repeat(auto-fit, minmax(220px, 1fr))';
			items.slice(0, 6).forEach((item) => {
				const tile = document.createElement('div');
				tile.className = 'tile';
				let label = '';
				let value = '';
				if (key === 'jobs') {
					label = item?.title || `Job #${item?.id ?? ''}`;
					value = item?.status ? String(item.status).replace('_', ' ') : 'Active';
				} else if (key === 'gallery') {
					label = item?.caption || item?.tag || `Gallery #${item?.id ?? ''}`;
					value = item?.item_type || 'image';
				} else if (key === 'partners') {
					label = item?.business_name || item?.name || `Partner #${item?.id ?? ''}`;
					value = item?.shop_type || item?.category || 'Partner';
				} else if (key === 'services') {
					label = item?.title || `Service #${item?.id ?? ''}`;
					value = item?.badge || 'Service';
				}
				tile.innerHTML = `<div class="label">${label}</div><div class="value">${value}</div>`;
				list.appendChild(tile);
			});
			feedBody.innerHTML = '';
			feedBody.appendChild(list);
			feedPanel.hidden = false;
		};

		const openManager = () => {
			if (!managerModal) return;
			managerModal.hidden = false;
			document.body.classList.add('mc-modal-open');
		};

		const closeManager = () => {
			if (!managerModal) return;
			closeFormModal();
			managerModal.hidden = true;
			document.body.classList.remove('mc-modal-open');
		};

		const setFormHeader = (title, subtitle = '') => {
			if (formTitle) formTitle.textContent = title || 'Add Item';
			if (formSubtitle) formSubtitle.textContent = subtitle || '';
		};

		const openFormModal = () => {
			if (!formModal) return;
			formModal.hidden = false;
			setTimeout(() => {
				const focusTarget = formModal.querySelector('input, select, textarea, button');
				if (focusTarget) focusTarget.focus({ preventScroll: true });
			}, 0);
		};

		const closeFormModal = () => {
			if (!formModal) return;
			formModal.hidden = true;
		};

		modalClosers.forEach((btn) => btn.addEventListener('click', closeManager));
		formClosers.forEach((btn) => btn.addEventListener('click', closeFormModal));
		const openJobModal = () => {
			if (!jobModal) return;
			jobModal.hidden = false;
			document.body.classList.add('mc-modal-open');
		};

		const closeJobModal = () => {
			if (!jobModal) return;
			jobModal.hidden = true;
			if (jobModalBody) jobModalBody.innerHTML = '';
			document.body.classList.remove('mc-modal-open');
		};

		jobModalClosers.forEach((btn) => btn.addEventListener('click', closeJobModal));
		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape') {
				closeFormModal();
				closeManager();
				closeJobModal();
			}
		});

		const setActiveTab = (key) => {
			managerTabs.forEach((tab) => {
				tab.classList.toggle('is-active', tab.dataset.managerTab === key);
			});
		};

		managerTabs.forEach((tab) => {
			tab.addEventListener('click', async () => {
				const key = tab.dataset.managerTab;
				if (key === 'signage') {
					if (signageLocked) {
						showManagerMessage('Restricted', 'Signage controls are limited to Sentra staff.');
						return;
					}
					showManagerMessage('Loading…', 'Fetching signage data.');
					try {
						const state = await refreshSignagePanel();
						renderSignageManager(state);
					} catch (err) {
						const message = err?.message ? err.message : 'Unable to load signage. Check API connectivity.';
						showManagerMessage('Connection error', message);
					}
					return;
				}
				if (!endpointMap[key]) return;
				showManagerMessage('Loading…', 'Fetching latest data.');
				try {
					const data = await fetchEndpoint(key);
					renderManager(key, data, false);
				} catch (err) {
					const message = err?.message ? err.message : `Unable to load ${key}. Check API connectivity.`;
					showManagerMessage('Connection error', message);
				}
			});
		});

		const showManagerMessage = (title, message) => {
			if (!managerPanel || !managerTitle || !managerSubtitle || !managerActions || !managerForm || !managerList) return;
			closeFormModal();
			openManager();
			managerTitle.textContent = title;
			managerSubtitle.textContent = '';
			managerActions.innerHTML = '';
			managerForm.innerHTML = '';
			managerList.innerHTML = `<p class="lead">${message}</p>`;
		};

		const showEmptyState = (title, message) => {
			if (!managerList) return;
			managerList.innerHTML = `
				<div class="mc-empty">
					<div class="mc-empty-icon">◎</div>
					<h4>${escapeHtml(title)}</h4>
					<p>${escapeHtml(message)}</p>
				</div>
			`;
		};

		const confirmPurgeGallery = async () => {
			const step1 = confirm('This will permanently delete all gallery media for this tenant. Continue?');
			if (!step1) return;
			const step2 = confirm('This cannot be undone. Are you absolutely sure you want to purge the gallery?');
			if (!step2) return;
			const phrase = prompt('Type PURGE GALLERY to confirm.');
			if (phrase !== 'PURGE GALLERY') {
				alert('Purge cancelled.');
				return;
			}

			showManagerMessage('Purging Gallery', 'Starting purge…');
			let deleted = 0;
			let failed = 0;
			try {
				while (true) {
					const data = await proxyRequest('gallery', 'GET', null, null, { per_page: 100 });
					const batch = Array.isArray(data?.items)
						? data.items
						: (Array.isArray(data?.results) ? data.results : (Array.isArray(data?.gallery) ? data.gallery : []));
					if (!batch.length) break;
					for (const item of batch) {
						const id = item?.id || item?.item_id || item?.media_id || item?.image_id;
						if (!id) {
							failed += 1;
							continue;
						}
						try {
							await sendEndpoint('gallery', 'DELETE', null, id);
							deleted += 1;
							if (deleted % 5 === 0) {
								showManagerMessage('Purging Gallery', `Deleted ${deleted} items…`);
							}
						} catch (err) {
							failed += 1;
						}
					}
				}
				const resultMsg = failed
					? `Deleted ${deleted} items. ${failed} failed.`
					: `Deleted ${deleted} items.`;
				showManagerMessage('Gallery Purged', resultMsg);
			} catch (err) {
				showManagerMessage('Purge Failed', err?.message || 'Unable to purge gallery.');
			}
			setTimeout(async () => {
				try {
				renderManager('gallery', await fetchEndpoint('gallery', { nocache: true }));
				} catch (e) {}
			}, 1200);
		};

		const renderManager = (key, data, focusCreate = false) => {
			if (!managerPanel || !managerTitle || !managerSubtitle || !managerActions || !managerForm || !managerList) return;
			closeFormModal();
			openManager();
			setActiveTab(key);
			managerActions.innerHTML = '';
			managerForm.innerHTML = '';
			managerList.innerHTML = '';

			const items = Array.isArray(data?.items) ? data.items : [];

			const addActionButton = (label, handler, outline = false) => {
				const btn = document.createElement('button');
				btn.type = 'button';
				btn.className = outline ? 'btn btn-outline' : 'btn btn-primary';
				btn.textContent = label;
				btn.addEventListener('click', handler);
				managerActions.appendChild(btn);
			};

			if (key === 'jobs') {
				managerTitle.textContent = 'Jobs Control';
				managerSubtitle.textContent = 'Manage job status, notes, and client updates.';
				addActionButton('Add Job', () => openFormModal());
				addActionButton('Featured Jobs', () => {
					const panel = managerList?.querySelector('#mc-featured-jobs-panel');
					if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
				}, true);
				addActionButton('Refresh Jobs', async () => renderManager('jobs', await fetchEndpoint('jobs')), true);
				renderJobs(items, focusCreate);
				return;
			}
			if (key === 'services') {
				managerTitle.textContent = 'Services Control';
				managerSubtitle.textContent = 'Update offerings, badges, and descriptions.';
				addActionButton('Add Service', () => openFormModal());
				addActionButton('Refresh Services', async () => renderManager('services', await fetchEndpoint('services')), true);
				renderServices(items, focusCreate);
				return;
			}
			if (key === 'partners') {
				managerTitle.textContent = 'Partners Control';
				managerSubtitle.textContent = 'Manage trusted partner shops and specialists.';
				addActionButton('Add Partner', () => openFormModal());
				addActionButton('Refresh Partners', async () => renderManager('partners', await fetchEndpoint('partners')), true);
				renderPartners(items, focusCreate);
				return;
			}
			if (key === 'gallery') {
				managerTitle.textContent = 'Gallery Control';
				managerSubtitle.textContent = 'Edit captions, tags, and featured items.';
				addActionButton('Add Gallery Item', () => openFormModal());
				addActionButton('Refresh Gallery', async () => renderManager('gallery', await fetchEndpoint('gallery', { nocache: true })), true);
				addActionButton('Purge Gallery', async () => confirmPurgeGallery(), true);
				addActionButton('Manage Folders', async () => {
					showManagerMessage('Loading…', 'Fetching folders.');
					try {
						const albums = await fetchAlbums();
						renderFolderManager(albums);
					} catch (err) {
						showManagerMessage('Connection error', err?.message || 'Unable to load folders.');
					}
				}, true);
				renderGallery(items, focusCreate);
				return;
			}
			if (key === 'staff') {
				managerTitle.textContent = 'Staff Badges';
				managerSubtitle.textContent = 'Grant staff access and manage roles for Moore’s.';
				addActionButton('Add Staff', () => openFormModal());
				addActionButton('Refresh Staff', async () => renderManager('staff', await fetchStaffList({ nocache: true })), true);
				renderStaff(items, focusCreate);
				return;
			}
		};

		const renderSignageManager = (state) => {
			if (!managerPanel || !managerTitle || !managerSubtitle || !managerActions || !managerForm || !managerList) return;
			if (signageLocked) {
				showManagerMessage('Restricted', 'Signage controls are limited to Sentra staff.');
				return;
			}
			openManager();
			setActiveTab('signage');
			managerTitle.textContent = 'Signage Control';
			managerSubtitle.textContent = 'Update the showroom display, playlists, and messaging.';
			managerActions.innerHTML = '';
			managerForm.innerHTML = '';
			managerList.innerHTML = '';

			const addActionButton = (label, handler, outline = false) => {
				const btn = document.createElement('button');
				btn.type = 'button';
				btn.className = outline ? 'btn btn-outline' : 'btn btn-primary';
				btn.textContent = label;
				btn.addEventListener('click', handler);
				managerActions.appendChild(btn);
			};

			addActionButton('Refresh Signage', async () => {
				showManagerMessage('Loading…', 'Fetching signage data.');
				try {
					const refreshed = await refreshSignagePanel();
					renderSignageManager(refreshed);
				} catch (err) {
					showManagerMessage('Connection error', err?.message || 'Unable to load signage.');
				}
			});
			if (signageManagerUrl) {
				addActionButton('Open Signage Manager', () => window.open(signageManagerUrl, '_blank'), true);
			}
			const playerUrl = buildPlayerUrl();
			if (playerUrl) {
				addActionButton('View Display', () => window.open(playerUrl, '_blank'), true);
			}

			const displays = Array.isArray(state?.displays) ? state.displays : [];
			const playlists = Array.isArray(state?.playlists) ? state.playlists : [];
			const display = state?.display || null;
			const playlist = state?.playlist || null;
			const items = Array.isArray(state?.items) ? state.items : [];

			const playlistOptions = playlists.map((entry) => {
				const selected = playlist && String(entry.id) === String(playlist.id) ? ' selected' : '';
				return `<option value="${escapeHtml(entry.id)}"${selected}>${escapeHtml(entry.name || `Playlist ${entry.id}`)}</option>`;
			}).join('');

			const displayName = display?.name || 'Moore’s Showroom';
			const displayDevice = display?.device_id || signageDeviceId || '';
			const displayStatus = display?.status || (display?.active === 0 ? 'inactive' : 'offline');
			const displayToken = display?.device_token || '';

			const textItem = items.find((item) => item.content_type === 'text') || null;
			const textData = parseContentData(textItem);
			const textContent = textData.content || '';
			const textDuration = textItem?.duration || 15;

			const galleryItem = items.find((item) => item.content_type === 'gallery') || null;
			const galleryData = parseContentData(galleryItem);
			const galleryTag = galleryData.tag || '';
			const galleryLimit = galleryData.limit || 12;
			const galleryDuration = galleryItem?.duration || 12;

			const videoItem = items.find((item) => item.content_type === 'video') || null;
			const videoData = parseContentData(videoItem);
			const videoUrl = videoData.url || '';
			const videoPath = videoData.path || '';
			const videoDuration = videoItem?.duration || 20;

			const clockItem = items.find((item) => item.content_type === 'clock') || null;
			const clockData = parseContentData(clockItem);
			const defaultTimezone = (() => {
				try {
					return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
				} catch (e) {
					return 'UTC';
				}
			})();
			const clockActive = clockItem ? (clockItem.active !== 0) : false;
			const clockFormat = clockData.format || '12h';
			const clockTimezone = clockData.timezone || defaultTimezone;

			managerForm.innerHTML = `
				<div class="mc-manager-form-card">
					<div class="mc-manager-form-head">
						<h4>${display ? 'Display Settings' : 'Register Display'}</h4>
						<p>${display ? 'Assign playlist and update display details.' : 'Create a display record for this device.'}</p>
					</div>
					<form data-signage-form="display">
						<div class="mc-manager-grid">
							<label class="mc-manager-field">
								<span>Display Name</span>
								<input type="text" name="name" value="${escapeHtml(displayName)}" required>
							</label>
							<label class="mc-manager-field">
								<span>Device ID</span>
								<input type="text" name="device_id" value="${escapeHtml(displayDevice)}" ${display ? 'disabled' : 'required'}>
							</label>
							<label class="mc-manager-field mc-manager-field-wide">
								<span>Device Token</span>
								<input type="text" name="device_token" value="${escapeHtml(displayToken)}" placeholder="Paste the device token used by the player">
							</label>
							<label class="mc-manager-field">
								<span>Status</span>
								<input type="text" value="${escapeHtml(displayStatus)}" disabled>
							</label>
							<label class="mc-manager-field mc-manager-field-wide">
								<span>Playlist</span>
								<select name="playlist_id" ${playlists.length ? '' : 'disabled'}>
									${playlistOptions || '<option value="">No playlists available</option>'}
								</select>
							</label>
						</div>
						<button class="btn btn-primary" type="submit">${display ? 'Update Display' : 'Register Display'}</button>
					</form>
				</div>
				<div class="mc-manager-form-card">
					<div class="mc-manager-form-head">
						<h4>Create Playlist</h4>
						<p>Start a new playlist for this display.</p>
					</div>
					<form data-signage-form="playlist">
						<div class="mc-manager-grid">
							<label class="mc-manager-field">
								<span>Playlist Name</span>
								<input type="text" name="name" placeholder="Moore's Default" required>
							</label>
							<label class="mc-manager-field">
								<span>Default</span>
								<select name="is_default">
									<option value="1">Yes</option>
									<option value="0">No</option>
								</select>
							</label>
							<label class="mc-manager-field mc-manager-field-wide">
								<span>Description</span>
								<textarea name="description" rows="2" placeholder="Optional description"></textarea>
							</label>
						</div>
						<button class="btn btn-outline" type="submit">Create Playlist</button>
					</form>
				</div>
				<div class="mc-manager-form-card">
					<div class="mc-manager-form-head">
						<h4>Text Slide</h4>
						<p>Update the fallback message that appears between media.</p>
					</div>
					<form data-signage-form="text">
						<div class="mc-manager-grid">
							<label class="mc-manager-field mc-manager-field-wide">
								<span>Message</span>
								<textarea name="content" rows="3" placeholder="Moore's CustomZ — Display online.">${escapeHtml(textContent)}</textarea>
							</label>
							<label class="mc-manager-field">
								<span>Duration (seconds)</span>
								<input type="number" name="duration" min="5" max="300" value="${escapeHtml(textDuration)}">
							</label>
						</div>
						<button class="btn btn-primary" type="submit">Save Text Slide</button>
					</form>
				</div>
				<div class="mc-manager-form-card">
					<div class="mc-manager-form-head">
						<h4>Gallery Feed</h4>
						<p>Pull real Moore’s gallery items into the display.</p>
					</div>
					<form data-signage-form="gallery">
						<div class="mc-manager-grid">
							<label class="mc-manager-field">
								<span>Tag (optional)</span>
								<input type="text" name="tag" placeholder="featured" value="${escapeHtml(galleryTag)}">
							</label>
							<label class="mc-manager-field">
								<span>Limit</span>
								<input type="number" name="limit" min="1" max="50" value="${escapeHtml(galleryLimit)}">
							</label>
							<label class="mc-manager-field">
								<span>Duration (seconds)</span>
								<input type="number" name="duration" min="5" max="120" value="${escapeHtml(galleryDuration)}">
							</label>
						</div>
						<button class="btn btn-primary" type="submit">Save Gallery Feed</button>
					</form>
				</div>
				<div class="mc-manager-form-card">
					<div class="mc-manager-form-head">
						<h4>Video Slide</h4>
						<p>Drop in a video URL or path for the showroom display.</p>
					</div>
					<form data-signage-form="video">
						<div class="mc-manager-grid">
							<label class="mc-manager-field mc-manager-field-wide">
								<span>Video URL</span>
								<input type="text" name="url" placeholder="https://..." value="${escapeHtml(videoUrl)}">
							</label>
							<label class="mc-manager-field mc-manager-field-wide">
								<span>Video Path</span>
								<input type="text" name="path" placeholder="/media/signage/video.mp4" value="${escapeHtml(videoPath)}">
							</label>
							<label class="mc-manager-field">
								<span>Duration (seconds)</span>
								<input type="number" name="duration" min="5" max="300" value="${escapeHtml(videoDuration)}">
							</label>
						</div>
						<button class="btn btn-primary" type="submit">Save Video Slide</button>
					</form>
				</div>
				<div class="mc-manager-form-card">
					<div class="mc-manager-form-head">
						<h4>Clock Widget</h4>
						<p>Toggle the live clock and set its format.</p>
					</div>
					<form data-signage-form="clock">
						<div class="mc-manager-grid">
							<label class="mc-manager-field">
								<span>Enabled</span>
								<select name="active">
									<option value="1"${clockActive ? ' selected' : ''}>On</option>
									<option value="0"${!clockActive ? ' selected' : ''}>Off</option>
								</select>
							</label>
							<label class="mc-manager-field">
								<span>Format</span>
								<select name="format">
									<option value="12h"${clockFormat === '12h' ? ' selected' : ''}>12h</option>
									<option value="24h"${clockFormat === '24h' ? ' selected' : ''}>24h</option>
								</select>
							</label>
							<label class="mc-manager-field mc-manager-field-wide">
								<span>Timezone</span>
								<input type="text" name="timezone" value="${escapeHtml(clockTimezone)}">
							</label>
						</div>
						<button class="btn btn-primary" type="submit">Update Clock</button>
					</form>
				</div>
			`;

			const displayForm = managerForm.querySelector('[data-signage-form="display"]');
			const playlistForm = managerForm.querySelector('[data-signage-form="playlist"]');
			const textForm = managerForm.querySelector('[data-signage-form="text"]');
			const galleryForm = managerForm.querySelector('[data-signage-form="gallery"]');
			const videoForm = managerForm.querySelector('[data-signage-form="video"]');
			const clockForm = managerForm.querySelector('[data-signage-form="clock"]');

			displayForm?.addEventListener('submit', async (event) => {
				event.preventDefault();
				const formData = new FormData(displayForm);
				const name = String(formData.get('name') || '').trim();
				const deviceId = String(formData.get('device_id') || '').trim();
				const deviceToken = String(formData.get('device_token') || '').trim();
				const playlistId = formData.get('playlist_id');
				const payload = { name };
				if (!display && deviceId) payload.device_id = deviceId;
				if (deviceToken) payload.device_token = deviceToken;
				if (playlistId) payload.playlist_id = Number(playlistId);
				try {
					if (display?.id) {
						await signageRequest(`displays/${display.id}`, 'PUT', payload);
					} else {
						await signageRequest('displays', 'POST', payload);
					}
					const refreshed = await refreshSignagePanel();
					renderSignageManager(refreshed);
				} catch (err) {
					showManagerMessage('Connection error', err?.message || 'Unable to update display.');
				}
			});

			playlistForm?.addEventListener('submit', async (event) => {
				event.preventDefault();
				const formData = new FormData(playlistForm);
				const payload = {
					name: String(formData.get('name') || '').trim(),
					description: String(formData.get('description') || '').trim(),
					is_default: formData.get('is_default') === '1'
				};
				if (!payload.name) return;
				try {
					await signageRequest('playlists', 'POST', payload);
					const refreshed = await refreshSignagePanel();
					renderSignageManager(refreshed);
				} catch (err) {
					showManagerMessage('Connection error', err?.message || 'Unable to create playlist.');
				}
			});

			textForm?.addEventListener('submit', async (event) => {
				event.preventDefault();
				if (!playlist?.id) {
					showManagerMessage('No playlist', 'Create a playlist before updating text slides.');
					return;
				}
				const formData = new FormData(textForm);
				const content = String(formData.get('content') || '').trim();
				const duration = Number(formData.get('duration') || 15);
				const payload = {
					content_type: 'text',
					content_data: { content },
					duration: duration || 15
				};
				try {
					if (textItem?.id) {
						await signageRequest(`playlists/${playlist.id}`, 'PUT', payload, { _sub_resource: 'items', _sub_id: textItem.id });
					} else {
						await signageRequest(`playlists/${playlist.id}`, 'POST', {
							...payload,
							position: items.length + 1
						}, { _sub_resource: 'items' });
					}
					const refreshed = await refreshSignagePanel();
					renderSignageManager(refreshed);
				} catch (err) {
					showManagerMessage('Connection error', err?.message || 'Unable to update text slide.');
				}
			});

			galleryForm?.addEventListener('submit', async (event) => {
				event.preventDefault();
				if (!playlist?.id) {
					showManagerMessage('No playlist', 'Create a playlist before adding gallery feeds.');
					return;
				}
				const formData = new FormData(galleryForm);
				const tag = String(formData.get('tag') || '').trim();
				const limit = Number(formData.get('limit') || 12);
				const duration = Number(formData.get('duration') || 12);
				const payload = {
					content_type: 'gallery',
					content_data: {
						tag: tag || null,
						limit: limit || 12
					},
					duration: duration || 12
				};
				try {
					if (galleryItem?.id) {
						await signageRequest(`playlists/${playlist.id}`, 'PUT', payload, { _sub_resource: 'items', _sub_id: galleryItem.id });
					} else {
						await signageRequest(`playlists/${playlist.id}`, 'POST', {
							...payload,
							position: items.length + 1
						}, { _sub_resource: 'items' });
					}
					const refreshed = await refreshSignagePanel();
					renderSignageManager(refreshed);
				} catch (err) {
					showManagerMessage('Connection error', err?.message || 'Unable to update gallery feed.');
				}
			});

			videoForm?.addEventListener('submit', async (event) => {
				event.preventDefault();
				if (!playlist?.id) {
					showManagerMessage('No playlist', 'Create a playlist before adding video.');
					return;
				}
				const formData = new FormData(videoForm);
				const url = String(formData.get('url') || '').trim();
				const path = String(formData.get('path') || '').trim();
				const duration = Number(formData.get('duration') || 20);
				if (!url && !path) {
					showManagerMessage('Missing video', 'Add a video URL or path first.');
					return;
				}
				const payload = {
					content_type: 'video',
					content_data: {
						url: url || undefined,
						path: path || undefined
					},
					duration: duration || 20
				};
				try {
					if (videoItem?.id) {
						await signageRequest(`playlists/${playlist.id}`, 'PUT', payload, { _sub_resource: 'items', _sub_id: videoItem.id });
					} else {
						await signageRequest(`playlists/${playlist.id}`, 'POST', {
							...payload,
							position: items.length + 1
						}, { _sub_resource: 'items' });
					}
					const refreshed = await refreshSignagePanel();
					renderSignageManager(refreshed);
				} catch (err) {
					showManagerMessage('Connection error', err?.message || 'Unable to update video slide.');
				}
			});

			clockForm?.addEventListener('submit', async (event) => {
				event.preventDefault();
				if (!playlist?.id) {
					showManagerMessage('No playlist', 'Create a playlist before updating the clock.');
					return;
				}
				const formData = new FormData(clockForm);
				const active = formData.get('active') === '1';
				const payload = {
					content_type: 'clock',
					content_data: {
						format: String(formData.get('format') || '12h'),
						timezone: String(formData.get('timezone') || defaultTimezone),
						style: 'digital'
					},
					duration: clockItem?.duration || 15,
					active
				};
				try {
					if (clockItem?.id) {
						await signageRequest(`playlists/${playlist.id}`, 'PUT', payload, { _sub_resource: 'items', _sub_id: clockItem.id });
					} else if (active) {
						await signageRequest(`playlists/${playlist.id}`, 'POST', {
							...payload,
							position: items.length + 1
						}, { _sub_resource: 'items' });
					}
					const refreshed = await refreshSignagePanel();
					renderSignageManager(refreshed);
				} catch (err) {
					showManagerMessage('Connection error', err?.message || 'Unable to update clock.');
				}
			});

			if (!playlists.length) {
				showEmptyState('No playlists yet', 'Create a playlist to start building the showroom feed.');
				return;
			}

			if (!items.length) {
				showEmptyState('No playlist items', 'Add a text slide, gallery feed, or clock widget.');
				return;
			}

			items.forEach((item) => {
				const data = parseContentData(item);
				const title = item.content_type ? item.content_type.toUpperCase() : 'ITEM';
				const detail = item.content_type === 'text' ? (data.content || 'Text slide') : (item.content_type === 'clock' ? 'Clock widget' : 'Media item');
				const meta = `Duration ${item.duration || 0}s • ${item.active === 0 ? 'Inactive' : 'Active'}`;
				const row = document.createElement('div');
				row.className = 'mc-manager-item';
				row.innerHTML = `
					<div class="mc-manager-item-header">
						<div>
							<div class="mc-manager-item-title">${escapeHtml(title)}</div>
							<div class="mc-manager-item-sub">${escapeHtml(detail)}</div>
						</div>
						<div class="mc-chip ${item.active === 0 ? 'mc-chip-inactive' : 'mc-chip-active'}">${item.active === 0 ? 'Inactive' : 'Active'}</div>
					</div>
					<div class="mc-manager-item-grid">
						<div class="mc-inline-field">
							<span>Position</span>
							<input type="text" value="${escapeHtml(item.position ?? '—')}" disabled>
						</div>
						<div class="mc-inline-field">
							<span>Duration</span>
							<input type="text" value="${escapeHtml(item.duration ?? '—')}" disabled>
						</div>
						<div class="mc-inline-field mc-inline-field-wide">
							<span>Notes</span>
							<input type="text" value="${escapeHtml(meta)}" disabled>
						</div>
					</div>
				`;
				managerList.appendChild(row);
			});
		};

		const renderFolderManager = (albums) => {
			if (!managerPanel || !managerTitle || !managerSubtitle || !managerActions || !managerForm || !managerList) return;
			closeFormModal();
			openManager();
			setActiveTab('gallery');
			managerTitle.textContent = 'Gallery Folders';
			managerSubtitle.textContent = 'Create folders and keep gallery organization tight.';
			managerActions.innerHTML = '';
			setFormHeader('Create Folder', 'Group gallery items by collection or project.');
			const addActionButton = (label, handler, outline = false) => {
				const btn = document.createElement('button');
				btn.type = 'button';
				btn.className = outline ? 'btn btn-outline' : 'btn btn-primary';
				btn.textContent = label;
				btn.addEventListener('click', handler);
				managerActions.appendChild(btn);
			};
			addActionButton('Add Folder', () => openFormModal());
			addActionButton('Back to Gallery', async () => {
				showManagerMessage('Loading…', 'Returning to gallery.');
				try {
					const data = await fetchEndpoint('gallery', { nocache: true });
					renderManager('gallery', data, false);
				} catch (err) {
					showManagerMessage('Connection error', err?.message || 'Unable to load gallery.');
				}
			}, true);
			managerForm.innerHTML = `
				<div class="mc-manager-form-card">
					<div class="mc-manager-form-head">
						<h4>Create Folder</h4>
						<p>Group gallery items by collection or project.</p>
					</div>
					<form data-manager-form="folders">
						<div class="mc-manager-grid">
							<label class="mc-manager-field">
								<span>Folder Name</span>
								<input type="text" name="name" placeholder="Showroom Builds" required>
							</label>
							<label class="mc-manager-field mc-manager-field-wide">
								<span>Description</span>
								<textarea name="description" rows="2" placeholder="Optional folder description"></textarea>
							</label>
						</div>
						<button class="btn btn-primary" type="submit">Add Folder</button>
					</form>
				</div>
			`;
			const form = managerForm.querySelector('form');
			form.addEventListener('submit', async (event) => {
				event.preventDefault();
				const data = Object.fromEntries(new FormData(form).entries());
				await sendEndpoint('gallery-albums', 'POST', data);
				renderFolderManager(await fetchAlbums());
			});

			if (!Array.isArray(albums) || !albums.length) {
				showEmptyState('No folders yet', 'Create a folder to organize gallery items.');
				return;
			}

			const list = document.createElement('div');
			list.className = 'mc-manager-list';
			albums.forEach((album) => {
				const row = document.createElement('div');
				row.className = 'mc-manager-item';
				row.innerHTML = `
					<div class="mc-manager-item-header">
						<div>
							<div class="mc-manager-item-title">${escapeHtml(album.name || 'Folder')}</div>
							<div class="mc-manager-item-sub">${escapeHtml(album.description || 'No description')}</div>
						</div>
						<div class="mc-chip">${escapeHtml(String(album.item_count ?? 0))} items</div>
					</div>
					<div class="mc-manager-item-grid">
						<label class="mc-inline-field">
							<span>Folder Name</span>
							<input type="text" value="${escapeHtml(album.name || '')}" data-field="name">
						</label>
						<label class="mc-inline-field mc-inline-field-wide">
							<span>Description</span>
							<textarea rows="2" data-field="description">${escapeHtml(album.description || '')}</textarea>
						</label>
					</div>
					<div class="mc-manager-item-actions">
						<button class="btn btn-primary" type="button" data-save>Save</button>
						<button class="btn btn-outline" type="button" data-delete>Delete</button>
					</div>
				`;
				row.querySelector('[data-save]').addEventListener('click', async () => {
					const payload = {};
					row.querySelectorAll('[data-field]').forEach((field) => {
						payload[field.dataset.field] = field.value;
					});
					await sendEndpoint('gallery-albums', 'PUT', payload, album.id);
					renderFolderManager(await fetchAlbums());
				});
				row.querySelector('[data-delete]').addEventListener('click', async () => {
					await sendEndpoint('gallery-albums', 'DELETE', null, album.id);
					renderFolderManager(await fetchAlbums());
				});
				list.appendChild(row);
			});
			managerList.innerHTML = '';
			managerList.appendChild(list);
		};

		let activeJobId = null;

		const renderJobs = (items, focusCreate) => {
			setFormHeader('New Job', 'Create a new job and assign services, team members, and key dates.');
			managerForm.innerHTML = `
				<div class="mc-manager-form-card">
					<div class="mc-manager-form-head">
						<h4>New Job</h4>
						<p>Create a new job and assign services, team members, and key dates.</p>
					</div>
					<form data-manager-form="jobs">
					<div class="mc-manager-grid">
						<label class="mc-manager-field">
							<span>Title</span>
							<input type="text" name="title" placeholder="Example: 1969 Camaro repaint" required>
						</label>
						<label class="mc-manager-field">
							<span>Client Name</span>
							<input type="text" name="client_name" placeholder="Client name">
						</label>
						<label class="mc-manager-field">
							<span>Client Email</span>
							<input type="email" name="client_email" placeholder="client@email.com">
						</label>
						<label class="mc-manager-field">
							<span>Sales Rep</span>
							<input type="text" name="sales_rep" placeholder="Assigned salesperson">
						</label>
						<label class="mc-manager-field">
							<span>Lead Tech</span>
							<input type="text" name="lead_tech" placeholder="Current lead tech">
						</label>
						<label class="mc-manager-field">
							<span>Tech Team</span>
							<input type="text" name="tech_team" placeholder="Comma separated">
						</label>
						<label class="mc-manager-field mc-manager-field-wide">
							<span>Services</span>
							<div class="mc-choice-grid" data-job-services-select></div>
						</label>
						<label class="mc-manager-field">
							<span>Start Date</span>
							<input type="date" name="start_date">
						</label>
						<label class="mc-manager-field">
							<span>Due Date</span>
							<input type="date" name="due_date">
						</label>
						<label class="mc-manager-field">
							<span>Estimated Hours</span>
							<input type="number" name="estimated_hours" step="0.1" min="0" placeholder="24">
						</label>
						<label class="mc-manager-field">
							<span>Folder</span>
							<select name="folder" data-job-folder-select>
								<option value="">No folder</option>
							</select>
						</label>
						<label class="mc-manager-field">
							<span>Status</span>
							<select name="status">
								<option value="new">New</option>
								<option value="in_progress">In Progress</option>
								<option value="on_hold">On Hold</option>
								<option value="awaiting_client">Awaiting Client</option>
								<option value="done">Done</option>
								<option value="invoiced">Invoiced</option>
							</select>
						</label>
						<label class="mc-manager-field mc-manager-field-wide">
							<span>Description / Summary</span>
							<textarea name="description" rows="2" placeholder="Quick summary of what the job includes"></textarea>
						</label>
						<label class="mc-manager-field mc-manager-field-wide">
							<span>Notes / Work Log</span>
							<textarea name="notes" rows="2" placeholder="Prep, parts ordered, client notes…"></textarea>
						</label>
					</div>
					<button class="btn btn-primary" type="submit">Create Job</button>
					</form>
				</div>
			`;

			const form = managerForm.querySelector('form[data-manager-form="jobs"]');
			const servicesContainer = form.querySelector('[data-job-services-select]');
			const folderSelect = form.querySelector('[data-job-folder-select]');
			const startInput = form.querySelector('input[name="start_date"]');
			if (startInput && !startInput.value) {
				startInput.value = toDateInput(new Date().toISOString());
			}
			const clientNameInput = form.querySelector('input[name="client_name"]');
			const clientEmailInput = form.querySelector('input[name="client_email"]');
			if (clientEmailInput && clientNameInput) {
				clientEmailInput.addEventListener('blur', async () => {
					const emailVal = clientEmailInput.value.trim();
					if (!emailVal) return;
					const resolved = await resolveClientRecord(clientNameInput.value.trim(), emailVal);
					if (resolved?.id) {
						clientNameInput.value = resolved.name || clientNameInput.value || autoNameFromEmail(emailVal);
						clientEmailInput.value = resolved.email || emailVal;
						return;
					}
					if (!clientNameInput.value.trim()) {
						if (isMooresEmail(emailVal)) {
							const staffName = await lookupStaffName(emailVal);
							clientNameInput.value = staffName || autoNameFromEmail(emailVal);
						} else {
							clientNameInput.value = autoNameFromEmail(emailVal);
						}
					}
				});
			}
			form.addEventListener('submit', async (event) => {
				event.preventDefault();
				const data = Object.fromEntries(new FormData(form).entries());
				const clientName = (data.client_name || '').trim();
				const clientEmail = (data.client_email || '').trim();
				const clientRecord = await resolveClientRecord(clientName, clientEmail);
				const effectiveClientName = clientRecord?.name || clientName;
				const effectiveClientEmail = clientRecord?.email || clientEmail;
				const useClientTags = !clientRecord && (effectiveClientName || effectiveClientEmail);
				const folder = (data.folder || '').trim();
				const servicesSelected = getSelectedServices(servicesContainer);
				const startDate = data.start_date || '';
				const description = data.description || '';
				const tags = composeJobTags([], {
					folder,
					services: servicesSelected,
					sales: (data.sales_rep || '').trim(),
					lead: (data.lead_tech || '').trim(),
					techs: data.tech_team,
					start: startDate,
					clientName: useClientTags ? effectiveClientName : '',
					clientEmail: useClientTags ? effectiveClientEmail : '',
				});
				const payload = {
					title: data.title,
					status: data.status,
					due_date: data.due_date || null,
					estimated_hours: data.estimated_hours ? parseFloat(data.estimated_hours) : null,
					description: description || null,
					notes: data.notes || '',
					tags: tags.length ? tags : null,
				};
				if (clientRecord?.id) {
					payload.client_id = clientRecord.id;
				}
				await sendEndpoint('jobs', 'POST', payload);
				renderManager('jobs', await fetchEndpoint('jobs'));
			});

			const formatMessageBody = (text) => {
				const safe = escapeHtml(text || '');
				const urlRegex = /(https?:\/\/[^\s]+)/g;
				return safe.replace(urlRegex, (url) => `<a href="${url}" target="_blank" rel="noopener">${url}</a>`).replace(/\n/g, '<br>');
			};

			const firstUrl = (text) => {
				if (!text) return '';
				const match = String(text).match(/https?:\/\/[^\s]+/);
				return match ? match[0] : '';
			};

			const isImageUrl = (url) => /\.(png|jpe?g|gif|webp)(\?|#|$)/i.test(url || '');

			const formatTimestamp = (value) => {
				if (!value) return '';
				const dt = new Date(value);
				if (!isNaN(dt.getTime())) return dt.toLocaleString();
				return String(value);
			};

			function wireStaffMessaging() {
				const staffPanel = portal.querySelector('[data-staff-comms]');
				if (!staffPanel) return;
				const nameField = staffPanel.querySelector('[data-staff-active-name]');
				const statusField = staffPanel.querySelector('[data-staff-active-status]');
				const refreshButtons = staffPanel.querySelectorAll('[data-staff-refresh]');
				const composeForms = staffPanel.querySelectorAll('[data-staff-compose]');

				const staffName = authPacket?.name
					|| authPacket?.user?.name
					|| authPacket?.email
					|| authPacket?.user?.email
					|| authPacket?.username
					|| authPacket?.user?.username
					|| 'Staff';

				if (nameField) nameField.textContent = staffName;
				if (statusField) statusField.textContent = 'Active';

				const staffThreads = {
					messages: 'staff_messages',
					activity: 'staff_activity',
				};

				const renderThread = (container, items) => {
					if (!container) return;
					if (!items.length) {
						container.innerHTML = '<p class="lead">No messages yet.</p>';
						return;
					}
					container.innerHTML = items.map((msg) => {
						const body = msg.body || '';
						const url = firstUrl(body);
						const media = url && isImageUrl(url)
							? `<div class="mc-chat-media"><img src="${escapeHtml(url)}" alt="attachment"></div>`
							: '';
						const senderType = msg.sender_type || 'staff';
						const isClient = senderType === 'client';
						return `
							<div class="mc-chat-message ${isClient ? 'is-client' : ''}">
								<div class="mc-chat-meta">
									<span>${escapeHtml(senderType)}</span>
									<span>${escapeHtml(formatTimestamp(msg.created_at))}</span>
								</div>
								<div class="mc-chat-body">${formatMessageBody(body)}</div>
								${media}
							</div>
						`;
					}).join('');
				};

				const loadThread = async (key) => {
					const threadId = staffThreads[key];
					const container = staffPanel.querySelector(`[data-staff-thread="${key}"]`);
					if (!threadId || !container) return;
					try {
						const data = await proxyRequest('messages', 'GET', null, null, { thread_id: threadId, per_page: 200 });
						const items = Array.isArray(data?.items) ? data.items.slice().reverse() : [];
						renderThread(container, items);
					} catch (err) {
						container.innerHTML = `<p class="lead">Unable to load messages. ${escapeHtml(err?.message || '')}</p>`;
					}
				};

				const sendThreadMessage = async (key, body) => {
					const threadId = staffThreads[key];
					if (!threadId || !body) return;
					await proxyRequest('messages', 'POST', {
						thread_id: threadId,
						body,
						sender_id: authPacket?.email || authPacket?.user?.email || authPacket?.username || '',
						sender_type: 'staff'
					});
				};

				refreshButtons.forEach((btn) => {
					btn.addEventListener('click', () => {
						const key = btn.getAttribute('data-staff-refresh');
						if (key) loadThread(key);
					});
				});

				composeForms.forEach((form) => {
					form.addEventListener('submit', async (event) => {
						event.preventDefault();
						const key = form.getAttribute('data-staff-compose');
						const messageField = form.querySelector('textarea[name="message"]');
						const body = messageField?.value.trim() || '';
						if (!body || !key) return;
						await sendThreadMessage(key, body);
						messageField.value = '';
						loadThread(key);
					});
				});

				const logStaffActivity = async () => {
					const flag = 'mc_staff_activity_logged';
					try {
						if (sessionStorage.getItem(flag) === '1') return;
						sessionStorage.setItem(flag, '1');
					} catch (e) {}
					const note = `${staffName} opened the employee portal.`;
					try {
						await sendThreadMessage('activity', note);
						loadThread('activity');
					} catch (e) {}
				};

				loadThread('messages');
				loadThread('activity');
				logStaffActivity();
			}

		const renderServiceChoices = (container, services, selected = []) => {
				if (!container) return;
				container.innerHTML = '';
				const selectedSet = new Set(selected.map((item) => item.toLowerCase()));
				if (!services.length) {
					container.innerHTML = '<span class="mc-muted">No services available yet.</span>';
					return;
				}
				container.classList.add('mc-choice-grid');
				services.forEach((svc, index) => {
					const label = svc?.title || svc?.name || svc?.badge || '';
					if (!label) return;
					const id = `svc_${index}_${label.replace(/\\s+/g, '_')}`;
					const checked = selectedSet.has(label.toLowerCase()) ? 'checked' : '';
					container.insertAdjacentHTML('beforeend', `
						<label class="mc-choice" for="${id}">
							<input type="checkbox" id="${id}" value="${escapeHtml(label)}" ${checked}>
							<span>${escapeHtml(label)}</span>
						</label>
					`);
				});
			};

			const getSelectedServices = (container) => {
				if (!container) return [];
				return Array.from(container.querySelectorAll('input[type="checkbox"]:checked'))
					.map((input) => String(input.value).trim())
					.filter(Boolean);
			};

		const populateFolderSelect = async (select, selectedValue) => {
			if (!select) return;
			const albums = await getFoldersCache();
				select.innerHTML = '<option value="">No folder</option>';
				albums.forEach((album) => {
					const name = album?.name || '';
					if (!name) return;
					const option = document.createElement('option');
					option.value = name;
					option.textContent = name;
					select.appendChild(option);
				});
				if (selectedValue) {
					const hasMatch = Array.from(select.options).some((option) => option.value === selectedValue);
					if (!hasMatch) {
						const fallback = document.createElement('option');
						fallback.value = selectedValue;
						fallback.textContent = selectedValue;
						select.appendChild(fallback);
					}
					select.value = selectedValue;
			}
		};

			(() => {
				if (servicesContainer) {
					getServicesCache().then((services) => {
						renderServiceChoices(servicesContainer, services, []);
					});
				}
				if (folderSelect) {
					populateFolderSelect(folderSelect, folderSelect.value || '');
				}
			})();

			const renderJobDetail = async (job, host) => {
				if (!host || !job) return;
				activeJobId = job.id;
				const threadId = threadIdForJob(job.id);
				const servicesList = collectPrefixed(job.tags, 'service:');
				const salesRep = collectPrefixed(job.tags, 'sales:')[0] || '';
				const leadTech = collectPrefixed(job.tags, 'lead:')[0] || '';
				const techTeam = collectPrefixed(job.tags, 'tech:').join(', ');
				const clientTagName = extractJobClientName(job.tags);
				const clientTagEmail = extractJobClientEmail(job.tags);
				const folderTag = deriveFolderTag(job.tags);
				const startDate = getStartDateFromTags(job.tags, job.created_at);
				const estHours = parseFloat(job.estimated_hours || 0);
				const actHours = parseFloat(job.actual_hours || 0);
				const progress = estHours > 0 ? Math.min(actHours / estHours, 1) : (job.status === 'done' ? 1 : 0);
				const progressPct = Math.round(progress * 100);
				host.innerHTML = `
					<div class="mc-job-detail-header">
						<div>
							<div class="kicker">Job #${escapeHtml(job.id ?? '')}</div>
							<h4>${escapeHtml(job.title || 'Untitled Job')}</h4>
						</div>
						<div class="mc-chip mc-chip-${escapeHtml(job.status || 'new')}">${escapeHtml((job.status || 'new').replace('_', ' '))}</div>
					</div>
					<div class="mc-job-panel-progress">
						<div class="mc-job-progress-bar" style="width:${progressPct}%"></div>
					</div>
					<div class="mc-job-progress-meta">
						<span>${progressPct}% complete</span>
						<span>${escapeHtml(actHours || 0)}h / ${escapeHtml(estHours || 0)}h</span>
					</div>
					<div class="mc-job-detail-grid">
						<label class="mc-inline-field">
							<span>Title</span>
							<input type="text" data-job-title value="${escapeHtml(job.title || '')}">
						</label>
						<label class="mc-inline-field">
							<span>Client Name</span>
							<input type="text" data-job-client-name value="" placeholder="Client name">
						</label>
						<label class="mc-inline-field">
							<span>Client Email</span>
							<input type="email" data-job-client-email value="" placeholder="client@email.com">
						</label>
						<label class="mc-inline-field">
							<span>Client Ref</span>
							<input type="text" value="${escapeHtml(job.client_id || '')}" disabled>
						</label>
						<label class="mc-inline-field">
							<span>Status</span>
							<select data-job-status>
								<option value="new">New</option>
								<option value="in_progress">In Progress</option>
								<option value="on_hold">On Hold</option>
								<option value="awaiting_client">Awaiting Client</option>
								<option value="done">Done</option>
								<option value="invoiced">Invoiced</option>
							</select>
						</label>
						<label class="mc-inline-field">
							<span>Start Date</span>
							<input type="date" data-job-start value="${escapeHtml(startDate)}">
						</label>
						<label class="mc-inline-field">
							<span>Due Date</span>
							<input type="date" data-job-due value="${escapeHtml(toDateInput(job.due_date))}">
						</label>
						<label class="mc-inline-field">
							<span>Estimated Hours</span>
							<input type="number" step="0.1" min="0" data-job-estimated value="${escapeHtml(job.estimated_hours ?? '')}">
						</label>
						<label class="mc-inline-field">
							<span>Actual Hours</span>
							<input type="number" step="0.1" min="0" data-job-actual value="${escapeHtml(job.actual_hours ?? '')}">
						</label>
						<label class="mc-inline-field">
							<span>Folder</span>
							<select data-job-folder></select>
						</label>
						<label class="mc-inline-field mc-inline-field-wide">
							<span>Services</span>
							<div class="mc-choice-grid" data-job-services-select></div>
						</label>
						<label class="mc-inline-field">
							<span>Sales Rep</span>
							<input type="text" data-job-sales value="${escapeHtml(salesRep)}" placeholder="Salesperson name">
						</label>
						<label class="mc-inline-field">
							<span>Lead Tech</span>
							<input type="text" data-job-lead value="${escapeHtml(leadTech)}" placeholder="Lead technician">
						</label>
						<label class="mc-inline-field">
							<span>Tech Team</span>
							<input type="text" data-job-techs value="${escapeHtml(techTeam)}" placeholder="Comma separated">
						</label>
						<label class="mc-inline-field mc-inline-field-wide">
							<span>Job Summary</span>
							<textarea rows="2" data-job-description>${escapeHtml(job.description || job.summary || '')}</textarea>
						</label>
						<label class="mc-inline-field mc-inline-field-wide">
							<span>Notes / Work Log</span>
							<textarea rows="3" data-job-notes>${escapeHtml(job.notes || '')}</textarea>
						</label>
					</div>
					<div class="mc-job-log">
						<div class="mc-job-log-head">
							<h5>Work Log</h5>
							<span class="mc-job-chat-meta">Add updates for tech involvement</span>
						</div>
						<div class="mc-job-log-controls">
							<input type="text" data-job-log-author placeholder="Tech name (optional)">
							<input type="text" data-job-log-entry placeholder="Log entry">
							<button class="btn btn-outline" type="button" data-job-log-add>Add Entry</button>
						</div>
						<div class="mc-job-log-list" data-job-log-list></div>
					</div>
					<div class="mc-job-client" data-job-client-card>
						<h5>Client Information</h5>
						<p class="lead">Client details will appear here when linked.</p>
					</div>
					<div class="mc-job-detail-actions">
						<button class="btn btn-primary" type="button" data-job-save>Save Job</button>
						<button class="btn btn-outline" type="button" data-job-refresh>Refresh Thread</button>
						<button class="btn btn-outline" type="button" data-job-delete>Delete Job</button>
					</div>
					<div class="mc-job-chat" data-job-chat>
						<div class="mc-job-chat-head">
							<h5>Job Messages</h5>
							<span class="mc-job-chat-meta">Thread: ${escapeHtml(job.title || `Job #${job.id}`)}</span>
						</div>
						<div class="mc-job-chat-thread" data-job-thread>
							<p class="lead">Loading messages…</p>
						</div>
						<form class="mc-job-chat-compose" data-job-compose>
							<textarea rows="2" name="message" placeholder="Share an update with the team…"></textarea>
							<button class="btn btn-primary" type="submit">Send</button>
						</form>
						<div class="mc-job-attachments">
							<div class="mc-job-attachments-head">
								<h5>Job Media</h5>
								<span class="mc-job-chat-meta">Images shared in this job</span>
							</div>
							<div class="mc-job-attachments-grid" data-job-media>
								<p class="lead">Loading media…</p>
							</div>
							<div class="mc-job-attachments-upload">
								<input type="file" accept="image/*,video/*" data-job-file>
								<input type="text" placeholder="Caption (optional)" data-job-caption>
								<button class="btn btn-outline" type="button" data-job-upload>Upload</button>
							</div>
						</div>
					</div>
				`;

				const statusSelect = host.querySelector('[data-job-status]');
				statusSelect.value = job.status || 'new';
				const servicesContainerDetail = host.querySelector('[data-job-services-select]');
				const folderSelectDetail = host.querySelector('[data-job-folder]');
				getServicesCache().then((services) => {
					renderServiceChoices(servicesContainerDetail, services, servicesList);
				});
				populateFolderSelect(folderSelectDetail, folderTag);
				const clientNameField = host.querySelector('[data-job-client-name]');
				const clientEmailField = host.querySelector('[data-job-client-email]');
				if (clientNameField && clientTagName) clientNameField.value = clientTagName;
				if (clientEmailField && clientTagEmail) clientEmailField.value = clientTagEmail;
				if (clientEmailField && clientNameField) {
					clientEmailField.addEventListener('blur', async () => {
						const emailVal = clientEmailField.value.trim();
						if (!emailVal) return;
						const resolved = await resolveClientRecord(clientNameField.value.trim(), emailVal);
						if (resolved?.id) {
							clientNameField.value = resolved.name || clientNameField.value || autoNameFromEmail(emailVal);
							clientEmailField.value = resolved.email || emailVal;
							return;
						}
						if (!clientNameField.value.trim()) {
							if (isMooresEmail(emailVal)) {
								const staffName = await lookupStaffName(emailVal);
								clientNameField.value = staffName || autoNameFromEmail(emailVal);
							} else {
								clientNameField.value = autoNameFromEmail(emailVal);
							}
						}
					});
				}

				const renderLogList = () => {
					const logList = host.querySelector('[data-job-log-list]');
					const notesText = host.querySelector('[data-job-notes]').value || '';
					const lines = notesText.split('\n').map((line) => line.trim()).filter(Boolean);
					if (!lines.length) {
						logList.innerHTML = '<p class="lead">No log entries yet.</p>';
						return;
					}
					logList.innerHTML = `
						<ul class="mc-log-list">
							${lines.map((line) => `<li>${escapeHtml(line)}</li>`).join('')}
						</ul>
					`;
				};
				renderLogList();

				const logAddButton = host.querySelector('[data-job-log-add]');
				logAddButton.addEventListener('click', () => {
					const author = host.querySelector('[data-job-log-author]').value.trim();
					const entry = host.querySelector('[data-job-log-entry]').value.trim();
					if (!entry) return;
					const stamp = new Date().toLocaleString();
					const prefix = author ? `${stamp} — ${author}: ` : `${stamp} — `;
					const notesField = host.querySelector('[data-job-notes]');
					const existing = notesField.value ? `${notesField.value}\n` : '';
					notesField.value = `${existing}${prefix}${entry}`;
					host.querySelector('[data-job-log-entry]').value = '';
					renderLogList();
				});

				const loadClientInfo = async () => {
					const card = host.querySelector('[data-job-client-card]');
					const rawClient = (job.client_id || '').toString().trim();
					if (!rawClient) {
						const fallbackName = (clientNameField?.value || '').trim() || clientTagName;
						const fallbackEmail = (clientEmailField?.value || '').trim() || clientTagEmail;
						if (fallbackName || fallbackEmail) {
							card.innerHTML = `
								<h5>Client Information</h5>
								<div class="mc-client-grid">
									<div><strong>Name</strong><span>${escapeHtml(fallbackName || '—')}</span></div>
									<div><strong>Email</strong><span>${escapeHtml(fallbackEmail || '—')}</span></div>
									<div><strong>Phone</strong><span>—</span></div>
								</div>
							`;
						} else {
							card.innerHTML = `
								<h5>Client Information</h5>
								<p class="lead">No client linked yet.</p>
							`;
						}
						return;
					}
					const idNum = parseInt(rawClient, 10);
					if (String(idNum) !== rawClient) {
						if (clientEmailField && !clientEmailField.value.trim()) {
							clientEmailField.value = rawClient;
							if (clientNameField && !clientNameField.value.trim() && isMooresEmail(rawClient)) {
								clientNameField.value = autoNameFromEmail(rawClient);
							}
						}
						card.innerHTML = `
							<h5>Client Information</h5>
							<p class="lead">Client ID: ${escapeHtml(rawClient)}</p>
						`;
						return;
					}
					try {
						const result = await proxyRequest('clients', 'GET', null, idNum);
						const client = result?.item || result?.client || result;
						if (!client) throw new Error('Client not found');
						if (clientNameField) clientNameField.value = client.name || '';
						if (clientEmailField) clientEmailField.value = client.email || '';
						card.innerHTML = `
							<h5>Client Information</h5>
							<div class="mc-client-grid">
								<div><strong>Name</strong><span>${escapeHtml(client.name || '—')}</span></div>
								<div><strong>Email</strong><span>${escapeHtml(client.email || '—')}</span></div>
								<div><strong>Phone</strong><span>${escapeHtml(client.phone || '—')}</span></div>
							</div>
						`;
					} catch (err) {
						card.innerHTML = `
							<h5>Client Information</h5>
							<p class="lead">Client details unavailable.</p>
						`;
					}
				};

				const loadThread = async () => {
					const thread = host.querySelector('[data-job-thread]');
					const mediaGrid = host.querySelector('[data-job-media]');
					try {
						const messagesData = await proxyRequest('messages', 'GET', null, null, { thread_id: threadId, per_page: 200 });
						const items = Array.isArray(messagesData?.items) ? messagesData.items.slice().reverse() : [];
						if (!items.length) {
							thread.innerHTML = '<p class="lead">No messages yet. Start the conversation.</p>';
						} else {
							thread.innerHTML = items.map((msg) => {
								const body = msg.body || '';
								const url = firstUrl(body);
								const media = url && isImageUrl(url)
									? `<div class="mc-chat-media"><img src="${escapeHtml(url)}" alt="attachment"></div>`
									: '';
								return `
									<div class="mc-chat-message ${msg.sender_type === 'client' ? 'is-client' : 'is-staff'}">
										<div class="mc-chat-meta">
											<span>${escapeHtml(msg.sender_type || 'staff')}</span>
											<span>${escapeHtml(formatTimestamp(msg.created_at))}</span>
										</div>
										<div class="mc-chat-body">${formatMessageBody(body)}</div>
										${media}
									</div>
								`;
							}).join('');
						}
					} catch (err) {
						thread.innerHTML = `<p class="lead">Unable to load messages. ${escapeHtml(err.message || '')}</p>`;
					}

					try {
						const jobId = String(job.id);
						const jobTag = `job:${jobId}`;
						let mediaItems = [];
						const tagged = await proxyRequest('gallery', 'GET', null, null, { per_page: 200, tag: jobTag });
						mediaItems = Array.isArray(tagged?.items) ? tagged.items : (Array.isArray(tagged?.results) ? tagged.results : []);
						if (!mediaItems.length) {
							const mediaData = await proxyRequest('gallery', 'GET', null, null, { per_page: 200 });
							mediaItems = Array.isArray(mediaData?.items) ? mediaData.items : (Array.isArray(mediaData?.results) ? mediaData.results : []);
						}
						mediaItems = mediaItems.filter((item) => {
							const galleryJobId = extractGalleryJobId(item);
							if (galleryJobId && galleryJobId === jobId) return true;
							const tags = extractGalleryTags(item);
							return tags.includes(jobTag);
						});
						if (!mediaItems.length) {
							mediaGrid.innerHTML = '<p class="lead">No media linked yet.</p>';
						} else {
							mediaGrid.innerHTML = mediaItems.map((item) => {
								const raw = item.media_url || item.thumbnail_url || item.image_path || item.file_path || '';
								const src = resolveMediaUrl(raw);
								const mediaId = item.id || item.media_id || item.image_id || '';
								if (!src) return '';
								return `
									<div class="mc-job-media-item">
										<a href="${escapeHtml(src)}" target="_blank" rel="noopener">
											<img src="${escapeHtml(src)}" alt="${escapeHtml(item.caption || 'Job media')}">
										</a>
										${mediaId ? `<button class="mc-job-media-remove" type="button" data-media-remove="${escapeHtml(mediaId)}">Remove</button>` : ''}
									</div>
								`;
							}).join('');
							mediaGrid.querySelectorAll('[data-media-remove]').forEach((button) => {
								button.addEventListener('click', async (event) => {
									event.preventDefault();
									event.stopPropagation();
									const mediaId = button.getAttribute('data-media-remove');
									if (!mediaId) return;
									if (!confirm('Remove this media from the job?')) return;
									try {
										await sendEndpoint('gallery', 'DELETE', null, mediaId);
										loadThread();
									} catch (err) {
										alert(`Unable to remove media: ${err.message || 'Request failed'}`);
									}
								});
							});
						}
					} catch (err) {
						mediaGrid.innerHTML = `<p class="lead">Unable to load media. ${escapeHtml(err.message || '')}</p>`;
					}
				};

				host.querySelector('[data-job-refresh]').addEventListener('click', loadThread);
				host.querySelector('[data-job-save]').addEventListener('click', async () => {
					const title = host.querySelector('[data-job-title]').value || '';
					const clientName = host.querySelector('[data-job-client-name]').value || '';
					const clientEmail = host.querySelector('[data-job-client-email]').value || '';
					const resolvedClient = await resolveClientRecord(clientName, clientEmail);
					const status = host.querySelector('[data-job-status]').value || 'new';
					const startDate = host.querySelector('[data-job-start]').value || '';
					const dueDate = host.querySelector('[data-job-due]').value || null;
					const estimated = host.querySelector('[data-job-estimated]').value;
					const actual = host.querySelector('[data-job-actual]').value;
					const notes = host.querySelector('[data-job-notes]').value || '';
					const description = host.querySelector('[data-job-description]').value || '';
					const folderValue = (host.querySelector('[data-job-folder]').value || '').trim();
					const services = getSelectedServices(servicesContainerDetail);
					const sales = (host.querySelector('[data-job-sales]').value || '').trim();
					const lead = (host.querySelector('[data-job-lead]').value || '').trim();
					const techs = host.querySelector('[data-job-techs]').value || '';
					const effectiveClientName = resolvedClient?.name || clientName;
					const effectiveClientEmail = resolvedClient?.email || clientEmail;
					const useClientTags = !resolvedClient && (effectiveClientName.trim() || effectiveClientEmail.trim());
					const tags = composeJobTags(job.tags, {
						folder: folderValue,
						services,
						sales,
						lead,
						techs,
						start: startDate,
						clientName: useClientTags ? effectiveClientName : '',
						clientEmail: useClientTags ? effectiveClientEmail : '',
					});
					let clientIdPayload = resolvedClient?.id || null;
					if (!clientIdPayload) {
						if (useClientTags) {
							clientIdPayload = null;
						} else {
							clientIdPayload = job.client_id || null;
						}
					}
					const payload = {
						title,
						status,
						description: description || null,
						due_date: dueDate || null,
						estimated_hours: estimated ? parseFloat(estimated) : null,
						actual_hours: actual ? parseFloat(actual) : null,
						notes,
						tags: tags.length ? tags : null,
					};
					if (clientIdPayload !== undefined) {
						payload.client_id = clientIdPayload;
					}
					await sendEndpoint('jobs', 'PUT', payload, job.id);
					renderManager('jobs', await fetchEndpoint('jobs'));
				});
				host.querySelector('[data-job-delete]').addEventListener('click', async () => {
					if (!confirm('Delete this job? This cannot be undone.')) return;
					await sendEndpoint('jobs', 'DELETE', null, job.id);
					closeJobModal();
					renderManager('jobs', await fetchEndpoint('jobs'));
				});

				const composeForm = host.querySelector('[data-job-compose]');
				composeForm.addEventListener('submit', async (event) => {
					event.preventDefault();
					const messageField = composeForm.querySelector('textarea[name="message"]');
					const body = messageField.value.trim();
					if (!body) return;
					await proxyRequest('messages', 'POST', {
						thread_id: threadId,
						body,
						sender_id: authPacket?.email || authPacket?.user?.email || '',
						sender_type: 'staff'
					});
					messageField.value = '';
					loadThread();
				});

				const uploadButton = host.querySelector('[data-job-upload]');
				uploadButton.addEventListener('click', async () => {
					const fileInput = host.querySelector('[data-job-file]');
					const captionInput = host.querySelector('[data-job-caption]');
					const file = fileInput.files?.[0];
					if (!file) return;
					const base64 = await fileToBase64(file);
					const jobTag = `job:${job.id}`;
					const payload = {
						file_b64: base64,
						filename: file.name,
						item_type: file.type && file.type.startsWith('video/') ? 'video' : 'image',
						caption: captionInput.value || null,
						job_id: String(job.id),
						tag: jobTag,
						tags: [jobTag]
					};
					const upload = await proxyRequest('gallery', 'POST', payload);
					const mediaUrl = resolveMediaUrl(upload?.media_url || upload?.item?.media_url || '');
					if (mediaUrl) {
						const note = captionInput.value ? `Media uploaded: ${captionInput.value}\n${mediaUrl}` : `Media uploaded: ${mediaUrl}`;
						await proxyRequest('messages', 'POST', {
							thread_id: threadId,
							body: note,
							sender_id: authPacket?.email || authPacket?.user?.email || '',
							sender_type: 'staff'
						});
					}
					fileInput.value = '';
					captionInput.value = '';
					loadThread();
				});

				loadThread();
				loadClientInfo();
			};

			const showJobDetail = (job) => {
				if (!jobModalBody) return;
				openJobModal();
				jobModalBody.innerHTML = '';
				renderJobDetail(job, jobModalBody);
			};

			const renderFeaturedJobsPanel = async () => {
				if (!managerList) return;
				const panel = document.createElement('div');
				panel.className = 'mc-manager-item mc-featured-jobs';
				panel.id = 'mc-featured-jobs-panel';
				panel.innerHTML = `
					<div class="mc-manager-item-header">
						<div>
							<div class="mc-manager-item-title">Featured Jobs</div>
							<div class="mc-manager-item-sub">Toggle which jobs should appear as featured on the homepage.</div>
						</div>
						<div class="mc-chip">Homepage</div>
					</div>
					<div class="mc-featured-jobs-body" data-featured-jobs-body>
						<p class="lead">Loading job media…</p>
					</div>
				`;
				managerList.appendChild(panel);

				const body = panel.querySelector('[data-featured-jobs-body]');
				if (!body) return;
				if (!items.length) {
					body.innerHTML = '<p class="lead">No jobs yet. Create a job first.</p>';
					return;
				}

				let galleryItems = [];
				try {
					const galleryData = await proxyRequest('gallery', 'GET', null, null, { per_page: 300 });
					galleryItems = Array.isArray(galleryData?.items)
						? galleryData.items
						: (Array.isArray(galleryData?.results) ? galleryData.results : (Array.isArray(galleryData?.gallery) ? galleryData.gallery : []));
				} catch (err) {
					body.innerHTML = `<p class="lead">Unable to load gallery items. ${escapeHtml(err?.message || '')}</p>`;
					return;
				}

				const jobMediaMap = new Map();
				galleryItems.forEach((item) => {
					const jobId = extractGalleryJobId(item);
					if (!jobId) return;
					const meta = parseMetadata(item.metadata);
					const tags = extractGalleryTags(item);
					const tagFeatured = tags.some((tag) => /job[_-]?featured/i.test(String(tag || '')));
					const isJobFeatured = !!(meta?.job_featured || meta?.jobFeatured || tagFeatured);
					if (!jobMediaMap.has(jobId)) {
						jobMediaMap.set(jobId, { items: [], featured: false });
					}
					const record = jobMediaMap.get(jobId);
					record.items.push(item);
					if (isJobFeatured) record.featured = true;
				});

				const sortedJobs = items.slice().sort((a, b) => compareSortValue(a.id ?? '', b.id ?? ''));
				const list = document.createElement('div');
				list.className = 'mc-featured-jobs-list';

				const updateJobFeatured = async (jobId, enabled, jobItems) => {
					if (!jobItems?.length) return;
					const updates = jobItems.map((item) => {
						const meta = parseMetadata(item.metadata);
						const part = extractGalleryPart(item);
						const process = extractGalleryProcess(item);
						const isFinal = isFinalProcess(process, meta);
						const payload = {
							metadata: buildGalleryMetadata(meta, {
								job_id: jobId,
								part,
								process,
								is_final: isFinal,
								job_featured: enabled,
							})
						};
						if (jobId) payload.job_id = jobId;
						return sendEndpoint('gallery', 'PUT', payload, item.id);
					});
					await Promise.all(updates);
				};

				sortedJobs.forEach((job) => {
					const jobId = String(job.id ?? '').trim();
					const record = jobMediaMap.get(jobId);
					const mediaCount = record?.items?.length || 0;
					const featured = !!record?.featured;
					const row = document.createElement('div');
					row.className = 'mc-featured-job';
					row.innerHTML = `
						<div>
							<div class="mc-featured-job-title">${escapeHtml(job.title || `Job #${jobId}`)}</div>
							<div class="mc-featured-job-sub">${mediaCount ? `${mediaCount} media items` : 'No gallery media yet'}</div>
						</div>
						<label class="mc-toggle">
							<input type="checkbox" ${featured ? 'checked' : ''} ${mediaCount ? '' : 'disabled'}>
							<span></span>
						</label>
					`;
					const toggle = row.querySelector('input[type="checkbox"]');
					if (toggle) {
						toggle.addEventListener('change', async () => {
							if (!jobId || !record?.items?.length) return;
							const next = toggle.checked;
							toggle.disabled = true;
							row.classList.add('is-updating');
							try {
								await updateJobFeatured(jobId, next, record.items);
								record.featured = next;
							} catch (err) {
								toggle.checked = !next;
								alert(`Unable to update featured job: ${err?.message || 'Request failed'}`);
							} finally {
								toggle.disabled = false;
								row.classList.remove('is-updating');
							}
						});
					}
					list.appendChild(row);
				});

				body.innerHTML = '';
				body.appendChild(list);
			};

			renderFeaturedJobsPanel();

			if (!items.length) {
				showEmptyState('No active jobs yet', 'Create the first job to start tracking build progress.');
				return;
			}

			const normalizeStatus = (value) => String(value || 'new').trim().toLowerCase();
			const formatStatusLabel = (value) => String(value || 'new').replace(/_/g, ' ');
			const toTimestamp = (value, fallback = Number.POSITIVE_INFINITY) => {
				if (!value) return fallback;
				const dt = new Date(value);
				const ts = dt.getTime();
				return Number.isFinite(ts) ? ts : fallback;
			};

			const statusCounts = items.reduce((acc, job) => {
				const status = normalizeStatus(job?.status);
				acc[status] = (acc[status] || 0) + 1;
				return acc;
			}, {});

			const controls = document.createElement('div');
			controls.className = 'mc-manager-item';
			controls.innerHTML = `
				<div class="mc-manager-item-header">
					<div>
						<div class="mc-manager-item-title">Jobs Queue</div>
						<div class="mc-manager-item-sub" data-jobs-summary>${items.length} total jobs</div>
					</div>
					<div class="mc-chip">Control</div>
				</div>
				<div class="mc-manager-item-grid">
					<label class="mc-inline-field mc-inline-field-wide">
						<span>Search</span>
						<input type="text" data-jobs-search placeholder="Search by title, client, tech, service, or job #">
					</label>
					<label class="mc-inline-field">
						<span>Status</span>
						<select data-jobs-filter-status>
							<option value="all">All (${items.length})</option>
							<option value="new">New (${statusCounts.new || 0})</option>
							<option value="in_progress">In Progress (${statusCounts.in_progress || 0})</option>
							<option value="on_hold">On Hold (${statusCounts.on_hold || 0})</option>
							<option value="awaiting_client">Awaiting Client (${statusCounts.awaiting_client || 0})</option>
							<option value="done">Done (${statusCounts.done || 0})</option>
							<option value="invoiced">Invoiced (${statusCounts.invoiced || 0})</option>
						</select>
					</label>
					<label class="mc-inline-field">
						<span>Sort</span>
						<select data-jobs-sort>
							<option value="priority">Priority (status + due soon)</option>
							<option value="due_soon">Due date (soonest)</option>
							<option value="progress_desc">Progress (highest first)</option>
							<option value="updated_desc">Recently updated</option>
							<option value="title_asc">Title (A-Z)</option>
							<option value="id_desc">Job ID (newest)</option>
						</select>
					</label>
				</div>
			`;
			managerList.appendChild(controls);

			const searchInput = controls.querySelector('[data-jobs-search]');
			const statusFilterSelect = controls.querySelector('[data-jobs-filter-status]');
			const sortSelect = controls.querySelector('[data-jobs-sort]');
			const summary = controls.querySelector('[data-jobs-summary]');

			const list = document.createElement('div');
			list.className = 'mc-manager-list mc-job-panel-grid';
			managerList.appendChild(list);

			const state = {
				query: '',
				status: 'all',
				sort: 'priority',
			};

			const progressForJob = (job) => {
				const est = parseFloat(job?.estimated_hours || 0);
				const act = parseFloat(job?.actual_hours || 0);
				const ratio = est > 0 ? Math.min(act / est, 1) : (normalizeStatus(job?.status) === 'done' ? 1 : 0);
				return {
					est,
					act,
					pct: Math.round(ratio * 100),
				};
			};

			const statusPriority = (status) => {
				switch (normalizeStatus(status)) {
					case 'in_progress': return 0;
					case 'awaiting_client': return 1;
					case 'on_hold': return 2;
					case 'new': return 3;
					case 'done': return 4;
					case 'invoiced': return 5;
					default: return 6;
				}
			};

			const sortJobs = (source) => {
				const rows = source.slice();
				if (state.sort === 'title_asc') {
					rows.sort((a, b) => String(a?.title || '').localeCompare(String(b?.title || '')));
					return rows;
				}
				if (state.sort === 'id_desc') {
					rows.sort((a, b) => compareSortValue(b?.id ?? '', a?.id ?? ''));
					return rows;
				}
				if (state.sort === 'updated_desc') {
					rows.sort((a, b) => {
						const aTs = toTimestamp(a?.updated_at || a?.created_at, 0);
						const bTs = toTimestamp(b?.updated_at || b?.created_at, 0);
						if (aTs !== bTs) return bTs - aTs;
						return compareSortValue(b?.id ?? '', a?.id ?? '');
					});
					return rows;
				}
				if (state.sort === 'progress_desc') {
					rows.sort((a, b) => {
						const ap = progressForJob(a).pct;
						const bp = progressForJob(b).pct;
						if (ap !== bp) return bp - ap;
						return compareSortValue(a?.id ?? '', b?.id ?? '');
					});
					return rows;
				}
				if (state.sort === 'due_soon') {
					rows.sort((a, b) => {
						const ad = toTimestamp(a?.due_date);
						const bd = toTimestamp(b?.due_date);
						if (ad !== bd) return ad - bd;
						return compareSortValue(a?.id ?? '', b?.id ?? '');
					});
					return rows;
				}
				rows.sort((a, b) => {
					const pri = statusPriority(a?.status) - statusPriority(b?.status);
					if (pri !== 0) return pri;
					const ad = toTimestamp(a?.due_date);
					const bd = toTimestamp(b?.due_date);
					if (ad !== bd) return ad - bd;
					return compareSortValue(a?.id ?? '', b?.id ?? '');
				});
				return rows;
			};

			const renderCards = (jobsToRender) => {
				list.innerHTML = '';
				if (!jobsToRender.length) {
					const empty = document.createElement('div');
					empty.className = 'mc-manager-item';
					empty.innerHTML = `
						<div class="mc-manager-item-header">
							<div>
								<div class="mc-manager-item-title">No jobs match this view</div>
								<div class="mc-manager-item-sub">Try clearing search or changing filters.</div>
							</div>
						</div>
					`;
					list.appendChild(empty);
					return;
				}

				jobsToRender.forEach((job) => {
					const title = job.title || 'Untitled Job';
					const status = normalizeStatus(job.status);
					const services = collectPrefixed(job.tags, 'service:');
					const techs = collectPrefixed(job.tags, 'tech:');
					const clientTagName = extractJobClientName(job.tags);
					const clientTagEmail = extractJobClientEmail(job.tags);
					const metaParts = [];
					if (job.client_id) {
						metaParts.push(`Client ${job.client_id}`);
					} else if (clientTagName || clientTagEmail) {
						metaParts.push(`Client ${clientTagName || clientTagEmail}`);
					}
					if (job.due_date) metaParts.push(`Due ${toDateInput(job.due_date)}`);
					if (job.estimated_hours) metaParts.push(`${job.estimated_hours}h est`);
					const meta = metaParts.length ? metaParts.join(' · ') : 'No extra details yet';
					const descriptionRaw = job.description || job.summary || '';
					const descriptionText = String(descriptionRaw).replace(/\s+/g, ' ').trim();
					const descriptionShort = descriptionText.length > 140 ? `${descriptionText.slice(0, 140)}…` : descriptionText;
					const progressData = progressForJob(job);
					const row = document.createElement('div');
					row.className = 'mc-job-panel';
					row.innerHTML = `
						<div class="mc-job-panel-header">
							<div>
								<div class="mc-job-panel-title">${escapeHtml(title)}</div>
								<div class="mc-job-panel-sub">Job #${escapeHtml(job.id ?? '')} · ${escapeHtml(meta)}</div>
							</div>
							<div class="mc-chip mc-chip-${escapeHtml(status)}">${escapeHtml(formatStatusLabel(status))}</div>
						</div>
						<div class="mc-job-panel-progress">
							<div class="mc-job-progress-bar" style="width:${progressData.pct}%"></div>
						</div>
						<div class="mc-job-panel-meta">
							<span>${progressData.pct}% complete</span>
							<span>${escapeHtml(progressData.act || 0)}h / ${escapeHtml(progressData.est || 0)}h</span>
						</div>
						${descriptionShort ? `<div class="mc-job-panel-desc">${escapeHtml(descriptionShort)}</div>` : ''}
						<div class="mc-job-panel-tags">
							${services.length ? services.map((svc) => `<span class="mc-pill">${escapeHtml(svc)}</span>`).join('') : '<span class="mc-muted">No services tagged</span>'}
						</div>
						<div class="mc-job-panel-tags">
							${techs.length ? techs.map((tech) => `<span class="mc-pill mc-pill-alt">${escapeHtml(tech)}</span>`).join('') : '<span class="mc-muted">No techs assigned</span>'}
						</div>
						<div class="mc-job-panel-actions">
							<button class="btn btn-primary" type="button" data-open>Open Job</button>
						</div>
					`;
					const openBtn = row.querySelector('[data-open]');
					openBtn.addEventListener('click', () => showJobDetail(job));
					list.appendChild(row);
				});
			};

			const applyFilters = () => {
				const query = state.query.trim().toLowerCase();
				let filtered = items.filter((job) => {
					const status = normalizeStatus(job?.status);
					if (state.status !== 'all' && status !== state.status) return false;
					if (!query) return true;
					const services = collectPrefixed(job?.tags, 'service:').join(' ');
					const techs = collectPrefixed(job?.tags, 'tech:').join(' ');
					const clientName = extractJobClientName(job?.tags);
					const clientEmail = extractJobClientEmail(job?.tags);
					const haystack = [
						job?.id,
						job?.title,
						job?.description,
						job?.summary,
						job?.notes,
						job?.client_id,
						clientName,
						clientEmail,
						services,
						techs,
					].map((v) => String(v || '').toLowerCase()).join(' ');
					return haystack.includes(query);
				});

				filtered = sortJobs(filtered);
				if (summary) {
					summary.textContent = `${filtered.length} of ${items.length} jobs shown`;
				}
				renderCards(filtered);
			};

			if (searchInput) {
				searchInput.addEventListener('input', () => {
					state.query = searchInput.value || '';
					applyFilters();
				});
			}
			if (statusFilterSelect) {
				statusFilterSelect.addEventListener('change', () => {
					state.status = statusFilterSelect.value || 'all';
					applyFilters();
				});
			}
			if (sortSelect) {
				sortSelect.addEventListener('change', () => {
					state.sort = sortSelect.value || 'priority';
					applyFilters();
				});
			}

			applyFilters();

			if (focusCreate) openFormModal();
		};

		const renderServices = (items, focusCreate) => {
			setFormHeader('Add Service', "Update what's offered with badges, pricing cues, and descriptions.");
			managerForm.innerHTML = `
				<div class="mc-manager-form-card">
					<div class="mc-manager-form-head">
						<h4>Add Service</h4>
						<p>Update what Moore's offers with badges, pricing cues, and descriptions.</p>
					</div>
					<form data-manager-form="services">
					<div class="mc-manager-grid">
						<label class="mc-manager-field">
							<span>Title</span>
							<input type="text" name="title" placeholder="Ceramic Coating" required>
						</label>
						<label class="mc-manager-field">
							<span>Badge</span>
							<input type="text" name="badge" placeholder="Popular / New / Premium">
						</label>
						<label class="mc-manager-field">
							<span>Starting Price</span>
							<input type="text" name="starting_price" placeholder="$1,250">
						</label>
						<label class="mc-manager-field mc-manager-field-wide">
							<span>Description</span>
							<textarea name="description" rows="2" placeholder="Short overview for the homepage"></textarea>
						</label>
					</div>
					<button class="btn btn-primary" type="submit">Add Service</button>
					</form>
				</div>
			`;
			const form = managerForm.querySelector('form');
			form.addEventListener('submit', async (event) => {
				event.preventDefault();
				const data = Object.fromEntries(new FormData(form).entries());
				await sendEndpoint('services', 'POST', data);
				renderManager('services', await fetchEndpoint('services'));
			});

			const list = document.createElement('div');
			list.className = 'mc-manager-list';
			if (!items.length) {
				showEmptyState('No services published', 'Add the first service to show on the homepage.');
				return;
			}
			items.forEach((svc) => {
				const row = document.createElement('div');
				row.className = 'mc-manager-item';
				row.innerHTML = `
					<div class="mc-manager-item-header">
						<div>
							<div class="mc-manager-item-title">${escapeHtml(svc.title || 'Service')}</div>
							<div class="mc-manager-item-sub">${escapeHtml(svc.starting_price || svc.badge || 'Service')}</div>
						</div>
						<div class="mc-chip">Service</div>
					</div>
					<div class="mc-manager-item-grid">
						<label class="mc-inline-field">
							<span>Title</span>
							<input type="text" value="${escapeHtml(svc.title || '')}" data-field="title">
						</label>
						<label class="mc-inline-field">
							<span>Badge</span>
							<input type="text" value="${escapeHtml(svc.badge || '')}" data-field="badge" placeholder="Badge">
						</label>
						<label class="mc-inline-field">
							<span>Starting Price</span>
							<input type="text" value="${escapeHtml(svc.starting_price || '')}" data-field="starting_price" placeholder="$1,250">
						</label>
						<label class="mc-inline-field mc-inline-field-wide">
							<span>Description</span>
							<textarea rows="2" data-field="description">${escapeHtml(svc.description || '')}</textarea>
						</label>
					</div>
					<div class="mc-manager-item-actions">
						<button class="btn btn-primary" type="button" data-save>Save</button>
						<button class="btn btn-outline" type="button" data-delete>Delete</button>
					</div>
				`;
				row.querySelector('[data-save]').addEventListener('click', async () => {
					const payload = {};
					row.querySelectorAll('[data-field]').forEach((field) => {
						payload[field.dataset.field] = field.value;
					});
					await sendEndpoint('services', 'PUT', payload, svc.id);
				});
				row.querySelector('[data-delete]').addEventListener('click', async () => {
					await sendEndpoint('services', 'DELETE', null, svc.id);
					renderManager('services', await fetchEndpoint('services'));
				});
				list.appendChild(row);
			});
			managerList.appendChild(list);
			if (focusCreate) openFormModal();
		};

		const renderPartners = (items, focusCreate) => {
			setFormHeader('Add Partner', 'Trusted vendors, fabricators, and specialists for Moore’s builds.');
			managerForm.innerHTML = `
				<div class="mc-manager-form-card">
					<div class="mc-manager-form-head">
						<h4>Add Partner</h4>
						<p>Trusted vendors, fabricators, and specialists for Moore's builds.</p>
					</div>
					<form data-manager-form="partners">
					<div class="mc-manager-grid">
						<label class="mc-manager-field">
							<span>Business Name</span>
							<input type="text" name="business_name" placeholder="Top Dog Performance" required>
						</label>
						<label class="mc-manager-field">
							<span>Shop Type</span>
							<input type="text" name="shop_type" placeholder="Fabrication / Gunsmith / Paint">
						</label>
						<label class="mc-manager-field">
							<span>Contact Name</span>
							<input type="text" name="contact_name" placeholder="Primary contact">
						</label>
						<label class="mc-manager-field">
							<span>Email</span>
							<input type="email" name="email" placeholder="shop@example.com">
						</label>
						<label class="mc-manager-field">
							<span>Phone</span>
							<input type="text" name="phone" placeholder="(555) 555-5555">
						</label>
						<label class="mc-manager-field mc-manager-field-wide">
							<span>Address</span>
							<input type="text" name="address" placeholder="Street address">
						</label>
						<label class="mc-manager-field">
							<span>City</span>
							<input type="text" name="city" placeholder="City">
						</label>
						<label class="mc-manager-field">
							<span>State</span>
							<input type="text" name="state" placeholder="State">
						</label>
						<label class="mc-manager-field">
							<span>ZIP</span>
							<input type="text" name="zip_code" placeholder="ZIP">
						</label>
						<label class="mc-manager-field">
							<span>Website</span>
							<input type="text" name="website" placeholder="https://">
						</label>
						<label class="mc-manager-field mc-manager-field-wide">
							<span>Notes</span>
							<textarea name="notes" rows="2" placeholder="Specialties, lead time, preferred contact"></textarea>
						</label>
					</div>
					<button class="btn btn-primary" type="submit">Add Partner</button>
					</form>
				</div>
			`;
			const form = managerForm.querySelector('form');
			form.addEventListener('submit', async (event) => {
				event.preventDefault();
				const data = Object.fromEntries(new FormData(form).entries());
				await sendEndpoint('partners', 'POST', data);
				renderManager('partners', await fetchEndpoint('partners'));
			});

			const list = document.createElement('div');
			list.className = 'mc-manager-list';
			if (!items.length) {
				showEmptyState('No partner shops yet', 'Add trusted partners to populate the Trusted Network section.');
				return;
			}
			items.forEach((partner) => {
				const row = document.createElement('div');
				row.className = 'mc-manager-item';
				const location = [partner.city, partner.state].filter(Boolean).join(', ');
				row.innerHTML = `
					<div class="mc-manager-item-header">
						<div>
							<div class="mc-manager-item-title">${escapeHtml(partner.business_name || 'Partner')}</div>
							<div class="mc-manager-item-sub">${escapeHtml(location || 'Location pending')}</div>
						</div>
						<div class="mc-chip">${escapeHtml(partner.shop_type || 'Partner')}</div>
					</div>
					<div class="mc-manager-item-grid">
						<label class="mc-inline-field">
							<span>Business Name</span>
							<input type="text" value="${escapeHtml(partner.business_name || '')}" data-field="business_name">
						</label>
						<label class="mc-inline-field">
							<span>Shop Type</span>
							<input type="text" value="${escapeHtml(partner.shop_type || '')}" data-field="shop_type" placeholder="Type">
						</label>
						<label class="mc-inline-field">
							<span>Contact Name</span>
							<input type="text" value="${escapeHtml(partner.contact_name || '')}" data-field="contact_name" placeholder="Contact">
						</label>
						<label class="mc-inline-field">
							<span>Email</span>
							<input type="text" value="${escapeHtml(partner.email || '')}" data-field="email" placeholder="Email">
						</label>
						<label class="mc-inline-field">
							<span>Phone</span>
							<input type="text" value="${escapeHtml(partner.phone || '')}" data-field="phone" placeholder="Phone">
						</label>
						<label class="mc-inline-field mc-inline-field-wide">
							<span>Address</span>
							<input type="text" value="${escapeHtml(partner.address || '')}" data-field="address" placeholder="Address">
						</label>
						<label class="mc-inline-field">
							<span>City</span>
							<input type="text" value="${escapeHtml(partner.city || '')}" data-field="city" placeholder="City">
						</label>
						<label class="mc-inline-field">
							<span>State</span>
							<input type="text" value="${escapeHtml(partner.state || '')}" data-field="state" placeholder="State">
						</label>
						<label class="mc-inline-field">
							<span>ZIP</span>
							<input type="text" value="${escapeHtml(partner.zip_code || '')}" data-field="zip_code" placeholder="ZIP">
						</label>
						<label class="mc-inline-field">
							<span>Website</span>
							<input type="text" value="${escapeHtml(partner.website || '')}" data-field="website" placeholder="Website">
						</label>
						<label class="mc-inline-field mc-inline-field-wide">
							<span>Notes</span>
							<textarea rows="2" data-field="notes">${escapeHtml(partner.notes || '')}</textarea>
						</label>
					</div>
					<div class="mc-manager-item-actions">
						<button class="btn btn-primary" type="button" data-save>Save</button>
						<button class="btn btn-outline" type="button" data-delete>Delete</button>
					</div>
				`;
				row.querySelector('[data-save]').addEventListener('click', async () => {
					const payload = {};
					row.querySelectorAll('[data-field]').forEach((field) => {
						payload[field.dataset.field] = field.value;
					});
					await sendEndpoint('partners', 'PUT', payload, partner.id);
				});
				row.querySelector('[data-delete]').addEventListener('click', async () => {
					await sendEndpoint('partners', 'DELETE', null, partner.id);
					renderManager('partners', await fetchEndpoint('partners'));
				});
				list.appendChild(row);
			});
			managerList.appendChild(list);
			if (focusCreate) openFormModal();
		};

		const renderGallery = (items, focusCreate) => {
			setFormHeader('Add Gallery Item', 'Point to an uploaded asset, add a caption, and mark it featured.');
			managerForm.innerHTML = `
				<div class="mc-manager-form-card">
					<div class="mc-manager-form-head">
						<h4>Add Gallery Item</h4>
						<p>Point to an uploaded asset, add a caption, and mark it featured.</p>
					</div>
					<form data-manager-form="gallery">
					<div class="mc-manager-grid">
						<label class="mc-manager-field">
							<span>Image Path</span>
							<input type="text" name="file_path" required placeholder="gallery/filename.jpg">
						</label>
						<label class="mc-manager-field">
							<span>Tag</span>
							<input type="text" name="tag" placeholder="paint / airbrush / custom">
						</label>
						<label class="mc-manager-field">
							<span>Job ID</span>
							<select name="job_id" data-gallery-job-select>
								<option value="">No job</option>
							</select>
						</label>
						<label class="mc-manager-field">
							<span>Part</span>
							<input type="text" name="part" placeholder="Hood / Helmet / Tank">
						</label>
						<label class="mc-manager-field">
							<span>Process / Stage</span>
							<input type="text" name="process" placeholder="prep / base / art / clear / final">
						</label>
						<label class="mc-manager-field mc-manager-field-inline">
							<span>Final Image</span>
							<label class="mc-toggle">
								<input type="checkbox" name="is_final" value="1">
								<span></span>
							</label>
						</label>
						<label class="mc-manager-field mc-manager-field-wide">
							<span>Caption</span>
							<textarea name="caption" rows="2" placeholder="Description shown on the homepage"></textarea>
						</label>
						<label class="mc-manager-field">
							<span>Featured</span>
							<select name="is_featured">
								<option value="0">No</option>
								<option value="1">Yes</option>
							</select>
						</label>
					</div>
					<button class="btn btn-primary" type="submit">Add Gallery Item</button>
				</form>
			`;
			const form = managerForm.querySelector('form');
			const jobSelect = form.querySelector('[data-gallery-job-select]');
			const applyJobSelect = async (select, selectedValue) => {
				if (typeof populateJobSelect !== 'function') return;
				await populateJobSelect(select, selectedValue);
			};
			if (jobSelect) {
				applyJobSelect(jobSelect, '');
			}
			form.addEventListener('submit', async (event) => {
				event.preventDefault();
				const formData = new FormData(form);
				const data = Object.fromEntries(formData.entries());
				const isFinal = formData.get('is_final') === '1';
				const jobId = String(data.job_id || '').trim();
				const part = String(data.part || '').trim();
				const process = String(data.process || '').trim();
				const payload = {
					file_path: data.file_path,
					tag: data.tag || '',
					caption: data.caption || '',
					is_featured: data.is_featured === '1',
					job_id: jobId || undefined,
					metadata: buildGalleryMetadata({}, {
						job_id: jobId,
						part,
						process,
						is_final: isFinal,
					})
				};
				await sendEndpoint('gallery', 'POST', payload);
				renderManager('gallery', await fetchEndpoint('gallery', { nocache: true }));
			});

			const list = document.createElement('div');
			list.className = 'mc-manager-list';
			if (!items.length) {
				showEmptyState('No gallery items yet', 'Upload a piece and it will appear here for curation.');
				return;
			}
			const orderedItems = sortGalleryItems(items);
			orderedItems.forEach((item) => {
				const row = document.createElement('div');
				row.className = 'mc-manager-item';
				const meta = parseMetadata(item.metadata);
				const jobId = extractGalleryJobId(item);
				const part = extractGalleryPart(item);
				const process = extractGalleryProcess(item);
				const isFinal = isFinalProcess(process, meta);
				const preview = item.thumbnail_url || item.media_url || item.image_path || item.thumbnail_path || item.cover_image || item.file_path || '';
				const previewUrl = preview ? (preview.startsWith('http') ? preview : `${apiBase}/${preview.replace(/^\/+/, '')}`) : '';
				const metaPieces = [];
				if (jobId) metaPieces.push(`Job ${jobId}`);
				if (part) metaPieces.push(`Part ${part}`);
				if (process) metaPieces.push(process);
				const metaLine = metaPieces.length ? metaPieces.join(' • ') : (item.tag || 'Uncategorized');
				row.innerHTML = `
					<div class="mc-manager-item-header">
						<div>
							<div class="mc-manager-item-title">${escapeHtml(item.caption || 'Gallery Item')}</div>
							<div class="mc-manager-item-sub">${escapeHtml(metaLine)}</div>
						</div>
						${item.is_featured ? '<div class="mc-chip">Featured</div>' : ''}
					</div>
					<div class="mc-manager-item-media">
						${previewUrl ? `<img src="${previewUrl}" alt="Gallery preview">` : '<div class="mc-manager-media-placeholder">No preview</div>'}
					</div>
					<div class="mc-manager-item-grid">
						<label class="mc-inline-field mc-inline-field-wide">
							<span>Caption</span>
							<input type="text" value="${escapeHtml(item.caption || '')}" data-field="caption" placeholder="Caption">
						</label>
						<label class="mc-inline-field">
							<span>Tag</span>
							<input type="text" value="${escapeHtml(item.tag || '')}" data-field="tag" placeholder="Tag">
						</label>
						<label class="mc-inline-field">
							<span>Job ID</span>
							<select data-field="job_id" data-job-select>
								<option value="">No job</option>
							</select>
						</label>
						<label class="mc-inline-field">
							<span>Part</span>
							<input type="text" value="${escapeHtml(part || '')}" data-field="part" placeholder="Part">
						</label>
						<label class="mc-inline-field">
							<span>Process</span>
							<input type="text" value="${escapeHtml(process || '')}" data-field="process" placeholder="Process">
						</label>
						<label class="mc-inline-field mc-inline-field-inline">
							<span>Final</span>
							<label class="mc-toggle">
								<input type="checkbox" data-field="is_final" ${isFinal ? 'checked' : ''}>
								<span></span>
							</label>
						</label>
						<label class="mc-inline-field mc-inline-field-inline">
							<span>Featured</span>
							<label class="mc-toggle">
								<input type="checkbox" data-field="is_featured" ${item.is_featured ? 'checked' : ''}>
								<span></span>
							</label>
						</label>
					</div>
					<div class="mc-manager-item-actions">
						<button class="btn btn-primary" type="button" data-save>Save</button>
						<button class="btn btn-outline" type="button" data-delete>Delete</button>
					</div>
				`;
				row.querySelector('[data-save]').addEventListener('click', async () => {
					const payload = {};
					row.querySelectorAll('[data-field]').forEach((field) => {
						if (field.type === 'checkbox') {
							payload[field.dataset.field] = field.checked;
						} else {
							payload[field.dataset.field] = field.value;
						}
					});
					const jobValue = String(payload.job_id || '').trim();
					const partValue = String(payload.part || '').trim();
					const processValue = String(payload.process || '').trim();
					const isFinalValue = !!payload.is_final;
					payload.metadata = buildGalleryMetadata(item.metadata, {
						job_id: jobValue,
						part: partValue,
						process: processValue,
						is_final: isFinalValue,
					});
					if (jobValue) payload.job_id = jobValue;
					delete payload.part;
					delete payload.process;
					delete payload.is_final;
					await sendEndpoint('gallery', 'PUT', payload, item.id);
					renderManager('gallery', await fetchEndpoint('gallery', { nocache: true }));
				});
				row.querySelector('[data-delete]').addEventListener('click', async () => {
					await sendEndpoint('gallery', 'DELETE', null, item.id);
					renderManager('gallery', await fetchEndpoint('gallery', { nocache: true }));
				});
				applyJobSelect(row.querySelector('[data-job-select]'), jobId);
				list.appendChild(row);
			});
			managerList.appendChild(list);
			if (focusCreate) openFormModal();
		};

		const renderStaff = (items, focusCreate) => {
			setFormHeader('Add Staff', 'Add a staff member by email and assign their role.');
			managerForm.innerHTML = `
				<div class="mc-manager-form-card">
					<div class="mc-manager-form-head">
						<h4>Add Staff Badge</h4>
						<p>Add a staff member by email and assign their role.</p>
					</div>
					<form data-manager-form="staff">
						<div class="mc-manager-grid">
							<label class="mc-manager-field">
								<span>Name</span>
								<input type="text" name="name" placeholder="Full name">
							</label>
							<label class="mc-manager-field">
								<span>Email</span>
								<input type="email" name="email" placeholder="name@example.com" required>
							</label>
							<label class="mc-manager-field">
								<span>Role</span>
								<select name="role">
									<option value="owner">Owner</option>
									<option value="admin">Admin</option>
									<option value="manager">Manager</option>
									<option value="staff" selected>Staff</option>
									<option value="viewer">Viewer</option>
								</select>
							</label>
							<label class="mc-manager-field mc-manager-field-wide">
								<span>Permissions / Badges</span>
								<input type="text" name="permissions" placeholder="comma separated (staff, owner)">
							</label>
						</div>
						<button class="btn btn-primary" type="submit">Add Staff</button>
					</form>
				</div>
			`;
			const form = managerForm.querySelector('form');
			const findExistingStaffByEmail = async (email) => {
				const cleaned = (email || '').trim().toLowerCase();
				if (!cleaned) return null;
				try {
					const data = await fetchStaffList({ nocache: true });
					const items = Array.isArray(data?.items) ? data.items : (Array.isArray(data?.results) ? data.results : []);
					return items.find((member) => (member.email || '').toLowerCase() === cleaned) || null;
				} catch (e) {
					return null;
				}
			};

			form.addEventListener('submit', async (event) => {
				event.preventDefault();
				const data = Object.fromEntries(new FormData(form).entries());
				const email = (data.email || '').trim();
				if (!email) {
					showManagerMessage('Missing Email', 'Enter an email address to add a staff member.');
					return;
				}
				const name = (data.name || '').trim() || autoNameFromEmail(email);
				const perms = (data.permissions || '').split(',').map((p) => p.trim()).filter(Boolean);
				const payload = { name, email, role: data.role || 'staff' };
				if (perms.length) payload.permissions = perms;
				try {
					const existing = await findExistingStaffByEmail(email);
					if (existing?.id) {
						const updatePayload = {
							name: name || existing.name || autoNameFromEmail(email),
							email,
							role: data.role || existing.role || 'staff',
							active: 1,
						};
						if (perms.length) updatePayload.permissions = perms;
						await sendEndpoint('staff', 'PUT', updatePayload, existing.id);
						renderManager('staff', await fetchStaffList({ nocache: true }));
						return;
					}
					await sendEndpoint('staff', 'POST', payload);
					renderManager('staff', await fetchStaffList({ nocache: true }));
				} catch (err) {
					const message = err?.message || 'Unable to add staff.';
					if (message.toLowerCase().includes('unique') || message.toLowerCase().includes('exists')) {
						try {
							const existing = await findExistingStaffByEmail(email);
							if (existing?.id) {
								const updatePayload = {
									name: name || existing.name || autoNameFromEmail(email),
									email,
									role: data.role || existing.role || 'staff',
									active: 1,
								};
								if (perms.length) updatePayload.permissions = perms;
								await sendEndpoint('staff', 'PUT', updatePayload, existing.id);
								renderManager('staff', await fetchStaffList({ nocache: true }));
								return;
							}
						} catch (e) {}
					}
					showManagerMessage('Add Staff Failed', message);
				}
			});

			const list = document.createElement('div');
			list.className = 'mc-manager-list';
			if (!items.length) {
				showEmptyState('No staff yet', 'Add an employee badge to grant access.');
				return;
			}
			items.forEach((member) => {
				const perms = Array.isArray(member.permissions) ? member.permissions.join(', ') : (member.permissions || '');
				const active = member.active ? 'active' : 'inactive';
				const row = document.createElement('div');
				row.className = 'mc-manager-item';
				row.innerHTML = `
					<div class="mc-manager-item-header">
						<div>
							<div class="mc-manager-item-title">${escapeHtml(member.name || 'Staff')}</div>
							<div class="mc-manager-item-sub">${escapeHtml(member.email || '')}</div>
						</div>
						<div class="mc-chip mc-chip-${escapeHtml(active)}">${escapeHtml(active)}</div>
					</div>
					<div class="mc-manager-item-grid">
						<label class="mc-inline-field">
							<span>Name</span>
							<input type="text" value="${escapeHtml(member.name || '')}" data-field="name">
						</label>
						<label class="mc-inline-field">
							<span>Email</span>
							<input type="text" value="${escapeHtml(member.email || '')}" data-field="email">
						</label>
						<label class="mc-inline-field">
							<span>Role</span>
							<select data-field="role">
								<option value="owner">Owner</option>
								<option value="admin">Admin</option>
								<option value="manager">Manager</option>
								<option value="staff">Staff</option>
								<option value="viewer">Viewer</option>
							</select>
						</label>
						<label class="mc-inline-field mc-inline-field-wide">
							<span>Permissions / Badges</span>
							<input type="text" value="${escapeHtml(perms)}" data-field="permissions" placeholder="comma separated">
						</label>
					</div>
					<div class="mc-manager-item-actions">
						<button class="btn btn-primary" type="button" data-save>Save</button>
						<button class="btn btn-outline" type="button" data-toggle>${member.active ? 'Deactivate' : 'Activate'}</button>
						<button class="btn btn-outline" type="button" data-reset>Password Reset</button>
						<button class="btn btn-outline" type="button" data-delete>Delete</button>
					</div>
				`;
				const roleSelect = row.querySelector('[data-field="role"]');
				roleSelect.value = member.role || 'staff';
				row.querySelector('[data-save]').addEventListener('click', async () => {
					const payload = {};
					row.querySelectorAll('[data-field]').forEach((field) => {
						if (field.dataset.field === 'permissions') {
							payload.permissions = field.value.split(',').map((p) => p.trim()).filter(Boolean);
						} else {
							payload[field.dataset.field] = field.value;
						}
					});
					await sendEndpoint('staff', 'PUT', payload, member.id);
					renderManager('staff', await fetchStaffList({ nocache: true }));
				});
				row.querySelector('[data-toggle]').addEventListener('click', async () => {
					await sendEndpoint('staff', 'PUT', { active: member.active ? 0 : 1 }, member.id);
					renderManager('staff', await fetchStaffList({ nocache: true }));
				});
				row.querySelector('[data-reset]').addEventListener('click', async () => {
					const email = String(member.email || '').trim();
					if (!email) {
						showManagerMessage('Missing Email', 'Add an email address before requesting a password reset.');
						return;
					}
					const ok = confirm(`Send a password reset for ${email}?`);
					if (!ok) return;
					try {
						const result = await requestPasswordReset(email);
						const token = result?.reset_token || result?.data?.reset_token || '';
						if (token) {
							prompt('Reset token (copy & share with the staff member):', token);
						} else {
							alert('If the account exists, a reset token was created.');
						}
					} catch (err) {
						showManagerMessage('Reset Failed', err?.message || 'Unable to request password reset.');
					}
				});
				row.querySelector('[data-delete]').addEventListener('click', async () => {
					await sendEndpoint('staff', 'DELETE', null, member.id);
					renderManager('staff', await fetchStaffList({ nocache: true }));
				});
				list.appendChild(row);
			});
			managerList.appendChild(list);
			if (focusCreate) openFormModal();
		};

		document.querySelectorAll('[data-employee-action]').forEach((btn) => {
			btn.addEventListener('click', async (event) => {
				const action = btn.dataset.employeeAction;
				if (action === 'signage') {
					event.preventDefault();
					if (signageLocked) {
						showManagerMessage('Restricted', 'Signage controls are limited to Sentra staff.');
						return;
					}
					btn.classList.add('is-loading');
					showManagerMessage('Loading…', 'Fetching signage data.');
					try {
						const state = await refreshSignagePanel();
						renderSignageManager(state);
					} catch (err) {
						const message = err?.message ? err.message : 'Unable to load signage. Check API connectivity.';
						showManagerMessage('Connection error', message);
					} finally {
						btn.classList.remove('is-loading');
					}
					return;
				}
				if (!endpointMap[action]) return;
				event.preventDefault();
				btn.classList.add('is-loading');
				showManagerMessage('Loading…', 'Fetching latest data.');
				try {
					const data = action === 'staff'
						? await fetchStaffList({ nocache: true })
						: await fetchEndpoint(action);
					renderList(action, data);
					renderManager(action, data, false);
				} catch (err) {
					const message = err?.message ? err.message : `Unable to load ${action}. Check API connectivity.`;
					if (feedBody) {
						feedBody.innerHTML = `<p class="lead">${message}</p>`;
					}
					if (feedTitle) feedTitle.textContent = 'Connection error';
					if (feedPanel) feedPanel.hidden = false;
					showManagerMessage('Connection error', message);
				} finally {
					btn.classList.remove('is-loading');
				}
			});
		});

		document.querySelectorAll('[data-employee-action="new-job"], [data-employee-action="upload"], [data-employee-action="invite"], [data-employee-action="pricing"], [data-employee-action="staff-new"]').forEach((btn) => {
			btn.addEventListener('click', async (event) => {
				event.preventDefault();
				const action = btn.dataset.employeeAction;
				const map = { 'new-job': 'jobs', 'upload': 'gallery', 'invite': 'partners', 'pricing': 'services', 'staff-new': 'staff' };
				const target = map[action];
				if (!target) return;
				btn.classList.add('is-loading');
				showManagerMessage('Loading…', 'Preparing control panel.');
				try {
					const data = target === 'staff'
						? await fetchStaffList({ nocache: true })
						: await fetchEndpoint(target);
					renderManager(target, data, true);
				} catch (err) {
					const message = err?.message ? err.message : `Unable to load ${target}. Check API connectivity.`;
					showManagerMessage('Connection error', message);
				} finally {
					btn.classList.remove('is-loading');
				}
			});
		});

		if (typeof wireStaffMessaging === 'function') {
			wireStaffMessaging();
		} else {
			debugLog('staff_messaging', 'wireStaffMessaging unavailable in current scope; skipped init');
		}
	};
})();
</script>

<?php get_footer(); ?>
