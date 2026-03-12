@extends('layouts.app')

@section('title', 'THOR REBRAND TOOL - Dashboard')

@section('content')
<div class="hero fade-in">
    <span class="badge">Operator Settings</span>
    <h1 class="hero-title">Account Oversight</h1>
    <p class="hero-desc">Your personal space for managing projects, assets, and account security.</p>
</div>

<div class="fade-in">
    <div style="display: flex; justify-content: flex-end; margin-bottom: 32px;">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-secondary" style="height: 48px;">Sign Out</button>
        </form>
    </div>

    <div class="file-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
        <!-- Rebrand Studio -->
        <a href="/" class="studio-card" style="text-decoration: none; transition: transform var(--transition);">
            <div class="section-label">
                <img src="/procreate-logo.svg" alt="Procreate" style="width: 28px; height: 28px; border-radius: 6px; box-shadow: 0 4px 10px rgba(0,0,0,0.5);">
                <h2 class="section-title">Procreate Rebrand Studio</h2>
            </div>
            <p class="drop-subtext" style="font-size: 1rem; line-height: 1.6;">
                Access the flagship rebranding engine to update your brushes and artwork collections.
            </p>
            <div style="margin-top: 24px; color: var(--primary); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                Launch Engine <span>→</span>
            </div>
        </a>

        <!-- Profile Settings -->
        <a href="{{ route('profile.edit') }}" class="studio-card" style="text-decoration: none; transition: transform var(--transition);">
            <div class="section-label">
                <div class="step-number" style="background: var(--secondary)">👤</div>
                <h2 class="section-title">Account Settings</h2>
            </div>
            <p class="drop-subtext" style="font-size: 1rem; line-height: 1.6;">
                Manage your profile information, email preferences, and security protocols.
            </p>
            <div style="margin-top: 24px; color: var(--secondary); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                Update Profile <span>→</span>
            </div>
        </a>

        <!-- Resource Usage (Static for now) -->
        <div class="studio-card">
            <div class="section-label">
                <div class="step-number" style="background: var(--success)">📊</div>
                <h2 class="section-title">Resource Usage</h2>
            </div>
            <p class="drop-subtext" style="margin-bottom: 16px;">System status: <span style="color: var(--success); font-weight: 700;">Healthy</span></p>
            <div class="progress-track" style="margin: 0 0 12px 0;">
                <div class="progress-bar" style="width: 12%; background: var(--success); box-shadow: 0 0 10px var(--success);"></div>
            </div>
            <p class="drop-subtext">1.2 GB / 10 GB Storage used</p>
        </div>
    </div>

    <!-- SAVED PDF RECORDS -->
    @if(isset($records) && $records->count() > 0)
    <div style="margin-top: 60px;">
        <div class="section-label" style="margin-bottom: 24px;">
            <div class="step-number" style="background: var(--secondary)"><i class="fa-solid fa-file-lines"></i></div>
            <h2 class="section-title">Saved PDF Lab Documents</h2>
        </div>
        <div class="glass-panel" style="background: var(--card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    <tr>
                        <th style="padding: 16px 24px; font-size: 0.85rem; color: var(--text-dim); text-transform: uppercase;">Title / Store Name</th>
                        <th style="padding: 16px 24px; font-size: 0.85rem; color: var(--text-dim); text-transform: uppercase;">Products</th>
                        <th style="padding: 16px 24px; font-size: 0.85rem; color: var(--text-dim); text-transform: uppercase;">Theme</th>
                        <th style="padding: 16px 24px; font-size: 0.85rem; color: var(--text-dim); text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $rec)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <td style="padding: 16px 24px; font-weight: 700;">
                            {{ $rec->title }}
                            <div style="font-size: 0.8rem; color: var(--text-dim); font-weight: 500;">{{ $rec->store_name }}</div>
                        </td>
                        <td style="padding: 16px 24px; color: #ccc;">{{ is_array($rec->products) ? count($rec->products) : 0 }} linked</td>
                        <td style="padding: 16px 24px;">
                            <span class="badge" style="background: rgba(255,255,255,0.05); color: #fff; margin:0;">
                                {{ ucfirst(str_replace('-style', '', $rec->theme)) }} / {{ ucfirst($rec->pdf_mode) }}
                            </span>
                        </td>
                        <td style="padding: 16px 24px; display: flex; gap: 8px;">
                            <a href="{{ route('pdf.edit', $rec->id) }}" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.8rem; height: auto;">Edit</a>
                            <a href="{{ route('pdf.preview', $rec->id) }}" target="_blank" class="btn" style="background: transparent; border: 1px solid var(--border-color); padding: 6px 14px; font-size: 0.8rem; height: auto;">Preview</a>
                            <form method="POST" action="{{ route('pdf.delete', $rec->id) }}" onsubmit="return confirm('Delete this PDF setting?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn" style="background: transparent; border: 1px solid rgba(255,50,50,0.3); color: #f55; padding: 6px 14px; font-size: 0.8rem; height: auto;">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<style>
    .studio-card:hover {
        transform: translateY(-4px);
        border-color: var(--primary);
    }
</style>
@endsection
