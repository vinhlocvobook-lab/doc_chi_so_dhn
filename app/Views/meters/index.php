<div class="fade-in">
    <?php
    // Pass llmModels to JS for use in the selector
    $llmModelNames = array_column($llmModels, 'model_name');
    ?>
    <script>
        const AVAILABLE_LLM_MODELS = <?= json_encode($llmModelNames, JSON_UNESCAPED_UNICODE) ?>;
    </script>

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
                    <th>LLM Models</th>
                    <th>Mặc định</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($meters as $meter): ?>
                    <?php
                    $llmList = [];
                    if (!empty($meter['last_llm_models'])) {
                        $decoded = json_decode($meter['last_llm_models'], true);
                        if (is_array($decoded)) {
                            usort($decoded, fn($a, $b) => ($a['priority'] ?? 99) <=> ($b['priority'] ?? 99));
                            $llmList = array_column($decoded, 'model_name');
                        }
                    }
                    ?>
                    <tr>
                        <td><strong>#<?= $meter['id'] ?></strong></td>
                        <td><?= htmlspecialchars($meter['model_dong_ho'] ?: '') ?: '<span style="color: #94a3b8; font-style: italic;">Chung</span>' ?>
                        </td>
                        <td><?= htmlspecialchars($meter['loai_hien_thi']) ?></td>
                        <td><?= $meter['phan_nguyen_digits'] ?> / <?= $meter['phan_thap_phan_digits'] ?></td>
                        <td>
                            <?php if ($llmList): ?>
                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                    <?php foreach ($llmList as $i => $m): ?>
                                        <span
                                            style="font-size:0.7rem; padding:2px 6px; background:rgba(79,70,229,0.12); color:#4f46e5; border-radius:4px; font-weight:600;">
                                            <?= $i + 1 ?>. <?= htmlspecialchars($m) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span style="color:#94a3b8; font-size:0.85rem;">Chưa cấu hình</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $meter['la_mac_dinh'] ? '<span class="badge badge-success">Mặc định</span>' : '' ?></td>
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
                        <td colspan="8" style="text-align: center; padding: 3rem;">Chưa có dữ liệu loại đồng hồ</td>
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
        style="width: 90%; max-width: 820px; margin: 2rem auto; padding: 2rem; position: relative; max-height: 90vh; overflow-y: auto;">
        <span onclick="closeMeterModal()"
            style="position: absolute; right: 1.5rem; top: 1rem; font-size: 2rem; cursor: pointer; color: white;">&times;</span>
        <h2 id="modal-title" style="color: var(--primary); margin-bottom: 2rem;">Thêm loại đồng hồ mới</h2>

        <form id="meter-form">
            <input type="hidden" name="id" id="meter-id">
            <!-- last_llm_models hidden field, updated by JS selector -->
            <input type="hidden" name="last_llm_models" id="meter-llm-json">

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
                    <h3 style="color: white; font-size: 1rem;">Cấu hình Phần Nguyên</h3>
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
                            placeholder="Màu chữ">
                        <input type="text" name="phan_nguyen_background" id="phan_nguyen_background"
                            class="filter-input" placeholder="Màu nền">
                    </div>
                </div>

                <!-- Phần thập phân -->
                <div
                    style="grid-column: span 2; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem; margin-top: 0.5rem;">
                    <h3 style="color: white; font-size: 1rem;">Cấu hình Phần Thập Phân</h3>
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

                <!-- Quy tắc & Vùng -->
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
                    style="grid-column: span 2; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem; margin-top: 0.5rem;">
                    <h3 style="color: white; font-size: 1rem;">🤖 Cấu hình AI Prompt</h3>
                </div>
                <div class="filter-group">
                    <label>Prompt Version</label>
                    <input type="text" name="last_prompt_version" id="last_prompt_version" class="filter-input"
                        value="1.0">
                </div>
                <div></div>
                <div style="grid-column: span 2;">
                    <div class="filter-group">
                        <label>Nội dung Prompt</label>
                        <textarea name="last_prompt_txt" id="last_prompt_txt" class="filter-input" rows="4"
                            placeholder="Nhập câu lệnh prompt cho AI..."></textarea>
                    </div>
                </div>

                <!-- LLM Model Selector -->
                <div
                    style="grid-column: span 2; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem; margin-top: 0.5rem;">
                    <h3 style="color: white; font-size: 1rem;">📋 Chọn LLM Models (theo thứ tự ưu tiên)</h3>
                </div>
                <div style="grid-column: span 2;">
                    <!-- Available models to pick from -->
                    <div style="margin-bottom: 0.75rem;">
                        <label
                            style="font-size: 0.8rem; color: rgba(255,255,255,0.7); display: block; margin-bottom: 0.5rem;">
                            Nhấn để thêm vào danh sách ưu tiên:
                        </label>
                        <div id="llm-available" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <?php if (empty($llmModels)): ?>
                                <span style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">
                                    Chưa có model nào. Hãy thêm tại <a href="/pricing" style="color: var(--primary);">Chi
                                        phí AI</a>.
                                </span>
                            <?php else: ?>
                                <?php foreach ($llmModels as $lm): ?>
                                    <button type="button" class="llm-chip"
                                        data-model="<?= htmlspecialchars($lm['model_name']) ?>"
                                        onclick="addLlmModel('<?= htmlspecialchars($lm['model_name'], ENT_QUOTES) ?>')">
                                        + <?= htmlspecialchars($lm['model_name']) ?>
                                    </button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Selected models ordered list -->
                    <div>
                        <label
                            style="font-size: 0.8rem; color: rgba(255,255,255,0.7); display: block; margin-bottom: 0.5rem;">
                            Danh sách đã chọn (kéo ↑↓ để đổi thứ tự ưu tiên):
                        </label>
                        <div id="llm-selected-list"
                            style="min-height: 60px; border: 1px dashed rgba(255,255,255,0.2); border-radius: 8px; padding: 0.5rem; display: flex; flex-direction: column; gap: 0.4rem;">
                            <span id="llm-empty-hint"
                                style="color: rgba(255,255,255,0.35); font-size: 0.85rem; padding: 0.5rem; text-align: center;">
                                Chưa có model nào được chọn
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Flags -->
                <div style="grid-column: span 2; display: flex; gap: 2rem; margin-top: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="la_mac_dinh" id="la_mac_dinh" value="1">
                        <label for="la_mac_dinh" style="margin: 0; cursor: pointer; color: white;">Đặt làm mặc
                            định</label>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                        <label for="is_active" style="margin: 0; cursor: pointer; color: white;">Đang hoạt động</label>
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

    /* LLM chip - available */
    .llm-chip {
        padding: 5px 12px;
        border-radius: 20px;
        border: 1px solid rgba(79, 70, 229, 0.4);
        background: rgba(79, 70, 229, 0.1);
        color: #a5b4fc;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.2s;
    }

    .llm-chip:hover {
        background: rgba(79, 70, 229, 0.3);
        color: white;
    }

    .llm-chip.used {
        opacity: 0.35;
        cursor: not-allowed;
        pointer-events: none;
    }

    /* LLM selected row */
    .llm-selected-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 6px 10px;
        background: rgba(255, 255, 255, 0.07);
        border-radius: 6px;
        animation: fadeIn 0.2s ease;
    }

    .llm-priority-badge {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        font-size: 0.75rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .llm-name {
        flex: 1;
        font-size: 0.9rem;
        color: white;
    }

    .llm-move-btn {
        background: none;
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        border-radius: 4px;
        padding: 2px 6px;
        cursor: pointer;
        font-size: 0.8rem;
        line-height: 1;
        transition: background 0.15s;
    }

    .llm-move-btn:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .llm-remove-btn {
        background: rgba(239, 68, 68, 0.15);
        border: none;
        color: #f87171;
        border-radius: 4px;
        padding: 3px 8px;
        cursor: pointer;
        font-size: 0.8rem;
        transition: background 0.15s;
    }

    .llm-remove-btn:hover {
        background: rgba(239, 68, 68, 0.3);
    }
</style>

<style>
    /* Toast notification */
    #spa-toast {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 9999;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        color: white;
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 0.3s, transform 0.3s;
        pointer-events: none;
        min-width: 220px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
    }

    #spa-toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    #spa-toast.success {
        background: rgba(22, 163, 74, 0.92);
    }

    #spa-toast.error {
        background: rgba(220, 38, 38, 0.92);
    }
