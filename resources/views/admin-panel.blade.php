@extends('layouts.app')

@section('title', 'Admin Panel - THOR REBRAND TOOL')

@section('content')
<div class="hero fade-in">
    <span class="badge">Administration</span>
    <h1 class="hero-title">Member Management</h1>
    <p class="hero-desc">Approve new access requests, manage tool permissions, and maintain member security.</p>
</div>

@if(session('success'))
<div class="admin-flash admin-flash--success fade-in">
    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
    <span>{{ session('success') }}</span>
</div>
@endif

@if(session('error'))
<div class="admin-flash admin-flash--error fade-in">
    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
    <span>{{ session('error') }}</span>
</div>
@endif

<div class="fade-in" style="max-width: 1400px; margin: 0 auto 60px;">
    <div class="studio-card" style="padding: 0; overflow: hidden; border-radius: 20px;">
        <div style="padding: 24px 32px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
            <h2 style="font-size: 1.25rem; font-weight: 800;">Member Directory</h2>
            <div style="display: flex; gap: 12px;">
                <span class="badge" style="background: rgba(249, 115, 22, 0.1); color: var(--primary); margin: 0;">{{ $users->count() }} Total Users</span>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.2); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 20px 32px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-dim);">User Details</th>
                        <th style="padding: 20px 32px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-dim);">Status</th>
                        <th style="padding: 20px 32px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-dim);">Tool Access</th>
                        <th style="padding: 20px 32px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-dim); text-align: right;">Security & Control</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    @foreach($users as $user)
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 24px 32px;">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="width: 44px; height: 44px; background: {{ $user->is_admin ? 'var(--primary)' : 'var(--surface-hover)' }}; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: {{ $user->is_admin ? '#000' : 'var(--text-main)' }}; font-size: 1.2rem;">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <div style="font-weight: 700; font-size: 1rem; color: var(--text-main);">
                                        {{ $user->name }} 
                                        @if($user->is_admin) 
                                            <span class="badge" style="font-size: 0.6rem; padding: 2px 8px; margin-left: 6px; background: var(--primary); color: #000; border: none;">Admin</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--text-dim);">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 24px 32px;">
                            <select onchange="updateStatus({{ $user->id }}, this.value)" style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); color: #fff; padding: 8px 12px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; outline: none;">
                                <option value="pending" {{ $user->status === 'pending' ? 'selected' : '' }}><i class="fa-solid fa-hourglass-half"></i> Pending</option>
                                <option value="approved" {{ $user->status === 'approved' ? 'selected' : '' }}><i class="fa-solid fa-circle-check"></i> Approved</option>
                                <option value="rejected" {{ $user->status === 'rejected' ? 'selected' : '' }}><i class="fa-solid fa-circle-xmark"></i> Rejected</option>
                            </select>
                        </td>
                        <td style="padding: 24px 32px;">
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                @php
                                    $tools = [
                                        ['id' => 'procreate_studio', 'label' => 'Procreate', 'color' => 'var(--primary)'],
                                        ['id' => 'pdf_lab', 'label' => 'PDF Lab', 'color' => 'var(--secondary)']
                                    ];
                                @endphp
                                @foreach($tools as $tool)
                                    <label style="display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.05); padding: 6px 12px; border-radius: 20px; border: 1px solid var(--border-color); cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='{{ $tool['color'] }}'" onmouseout="this.style.borderColor='var(--border-color)'">
                                        <input type="checkbox" 
                                               onchange="updateToolAccess({{ $user->id }})" 
                                               class="tool-checkbox-{{ $user->id }}" 
                                               value="{{ $tool['id'] }}" 
                                               {{ $user->hasExplicitToolAccess($tool['id']) ? 'checked' : '' }}
                                               style="accent-color: {{ $tool['color'] }}; width: 14px; height: 14px;">
                                        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">{{ $tool['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </td>
                        <td style="padding: 24px 32px; text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                                <button onclick="resetPassword({{ $user->id }})" class="btn btn-secondary" style="height: 40px; padding: 0 16px; font-size: 0.8rem; background: rgba(255,255,255,0.02);">
                                    <i class="fa-solid fa-key"></i> Reset Pass
                                </button>
                                @if(!$user->is_admin)
                                <form method="POST" action="{{ route('admin.users.delete', $user->id) }}" onsubmit="return confirm('Are you sure you want to delete this member? This action is permanent.');" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary" style="height: 40px; width: 40px; padding: 0; color: var(--error); border-color: rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.05);" aria-label="Delete {{ $user->name }}">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Background -->
