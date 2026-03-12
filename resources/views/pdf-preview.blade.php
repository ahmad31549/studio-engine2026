<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Preview — {{ $record->store_name }} PDF</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Orbitron:wght@700;900&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        :root {
            --accent: #FFD700;
            --accent-glow: rgba(255, 215, 0, 0.4);
            --bg: #050507;
            --panel: #0d0d10;
            --card-border: rgba(255, 215, 0, 0.1);
            --text: #ffffff;
            --text-dim: #7777a0;
            --gradient: linear-gradient(135deg, #FFD700 0%, #FF8C00 100%);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .orb {
            position: fixed;
            width: 700px;
            height: 700px;
            border-radius: 50%;
            filter: blur(140px);
            z-index: -1;
            pointer-events: none;
            opacity: 0.1;
        }

        .orb-1 {
            top: -250px;
            right: -200px;
            background: #FFD700;
        }

        .orb-2 {
            bottom: -250px;
            left: -200px;
            background: #7C3AED;
        }

        nav {
            height: 72px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            display: flex;
            align-items: center;
            padding: 0 36px;
            background: rgba(5, 5, 7, 0.92);
            backdrop-filter: blur(24px);
            position: sticky;
            top: 0;
            z-index: 1000;
            justify-content: space-between;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand {
            font-family: 'Orbitron', sans-serif;
            font-size: 17px;
            font-weight: 900;
            letter-spacing: 3px;
        }

        .brand span {
            color: var(--accent);
        }

        .nav-badge {
            background: rgba(255, 215, 0, 0.1);
            color: var(--accent);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            font-size: 10px;
            font-weight: 900;
            padding: 4px 14px;
            letter-spacing: 1.5px;
        }

        .nav-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 11px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all 0.3s;
            text-decoration: none;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--gradient);
            color: #000;
            box-shadow: 0 4px 20px rgba(255, 215, 0, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px var(--accent-glow);
        }

        .btn-ghost {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.07);
            color: #fff;
        }

        .btn-ghost:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .preview-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 24px 80px;
            gap: 20px;
        }

        .preview-meta {
            text-align: center;
        }

        .preview-meta h2 {
            font-size: 22px;
            font-weight: 900;
            margin-bottom: 6px;
        }

        .preview-meta p {
            font-size: 13px;
            color: var(--text-dim);
        }

        #preview-outer {
            width: 100%;
            display: flex;
            justify-content: center;
            overflow: hidden;
            background: transparent;
        }

        #preview-canvas {
            width: 794px;
            background: #fff;
            transform-origin: top center;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.7);
            border-radius: 4px;
        }

        /* ======= PDF STYLES ======= */
        .pdf-page {
            width: 794px;
            min-height: 1123px;
            background: #fff;
            color: #111;
            padding: 40px 50px;
            display: flex;
            flex-direction: column;
            font-family: 'Outfit', sans-serif;
        }

        .pdf-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            margin-bottom: 24px;
            border-bottom: 2px solid #f0f0f0;
        }

        .pdf-logo-wrap {
            display: flex;
            flex-direction: column;
        }

        .pdf-logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 1px;
            color: #111;
        }

        .pdf-logo span {
            color: #d4a000;
        }

        .pdf-logo-tagline {
            font-size: 10px;
            font-weight: 700;
            color: #aaa;
            letter-spacing: 2px;
            margin-top: 3px;
        }

        .pdf-header-badge {
            background: linear-gradient(135deg, #111, #222);
            border: 1px solid rgba(212, 160, 0, 0.3);
            border-radius: 12px;
            padding: 10px 20px;
            text-align: right;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .pdf-header-badge::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(45deg, transparent 48%, rgba(212, 160, 0, 0.1) 50%, transparent 52%);
            background-size: 200% 200%;
            animation: shine 3s infinite linear;
        }

        @keyframes shine {
            from { background-position: -100% -100%; }
            to { background-position: 100% 100%; }
        }

        .pdf-badge-line1 {
            font-size: 8px;
            font-weight: 950;
            color: #D4A000;
            letter-spacing: 2.5px;
            text-transform: uppercase;
        }

        .pdf-badge-line2 {
            font-size: 13px;
            font-weight: 900;
            color: #fff;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }

        .pdf-hero {
            background: #000;
            color: #fff;
            border-radius: 24px;
            padding: 30px 32px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(212, 160, 0, 0.2);
            box-shadow: 0 18px 42px rgba(0, 0, 0, 0.16);
        }

        .pdf-hero::before {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: 
                radial-gradient(circle at 100% 0%, rgba(212, 160, 0, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 0% 100%, rgba(212, 160, 0, 0.05) 0%, transparent 50%);
            z-index: 1;
        }

        .pdf-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(115deg, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0) 38%);
            opacity: 0.45;
            pointer-events: none;
            z-index: 1;
        }

        .pdf-hero-layout {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .pdf-hero-top {
            display: flex;
            align-items: flex-start;
            gap: 18px;
        }

        .pdf-hero-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.16),
                0 16px 28px rgba(0, 0, 0, 0.12);
            color: inherit;
        }

        .pdf-hero-emoji {
            font-size: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            color: inherit;
        }

        .pdf-hero-copy {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 0;
        }

        .pdf-hero-kicker {
            display: inline-flex;
            align-items: center;
            align-self: flex-start;
            min-height: 32px;
            padding: 0 13px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.18);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: inherit;
            opacity: 0.86;
        }

        .pdf-hero-title {
            font-size: 29px;
            font-weight: 900;
            color: #fff;
            letter-spacing: -0.04em;
            line-height: 1.04;
            margin: 0;
            max-width: 560px;
            white-space: normal;
        }

        .pdf-hero-msg {
            font-size: 13.5px;
            line-height: 1.72;
            color: rgba(255,255,255,0.7);
            font-weight: 500;
            max-width: 560px;
            margin: 0;
        }

        .pdf-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .pdf-hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 36px;
            padding: 0 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.16);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.14);
            color: inherit;
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.9;
        }

        .pdf-hero-pill i {
            font-size: 11px;
        }

        .pdf-page.gold-style:not(.artist-edition):not(.dark-mode) .pdf-header-badge {
            background: linear-gradient(135deg, #fff7cf, #ffe39b);
            border-color: rgba(212, 160, 0, 0.36);
            box-shadow: 0 10px 24px rgba(212, 160, 0, 0.16);
        }

        .pdf-page.gold-style:not(.artist-edition):not(.dark-mode) .pdf-header-badge::before {
            background: linear-gradient(45deg, transparent 46%, rgba(255, 255, 255, 0.34) 50%, transparent 54%);
            opacity: 0.75;
        }

        .pdf-page.gold-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line1,
        .pdf-page.gold-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line2 {
            position: relative;
            z-index: 1;
        }

        .pdf-page.gold-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line1 {
            color: #916600;
        }

        .pdf-page.gold-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line2 {
            color: #382600;
        }

        .pdf-page.gold-style:not(.artist-edition):not(.dark-mode) .pdf-hero {
            background: linear-gradient(135deg, #fff6d5, #ffe5a8);
            color: #2c2206;
            border-color: rgba(212, 160, 0, 0.32);
            box-shadow: 0 14px 34px rgba(212, 160, 0, 0.16);
        }

        .pdf-page.gold-style:not(.artist-edition):not(.dark-mode) .pdf-hero::before {
            background:
                radial-gradient(circle at 100% 0%, rgba(212, 160, 0, 0.18) 0%, transparent 48%),
                radial-gradient(circle at 0% 100%, rgba(255, 255, 255, 0.34) 0%, transparent 48%);
        }

        .pdf-page.gold-style:not(.artist-edition):not(.dark-mode) .pdf-hero-title {
            color: #352400;
        }

        .pdf-page.gold-style:not(.artist-edition):not(.dark-mode) .pdf-hero-msg {
            color: rgba(53, 36, 0, 0.76);
        }

        .pdf-page.rose-style:not(.artist-edition):not(.dark-mode) .pdf-header-badge {
            background: linear-gradient(135deg, #fff1f5, #ffd9df);
            border-color: rgba(255, 142, 162, 0.34);
            box-shadow: 0 10px 24px rgba(255, 142, 162, 0.14);
        }

        .pdf-page.rose-style:not(.artist-edition):not(.dark-mode) .pdf-header-badge::before {
            background: linear-gradient(45deg, transparent 46%, rgba(255, 255, 255, 0.32) 50%, transparent 54%);
            opacity: 0.7;
        }

        .pdf-page.rose-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line1,
        .pdf-page.rose-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line2 {
            position: relative;
            z-index: 1;
        }

        .pdf-page.rose-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line1 {
            color: #b04b67;
        }

        .pdf-page.rose-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line2 {
            color: #54212f;
        }

        .pdf-page.rose-style:not(.artist-edition):not(.dark-mode) .pdf-hero {
            background: linear-gradient(135deg, #fff2f5, #ffdce5);
            color: #4c2431;
            border-color: rgba(255, 142, 162, 0.32);
            box-shadow: 0 14px 34px rgba(255, 142, 162, 0.14);
        }

        .pdf-page.rose-style:not(.artist-edition):not(.dark-mode) .pdf-hero::before {
            background:
                radial-gradient(circle at 100% 0%, rgba(255, 142, 162, 0.18) 0%, transparent 48%),
                radial-gradient(circle at 0% 100%, rgba(255, 176, 107, 0.18) 0%, transparent 48%);
        }

        .pdf-page.rose-style:not(.artist-edition):not(.dark-mode) .pdf-hero-title {
            color: #571f2f;
        }

        .pdf-page.rose-style:not(.artist-edition):not(.dark-mode) .pdf-hero-msg {
            color: rgba(87, 31, 47, 0.78);
        }

        .pdf-page.aqua-style:not(.artist-edition):not(.dark-mode) .pdf-header-badge {
            background: linear-gradient(135deg, #e9fffd, #c9eeff);
            border-color: rgba(88, 223, 208, 0.34);
            box-shadow: 0 10px 24px rgba(88, 223, 208, 0.14);
        }

        .pdf-page.aqua-style:not(.artist-edition):not(.dark-mode) .pdf-header-badge::before {
            background: linear-gradient(45deg, transparent 46%, rgba(255, 255, 255, 0.34) 50%, transparent 54%);
            opacity: 0.7;
        }

        .pdf-page.aqua-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line1,
        .pdf-page.aqua-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line2 {
            position: relative;
            z-index: 1;
        }

        .pdf-page.aqua-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line1 {
            color: #117b82;
        }

        .pdf-page.aqua-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line2 {
            color: #153853;
        }

        .pdf-page.aqua-style:not(.artist-edition):not(.dark-mode) .pdf-hero {
            background: linear-gradient(135deg, #ecfffd, #d5f4ff);
            color: #103647;
            border-color: rgba(88, 223, 208, 0.3);
            box-shadow: 0 14px 34px rgba(94, 166, 255, 0.12);
        }

        .pdf-page.aqua-style:not(.artist-edition):not(.dark-mode) .pdf-hero::before {
            background:
                radial-gradient(circle at 100% 0%, rgba(88, 223, 208, 0.16) 0%, transparent 48%),
                radial-gradient(circle at 0% 100%, rgba(94, 166, 255, 0.18) 0%, transparent 48%);
        }

        .pdf-page.aqua-style:not(.artist-edition):not(.dark-mode) .pdf-hero-title {
            color: #103d56;
        }

        .pdf-page.aqua-style:not(.artist-edition):not(.dark-mode) .pdf-hero-msg {
            color: rgba(16, 61, 86, 0.76);
        }

        .pdf-page.purple-style:not(.artist-edition):not(.dark-mode) .pdf-header-badge {
            background: linear-gradient(135deg, #f4ebff, #e1d2ff);
            border-color: rgba(124, 58, 237, 0.3);
            box-shadow: 0 10px 24px rgba(124, 58, 237, 0.14);
        }

        .pdf-page.purple-style:not(.artist-edition):not(.dark-mode) .pdf-header-badge::before {
            background: linear-gradient(45deg, transparent 46%, rgba(255, 255, 255, 0.34) 50%, transparent 54%);
            opacity: 0.72;
        }

        .pdf-page.purple-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line1,
        .pdf-page.purple-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line2 {
            position: relative;
            z-index: 1;
        }

        .pdf-page.purple-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line1 {
            color: #6b35bf;
        }

        .pdf-page.purple-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line2 {
            color: #2e175c;
        }

        .pdf-page.purple-style:not(.artist-edition):not(.dark-mode) .pdf-hero {
            background: linear-gradient(135deg, #f4edff, #e5d9ff);
            color: #2b1c54;
            border-color: rgba(124, 58, 237, 0.28);
            box-shadow: 0 14px 34px rgba(124, 58, 237, 0.14);
        }

        .pdf-page.purple-style:not(.artist-edition):not(.dark-mode) .pdf-hero::before {
            background:
                radial-gradient(circle at 100% 0%, rgba(124, 58, 237, 0.16) 0%, transparent 48%),
                radial-gradient(circle at 0% 100%, rgba(176, 140, 255, 0.18) 0%, transparent 48%);
        }

        .pdf-page.purple-style:not(.artist-edition):not(.dark-mode) .pdf-hero-title {
            color: #2f185e;
        }

        .pdf-page.purple-style:not(.artist-edition):not(.dark-mode) .pdf-hero-msg {
            color: rgba(47, 24, 94, 0.78);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-header-badge {
            background: linear-gradient(135deg, #f4f0e9, #ddd4c7);
            border-color: rgba(35, 31, 27, 0.18);
            box-shadow: 0 10px 24px rgba(24, 22, 19, 0.1);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-header-badge::before {
            background: linear-gradient(45deg, transparent 46%, rgba(255, 255, 255, 0.28) 50%, transparent 54%);
            opacity: 0.5;
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line1,
        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line2 {
            position: relative;
            z-index: 1;
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line1 {
            color: #8b6a2a;
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-badge-line2 {
            color: #1d1d22;
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-hero {
            background: linear-gradient(135deg, #f6f3ed, #dfd8ce);
            color: #1d1d22;
            border-color: rgba(34, 31, 27, 0.16);
            box-shadow: 0 14px 34px rgba(27, 24, 20, 0.1);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-hero::before {
            background:
                radial-gradient(circle at 100% 0%, rgba(186, 146, 74, 0.14) 0%, transparent 48%),
                radial-gradient(circle at 0% 100%, rgba(255, 255, 255, 0.28) 0%, transparent 48%);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-hero-title {
            color: #191a1f;
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-hero-msg {
            color: rgba(25, 26, 31, 0.76);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-download-card.black-style,
        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .black-style {
            background: linear-gradient(135deg, #f6f3ed, #e2dcd2);
            border: 1px solid rgba(34, 31, 27, 0.12);
            border-left: 5px solid #b58a34;
            color: #16171b;
            box-shadow: 0 12px 28px rgba(20, 18, 16, 0.08);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-download-card.black-style .card-icon-wrap,
        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .black-style .card-icon-wrap {
            background: linear-gradient(135deg, #23242a, #111216);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.04);
            box-shadow: 0 10px 22px rgba(17, 18, 22, 0.16);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-download-card.black-style .card-num,
        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .black-style .card-num {
            color: #9b793d;
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-download-card.black-style .card-name,
        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .black-style .card-name {
            color: #18191d;
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-download-card.black-style .card-sub,
        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .black-style .card-sub {
            color: rgba(24, 25, 29, 0.64);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-download-card.black-style .pdf-dl-btn,
        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .black-style .pdf-dl-btn {
            background: linear-gradient(135deg, #23242a, #111216);
            color: #fff;
            box-shadow: 0 10px 20px rgba(17, 18, 22, 0.16);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-how-section {
            background: linear-gradient(135deg, #f7f4ef, #ebe4d9);
            border: 1px solid rgba(34, 31, 27, 0.1);
            box-shadow: 0 10px 24px rgba(20, 18, 16, 0.05);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-how-title {
            color: #847d73;
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-step-title {
            color: #18191d;
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-step-desc {
            color: rgba(24, 25, 29, 0.66);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-step-num {
            background: linear-gradient(135deg, #23242a, #111216);
            color: #fff;
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-footer {
            border-top-color: rgba(34, 31, 27, 0.1);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-footer-store {
            color: #18191d;
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-footer-link {
            color: rgba(24, 25, 29, 0.46);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-review-cta {
            background: linear-gradient(135deg, #23242a, #111216);
            color: #fff;
        }

        .pdf-section-label {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }

        .pdf-section-label span {
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 2px;
            color: #888;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .pdf-section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #eee;
        }

        /* Download Cards */
        .pdf-download-card {
            display: flex;
            align-items: center;
            gap: 18px;
            border-radius: 16px;
            padding: 18px 22px;
            margin-bottom: 12px;
            border: 1.5px solid;
            position: relative;
            overflow: hidden;
        }

        .gold-style {
            background: linear-gradient(to right, #ffffff, #fffdfa);
            border: 1px solid #e0e0e0;
            border-left: 5px solid #D4A000;
            transition: all 0.3s;
        }

        .gold-style .card-icon-wrap {
            background: linear-gradient(135deg, #fff0b1, #f2c94c);
            color: #3b2b00;
            border-radius: 12px;
            border: 1px solid rgba(212, 160, 0, 0.16);
            box-shadow: 0 10px 22px rgba(212, 160, 0, 0.18);
        }

        .gold-style .pdf-dl-btn {
            background: #D4A000;
            color: #fff;
            box-shadow: 0 4px 12px rgba(212, 160, 0, 0.2);
        }

        .gold-style .card-num {
            color: #D4A000;
        }

        .purple-style {
            background: linear-gradient(135deg, #fdfbff, #f5f0ff);
            border-color: #c4a8f0;
        }

        .purple-style .card-icon-wrap {
            background: linear-gradient(135deg, #7C3AED, #5B21B6);
        }

        .purple-style .pdf-dl-btn {
            background: linear-gradient(135deg, #7C3AED, #5B21B6);
            color: #fff;
        }

        .purple-style .card-num {
            color: #7C3AED;
        }

        .pdf-download-card.rose-style {
            background: linear-gradient(135deg, #fff9fb, #fff1f5);
            border-color: #f2bfd0;
            border-left: 5px solid #ff8ea2;
        }

        .pdf-download-card.rose-style .card-icon-wrap {
            background: linear-gradient(135deg, #ff8ea2, #ffb06b);
            color: #2a1214;
        }

        .pdf-download-card.rose-style .pdf-dl-btn {
            background: linear-gradient(135deg, #ff8ea2, #ffb06b);
            color: #fff;
        }

        .pdf-download-card.rose-style .card-num {
            color: #ff8ea2;
        }

        .pdf-download-card.aqua-style {
            background: linear-gradient(135deg, #f5fffe, #eef7ff);
            border-color: #b5e7ea;
            border-left: 5px solid #58dfd0;
        }

        .pdf-download-card.aqua-style .card-icon-wrap {
            background: linear-gradient(135deg, #58dfd0, #5ea6ff);
            color: #0d1a24;
        }

        .pdf-download-card.aqua-style .pdf-dl-btn {
            background: linear-gradient(135deg, #58dfd0, #5ea6ff);
            color: #0b1722;
        }

        .pdf-download-card.aqua-style .card-num {
            color: #1ea6b6;
        }

        .black-style {
            background: linear-gradient(135deg, #f8f8f8, #f0f0f0);
            border-color: #ccc;
        }

        .black-style .card-icon-wrap {
            background: linear-gradient(135deg, #1a1a1a, #111);
        }

        .black-style .pdf-dl-btn {
            background: linear-gradient(135deg, #1a1a1a, #111);
            color: #fff;
        }

        .black-style .card-num {
            color: #333;
        }

        .card-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .card-body {
            flex: 1;
        }

        .card-num {
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .card-name {
            font-size: 15px;
            font-weight: 900;
            color: #111;
            margin-bottom: 3px;
        }

        .card-sub {
            font-size: 10px;
            color: #888;
            font-weight: 500;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .pdf-dl-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 20px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 12px;
            text-decoration: none;
            letter-spacing: 0.3px;
        }

        /* How to Use */
        .pdf-how-section {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 20px;
            padding: 30px 36px;
            margin-top: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.02);
        }

        .pdf-how-title {
            font-size: 10px;
            font-weight: 900;
            color: #888;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .pdf-steps {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .pdf-step {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .pdf-step-num {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #111;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 900;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .pdf-step-num.gold {
            background: linear-gradient(135deg, #D4A000, #B88800);
        }

        .pdf-step-num.purple {
            background: linear-gradient(135deg, #7C3AED, #5B21B6);
        }

        .pdf-step-num.rose {
            background: linear-gradient(135deg, #ff8ea2, #ffb06b);
        }

        .pdf-step-num.aqua {
            background: linear-gradient(135deg, #58dfd0, #5ea6ff);
            color: #08121a;
        }

        .pdf-step-body {}

        .pdf-step-title {
            font-size: 12px;
            font-weight: 800;
            color: #111;
            margin-bottom: 1px;
        }

        .pdf-step-desc {
            font-size: 11px;
            color: #777;
            line-height: 1.5;
            font-weight: 500;
        }

        .pdf-footer {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1.5px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pdf-footer-store {
            font-size: 13px;
            font-weight: 900;
            color: #111;
            margin-bottom: 2px;
        }

        .pdf-footer-link {
            font-size: 10px;
            color: #bbb;
            font-weight: 600;
        }

        .pdf-footer-right {
            text-align: right;
        }

        .pdf-stars {
            font-size: 14px;
            margin-bottom: 3px;
        }

        .pdf-review-cta {
            background: linear-gradient(135deg, #D4A000, #B88800);
            color: #fff;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 1.5px;
            padding: 5px 14px;
            border-radius: 20px;
            display: inline-block;
        }

        /* ══ PDF DARK MODE ══ */
        .pdf-page.dark-mode {
            background: #111113;
            color: #e0e0e0;
        }

        .pdf-page.dark-mode .pdf-header {
            border-bottom-color: #2a2a2e;
        }

        .pdf-page.dark-mode .pdf-logo {
            color: #fff;
        }

        .pdf-page.dark-mode .pdf-logo span {
            color: #FFD700;
        }

        .pdf-page.dark-mode .pdf-logo-tagline {
            color: #666;
        }

        .pdf-page.dark-mode .pdf-header-badge {
            background: linear-gradient(135deg, #1e1a0a, #23200e);
            border-color: #5a4e00;
        }

        .pdf-page.dark-mode .pdf-badge-line1 {
            color: #c8a400;
        }

        .pdf-page.dark-mode .pdf-badge-line2 {
            color: #FFD700;
        }

        .pdf-page.dark-mode .pdf-hero {
            background: linear-gradient(135deg, #1a1800, #1e1b05, #23200a);
            border-color: #5a4e00;
        }

        .pdf-page.dark-mode .pdf-hero::before {
            background: radial-gradient(circle, rgba(255, 215, 0, 0.08), transparent 70%);
        }

        .pdf-page.dark-mode .pdf-hero::after {
            color: rgba(255, 200, 0, 0.04);
        }

        .pdf-page.dark-mode .pdf-hero-title {
            color: #f0e6c0;
        }

        .pdf-page.dark-mode .pdf-hero-msg {
            color: #999;
        }

        .pdf-page.dark-mode .pdf-section-label span {
            color: #666;
        }

        .pdf-page.dark-mode .pdf-section-label::after {
            background: #2a2a2e;
        }

        .pdf-page.dark-mode .pdf-download-card.gold-style {
            background: linear-gradient(135deg, #1a1800, #1e1b05);
            border-color: #5a4e00;
        }

        .pdf-page.dark-mode .pdf-download-card.purple-style {
            background: linear-gradient(135deg, #15101f, #1a1428);
            border-color: #4a3580;
        }

        .pdf-page.dark-mode .pdf-download-card.rose-style {
            background: linear-gradient(135deg, #26141b, #21131d);
            border-color: #7d4358;
        }

        .pdf-page.dark-mode .pdf-download-card.aqua-style {
            background: linear-gradient(135deg, #102126, #10202d);
            border-color: #2f6e7a;
        }

        .pdf-page.dark-mode .pdf-download-card.black-style {
            background: linear-gradient(135deg, #1a1a1a, #151515);
            border-color: #333;
        }

        .pdf-page.dark-mode .card-name {
            color: #eee;
        }

        .pdf-page.dark-mode .card-sub {
            color: #777;
        }

        .pdf-page.dark-mode .pdf-how-section {
            background: #161618;
            border-color: #2a2a2e;
        }

        .pdf-page.dark-mode .pdf-how-title {
            color: #666;
        }

        .pdf-page.dark-mode .pdf-step-title {
            color: #ddd;
        }

        .pdf-page.dark-mode .pdf-step-desc {
            color: #888;
        }

        .pdf-page.dark-mode .pdf-footer {
            border-top-color: #2a2a2e;
        }

        .pdf-page.dark-mode .pdf-footer-store {
            color: #eee;
        }

        .pdf-page.dark-mode .pdf-footer-link {
            color: #666;
        }

        .pdf-page.artist-edition {
            --artist-glow-1: rgba(255, 152, 72, 0.34);
            --artist-glow-2: rgba(255, 93, 143, 0.18);
            --artist-glow-3: rgba(100, 224, 212, 0.14);
            --artist-page-base: linear-gradient(145deg, #160f15 0%, #120d14 52%, #0d1119 100%);
            --artist-text: #fcf7f1;
            --artist-header-border: rgba(255, 246, 238, 0.1);
            --artist-logo: #fffaf4;
            --artist-logo-accent: #ff8a5b;
            --artist-tagline: rgba(255, 236, 222, 0.7);
            --artist-badge-bg: linear-gradient(135deg, rgba(255, 138, 91, 0.28), rgba(255, 111, 143, 0.14), rgba(100, 224, 212, 0.12));
            --artist-badge-border: rgba(255, 210, 136, 0.32);
            --artist-badge-shadow: rgba(255, 138, 91, 0.16);
            --artist-badge-line1: #ffd989;
            --artist-badge-line2: #fffaf4;
            --artist-hero-bg: linear-gradient(140deg, rgba(51, 20, 34, 0.98), rgba(98, 41, 44, 0.96), rgba(28, 45, 67, 0.97));
            --artist-hero-border: rgba(255, 149, 103, 0.28);
            --artist-hero-shadow: rgba(8, 6, 14, 0.54);
            --artist-hero-glow-1: rgba(255, 138, 91, 0.3);
            --artist-hero-glow-2: rgba(100, 224, 212, 0.16);
            --artist-hero-glow-3: rgba(255, 111, 143, 0.1);
            --artist-title-color: #ffd5ad;
            --artist-hero-msg: rgba(255, 246, 238, 0.86);
            --artist-chip-1-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(255, 138, 91, 0.1));
            --artist-chip-1-border: rgba(255, 210, 136, 0.22);
            --artist-chip-2-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(100, 224, 212, 0.11));
            --artist-chip-2-border: rgba(100, 224, 212, 0.24);
            --artist-chip-3-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(255, 111, 143, 0.11));
            --artist-chip-3-border: rgba(255, 111, 143, 0.22);
            --artist-chip-label: rgba(255, 226, 202, 0.58);
            --artist-chip-text: #fffaf4;
            --artist-section-label: rgba(255, 226, 210, 0.72);
            --artist-divider: rgba(255, 255, 255, 0.1);
            --artist-card-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(255, 138, 91, 0.08), rgba(100, 224, 212, 0.04));
            --artist-card-border: rgba(255, 255, 255, 0.1);
            --artist-card-accent: #ff8a5b;
            --artist-card-shadow: rgba(9, 7, 14, 0.28);
            --artist-icon-bg: linear-gradient(135deg, #ff8a5b, #ffcf70);
            --artist-icon-text: #1a120f;
            --artist-icon-shadow: rgba(255, 138, 91, 0.28);
            --artist-card-num: rgba(255, 220, 197, 0.7);
            --artist-card-name: #fffaf4;
            --artist-card-sub: rgba(255, 242, 234, 0.82);
            --artist-card-meta: rgba(255, 220, 197, 0.54);
            --artist-cta-bg: linear-gradient(135deg, #ff8458 0%, #ffb25d 52%, #ffd36f 100%);
            --artist-cta-text: #1a120f;
            --artist-cta-shadow: rgba(255, 138, 91, 0.24);
            --artist-how-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.06), rgba(255, 138, 91, 0.08), rgba(100, 224, 212, 0.05));
            --artist-how-border: rgba(255, 210, 136, 0.18);
            --artist-how-title: rgba(255, 232, 214, 0.68);
            --artist-step-bg: linear-gradient(135deg, #ffd36f, #ff8a5b, #ff6f8f);
            --artist-step-text: #1a120f;
            --artist-step-title: #fff9f3;
            --artist-step-desc: rgba(255, 242, 234, 0.78);
            --artist-footer-border: rgba(255, 255, 255, 0.1);
            --artist-footer-store: #fffaf4;
            --artist-footer-link: rgba(255, 231, 214, 0.56);
            --artist-stars: #ffdc96;
            --artist-review-bg: linear-gradient(135deg, rgba(255, 138, 91, 0.16), rgba(255, 111, 143, 0.12));
            --artist-review-text: #ffe3cd;
            --artist-review-border: rgba(255, 210, 136, 0.3);
            --artist-review-shadow: rgba(255, 138, 91, 0.08);
            background:
                radial-gradient(circle at top right, var(--artist-glow-1), transparent 32%),
                radial-gradient(circle at 10% 10%, var(--artist-glow-2), transparent 24%),
                radial-gradient(circle at 22% 100%, var(--artist-glow-3), transparent 28%),
                var(--artist-page-base);
            color: var(--artist-text);
        }

        .pdf-page.artist-edition.gold-style {
            --artist-glow-1: rgba(246, 200, 76, 0.32);
            --artist-glow-2: rgba(255, 173, 82, 0.14);
            --artist-glow-3: rgba(255, 244, 189, 0.12);
            --artist-page-base: linear-gradient(145deg, #191409 0%, #120f0c 54%, #0f1218 100%);
            --artist-logo-accent: #f6c84c;
            --artist-tagline: rgba(255, 242, 214, 0.72);
            --artist-badge-bg: linear-gradient(135deg, rgba(246, 200, 76, 0.24), rgba(255, 244, 189, 0.1), rgba(255, 173, 82, 0.12));
            --artist-badge-border: rgba(255, 220, 140, 0.34);
            --artist-badge-shadow: rgba(246, 200, 76, 0.16);
            --artist-badge-line1: #ffe38a;
            --artist-hero-bg: linear-gradient(140deg, rgba(52, 37, 14, 0.98), rgba(91, 60, 22, 0.96), rgba(25, 28, 42, 0.97));
            --artist-hero-border: rgba(246, 200, 76, 0.3);
            --artist-hero-glow-1: rgba(246, 200, 76, 0.28);
            --artist-hero-glow-2: rgba(255, 244, 189, 0.12);
            --artist-hero-glow-3: rgba(255, 173, 82, 0.1);
            --artist-title-color: #ffe17a;
            --artist-chip-1-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(246, 200, 76, 0.1));
            --artist-chip-1-border: rgba(255, 220, 140, 0.22);
            --artist-chip-2-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(255, 244, 189, 0.12));
            --artist-chip-2-border: rgba(255, 244, 189, 0.22);
            --artist-chip-3-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(255, 173, 82, 0.11));
            --artist-chip-3-border: rgba(255, 173, 82, 0.22);
            --artist-chip-label: rgba(255, 232, 191, 0.58);
            --artist-section-label: rgba(255, 236, 198, 0.74);
            --artist-card-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(246, 200, 76, 0.08), rgba(255, 173, 82, 0.05));
            --artist-card-accent: #f6c84c;
            --artist-icon-bg: linear-gradient(135deg, #ffe07f, #f6c84c);
            --artist-icon-text: #23180a;
            --artist-icon-shadow: rgba(246, 200, 76, 0.28);
            --artist-card-num: rgba(255, 234, 190, 0.72);
            --artist-card-meta: rgba(255, 231, 191, 0.54);
            --artist-cta-bg: linear-gradient(135deg, #f6c84c 0%, #ffb649 52%, #ffd56e 100%);
            --artist-cta-text: #20160d;
            --artist-cta-shadow: rgba(246, 200, 76, 0.24);
            --artist-how-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.06), rgba(246, 200, 76, 0.08), rgba(255, 244, 189, 0.05));
            --artist-how-border: rgba(255, 220, 140, 0.18);
            --artist-how-title: rgba(255, 234, 196, 0.68);
            --artist-step-bg: linear-gradient(135deg, #ffe07f, #f6c84c, #ffb649);
            --artist-footer-link: rgba(255, 236, 198, 0.56);
            --artist-stars: #ffe38a;
            --artist-review-bg: linear-gradient(135deg, rgba(246, 200, 76, 0.16), rgba(255, 182, 73, 0.12));
            --artist-review-text: #ffeac2;
            --artist-review-border: rgba(255, 220, 140, 0.3);
            --artist-review-shadow: rgba(246, 200, 76, 0.08);
        }

        .pdf-page.artist-edition.rose-style {
            --artist-glow-1: rgba(255, 148, 107, 0.3);
            --artist-glow-2: rgba(255, 120, 163, 0.18);
            --artist-glow-3: rgba(255, 196, 140, 0.12);
            --artist-page-base: linear-gradient(145deg, #191016 0%, #150e16 52%, #10121c 100%);
            --artist-logo-accent: #ff8ea2;
            --artist-tagline: rgba(255, 232, 227, 0.72);
            --artist-badge-bg: linear-gradient(135deg, rgba(255, 142, 162, 0.26), rgba(255, 176, 107, 0.12), rgba(255, 255, 255, 0.08));
            --artist-badge-border: rgba(255, 186, 158, 0.32);
            --artist-badge-shadow: rgba(255, 142, 162, 0.16);
            --artist-badge-line1: #ffd5c8;
            --artist-hero-bg: linear-gradient(140deg, rgba(61, 23, 34, 0.98), rgba(104, 41, 51, 0.95), rgba(44, 28, 53, 0.96));
            --artist-hero-border: rgba(255, 142, 162, 0.26);
            --artist-hero-glow-1: rgba(255, 142, 162, 0.28);
            --artist-hero-glow-2: rgba(255, 176, 107, 0.14);
            --artist-hero-glow-3: rgba(183, 132, 255, 0.1);
            --artist-title-color: #ffc2ce;
            --artist-chip-1-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(255, 142, 162, 0.11));
            --artist-chip-1-border: rgba(255, 186, 158, 0.22);
            --artist-chip-2-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(255, 176, 107, 0.11));
            --artist-chip-2-border: rgba(255, 176, 107, 0.22);
            --artist-chip-3-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(183, 132, 255, 0.1));
            --artist-chip-3-border: rgba(183, 132, 255, 0.2);
            --artist-chip-label: rgba(255, 220, 214, 0.58);
            --artist-section-label: rgba(255, 224, 219, 0.72);
            --artist-card-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(255, 142, 162, 0.08), rgba(255, 176, 107, 0.05));
            --artist-card-accent: #ff8ea2;
            --artist-icon-bg: linear-gradient(135deg, #ffb7c6, #ff8ea2, #ffb06b);
            --artist-icon-text: #2b1415;
            --artist-icon-shadow: rgba(255, 142, 162, 0.28);
            --artist-card-num: rgba(255, 222, 213, 0.7);
            --artist-card-meta: rgba(255, 220, 214, 0.54);
            --artist-cta-bg: linear-gradient(135deg, #ffb4c1 0%, #ff8ea2 52%, #ffb06b 100%);
            --artist-cta-text: #241111;
            --artist-cta-shadow: rgba(255, 142, 162, 0.24);
            --artist-how-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.06), rgba(255, 142, 162, 0.08), rgba(255, 176, 107, 0.05));
            --artist-how-border: rgba(255, 186, 158, 0.18);
            --artist-how-title: rgba(255, 225, 219, 0.68);
            --artist-step-bg: linear-gradient(135deg, #ffb4c1, #ff8ea2, #ffb06b);
            --artist-footer-link: rgba(255, 225, 219, 0.56);
            --artist-stars: #ffd1ad;
            --artist-review-bg: linear-gradient(135deg, rgba(255, 142, 162, 0.16), rgba(255, 176, 107, 0.12));
            --artist-review-text: #ffe1d8;
            --artist-review-border: rgba(255, 186, 158, 0.3);
            --artist-review-shadow: rgba(255, 142, 162, 0.08);
        }

        .pdf-page.artist-edition.aqua-style {
            --artist-glow-1: rgba(88, 223, 208, 0.3);
            --artist-glow-2: rgba(94, 166, 255, 0.18);
            --artist-glow-3: rgba(183, 255, 236, 0.12);
            --artist-page-base: linear-gradient(145deg, #0f1418 0%, #0d1118 52%, #0b1420 100%);
            --artist-logo-accent: #58dfd0;
            --artist-tagline: rgba(224, 246, 243, 0.72);
            --artist-badge-bg: linear-gradient(135deg, rgba(88, 223, 208, 0.24), rgba(94, 166, 255, 0.12), rgba(255, 255, 255, 0.08));
            --artist-badge-border: rgba(140, 239, 233, 0.3);
            --artist-badge-shadow: rgba(88, 223, 208, 0.16);
            --artist-badge-line1: #bafaf1;
            --artist-hero-bg: linear-gradient(140deg, rgba(18, 41, 47, 0.98), rgba(22, 63, 79, 0.96), rgba(20, 38, 69, 0.97));
            --artist-hero-border: rgba(88, 223, 208, 0.24);
            --artist-hero-glow-1: rgba(88, 223, 208, 0.28);
            --artist-hero-glow-2: rgba(94, 166, 255, 0.16);
            --artist-hero-glow-3: rgba(183, 255, 236, 0.1);
            --artist-title-color: #b7fff6;
            --artist-hero-msg: rgba(239, 255, 252, 0.86);
            --artist-chip-1-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(88, 223, 208, 0.1));
            --artist-chip-1-border: rgba(140, 239, 233, 0.22);
            --artist-chip-2-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(94, 166, 255, 0.1));
            --artist-chip-2-border: rgba(94, 166, 255, 0.22);
            --artist-chip-3-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(183, 255, 236, 0.1));
            --artist-chip-3-border: rgba(183, 255, 236, 0.2);
            --artist-chip-label: rgba(199, 244, 238, 0.58);
            --artist-section-label: rgba(208, 248, 242, 0.72);
            --artist-card-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(88, 223, 208, 0.08), rgba(94, 166, 255, 0.05));
            --artist-card-accent: #58dfd0;
            --artist-icon-bg: linear-gradient(135deg, #7af2e2, #58dfd0, #6bb7ff);
            --artist-icon-text: #0d1822;
            --artist-icon-shadow: rgba(88, 223, 208, 0.28);
            --artist-card-num: rgba(198, 245, 239, 0.72);
            --artist-card-meta: rgba(198, 245, 239, 0.54);
            --artist-cta-bg: linear-gradient(135deg, #7af2e2 0%, #58dfd0 52%, #6bb7ff 100%);
            --artist-cta-text: #0c1721;
            --artist-cta-shadow: rgba(88, 223, 208, 0.24);
            --artist-how-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.06), rgba(88, 223, 208, 0.08), rgba(94, 166, 255, 0.05));
            --artist-how-border: rgba(140, 239, 233, 0.18);
            --artist-how-title: rgba(206, 248, 242, 0.68);
            --artist-step-bg: linear-gradient(135deg, #7af2e2, #58dfd0, #6bb7ff);
            --artist-step-text: #09141d;
            --artist-footer-link: rgba(208, 248, 242, 0.56);
            --artist-stars: #bafaf1;
            --artist-review-bg: linear-gradient(135deg, rgba(88, 223, 208, 0.16), rgba(94, 166, 255, 0.12));
            --artist-review-text: #d8fffb;
            --artist-review-border: rgba(140, 239, 233, 0.28);
            --artist-review-shadow: rgba(88, 223, 208, 0.08);
        }

        .pdf-page.artist-edition.purple-style {
            --artist-glow-1: rgba(176, 140, 255, 0.3);
            --artist-glow-2: rgba(255, 130, 201, 0.18);
            --artist-glow-3: rgba(140, 118, 255, 0.12);
            --artist-page-base: linear-gradient(145deg, #16101d 0%, #130f1c 52%, #111423 100%);
            --artist-logo-accent: #b08cff;
            --artist-tagline: rgba(233, 224, 250, 0.72);
            --artist-badge-bg: linear-gradient(135deg, rgba(176, 140, 255, 0.24), rgba(255, 130, 201, 0.14), rgba(255, 255, 255, 0.08));
            --artist-badge-border: rgba(210, 182, 255, 0.3);
            --artist-badge-shadow: rgba(176, 140, 255, 0.16);
            --artist-badge-line1: #e3ceff;
            --artist-hero-bg: linear-gradient(140deg, rgba(40, 24, 61, 0.98), rgba(67, 31, 88, 0.96), rgba(31, 34, 73, 0.97));
            --artist-hero-border: rgba(176, 140, 255, 0.24);
            --artist-hero-glow-1: rgba(176, 140, 255, 0.28);
            --artist-hero-glow-2: rgba(255, 130, 201, 0.14);
            --artist-hero-glow-3: rgba(140, 118, 255, 0.1);
            --artist-title-color: #e2cfff;
            --artist-hero-msg: rgba(245, 239, 255, 0.84);
            --artist-chip-1-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(176, 140, 255, 0.1));
            --artist-chip-1-border: rgba(210, 182, 255, 0.22);
            --artist-chip-2-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(255, 130, 201, 0.1));
            --artist-chip-2-border: rgba(255, 130, 201, 0.22);
            --artist-chip-3-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.07), rgba(140, 118, 255, 0.1));
            --artist-chip-3-border: rgba(140, 118, 255, 0.22);
            --artist-chip-label: rgba(223, 210, 249, 0.58);
            --artist-section-label: rgba(228, 216, 252, 0.72);
            --artist-card-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(176, 140, 255, 0.08), rgba(255, 130, 201, 0.05));
            --artist-card-accent: #b08cff;
            --artist-icon-bg: linear-gradient(135deg, #d8c5ff, #b08cff, #ff9fde);
            --artist-icon-text: #1d1327;
            --artist-icon-shadow: rgba(176, 140, 255, 0.28);
            --artist-card-num: rgba(223, 210, 249, 0.72);
            --artist-card-meta: rgba(223, 210, 249, 0.54);
            --artist-cta-bg: linear-gradient(135deg, #d8c5ff 0%, #b08cff 52%, #ff9fde 100%);
            --artist-cta-text: #1a1223;
            --artist-cta-shadow: rgba(176, 140, 255, 0.24);
            --artist-how-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.06), rgba(176, 140, 255, 0.08), rgba(255, 130, 201, 0.05));
            --artist-how-border: rgba(210, 182, 255, 0.18);
            --artist-how-title: rgba(228, 216, 252, 0.68);
            --artist-step-bg: linear-gradient(135deg, #d8c5ff, #b08cff, #ff9fde);
            --artist-step-text: #1a1223;
            --artist-footer-link: rgba(228, 216, 252, 0.56);
            --artist-stars: #e3ceff;
            --artist-review-bg: linear-gradient(135deg, rgba(176, 140, 255, 0.16), rgba(255, 130, 201, 0.12));
            --artist-review-text: #eadcff;
            --artist-review-border: rgba(210, 182, 255, 0.28);
            --artist-review-shadow: rgba(176, 140, 255, 0.08);
        }

        .pdf-page.artist-edition.black-style {
            --artist-logo-accent: #ff8a5b;
        }

        .pdf-page.artist-edition .pdf-header {
            border-bottom-color: var(--artist-header-border);
            padding-bottom: 24px;
            margin-bottom: 28px;
        }

        .pdf-page.artist-edition .pdf-logo {
            font-family: 'Outfit', sans-serif;
            font-size: 19px;
            letter-spacing: 0.18em;
            color: var(--artist-logo);
        }

        .pdf-page.artist-edition .pdf-logo span {
            color: var(--artist-logo-accent);
        }

        .pdf-page.artist-edition .pdf-logo-tagline {
            color: var(--artist-tagline);
            letter-spacing: 0.22em;
        }

        .pdf-page.artist-edition .pdf-header-badge {
            background: var(--artist-badge-bg);
            border-color: var(--artist-badge-border);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05), 0 14px 34px var(--artist-badge-shadow);
        }

        .pdf-page.artist-edition .pdf-header-badge::before {
            display: none;
        }

        .pdf-page.artist-edition .pdf-badge-line1 {
            color: var(--artist-badge-line1);
            position: relative;
            z-index: 1;
            text-shadow: 0 1px 10px rgba(0, 0, 0, 0.16);
        }

        .pdf-page.artist-edition .pdf-badge-line2 {
            color: var(--artist-badge-line2);
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 14px rgba(0, 0, 0, 0.2);
        }

        .pdf-page.artist-edition .pdf-hero {
            background: var(--artist-hero-bg);
            border-color: var(--artist-hero-border);
            box-shadow: 0 28px 60px var(--artist-hero-shadow);
        }

        .pdf-page.artist-edition .pdf-hero::before {
            background:
                radial-gradient(circle at 100% 0%, var(--artist-hero-glow-1) 0%, transparent 44%),
                radial-gradient(circle at 0% 100%, var(--artist-hero-glow-2) 0%, transparent 36%),
                radial-gradient(circle at 50% 50%, var(--artist-hero-glow-3) 0%, transparent 52%);
        }

        .pdf-page.artist-edition .pdf-hero-title {
            background: none !important;
            color: var(--artist-title-color) !important;
            -webkit-text-fill-color: var(--artist-title-color);
            -webkit-text-stroke: 0.5px rgba(13, 11, 10, 0.22);
            text-shadow: 0 2px 0 rgba(22, 16, 12, 0.12), 0 10px 24px rgba(0, 0, 0, 0.22);
            font-size: 26px;
            line-height: 1.06;
            max-width: 100%;
        }

        .pdf-page.artist-edition .pdf-hero-msg {
            color: var(--artist-hero-msg);
            max-width: 560px;
        }

        .pdf-trust-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin: -8px 0 24px;
        }

        .pdf-trust-chip {
            border-radius: 14px;
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .pdf-page.artist-edition .pdf-trust-chip {
            background: var(--artist-chip-1-bg);
            border-color: var(--artist-chip-1-border);
        }

        .pdf-page.artist-edition .pdf-trust-chip:nth-child(2) {
            background: var(--artist-chip-2-bg);
            border-color: var(--artist-chip-2-border);
        }

        .pdf-page.artist-edition .pdf-trust-chip:nth-child(3) {
            background: var(--artist-chip-3-bg);
            border-color: var(--artist-chip-3-border);
        }

        .pdf-trust-label {
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--artist-chip-label);
            margin-bottom: 5px;
        }

        .pdf-trust-value {
            font-size: 11px;
            font-weight: 700;
            line-height: 1.4;
            color: var(--artist-chip-text);
        }

        .pdf-page.artist-edition .pdf-section-label span {
            color: var(--artist-section-label);
        }

        .pdf-page.artist-edition .pdf-section-label::after {
            background: var(--artist-divider);
        }

        .pdf-page.artist-edition .pdf-download-card.artist-card {
            background: var(--artist-card-bg);
            border: 1px solid var(--artist-card-border);
            border-left: 4px solid var(--artist-card-accent);
            border-radius: 20px;
            color: #fff;
            box-shadow: 0 16px 38px var(--artist-card-shadow);
        }

        .pdf-page.artist-edition .pdf-download-card.artist-card .card-icon-wrap {
            background: var(--artist-icon-bg);
            color: var(--artist-icon-text);
            border: none;
            box-shadow: 0 14px 30px var(--artist-icon-shadow);
        }

        .pdf-page.artist-edition .pdf-download-card.artist-card .card-num {
            color: var(--artist-card-num);
        }

        .pdf-page.artist-edition .pdf-download-card.artist-card .card-name {
            color: var(--artist-card-name);
        }

        .pdf-page.artist-edition .pdf-download-card.artist-card .card-sub {
            color: var(--artist-card-sub);
        }

        .pdf-page.artist-edition .pdf-download-card.artist-card .pdf-dl-btn {
            background: var(--artist-cta-bg);
            color: var(--artist-cta-text);
            border-radius: 999px;
            box-shadow: 0 14px 28px var(--artist-cta-shadow);
        }

        .card-meta-line {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--artist-card-meta);
            margin-bottom: 11px;
        }

        .pdf-page.artist-edition .pdf-how-section {
            background: var(--artist-how-bg);
            border-color: var(--artist-how-border);
            box-shadow: none;
        }

        .pdf-page.artist-edition .pdf-how-title {
            color: var(--artist-how-title);
        }

        .pdf-page.artist-edition .pdf-step-num {
            background: var(--artist-step-bg);
            color: var(--artist-step-text);
        }

        .pdf-page.artist-edition .pdf-step-title {
            color: var(--artist-step-title);
        }

        .pdf-page.artist-edition .pdf-step-desc {
            color: var(--artist-step-desc);
        }

        .pdf-page.artist-edition .pdf-footer {
            border-top-color: var(--artist-footer-border);
        }

        .pdf-page.artist-edition .pdf-footer-store {
            color: var(--artist-footer-store);
        }

        .pdf-page.artist-edition .pdf-footer-link {
            color: var(--artist-footer-link);
        }

        .pdf-page.artist-edition .pdf-stars {
            color: var(--artist-stars);
        }

        .pdf-page.artist-edition .pdf-review-cta {
            background: var(--artist-review-bg);
            color: var(--artist-review-text);
            border: 1px solid var(--artist-review-border);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.04), 0 10px 24px var(--artist-review-shadow);
        }

        #toast {
            position: fixed;
            bottom: 36px;
            right: 36px;
            background: #fff;
            color: #000;
            padding: 14px 28px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 14px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            transform: translateY(120px);
            transition: 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 5px solid var(--accent);
        }

        #toast.active {
            transform: translateY(0);
        }

        #spinner {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.78);
            backdrop-filter: blur(8px);
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 18px;
        }

        #spinner.show {
            display: flex;
        }

        .spin-ring {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            border: 4px solid rgba(255, 215, 0, 0.15);
            border-top-color: #FFD700;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .spin-text {
            font-size: 14px;
            font-weight: 800;
            color: #FFD700;
            letter-spacing: 1px;
        }
    </style>
</head>

<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div id="spinner">
        <div class="spin-ring"></div>
        <div class="spin-text">PDF BAN RAHA HAI...</div>
    </div>

    <nav>
        <div class="nav-left">
            <div class="brand">STUDIO<span>X</span></div>
            <div class="nav-badge"><i class="fa-solid fa-eye"></i> LIVE PREVIEW</div>
        </div>
        <div class="nav-actions">
            <a href="{{ route('pdf.edit', $record->id) }}" class="btn btn-ghost"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
            <a href="{{ route('dashboard') }}" class="btn btn-ghost">Back to Dashboard</a>
            <button id="downloadBtn" class="btn btn-primary"><i class="fa-solid fa-download"></i> Download PDF</button>
        </div>
    </nav>

    <div class="preview-wrapper">
        <div class="preview-meta">
            <h2><i class="fa-solid fa-file-lines"></i> {{ $record->store_name }} - Download Card Preview</h2>
            <p>This is the exact design your buyer will see in the PDF</p>
        </div>

        <div id="preview-outer">
            <div id="preview-canvas">
                @php
                    $themeMap = [
                        'gold-style' => 'gold-style',
                        'rose-style' => 'rose-style',
                        'aqua-style' => 'aqua-style',
                        'purple-style' => 'purple-style',
                        'black-style' => 'black-style',
                        'gd' => 'gold-style',
                        'rs' => 'rose-style',
                        'aq' => 'aqua-style',
                        'pu' => 'purple-style',
                        'bl' => 'black-style',
                    ];
                    $theme = $themeMap[$record->theme] ?? 'gold-style';
                    $products = $record->products ?? [];
                    $productIconMap = [
                        'fa-palette' => '<i class="fa-solid fa-palette" aria-hidden="true"></i>',
                        'fa-clapperboard' => '<i class="fa-solid fa-clapperboard" aria-hidden="true"></i>',
                        'fa-mountain-sun' => '<i class="fa-solid fa-mountain-sun" aria-hidden="true"></i>',
                        'fa-camera' => '<i class="fa-solid fa-camera" aria-hidden="true"></i>',
                        'fa-image' => '<i class="fa-solid fa-image" aria-hidden="true"></i>',
                        'fa-music' => '<i class="fa-solid fa-music" aria-hidden="true"></i>',
                        'fa-ruler-combined' => '<i class="fa-solid fa-ruler-combined" aria-hidden="true"></i>',
                        'fa-paint-brush' => '<i class="fa-solid fa-paint-brush" aria-hidden="true"></i>',
                        'fa-lightbulb' => '<i class="fa-solid fa-paint-brush" aria-hidden="true"></i>',
                        'fa-fire' => '<i class="fa-solid fa-fire" aria-hidden="true"></i>',
                        'fa-star' => '<i class="fa-solid fa-star" aria-hidden="true"></i>',
                    ];
                    $resolveProductIcon = function ($value) use ($productIconMap) {
                        $raw = is_string($value) ? $value : '';
                        if (preg_match_all('/fa-[a-z0-9-]+/i', $raw, $matches)) {
                            $ignored = ['fa-solid', 'fa-regular', 'fa-brands', 'fa-light', 'fa-thin', 'fa-duotone', 'fa-sharp'];
                            foreach ($matches[0] as $match) {
                                $iconName = strtolower($match);
                                if (in_array($iconName, $ignored, true)) {
                                    continue;
                                }
                                if ($iconName === 'fa-sparkles') {
                                    $iconName = 'fa-star';
                                } elseif ($iconName === 'fa-lightbulb') {
                                    $iconName = 'fa-paint-brush';
                                }
                                if (isset($productIconMap[$iconName])) {
                                    return $productIconMap[$iconName];
                                }
                                break;
                            }
                        }

                        return $productIconMap['fa-palette'];
                    };
                    $numClass = [
                        'gold-style' => 'gold',
                        'rose-style' => 'rose',
                        'aqua-style' => 'aqua',
                        'purple-style' => 'purple',
                    ][$theme] ?? '';
                    $artistProbe = strtolower(implode(' ', array_filter([
                        (string) ($record->store_name ?? ''),
                        (string) ($record->created_by ?? ''),
                        (string) ($record->title ?? ''),
                        (string) ($record->welcome_title ?? ''),
                        (string) ($record->welcome_msg ?? ''),
                        json_encode($products),
                    ])));
                    $isArtistLayout = preg_match('/drdoom|doom|procreate|brushset|brush library|ipad|paint-brush|fa-paint-brush|fa-lightbulb/', $artistProbe) === 1;

                    $storeName = $record->store_name;
                    $upper = strtoupper($storeName);
                    $mid = (int) ceil(strlen($upper) / 2);
                    $logoFirst = substr($upper, 0, $mid);
                    $logoLast = substr($upper, $mid);
                    $defaultWelcomeTitle = 'Your Files are Ready!';
                    $artistWelcomeTitle = 'Your Procreate Brush Library Is Ready';
                    $defaultWelcomeMessage = 'Thank you for your purchase! We\'ve put a lot of love into these assets. If you need any help, just message us on Etsy.';
                    $artistWelcomeMessage = 'Thank you for supporting independent art. Every file in this order was prepared for a smooth Procreate workflow on iPad.';
                    $welcomeTitle = trim(strip_tags((string) ($record->welcome_title ?? '')));
                    if ($welcomeTitle === '' || ($isArtistLayout && $welcomeTitle === $defaultWelcomeTitle)) {
                        $welcomeTitle = $isArtistLayout ? $artistWelcomeTitle : $defaultWelcomeTitle;
                    }
                    $welcomeMessage = trim(strip_tags((string) ($record->welcome_msg ?? $record->message ?? $defaultWelcomeMessage)));
                    if ($welcomeMessage === '' || ($isArtistLayout && $welcomeMessage === $defaultWelcomeMessage)) {
                        $welcomeMessage = $isArtistLayout ? $artistWelcomeMessage : $defaultWelcomeMessage;
                    }
                    $tagline = $isArtistLayout ? 'INDEPENDENT DIGITAL ARTIST' : 'DIGITAL DOWNLOAD STORE';
                    $badgeLine1 = $isArtistLayout ? 'AUTHENTIC PROCREATE RELEASE' : 'EXCLUSIVE DIGITAL DELIVERY';
                    $badgeLine2 = $isArtistLayout ? 'Artist Download Guide' : 'Thank You Card';
                    $heroEmoji = $isArtistLayout ? '<i class="fa-solid fa-paint-brush" aria-hidden="true"></i>' : '<i class="fa-solid fa-gift" aria-hidden="true"></i>';
                    $sectionLabel = $isArtistLayout ? 'YOUR PROCREATE DOWNLOADS' : 'YOUR DOWNLOAD LINKS';
                    $howTitle = $isArtistLayout ? 'PROCREATE INSTALL GUIDE' : 'HOW TO USE YOUR FILES';
                    $reviewCta = $isArtistLayout ? 'SUPPORT THIS ARTIST' : 'LEAVE A REVIEW';
                    $defaultCardName = $isArtistLayout ? 'Procreate Brushset Download' : 'Premium Asset Pack';
                    $defaultCardDesc = $isArtistLayout
                        ? 'Artist-crafted .brushset file | Instant digital delivery | Ready for Procreate on iPad'
                        : 'Instant Access | Digital Download | No Expiry';
                    $trustChips = $isArtistLayout ? [
                        ['label' => 'CREATED BY', 'value' => ($record->created_by ?: $record->store_name ?: 'Independent Artist')],
                        ['label' => 'FORMAT', 'value' => 'Procreate / iPad Ready'],
                        ['label' => 'DELIVERY', 'value' => 'Instant Digital Download'],
                    ] : [];
                    $steps = $isArtistLayout
                        ? [
                            ['n' => '1', 'title' => 'Save To Files', 'desc' => $record->step1 ?? ''],
                            ['n' => '2', 'title' => 'Tap To Import', 'desc' => $record->step2 ?? ''],
                            ['n' => '3', 'title' => 'Start Creating', 'desc' => $record->step3 ?? ''],
                        ]
                        : [
                            ['n' => '1', 'title' => 'Download Your Files', 'desc' => $record->step1 ?? ''],
                            ['n' => '2', 'title' => 'Import Into Your App', 'desc' => $record->step2 ?? ''],
                            ['n' => '3', 'title' => 'Apply & Enjoy!', 'desc' => $record->step3 ?? ''],
                        ];
                @endphp

                <div class="pdf-page {{ $theme }} {{ ($record->pdf_mode ?? 'light') === 'dark' ? 'dark-mode' : '' }} {{ $isArtistLayout ? 'artist-edition' : '' }}" id="pdf-root">

                    <!-- HEADER -->
                    <div class="pdf-header">
                        <div class="pdf-logo-wrap">
                            <div class="pdf-logo">{{ $logoFirst }}<span>{{ $logoLast }}</span></div>
                            <div class="pdf-logo-tagline">{{ $tagline }}</div>
                        </div>
                        <div class="pdf-header-badge">
                            <div class="pdf-badge-line1">{{ $badgeLine1 }}</div>
                            <div class="pdf-badge-line2"><i class="fa-solid fa-star-of-life"></i> Thank You Card</div>
                        </div>
                    </div>

                    <!-- HERO -->
                    <div class="pdf-hero">
                        <div class="pdf-hero-layout">
                            <div class="pdf-hero-top">
                                <div class="pdf-hero-icon">
                                    <span class="pdf-hero-emoji">{!! $heroEmoji !!}</span>
                                </div>
                                <div class="pdf-hero-copy">
                                    <div class="pdf-hero-kicker">Digital Delivery Ready</div>
                                    <div class="pdf-hero-title">{{ $welcomeTitle }}</div>
                                </div>
                            </div>
                            <div class="pdf-hero-msg">
                                {{ $welcomeMessage }}
                            </div>
                            <div class="pdf-hero-meta">
                                <div class="pdf-hero-pill"><i class="fa-solid fa-link"></i> Access links included</div>
                                <div class="pdf-hero-pill"><i class="fa-solid fa-comments"></i> Support available on Etsy</div>
                            </div>
                        </div>
                    </div>
                    @if(!empty($trustChips))
                        <div class="pdf-trust-strip">
                            @foreach($trustChips as $chip)
                                <div class="pdf-trust-chip">
                                    <div class="pdf-trust-label">{{ $chip['label'] }}</div>
                                    <div class="pdf-trust-value">{{ $chip['value'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- DOWNLOAD CARDS -->
                    <div class="pdf-section-label"><span>{{ $sectionLabel }}</span></div>
                    @foreach($products as $i => $product)
                        <div class="pdf-download-card {{ $theme }} {{ $isArtistLayout ? 'artist-card' : '' }}">
                            <div class="card-icon-wrap">{!! $resolveProductIcon($product['type'] ?? null) !!}</div>
                            <div class="card-body">
                                <div class="card-num">FILE {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
                                <div class="card-name">{{ $product['name'] ?? $defaultCardName }}</div>
                                <div class="card-sub">
                                    {{ !empty($product['desc']) ? $product['desc'] : 'Instant Access · Digital Download · No Expiry' }}
                                </div>
                                <a href="{{ $product['link'] ?? '#' }}" class="pdf-dl-btn" target="_blank">⬇ Download
                                    Now</a>
                            </div>
                        </div>
                    @endforeach

                    <!-- HOW TO USE -->
                    @if($record->step1 || $record->step2 || $record->step3)
                        <div class="pdf-how-section">
                            <div class="pdf-how-title"><i class="fa-solid fa-book-open"></i> HOW TO USE YOUR FILES</div>
                            <div class="pdf-steps">
                                @foreach($steps as $step)
                                    @if($step['desc'])
                                        <div class="pdf-step">
                                            <div class="pdf-step-num {{ $numClass }}">{{ $step['n'] }}</div>
                                            <div class="pdf-step-body">
                                                <div class="pdf-step-title">{{ $step['title'] }}</div>
                                                <div class="pdf-step-desc">{{ $step['desc'] }}</div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- FOOTER -->
                    <div class="pdf-footer">
                        <div>
                            <div class="pdf-footer-store">{{ $record->store_name }}</div>
                            <div class="pdf-footer-link">
                                <a href="{{ str_starts_with($record->store_link ?? '', 'http') ? ($record->store_link ?? '') : 'https://' . ($record->store_link ?? '') }}"
                                    target="_blank" style="color:inherit;text-decoration:none;">
                                    {{ $record->store_link ?? '' }}
                                </a>
                            </div>
                        </div>
                        <div class="pdf-footer-right">
                            <div class="pdf-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                            <div class="pdf-review-cta">{{ $reviewCta }}</div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div id="toast">
        <span id="toastIcon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
        <span id="toastMsg">Done!</span>
    </div>

    <script>
        function rescale() {
            const outer = document.getElementById('preview-outer');
            const cnv = document.getElementById('preview-canvas');
            const pdf = document.getElementById('pdf-root');
            const scale = outer.clientWidth / 794;
            cnv.style.transform = `scale(${scale})`;
            cnv.style.transformOrigin = 'top left';
            cnv.style.width = '794px';
            outer.style.height = Math.ceil(pdf.scrollHeight * scale) + 'px';
        }

        const isArtistLayout = @json($isArtistLayout);
        if (isArtistLayout) {
            const badgeLine2 = document.querySelector('.pdf-badge-line2');
            if (badgeLine2) badgeLine2.textContent = @json($badgeLine2);

            const heroEmoji = document.querySelector('.pdf-hero-emoji');
            if (heroEmoji) heroEmoji.innerHTML = @json($heroEmoji);

            const howTitle = document.querySelector('.pdf-how-title');
            if (howTitle) howTitle.textContent = @json($howTitle);

            document.querySelectorAll('.pdf-download-card.artist-card .card-body').forEach((body) => {
                if (body.querySelector('.card-meta-line')) return;
                const cta = body.querySelector('.pdf-dl-btn');
                if (!cta) return;
                const meta = document.createElement('div');
                meta.className = 'card-meta-line';
                meta.textContent = 'INDEPENDENT ARTIST RELEASE | PERSONAL-USE DIGITAL PRODUCT';
                body.insertBefore(meta, cta);
            });
        }

        document.querySelectorAll('.pdf-dl-btn').forEach((button) => {
            button.innerHTML = '<i class="fa-solid fa-download" aria-hidden="true"></i> Download Now';
        });

        document.querySelectorAll('.card-sub').forEach((sub) => {
            const text = (sub.textContent || '').trim();
            if (/[^\x20-\x7E]/.test(text) && /instant access/i.test(text)) {
                sub.textContent = 'Instant Access | Digital Download | No Expiry';
            } else if (/[^\x20-\x7E]/.test(text) && /(brushset|procreate|ipad)/i.test(text)) {
                sub.textContent = 'Artist-crafted .brushset file | Instant digital delivery | Ready for Procreate on iPad';
            }
        });

        rescale();
        window.addEventListener('resize', rescale);

        function toast(message, iconClass = 'fa-circle-check') {
            document.getElementById('toastIcon').innerHTML = `<i class="fa-solid ${iconClass}"></i>`;
            document.getElementById('toastMsg').textContent = message;
            const t = document.getElementById('toast');
            t.classList.add('active');
            setTimeout(() => t.classList.remove('active'), 4000);
        }

        document.getElementById('downloadBtn').addEventListener('click', async () => {
            const btn = document.getElementById('downloadBtn');
            const spinner = document.getElementById('spinner');
            const cnv = document.getElementById('preview-canvas');
            const root = document.getElementById('pdf-root');
            btn.disabled = true; btn.textContent = 'Generating...';
            spinner.classList.add('show');
            cnv.style.transform = 'none';
            try {
                const { jsPDF } = window.jspdf;
                const c = await html2canvas(root, { scale: 3, useCORS: true, logging: false });
                const pdf = new jsPDF('p', 'mm', 'a4');
                const imgData = c.toDataURL('image/png');
                pdf.addImage(imgData, 'PNG', 0, 0, 210, 297);

                // ── Add Clickable Links Overlays ──────────────────
                const rootRect = root.getBoundingClientRect();
                const pxToMmX = 210 / rootRect.width;
                const pxToMmY = 297 / rootRect.height;

                root.querySelectorAll('a').forEach(el => {
                    const rect = el.getBoundingClientRect();
                    const url = el.getAttribute('href');
                    if (url && url !== '#' && url !== window.location.href) {
                        const x = (rect.left - rootRect.left) * pxToMmX;
                        const y = (rect.top - rootRect.top) * pxToMmY;
                        const w = rect.width * pxToMmX;
                        const h = rect.height * pxToMmY;
                        pdf.link(x, y, w, h, { url });
                    }
                });

                const pdfBlob = pdf.output('blob');
                const sizeMB = (pdfBlob.size / (1024 * 1024)).toFixed(1);

                pdf.save(`{{ Str::slug($record->store_name) }}-Download-Card.pdf`);
                toast(`PDF download ho gayi! (${sizeMB} MB)`);
            } catch (e) { toast('Error: ' + e.message, 'fa-circle-xmark'); }
            finally {
                rescale();
                btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-download"></i> Download PDF';
                spinner.classList.remove('show');
            }
        });
    </script>
</body>

</html>

