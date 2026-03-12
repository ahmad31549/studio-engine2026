@extends('layouts.app')

@section('title', 'Select Your Studio - THOR REBRAND TOOL')

@section('content')
@php
    $user = auth()->user();
    $hasProcreate = $user->hasToolAccess('procreate_studio');
    $hasPdf = $user->hasToolAccess('pdf_lab');
    $hasGraphic = $user->hasToolAccess('graphic_rebrand');
@endphp

<div class="hero fade-in selection-hero">
    <span class="badge">Studio Workspace</span>
    <h1 class="hero-title">Select Your Tool</h1>
    <p class="hero-desc">Choose the specialized engine you want to launch for your next project.</p>
</div>

<div class="selection-shell fade-in">
    <div class="selection-grid">
        <a
            href="{{ $hasProcreate ? route('studio.procreate') : '#' }}"
            class="studio-card selection-card {{ $hasProcreate ? 'selection-card--active selection-card--procreate' : 'selection-card--locked' }}"
            @if(!$hasProcreate) aria-disabled="true" onclick="return false;" @endif
        >
            <div class="selection-card__top">
                <div class="selection-card__badges">
                    @unless($hasProcreate)
                        <span class="selection-chip selection-chip--locked">Access Required</span>
                    @endunless
                </div>
                <div class="selection-card__header">
                    <div class="selection-card__icon selection-card__icon--procreate">
                        <i class="fa-solid fa-palette" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="selection-card__eyebrow">Creative Metadata Engine</p>
                        <h2 class="selection-card__title">Procreate Rebrand Studio</h2>
                    </div>
                </div>
                <p class="selection-card__copy">
                    The flagship engine for rebranding brushes, swatches, and <code class="selection-card__code">.procreate</code> files with custom metadata.
                </p>
            </div>
            <div class="selection-card__footer">
                @if($hasProcreate)
                    <span class="btn btn-primary selection-card__action">
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        Launch Studio
                    </span>
                @else
                    <span class="btn btn-secondary selection-card__action selection-card__action--locked">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        Locked
                    </span>
                @endif
            </div>
        </a>

        <a
            href="{{ $hasPdf ? route('pdf.index') : '#' }}"
            class="studio-card selection-card {{ $hasPdf ? 'selection-card--active selection-card--pdf' : 'selection-card--locked' }}"
            @if(!$hasPdf) aria-disabled="true" onclick="return false;" @endif
        >
            <div class="selection-card__top">
                <div class="selection-card__badges">
                    @unless($hasPdf)
                        <span class="selection-chip selection-chip--locked">Access Required</span>
                    @endunless
                </div>
                <div class="selection-card__header">
                    <div class="selection-card__icon selection-card__icon--pdf">
                        <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="selection-card__eyebrow">Listing Delivery Engine</p>
                        <h2 class="selection-card__title">Etsy PDF Lab</h2>
                    </div>
                </div>
                <p class="selection-card__copy">
                    Automated PDF watermarking and branding for digital products, download guides, and listing assets.
                </p>
            </div>
            <div class="selection-card__footer">
                @if($hasPdf)
                    <span class="btn btn-secondary selection-card__action selection-card__action--pdf">
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        Launch Lab
                    </span>
                @else
                    <span class="btn btn-secondary selection-card__action selection-card__action--locked">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        Locked
                    </span>
                @endif
            </div>
        </a>

        <div class="studio-card selection-card selection-card--locked selection-card--soon" aria-disabled="true">
            <div class="selection-card__top">
                <div class="selection-card__badges">
                    @unless($hasGraphic)
                        <span class="selection-chip selection-chip--locked">Access Required</span>
                    @endunless
                    <span class="selection-chip selection-chip--soon">Coming Soon</span>
                </div>
                <div class="selection-card__header">
                    <div class="selection-card__icon selection-card__icon--graphic">
                        <i class="fa-solid fa-image" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="selection-card__eyebrow">Bulk Visual Toolkit</p>
                        <h2 class="selection-card__title">Graphic Rebrand</h2>
                    </div>
                </div>
                <p class="selection-card__copy">
                    Bulk brand application for vector assets, mockups, creative kits, and visual design templates.
                </p>
            </div>
            <div class="selection-card__footer">
                <span class="btn btn-secondary selection-card__action selection-card__action--soon">
                    <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
                    Coming Soon
                </span>
            </div>
        </div>
    </div>
