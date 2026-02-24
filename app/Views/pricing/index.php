<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="color: white; margin: 0;">Chi phí API LLM</h1>
            <p style="color: rgba(255,255,255,0.7); margin: 0.25rem 0 0;">Bảng giá Gemini API / 1 triệu token (USD)</p>
        </div>
        <button class="btn btn-primary" onclick="openPricingModal()">+ Thêm mô hình</button>
    </div>

    <div class="glass-card" style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Model</th>
                    <th>Ngưỡng context</th>
                    <th colspan="2" style="text-align:center; border-left: 1px solid rgba(0,0,0,0.08);">Input (/ 1M
                        token)</th>
                    <th colspan="2" style="text-align:center; border-left: 1px solid rgba(0,0,0,0.08);">Output (/ 1M
                        token)</th>
                    <th style="border-left: 1px solid rgba(0,0,0,0.08);">Thao tác</th>
                </tr>
                <tr style="font-size: 0.8rem; color: var(--text-muted);">
                    <th></th>
                    <th></th>
                    <th style="border-left: 1px solid rgba(0,0,0,0.08);">≤ ngưỡng</th>
                    <th>> ngưỡng</th>
                    <th style="border-left: 1px solid rgba(0,0,0,0.08);">≤ ngưỡng</th>
                    <th>> ngưỡng</th>
                    <th style="border-left: 1px solid rgba(0,0,0,0.08);"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pricings as $p): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--primary);">
                                <?= htmlspecialchars($p['model_name']) ?>
                            </strong>
                        </td>
                        <td>
                            <span style="font-size:0.85rem;">
                                <?= number_format($p['context_threshold']) ?> tokens
                            </span>
                        </td>
                        <td style="border-left: 1px solid rgba(0,0,0,0.05);">
                            <?= $p['input_price_low_context'] !== null ? '<span class="price-chip">$' . number_format((float) $p['input_price_low_context'], 4) . '</span>' : '<span style="color:#94a3b8">—</span>' ?>
                        </td>
                        <td>
                            <?= $p['input_price_high_context'] !== null ? '<span class="price-chip price-high">$' . number_format((float) $p['input_price_high_context'], 4) . '</span>' : '<span style="color:#94a3b8">—</span>' ?>
                        </td>
                        <td style="border-left: 1px solid rgba(0,0,0,0.05);">
                            <?= $p['output_price_low_context'] !== null ? '<span class="price-chip price-out">$' . number_format((float) $p['output_price_low_context'], 4) . '</span>' : '<span style="color:#94a3b8">—</span>' ?>
                        </td>
                        <td>
                            <?= $p['output_price_high_context'] !== null ? '<span class="price-chip price-out-high">$' . number_format((float) $p['output_price_high_context'], 4) . '</span>' : '<span style="color:#94a3b8">—</span>' ?>
                        </td>
                        <td style="border-left: 1px solid rgba(0,0,0,0.05);">
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;"
                                    onclick='editPricing(<?= json_encode($p) ?>)'>Sửa</button>
                                <button class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;"
                                    onclick="deletePricing(<?= $p['id'] ?>)">Xóa</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pricings)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            Chưa có dữ liệu. Nhấn "+ Thêm mô hình" để bắt đầu.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p style="color: rgba(255,255,255,0.5); font-size: 0.8rem; margin-top: 1rem; text-align: right;">
        * Giá tính theo USD / 1 triệu token. Nguồn: Google AI Pricing.
    </p>
</div>

