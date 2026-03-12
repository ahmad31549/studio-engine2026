<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Etsy PDF Lab — Elite PDF Generator</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Orbitron:wght@700;900&display=swap"
        rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        :root {
            --accent: #FFD700;
            --accent-soft: rgba(255, 215, 0, 0.12);
            --accent-glow: rgba(255, 215, 0, 0.4);
            --bg: #050507;
            --panel: #0d0d10;
            --card: #131318;
            --card-border: rgba(255, 215, 0, 0.1);
            --text: #ffffff;
            --text-dim: #7777a0;
            --gradient: linear-gradient(135deg, #FFD700 0%, #FF8C00 100%);
            --glass: rgba(255, 255, 255, 0.03);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        * {
            scroll-behavior: smooth;
        }

        ::-webkit-scrollbar {
            width: 16px;
            height: 16px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg);
            border-left: 1px solid rgba(255, 215, 0, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(255, 215, 0, 0.5), rgba(255, 215, 0, 0.3));
            border-radius: 8px;
            border: 3px solid var(--bg);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, rgba(255, 215, 0, 0.8), rgba(255, 215, 0, 0.6));
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

        /* ORBS */
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

        /* NAV */
        nav {
            height: 76px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            display: flex;
            align-items: center;
            padding: 0 36px;
            background: rgba(5, 5, 7, 0.9);
            backdrop-filter: blur(24px);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-content {
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-bolt {
            font-size: 26px;
            filter: drop-shadow(0 0 12px var(--accent));
            animation: glw 2s ease-in-out infinite alternate;
        }

        @keyframes glw {
            from {
                filter: drop-shadow(0 0 6px var(--accent));
            }

            to {
                filter: drop-shadow(0 0 20px var(--accent));
            }
        }

        .brand-text {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 3px;
        }

        .brand-text span {
            color: var(--accent);
        }

        .nav-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .quality-wrap {
            position: relative;
        }

        .quality-select {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ccc;
            padding: 9px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23999' viewBox='0 0 16 16'%3E%3Cpath d='M4 6l4 4 4-4'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            padding-right: 28px;
        }

        .quality-select:hover {
            border-color: rgba(255, 215, 0, 0.3);
        }

        .quality-select option {
            background: #1a1a1e;
            color: #eee;
        }

        .btn {
            padding: 11px 22px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            box-shadow: 0 8px 30px var(--accent-glow);
        }

        .btn-ghost {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.07);
            color: #fff;
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--accent);
            color: var(--accent);
        }

        /* WORKSPACE */
        .workspace {
            flex: 1;
            display: grid;
            grid-template-columns: 500px 1fr;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
            gap: 40px;
            padding: 36px 40px;
        }

        @media(max-width:1100px) {
            .workspace {
                grid-template-columns: 1fr;
            }

            .preview-top-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .preview-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }

        /* LEFT CONTROLS */
        .controls {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .glass-card {
            background: var(--panel);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
        }

        /* Accordion header */
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 28px;
            cursor: pointer;
            user-select: none;
            transition: background 0.2s;
        }
        .card-header:hover {
            background: rgba(255, 215, 0, 0.04);
        }
        .card-header:focus-visible {
            outline: 2px solid rgba(255, 215, 0, 0.5);
            outline-offset: -2px;
        }
        .card-header-chevron {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: var(--text-dim);
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .card-header:hover .card-header-chevron {
            background: rgba(255, 215, 0, 0.1);
            color: var(--accent);
            border-color: rgba(255, 215, 0, 0.25);
        }
        .glass-card.open .card-header-chevron {
            transform: rotate(180deg);
        }
        .glass-card.open .card-header:hover .card-header-chevron {
            transform: rotate(180deg);
        }
        /* Accordion body */
        .card-body-wrap {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-in-out, opacity 0.3s ease-in-out;
        }
        .glass-card.open .card-body-wrap {
            max-height: 1000px; /* Large enough to hold content */
            opacity: 1;
        }
        .card-body-inner {
            padding: 0 28px 24px;
        }

        /* Filename input in nav */
        .filename-wrap {
            display: flex;
            align-items: center;
        }
        .filename-input {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            color: #ccc;
            padding: 9px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            width: 200px;
            outline: none;
            transition: 0.2s;
        }
        .filename-input:focus {
            border-color: rgba(255,215,0,0.4);
            color: #fff;
        }

        .group-title {
            font-size: 10px;
            font-weight: 900;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 2.5px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .group-title .line {
            height: 1px;
            flex: 1;
            background: linear-gradient(90deg, var(--accent-soft), transparent);
        }

        .input-box {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 16px;
        }

        .input-box:last-child {
            margin-bottom: 0;
        }

        .input-box label {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-dim);
            letter-spacing: 0.5px;
        }

        input,
        textarea,
        select {
            background: #0d0d10;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 13px 16px;
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
            width: 100%;
        }

        input:focus,
        textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.07);
        }

        /* Steps */
        .steps-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .step-box {
            background: #0d0d10;
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 14px;
            padding: 16px;
            position: relative;
        }

        .step-number {
            position: absolute;
            top: -10px;
            left: 14px;
            background: var(--gradient);
            color: #000;
            font-size: 10px;
            font-weight: 900;
            padding: 2px 10px;
            border-radius: 20px;
            letter-spacing: 1px;
        }

        .step-box textarea {
            background: transparent;
            border: none;
            padding: 10px 0 0;
            font-size: 13px;
            resize: none;
            color: #ccc;
        }

        .step-box textarea:focus {
            box-shadow: none;
            border: none;
        }

        /* Products */
        .product-stack {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-bottom: 16px;
        }

        .product-item {
            background: #0a0a0d;
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 16px;
            padding: 20px;
            position: relative;
            animation: slideIn 0.35s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .product-badge {
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 9px;
            font-weight: 900;
            padding: 3px 10px;
            border-radius: 6px;
            letter-spacing: 1px;
        }

        .remove-btn {
            color: #f55;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            border: none;
            background: none;
        }

        /* Type selector */
        .type-select {
            background: #0d0d10;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            padding: 10px 14px;
            color: #ccc;
            font-size: 13px;
            font-family: inherit;
            margin-bottom: 0;
            cursor: pointer;
            flex: 1 1 auto;
        }

        .type-select-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .type-icon-preview {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            flex: 0 0 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #f4d08f;
            font-size: 15px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .add-product-btn {
            width: 100%;
            padding: 16px;
            border-radius: 14px;
            background: rgba(255, 215, 0, 0.03);
            border: 2px dashed rgba(255, 215, 0, 0.15);
            color: var(--accent);
            font-weight: 800;
            font-size: 13px;
            cursor: pointer;
            transition: 0.3s;
            font-family: inherit;
        }

        .add-product-btn:hover {
            background: var(--accent-soft);
            border-style: solid;
        }

        /* RIGHT: PREVIEW — sticky column panel */
        .preview-pane {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 0;
            position: sticky;
            top: 84px;
            align-self: start;
        }

        .preview-sticky-header {
            padding: 0 0 14px;
        }

        .preview-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--panel);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 12px 20px;
            gap: 16px;
        }

        .preview-label {
            font-size: 11px;
            font-weight: 800;
            color: var(--text-dim);
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .preview-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .theme-pills {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .preview-top-bar > .theme-pills {
            display: none;
        }

        .theme-pill {
            --pill-bg: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.04));
            --pill-border: rgba(255, 255, 255, 0.08);
            --pill-text: #d7d8e3;
            --pill-shadow: rgba(0, 0, 0, 0.25);
            --pill-active-text: #0e0e12;
            --pill-swatch: linear-gradient(135deg, #f5c542, #ff9a3d);
            align-items: center;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--pill-border);
            border-radius: 999px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.02);
            color: var(--pill-text);
            cursor: pointer;
            display: inline-flex;
            font-family: inherit;
            font-size: 11px;
            font-weight: 800;
            gap: 8px;
            letter-spacing: 0.04em;
            min-height: 34px;
            padding: 6px 14px 6px 10px;
            transition: transform 0.18s ease, border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .theme-pill:hover:not(.active) {
            border-color: rgba(255, 255, 255, 0.18);
            color: #f8f8fc;
            transform: translateY(-1px);
        }

        .theme-pill.active {
            background: var(--pill-bg);
            border-color: transparent;
            box-shadow: 0 14px 28px var(--pill-shadow);
            color: var(--pill-active-text);
        }

        .theme-pill-swatch {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: var(--pill-swatch);
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.24), 0 6px 14px var(--pill-shadow);
            position: relative;
            flex-shrink: 0;
        }

        .theme-pill-swatch::after {
            content: '';
            position: absolute;
            inset: 5px;
            border-radius: 999px;
            background: rgba(15, 15, 20, 0.82);
        }

        .theme-pill.active .theme-pill-swatch::after {
            background: rgba(255, 255, 255, 0.26);
        }

        .theme-pill-label {
            line-height: 1;
            white-space: nowrap;
        }

        .theme-pill-gold {
            --pill-bg: linear-gradient(135deg, #f7d216, #ffb900);
            --pill-border: rgba(247, 210, 22, 0.24);
            --pill-text: #f5d978;
            --pill-shadow: rgba(247, 210, 22, 0.24);
            --pill-swatch: linear-gradient(135deg, #fff0a6, #f7c62f);
        }

        .theme-pill-rose {
            --pill-bg: linear-gradient(135deg, #ff8ea2, #ffb06b);
            --pill-border: rgba(255, 142, 162, 0.24);
            --pill-text: #ffbfd1;
            --pill-shadow: rgba(255, 142, 162, 0.22);
            --pill-swatch: linear-gradient(135deg, #ffd0da, #ff7f95);
        }

        .theme-pill-aqua {
            --pill-bg: linear-gradient(135deg, #66e2d5, #5ea6ff);
            --pill-border: rgba(102, 226, 213, 0.24);
            --pill-text: #a8f1e8;
            --pill-shadow: rgba(94, 166, 255, 0.2);
            --pill-swatch: linear-gradient(135deg, #c7fff3, #57cde0);
        }

        .theme-pill-purple {
            --pill-bg: linear-gradient(135deg, #b08cff, #ff82c9);
            --pill-border: rgba(176, 140, 255, 0.24);
            --pill-text: #d9c5ff;
            --pill-shadow: rgba(176, 140, 255, 0.22);
            --pill-swatch: linear-gradient(135deg, #ecdfff, #9b72ff);
        }

        .theme-pill-noir {
            --pill-bg: linear-gradient(135deg, #f7d216, #ffb900);
            --pill-border: rgba(247, 210, 22, 0.24);
            --pill-text: #f5d978;
            --pill-shadow: rgba(247, 210, 22, 0.24);
            --pill-swatch: linear-gradient(135deg, #1f2027, #3a3a44);
        }

        /* Preview canvas */
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
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7);
            border-radius: 4px;
        }

        /* =========================================
           PDF PAGE STYLES
           ========================================= */
        .pdf-page {
            width: 794px;
            min-height: 1123px;
            background: #fff;
            color: #111;
            padding: 40px 50px;
            display: flex;
            flex-direction: column;
            gap: 0;
            font-family: 'Outfit', sans-serif;
        }

        /* PDF Header */
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

        /* Welcome Hero */
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

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-download-card.black-style {
            background: linear-gradient(135deg, #f6f3ed, #e2dcd2);
            border: 1px solid rgba(34, 31, 27, 0.12);
            border-left: 5px solid #b58a34;
            color: #16171b;
            box-shadow: 0 12px 28px rgba(20, 18, 16, 0.08);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-download-card.black-style .card-icon-wrap {
            background: linear-gradient(135deg, #23242a, #111216);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.04);
            box-shadow: 0 10px 22px rgba(17, 18, 22, 0.16);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-download-card.black-style .card-num {
            color: #9b793d;
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-download-card.black-style .card-name {
            color: #18191d;
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-download-card.black-style .card-sub {
            color: rgba(24, 25, 29, 0.64);
        }

        .pdf-page.black-style:not(.artist-edition):not(.dark-mode) .pdf-download-card.black-style .pdf-dl-btn {
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

        /* Download Section */
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

        .pdf-section-label::before,
        .pdf-section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #eee;
        }

        .pdf-section-label::before {
            display: none;
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

        .pdf-download-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: 100%;
            opacity: 0.04;
            pointer-events: none;
        }

        /* Theme: Gold */
        .pdf-download-card.gold-style {
            background: linear-gradient(to right, #ffffff, #fffdfa);
            border: 1px solid #e0e0e0;
            border-left: 5px solid #D4A000;
            transition: all 0.3s;
        }

        .pdf-download-card.gold-style .card-icon-wrap {
            background: linear-gradient(135deg, #fff0b1, #f2c94c);
            color: #3b2b00;
            border-radius: 12px;
            border: 1px solid rgba(212, 160, 0, 0.16);
            box-shadow: 0 10px 22px rgba(212, 160, 0, 0.18);
        }

        .pdf-download-card.gold-style .pdf-dl-btn {
            background: #D4A000;
            color: #fff;
            box-shadow: 0 4px 12px rgba(212, 160, 0, 0.2);
        }

        .pdf-download-card.gold-style .card-num {
            color: #D4A000;
        }

        /* Theme: Purple */
        .pdf-download-card.purple-style {
            background: linear-gradient(135deg, #fdfbff, #f5f0ff);
            border-color: #c4a8f0;
        }

        .pdf-download-card.purple-style .card-icon-wrap {
            background: linear-gradient(135deg, #7C3AED, #5B21B6);
        }

        .pdf-download-card.purple-style .pdf-dl-btn {
            background: linear-gradient(135deg, #7C3AED, #5B21B6);
            color: #fff;
        }

        .pdf-download-card.purple-style .card-num {
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

        /* Theme: Black (Elevated for Pro Artists) */
        .pdf-download-card.black-style {
            background: #111113;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            color: #fff;
        }

        .pdf-download-card.black-style .card-icon-wrap {
            background: #232328;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .pdf-download-card.black-style .pdf-dl-btn {
            background: #fff;
            color: #000;
            box-shadow: 0 4px 15px rgba(255,255,255,0.1);
        }

        .pdf-download-card.black-style .card-num {
            color: rgba(255,255,255,0.5);
            font-weight: 700;
        }

        .pdf-download-card.black-style .card-name {
            color: #fff;
        }

        .pdf-download-card.black-style .card-sub {
            color: rgba(255,255,255,0.5);
        }

        /* How to use for Black Theme */
        .pdf-page.black-style .pdf-how-section {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .pdf-page.black-style .pdf-how-title {
            color: rgba(255,255,255,0.4);
        }

        .pdf-page.black-style .pdf-step-title {
            color: #fff;
        }

        .pdf-page.black-style .pdf-step-desc {
            color: rgba(255,255,255,0.5);
        }

        .pdf-page.black-style .pdf-step-num {
            background: #fff;
            color: #000;
        }

        .pdf-page.black-style .pdf-footer {
            border-top-color: rgba(255,255,255,0.08);
        }

        .pdf-page.black-style .pdf-footer-store {
            color: #fff;
        }

        .pdf-page.black-style .pdf-review-cta {
            background: #fff;
            color: #000;
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

        /* How To Use */
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
            gap: 0;
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

        /* Footer */
        .pdf-footer {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1.5px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pdf-footer-left {}

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

        /* TOAST */
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

        /* Spinner */
        #spinner {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.75);
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
            background: linear-gradient(135deg, #1a1800 0%, #1e1b05 50%, #23200a 100%);
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

        .pdf-page.dark-mode .pdf-hero-emoji {
            filter: brightness(0.9);
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

        .pdf-page.dark-mode .card-num {
            opacity: 0.9;
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

        /* Mode toggle */
        .mode-toggle {
            display: flex;
            gap: 4px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            padding: 3px;
        }

        .mode-btn {
            padding: 5px 12px;
            border: none;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            background: transparent;
            color: #777;
            transition: all 0.2s;
        }

        .mode-btn.active {
            background: rgba(255, 215, 0, 0.15);
            color: #FFD700;
        }
    </style>
</head>

<body>
    @php
        $initialWelcomeTitle = trim(strip_tags((string) ($record?->welcome_title ?? '')));
        if ($initialWelcomeTitle === '') {
            $initialWelcomeTitle = 'Your Files are Ready!';
        }
    @endphp
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div id="spinner">
        <div class="spin-ring"></div>
        <div class="spin-text">GENERATING PDF...</div>
    </div>

    <nav>
        <div class="nav-content">
            <div class="brand">
                <span class="brand-bolt"><i class="fa-solid fa-bolt"></i></span>
                <div class="brand-text">ETSY <span>PDF LAB</span></div>
            </div>
            <div class="nav-actions">
                <a href="{{ route('pdf.index') }}" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
                <button id="saveBtn" class="btn btn-ghost"><i class="fa-solid fa-floppy-disk"></i> Save Draft</button>
                <div class="quality-wrap">
                    <select id="pdfQuality" class="quality-select">
                        <option value="standard">Standard (~2MB)</option>
                        <option value="high" selected>High (~5MB)</option>
                        <option value="ultra">Ultra HD (~15MB)</option>
                    </select>
                </div>
                <div class="filename-wrap">
                    <input id="pdfFileName" type="text" placeholder="Enter PDF file name..." value="{{ $record?->title ?? ($record?->store_name ? $record->store_name . '-Download-Card' : 'ThorPresets-Download-Card') }}" class="filename-input">
                </div>
                <button id="downloadBtn" class="btn btn-primary"><i class="fa-solid fa-download"></i> Download PDF</button>
            </div>
        </div>
    </nav>

    <main class="workspace">

        <!-- ══════════════════════════════ LEFT: EDITOR ══════════════════════════════ -->
        <div class="controls">

            <!-- Store Info -->
            <div class="glass-card">
                <div class="card-header">
                    <div class="group-title" style="margin:0;"><i class="fa-solid fa-store"></i> Store Details</div>
                    <div class="card-header-chevron">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                <div class="card-body-wrap">
                    <div class="card-body-inner">
                        <div class="input-box">
                            <label>Store Name</label>
                            <input id="storeName" type="text" placeholder="e.g. ThorPresets"
                                value="{{ $record?->store_name ?? 'ThorPresets' }}">
                        </div>
                        <div class="input-box">
                            <label>Store URL (Etsy Link)</label>
                            <input id="storeLink" type="text" placeholder="etsy.com/shop/yourstore"
                                value="{{ $record?->store_link ?? 'etsy.com/shop/ThorPresets' }}">
                        </div>
                        <div class="input-box">
                            <label>Created By</label>
                            <input id="createdBy" type="text" placeholder="e.g. Independent Artist"
                                value="{{ $record?->created_by ?? $record?->store_name ?? 'ThorPresets' }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Welcome Message -->
            <div class="glass-card">
                <div class="card-header">
                    <div class="group-title" style="margin:0;"><i class="fa-solid fa-comment-dots"></i> Thank You Message</div>
                    <div class="card-header-chevron">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                <div class="card-body-wrap">
                    <div class="card-body-inner">
                        <div class="input-box">
                            <label>Welcome Title</label>
                            <input id="welcomeTitle" type="text" placeholder="Your Files are Ready!"
                                value="{{ $initialWelcomeTitle }}">
                        </div>
                        <div class="input-box">
                            <label>Personal Message</label>
                            <textarea id="welcomeMsg" rows="3"
                                placeholder="Write a warm thank you note...">{{ $record?->welcome_msg ?? "Thank you for your purchase! We've put a lot of love into these assets. If you need any help, just message us on Etsy." }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Downloads -->
            <div class="glass-card">
                <div class="card-header">
                    <div class="group-title" style="margin:0;"><i class="fa-solid fa-box-open"></i> Download Links</div>
                    <div class="card-header-chevron">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                <div class="card-body-wrap">
                    <div class="card-body-inner">
                        <div id="productStack" class="product-stack" style="margin-bottom:12px;"></div>
                        <button id="addProductBtn" class="add-product-btn"><i class="fa-solid fa-plus"></i> Add Another File</button>
                    </div>
                </div>
            </div>

            <!-- How to Use -->
            <div class="glass-card">
                <div class="card-header">
                    <div class="group-title" style="margin:0;"><i class="fa-solid fa-graduation-cap"></i> How to Use Steps</div>
                    <div class="card-header-chevron">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                <div class="card-body-wrap">
                    <div class="card-body-inner">
                        <div style="font-size:11px;color:var(--text-dim);margin-bottom:18px;">Explain how the buyer should use the files (LUTs, presets, graphics)</div>
                        <div class="steps-grid">
                            <div class="step-box" style="grid-column:1/-1;">
                                <div class="step-number">STEP 1</div>
                                <textarea id="step1" rows="2"
                                    placeholder="e.g. Download the ZIP file by clicking the button above and extract it to your computer.">{{ $record?->step1 ?? 'Download the ZIP file by clicking the Download button above. Extract the folder to your Desktop.' }}</textarea>
                            </div>
                            <div class="step-box" style="grid-column:1/-1;">
                                <div class="step-number">STEP 2</div>
                                <textarea id="step2" rows="2"
                                    placeholder="e.g. Open Lightroom / Premiere Pro and import the .xmp or .cube files from the extracted folder.">{{ $record?->step2 ?? 'Open Lightroom, Premiere Pro, or your editing app. Go to Import → Navigate to the extracted folder and select the files.' }}</textarea>
                            </div>
                            <div class="step-box" style="grid-column:1/-1;">
                                <div class="step-number">STEP 3</div>
                                <textarea id="step3" rows="2"
                                    placeholder="e.g. Apply preset/LUT to your photo or video. Adjust intensity to your taste. Enjoy!">{{ $record?->step3 ?? 'Apply the Preset or LUT to your photo/video. Adjust the intensity/opacity to taste. Enjoy your stunning results!' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ══════════════════════════════ RIGHT: PREVIEW ══════════════════════════════ -->
        <div class="preview-pane">

            <div class="preview-sticky-header">
                <div class="preview-top-bar">
                    <div class="preview-label"><i class="fa-solid fa-eye"></i> Live Preview</div>
                    <div class="preview-actions">
                        <div class="theme-pills">
                            <button class="theme-pill theme-pill-gold active" data-style="gold-style" type="button">
                                <span class="theme-pill-swatch" aria-hidden="true"></span>
                                <span class="theme-pill-label">Gold</span>
                            </button>
                            <button class="theme-pill theme-pill-rose" data-style="rose-style" type="button">
                                <span class="theme-pill-swatch" aria-hidden="true"></span>
                                <span class="theme-pill-label">Rose</span>
                            </button>
                            <button class="theme-pill theme-pill-aqua" data-style="aqua-style" type="button">
                                <span class="theme-pill-swatch" aria-hidden="true"></span>
                                <span class="theme-pill-label">Aqua</span>
                            </button>
                            <button class="theme-pill theme-pill-purple" data-style="purple-style" type="button">
                                <span class="theme-pill-swatch" aria-hidden="true"></span>
                                <span class="theme-pill-label">Violet</span>
                            </button>
                            <button class="theme-pill theme-pill-noir" data-style="black-style" type="button">
                                <span class="theme-pill-swatch" aria-hidden="true"></span>
                                <span class="theme-pill-label">Noir</span>
                            </button>
                        </div>
                        <div class="mode-toggle">
                            <button class="mode-btn active" data-mode="light" type="button">Light</button>
                            <button class="mode-btn" data-mode="dark" type="button">Dark</button>
                        </div>
                    </div>
                    <div class="theme-pills">
                        <button class="theme-pill active" data-style="gold-style"><i class="fa-solid fa-star"></i> Gold</button>
                        <button class="theme-pill" data-style="purple-style"><i class="fa-solid fa-circle" style="color: #a78bfa;"></i> Purple</button>
                        <button class="theme-pill" data-style="black-style"><i class="fa-solid fa-circle" style="color: #374151;"></i> Noir</button>
                        <div class="mode-toggle">
                            <button class="mode-btn active" data-mode="light"><i class="fa-solid fa-sun"></i></button>
                            <button class="mode-btn" data-mode="dark"><i class="fa-solid fa-moon"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="preview-outer">
                <div id="preview-canvas">

                    <!-- ══════════════════ THE ACTUAL PDF ══════════════════ -->
                    <div class="pdf-page" id="pdf-root">

                        <!-- HEADER -->
                        <div class="pdf-header">
                            <div class="pdf-logo-wrap">
                                <div class="pdf-logo" id="p-logo">THOR<span>PRESETS</span></div>
                                <div class="pdf-logo-tagline" id="p-logo-tagline">DIGITAL DOWNLOAD STORE</div>
                            </div>
                            <div class="pdf-header-badge">
                                <div class="pdf-badge-line1" id="p-badge-line1">EXCLUSIVE DIGITAL DELIVERY</div>
                                <div class="pdf-badge-line2"><i class="fa-solid fa-star-of-life"></i> Thank You Card</div>
                            </div>
                        </div>

                        <!-- HERO -->
                        <div class="pdf-hero">
                            <div class="pdf-hero-layout">
                                <div class="pdf-hero-top">
                                    <div class="pdf-hero-icon">
                                        <span class="pdf-hero-emoji"><i class="fa-solid fa-gift" aria-hidden="true"></i></span>
                                    </div>
                                    <div class="pdf-hero-copy">
                                        <div class="pdf-hero-kicker">Digital Delivery Ready</div>
                                        <div class="pdf-hero-title" id="p-welcome-title">Your Files are Ready!</div>
                                    </div>
                                </div>
                                <div class="pdf-hero-msg" id="p-welcome-msg">Thank you for your purchase! We've put a lot of
                                    love into these assets. If you need any help, just message us on Etsy.</div>
                                <div class="pdf-hero-meta">
                                    <div class="pdf-hero-pill"><i class="fa-solid fa-link"></i> Access links included</div>
                                    <div class="pdf-hero-pill"><i class="fa-solid fa-comments"></i> Support available on Etsy</div>
                                </div>
                            </div>
                        </div>
                        <div class="pdf-trust-strip" id="p-trust-strip" style="display:none;"></div>

                        <!-- DOWNLOADS -->
                        <div class="pdf-section-label"><span id="p-section-label">YOUR DOWNLOAD LINKS</span></div>
                        <div id="p-items-list"></div>

                        <!-- HOW TO USE -->
                        <div class="pdf-how-section" id="p-how-section">
                            <div class="pdf-how-title"><i class="fa-solid fa-book-open"></i> HOW TO USE YOUR FILES</div>
                            <div class="pdf-steps" id="p-steps-list">
                                <!-- steps injected by JS -->
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="pdf-footer">
                            <div class="pdf-footer-left">
                                <div class="pdf-footer-store" id="p-footer-name">ThorPresets</div>
                                <div class="pdf-footer-link" id="p-footer-link">etsy.com/shop/ThorPresets</div>
                            </div>
                            <div class="pdf-footer-right">
                                <div class="pdf-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                                <div class="pdf-review-cta" id="p-review-cta">LEAVE A REVIEW</div>
                            </div>
                        </div>

                    </div><!-- /pdf-page -->
                </div>
            </div>
        </div>

    </main>

    <div id="toast">
        <span id="toastIcon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
        <div id="toastContent">Done!</div>
    </div>

    <script>
        let assets = [];
        let currentTheme = 'gold-style';
        let currentMode = 'light';
        const EX = @json($record);
        const DEFAULT_WELCOME_TITLE = 'Your Files are Ready!';
        const DEFAULT_WELCOME_MESSAGE = "Thank you for your purchase! We've put a lot of love into these assets. If you need any help, just message us on Etsy.";
        const ARTIST_WELCOME_TITLE = 'Your Procreate Brush Library Is Ready';
        const ARTIST_WELCOME_MESSAGE = 'Thank you for supporting independent art. Every file in this order was prepared for a smooth Procreate workflow on iPad.';
        const DEFAULT_DOWNLOAD_DESC = 'Instant Access | Digital Download | No Expiry';
        const ARTIST_DOWNLOAD_DESC = 'Artist-crafted .brushset file | Instant digital delivery | Ready for Procreate on iPad';
        const ICON_OPTIONS = [
            { icon: 'fa-palette', html: '<i class="fa-solid fa-palette" aria-hidden="true"></i>', label: 'Graphic' },
            { icon: 'fa-clapperboard', html: '<i class="fa-solid fa-clapperboard" aria-hidden="true"></i>', label: 'LUT / Video' },
            { icon: 'fa-mountain-sun', html: '<i class="fa-solid fa-mountain-sun" aria-hidden="true"></i>', label: 'Preset / Photo' },
            { icon: 'fa-camera', html: '<i class="fa-solid fa-camera" aria-hidden="true"></i>', label: 'Photo Pack' },
            { icon: 'fa-image', html: '<i class="fa-solid fa-image" aria-hidden="true"></i>', label: 'Template' },
            { icon: 'fa-music', html: '<i class="fa-solid fa-music" aria-hidden="true"></i>', label: 'Audio' },
            { icon: 'fa-ruler-combined', html: '<i class="fa-solid fa-ruler-combined" aria-hidden="true"></i>', label: 'Vector / SVG' },
            { icon: 'fa-paint-brush', html: '<i class="fa-solid fa-paint-brush" aria-hidden="true"></i>', label: 'Brush' },
            { icon: 'fa-fire', html: '<i class="fa-solid fa-fire" aria-hidden="true"></i>', label: 'Bundle' },
            { icon: 'fa-star', html: '<i class="fa-solid fa-star" aria-hidden="true"></i>', label: 'Other' },
        ];

        function stripHtml(value = '') {
            const temp = document.createElement('div');
            temp.innerHTML = String(value ?? '');
            return (temp.textContent || temp.innerText || '').trim();
        }

        function getIconOption(iconName) {
            return ICON_OPTIONS.find((option) => option.icon === iconName) || ICON_OPTIONS[0];
        }

        function getIconName(value) {
            const matches = String(value ?? '').match(/fa-[a-z0-9-]+/ig) || [];
            const ignored = new Set(['fa-solid', 'fa-regular', 'fa-brands', 'fa-light', 'fa-thin', 'fa-duotone', 'fa-sharp']);
            const iconName = matches.map((match) => match.toLowerCase()).find((match) => !ignored.has(match)) || '';
            if (iconName === 'fa-sparkles') {
                return 'fa-star';
            }

            if (iconName === 'fa-lightbulb') {
                return 'fa-paint-brush';
            }

            return iconName;
        }

        function normalizeIconMarkup(value) {
            return getIconOption(getIconName(value)).html;
        }

        if (EX) {
            EX.welcome_title = stripHtml(EX.welcome_title || '');
            EX.welcome_msg = stripHtml(EX.welcome_msg || '');
            EX.products = Array.isArray(EX.products)
                ? EX.products.map((product) => ({
                    ...product,
                    name: stripHtml(product?.name || ''),
                    desc: stripHtml(product?.desc || ''),
                    type: normalizeIconMarkup(product?.type),
                }))
                : [];
        }

        // ── Helpers ──────────────────────────────────────────
        function v(id) {
            const el = document.getElementById(id);
            return el ? el.value.trim() : '';
        }

        function hasArtistIcon(products = []) {
            return products.some((product) => getIconName(product?.type) === 'fa-paint-brush');
        }

        function isArtistEdition() {
            if (hasArtistIcon(EX?.products || []) || hasArtistIcon(assets)) {
                return true;
            }

            const snapshot = [
                EX?.store_name,
                EX?.title,
                EX?.welcome_title,
                EX?.welcome_msg,
                ...(EX?.products || []).flatMap(p => [p?.name, p?.desc]),
                v('storeName'),
                v('welcomeTitle'),
                v('welcomeMsg'),
                ...assets.flatMap(a => [a.name, a.desc]),
                new URLSearchParams(window.location.search).get('preset')
            ].join(' ').toLowerCase();
            return /drdoom|doom|procreate|brushset|brush library|ipad/.test(snapshot);
        }

        function syncWelcomeDefaults(artistEdition) {
            const welcomeTitleInput = document.getElementById('welcomeTitle');
            const welcomeMsgInput = document.getElementById('welcomeMsg');

            if (welcomeTitleInput) {
                const title = welcomeTitleInput.value.trim();
                if (!title || title === DEFAULT_WELCOME_TITLE || title === ARTIST_WELCOME_TITLE) {
                    welcomeTitleInput.value = artistEdition ? ARTIST_WELCOME_TITLE : DEFAULT_WELCOME_TITLE;
                }
            }

            if (welcomeMsgInput) {
                const message = welcomeMsgInput.value.trim();
                if (!message || message === DEFAULT_WELCOME_MESSAGE || message === ARTIST_WELCOME_MESSAGE) {
                    welcomeMsgInput.value = artistEdition ? ARTIST_WELCOME_MESSAGE : DEFAULT_WELCOME_MESSAGE;
                }
            }
        }

        function applyPdfRootClasses() {
            const root = document.getElementById('pdf-root');
            if (!root) return;
            const classes = ['pdf-page', currentTheme, currentMode === 'dark' ? 'dark-mode' : 'light-mode'];
            if (isArtistEdition()) classes.push('artist-edition');
            root.className = classes.join(' ');
        }

        function setTheme(s) {
            currentTheme = s;
            document.querySelectorAll('.theme-pill').forEach(btn => btn.classList.toggle('active', btn.dataset.style === s));
            applyPdfRootClasses();
            renderAll();
        }

        function setMode(m) {
            currentMode = m;
            document.querySelectorAll('.mode-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.mode === m));
            applyPdfRootClasses();
            renderAll();
        }

        function rescale() {
            const outer = document.getElementById('preview-outer');
            const cnv = document.getElementById('preview-canvas');
            const root = document.getElementById('pdf-root');
            if (!outer || !cnv || !root) return;
            const containerWidth = outer.clientWidth - 40;
            const contentWidth = root.offsetWidth;
            const scale = containerWidth / contentWidth;
            cnv.style.transform = `scale(${Math.min(scale, 1)})`;
        }

        // ── Bootstrap ──────────────────────────────────────────
        function bootstrap() {
            if (EX?.products?.length) {
                EX.products.forEach((product) => addAsset(product.name, product.link, product.type, product.desc || ''));
            } else {
                addAsset('', '', normalizeIconMarkup('fa-palette'), '');
            }

            const savedTheme = EX?.theme;
            const themeMap = {
                gd: 'gold-style',
                pu: 'purple-style',
                bl: 'black-style',
                rs: 'rose-style',
                aq: 'aqua-style',
                'gold-style': 'gold-style',
                'rose-style': 'rose-style',
                'aqua-style': 'aqua-style',
                'purple-style': 'purple-style',
                'black-style': 'black-style'
            };
            if (savedTheme && themeMap[savedTheme]) setTheme(themeMap[savedTheme]);

            // Restore saved mode
            const savedMode = EX?.pdf_mode;
            if (savedMode === 'dark') setMode('dark');

            setupAccordions();
            bindInputs();
            const welcomeTitleInput = document.getElementById('welcomeTitle');
            if (welcomeTitleInput && /<i\s/i.test(welcomeTitleInput.value)) {
                welcomeTitleInput.value = DEFAULT_WELCOME_TITLE;
            }
            const step2Input = document.getElementById('step2');
            if (step2Input && /[^\x20-\x7E]/.test(step2Input.value)) {
                step2Input.value = 'Open Lightroom, Premiere Pro, or your editing app. Go to Import, navigate to the extracted folder, and select the files.';
            }
            syncWelcomeDefaults(isArtistEdition());
             
            // Toggle Listeners
            document.querySelectorAll('.theme-pill').forEach(btn => {
                btn.addEventListener('click', () => setTheme(btn.dataset.style));
            });
            document.querySelectorAll('.mode-btn').forEach(btn => {
                btn.addEventListener('click', () => setMode(btn.dataset.mode));
            });

            renderAll();
            rescale();
            window.addEventListener('resize', rescale);
        }

        // ── Assets ─────────────────────────────────────────────
        function addAsset(name = '', link = '', type = normalizeIconMarkup('fa-palette'), desc = '') {
            assets.push({
                id: 'a' + Date.now() + Math.random(),
                name,
                link,
                type: normalizeIconMarkup(type),
                desc,
            });
            updateStack();
            renderAll();
        }

        function killAsset(id) {
            assets = assets.filter(a => a.id !== id);
            updateStack();
            renderAll();
        }

        const ICONS = ['<i class="fa-solid fa-palette"></i>', '<i class="fa-solid fa-clapperboard"></i>', '<i class="fa-solid fa-mountain-sun"></i>', '<i class="fa-solid fa-camera"></i>', '<i class="fa-solid fa-image"></i>', '<i class="fa-solid fa-music"></i>', '<i class="fa-solid fa-ruler-combined"></i>', '<i class="fa-solid fa-paint-brush"></i>', '<i class="fa-solid fa-fire"></i>', '<i class="fa-solid fa-star"></i>'];

        function updateStack() {
            const stackEl = document.getElementById('productStack');
            stackEl.innerHTML = '';
            assets.forEach((ast, i) => {
                const div = document.createElement('div');
                const selectedIcon = getIconName(ast.type) || 'fa-palette';
                div.className = 'product-item';
                div.innerHTML = `
                    <div class="product-header">
                        <div class="product-badge">FILE 0${i + 1}</div>
                        ${assets.length > 1 ? `<button class="remove-btn" onclick="killAsset('${ast.id}')">✕ Remove</button>` : ''}
                    </div>
                    <div style="margin-bottom:10px;">
                        <label style="font-size:10px;font-weight:700;color:var(--text-dim);letter-spacing:.5px;">TYPE ICON</label>
                        <div class="type-select-row">
                            <div class="type-icon-preview" aria-hidden="true">${getIconOption(selectedIcon).html}</div>
                            <select class="type-select" onchange="syncAsset('${ast.id}','type',getIconOption(this.value).html)">
                            ${ICONS.map(ic => `<option value="${ic}" ${ast.type === ic ? 'selected' : ''}>${ic} — ${iconLabel(ic)}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="input-box">
                        <label>File / Pack Name</label>
                        <input type="text" value="${esc(ast.name)}" placeholder="e.g. Procreate Portrait Brushset" oninput="syncAsset('${ast.id}','name',this.value)">
                    </div>
                    <div class="input-box">
                        <label>Short Description (optional)</label>
                        <textarea rows="2" placeholder="e.g. Artist-crafted .brushset for Procreate on iPad with instant download access." oninput="syncAsset('${ast.id}','desc',this.value)" style="font-size:13px;resize:none;">${esc(ast.desc || '')}</textarea>
                    </div>
                    <div class="input-box" style="margin-bottom:0">
                        <label>Direct Download Link (Google Drive / Dropbox)</label>
                        <input type="text" value="${esc(ast.link)}" placeholder="https://drive.google.com/..." oninput="syncAsset('${ast.id}','link',this.value)">
                    </div>
                `;
                const removeBtn = div.querySelector('.remove-btn');
                if (removeBtn) removeBtn.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i> Remove';
                const typeSelect = div.querySelector('.type-select');
                if (typeSelect) {
                    typeSelect.innerHTML = ICON_OPTIONS
                        .map((option) => `<option value="${option.icon}">${option.label}</option>`)
                        .join('');
                    typeSelect.value = selectedIcon;
                }
                stackEl.appendChild(div);
            });
        }

        function iconLabel(ic) {
            const map = { '<i class="fa-solid fa-palette"></i>': 'Graphic', '<i class="fa-solid fa-clapperboard"></i>': 'LUT / Video', '<i class="fa-solid fa-mountain-sun"></i>': 'Preset / Photo', '<i class="fa-solid fa-camera"></i>': 'Photo Pack', '<i class="fa-solid fa-image"></i>': 'Template', '<i class="fa-solid fa-music"></i>': 'Audio', '<i class="fa-solid fa-ruler-combined"></i>': 'Vector / SVG', '<i class="fa-solid fa-paint-brush"></i>': 'Brush', '<i class="fa-solid fa-fire"></i>': 'Bundle', '<i class="fa-solid fa-star"></i>': 'Other' };
            return map[ic] || 'Asset';
        }

        function esc(s) { return (s || '').replace(/"/g, '&quot;'); }

        function syncAsset(id, key, val) {
            const ast = assets.find(a => a.id === id);
            if (ast) ast[key] = key === 'type' ? normalizeIconMarkup(val) : val;
            if (key === 'type') {
                syncWelcomeDefaults(isArtistEdition());
                updateStack();
            }
            renderAll();
        }

        // ── Bind inputs ───────────────────────────────────────
        function bindInputs() {
            ['storeName', 'storeLink', 'createdBy', 'welcomeTitle', 'welcomeMsg', 'step1', 'step2', 'step3'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', renderAll);
            });
        }

        // ── Render preview ────────────────────────────────────
        function renderAll() {
            applyPdfRootClasses();
            const artistEdition = isArtistEdition();
            const store = v('storeName') || 'ThorPresets';
            const link = v('storeLink') || 'etsy.com/shop/your';
            const createdBy = v('createdBy') || store || 'Independent Artist';
            const rawTitle = v('welcomeTitle');
            const rawMsg = v('welcomeMsg');
            const title = rawTitle || DEFAULT_WELCOME_TITLE;
            const msg = rawMsg || DEFAULT_WELCOME_MESSAGE;
            const s1 = v('step1') || '';
            const s2 = v('step2') || '';
            const s3 = v('step3') || '';
            const effectiveTitle = artistEdition && (!rawTitle || rawTitle === DEFAULT_WELCOME_TITLE)
                ? ARTIST_WELCOME_TITLE
                : title;
            const effectiveMsg = artistEdition && (!rawMsg || rawMsg === DEFAULT_WELCOME_MESSAGE)
                ? ARTIST_WELCOME_MESSAGE
                : msg;

            // Logo Render Logic
            const logoEl = document.getElementById('p-logo');
            const upper = store.toUpperCase();
            const mid = Math.ceil(upper.length / 2);
            logoEl.innerHTML = `${upper.slice(0, mid)}<span>${upper.slice(mid)}</span>`;
            document.getElementById('p-logo-tagline').textContent = artistEdition ? 'INDEPENDENT DIGITAL ARTIST' : 'DIGITAL DOWNLOAD STORE';
            document.getElementById('p-badge-line1').textContent = artistEdition ? 'AUTHENTIC PROCREATE RELEASE' : 'EXCLUSIVE DIGITAL DELIVERY';
            const badgeLine2 = document.querySelector('.pdf-badge-line2');
            if (badgeLine2) badgeLine2.textContent = artistEdition ? 'Artist Download Guide' : 'Thank You Card';
            const heroEmoji = document.querySelector('.pdf-hero-emoji');
            if (heroEmoji) heroEmoji.innerHTML = artistEdition
                ? '<i class="fa-solid fa-paint-brush" aria-hidden="true"></i>'
                : '<i class="fa-solid fa-gift" aria-hidden="true"></i>';
            document.getElementById('p-section-label').textContent = artistEdition ? 'YOUR PROCREATE DOWNLOADS' : 'YOUR DOWNLOAD LINKS';
            const howTitle = document.querySelector('.pdf-how-title');
            if (howTitle) howTitle.textContent = artistEdition ? 'PROCREATE INSTALL GUIDE' : 'HOW TO USE YOUR FILES';
            document.getElementById('p-review-cta').textContent = artistEdition ? 'SUPPORT THIS ARTIST' : 'LEAVE A REVIEW';

            document.getElementById('p-welcome-title').textContent = effectiveTitle;
            document.getElementById('p-welcome-msg').textContent = effectiveMsg;
            document.getElementById('p-footer-name').textContent = store;
            document.getElementById('p-footer-link').innerHTML = `<a href="${link.startsWith('http') ? link : 'https://' + link}" target="_blank" style="color:inherit;text-decoration:none;">${link}</a>`;
            const trustStrip = document.getElementById('p-trust-strip');
            if (artistEdition) {
                trustStrip.style.display = 'grid';
                trustStrip.innerHTML = [
                    ['CREATED BY', createdBy],
                    ['FORMAT', 'Procreate / iPad Ready'],
                    ['DELIVERY', 'Instant Digital Download'],
                ].map(([label, value]) => `
                    <div class="pdf-trust-chip">
                        <div class="pdf-trust-label">${label}</div>
                        <div class="pdf-trust-value">${value}</div>
                    </div>
                `).join('');
            } else {
                trustStrip.style.display = 'none';
                trustStrip.innerHTML = '';
            }

            // Download cards
            const list = document.getElementById('p-items-list');
            list.innerHTML = '';
            assets.forEach((ast, i) => {
                const card = document.createElement('div');
                card.className = `pdf-download-card ${currentTheme}${artistEdition ? ' artist-card' : ''}`;
                const fallbackName = artistEdition ? 'Procreate Brushset Download' : 'Premium Asset Pack';
                const normalizedDesc = artistEdition ? ARTIST_DOWNLOAD_DESC : DEFAULT_DOWNLOAD_DESC;
                const fallbackDesc = artistEdition
                    ? 'Artist-crafted .brushset file · Instant digital delivery · Ready for Procreate on iPad'
                    : 'Instant Access Â· Digital Download Â· No Expiry';
                const metaLine = artistEdition
                    ? '<div class="card-meta-line">INDEPENDENT ARTIST RELEASE · PERSONAL-USE DIGITAL PRODUCT</div>'
                    : '';
                card.innerHTML = `
                    <div class="card-icon-wrap">${ast.type || '<i class="fa-solid fa-palette"></i>'}</div>
                    <div class="card-body">
                        <div class="card-num">FILE ${String(i + 1).padStart(2, '0')}</div>
                        <div class="card-name">${ast.name || fallbackName}</div>
                        <div class="card-sub">${ast.desc ? ast.desc : 'Instant Access · Digital Download · No Expiry'}</div>
                        <a href="${ast.link || '#'}" class="pdf-dl-btn" target="_blank">⬇ Download Now</a>
                    </div>
                `;
                const iconEl = card.querySelector('.card-icon-wrap');
                if (iconEl) iconEl.innerHTML = normalizeIconMarkup(ast.type);
                const buttonEl = card.querySelector('.pdf-dl-btn');
                if (buttonEl) buttonEl.innerHTML = '<i class="fa-solid fa-download" aria-hidden="true"></i> Download Now';
                const subEl = card.querySelector('.card-sub');
                if (subEl) subEl.textContent = ast.desc || normalizedDesc;
                if (artistEdition) {
                    const bodyEl = card.querySelector('.card-body');
                    const ctaEl = card.querySelector('.pdf-dl-btn');
                    if (bodyEl && ctaEl) {
                        const metaEl = document.createElement('div');
                        metaEl.className = 'card-meta-line';
                        metaEl.textContent = 'INDEPENDENT ARTIST RELEASE · PERSONAL-USE DIGITAL PRODUCT';
                        metaEl.textContent = 'INDEPENDENT ARTIST RELEASE | PERSONAL-USE DIGITAL PRODUCT';
                        bodyEl.insertBefore(metaEl, ctaEl);
                    }
                }
                list.appendChild(card);
            });

            // Steps
            const stepData = artistEdition
                ? [
                    { n: '1', title: 'Save To Files', desc: s1 },
                    { n: '2', title: 'Tap To Import', desc: s2 },
                    { n: '3', title: 'Start Creating', desc: s3 },
                ]
                : [
                    { n: '1', title: 'Download Your Files', desc: s1 },
                    { n: '2', title: 'Import Into Your App', desc: s2 },
                    { n: '3', title: 'Apply & Enjoy!', desc: s3 },
                ];
            const numClass = {
                'gold-style': 'gold',
                'rose-style': 'rose',
                'aqua-style': 'aqua',
                'purple-style': 'purple'
            }[currentTheme] || '';
            const stepsEl = document.getElementById('p-steps-list');
            const howSection = document.getElementById('p-how-section');
            stepsEl.innerHTML = '';
            stepData.forEach(st => {
                if (!st.desc) return;
                const row = document.createElement('div');
                row.className = 'pdf-step';
                row.innerHTML = `
                    <div class="pdf-step-num ${numClass}">${st.n}</div>
                    <div class="pdf-step-body">
                        <div class="pdf-step-title">${st.title}</div>
                        <div class="pdf-step-desc">${st.desc}</div>
                    </div>
                `;
                stepsEl.appendChild(row);
            });

            howSection.style.display = stepsEl.children.length ? 'block' : 'none';
            requestAnimationFrame(rescale);
        }

        function v(id) { const el = document.getElementById(id); return el ? el.value : ''; }

        function getPdfFileName() {
            const rawName = (v('pdfFileName') || v('storeName') || 'ThorPresets-Download-Card')
                .replace(/\.pdf$/i, '')
                .replace(/[<>:"/\\|?*\x00-\x1f]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();

            return `${rawName || 'download-card'}.pdf`;
        }

        function setupAccordions() {
            document.querySelectorAll('.glass-card .card-header').forEach((header, index) => {
                const card = header.closest('.glass-card');
                if (!card) return;

                // Open the first one by default if none are open
                if (index === 0 && !document.querySelector('.glass-card.open')) {
                    card.classList.add('open');
                }

                const syncState = () => {
                    header.setAttribute('aria-expanded', card.classList.contains('open') ? 'true' : 'false');
                };

                const toggle = () => {
                    card.classList.toggle('open');
                    syncState();
                };

                header.setAttribute('role', 'button');
                header.setAttribute('tabindex', '0');
                
                // Initialize default state (usually closed unless explicitly open)
                syncState();

                header.addEventListener('click', toggle);
                header.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        toggle();
                    }
                });
            });
        }
        // ── Save ──────────────────────────────────────────────
        document.getElementById('saveBtn').addEventListener('click', async () => {
            const btn = document.getElementById('saveBtn');
            const themeShort = {
                'gold-style': 'gold-style',
                'rose-style': 'rose-style',
                'aqua-style': 'aqua-style',
                'purple-style': 'purple-style',
                'black-style': 'black-style'
            };
            const payload = {
                title: document.getElementById('pdfFileName').value.trim() || v('storeName') + ' — PDF',
                store_name: v('storeName'),
                store_link: v('storeLink'),
                created_by: v('createdBy'),
                welcome_title: v('welcomeTitle'),
                welcome_msg: v('welcomeMsg'),
                step1: v('step1'),
                step2: v('step2'),
                step3: v('step3'),
                theme: themeShort[currentTheme] || currentTheme,
                products: assets.map(a => ({ name: a.name, link: a.link, type: a.type, desc: a.desc })),
                pdf_mode: currentMode,
                id: @json($record?->id ?? ''),
            };

            btn.disabled = true; btn.textContent = 'Saving...';
            try {
                const r = await fetch('{{ route('pdf.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                if (!r.ok) {
                    const errText = await r.text();
                    console.error('Save failed:', r.status, errText);
                    // Try to parse as JSON for validation errors
                    try {
                        const errJson = JSON.parse(errText);
                        const msg = errJson.message || Object.values(errJson.errors || {}).flat().join(', ') || 'Unknown error';
                        notify(msg, 'fa-circle-xmark');
                    } catch {
                        if (r.status === 419) {
                            notify('Session expired. Refresh the page (Ctrl+Shift+R) and try again.', 'fa-triangle-exclamation');
                        } else {
                            notify('Server error (' + r.status + ')', 'fa-circle-xmark');
                        }
                    }
                    return;
                }

                const d = await r.json();
                if (d.success) {
                    notify('Saved successfully!', 'fa-floppy-disk');
                    // Update URL if new record was created
                    if (d.id && !@json($record?->id ?? '')) {
                        window.history.replaceState({}, '', '/studio/pdf/edit/' + d.id);
                    }
                } else {
                    notify(d.message || 'Unknown error', 'fa-circle-xmark');
                }
            } catch (e) {
                console.error('Network error:', e);
                notify('Network error: ' + e.message, 'fa-circle-xmark');
            } finally {
                btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Draft';
            }
        });

        // ── Download PDF ──────────────────────────────────────
        const QUALITY_PRESETS = {
            standard: { scale: 2, format: 'JPEG', quality: 0.82 },
            high: { scale: 3, format: 'JPEG', quality: 0.90 },
            ultra: { scale: 4, format: 'JPEG', quality: 0.95 },
        };

        document.getElementById('downloadBtn').addEventListener('click', async () => {
            const btn = document.getElementById('downloadBtn');
            const spinner = document.getElementById('spinner');
            const cnv = document.getElementById('preview-canvas');
            const root = document.getElementById('pdf-root');
            const preset = QUALITY_PRESETS[document.getElementById('pdfQuality').value] || QUALITY_PRESETS.high;

            btn.disabled = true; btn.textContent = 'Generating...';
            spinner.classList.add('show');
            cnv.style.transform = 'none';

            try {
                const { jsPDF } = window.jspdf;
                const c = await html2canvas(root, {
                    scale: preset.scale,
                    useCORS: true,
                    logging: false,
                    backgroundColor: currentMode === 'dark' ? '#111113' : '#ffffff'
                });

                const imgData = c.toDataURL('image/jpeg', preset.quality);
                const pdf = new jsPDF('p', 'mm', 'a4');
                pdf.addImage(imgData, 'JPEG', 0, 0, 210, 297);

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

                // Calculate size
                const pdfBlob = pdf.output('blob');
                const sizeMB = (pdfBlob.size / (1024 * 1024)).toFixed(1);

                pdf.save(getPdfFileName());
                notify(`PDF download ho gayi! (${sizeMB} MB)`, 'fa-circle-check');
            } catch (e) {
                console.error('PDF error:', e);
                notify('Error: ' + e.message, 'fa-circle-xmark');
            } finally {
                rescale();
                btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-download"></i> Download PDF';
                spinner.classList.remove('show');
            }
        });

        // ── Notify ───────────────────────────────────────────
        function notify(message, iconClass = 'fa-circle-check') {
            const t = document.getElementById('toast');
            document.getElementById('toastIcon').innerHTML = `<i class="fa-solid ${iconClass}"></i>`;
            document.getElementById('toastContent').textContent = message;
            t.classList.add('active');
            setTimeout(() => t.classList.remove('active'), 4000);
        }

        bootstrap();
    </script>
</body>

</html>
