<?php
// Collect distinct loaiDongHo_new values already in DB for the filter dropdown
// (passed as $meterTypes from controller, each has model_dong_ho)
?>
<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h1 style="color: white;">Lịch sử ghi nhận</h1>
        <button class="btn btn-secondary" onclick="toggleAllDetails()" id="btn-toggle-all"
            style="padding: 8px 16px;">Hiện tất cả chi tiết</button>
    </div>

    <!-- Filter Form -->
    <form action="/" method="GET" class="filter-form glass-card"
        style="padding: 1.5rem; background: rgba(255,255,255,0.2);">
        <input type="hidden" name="filter" value="1">
        <div class="filter-group">
            <label>Năm</label>
            <select name="nam" class="filter-input">
                <option value="">Tất cả</option>
                <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                    <option value="<?= $y ?>" <?= $filters['nam'] == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Tháng</label>
            <select name="thang" class="filter-input">
                <option value="">Tất cả</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $filters['thang'] == $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Số danh bộ</label>
            <input type="text" name="soDanhBo" class="filter-input" placeholder="Nhập số..."
                value="<?= htmlspecialchars($filters['soDanhBo']) ?>">
        </div>
        <div class="filter-group">
            <label>Loại ĐH (API)</label>
            <input list="loaiDongHoList" name="loaiDongHo" class="filter-input" placeholder="Chọn hoặc nhập..."
                value="<?= htmlspecialchars($filters['loaiDongHo']) ?>">
            <datalist id="loaiDongHoList">
                <?php foreach ($distinctLoaiDongHo as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>">
                    <?php endforeach; ?>
            </datalist>
        </div>
        <div class="filter-group">
            <label>Loại ĐH (chuẩn)</label>
            <select name="loaiDongHo_new" class="filter-input">
                <option value="">Tất cả</option>
                <option value="__NULL__" <?= $filters['loaiDongHo_new'] === '__NULL__' ? 'selected' : '' ?>>
                    — Chưa phân loại —
                </option>
                <?php foreach ($meterTypes as $mt): ?>
                    <option value="<?= htmlspecialchars($mt['model_dong_ho'] ?? '') ?>"
                        <?= $filters['loaiDongHo_new'] === ($mt['model_dong_ho'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($mt['model_dong_ho'] ?? '(Chung)') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <label style="margin-bottom: 0;">Phân loại ảnh</label>
                <div style="font-size: 0.75rem;">
                    <a href="javascript:void(0)" onclick="toggleSelectAll('image_type_select', true)"
                        style="color: #4ade80; text-decoration: none;">Chọn hết</a>
                    <span style="color: rgba(255,255,255,0.2); margin: 0 4px;">|</span>
                    <a href="javascript:void(0)" onclick="toggleSelectAll('image_type_select', false)"
                        style="color: #f87171; text-decoration: none;">Bỏ chọn</a>
                </div>
            </div>
            <select name="image_type[]" id="image_type_select" class="filter-input" multiple size="4"
                style="height: auto;">
                <option value="__NULL__" <?= in_array('__NULL__', $filters['image_type']) ? 'selected' : '' ?>>Chưa phân
                    loại</option>
                <option value="hinh_ro" <?= in_array('hinh_ro', $filters['image_type']) ? 'selected' : '' ?>>Hình rõ
                    (hinh_ro)</option>
                <option value="hinh_mo" <?= in_array('hinh_mo', $filters['image_type']) ? 'selected' : '' ?>>Hình mờ
                    (hinh_mo)</option>
                <option value="hinh_khong_day_du" <?= in_array('hinh_khong_day_du', $filters['image_type']) ? 'selected' : '' ?>>Hình không đầy đủ</option>
                <option value="hinh_khong_doc_duoc" <?= in_array('hinh_khong_doc_duoc', $filters['image_type']) ? 'selected' : '' ?>>Hình không đọc được</option>
            </select>
            <div style="font-size: 0.75rem; color: #a5b4fc; margin-top: 4px;">Giữ Ctrl/Cmd để chọn nhiều. (Trống = Tất
                cả)</div>
        </div>
        <div class="filter-group" style="flex-direction: row; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <input type="checkbox" name="coHinh" value="1" id="coHinh" <?= $filters['coHinh'] ? 'checked' : '' ?>>
            <label for="coHinh" style="margin-bottom: 0; cursor: pointer;">Có hình ảnh</label>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-bottom: 0.2rem;">Tìm kiếm</button>
    </form>

    <div id="history-results">
        <div class="glass-card" style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Chỉ số</th>
                        <th>Năm</th>
                        <th>Tháng</th>
                        <th>Loại ĐH (chuẩn)</th>
                        <th>Số Danh Bộ</th>
                        <th>Hình</th>
                        <th>Thời gian</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $item): ?>
                        <tr>
                            <td><strong>#<?= $item['id'] ?></strong></td>
                            <td><?= $item['chiSoNuoc'] ?></td>
                            <td><?= $item['nam'] ?></td>
                            <td><?= $item['thang'] ?></td>
                            <td>
                                <?php if (!empty($item['loaiDongHo_new'])): ?>
                                    <span
                                        style="font-size:0.8rem; padding:2px 8px; background:rgba(79,70,229,0.12); color:#4f46e5; border-radius:4px; font-weight:600;">
                                        <?= htmlspecialchars($item['loaiDongHo_new']) ?>
                                    </span> <br />
                                    <span
                                        style="color:#94a3b8; font-size:0.8rem;"><?= htmlspecialchars($item['loaiDongHo']) ?></span>
                                    <br />
                                    <span style="color:#94a3b8; font-size:0.8rem;">
                                        <?= htmlspecialchars($item['soSerial']) ?></span>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:0.8rem;">chưa phân loại</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $item['soDanhBo'] ?></td>
                            <td>
                                <?php if ($item['linkHinhDongHo']): ?>
                                    <span title="Có hình ảnh" style="color: #4ade80; font-size: 1.2rem;">●</span>
                                <?php else: ?>
                                    <span title="Không có hình" style="color: #f87171; font-size: 1.2rem;">○</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                            <td>
                                <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                                    <button class="btn btn-primary" style="padding: 5px 10px; font-size: 0.85rem;"
                                        onclick="toggleRow(<?= $item['id'] ?>)">Chi tiết</button>
                                    <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.85rem;"
                                        onclick="openEditMeterType(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['loaiDongHo_new'] ?? '')) ?>', '<?= addslashes(htmlspecialchars($item['soDanhBo'])) ?>')">
                                        🔧 Loại ĐH
                                    </button>
                                    <?php if (!empty($item['linkHinhDongHo'])): ?>
                                        <button class="btn btn-secondary"
                                            style="padding: 5px 10px; font-size: 0.85rem; background:rgba(59,130,246,0.15); color:#60a5fa; border:1px solid rgba(59,130,246,0.3);"
                                            onclick="openEditImageType(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['image_type'] ?? '')) ?>', '<?= addslashes(htmlspecialchars($item['soDanhBo'])) ?>')">
                                            🖼️ Phân loại ảnh
                                        </button>
                                        <button class="btn"
                                            style="padding:5px 10px; font-size:0.85rem; background:rgba(139,92,246,0.15); color:#a78bfa; border:1px solid rgba(139,92,246,0.3);"
                                            data-ai-prompt="<?= htmlspecialchars($item['eff_prompt_txt'] ?? '') ?>"
                                            data-ai-model="<?= htmlspecialchars($item['eff_llm_models'] ?? '') ?>"
                                            data-ai-version="<?= htmlspecialchars($item['eff_prompt_version'] ?? '') ?>"
                                            onclick="openAiRead(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['linkHinhDongHo'])) ?>', '<?= addslashes(htmlspecialchars($item['soDanhBo'])) ?>', <?= (int) $item['chiSoNuoc'] ?>, this)">
                                            🤖 Test AI
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <tr id="detail-<?= $item['id'] ?>" class="detail-row">
                            <td colspan="9">
                                <div class="detail-content">
                                    <div style="flex: 1;">
                                        <h3 style="margin-bottom: 1rem; color: var(--primary);">Thông tin chi tiết
                                            #<?= $item['id'] ?> (Danh bộ: <?= $item['soDanhBo'] ?>)</h3>
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                            <div><strong>Loại ĐH (API):</strong> <?= $item['loaiDongHo'] ?></div>
                                            <div><strong>Loại ĐH (chuẩn):</strong>
                                                <span
                                                    style="color:#a5b4fc;"><?= htmlspecialchars($item['loaiDongHo_new'] ?? '—') ?></span>
                                            </div>
                                            <div><strong>Phân loại ảnh:</strong>
                                                <span
                                                    style="color:#4ade80;"><?= htmlspecialchars($item['image_type'] ?? '—') ?></span>
                                            </div>
                                            <div><strong>Năm/Tháng:</strong> <?= $item['nam'] ?>/<?= $item['thang'] ?></div>
                                            <div><strong>Chỉ số TN:</strong> <?= $item['chiSoNuocTN'] ?></div>
                                            <div><strong>Lượng tiêu thụ (Tháng này):</strong>
                                                <?= $item['luongNuocTieuThuThangNay'] ?> m³</div>
                                            <div><strong>Lượng tiêu thụ (Tháng trước):</strong>
                                                <?= $item['luongNuocTieuThuThangTruoc'] ?> m³</div>
                                            <div><strong>Trung bình 3 tháng:</strong>
                                                <?= $item['luongNuocTieuThuTrungBinh3ThangTruoc'] ?> m³</div>
                                            <div><strong>Số Serial:</strong> <?= $item['soSerial'] ?></div>
                                            <div><strong>Thời gian tạo:</strong> <?= $item['created_at'] ?></div>
                                        </div>
                                    </div>
                                    <?php if ($item['linkHinhDongHo']): ?>
                                        <div style="text-align: center;">
                                            <p style="font-weight: 600; margin-bottom: 0.5rem; color: var(--text-main);">Hình
                                                ảnh đồng hồ</p>
                                            <a href="<?= htmlspecialchars($item['linkHinhDongHo']) ?>" target="_blank">
                                                <img src="<?= htmlspecialchars($item['linkHinhDongHo']) ?>" class="detail-img"
                                                    alt="Hình ảnh đồng hồ">
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2rem;">Không tìm thấy dữ liệu phù hợp</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div style="margin-top: 1rem; color: rgba(255,255,255,0.6); font-size: 0.9rem; text-align: center;">
                Tổng số: <strong><?= number_format($totalItems) ?></strong> dòng · Trang <strong><?= $page ?></strong> /
                <strong><?= $totalPages ?></strong>
            </div>
            <div class="pagination">
                <!-- First Page -->
                <?php if ($page > 1): ?>
                    <a href="/?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="page-link"
                        title="Trang đầu">«</a>
                <?php endif; ?>

                <!-- Jump -10 -->
                <?php if ($page > 10): ?>
                    <a href="/?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 10)])) ?>" class="page-link"
                        title="Lùi 10 trang">-10</a>
                <?php endif; ?>

                <!-- Jump -5 -->
                <?php if ($page > 5): ?>
                    <a href="/?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 5)])) ?>" class="page-link"
                        title="Lùi 5 trang">-5</a>
                <?php endif; ?>

                <?php if ($page > 1): ?>
                    <a href="/?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link">Trước</a>
                <?php endif; ?>
                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                    ?>
                    <a href="/?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                        class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="/?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link">Sau</a>
                <?php endif; ?>

                <!-- Jump +5 -->
                <?php if ($page <= $totalPages - 5): ?>
                    <a href="/?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $page + 5)])) ?>"
                        class="page-link" title="Tiếp 5 trang">+5</a>
                <?php endif; ?>

                <!-- Jump +10 -->
                <?php if ($page <= $totalPages - 10): ?>
                    <a href="/?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $page + 10)])) ?>"
                        class="page-link" title="Tiếp 10 trang">+10</a>
                <?php endif; ?>

                <!-- Last Page -->
                <?php if ($page < $totalPages): ?>
                    <a href="/?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="page-link"
                        title="Trang cuối">»</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Meter Type Modal -->
<div id="edit-metertype-modal"
    style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.7); backdrop-filter:blur(5px);">
    <div class="glass-card" style="width:90%; max-width:480px; margin:8rem auto; padding:2rem; position:relative;">
        <span onclick="closeEditMeterType()"
            style="position:absolute; right:1.5rem; top:1rem; font-size:2rem; cursor:pointer; color:white;">&times;</span>
        <h2 style="color:var(--primary); margin-bottom:0.5rem;">🔧 Điều chỉnh Loại Đồng hồ</h2>
        <p id="edit-mt-sodb" style="color:rgba(255,255,255,0.6); font-size:0.85rem; margin-bottom:1.5rem;"></p>

        <div class="filter-group">
            <label>Loại ĐH (chuẩn)</label>
            <select id="edit-mt-select" class="filter-input">
                <option value="">— Xóa phân loại —</option>
                <?php foreach ($meterTypes as $mt): ?>
                    <option value="<?= htmlspecialchars($mt['model_dong_ho'] ?? '') ?>">
                        <?= htmlspecialchars($mt['model_dong_ho'] ?? '(Chung)') ?>
                        <?php if (!empty($mt['loai_hien_thi'])): ?> ·
                            <?= htmlspecialchars($mt['loai_hien_thi']) ?>     <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div
            style="margin-top:1rem; padding:0.75rem; background:rgba(255,165,0,0.1); border:1px solid rgba(255,165,0,0.3); border-radius:8px;">
            <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                <input type="checkbox" id="edit-mt-bulk">
                <span style="color:rgba(255,255,255,0.85); font-size:0.9rem;">Áp dụng cho <strong>tất cả bản
                        ghi</strong> cùng Số Danh Bộ này</span>
            </label>
        </div>

        <div style="margin-top:1.5rem; display:flex; gap:1rem; justify-content:flex-end;">
            <button class="btn btn-secondary" onclick="closeEditMeterType()">Hủy</button>
            <button class="btn btn-primary" id="edit-mt-save-btn" onclick="saveEditMeterType()">Lưu</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="history-toast"
    style="position:fixed; bottom:2rem; right:2rem; z-index:9999; padding:12px 20px; border-radius:8px; font-size:0.9rem; font-weight:500; color:white; opacity:0; transform:translateY(10px); transition:opacity 0.3s,transform 0.3s; pointer-events:none; min-width:220px; box-shadow:0 4px 16px rgba(0,0,0,0.3);">
</div>

<script>
    (function () {
        let _editId = null;

        window.toast = function (msg, type) {
            const t = document.getElementById('history-toast');
            if (!t) return;
            t.textContent = msg;
            t.style.background = type === 'error' ? 'rgba(220,38,38,0.92)' : 'rgba(22,163,74,0.92)';
            t.style.opacity = '1'; t.style.transform = 'translateY(0)';
            clearTimeout(window._hToast);
            window._hToast = setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateY(10px)'; }, 3000);
        }

        window.openEditMeterType = function (id, currentVal, soDanhBo) {
            _editId = id;
            document.getElementById('edit-mt-sodb').textContent = 'ID #' + id + ' · Số danh bộ: ' + soDanhBo;
            document.getElementById('edit-mt-select').value = currentVal || '';
            document.getElementById('edit-mt-bulk').checked = false;
            document.getElementById('edit-metertype-modal').style.display = 'block';
        };

        window.closeEditMeterType = function () {
            document.getElementById('edit-metertype-modal').style.display = 'none';
            _editId = null;
        };

        window.saveEditMeterType = async function () {
            if (!_editId) return;
            const sel = document.getElementById('edit-mt-select').value;
            const bulk = document.getElementById('edit-mt-bulk').checked;
            const btn = document.getElementById('edit-mt-save-btn');
            btn.disabled = true; btn.textContent = 'Đang lưu...';

            const fd = new FormData();
            fd.append('id', _editId);
            fd.append('loaiDongHo_new', sel);
            if (bulk) fd.append('bulk', '1');

            try {
                const res = await fetch('/history/update-meter-type', {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await res.json();
                if (result.success) {
                    window.toast(result.message || 'Đã lưu!');
                    window.closeEditMeterType();
                    if (window.loadPage) {
                        window.loadPage(window.location.pathname + window.location.search, true);
                    } else {
                        window.location.reload();
                    }
                } else {
                    window.toast('Lỗi: ' + (result.error || 'Không xác định'), 'error');
                }
            } catch (e) {
                window.toast('Lỗi kết nối: ' + e.message, 'error');
            } finally {
                btn.disabled = false; btn.textContent = 'Lưu';
            }
        };
        window.toggleSelectAll = function (selectId, status) {
            const select = document.getElementById(selectId);
            if (!select) return;
            for (let i = 0; i < select.options.length; i++) {
                select.options[i].selected = status;
            }
        };
    })();
</script>

<!-- ═══════════════════════════════════════════════════════════════ -->
<!-- AI Read Modal -->
<!-- ═══════════════════════════════════════════════════════════════ -->
<div id="ai-read-modal"
    style="display:none; position:fixed; z-index:1100; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.8); backdrop-filter:blur(8px); overflow-y:auto;">
    <div style="width:92%; max-width:720px; margin:2rem auto; padding:2rem;
                background:linear-gradient(135deg,#0f0f2e 0%,#1a1a3e 100%);
                border:1px solid rgba(139,92,246,0.25); border-radius:20px;
                box-shadow:0 24px 80px rgba(0,0,0,0.6),inset 0 1px 0 rgba(255,255,255,0.07);">
        <span onclick="closeAiRead()"
            style="position:absolute; right:1.5rem; top:1rem; font-size:1.8rem; cursor:pointer; color:rgba(255,255,255,0.5);">&times;</span>
        <h2 style="color:#a78bfa; margin-bottom:0.3rem; font-size:1.3rem;">🤖 Test Đọc chỉ số bằng AI</h2>
        <p id="ai-read-info" style="color:rgba(255,255,255,0.5); font-size:0.8rem; margin-bottom:1.2rem;"></p>

        <!-- Image preview -->
        <div style="text-align:center; margin-bottom:1.2rem;">
            <img id="ai-read-img" src=""
                style="max-height:160px; border-radius:10px; border:1px solid rgba(139,92,246,0.2);">
        </div>

        <!-- Config row -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
            <div>
                <label
                    style="font-size:0.75rem; font-weight:600; color:rgba(167,139,250,0.9); text-transform:uppercase; letter-spacing:0.04em;">Mô
                    hình AI</label>
                <select id="ai-read-model"
                    style="width:100%; padding:8px 12px; border-radius:8px; background:rgba(255,255,255,0.06); border:1px solid rgba(139,92,246,0.3); color:#f1f5f9; font-size:0.9rem; outline:none;">
                    <?php foreach ($llmModels as $lm): ?>
                        <option value="<?= htmlspecialchars($lm['model_name']) ?>">
                            <?= htmlspecialchars($lm['model_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; align-items:flex-end;">
                <button type="button" onclick="viewPastLogs()"
                    style="width:100%; padding:8px 12px; border-radius:8px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:rgba(255,255,255,0.65); cursor:pointer; font-size:0.8rem;">📋
                    Lịch sử đọc</button>
            </div>
        </div>

        <!-- Prompt -->
        <div style="margin-bottom:0.5rem;">
            <label
                style="font-size:0.75rem; font-weight:600; color:rgba(167,139,250,0.9); text-transform:uppercase; letter-spacing:0.04em;">Nội
                dung Prompt</label>
            <textarea id="ai-read-prompt" rows="5"
                style="width:100%; padding:10px 13px; border-radius:8px; background:rgba(255,255,255,0.06); border:1px solid rgba(139,92,246,0.3); color:#f1f5f9; font-size:0.85rem; resize:vertical; outline:none; box-sizing:border-box;"
                placeholder="Nhập prompt cho AI..."></textarea>
        </div>

        <!-- Prompt Version & Save Button -->
        <div style="display:grid; grid-template-columns:1fr 1fr auto; gap:1rem; margin-bottom:1.2rem; align-items:end;">
            <div>
                <label
                    style="font-size:0.75rem; font-weight:600; color:rgba(167,139,250,0.9); text-transform:uppercase; letter-spacing:0.04em;">Phiên
                    bản prompt</label>
                <input type="text" id="ai-read-prompt-version"
                    style="width:100%; padding:8px 12px; border-radius:8px; background:rgba(255,255,255,0.06); border:1px solid rgba(139,92,246,0.3); color:#f1f5f9; font-size:0.9rem; outline:none;"
                    placeholder="VD: v1.0">
            </div>
            <div>
                <label
                    style="font-size:0.75rem; font-weight:600; color:rgba(167,139,250,0.9); text-transform:uppercase; letter-spacing:0.04em;">Phạm
                    vi áp dụng</label>
                <select id="ai-read-prompt-scope"
                    style="width:100%; padding:8px 12px; border-radius:8px; background:rgba(255,255,255,0.06); border:1px solid rgba(139,92,246,0.3); color:#f1f5f9; font-size:0.9rem; outline:none;">
                    <option value="id">Chỉ bản ghi này</option>
                    <option value="soDanhBo">Cùng Số danh bộ</option>
                    <option value="loaiDongHo">Cùng Loại ĐH (API)</option>
                    <option value="loaiDongHo_new">Cùng Loại ĐH (Chuẩn)</option>
                </select>
            </div>
            <div>
                <button type="button" onclick="savePromptInfo(event)"
                    style="padding:8px 16px; border-radius:8px; background:rgba(167,139,250,0.15); border:1px solid rgba(167,139,250,0.5); color:#a78bfa; font-size:0.85rem; cursor:pointer; font-weight:600; transition:all 0.2s;">💾
                    Lưu cấu hình</button>
            </div>
        </div>

        <!-- Prompt suggestions toggle -->
        <div style="margin-bottom:1.2rem;">
            <button type="button" id="ai-prompt-toggle" onclick="toggleAiPrompts()"
                style="background:rgba(139,92,246,0.1); border:1px dashed rgba(139,92,246,0.35); color:rgba(167,139,250,0.9); padding:5px 12px; border-radius:6px; cursor:pointer; font-size:0.8rem;">💡
                Mẫu Prompt gợi ý <span id="ai-prompt-arrow">▼</span></button>
            <div id="ai-prompt-panel" style="display:none; margin-top:0.6rem;">
                <p style="font-size:0.75rem; color:rgba(255,255,255,0.4); margin-bottom:0.5rem;">Nhấn <strong>Áp
                        dụng</strong> để điền vào prompt:</p>
                <div id="ai-prompt-list" style="display:flex; flex-direction:column; gap:0.5rem;"></div>
            </div>
        </div>

        <!-- Action -->
        <div style="text-align:center; margin-bottom:1.2rem;">
            <button id="ai-read-start" onclick="startAiRead()"
                style="padding:10px 32px; border-radius:10px; background:linear-gradient(135deg,#7c3aed,#4f46e5); color:white; border:none; font-size:0.95rem; font-weight:600; cursor:pointer; letter-spacing:0.02em; transition:opacity 0.2s;">▶
                Bắt đầu đọc</button>
        </div>

        <!-- SSE Progress -->
        <div id="ai-progress" style="display:none; margin-bottom:1rem;">
            <div
                style="padding:1rem; background:rgba(255,255,255,0.04); border:1px solid rgba(139,92,246,0.15); border-radius:12px;">
                <div id="ai-steps" style="display:flex; flex-direction:column; gap:0.5rem; font-size:0.82rem;"></div>
            </div>
        </div>

        <!-- Result -->
        <div id="ai-result" style="display:none;">
            <div
                style="padding:1.2rem; background:rgba(74,222,128,0.06); border:1px solid rgba(74,222,128,0.2); border-radius:12px;">
                <h3 style="color:#4ade80; font-size:0.9rem; margin-bottom:0.8rem;">📊 Kết quả đọc chỉ số</h3>
                <div id="ai-result-body" style="font-size:0.85rem; color:rgba(255,255,255,0.85);"></div>
            </div>
        </div>

        <!-- Error -->
        <div id="ai-error" style="display:none;">
            <div
                style="padding:1rem; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:12px;">
                <p id="ai-error-msg" style="color:#f87171; font-size:0.85rem; margin:0;"></p>
            </div>
        </div>

        <!-- Past logs -->
        <div id="ai-past-logs" style="display:none; margin-top:1rem;">
            <div
                style="padding:1rem; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); border-radius:12px;">
                <h3 style="color:rgba(167,139,250,0.8); font-size:0.82rem; margin-bottom:0.6rem;">📋 Lịch sử đọc trước
                    đó</h3>
                <div id="ai-past-logs-body"
                    style="font-size:0.8rem; color:rgba(255,255,255,0.7); max-height:200px; overflow-y:auto;"></div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        let _aiReadId = null;
        let _aiReadSDB = '';
        let _aiReadHumanCS = 0;
        let _sseSource = null;
        let _aiPromptsOpen = false;

        const AI_PROMPTS = [
            {
                name: '🔢 Cơ bản – Chỉ trả số',
                text: `Đây là hình ảnh đồng hồ nước.\nHãy đọc chỉ số nước hiển thị trên mặt đồng hồ.\nChỉ trả về con số nguyên, không giải thích thêm.`
            },
            {
                name: '🧠 Chi tiết – JSON',
                text: `Bạn là chuyên gia đọc chỉ số đồng hồ nước.\nQuan sát kỹ hình ảnh đồng hồ nước.\nHãy đọc chỉ số và trả về kết quả theo JSON:\n{"chi_so": "<số>", "chi_so_phan_nguyen": "<phần nguyên>", "chi_so_phan_thap_phan": "<phần thập phân>", "nhan_hieu": "<nhãn hiệu>", "model": "<model>", "so_serial": "<serial>", "giai_thich_chi_so": "<giải thích>"}`
            },
            {
                name: '📋 Đầy đủ (từ mẫu gốc)',
                text: `Bối cảnh: Bạn là hệ thống AI chuyên phân tích hình ảnh đồng hồ nước, tự động nhận diện model, trích xuất thông tin.\n\nNhiệm vụ: Xác định nhãn hiệu, model, serial. Dựa vào model, áp dụng quy tắc đọc số. Soạn giải thích quá trình đọc.\n\nQuy tắc trích xuất:\n- Nhận dạng: nhãn hiệu, model, serial\n- Chỉ số: tìm vùng chỉ số chính (khung chữ nhật, m³)\n- Nếu chữ số không xác định: thay bằng "X"\n- Luôn ghi nhận chữ số nhỏ hơn khi kim nằm giữa 2 vạch\n\nKết quả JSON:\n{"chi_so": "9876,54", "chi_so_phan_nguyen": "9876", "chi_so_phan_thap_phan": "54", "nhan_hieu": "...", "model": "...", "so_serial": "...", "giai_thich_chi_so": "..."}`
            },
            {
                name: '⚠️ Xử lý mờ/lệch',
                text: `Bạn là AI chuyên đọc chỉ số đồng hồ nước. Phân tích hình ảnh.\nQuy tắc:\n- Nếu hình mờ, góc lệch: ước tính tốt nhất\n- Bỏ qua bụi bẩn, sương\n- Chữ số không rõ: thay bằng "X"\nTrả về JSON: {"chi_so": "<số>", "chi_so_phan_nguyen": "<số>", "nhan_hieu": null, "model": null, "so_serial": null, "giai_thich_chi_so": "<giải thích>"}`
            }
        ];

        window.openAiRead = function (id, imgUrl, sodb, humanCS, btnEl) {
            _aiReadId = id;
            _aiReadSDB = sodb;
            _aiReadHumanCS = humanCS;
            document.getElementById('ai-read-info').textContent = `ID #${id} · Số danh bộ: ${sodb} · Chỉ số hiện tại: ${humanCS}`;
            document.getElementById('ai-read-img').src = imgUrl;
            document.getElementById('ai-progress').style.display = 'none';
            document.getElementById('ai-result').style.display = 'none';
            document.getElementById('ai-error').style.display = 'none';
            document.getElementById('ai-past-logs').style.display = 'none';
            document.getElementById('ai-steps').innerHTML = '';
            document.getElementById('ai-read-start').disabled = false;
            document.getElementById('ai-read-start').textContent = '▶ Bắt đầu đọc';

            if (btnEl) {
                const lastPromptTxt = btnEl.getAttribute('data-ai-prompt');
                const lastModel = btnEl.getAttribute('data-ai-model');
                const lastVersion = btnEl.getAttribute('data-ai-version');

                document.getElementById('ai-read-prompt').value = lastPromptTxt || '';
                if (lastModel) document.getElementById('ai-read-model').value = lastModel;
                document.getElementById('ai-read-prompt-version').value = lastVersion || '';
                document.getElementById('ai-read-prompt-scope').value = 'id';
            }

            document.getElementById('ai-read-modal').style.display = 'block';
        };

        window.closeAiRead = function () {
            document.getElementById('ai-read-modal').style.display = 'none';
            if (_sseSource) { _sseSource.close(); _sseSource = null; }
            _aiReadId = null;
        };

        window.toggleAiPrompts = function () {
            _aiPromptsOpen = !_aiPromptsOpen;
            const panel = document.getElementById('ai-prompt-panel');
            document.getElementById('ai-prompt-arrow').textContent = _aiPromptsOpen ? '▲' : '▼';
            panel.style.display = _aiPromptsOpen ? 'block' : 'none';
            if (_aiPromptsOpen) buildAiPrompts();
        };

        function buildAiPrompts() {
            const list = document.getElementById('ai-prompt-list');
            list.innerHTML = '';
            AI_PROMPTS.forEach((p, i) => {
                const d = document.createElement('div');
                d.style.cssText = 'padding:8px 12px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:8px; display:flex; justify-content:space-between; align-items:center;';
                d.innerHTML = `<span style="color:white; font-size:0.82rem;">${p.name}</span><button type="button" onclick="applyAiPrompt(${i})" style="flex-shrink:0; background:#4f46e5; color:white; border:none; border-radius:6px; padding:3px 10px; font-size:0.75rem; cursor:pointer;">Áp dụng</button>`;
                list.appendChild(d);
            });
        }

        window.applyAiPrompt = function (idx) {
            const ta = document.getElementById('ai-read-prompt');
            ta.value = AI_PROMPTS[idx].text;
            ta.style.borderColor = '#4f46e5';
            setTimeout(() => ta.style.borderColor = '', 1000);
        };

        window.savePromptInfo = async function (event) {
            if (!_aiReadId) return;
            const model = document.getElementById('ai-read-model').value;
            const prompt = document.getElementById('ai-read-prompt').value.trim();
            const version = document.getElementById('ai-read-prompt-version').value.trim();
            const scope = document.getElementById('ai-read-prompt-scope').value;

            const fd = new FormData();
            fd.append('id', _aiReadId);
            fd.append('modelName', model);
            fd.append('promptText', prompt);
            fd.append('promptVersion', version);
            fd.append('applyScope', scope);

            const btn = event.currentTarget || document.querySelector('button[onclick="savePromptInfo(event)"]');
            const originalText = btn.textContent;
            btn.textContent = '⏳ Đang lưu...';
            btn.disabled = true;

            try {
                const res = await fetch('/history/save-prompt-info', {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await res.json();
                if (result.success) {
                    window.toast(result.message || 'Đã lưu cấu hình prompt!');
                    // Optionally update the list button attributes here
                    const btnEl = document.querySelector(`button[onclick*="openAiRead(${_aiReadId},"]`);
                    if (btnEl) {
                        btnEl.setAttribute('data-ai-prompt', prompt);
                        btnEl.setAttribute('data-ai-model', model);
                        btnEl.setAttribute('data-ai-version', version);
                    }
                } else {
                    window.toast('Lỗi: ' + (result.error || 'Không xác định'), 'error');
                }
            } catch (e) {
                window.toast('Lỗi kết nối: ' + e.message, 'error');
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        };

        window.startAiRead = function () {
            if (!_aiReadId) return;
            const model = document.getElementById('ai-read-model').value;
            const prompt = document.getElementById('ai-read-prompt').value.trim();
            if (!prompt) { alert('Vui lòng nhập prompt!'); return; }

            // Reset UI
            const steps = document.getElementById('ai-steps');
            steps.innerHTML = '';
            document.getElementById('ai-progress').style.display = 'block';
            document.getElementById('ai-result').style.display = 'none';
            document.getElementById('ai-error').style.display = 'none';
            const btn = document.getElementById('ai-read-start');
            btn.disabled = true;
            btn.textContent = '⏳ Đang xử lý...';

            // Build SSE URL
            const params = new URLSearchParams({
                id: _aiReadId,
                model_name: model,
                prompt_text: prompt
            });
            const url = '/history/ai-read?' + params.toString();

            if (_sseSource) _sseSource.close();
            _sseSource = new EventSource(url);

            _sseSource.addEventListener('progress', (e) => {
                const d = JSON.parse(e.data);
                addStep(d.label || d.step);
            });

            _sseSource.addEventListener('done', (e) => {
                _sseSource.close();
                _sseSource = null;
                btn.disabled = false;
                btn.textContent = '▶ Bắt đầu đọc';
                addStep('✅ Hoàn tất!');

                const d = JSON.parse(e.data);
                showResult(d);
            });

            _sseSource.addEventListener('error_event', (e) => {
                _sseSource.close();
                _sseSource = null;
                btn.disabled = false;
                btn.textContent = '▶ Bắt đầu đọc';
                const d = JSON.parse(e.data);
                document.getElementById('ai-error-msg').textContent = '❌ ' + (d.message || 'Lỗi không xác định');
                document.getElementById('ai-error').style.display = 'block';
            });

            _sseSource.onerror = function (e) {
                // SSE connection closed — check if it was normal completion
                if (_sseSource) {
                    _sseSource.close();
                    _sseSource = null;
                }
                btn.disabled = false;
                btn.textContent = '▶ Bắt đầu đọc';
            };
        };

        function addStep(label) {
            const el = document.createElement('div');
            el.style.cssText = 'padding:4px 0; color:rgba(255,255,255,0.8); animation:fadeIn 0.3s;';
            el.textContent = label;
            document.getElementById('ai-steps').appendChild(el);
            // Scroll to bottom
            const container = document.getElementById('ai-steps').parentElement;
            container.scrollTop = container.scrollHeight;
        }

        function getScorePocColor(score) {
            if (score === null || score === undefined) return '#94a3b8';
            if (score >= 90) return '#4ade80';
            if (score >= 70) return '#fbbf24';
            if (score >= 50) return '#fb923c';
            return '#f87171';
        }
        function getScoreTTColor(score) {
            if (score === null || score === undefined) return '#94a3b8';
            if (score >= 80) return '#4ade80';
            if (score >= 60) return '#60a5fa';
            if (score >= 40) return '#fb923c';
            return '#f87171';
        }
        function getMucDoLabel(mucDo) {
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
            return map[mucDo] || mucDo || '—';
        }

        function showResult(d) {
            const body = document.getElementById('ai-result-body');
            const match = d.is_exact_match === 1;
            const matchIcon = d.is_exact_match === null ? '❓' : (match ? '✅' : '❌');
            const matchColor = d.is_exact_match === null ? '#94a3b8' : (match ? '#4ade80' : '#f87171');

            let html = `
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem;">
                <div>
                    <div style="color:rgba(255,255,255,0.4); font-size:0.7rem; text-transform:uppercase;">AI đọc được</div>
                    <div style="font-size:1.8rem; font-weight:700; color:#a78bfa;">${d.ai_chi_so ?? 'N/A'}</div>
                    <div style="font-size:0.75rem; color:rgba(255,255,255,0.5);">Parse: ${d.ai_chi_so_parse ?? 'N/A'}</div>
                </div>
                <div>
                    <div style="color:rgba(255,255,255,0.4); font-size:0.7rem; text-transform:uppercase;">Chỉ số thực tế</div>
                    <div style="font-size:1.8rem; font-weight:700; color:white;">${d.human_chi_so ?? 'N/A'}</div>
                    <div style="font-size:0.75rem; color:${matchColor};">${matchIcon} ${match ? 'Chính xác' : (d.sai_so !== null ? 'Sai số: ' + d.sai_so : 'Không so sánh được')}</div>
                </div>
            </div>`;

            // ── Scoring badges ──
            const sPoc = d.score_poc;
            const sTT = d.score_thuc_te;
            const pocColor = getScorePocColor(sPoc);
            const ttColor = getScoreTTColor(sTT);
            html += `
            <div style="margin-top:0.8rem; display:flex; gap:0.8rem; flex-wrap:wrap;">
                <div style="flex:1; min-width:140px; padding:0.7rem; background:rgba(255,255,255,0.04); border:1px solid ${pocColor}33; border-radius:10px; text-align:center;">
                    <div style="font-size:0.65rem; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.05em;">Score POC</div>
                    <div style="font-size:1.6rem; font-weight:800; color:${pocColor}; margin:2px 0;">${sPoc ?? '—'}<span style="font-size:0.7rem; font-weight:400;">/100</span></div>
                    <div style="font-size:0.7rem; color:${pocColor}; opacity:0.85;">${getMucDoLabel(d.muc_do_poc)}</div>
                </div>
                <div style="flex:1; min-width:140px; padding:0.7rem; background:rgba(255,255,255,0.04); border:1px solid ${ttColor}33; border-radius:10px; text-align:center;">
                    <div style="font-size:0.65rem; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.05em;">Score Thực tế</div>
                    <div style="font-size:1.6rem; font-weight:800; color:${ttColor}; margin:2px 0;">${sTT ?? '—'}<span style="font-size:0.7rem; font-weight:400;">/100</span></div>
                    <div style="font-size:0.7rem; color:${ttColor}; opacity:0.85;">${getMucDoLabel(d.muc_do_thuc_te)}</div>
                </div>
            </div>`;

            // ── Rationality ──
            if (d.ly_do_hop_ly) {
                const rIcon = d.is_rationality === true ? '✅' : (d.is_rationality === false ? '⚠️' : '❓');
                const rColor = d.is_rationality === true ? 'rgba(74,222,128,0.15)' : 'rgba(251,191,36,0.12)';
                const rBorder = d.is_rationality === true ? 'rgba(74,222,128,0.25)' : 'rgba(251,191,36,0.25)';
                html += `<div style="margin-top:0.6rem; padding:0.5rem 0.8rem; background:${rColor}; border:1px solid ${rBorder}; border-radius:8px; font-size:0.78rem; color:rgba(255,255,255,0.75);">${rIcon} ${d.ly_do_hop_ly}</div>`;
            }

            // ── Details grid ──
            html += `
            <div style="margin-top:0.8rem; padding-top:0.8rem; border-top:1px solid rgba(255,255,255,0.08); display:grid; grid-template-columns:repeat(3,1fr); gap:0.5rem; font-size:0.78rem;">
                <div><span style="color:rgba(255,255,255,0.4);">Model:</span> <span style="color:#c4b5fd;">${d.model_version || ''}</span></div>
                <div><span style="color:rgba(255,255,255,0.4);">Thời gian:</span> ${d.thoi_gian_ms || 0}ms</div>
                <div><span style="color:rgba(255,255,255,0.4);">Log ID:</span> #${d.log_id || ''}</div>
                <div><span style="color:rgba(255,255,255,0.4);">Prompt tokens:</span> ${d.tokens?.prompt || 0}</div>
                <div><span style="color:rgba(255,255,255,0.4);">Output tokens:</span> ${d.tokens?.output || 0}</div>
                <div><span style="color:rgba(255,255,255,0.4);">Thinking:</span> ${d.tokens?.thinking || 0}</div>
                <div style="grid-column:span 3;"><span style="color:rgba(255,255,255,0.4);">Chi phí:</span> <span style="color:#fbbf24;">${Number(d.cost?.vnd || 0).toFixed(2)} VND</span> (${Number(d.cost?.usd || 0).toFixed(6)} USD)</div>
            </div>`;

            if (d.content?.giai_thich_chi_so) {
                html += `<div style="margin-top:0.6rem; padding:0.6rem; background:rgba(255,255,255,0.04); border-radius:8px; font-size:0.78rem; color:rgba(255,255,255,0.6);">💬 ${d.content.giai_thich_chi_so}</div>`;
            }

            body.innerHTML = html;
            document.getElementById('ai-result').style.display = 'block';
        }

        window.viewPastLogs = async function () {
            if (!_aiReadId) return;
            const panel = document.getElementById('ai-past-logs');
            const body = document.getElementById('ai-past-logs-body');
            if (panel.style.display === 'block') { panel.style.display = 'none'; return; }

            body.innerHTML = '<div style="text-align:center; padding:1rem;">⏳ Đang tải...</div>';
            panel.style.display = 'block';

            try {
                const res = await fetch('/history/ai-read-logs?id_data=' + _aiReadId);
                const logs = await res.json();
                if (!logs.length) {
                    body.innerHTML = '<div style="text-align:center; padding:1rem; color:rgba(255,255,255,0.4);">Chưa có lịch sử đọc</div>';
                    return;
                }
                body.innerHTML = logs.map(l => {
                    const pocClr = getScorePocColor(l.score_poc);
                    const ttClr = getScoreTTColor(l.score_thuc_te);
                    const pocBadge = l.score_poc !== null && l.score_poc !== undefined
                        ? `<span style="padding:1px 6px; border-radius:4px; background:${pocClr}22; color:${pocClr}; font-weight:600; font-size:0.75rem;">POC:${l.score_poc}</span>`
                        : '';
                    const ttBadge = l.score_thuc_te !== null && l.score_thuc_te !== undefined
                        ? `<span style="padding:1px 6px; border-radius:4px; background:${ttClr}22; color:${ttClr}; font-weight:600; font-size:0.75rem;">TT:${l.score_thuc_te}</span>`
                        : '';
                    return `
                    <div style="padding:8px 10px; border-bottom:1px solid rgba(255,255,255,0.06);">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="color:#a78bfa;">#${l.id} · ${l.model_name}</span>
                            <span style="color:rgba(255,255,255,0.35);">${l.created_at}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; margin-top:2px;">
                            <span>AI: <strong>${l.ai_chi_so || 'N/A'}</strong></span>
                            <span style="color:rgba(255,255,255,0.35);">|</span>
                            <span>Human: ${l.human_chi_so || 'N/A'}</span>
                            <span style="color:rgba(255,255,255,0.35);">|</span>
                            <span>${l.trang_thai_api === 'thanh_cong' ? '✅' : '❌'}</span>
                            ${pocBadge} ${ttBadge}
                            <span style="color:#fbbf24; font-size:0.75rem;">${Number(l.chi_phi_vnd || 0).toFixed(2)} VND</span>
                        </div>
                    </div>`;
                }).join('');
            } catch (e) {
                body.innerHTML = '<div style="color:#f87171;">Lỗi: ' + e.message + '</div>';
            }
        };
    })();
</script>

<!-- Edit Image Type Modal -->
<div id="edit-imagetype-modal"
    style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.7); backdrop-filter:blur(5px);">
    <div class="glass-card" style="width:90%; max-width:480px; margin:8rem auto; padding:2rem; position:relative;">
        <span onclick="closeEditImageType()"
            style="position:absolute; right:1.5rem; top:1rem; font-size:2rem; cursor:pointer; color:white;">&times;</span>
        <h2 style="color:var(--primary); margin-bottom:0.5rem;">🖼️ Phân loại ảnh</h2>
        <p id="edit-it-sodb" style="color:rgba(255,255,255,0.6); font-size:0.85rem; margin-bottom:1.5rem;"></p>

        <div class="filter-group">
            <label>Loại Hình Ảnh</label>
            <select id="edit-it-select" class="filter-input">
                <option value="">— Chưa phân loại —</option>
                <option value="hinh_ro">Hình rõ (hinh_ro)</option>
                <option value="hinh_mo">Hình mờ (hinh_mo)</option>
                <option value="hinh_khong_day_du">Hình không đầy đủ (hinh_khong_day_du)</option>
                <option value="hinh_khong_doc_duoc">Hình không đọc được (hinh_khong_doc_duoc)</option>
            </select>
        </div>

        <div style="margin-top:1.5rem; display:flex; gap:1rem; justify-content:flex-end;">
            <button class="btn btn-secondary" onclick="closeEditImageType()">Hủy</button>
            <button class="btn btn-primary" id="edit-it-save-btn" onclick="saveEditImageType()">Lưu</button>
        </div>
    </div>
</div>

<script>
    (function () {
        let _editItId = null;

        window.openEditImageType = function (id, currentVal, soDanhBo) {
            _editItId = id;
            document.getElementById('edit-it-sodb').textContent = 'ID #' + id + ' · Số danh bộ: ' + soDanhBo;
            document.getElementById('edit-it-select').value = currentVal || '';
            document.getElementById('edit-imagetype-modal').style.display = 'block';
        };

        window.closeEditImageType = function () {
            document.getElementById('edit-imagetype-modal').style.display = 'none';
            _editItId = null;
        };

        window.saveEditImageType = async function () {
            if (!_editItId) return;
            const sel = document.getElementById('edit-it-select').value;
            const btn = document.getElementById('edit-it-save-btn');
            btn.disabled = true; btn.textContent = 'Đang lưu...';

            const fd = new FormData();
            fd.append('id', _editItId);
            fd.append('image_type', sel);

            try {
                const res = await fetch('/history/update-image-type', {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await res.json();
                if (result.success) {
                    window.toast(result.message || 'Đã lưu!');
                    window.closeEditImageType();
                    if (window.loadPage) {
                        window.loadPage(window.location.pathname + window.location.search, true);
                    } else {
                        window.location.reload();
                    }
                } else {
                    window.toast('Lỗi: ' + (result.error || 'Không xác định'), 'error');
                }
            } catch (e) {
                window.toast('Lỗi kết nối: ' + e.message, 'error');
            } finally {
                btn.disabled = false; btn.textContent = 'Lưu';
            }
        };
    })();
</script>