</div>

<style>
    .selection-hero {
        max-width: 860px;
        margin: 0 auto 52px;
    }

    .selection-hero .hero-title {
        font-size: clamp(3.1rem, 5.8vw, 5.6rem);
        margin-bottom: 18px;
    }

    .selection-hero .hero-desc {
        max-width: 720px;
        font-size: clamp(1.05rem, 1.8vw, 1.35rem);
        line-height: 1.6;
    }

    .selection-shell {
        --selection-warm: rgba(249, 115, 22, 0.18);
        --selection-warm-strong: rgba(251, 146, 60, 0.34);
        --selection-cool: rgba(99, 102, 241, 0.18);
        --selection-cool-strong: rgba(129, 140, 248, 0.32);
        --selection-green: rgba(16, 185, 129, 0.16);
        --selection-green-strong: rgba(52, 211, 153, 0.3);
        max-width: 1220px;
        margin: 0 auto 72px;
        position: relative;
        isolation: isolate;
    }

    .selection-shell::before {
        content: '';
        position: absolute;
        inset: 40px 4% -32px;
        background:
            radial-gradient(circle at 16% 24%, rgba(249, 115, 22, 0.16), transparent 26%),
            radial-gradient(circle at 84% 20%, rgba(99, 102, 241, 0.16), transparent 28%),
            radial-gradient(circle at 50% 100%, rgba(16, 185, 129, 0.09), transparent 30%);
        filter: blur(22px);
        opacity: 0.95;
        pointer-events: none;
        z-index: -1;
    }

    .selection-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 28px;
    }

    .selection-card {
        --selection-accent: rgba(255, 255, 255, 0.12);
        --selection-accent-strong: rgba(255, 255, 255, 0.2);
        --selection-title-start: #ffffff;
        --selection-title-end: #dbe4f3;
        text-decoration: none;
        color: inherit;
        position: relative;
        min-height: 480px;
        padding: 32px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 28px;
        overflow: hidden;
        isolation: isolate;
        background:
            linear-gradient(160deg, rgba(21, 25, 39, 0.96), rgba(8, 10, 18, 0.95)),
            rgba(22, 24, 31, 0.68);
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow:
            0 20px 46px rgba(0, 0, 0, 0.42),
            inset 0 1px 0 rgba(255, 255, 255, 0.05);
        transition:
            transform 240ms cubic-bezier(0.22, 1, 0.36, 1),
            border-color 240ms ease,
            box-shadow 240ms ease,
            filter 240ms ease;
    }

    .selection-card:visited,
    .selection-card:hover,
    .selection-card:focus-visible {
        color: inherit;
    }

    .selection-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at top right, var(--selection-accent-strong), transparent 34%),
            radial-gradient(circle at bottom left, var(--selection-accent), transparent 30%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0));
        opacity: 1;
        pointer-events: none;
        z-index: -1;
    }

    .selection-card::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        right: -110px;
        bottom: -120px;
        background: radial-gradient(circle, var(--selection-accent), transparent 72%);
        filter: blur(12px);
        opacity: 0.85;
        pointer-events: none;
        z-index: -1;
    }

    .selection-card--procreate {
        --selection-accent: var(--selection-warm);
        --selection-accent-strong: var(--selection-warm-strong);
        --selection-title-start: #fff6ec;
        --selection-title-end: #ffbf85;
    }

    .selection-card--pdf {
        --selection-accent: var(--selection-cool);
        --selection-accent-strong: var(--selection-cool-strong);
        --selection-title-start: #f3f5ff;
        --selection-title-end: #b7c2ff;
    }

    .selection-card--soon {
        --selection-accent: var(--selection-green);
        --selection-accent-strong: var(--selection-green-strong);
        --selection-title-start: #f0fdf6;
        --selection-title-end: #96efc1;
    }

    .selection-card--active {
        filter: none;
    }

    .selection-card--active:hover {
        transform: translateY(-10px);
        border-color: var(--selection-accent-strong) !important;
        box-shadow:
            0 28px 60px rgba(0, 0, 0, 0.5),
            0 20px 38px var(--selection-accent),
            inset 0 1px 0 rgba(255, 255, 255, 0.06);
    }

    .selection-card--locked {
        cursor: default;
        filter: saturate(0.84);
    }

    .selection-card--soon {
        background:
            linear-gradient(160deg, rgba(17, 31, 28, 0.95), rgba(10, 14, 18, 0.96)),
            rgba(255, 255, 255, 0.02);
    }

    .selection-card__top {
        display: flex;
        flex-direction: column;
        gap: 28px;
    }

    .selection-card__badges {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
        min-height: 34px;
    }

    .selection-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        padding: 0 16px;
        border-radius: 999px;
        border: 1px solid transparent;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        white-space: nowrap;
        backdrop-filter: blur(12px);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }

    .selection-chip--locked {
        background: rgba(239, 68, 68, 0.12);
        border-color: rgba(248, 113, 113, 0.34);
        color: #fecaca;
    }

    .selection-chip--soon {
        background: rgba(16, 185, 129, 0.12);
        border-color: rgba(52, 211, 153, 0.35);
        color: #b8f6dc;
    }

    .selection-card__header {
        display: flex;
        align-items: flex-start;
        gap: 18px;
    }

    .selection-card__icon {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex: 0 0 auto;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.08),
            0 14px 30px rgba(0, 0, 0, 0.24);
    }

    .selection-card__icon--procreate {
        background: linear-gradient(135deg, rgba(249, 115, 22, 0.3), rgba(249, 115, 22, 0.1));
        color: #ffd1a9;
    }

    .selection-card__icon--pdf {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.28), rgba(99, 102, 241, 0.1));
        color: #d2d7ff;
    }

    .selection-card__icon--graphic {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.26), rgba(16, 185, 129, 0.1));
        color: #c9f7e3;
    }

    .selection-card__eyebrow {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(226, 232, 240, 0.7);
        margin-bottom: 8px;
    }

    .selection-card--procreate .selection-card__eyebrow {
        color: #f6c39d;
    }

    .selection-card--pdf .selection-card__eyebrow {
        color: #c7d0ff;
    }

    .selection-card--soon .selection-card__eyebrow {
        color: #a9ebca;
    }

    .selection-card__title {
        margin: 0;
        font-size: clamp(1.8rem, 2.6vw, 2.35rem);
        line-height: 1.08;
        color: var(--text-main);
        background: linear-gradient(135deg, var(--selection-title-start), var(--selection-title-end));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .selection-card__copy {
        margin: 0;
        font-size: 1.05rem;
        line-height: 1.7;
        color: #c6d0e1;
        max-width: 28ch;
    }

    .selection-card__code {
        font-family: inherit;
        font-size: 0.95em;
        padding: 0.14rem 0.38rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #f8fafc;
    }

    .selection-card__footer {
        margin-top: auto;
    }

    .selection-card__action {
        width: 100%;
        min-height: 58px;
        border-radius: 18px;
        font-size: 1rem;
        font-weight: 800;
        pointer-events: none;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.08),
            0 16px 28px rgba(0, 0, 0, 0.24);
    }

    .selection-card--procreate .selection-card__action {
        background: linear-gradient(135deg, #ff7918, #ff9d40);
        color: #231305;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.2),
            0 18px 34px rgba(249, 115, 22, 0.28);
    }

    .selection-card__action--locked {
        opacity: 0.82;
        color: #d8e0ed;
        border-color: rgba(255, 255, 255, 0.09);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.055), rgba(255, 255, 255, 0.03));
    }

    .selection-card__action--pdf {
        border-color: rgba(129, 140, 248, 0.28);
        background: linear-gradient(135deg, rgba(34, 39, 78, 0.96), rgba(23, 28, 58, 0.94));
        color: #eef2ff;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.08),
            0 18px 30px rgba(99, 102, 241, 0.16);
    }

    .selection-card__action--soon {
        opacity: 0.9;
        border-color: rgba(52, 211, 153, 0.24);
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.14), rgba(16, 185, 129, 0.08));
        color: #d1fae5;
    }

    @media (max-width: 1100px) {
        .selection-grid {
            grid-template-columns: 1fr;
        }

        .selection-card {
            min-height: 0;
        }

        .selection-card__copy {
            max-width: none;
        }
    }

    @media (max-width: 640px) {
        .selection-hero {
            margin-bottom: 36px;
        }

        .selection-hero .hero-title {
            font-size: clamp(2.55rem, 12vw, 3.4rem);
        }

        .selection-card {
            padding: 24px;
        }

        .selection-card__header {
            flex-direction: column;
            gap: 14px;
        }

        .selection-card__badges {
            justify-content: flex-start;
        }
    }
</style>
@endsection
