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
            <label>Loại đồng hồ</label>
            <input type="text" name="loaiDongHo" class="filter-input" placeholder="Nhập loại..."
                value="<?= htmlspecialchars($filters['loaiDongHo']) ?>">
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
                        <th>Loại ĐH</th>
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
                            <td><?= $item['loaiDongHo'] ?></td>
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
                                <button class="btn btn-primary" style="padding: 6px 12px; font-size: 0.9rem;"
                                    onclick="toggleRow(<?= $item['id'] ?>)">Chi tiết</button>
                            </td>
                        </tr>
                        <tr id="detail-<?= $item['id'] ?>" class="detail-row">
                            <td colspan="9">
                                <div class="detail-content">
                                    <div style="flex: 1;">
                                        <h3 style="margin-bottom: 1rem; color: var(--primary);">Thông tin chi tiết
                                            #<?= $item['id'] ?> (Danh bộ: <?= $item['soDanhBo'] ?>)</h3>
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                            <div><strong>Loại đồng hồ:</strong> <?= $item['loaiDongHo'] ?></div>
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
                                                ảnh
                                                đồng hồ</p>
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