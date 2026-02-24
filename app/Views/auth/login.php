<div class="auth-wrapper">
    <div class="auth-card glass-card fade-in">
        <h2 style="text-align: center; margin-bottom: 2rem;">Đăng nhập</h2>

        <?php if (isset($error)): ?>
            <div
                style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 10px; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="/login" method="POST">
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Tài khoản</label>
                <input type="text" name="username" required
                    style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.5);">
            </div>

            <div style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Mật khẩu</label>
                <input type="password" name="password" required
                    style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.5);">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Vào hệ thống</button>
        </form>
    </div>
</div>