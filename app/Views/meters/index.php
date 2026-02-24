<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="color: white; margin: 0;">Quản lý Loại đồng hồ</h1>
        <button class="btn btn-primary" onclick="openMeterModal()">+ Thêm loại mới</button>
    </div>

    <div class="glass-card" style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Model</th>
                    <th>Loại hiển thị</th>
                    <th>Số chữ số (Nguyên/Thập)</th>
                    <th>Mặc định</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($meters as $meter): ?>
                    <tr>
                        <td><strong>#
                                <?= $meter['id'] ?>
                            </strong></td>
                        <td>
                            <?= $meter['model_dong_ho'] ?: '<span style="color: #94a3b8; font-style: italic;">Chung</span>' ?>
                        </td>
                        <td>
                            <?= $meter['loai_hien_thi'] ?>
                        </td>
                        <td>
                            <?= $meter['phan_nguyen_digits'] ?> /
                            <?= $meter['phan_thap_phan_digits'] ?>
                        </td>
                        <td>
                            <?php if ($meter['la_mac_dinh']): ?>
                                <span class="badge badge-success">Mặc định</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $meter['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $meter['is_active'] ? 'Hoạt động' : 'Tạm dừng' ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;"
                                    onclick='editMeter(<?= json_encode($meter) ?>)'>Sửa</button>
                                <button class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;"
                                    onclick="deleteMeter(<?= $meter['id'] ?>)">Xóa</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($meters)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem;">Chưa có dữ liệu loại đồng hồ</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form -->
<div id="meter-modal" class="modal"
    style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px);">
    <div class="glass-card"
        style="width: 90%; max-width: 800px; margin: 2rem auto; padding: 2rem; position: relative; max-height: 90vh; overflow-y: auto;">
        <span onclick="closeMeterModal()"
            style="position: absolute; right: 1.5rem; top: 1rem; font-size: 2rem; cursor: pointer; color: white;">&times;</span>
        <h2 id="modal-title" style="color: var(--primary); margin-bottom: 2rem;">Thêm loại đồng hồ mới</h2>

        <form id="meter-form">
            <input type="hidden" name="id" id="meter-id">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Nhận dạng -->
                <div class="filter-group">
                    <label>Model Đồng hồ</label>
                    <input type="text" name="model_dong_ho" id="model_dong_ho" class="filter-input"
                        placeholder="VD: MULTIMAG (Để trống nếu là chung)">
                </div>
                <div class="filter-group">
                    <label>Loại hiển thị</label>
                    <select name="loai_hien_thi" id="loai_hien_thi" class="filter-input" required>
                        <option value="Đồng hồ cơ vòng số">Đồng hồ cơ vòng số</option>
                        <option value="Đồng hồ điện tử, màn hình LCD">Đồng hồ điện tử, màn hình LCD</option>
                    </select>
                </div>

                <!-- Phần nguyên -->
                <div
                    style="grid-column: span 2; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem; margin-top: 1rem;">
                    <h3 style="color: white; font-size: 1.1rem;">Cấu hình Phần Nguyên</h3>
                </div>
                <div class="filter-group">
                    <label>Số chữ số</label>
                    <input type="number" name="phan_nguyen_digits" id="phan_nguyen_digits" class="filter-input"
                        value="4">
                </div>
                <div class="filter-group">
                    <label>Màu chữ / Màu nền</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" name="phan_nguyen_color" id="phan_nguyen_color" class="filter-input"
                            placeholder="Màu chữ (VD: đen)">
                        <input type="text" name="phan_nguyen_background" id="phan_nguyen_background"
                            class="filter-input" placeholder="Màu nền (VD: trắng)">
                    </div>
                </div>

                <!-- Phần thập phân -->
                <div
                    style="grid-column: span 2; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem; margin-top: 1rem;">
                    <h3 style="color: white; font-size: 1.1rem;">Cấu hình Phần Thập Phân</h3>
                </div>
                <div class="filter-group">
                    <label>Số chữ số (0 nếu không có)</label>
                    <input type="number" name="phan_thap_phan_digits" id="phan_thap_phan_digits" class="filter-input"
                        value="0">
                </div>
                <div class="filter-group">
                    <label>Màu chữ / Màu nền</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" name="phan_thap_phan_color" id="phan_thap_phan_color" class="filter-input"
                            placeholder="Màu chữ">
                        <input type="text" name="phan_thap_phan_background" id="phan_thap_phan_background"
                            class="filter-input" placeholder="Màu nền">
                    </div>
                </div>

                <!-- Quy tắc & Vùng hiển thị -->
                <div style="grid-column: span 2;">
                    <div class="filter-group">
                        <label>Vùng hiển thị chỉ số</label>
                        <textarea name="vung_hien_thi" id="vung_hien_thi" class="filter-input" rows="2"></textarea>
                    </div>
                </div>
                <div class="filter-group">
                    <label>Quy tắc làm tròn</label>
                    <textarea name="quy_tac_lam_tron" id="quy_tac_lam_tron" class="filter-input" rows="2"></textarea>
                </div>
                <div class="filter-group">
                    <label>Quy tắc bổ sung</label>
                    <textarea name="quy_tac_bo_sung" id="quy_tac_bo_sung" class="filter-input" rows="2"></textarea>
                </div>

                <!-- AI Config -->
                <div
                    style="grid-column: span 2; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem; margin-top: 1rem;">
                    <h3 style="color: white; font-size: 1.1rem;">Cấu hình AI Prompt</h3>
                </div>
                <div class="filter-group">
                    <label>Prompt Version</label>
                    <input type="text" name="last_prompt_version" id="last_prompt_version" class="filter-input"
                        value="1.0">
                </div>
                <div class="filter-group">
                    <label>LLM Models (JSON)</label>
                    <input type="text" name="last_llm_models" id="last_llm_models" class="filter-input"
                        value='[{"priority":1,"model_name":"gemini-2.5-flash-lite"},{"priority":2,"model_name":"gemini-2.5-pro"}]'>
                </div>
                <div style="grid-column: span 2;">
                    <div class="filter-group">
                        <label>Nội dung Prompt</label>
                        <textarea name="last_prompt_txt" id="last_prompt_txt" class="filter-input" rows="5"
                            placeholder="Nhập câu lệnh prompt cho AI..."></textarea>
                    </div>
                </div>

                <!-- Flags -->
                <div style="display: flex; gap: 2rem; margin-top: 1rem;">
                    <div class="filter-group" style="flex-direction: row; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="la_mac_dinh" id="la_mac_dinh" value="1">
                        <label for="la_mac_dinh" style="margin: 0; cursor: pointer;">Đặt làm mặc định</label>
                    </div>
                    <div class="filter-group" style="flex-direction: row; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                        <label for="is_active" style="margin: 0; cursor: pointer;">Đang hoạt động</label>
                    </div>
                </div>
            </div>

            <div style="margin-top: 2rem; text-align: right; display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" class="btn btn-secondary" onclick="closeMeterModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu thông tin</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openMeterModal() {
        document.getElementById('modal-title').textContent = 'Thêm loại đồng hồ mới';
        document.getElementById('meter-form').reset();
        document.getElementById('meter-id').value = '';
        document.getElementById('meter-modal').style.display = 'block';
    }

    function closeMeterModal() {
        document.getElementById('meter-modal').style.display = 'none';
    }

    function editMeter(meter) {
        document.getElementById('modal-title').textContent = 'Chỉnh sửa loại đồng hồ';
        document.getElementById('meter-id').value = meter.id;
        document.getElementById('model_dong_ho').value = meter.model_dong_ho || '';
        document.getElementById('loai_hien_thi').value = meter.loai_hien_thi;
        document.getElementById('vung_hien_thi').value = meter.vung_hien_thi || '';
        document.getElementById('phan_nguyen_digits').value = meter.phan_nguyen_digits;
        document.getElementById('phan_nguyen_color').value = meter.phan_nguyen_color || '';
        document.getElementById('phan_nguyen_background').value = meter.phan_nguyen_background || '';
        document.getElementById('phan_thap_phan_digits').value = meter.phan_thap_phan_digits;
        document.getElementById('phan_thap_phan_color').value = meter.phan_thap_phan_color || '';
        document.getElementById('phan_thap_phan_background').value = meter.phan_thap_phan_background || '';
        document.getElementById('quy_tac_lam_tron').value = meter.quy_tac_lam_tron || '';
        document.getElementById('quy_tac_bo_sung').value = meter.quy_tac_bo_sung || '';
        document.getElementById('la_mac_dinh').checked = meter.la_mac_dinh == 1;
        document.getElementById('is_active').checked = meter.is_active == 1;
        document.getElementById('last_prompt_version').value = meter.last_prompt_version || '';
        document.getElementById('last_prompt_txt').value = meter.last_prompt_txt || '';
        document.getElementById('last_llm_models').value = typeof meter.last_llm_models === 'string' ? meter.last_llm_models : JSON.stringify(meter.last_llm_models);

        document.getElementById('meter-modal').style.display = 'block';
    }

    document.getElementById('meter-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        try {
            const response = await fetch('/meters/save', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json();
            if (result.success) {
                closeMeterModal();
                loadPage('/meters'); // Reload the list
            } else {
                alert(result.error || 'Có lỗi xảy ra');
            }
        } catch (err) {
            console.error(err);
            alert('Lỗi kết nối server');
        }
    });

    async function deleteMeter(id) {
        if (!confirm('Bạn có chắc chắn muốn xóa loại đồng hồ này?')) return;

        try {
            const formData = new FormData();
            formData.append('id', id);
            const response = await fetch('/meters/delete', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json();
            if (result.success) {
                loadPage('/meters');
            } else {
                alert(result.error || 'Có lỗi xảy ra');
            }
        } catch (err) {
            console.error(err);
        }
    }
</script>

<style>
    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-success {
        background: rgba(74, 222, 128, 0.2);
        color: #4ade80;
        border: 1px solid rgba(74, 222, 128, 0.3);
    }

    .badge-danger {
        background: rgba(248, 113, 113, 0.2);
        color: #f87171;
        border: 1px solid rgba(248, 113, 113, 0.3);
    }

    .modal {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }
</style>