<div id="modalBackdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; padding: 24px;">
    <!-- Reset Password Modal -->
    <div id="passwordModal" class="studio-card fade-in" style="width: min(100%, 450px); margin: 0; display: none;">
        <div class="section-label">
            <div class="step-number"><i class="fa-solid fa-key"></i></div>
            <h2 class="section-title">Reset User Password</h2>
        </div>
        <p class="drop-subtext" style="margin-bottom: 24px;">Enter a new security credential for this member.</p>
        
        <div class="control-group">
            <label class="control-label">New Password</label>
            <input type="password" id="newPasswordInput" class="text-input" placeholder="Min 8 characters">
        </div>

        <div style="display: flex; gap: 12px; margin-top: 32px;">
            <button onclick="closeModal()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
            <button id="confirmPasswordBtn" class="btn btn-primary" style="flex: 1.5;">Apply New Password</button>
        </div>
    </div>
</div>

<style>
    th, td { border-bottom: 1px solid var(--border-color); }
    table { border-spacing: 0; }
    .fade-in { animation: fadeIn 0.5s ease-out; }
    .admin-flash {
        max-width: 1400px;
        margin: 0 auto 24px;
        padding: 16px 20px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 700;
        border: 1px solid transparent;
    }
    .admin-flash--success {
        background: rgba(16, 185, 129, 0.12);
        border-color: rgba(16, 185, 129, 0.28);
        color: #8df0c4;
    }
    .admin-flash--error {
        background: rgba(239, 68, 68, 0.12);
        border-color: rgba(239, 68, 68, 0.28);
        color: #ff9c9c;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

@endsection

@push('scripts')
<script>
    let activeUserId = null;

    function openModal(id) {
        activeUserId = id;
        document.getElementById('modalBackdrop').style.display = 'flex';
        document.getElementById('passwordModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('modalBackdrop').style.display = 'none';
        document.getElementById('passwordModal').style.display = 'none';
        document.getElementById('newPasswordInput').value = '';
    }

    async function updateStatus(userId, status) {
        try {
            const resp = await fetch(`/admin/users/${userId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ status })
            });
            const data = await resp.json();
            if(data.success) {
                showToast('<i class="fa-solid fa-circle-check"></i> Status updated successfully');
            } else {
                alert(data.message || 'Error updating status');
            }
        } catch (e) {
            console.error(e);
            alert('Connection error');
        }
    }

    async function updateToolAccess(userId) {
        const checkboxes = document.querySelectorAll(`.tool-checkbox-${userId}`);
        const tool_access = Array.from(checkboxes)
            .filter(i => i.checked)
            .map(i => i.value);

        try {
            const resp = await fetch(`/admin/users/${userId}/tool-access`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ tool_access })
            });
            const data = await resp.json();
            if(data.success) {
                showToast('🔓 Tool permissions synchronized');
            } else {
                alert(data.message || 'Error updating access');
            }
        } catch (e) {
            console.error(e);
            alert('Connection error');
        }
    }

    function resetPassword(userId) {
        openModal(userId);
    }

    document.getElementById('confirmPasswordBtn').onclick = async () => {
        const password = document.getElementById('newPasswordInput').value;
        if (password.length < 8) return alert('Password must be at least 8 characters');

        const btn = document.getElementById('confirmPasswordBtn');
        btn.innerText = 'Updating...';
        btn.disabled = true;

        try {
            const resp = await fetch(`/admin/users/${activeUserId}/reset-password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ password })
            });
            const data = await resp.json();
            if(data.success) {
                showToast('<i class="fa-solid fa-key"></i> Password changed');
                closeModal();
            } else {
                alert(data.message || 'Error resetting password');
            }
        } catch (e) {
            console.error(e);
            alert('Connection error');
        } finally {
            btn.innerText = 'Apply New Password';
            btn.disabled = false;
        }
    };

    function showToast(msg) {
        // Simple notification (or use your existing toast system)
        console.log(msg);
        const toast = document.createElement('div');
        toast.style.cssText = 'position:fixed; bottom:30px; left:50%; transform:translateX(-50%); background:var(--primary); color:#000; padding:12px 24px; border-radius:12px; font-weight:800; z-index:100000; box-shadow:0 10px 30px rgba(0,0,0,0.5); animation:slideUp 0.3s ease-out;';
        toast.innerText = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
</script>
<style>
    @keyframes slideUp { from { bottom: -50px; opacity: 0; } to { bottom: 30px; opacity: 1; } }
</style>
@endpush
