<div class="fade-in" style="max-width: 600px; margin: 0 auto;">
    <h1 style="color: white; margin-bottom: 2rem;">Hồ sơ cá nhân</h1>

    <div class="glass-card" style="padding: 2rem;">
        <div style="display: flex; align-items: center; gap: 2rem; margin-bottom: 2rem;">
            <div
                style="width: 80px; height: 80px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; fons-size: 2rem; font-weight: bold;">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <div>
                <h2>
                    <?= $user['ten_nhan_vien'] ?>
                </h2>
                <p style="color: var(--text-muted);">
                    <?= $user['vai_tro'] ?>
                </p>
            </div>
        </div>

        <div style="display: grid; gap: 1rem;">
            <div style="border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 0.5rem;">
                <label style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Email</label>
                <div>
                    <?= $user['email'] ?>
                </div>
            </div>
            <div style="border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 0.5rem;">
                <label style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Tài khoản</label>
                <div>
                    <?= $user['username'] ?>
                </div>
            </div>
            <div style="border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 0.5rem;">
                <label style="font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">Ngày tạo</label>
                <div>
                    <?= $user['created_at'] ?>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" style="margin-top: 2rem; width: 100%;">Cập nhật thông tin</button>
    </div>
</div>