<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Etsy PDF Lab — My Records</title>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --accent: #FFD700;
            --accent-soft: rgba(255, 215, 0, 0.10);
            --accent-glow: rgba(255, 215, 0, 0.35);
            --bg: #050507;
            --panel: #0d0d10;
            --card: #111116;
            --card-border: rgba(255, 215, 0, 0.10);
            --text: #ffffff;
            --text-dim: #6666a0;
            --gradient: linear-gradient(135deg, #FFD700 0%, #FF8C00 100%);
            --purple: #7C3AED;
            --success: #10b981;
            --danger: #ef4444;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        ::-webkit-scrollbar { width: 14px; height: 14px; }
        ::-webkit-scrollbar-track { background: var(--bg); border-left: 1px solid rgba(255,255,255,0.05); }
        ::-webkit-scrollbar-thumb { background: #444; border-radius: 8px; border: 3px solid var(--bg); }
        ::-webkit-scrollbar-thumb:hover { background: #666; }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── ORB BACKGROUNDS ── */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(140px);
            z-index: 0;
            pointer-events: none;
            opacity: 0.08;
        }
        .orb-1 { top: -300px; right: -200px; width: 700px; height: 700px; background: #FFD700; }
        .orb-2 { bottom: -200px; left: -150px; width: 600px; height: 600px; background: #7C3AED; }

        /* ── NAV ── */
        nav {
            height: 72px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            display: flex;
            align-items: center;
            padding: 0 36px;
            background: rgba(5,5,7,0.88);
            backdrop-filter: blur(24px);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .nav-inner {
            width: 100%;
            max-width: 1300px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .brand-bolt {
            font-size: 22px;
            filter: drop-shadow(0 0 10px var(--accent));
            animation: glw 2s ease-in-out infinite alternate;
        }
        @keyframes glw {
            from { filter: drop-shadow(0 0 5px var(--accent)); }
            to   { filter: drop-shadow(0 0 18px var(--accent)); }
        }
        .brand-text {
            font-family: 'Orbitron', sans-serif;
            font-size: 17px;
            font-weight: 900;
            letter-spacing: 3px;
            color: #fff;
        }
        .brand-text span { color: var(--accent); }

        .nav-back {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-dim);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .nav-back:hover { color: var(--accent); }
        .nav-back svg { width: 16px; height: 16px; }

        /* ── MAIN ── */
        .main {
            max-width: 1300px;
            margin: 0 auto;
            padding: 48px 36px 80px;
            position: relative;
            z-index: 1;
        }

        /* ── PAGE HEADER ── */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 40px;
            gap: 20px;
            flex-wrap: wrap;
        }
        .page-header-left h1 {
            font-size: 2.4rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            line-height: 1.1;
        }
        .page-header-left h1 span { color: var(--accent); }
        .page-header-left p {
            margin-top: 8px;
            color: var(--text-dim);
            font-size: 1rem;
            font-weight: 500;
        }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 14px;
            cursor: pointer;
            border: none;
            font-family: inherit;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
            white-space: nowrap;
        }
        .btn-primary {
            background: var(--gradient);
            color: #000;
            box-shadow: 0 4px 20px rgba(255,215,0,0.22);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px var(--accent-glow);
        }
        .btn-ghost {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.07);
            color: #ccc;
        }
        .btn-ghost:hover {
            background: rgba(255,255,255,0.08);
            border-color: var(--accent);
            color: var(--accent);
        }
        .btn-danger {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #f87171;
        }
        .btn-danger:hover {
            background: rgba(239,68,68,0.2);
            border-color: var(--danger);
        }
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
            border-radius: 10px;
        }

        /* ── STATS ROW ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 44px;
        }
        .stat-card {
            background: var(--panel);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 28px 32px;
            transition: transform 0.3s, border-color 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255,215,0,0.25);
        }
        .stat-value {
            font-size: 3rem;
            font-weight: 900;
            font-family: 'Orbitron', sans-serif;
            color: var(--accent);
            line-height: 1;
            margin-bottom: 6px;
        }
        .stat-value.purple { color: var(--purple); }
        .stat-value.green  { color: var(--success); }
        .stat-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        /* ── SECTION HEADING ── */
        .section-heading {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }
        .section-heading-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, rgba(255,215,0,0.15), transparent);
        }
        .section-heading-text {
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--text-dim);
        }

        /* ── RECORDS GRID ── */
        .records-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        /* ── RECORD CARD ── */
        .record-card {
            background: var(--card);
            border: 1px solid rgba(255,255,255,0.055);
            border-radius: 20px;
            padding: 26px;
            display: flex;
            flex-direction: column;
            gap: 0;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            position: relative;
            overflow: hidden;
        }
        .record-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 20px;
            background: var(--accent-soft);
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        .record-card:hover {
            border-color: rgba(255,215,0,0.2);
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5), 0 0 30px rgba(255,215,0,0.05);
        }
        .record-card:hover::before { opacity: 1; }

        .record-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .record-store {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: var(--text-dim);
            font-weight: 600;
            margin-bottom: 16px;
        }
        .record-store-icon {
            width: 18px;
            height: 18px;
            border-radius: 5px;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            flex-shrink: 0;
        }

        /* Tags */
        .record-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .tag {
            font-size: 0.7rem;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 6px;
            letter-spacing: 0.5px;
        }
        .tag-theme {
            background: rgba(124,58,237,0.15);
            color: #a78bfa;
            border: 1px solid rgba(124,58,237,0.25);
        }
        .tag-gold {
            background: rgba(255,215,0,0.12);
            color: #fbbf24;
            border: 1px solid rgba(255,215,0,0.2);
        }
        .tag-black {
            background: rgba(255,255,255,0.06);
            color: #aaa;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .tag-product {
            background: rgba(16,185,129,0.12);
            color: #34d399;
            border: 1px solid rgba(16,185,129,0.2);
        }

        /* Time */
        .record-time {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.75rem;
            color: var(--text-dim);
            font-weight: 500;
            margin-bottom: 20px;
        }
        .record-time svg { width: 13px; height: 13px; opacity: 0.6; }

        /* Actions */
        .record-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            grid-column: 1 / -1;
        }
        .empty-icon {
            font-size: 60px;
            margin-bottom: 20px;
            filter: grayscale(0.4) opacity(0.6);
        }
        .empty-state h3 {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: #ddd;
        }
        .empty-state p {
            color: var(--text-dim);
            margin-bottom: 28px;
            font-size: 0.95rem;
            line-height: 1.6;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ── FLASH ── */
        .flash {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(16,185,129,0.12);
            border: 1px solid rgba(16,185,129,0.25);
            color: #34d399;
            border-radius: 14px;
            padding: 14px 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 28px;
            animation: slideDown 0.4s ease-out;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .stats-row { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; }
            .main { padding: 32px 20px 60px; }
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <!-- NAV -->
    <nav>
        <div class="nav-inner">
            <a href="{{ route('selection') }}" class="brand">
                <span class="brand-bolt"><i class="fa-solid fa-bolt"></i></span>
                <span class="brand-text">ETSY <span>PDF LAB</span></span>
            </a>
            <a href="{{ route('selection') }}" class="nav-back">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back to Studio
            </a>
        </div>
    </nav>

    <div class="main">

        <!-- Flash Message -->
        @if(session('success'))
        <div class="flash">
            <span><i class="fa-solid fa-circle-check"></i></span>
            {{ session('success') }}
        </div>
        @endif

        <!-- PAGE HEADER -->
        <div class="page-header">
            <div class="page-header-left">
                <h1>Your <span>PDF Records</span> <i class="fa-solid fa-file-pdf"></i></h1>
                <p>Review saved PDFs, edit them, or create a new one</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="{{ route('pdf.create', ['preset' => 'thor']) }}" class="btn btn-primary">
                    <i class="fa-solid fa-bolt"></i> New PDF (ThorPresets)
                </a>
                <a href="{{ route('pdf.create', ['preset' => 'drdoom']) }}" class="btn btn-primary" style="background: linear-gradient(135deg, #FF6B6B 0%, #C92A2A 100%); box-shadow: 0 4px 20px rgba(201,42,42,0.22); color: #fff;">
                    <i class="fa-solid fa-skull"></i> New PDF (DrDOOMARTS)
                </a>
            </div>
        </div>

        <!-- STATS -->
        @php
            $totalPdfs  = $records->count();
            $goldCount  = $records->where('theme', 'gold-style')->count();
            $totalProds = $records->sum(fn($r) => is_array($r->products) ? count($r->products) : 0);
        @endphp
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value">{{ $totalPdfs }}</div>
                <div class="stat-label">Total PDFs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value purple">{{ $goldCount }}</div>
                <div class="stat-label"><i class="fa-solid fa-star" aria-hidden="true" style="color:#fbbf24;"></i> Gold Theme</div>
            </div>
            <div class="stat-card">
                <div class="stat-value green">{{ $totalProds }}</div>
                <div class="stat-label">Total Products</div>
            </div>
        </div>

        <!-- RECORDS SECTION -->
        <div class="section-heading">
            <span class="section-heading-text">Your Saved PDFs</span>
            <div class="section-heading-line"></div>
        </div>

        <div class="records-grid">
            @forelse($records as $rec)
            @php
                $themeClass = match($rec->theme) {
                    'gold-style'   => 'tag-gold',
                    'purple-style' => 'tag-theme',
                    'black-style'  => 'tag-black',
                    default        => 'tag-black',
                };
                $themeLabel = str_replace('-style', '', $rec->theme);
                $prodCount  = is_array($rec->products) ? count($rec->products) : 0;
            @endphp
            <div class="record-card">
                <div class="record-title">{{ $rec->title }}</div>
                <div class="record-store">
                    <div class="record-store-icon"><i class="fa-solid fa-store"></i></div>
                    {{ $rec->store_name }}
                </div>

                <div class="record-tags">
                    <span class="tag {{ $themeClass }}">{{ $themeLabel }}</span>
                    <span class="tag tag-product"><i class="fa-solid fa-layer-group"></i> {{ $prodCount }} Product{{ $prodCount != 1 ? 's' : '' }}</span>
                </div>

                <div class="record-time">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $rec->created_at->diffForHumans() }}
                </div>

                <div class="record-actions">
                    <a href="{{ route('pdf.preview', $rec->id) }}" class="btn btn-ghost btn-sm" target="_blank"><i class="fa-solid fa-eye"></i> Preview</a>
                    <a href="{{ route('pdf.edit', $rec->id) }}" class="btn btn-ghost btn-sm" style="border-color: rgba(255,215,0,0.2); color: var(--accent);"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                    <form method="POST" action="{{ route('pdf.delete', $rec->id) }}" onsubmit="return confirm('Delete this PDF record?');" style="margin:0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Delete</button>
                    </form>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                <h3>No PDF Records Found</h3>
                <p>You have not created any PDFs yet. Start a new PDF and prepare professional listing assets for your Etsy store.</p>
                <div style="display: flex; gap: 12px; justify-content: center; margin-top: 15px;">
                    <a href="{{ route('pdf.create', ['preset' => 'thor']) }}" class="btn btn-primary"><i class="fa-solid fa-bolt"></i> Create First PDF (ThorPresets)</a>
                    <a href="{{ route('pdf.create', ['preset' => 'drdoom']) }}" class="btn btn-primary" style="background: linear-gradient(135deg, #FF6B6B 0%, #C92A2A 100%); color: #fff;"><i class="fa-solid fa-skull"></i> Create First PDF (DrDOOMARTS)</a>
                </div>
            </div>
            @endforelse
        </div>

    </div>
</body>
</html>

