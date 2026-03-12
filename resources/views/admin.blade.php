@extends('layouts.app')

@section('title', 'THOR REBRAND TOOL - System Control')

@section('content')
<div class="hero fade-in">
    <span class="badge">System Control</span>
    <h1 class="hero-title">Admin Dashboard</h1>
    <p class="hero-desc">Monitor recent activity, track processing jobs, and manage system resources.</p>
</div>

<div id="adminRoot">
    <!-- LOGIN VIEW -->
    <div id="loginView" style="display: none; justify-content: center; padding: 40px 0;">
        <div class="studio-card" style="width: min(100%, 450px);">
            <div class="section-label">
                <div class="step-number"><i class="fa-solid fa-lock"></i></div>
                <h2 class="section-title">Admin Access</h2>
            </div>
            <form id="loginForm" class="control-group" style="gap: 20px;">
                <div class="control-group">
                    <label class="control-label">Username</label>
                    <input type="text" id="username" placeholder="admin" class="text-input" required>
                </div>
                <div class="control-group">
                    <label class="control-label">Password</label>
                    <input type="password" id="password" placeholder="••••••••" class="text-input" required>
                </div>
                <button type="submit" id="loginBtn" class="btn btn-primary" style="margin-top: 20px;">Authorize Session</button>
            </form>
        </div>
    </div>

    <!-- DASHBOARD VIEW -->
    <div id="dashboardView" style="display: none;" class="fade-in">
        <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
            <button id="logoutBtn" class="btn btn-secondary" style="height: 40px; padding: 0 20px; font-size: 0.875rem;">Sign Out</button>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
            <!-- JOB PIPELINE -->
            <div class="studio-card">
                <div class="section-label">
                    <div class="step-number" style="background: var(--secondary)">⚙️</div>
                    <h2 class="section-title">Job Pipeline</h2>
                </div>
                <div id="jobList" class="file-grid" style="grid-template-columns: 1fr; gap: 12px;">
                    <!-- Jobs Injected Here -->
                </div>
            </div>

            <!-- ACTIVITY LOG -->
            <div class="studio-card">
                <div class="section-label">
                    <div class="step-number" style="background: var(--success)">📈</div>
                    <h2 class="section-title">Security Log</h2>
                </div>
                <div id="activityList" class="file-grid" style="grid-template-columns: 1fr; gap: 12px;">
                    <!-- Activities Injected Here -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const CONFIGURED_API_BASE = @json(config('services.backend.url'));
    const normalizeBase = (value) => value ? value.replace(/\/$/, '') : '';
    const shouldRetryWithFallback = (status) => [404, 413, 431, 500, 502, 503, 504].includes(status);
    let activeApiBase = normalizeBase(CONFIGURED_API_BASE);

    function getApiBases() {
        const bases = [];
        const configuredBase = normalizeBase(CONFIGURED_API_BASE);
        if (configuredBase) {
            bases.push(configuredBase);
        }

        if (typeof window !== 'undefined') {
            const hostname = window.location.hostname || '127.0.0.1';
            bases.push(`http://${hostname}:8001`);
            bases.push(`http://${hostname}:8000`);
        }

        return Array.from(new Set(bases.filter(Boolean)));
    }

    async function apiFetch(path, init = {}) {
        let lastResponse = null;
        let lastError = null;

        for (const base of getApiBases()) {
            try {
                const response = await fetch(`${base}${path}`, init);
                if (response.ok) {
                    activeApiBase = base;
                    return response;
                }

                lastResponse = response;
                if (!shouldRetryWithFallback(response.status)) {
                    activeApiBase = base;
                    return response;
                }
            } catch (error) {
                lastError = error;
            }
        }

        if (lastResponse) return lastResponse;
        throw lastError || new Error('Request failed');
    }

    async function readErrorMessage(response, fallback) {
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const data = await response.json().catch(() => null);
            if (typeof data?.detail === 'string' && data.detail.trim()) {
                return data.detail;
            }
        }

        const text = (await response.text().catch(() => '')).trim();
        return text ? `${fallback} (${response.status}): ${text.slice(0, 180)}` : `${fallback} (${response.status})`;
    }

    let token = localStorage.getItem('adminToken');

    const elements = {
        loginView: document.getElementById('loginView'),
        dashboardView: document.getElementById('dashboardView'),
        loginForm: document.getElementById('loginForm'),
        usernameIn: document.getElementById('username'),
        passwordIn: document.getElementById('password'),
        loginBtn: document.getElementById('loginBtn'),
        logoutBtn: document.getElementById('logoutBtn'),
        jobList: document.getElementById('jobList'),
        activityList: document.getElementById('activityList')
    };

    function updateView() {
        if (!token) {
            elements.loginView.style.display = 'flex';
            elements.dashboardView.style.display = 'none';
        } else {
            elements.loginView.style.display = 'none';
            elements.dashboardView.style.display = 'block';
            fetchData();
        }
    }

    elements.loginForm.onsubmit = async (e) => {
        e.preventDefault();
        elements.loginBtn.innerText = "Authenticating...";
        elements.loginBtn.disabled = true;

        const formData = new URLSearchParams();
        formData.append('username', elements.usernameIn.value);
        formData.append('password', elements.passwordIn.value);

        try {
            const resp = await apiFetch('/admin/token', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });

            if (!resp.ok) throw new Error(await readErrorMessage(resp, "Invalid credentials"));

            const data = await resp.json();
            token = data.access_token;
            localStorage.setItem('adminToken', token);
            updateView();
        } catch (e) {
            alert(e.message);
        } finally {
            elements.loginBtn.innerText = "Authorize Session";
            elements.loginBtn.disabled = false;
        }
    };

    elements.logoutBtn.onclick = () => {
        token = null;
        localStorage.removeItem('adminToken');
        updateView();
    };

    async function fetchData() {
        try {
            const [jobsRes, actRes] = await Promise.all([
                apiFetch('/admin/jobs', { headers: { Authorization: `Bearer ${token}` } }),
                apiFetch('/admin/activity', { headers: { Authorization: `Bearer ${token}` } })
            ]);

            if (jobsRes.status === 401 || actRes.status === 401) return elements.logoutBtn.click();

            if (jobsRes.ok) renderJobs(Object.values(await jobsRes.json()));
            if (actRes.ok) renderActivities(await actRes.json());
        } catch (e) { console.error(e); }
    }

    function renderJobs(jobs) {
        if (jobs.length === 0) {
            elements.jobList.innerHTML = '<p class="drop-subtext">No active or historic jobs found.</p>';
            return;
        }
        elements.jobList.innerHTML = '';
        jobs.sort((a, b) => (b.created_at || 0) - (a.created_at || 0)).slice(0, 10).forEach(job => {
            const div = document.createElement('div');
            div.className = 'file-item';
            div.innerHTML = `
                <div class="file-icon">${job.status === 'completed' ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-hourglass-half"></i>'}</div>
                <div class="file-info">
                    <div class="file-name">Job ${job.id.substring(0, 8)}</div>
                    <div class="file-meta">${new Date((job.created_at || 0) * 1000).toLocaleString()}</div>
                </div>
                <span class="badge" style="background: ${job.status === 'completed' ? 'var(--success)' : 'var(--primary)'}; color: #000; margin: 0; position: relative; top: 0; right: 0;">${job.status}</span>
            `;
            elements.jobList.appendChild(div);
        });
    }

    function renderActivities(acts) {
        if (acts.length === 0) {
            elements.activityList.innerHTML = '<p class="drop-subtext">No security events recorded.</p>';
            return;
        }
        elements.activityList.innerHTML = '';
        acts.forEach(act => {
            const div = document.createElement('div');
            div.className = 'file-item';
            div.innerHTML = `
                <div class="file-icon">🛡️</div>
                <div class="file-info">
                    <div class="file-name">${act.admin_user}: ${act.action.replace('_', ' ')}</div>
                    <div class="file-meta">${new Date((act.timestamp || 0) * 1000).toLocaleString()}</div>
                </div>
            `;
            elements.activityList.appendChild(div);
        });
    }

    updateView();
    setInterval(() => { if (token) fetchData(); }, 10000);
</script>
@endpush
