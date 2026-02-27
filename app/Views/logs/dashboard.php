<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h1 style="color: white; margin: 0;">📊 Dashboard Phân Tích AI</h1>
    </div>

    <!-- Filter Bar -->
    <div class="glass-card" style="padding: 1rem; margin-bottom: 1.5rem;">
        <form action="/logs/dashboard" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.8rem; align-items: end;">
            
            <div>
                <label style="display: block; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">Từ ngày:</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>" class="filter-input" style="width: 100%; padding: 0.4rem;">
            </div>
            
            <div>
                <label style="display: block; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">Đến ngày:</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>" class="filter-input" style="width: 100%; padding: 0.4rem;">
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">Danh bộ:</label>
                <input type="text" name="soDanhBo" value="<?= htmlspecialchars($filters['soDanhBo'] ?? '') ?>" class="filter-input" placeholder="Nhập số danh bộ..." style="width: 100%; padding: 0.4rem;">
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">AI Model:</label>
                <select name="model_name" class="filter-input" style="width: 100%; padding: 0.4rem;">
                    <option value="">Tất cả Model</option>
                    <?php foreach ($distinctModels as $m): ?>
                        <option value="<?= htmlspecialchars($m) ?>" <?= ($filters['model_name'] === $m) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">Prompt Ver:</label>
                <select name="prompt_version" class="filter-input" style="width: 100%; padding: 0.4rem;">
                    <option value="">Tất cả Prompt</option>
                    <?php foreach ($distinctPromptVersions as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= ($filters['prompt_version'] === $p) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">Loại Đồng Hồ:</label>
                <select name="loaiDongHo_new" class="filter-input" style="width: 100%; padding: 0.4rem;">
                    <option value="">Tất cả LĐH</option>
                    <?php foreach ($distinctMeterTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= ($filters['loaiDongHo_new'] === $t) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; color: #475569; margin-bottom: 0.3rem;">Loại Ảnh:</label>
                <select name="image_type" class="filter-input" style="width: 100%; padding: 0.4rem;">
                    <option value="">Tất cả Loại Ảnh</option>
                    <?php foreach ($distinctImageTypes as $iType): ?>
                        <option value="<?= htmlspecialchars($iType) ?>" <?= ($filters['image_type'] === $iType) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($iType) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.4rem 1rem;">Lọc Dữ Liệu</button>
            </div>
        </form>
    </div>

    <!-- KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <!-- Total Requests -->
        <div class="glass-card" style="padding: 1.5rem; text-align: center;">
            <div style="font-size: 0.9rem; color: #475569; font-weight: 600; text-transform: uppercase;">Tổng Lượt Đọc</div>
            <div style="font-size: 2.5rem; font-weight: 800; color: #1e293b; margin-top: 0.5rem;">
                <?= number_format($overall['total_requests'] ?? 0) ?>
            </div>
            <div style="font-size: 0.85rem; color: #ef4444; margin-top: 0.2rem;">
                Lỗi API: <?= $overall['api_error_rate'] ?? 0 ?>%
            </div>
        </div>

        <!-- Exact Match Rate -->
        <div class="glass-card" style="padding: 1.5rem; text-align: center;">
            <div style="font-size: 0.9rem; color: #475569; font-weight: 600; text-transform: uppercase;">Khớp Kế Toán</div>
            <div style="font-size: 2.5rem; font-weight: 800; color: #16a34a; margin-top: 0.5rem;">
                <?= number_format($overall['exact_match_rate'] ?? 0, 1) ?>%
            </div>
            <div style="font-size: 0.85rem; color: #6366f1; margin-top: 0.2rem;">
                Hợp lý: <?= number_format($overall['rationality_rate'] ?? 0, 1) ?>%
            </div>
        </div>

        <!-- Average Scores -->
        <div class="glass-card" style="padding: 1.5rem; text-align: center;">
            <div style="font-size: 0.9rem; color: #475569; font-weight: 600; text-transform: uppercase;">Điểm Trung Bình</div>
            <div style="font-size: 2.5rem; font-weight: 800; color: #2563eb; margin-top: 0.5rem;">
                <?= number_format($overall['avg_score_poc'] ?? 0, 1) ?>
            </div>
            <div style="font-size: 0.85rem; color: #475569; margin-top: 0.2rem;">
                Thực tế: <?= number_format($overall['avg_score_thuc_te'] ?? 0, 1) ?>
            </div>
        </div>

        <!-- Total Cost -->
        <div class="glass-card" style="padding: 1.5rem; text-align: center;">
            <div style="font-size: 0.9rem; color: #475569; font-weight: 600; text-transform: uppercase;">Chi Phí API (VNĐ)</div>
            <div style="font-size: 2.5rem; font-weight: 800; color: #d97706; margin-top: 0.5rem;">
                <?= number_format($overall['total_cost_vnd'] ?? 0) ?>
            </div>
            <div style="font-size: 0.85rem; color: #475569; margin-top: 0.2rem;">
                Trung bình: <?= ($overall['total_requests'] ?? 0) > 0 ? number_format(($overall['total_cost_vnd'] ?? 0) / $overall['total_requests'], 1) : 0 ?> đ/lượt
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Chart: Match Rate by Model -->
        <div class="glass-card" style="padding: 1.5rem;">
            <h3 style="color: #1e293b; margin-top: 0;">Hiệu suất theo Model</h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <?php if (empty($byModel)): ?>
                    <div style="display: flex; height: 100%; align-items: center; justify-content: center; color: #475569;">
                        Chưa có dữ liệu cho biểu đồ
                    </div>
                <?php else: ?>
                    <canvas id="modelChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table: Metrics by Meter Type -->
        <div class="glass-card" style="padding: 1.5rem; overflow-x: auto;">
            <h3 style="color: #1e293b; margin-top: 0;">Thống kê theo Loại Đồng Hồ</h3>
            <table class="data-table" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th>Loại Đồng Hồ</th>
                        <th>Lượt Đọc</th>
                        <th>Khớp (%)</th>
                        <th>Hợp lý (%)</th>
                        <th>Điểm POC</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($byType)): ?>
                        <tr><td colspan="5" style="text-align: center; color: #475569;">Chưa có dữ liệu</td></tr>
                    <?php else: ?>
                        <?php foreach ($byType as $row): ?>
                            <tr>
                                <td style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($row['group_name'] ?? 'Không xác định') ?></td>
                                <td style="color: #475569;"><?= number_format($row['total_requests']) ?></td>
                                <td style="color: <?= $row['exact_match_rate'] > 80 ? '#16a34a' : ($row['exact_match_rate'] > 50 ? '#d97706' : '#ef4444') ?>; font-weight: 600;">
                                    <?= number_format($row['exact_match_rate'], 1) ?>%
                                </td>
                                <td style="color: #475569;"><?= number_format($row['rationality_rate'], 1) ?>%</td>
                                <td style="color: #475569;"><?= number_format($row['avg_score_poc'], 1) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function initDashboardChart() {
        // Wait for Chart.js to load (important for SPA dynamic script injection)
        if (typeof Chart === 'undefined') {
            setTimeout(initDashboardChart, 50);
            return;
        }

        const byModelData = <?= json_encode($byModel) ?>;
        const canvas = document.getElementById('modelChart');
        if (!canvas) return;

        // Destroy old chart instance to avoid "Canvas is already in use" errors in SPA
        const existingChart = Chart.getChart(canvas);
        if (existingChart) {
            existingChart.destroy();
        }

        if (byModelData && byModelData.length > 0) {
            const labels = byModelData.map(d => d.group_name || 'Khác');
            const matchRates = byModelData.map(d => d.exact_match_rate);
            const rationalRates = byModelData.map(d => d.rationality_rate);

            const ctx = canvas.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Tỷ lệ khớp (%)',
                            data: matchRates,
                            backgroundColor: 'rgba(74, 222, 128, 0.7)',
                            borderColor: 'rgb(74, 222, 128)',
                            borderWidth: 1
                        },
                        {
                            label: 'Tỷ lệ hợp lý (%)',
                            data: rationalRates,
                            backgroundColor: 'rgba(167, 139, 250, 0.7)',
                            borderColor: 'rgb(167, 139, 250)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: { color: '#475569' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#475569' }
                        }
                    },
                    plugins: {
                        legend: { labels: { color: '#1e293b' } }
                    }
                }
            });
        }
    })();
</script>