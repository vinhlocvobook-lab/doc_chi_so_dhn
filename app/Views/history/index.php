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
            <label>Loại ĐH (API cũ)</label>
            <input type="text" name="loaiDongHo" class="filter-input" placeholder="Nhập loại..."
                value="<?= htmlspecialchars($filters['loaiDongHo']) ?>">
        </div>
        <!-- NEW: loaiDongHo_new filter -->
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
                        <th>S/N</th>
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
                                    </span>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:0.8rem;">chưa phân loại</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $item['soSerial'] ?></td>
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
                        class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link">Sau</a>
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
                            <?= htmlspecialchars($mt['loai_hien_thi']) ?>    <?php endif; ?>
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

        function toast(msg, type) {
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
                    toast(result.message || 'Đã lưu!');
                    window.closeEditMeterType();
                    window.loadPage(window.location.pathname + window.location.search);
                } else {
                    toast('Lỗi: ' + (result.error || 'Không xác định'), 'error');
                }
            } catch (e) {
                toast('Lỗi kết nối: ' + e.message, 'error');
            } finally {
                btn.disabled = false; btn.textContent = 'Lưu';
            }
        };
    })();
</script>