</style>

<div id="spa-toast"></div>

<script>
    (function () {
        // ── Toast helper ────────────────────────────────────────────────
        function showToast(msg, type) {
            let t = document.getElementById('spa-toast');
            if (!t) return;
            t.textContent = msg;
            t.className = 'show ' + (type || 'success');
            clearTimeout(window._toastTimer);
            window._toastTimer = setTimeout(() => t.className = '', 3000);
        }
        window.showToast = showToast;

        // ── LLM Model Selector ──────────────────────────────────────────
        let selectedLlmModels = [];

        function renderLlmSelected() {
            const list = document.getElementById('llm-selected-list');
            const hint = document.getElementById('llm-empty-hint');
            const jsonInput = document.getElementById('meter-llm-json');
            if (!list) return;

            list.querySelectorAll('.llm-selected-row').forEach(r => r.remove());

            if (selectedLlmModels.length === 0) {
                if (hint) hint.style.display = 'block';
                if (jsonInput) jsonInput.value = '';
            } else {
                if (hint) hint.style.display = 'none';
                selectedLlmModels.forEach((model, idx) => {
                    const row = document.createElement('div');
                    row.className = 'llm-selected-row';
                    row.dataset.model = model;
                    row.innerHTML = `
                <span class="llm-priority-badge">${idx + 1}</span>
                <span class="llm-name">${model}</span>
                <button type="button" class="llm-move-btn" onclick="moveLlm(${idx},-1)" ${idx === 0 ? 'disabled style="opacity:0.3"' : ''}>↑</button>
                <button type="button" class="llm-move-btn" onclick="moveLlm(${idx},1)" ${idx === selectedLlmModels.length - 1 ? 'disabled style="opacity:0.3"' : ''}>↓</button>
                <button type="button" class="llm-remove-btn" onclick="removeLlm('${model}')">✕ Xóa</button>
                `;
                    list.appendChild(row);
                });
                if (jsonInput) jsonInput.value = JSON.stringify(selectedLlmModels.map((m, i) => ({ priority: i + 1, model_name: m })));
            }
            document.querySelectorAll('.llm-chip').forEach(chip => {
                chip.classList.toggle('used', selectedLlmModels.includes(chip.dataset.model));
            });
        }

        window.addLlmModel = function (modelName) {
            if (!selectedLlmModels.includes(modelName)) {
                selectedLlmModels.push(modelName);
                renderLlmSelected();
            }
        };
        window.removeLlm = function (modelName) {
            selectedLlmModels = selectedLlmModels.filter(m => m !== modelName);
            renderLlmSelected();
        };
        window.moveLlm = function (idx, dir) {
            const ni = idx + dir;
            if (ni < 0 || ni >= selectedLlmModels.length) return;
            [selectedLlmModels[idx], selectedLlmModels[ni]] = [selectedLlmModels[ni], selectedLlmModels[idx]];
            renderLlmSelected();
        };

        // ── Modal ───────────────────────────────────────────────────────
        window.openMeterModal = function () {
            document.getElementById('modal-title').textContent = 'Thêm loại đồng hồ mới';
            document.getElementById('meter-form').reset();
            document.getElementById('meter-id').value = '';
            selectedLlmModels = [];
            renderLlmSelected();
            document.getElementById('meter-modal').style.display = 'block';
        };
        window.closeMeterModal = function () {
            document.getElementById('meter-modal').style.display = 'none';
        };
        window.editMeter = function (meter) {
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

            selectedLlmModels = [];
            if (meter.last_llm_models) {
                try {
                    const parsed = typeof meter.last_llm_models === 'string'
                        ? JSON.parse(meter.last_llm_models) : meter.last_llm_models;
                    if (Array.isArray(parsed)) {
                        selectedLlmModels = parsed.sort((a, b) => (a.priority ?? 99) - (b.priority ?? 99)).map(x => x.model_name).filter(Boolean);
                    }
                } catch (e) { }
            }
            renderLlmSelected();
            document.getElementById('meter-modal').style.display = 'block';
        };

        // ── Form Submit ─────────────────────────────────────────────────
        const form = document.getElementById('meter-form');
        if (form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                e.stopPropagation();
                const btn = this.querySelector('[type=submit]');
                if (btn) { btn.disabled = true; btn.textContent = 'Đang lưu...'; }
                const formData = new FormData(this);
                try {
                    const response = await fetch('/meters/save', {
                        method: 'POST', body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    let result;
                    const ct = response.headers.get('Content-Type') || '';
                    if (ct.includes('application/json')) {
                        result = await response.json();
                    } else {
                        const text = await response.text();
                        console.error('Non-JSON response:', response.status, text.substring(0, 500));
                        showToast('Lỗi server (HTTP ' + response.status + '): ' + text.substring(0, 100), 'error');
                        return;
                    }
                    if (result.success) {
                        window.closeMeterModal();
                        showToast(result.message || 'Lưu thành công!');
                        window.loadPage('/meters');
                    } else {
                        showToast('Lỗi: ' + (result.error || 'Không xác định'), 'error');
                    }
                } catch (err) {
                    console.error('Submit error:', err);
                    showToast('Lỗi kết nối: ' + err.message, 'error');
                } finally {
                    if (btn) { btn.disabled = false; btn.textContent = 'Lưu thông tin'; }
                }
            });
        }

        // ── Delete ──────────────────────────────────────────────────────
        window.deleteMeter = async function (id) {
            if (!confirm('Bạn có chắc chắn muốn xóa loại đồng hồ này?')) return;
            const fd = new FormData(); fd.append('id', id);
            try {
                const res = await fetch('/meters/delete', {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await res.json();
                if (result.success) {
                    showToast('Đã xóa thành công');
                    window.loadPage('/meters');
                } else {
                    showToast('Lỗi: ' + (result.error || 'Không xác định'), 'error');
                }
            } catch (err) { showToast('Lỗi kết nối', 'error'); }
        };
    })();
</script>