<!-- Modal Form -->
<div id="pricing-modal"
    style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px);">
    <div class="glass-card"
        style="width: 90%; max-width: 680px; margin: 3rem auto; padding: 2rem; position: relative; max-height: 90vh; overflow-y: auto;">
        <span onclick="closePricingModal()"
            style="position: absolute; right: 1.5rem; top: 1rem; font-size: 2rem; cursor: pointer; color: white; line-height: 1;">&times;</span>
        <h2 id="pricing-modal-title" style="color: var(--primary); margin-bottom: 2rem;">Thêm mô hình mới</h2>

        <form id="pricing-form">
            <input type="hidden" name="id" id="pricing-id">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Model name -->
                <div class="filter-group" style="grid-column: span 2;">
                    <label>Tên Model <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="model_name" id="pricing-model_name" class="filter-input"
                        placeholder="VD: gemini-2.5-flash, gemini-2.5-pro" required>
                </div>

                <!-- Input prices -->
                <div
                    style="grid-column: span 2; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.25rem; margin-top: 0.5rem;">
                    <h3 style="color: white; font-size: 1rem;">💰 Giá Input (USD / 1M token)</h3>
                </div>
                <div class="filter-group">
                    <label>Context ngắn (≤ ngưỡng)</label>
                    <div style="position: relative;">
                        <span
                            style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #64748b;">$</span>
                        <input type="number" step="0.00000001" name="input_price_low_context" id="pricing-input_low"
                            class="filter-input" style="padding-left: 22px;" placeholder="0.00010000">
                    </div>
                </div>
                <div class="filter-group">
                    <label>Context dài (> ngưỡng)</label>
                    <div style="position: relative;">
                        <span
                            style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #64748b;">$</span>
                        <input type="number" step="0.00000001" name="input_price_high_context" id="pricing-input_high"
                            class="filter-input" style="padding-left: 22px;" placeholder="0.00020000">
                    </div>
                </div>

                <!-- Output prices -->
                <div
                    style="grid-column: span 2; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.25rem; margin-top: 0.5rem;">
                    <h3 style="color: white; font-size: 1rem;">💸 Giá Output (USD / 1M token)</h3>
                </div>
                <div class="filter-group">
                    <label>Context ngắn (≤ ngưỡng)</label>
                    <div style="position: relative;">
                        <span
                            style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #64748b;">$</span>
                        <input type="number" step="0.00000001" name="output_price_low_context" id="pricing-output_low"
                            class="filter-input" style="padding-left: 22px;" placeholder="0.00040000">
                    </div>
                </div>
                <div class="filter-group">
                    <label>Context dài (> ngưỡng)</label>
                    <div style="position: relative;">
                        <span
                            style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #64748b;">$</span>
                        <input type="number" step="0.00000001" name="output_price_high_context" id="pricing-output_high"
                            class="filter-input" style="padding-left: 22px;" placeholder="0.00080000">
                    </div>
                </div>

                <!-- Config -->
                <div
                    style="grid-column: span 2; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.25rem; margin-top: 0.5rem;">
                    <h3 style="color: white; font-size: 1rem;">⚙️ Cấu hình</h3>
                </div>
                <div class="filter-group">
                    <label>Ngưỡng context (tokens)</label>
                    <input type="number" name="context_threshold" id="pricing-threshold" class="filter-input"
                        value="128000">
                </div>
                <div class="filter-group">
                    <label>Đơn vị tiền tệ</label>
                    <select name="currency" id="pricing-currency" class="filter-input">
                        <option value="USD">USD</option>
                        <option value="VND">VND</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" class="btn btn-secondary" onclick="closePricingModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<style>
    .price-chip {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 0.85rem;
        font-weight: 600;
        background: rgba(79, 70, 229, 0.12);
        color: #4f46e5;
    }

    .price-high {
        background: rgba(234, 179, 8, 0.12);
        color: #b45309;
    }

    .price-out {
        background: rgba(16, 185, 129, 0.12);
        color: #047857;
    }

    .price-out-high {
        background: rgba(239, 68, 68, 0.12);
        color: #b91c1c;
    }
</style>

<script>
    function openPricingModal() {
        document.getElementById('pricing-modal-title').textContent = 'Thêm mô hình mới';
        document.getElementById('pricing-form').reset();
        document.getElementById('pricing-id').value = '';
        document.getElementById('pricing-threshold').value = 128000;
        document.getElementById('pricing-modal').style.display = 'block';
    }

    function closePricingModal() {
        document.getElementById('pricing-modal').style.display = 'none';
    }

    function editPricing(p) {
        document.getElementById('pricing-modal-title').textContent = 'Chỉnh sửa: ' + p.model_name;
        document.getElementById('pricing-id').value = p.id;
        document.getElementById('pricing-model_name').value = p.model_name;
        document.getElementById('pricing-input_low').value = p.input_price_low_context || '';
        document.getElementById('pricing-input_high').value = p.input_price_high_context || '';
        document.getElementById('pricing-output_low').value = p.output_price_low_context || '';
        document.getElementById('pricing-output_high').value = p.output_price_high_context || '';
        document.getElementById('pricing-threshold').value = p.context_threshold;
        document.getElementById('pricing-currency').value = p.currency || 'USD';
        document.getElementById('pricing-modal').style.display = 'block';
    }

    document.getElementById('pricing-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        try {
            const res = await fetch('/pricing/save', {
                method: 'POST', body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await res.json();
            if (result.success) {
                closePricingModal();
                window.loadPage('/pricing');
            } else {
                alert(result.error || 'Có lỗi xảy ra');
            }
        } catch (err) { alert('Lỗi kết nối server'); }
    });

    async function deletePricing(id) {
        if (!confirm('Xóa mô hình này?')) return;
        const fd = new FormData(); fd.append('id', id);
        try {
            const res = await fetch('/pricing/delete', {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await res.json();
            if (result.success) window.loadPage('/pricing');
        } catch (err) { console.error(err); }
    }
</script>