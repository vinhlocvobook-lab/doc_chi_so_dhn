<?php
// Mức độ POC labels
$mucDoPocMap = [
    'AI_CHINH_XAC_CAO' => ['label' => 'Chính xác cao', 'color' => '#15803d'],
    'AI_CHAP_NHAN_DUOC' => ['label' => 'Chấp nhận được', 'color' => '#a16207'],
    'AI_CAN_CANH_BAO' => ['label' => 'Cần cảnh báo', 'color' => '#c2410c'],
    'AI_KHONG_DAT_YEU_CAU' => ['label' => 'Không đạt', 'color' => '#dc2626'],
];
$mucDoTTMap = [
    'TU_DONG_CHAP_NHAN' => ['label' => 'Tự động chấp nhận', 'color' => '#15803d'],
    'CHAP_NHAN_CO_THEO_DOI' => ['label' => 'Chấp nhận, theo dõi', 'color' => '#1d4ed8'],
    'CAN_REVIEW' => ['label' => 'Cần review', 'color' => '#c2410c'],
    'TU_CHOI' => ['label' => 'Từ chối', 'color' => '#dc2626'],
];

function getScoreColor($score, $type = 'poc')
{
    if ($score === null)
        return '#64748b';
    if ($type === 'poc') {
        if ($score >= 90)
            return '#15803d';
        if ($score >= 70)
            return '#a16207';
        if ($score >= 50)
            return '#c2410c';
        return '#dc2626';
    }
    if ($score >= 80)
        return '#15803d';
    if ($score >= 60)
        return '#1d4ed8';
    if ($score >= 40)
        return '#c2410c';
    return '#dc2626';
}
?>
<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h1 style="color: white; margin: 0;">📋 Log Đọc Chỉ Số AI</h1>
        <span style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">
            Tổng: <strong style="color: #a78bfa;">
                <?= number_format($totalItems) ?>
            </strong> bản ghi
        </span>
    </div>

    <!-- Filter Form -->
    <form action="/logs" method="GET" class="filter-form glass-card"
        style="padding: 1.5rem; background: rgba(255,255,255,0.2);">
        <input type="hidden" name="filter" value="1">

        <div class="filter-group">
            <label>Model AI</label>
            <select name="model_name" class="filter-input">
                <option value="">Tất cả</option>
                <?php foreach ($distinctModels as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= $filters['model_name'] === $m ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Trạng thái API</label>
            <select name="trang_thai_api" class="filter-input">
                <option value="">Tất cả</option>
                <option value="thanh_cong" <?= $filters['trang_thai_api'] === 'thanh_cong' ? 'selected' : '' ?>>✅ Thành
                    công</option>
                <option value="loi_api" <?= $filters['trang_thai_api'] === 'loi_api' ? 'selected' : '' ?>>❌ Lỗi API
                </option>
            </select>
        </div>

        <div class="filter-group">
            <label>Chính xác</label>
            <select name="is_exact_match" class="filter-input">
                <option value="">Tất cả</option>
                <option value="1" <?= $filters['is_exact_match'] === '1' ? 'selected' : '' ?>>✅ Đúng</option>
                <option value="0" <?= $filters['is_exact_match'] === '0' ? 'selected' : '' ?>>❌ Sai</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Hợp lý</label>
            <select name="is_rationality" class="filter-input">
                <option value="">Tất cả</option>
                <option value="1" <?= $filters['is_rationality'] === '1' ? 'selected' : '' ?>>✅ Hợp lý</option>
                <option value="0" <?= $filters['is_rationality'] === '0' ? 'selected' : '' ?>>⚠️ Bất hợp lý</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Mức POC</label>
            <select name="muc_do_poc" class="filter-input">
                <option value="">Tất cả</option>
                <?php foreach ($mucDoPocMap as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $filters['muc_do_poc'] === $k ? 'selected' : '' ?>>
                        <?= $v['label'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Mức Thực tế</label>
            <select name="muc_do_thuc_te" class="filter-input">
                <option value="">Tất cả</option>
                <?php foreach ($mucDoTTMap as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $filters['muc_do_thuc_te'] === $k ? 'selected' : '' ?>>
                        <?= $v['label'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>ID Data</label>
            <input type="text" name="id_data" class="filter-input" placeholder="ID bản ghi gốc..."
                value="<?= htmlspecialchars($filters['id_data']) ?>" style="width: 100px;">
        </div>

        <div class="filter-group">
            <label>Từ ngày</label>
            <input type="date" name="date_from" class="filter-input"
                value="<?= htmlspecialchars($filters['date_from']) ?>">
        </div>

        <div class="filter-group">
            <label>Đến ngày</label>
            <input type="date" name="date_to" class="filter-input" value="<?= htmlspecialchars($filters['date_to']) ?>">
        </div>

        <button type="submit" class="btn btn-primary" style="margin-bottom: 0.2rem;">Tìm kiếm</button>
        <a href="/logs" class="btn btn-secondary" style="margin-bottom: 0.2rem; text-decoration: none;">Xóa lọc</a>
    </form>

    <!-- Data Table -->
    <div class="glass-card" style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ID Data</th>
                    <th>Model</th>
                    <th>AI đọc</th>
                    <th>Human</th>
                    <th>Khớp</th>
                    <th>Score POC</th>
                    <th>Score TT</th>
                    <th>Chi phí</th>
                    <th>Thời gian</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <?php
                    $pocColor = getScoreColor($log['score_poc'] ?? null, 'poc');
                    $ttColor = getScoreColor($log['score_thuc_te'] ?? null, 'tt');
                    $mucPoc = $mucDoPocMap[$log['muc_do_poc'] ?? ''] ?? null;
                    $mucTT = $mucDoTTMap[$log['muc_do_thuc_te'] ?? ''] ?? null;
                    ?>
                    <tr>
                        <td><strong>#
                                <?= $log['id'] ?>
                            </strong></td>
                        <td>
                            <a href="/?filter=1&soDanhBo=&nam=&thang=&loaiDongHo=&loaiDongHo_new="
                                style="color: #4338ca; text-decoration: none; font-weight:600;" title="Xem bản ghi gốc">#
                                <?= $log['id_data'] ?>
                            </a>
                        </td>
                        <td>
                            <span
                                style="font-size:0.78rem; padding:2px 8px; background:rgba(79,70,229,0.15); color:#4338ca; border-radius:4px; font-weight:600;">
                                <?= htmlspecialchars($log['model_name'] ?? '—') ?>
                            </span>
                        </td>
                        <td style="font-weight: 700; color: #4338ca;">
                            <?= htmlspecialchars($log['ai_chi_so'] ?? '—') ?>
                            <?php if ($log['ai_chi_so_parse'] !== null && $log['ai_chi_so_parse'] != $log['ai_chi_so']): ?>
                                <div style="font-size:0.7rem; color:#64748b; font-weight:400;">
                                    Parse:
                                    <?= $log['ai_chi_so_parse'] ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 600; color: #1e293b;">
                            <?= $log['human_chi_so'] ?? '—' ?>
                        </td>
                        <td>
                            <?php if ($log['is_exact_match'] === null): ?>
                                <span style="color:#64748b;">—</span>
                            <?php elseif ($log['is_exact_match']): ?>
                                <span style="color:#15803d;" title="Chính xác">✅</span>
                            <?php else: ?>
                                <span style="color:#dc2626; font-weight:600;" title="Sai số: <?= $log['sai_so'] ?>">❌
                                    <?= $log['sai_so'] ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log['score_poc'] !== null): ?>
                                <div style="text-align:center;">
                                    <span style="font-weight:700; color:<?= $pocColor ?>; font-size:1rem;">
                                        <?= $log['score_poc'] ?>
                                    </span>
                                    <?php if ($mucPoc): ?>
                                        <div style="font-size:0.65rem; color:<?= $mucPoc['color'] ?>; opacity:0.8;">
                                            <?= $mucPoc['label'] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span style="color:#64748b;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log['score_thuc_te'] !== null): ?>
                                <div style="text-align:center;">
                                    <span style="font-weight:700; color:<?= $ttColor ?>; font-size:1rem;">
                                        <?= $log['score_thuc_te'] ?>
                                    </span>
                                    <?php if ($mucTT): ?>
                                        <div style="font-size:0.65rem; color:<?= $mucTT['color'] ?>; opacity:0.8;">
                                            <?= $mucTT['label'] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span style="color:#94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="color:#92400e; font-size:0.8rem; font-weight:600;">
                                <?= number_format($log['chi_phi_vnd'] ?? 0, 2) ?>
                            </span>
                            <span style="color:#64748b; font-size:0.7rem;">VND</span>
                        </td>
                        <td style="font-size:0.8rem; color:#475569;">
                            <?= $log['thoi_gian_xu_ly'] ?? 0 ?>ms
                        </td>
                        <td>
                            <?php if (($log['trang_thai_api'] ?? '') === 'thanh_cong'): ?>
                                <span
                                    style="padding:2px 8px; border-radius:4px; background:rgba(22,163,74,0.12); color:#15803d; font-size:0.75rem; font-weight:600;">
                                    Thành công
                                </span>
                            <?php else: ?>
                                <span
                                    style="padding:2px 8px; border-radius:4px; background:rgba(220,38,38,0.1); color:#dc2626; font-size:0.75rem; font-weight:600;">
                                    Lỗi
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.78rem; color:#475569; white-space:nowrap;">
                            <?= isset($log['created_at']) ? date('d/m/Y H:i', strtotime($log['created_at'])) : '—' ?>
                        </td>
                        <td>
                            <button class="btn btn-primary" style="padding: 5px 10px; font-size: 0.82rem;"
                                onclick="showLogDetail(<?= $log['id'] ?>)">Chi tiết</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="13" style="text-align: center; padding: 3rem; color: #64748b;">
                            Không tìm thấy log nào phù hợp
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link">Trước</a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
                ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                    class="page-link <?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link">Sau</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Detail Modal -->
<div id="log-detail-modal"
    style="display:none; position:fixed; z-index:1100; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.8); backdrop-filter:blur(8px); overflow-y:auto;">
    <div style="width:92%; max-width:800px; margin:2rem auto; padding:2rem;
                background:linear-gradient(135deg,#0f0f2e 0%,#1a1a3e 100%);
                border:1px solid rgba(139,92,246,0.25); border-radius:20px;
                box-shadow:0 24px 80px rgba(0,0,0,0.6),inset 0 1px 0 rgba(255,255,255,0.07);">
        <span onclick="closeLogDetail()"
            style="position:absolute; right:1.5rem; top:1rem; font-size:1.8rem; cursor:pointer; color:rgba(255,255,255,0.5); transition:color 0.2s;"
            onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">&times;</span>
        <h2 style="color:#a78bfa; margin-bottom:1.5rem; font-size:1.3rem;">📋 Chi tiết Log AI</h2>
        <div id="log-detail-body" style="color:rgba(255,255,255,0.85); font-size:0.85rem;">
            <div style="text-align:center; padding:2rem; color:rgba(255,255,255,0.4);">⏳ Đang tải...</div>
        </div>
    </div>
</div>

<script>
    (function () {

        window.showLogDetail = async function (id) {
            const modal = document.getElementById('log-detail-modal');
            const body = document.getElementById('log-detail-body');
            modal.style.display = 'block';
            body.innerHTML = '<div style="text-align:center; padding:2rem; color:rgba(255,255,255,0.4);">⏳ Đang tải...</div>';

            try {
                const res = await fetch('/logs/detail?id=' + id, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const log = await res.json();
                if (log.error) {
                    body.innerHTML = '<div style="color:#f87171; padding:1rem;">❌ ' + log.error + '</div>';
                    return;
                }
                body.innerHTML = renderLogDetail(log);
            } catch (e) {
                body.innerHTML = '<div style="color:#f87171; padding:1rem;">❌ Lỗi: ' + e.message + '</div>';
            }
        };

        window.closeLogDetail = function () {
            document.getElementById('log-detail-modal').style.display = 'none';
        };

        // Close on click outside
        document.getElementById('log-detail-modal').addEventListener('click', function (e) {
            if (e.target === this) closeLogDetail();
        });

        function scoreColor(score, type) {
            if (score === null || score === undefined) return '#94a3b8';
            if (type === 'poc') {
                if (score >= 90) return '#4ade80';
                if (score >= 70) return '#fbbf24';
                if (score >= 50) return '#fb923c';
                return '#f87171';
            }
            if (score >= 80) return '#4ade80';
            if (score >= 60) return '#60a5fa';
            if (score >= 40) return '#fb923c';
            return '#f87171';
        }

        function mucDoLabel(key) {
            const map = {
                'AI_CHINH_XAC_CAO': 'Chính xác cao',
                'AI_CHAP_NHAN_DUOC': 'Chấp nhận được',
                'AI_CAN_CANH_BAO': 'Cần cảnh báo',
                'AI_KHONG_DAT_YEU_CAU': 'Không đạt',
                'TU_DONG_CHAP_NHAN': 'Tự động chấp nhận',
                'CHAP_NHAN_CO_THEO_DOI': 'Chấp nhận, theo dõi',
                'CAN_REVIEW': 'Cần review',
                'TU_CHOI': 'Từ chối'
            };
            return map[key] || key || '—';
        }

        function val(v, fallback) {
            return (v !== null && v !== undefined && v !== '') ? v : (fallback || '—');
        }

        function renderLogDetail(l) {
            const matchIcon = l.is_exact_match === null ? '❓' : (l.is_exact_match == 1 ? '✅' : '❌');
            const matchColor = l.is_exact_match === null ? '#94a3b8' : (l.is_exact_match == 1 ? '#4ade80' : '#f87171');
            const ratIcon = l.is_rationality === null ? '❓' : (l.is_rationality == 1 ? '✅' : '⚠️');
            const statusBg = l.trang_thai_api === 'thanh_cong' ? 'rgba(74,222,128,0.15)' : 'rgba(248,113,113,0.15)';
            const statusColor = l.trang_thai_api === 'thanh_cong' ? '#4ade80' : '#f87171';
            const pocClr = scoreColor(l.score_poc, 'poc');
            const ttClr = scoreColor(l.score_thuc_te, 'tt');

            let html = '';

            // Header: Status + ID + Model
            html += `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem; flex-wrap:wrap; gap:0.5rem;">
            <div style="display:flex; align-items:center; gap:0.8rem;">
                <span style="font-size:1.4rem; font-weight:800; color:#a78bfa;">Log #${l.id}</span>
                <span style="padding:3px 10px; border-radius:6px; background:${statusBg}; color:${statusColor}; font-size:0.78rem; font-weight:600;">
                    ${l.trang_thai_api === 'thanh_cong' ? '✅ Thành công' : '❌ Lỗi'}
                </span>
            </div>
            <span style="color:rgba(255,255,255,0.35); font-size:0.78rem;">${val(l.created_at)}</span>
        </div>`;

            // AI vs Human comparison
            html += `
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
            <div style="padding:1rem; background:rgba(139,92,246,0.08); border:1px solid rgba(139,92,246,0.2); border-radius:12px; text-align:center;">
                <div style="color:rgba(255,255,255,0.4); font-size:0.7rem; text-transform:uppercase; letter-spacing:0.05em;">AI Đọc được</div>
                <div style="font-size:1.8rem; font-weight:800; color:#c4b5fd; margin:4px 0;">${val(l.ai_chi_so, 'N/A')}</div>
                <div style="font-size:0.75rem; color:rgba(255,255,255,0.4);">Parse: ${val(l.ai_chi_so_parse, 'N/A')}</div>
            </div>
            <div style="padding:1rem; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:12px; text-align:center;">
                <div style="color:rgba(255,255,255,0.4); font-size:0.7rem; text-transform:uppercase; letter-spacing:0.05em;">Chỉ số thực tế</div>
                <div style="font-size:1.8rem; font-weight:800; color:white; margin:4px 0;">${val(l.human_chi_so, 'N/A')}</div>
                <div style="font-size:0.75rem; color:${matchColor};">${matchIcon} ${l.is_exact_match == 1 ? 'Chính xác' : (l.sai_so !== null ? 'Sai số: ' + l.sai_so : '—')}</div>
            </div>
        </div>`;

            // Score cards
            html += `
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem; margin-bottom:1rem;">
            <div style="padding:0.8rem; background:rgba(255,255,255,0.04); border:1px solid ${pocClr}33; border-radius:10px; text-align:center;">
                <div style="font-size:0.65rem; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.05em;">Score POC</div>
                <div style="font-size:1.6rem; font-weight:800; color:${pocClr}; margin:2px 0;">${val(l.score_poc, '—')}<span style="font-size:0.7rem; font-weight:400;">/100</span></div>
                <div style="font-size:0.7rem; color:${pocClr}; opacity:0.85;">${mucDoLabel(l.muc_do_poc)}</div>
            </div>
            <div style="padding:0.8rem; background:rgba(255,255,255,0.04); border:1px solid ${ttClr}33; border-radius:10px; text-align:center;">
                <div style="font-size:0.65rem; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.05em;">Score Thực tế</div>
                <div style="font-size:1.6rem; font-weight:800; color:${ttClr}; margin:2px 0;">${val(l.score_thuc_te, '—')}<span style="font-size:0.7rem; font-weight:400;">/100</span></div>
                <div style="font-size:0.7rem; color:${ttClr}; opacity:0.85;">${mucDoLabel(l.muc_do_thuc_te)}</div>
            </div>
        </div>`;

            // Rationality
            if (l.is_rationality !== null) {
                const rBg = l.is_rationality == 1 ? 'rgba(74,222,128,0.08)' : 'rgba(251,191,36,0.08)';
                const rBorder = l.is_rationality == 1 ? 'rgba(74,222,128,0.2)' : 'rgba(251,191,36,0.2)';
                html += `<div style="padding:0.6rem 0.8rem; background:${rBg}; border:1px solid ${rBorder}; border-radius:8px; font-size:0.8rem; margin-bottom:1rem;">
                ${ratIcon} <strong>Đánh giá hợp lý:</strong> ${l.is_rationality == 1 ? 'Hợp lý' : 'Bất hợp lý'}
                ${l.ly_do_bat_hop_ly ? ' — ' + l.ly_do_bat_hop_ly : ''}
            </div>`;
            }

            // Detail grid
            html += `
        <div style="padding-top:0.8rem; border-top:1px solid rgba(255,255,255,0.08);">
            <h3 style="color:rgba(167,139,250,0.8); font-size:0.82rem; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.8rem;">Thông tin chi tiết</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.6rem; font-size:0.8rem;">
                <div><span style="color:rgba(255,255,255,0.4);">ID Data:</span> <a href="/?filter=1" style="color:#a5b4fc;">#${val(l.id_data)}</a></div>
                <div><span style="color:rgba(255,255,255,0.4);">Model:</span> <span style="color:#c4b5fd;">${val(l.model_name)}</span></div>
                <div><span style="color:rgba(255,255,255,0.4);">Prompt Version:</span> ${val(l.prompt_version)}</div>
                <div><span style="color:rgba(255,255,255,0.4);">Thời gian xử lý:</span> ${val(l.thoi_gian_xu_ly, 0)}ms</div>
                <div><span style="color:rgba(255,255,255,0.4);">Prompt tokens:</span> ${val(l.prompt_tokens, 0)}</div>
                <div><span style="color:rgba(255,255,255,0.4);">Output tokens:</span> ${val(l.output_tokens, 0)}</div>
                <div><span style="color:rgba(255,255,255,0.4);">Thinking tokens:</span> ${val(l.thinking_tokens, 0)}</div>
                <div><span style="color:rgba(255,255,255,0.4);">Retry count:</span> ${val(l.retry_count, 0)}</div>
                <div><span style="color:rgba(255,255,255,0.4);">Có ký tự X:</span> ${l.co_ky_tu_x == 1 ? 'Có (' + l.so_ky_tu_x + ')' : 'Không'}</div>
                <div><span style="color:rgba(255,255,255,0.4);">Char accuracy:</span> ${l.char_accuracy_rate !== null ? (l.char_accuracy_rate * 100).toFixed(1) + '%' : '—'} (${val(l.char_match_count, 0)}/${val(l.char_total_count, 0)})</div>
                <div style="grid-column:span 2;"><span style="color:rgba(255,255,255,0.4);">Chi phí:</span> <span style="color:#fbbf24; font-weight:600;">${Number(l.chi_phi_vnd || 0).toFixed(4)} VND</span> <span style="color:rgba(255,255,255,0.3);">(${Number(l.chi_phi_usd || 0).toFixed(8)} USD)</span></div>
                <div><span style="color:rgba(255,255,255,0.4);">API started:</span> ${val(l.api_started_at)}</div>
                <div><span style="color:rgba(255,255,255,0.4);">API completed:</span> ${val(l.api_completed_at)}</div>
            </div>
        </div>`;

            // Rationality detail
            if (l.luong_tieu_thu_ai !== null || l.nguong_hop_ly_min !== null) {
                html += `
            <div style="margin-top:1rem; padding-top:0.8rem; border-top:1px solid rgba(255,255,255,0.08);">
                <h3 style="color:rgba(167,139,250,0.8); font-size:0.82rem; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.8rem;">Đánh giá hợp lý</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0.6rem; font-size:0.8rem;">
                    <div><span style="color:rgba(255,255,255,0.4);">Lượng tiêu thụ AI:</span> ${val(l.luong_tieu_thu_ai)} m³</div>
                    <div><span style="color:rgba(255,255,255,0.4);">Ngưỡng min:</span> ${val(l.nguong_hop_ly_min)} m³</div>
                    <div><span style="color:rgba(255,255,255,0.4);">Ngưỡng max:</span> ${val(l.nguong_hop_ly_max)} m³</div>
                </div>
            </div>`;
            }

            // Score detail
            html += `
        <div style="margin-top:1rem; padding-top:0.8rem; border-top:1px solid rgba(255,255,255,0.08);">
            <h3 style="color:rgba(167,139,250,0.8); font-size:0.82rem; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.8rem;">Chi tiết Score</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.6rem; font-size:0.8rem;">
                <div><span style="color:rgba(255,255,255,0.4);">Score số sát:</span> ${val(l.score_so_sat)}</div>
                <div><span style="color:rgba(255,255,255,0.4);">Score ký tự POC:</span> ${val(l.score_ky_tu_poc)}</div>
                <div><span style="color:rgba(255,255,255,0.4);">Score hợp lý:</span> ${val(l.score_hop_ly)}</div>
                <div><span style="color:rgba(255,255,255,0.4);">Score độ lệch TB:</span> ${val(l.score_do_lech_tb)}</div>
                <div><span style="color:rgba(255,255,255,0.4);">Score đọc được:</span> ${val(l.score_doc_duoc)}</div>
            </div>
        </div>`;

            // Prompt text
            if (l.prompt_text) {
                html += `
            <div style="margin-top:1rem; padding-top:0.8rem; border-top:1px solid rgba(255,255,255,0.08);">
                <h3 style="color:rgba(167,139,250,0.8); font-size:0.82rem; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.5rem;">Prompt</h3>
                <pre style="font-size:0.75rem; color:rgba(255,255,255,0.55); white-space:pre-wrap; font-family:monospace; max-height:120px; overflow-y:auto; padding:0.6rem; background:rgba(255,255,255,0.04); border-radius:8px;">${escHtml(l.prompt_text)}</pre>
            </div>`;
            }

            // Error message
            if (l.thong_bao_loi) {
                html += `
            <div style="margin-top:1rem; padding:0.8rem; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); border-radius:8px;">
                <strong style="color:#f87171;">❌ Lỗi:</strong>
                <div style="color:rgba(255,255,255,0.6); margin-top:0.3rem; font-size:0.8rem;">${escHtml(l.thong_bao_loi)}</div>
            </div>`;
            }

            // Raw response (collapsible)
            if (l.raw_response) {
                html += `
            <div style="margin-top:1rem; padding-top:0.8rem; border-top:1px solid rgba(255,255,255,0.08);">
                <details>
                    <summary style="cursor:pointer; color:rgba(167,139,250,0.8); font-size:0.82rem; text-transform:uppercase; letter-spacing:0.05em;">Raw Response (click để xem)</summary>
                    <pre style="margin-top:0.5rem; font-size:0.7rem; color:rgba(255,255,255,0.45); white-space:pre-wrap; font-family:monospace; max-height:200px; overflow-y:auto; padding:0.6rem; background:rgba(255,255,255,0.04); border-radius:8px;">${escHtml(l.raw_response)}</pre>
                </details>
            </div>`;
            }

            return html;
        }

        function escHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

    })();
</script>