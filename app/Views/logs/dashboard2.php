<?php
// Helpers
$total_requests = $overall['total_requests'] ?? 0;
$exact_match_rate = $overall['exact_match_rate'] ?? 0;
$rationality_rate = $overall['rationality_rate'] ?? 0;
$avg_score_poc = $overall['avg_score_poc'] ?? 0;
$avg_score_thuc_te = $overall['avg_score_thuc_te'] ?? 0;

// Fake some stats that don't exist in $overall naturally for the UI demo,
// but we bind the main numbers to the real DB.
$auto_process_rate = $rationality_rate; // Using rationality as proxy
$error_count = (int)($total_requests * ($overall['api_error_rate'] ?? 0) / 100);
$review_count = $total_requests - (int)($total_requests * ($exact_match_rate / 100));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Watch — Dashboard Đọc Đồng Hồ Nước (V2)</title>
    <!-- Modern Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* V2 Color Palette */
            --bg-base: #090e17; /* Even deeper dark */
            --bg-surface: rgba(16, 24, 39, 0.7); /* Glassy surface */
            --bg-sidebar: #0b111a;
            
            --border: rgba(255, 255, 255, 0.08);
            --border-hover: rgba(255, 255, 255, 0.15);
            
            --text-main: #e2e8f0;
            --text-muted: #94a3b8;
            
            --accent-primary: #3b82f6; /* Modern Blue */
            --accent-glow: rgba(59, 130, 246, 0.4);
            
            --status-success: #10b981;
            --status-warning: #f59e0b;
            --status-error: #ef4444;
            --status-info: #0ea5e9;

            --font-sans: 'Inter', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-base);
            color: var(--text-main);
            font-family: var(--font-sans);
            font-size: 14px;
            line-height: 1.5;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
            
            /* Subtle background glow effect */
            background-image: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                              radial-gradient(circle at 85% 30%, rgba(16, 185, 129, 0.04) 0%, transparent 50%);
        }

        /* typography */
        h1, h2, h3 { font-weight: 600; letter-spacing: -0.02em; }
        .mono { font-family: var(--font-mono); }
        .text-success { color: var(--status-success); }
        .text-warning { color: var(--status-warning); }
        .text-error { color: var(--status-error); }
        .text-info { color: var(--status-info); }
        .text-primary { color: var(--accent-primary); }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 260px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 24px 20px;
            position: fixed;
            height: 100vh;
            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
            padding: 0 8px;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--accent-primary), var(--status-info));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .logo-text {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: -0.03em;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
        }

        .nav-item.active {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-primary);
            border-left: 3px solid var(--accent-primary);
            padding-left: 11px; /* compensate for border */
        }

        .nav-icon { font-size: 18px; }

        /* ── MAIN CONTENT ── */
        .content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ── HEADER ── */
        .header {
            height: 72px;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(9, 14, 23, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .header-title {
            font-size: 18px;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .live-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: var(--status-success);
            letter-spacing: 0.05em;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background: var(--status-success);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--status-success);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* ── PAGE BODY ── */
        .page-body {
            padding: 32px;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }

        /* ── UTILITIES & COMPONENTS ── */
        .glass-panel {
            background: var(--bg-surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }

        .glass-panel:hover {
            border-color: var(--border-hover);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i { font-style: normal; font-size: 18px; }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--accent-primary);
            color: #fff;
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            background: #2563eb;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border);
        }

        .btn-ghost:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text-main);
            border-color: var(--border-hover);
        }

        /* ── FILTERS ── */
        .filters-container {
            margin-bottom: 32px;
            background: rgba(255,255,255,0.02);
            padding: 16px 24px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
        }
        
        .filters-form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            min-width: 140px;
        }

        .filter-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            font-weight: 600;
        }

        .filter-select, .filter-input {
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
            width: 100%;
            font-family: inherit;
        }

        .filter-select:hover, .filter-input:hover, .filter-select:focus, .filter-input:focus {
            border-color: var(--accent-primary);
            background: rgba(0,0,0,0.4);
        }

        /* ── KPI GRID ── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .kpi-card {
            padding: 24px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Top colored accent line */
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--accent-primary);
            opacity: 0.8;
        }

        .kpi-card.c-success::before { background: var(--status-success); }
        .kpi-card.c-warning::before { background: var(--status-warning); }
        .kpi-card.c-error::before { background: var(--status-error); }
        .kpi-card.c-info::before { background: var(--status-info); }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .kpi-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .kpi-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }

        .kpi-card.c-success .kpi-icon { color: var(--status-success); background: rgba(16, 185, 129, 0.1); }
        .kpi-card.c-primary .kpi-icon { color: var(--accent-primary); background: rgba(59, 130, 246, 0.1); }
        .kpi-card.c-warning .kpi-icon { color: var(--status-warning); background: rgba(245, 158, 11, 0.1); }
        .kpi-card.c-error .kpi-icon { color: var(--status-error); background: rgba(239, 68, 68, 0.1); }
        .kpi-card.c-info .kpi-icon { color: var(--status-info); background: rgba(14, 165, 233, 0.1); }

        .kpi-value {
            font-size: 36px;
            font-weight: 700;
            font-family: var(--font-mono);
            line-height: 1.1;
            margin-bottom: 8px;
            letter-spacing: -0.03em;
        }

        .kpi-meta {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ── GRID LAYOUTS ── */
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }

        .col-span-8 { grid-column: span 8; }
        .col-span-4 { grid-column: span 4; }
        .col-span-6 { grid-column: span 6; }
        .col-span-12 { grid-column: span 12; }

        @media (max-width: 1200px) {
            .col-span-8, .col-span-4, .col-span-6 { grid-column: span 12; }
        }

        /* ── GAUGES & SCORE CARDS ── */
        .score-split {
            display: flex;
            gap: 32px;
            align-items: center;
        }

        .gauge-container {
            position: relative;
            width: 140px;
            height: 140px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .gauge-svg {
            transform: rotate(-90deg);
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0; left: 0;
        }

        .gauge-bg {
            fill: none;
            stroke: rgba(255,255,255,0.05);
            stroke-width: 10;
        }

        .gauge-fill {
            fill: none;
            stroke-width: 10;
            stroke-linecap: round;
            transition: stroke-dashoffset 1.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .gauge-content {
            text-align: center;
            z-index: 2;
        }

        .gauge-val {
            font-size: 32px;
            font-weight: 700;
            font-family: var(--font-mono);
            line-height: 1;
        }
        .gauge-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        
        /* ── TABLE ── */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .modern-table th {
            text-align: left;
            padding: 12px 16px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            font-weight: 600;
        }

        .modern-table td {
            padding: 16px;
            font-size: 13px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            vertical-align: middle;
        }

        .modern-table tr:hover td {
            background: rgba(255,255,255,0.02);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            font-family: var(--font-mono);
        }

        .badge-model {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-primary);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        /* ── ANIMATIONS ── */
        .fade-in-up {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon">💧</div>
            <div class="logo-text">AI Watch.</div>
        </div>

        <ul class="nav-list">
            <li><a href="/" class="nav-item"><span class="nav-icon">📜</span> Lịch sử</a></li>
            <li><a href="/logs/dashboard" class="nav-item"><span class="nav-icon">📊</span> V1 Dashboard</a></li>
            <li><a href="/logs/dashboard2" class="nav-item active"><span class="nav-icon">🚀</span> V2 Dashboard</a></li>
            <li><a href="/logs" class="nav-item"><span class="nav-icon">📋</span> Logs API</a></li>
            <li><a href="/out" class="nav-item"><span class="nav-icon">🚪</span> Thoát</a></li>
        </ul>
        
        <div style="flex:1"></div>
        <div style="font-size: 11px; color: var(--text-muted); text-align: center; font-family: var(--font-mono);">
            v2.0.1-beta
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="content">
        <!-- HEADER -->
        <header class="header">
            <div>
                <h1 class="header-title">Đánh Giá Hiệu Suất OCR Đồng Hồ Nước (V2)</h1>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Dữ liệu: tn_meter_reading_log | Đơn vị: Cấp Nước Cần Giờ</div>
            </div>
            <div class="header-actions">
                <div class="live-indicator">
                    <div class="pulse-dot"></div>
                    LIVE
                </div>
            </div>
        </header>

        <div class="page-body">
            
            <!-- FILTERS -->
            <div class="filters-container fade-in-up">
                <form action="/logs/dashboard2" method="GET" class="filters-form">
                    <div class="filter-group">
                        <span class="filter-label">AI Model</span>
                        <select name="model_name" class="filter-select">
                            <option value="">Tất cả model</option>
                            <?php foreach ($distinctModels as $m): ?>
                                <option value="<?= htmlspecialchars($m) ?>" <?= ($filters['model_name'] === $m) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">Phiên bản Prompt</span>
                        <select name="prompt_version" class="filter-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($distinctPromptVersions as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= ($filters['prompt_version'] === $p) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <span class="filter-label">Từ ngày</span>
                        <input type="date" name="date_from" class="filter-input" value="<?= htmlspecialchars($filters['date_from']) ?>">
                    </div>
                    <div class="filter-group">
                        <span class="filter-label">Đến ngày</span>
                        <input type="date" name="date_to" class="filter-input" value="<?= htmlspecialchars($filters['date_to']) ?>">
                    </div>
                    
                    <div>
                        <button type="submit" class="btn btn-primary" style="height: 38px;">Áp dụng</button>
                    </div>
                </form>
            </div>

            <!-- KPI ROW -->
            <div class="kpi-grid fade-in-up delay-1">
                <div class="glass-panel kpi-card c-primary">
                    <div class="kpi-header">
                        <span class="kpi-title">Tổng ảnh POC</span>
                        <div class="kpi-icon">📷</div>
                    </div>
                    <div>
                        <div class="kpi-value text-primary"><?= number_format($total_requests) ?></div>
                        <div class="kpi-meta">Dữ liệu từ <?= htmlspecialchars($filters['date_from']) ?></div>
                    </div>
                </div>

                <div class="glass-panel kpi-card c-success">
                    <div class="kpi-header">
                        <span class="kpi-title">Độ chính xác (Exact)</span>
                        <div class="kpi-icon">🎯</div>
                    </div>
                    <div>
                        <div class="kpi-value text-success"><?= number_format($exact_match_rate, 1) ?><span style="font-size:20px">%</span></div>
                        <div class="kpi-meta">So với Ground Truth</div>
                    </div>
                </div>

                <div class="glass-panel kpi-card c-info">
                    <div class="kpi-header">
                        <span class="kpi-title">Tự động xử lý</span>
                        <div class="kpi-icon">⚡</div>
                    </div>
                    <div>
                        <div class="kpi-value text-info"><?= number_format($auto_process_rate, 1) ?><span style="font-size:20px">%</span></div>
                        <div class="kpi-meta">Dựa trên Score Hợp Lý</div>
                    </div>
                </div>

                <div class="glass-panel kpi-card c-warning">
                    <div class="kpi-header">
                        <span class="kpi-title">Cần Review</span>
                        <div class="kpi-icon">👁️</div>
                    </div>
                    <div>
                        <div class="kpi-value text-warning"><?= number_format($review_count) ?></div>
                        <div class="kpi-meta">Điểm dưới mức tự tin</div>
                    </div>
                </div>
            </div>

            <!-- BENTO GRID (SCORES & MODELS) -->
            <div class="bento-grid fade-in-up delay-2">
                <!-- Score POC -->
                <div class="glass-panel col-span-6">
                    <div class="section-header">
                        <h2 class="section-title"><i>🎯</i> Phân tích Score POC (Ground Truth)</h2>
                    </div>
                    <div class="score-split">
                        <div class="gauge-container">
                            <svg class="gauge-svg" viewBox="0 0 100 100">
                                <circle class="gauge-bg" cx="50" cy="50" r="42"></circle>
                                <circle class="gauge-fill" id="gauge-poc" cx="50" cy="50" r="42" stroke="var(--status-success)" stroke-dasharray="264" stroke-dashoffset="264"></circle>
                            </svg>
                            <div class="gauge-content">
                                <div class="gauge-val text-success"><?= number_format($avg_score_poc, 1) ?></div>
                                <div class="gauge-sub">Trung bình</div>
                            </div>
                        </div>
                        <div style="flex:1">
                            <p style="color:var(--text-muted); font-size: 13px;">Điểm đo lường độ chính xác ký tự và mức độ bám sát của chỉ số AI đọc được so với toán học cơ bản.</p>
                        </div>
                    </div>
                </div>

                <!-- Score Thực tế -->
                <div class="glass-panel col-span-6">
                    <div class="section-header">
                        <h2 class="section-title"><i>⚡</i> Phân tích Score Thực tế (Rules)</h2>
                    </div>
                    <div class="score-split">
                        <div class="gauge-container">
                            <svg class="gauge-svg" viewBox="0 0 100 100">
                                <circle class="gauge-bg" cx="50" cy="50" r="42"></circle>
                                <circle class="gauge-fill" id="gauge-real" cx="50" cy="50" r="42" stroke="var(--accent-primary)" stroke-dasharray="264" stroke-dashoffset="264"></circle>
                            </svg>
                            <div class="gauge-content">
                                <div class="gauge-val text-primary"><?= number_format($avg_score_thuc_te, 1) ?></div>
                                <div class="gauge-sub">Trung bình</div>
                            </div>
                        </div>
                        <div style="flex:1">
                            <p style="color:var(--text-muted); font-size: 13px;">Điểm đo lường mức độ hợp lý thông qua lịch sử sử dụng, không cần Ground Truth.</p>
                        </div>
                    </div>
                </div>

                <!-- Models Table -->
                <div class="glass-panel col-span-12 fade-in-up delay-3">
                    <div class="section-header">
                        <h2 class="section-title"><i>🤖</i> So sánh Hiệu suất Models</h2>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Model AI</th>
                                    <th>Data Cnt</th>
                                    <th>Exact Match</th>
                                    <th>Score POC</th>
                                    <th>Score Thực Tế</th>
                                    <th>Khớp Hợp Lý</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($byModel)): ?>
                                    <tr><td colspan="6" style="text-align:center">Chưa có dữ liệu</td></tr>
                                <?php else: ?>
                                    <?php foreach ($byModel as $row): 
                                        $match = $row['exact_match_rate'] ?? 0;
                                        $color = $match > 85 ? 'var(--status-success)' : ($match > 60 ? 'var(--status-warning)' : 'var(--status-error)');
                                    ?>
                                    <tr>
                                        <td><span class="badge badge-model"><?= htmlspecialchars($row['group_name'] ?? 'Undefined') ?></span></td>
                                        <td class="mono"><?= number_format($row['total_requests'] ?? 0) ?></td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:10px">
                                                <div style="flex:1; height:4px; background:rgba(255,255,255,0.05); border-radius:2px">
                                                    <div style="width:<?= $match ?>%; height:100%; background:<?= $color ?>; border-radius:2px"></div>
                                                </div>
                                                <span class="mono" style="color:<?= $color ?>"><?= number_format($match, 1) ?>%</span>
                                            </div>
                                        </td>
                                        <td class="mono"><?= number_format($row['avg_score_poc'] ?? 0, 1) ?></td>
                                        <td class="mono"><?= number_format($row['avg_score_thuc_te'] ?? 0, 1) ?></td>
                                        <td class="mono"><?= number_format($row['rationality_rate'] ?? 0, 1) ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!-- /bento-grid -->

        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Animate Gauges
            setTimeout(() => {
                const circ = 2 * Math.PI * 42; // r=42 -> Circumference ~ 263.89
                
                // POC target
                const pocVal = <?= $avg_score_poc ?>;
                const pocGauge = document.getElementById('gauge-poc');
                if (pocGauge) {
                    const offset = circ - (pocVal / 100) * circ;
                    pocGauge.style.strokeDashoffset = offset;
                }

                // Real target
                const realVal = <?= $avg_score_thuc_te ?>;
                const realGauge = document.getElementById('gauge-real');
                if (realGauge) {
                    const offset = circ - (realVal / 100) * circ;
                    realGauge.style.strokeDashoffset = offset;
                }
            }, 300);
        });
    </script>
</body>
</html>
