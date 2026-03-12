@extends('layouts.app')

@section('title', 'THOR REBRAND TOOL - Profile Settings')

@section('content')
<div class="hero fade-in">
    <span class="badge">Operator Profile</span>
    <h1 class="hero-title">Profile Settings</h1>
    <p class="hero-desc">Update your personal information and account security preferences.</p>
</div>

<div class="fade-in" style="max-width: 800px; margin: 0 auto 24px; display: flex; justify-content: flex-end;">
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-secondary">Sign Out</button>
    </form>
</div>

<div class="fade-in" style="max-width: 800px; margin: 0 auto 100px;">
    <!-- Update Identity -->
    <section class="studio-card">
        <div class="section-label">
            <div class="step-number">👤</div>
            <h2 class="section-title">Personal Information</h2>
        </div>
        <p class="drop-subtext" style="margin-bottom: 32px;">Update your display name and email address.</p>
        
        <form method="post" action="{{ route('profile.update') }}" class="control-group" style="gap: 20px;">
            @csrf
            @method('patch')

            <div class="control-group">
                <label for="name" class="control-label">Full Name</label>
                <input id="name" name="name" type="text" class="text-input" value="{{ old('name', $user->name) }}" required autofocus>
                @error('name') <p class="drop-subtext" style="color: var(--error);">{{ $message }}</p> @enderror
            </div>

            <div class="control-group">
                <label for="email" class="control-label">Email Address</label>
                <input id="email" name="email" type="email" class="text-input" value="{{ old('email', $user->email) }}" required>
                @error('email') <p class="drop-subtext" style="color: var(--error);">{{ $message }}</p> @enderror
            </div>

            <div style="margin-top: 12px; display: flex; align-items: center; gap: 20px;">
                <button type="submit" class="btn btn-primary">Save Profile</button>
                @if (session('status') === 'profile-updated')
                    <p class="drop-subtext" style="color: var(--success); font-weight: 700;">Profile updated successfully.</p>
                @endif
            </div>
        </form>
    </section>

    <!-- Update Password -->
    <section class="studio-card">
        <div class="section-label">
            <div class="step-number" style="background: var(--secondary)"><i class="fa-solid fa-lock"></i></div>
            <h2 class="section-title">Security Update</h2>
        </div>
        <p class="drop-subtext" style="margin-bottom: 32px;">Ensure your account remains secure with a strong password.</p>
        
        <form method="post" action="{{ route('password.update') }}" class="control-group" style="gap: 20px;">
            @csrf
            @method('put')

            <div class="control-group">
                <label for="update_password_current_password" class="control-label">Current Password</label>
                <input id="update_password_current_password" name="current_password" type="password" class="text-input" autocomplete="current-password">
                @error('current_password', 'updatePassword') <p class="drop-subtext" style="color: var(--error);">{{ $message }}</p> @enderror
            </div>

            <div class="control-group">
                <label for="update_password_password" class="control-label">New Password</label>
                <input id="update_password_password" name="password" type="password" class="text-input" autocomplete="new-password">
                @error('password', 'updatePassword') <p class="drop-subtext" style="color: var(--error);">{{ $message }}</p> @enderror
            </div>

            <div class="control-group">
                <label for="update_password_password_confirmation" class="control-label">Confirm New Password</label>
                <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="text-input" autocomplete="new-password">
            </div>

            <div style="margin-top: 12px; display: flex; align-items: center; gap: 20px;">
                <button type="submit" class="btn btn-primary" style="background: var(--secondary); box-shadow: 0 8px 16px rgba(99, 102, 241, 0.2);">Update Password</button>
                @if (session('status') === 'password-updated')
                    <p class="drop-subtext" style="color: var(--success); font-weight: 700;">Security code updated.</p>
                @endif
            </div>
        </form>
    </section>

    <!-- Delete Account -->
    <section class="studio-card" style="border-color: rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.02);">
        <div class="section-label">
            <div class="step-number" style="background: var(--error)">⚠️</div>
            <h2 class="section-title" style="color: var(--error)">Danger Zone</h2>
        </div>
        <p class="drop-subtext" style="margin-bottom: 32px;">Permanently delete your account and all associated collections from the studio engine.</p>
        
        <button type="button" class="btn btn-secondary" style="border-color: var(--error); color: var(--error);" id="deleteBtn">Delete Account Permanently</button>

        <div id="deleteModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; padding: 24px;">
            <div class="studio-card" style="width: min(100%, 500px);">
                <h2 class="section-title">Are you sure?</h2>
                <p class="drop-subtext" style="margin: 16px 0 32px;">Once your account is deleted, all of your assets and metadata will be permanently erased. This cannot be undone.</p>
                
                <form method="post" action="{{ route('profile.destroy') }}" class="control-group" style="gap: 20px;">
                    @csrf
                    @method('delete')
                    
                    <div class="control-group">
                        <label for="password" class="control-label">Confirm Password</label>
                        <input id="password" name="password" type="password" class="text-input" placeholder="••••••••" required>
                        @error('password', 'userDeletion') <p class="drop-subtext" style="color: var(--error);">{{ $message }}</p> @enderror
                    </div>

                    <div style="display: flex; gap: 16px; justify-content: flex-end; margin-top: 12px;">
                        <button type="button" class="btn btn-secondary" id="cancelBtn">Go Back</button>
                        <button type="submit" class="btn btn-primary" style="background: var(--error); box-shadow: 0 8px 16px rgba(239, 68, 68, 0.2);">Permanently Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
const delBtn = document.getElementById('deleteBtn');
const delModal = document.getElementById('deleteModal');
const cancelBtn = document.getElementById('cancelBtn');

if(delBtn) delBtn.onclick = () => delModal.style.display = 'flex';
if(cancelBtn) cancelBtn.onclick = () => delModal.style.display = 'none';
window.onclick = (e) => { if(e.target === delModal) delModal.style.display = 'none'; };
</script>
@endpush
