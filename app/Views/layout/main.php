<?php
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($isAjax): ?>
    <?php require $viewPath; ?>
<?php else: ?>
    <!DOCTYPE html>
    <html lang="vi">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Quản lý Chỉ số Nước</title>
        <link rel="stylesheet" href="/assets/css/style.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    </head>

    <body>
        <?php if (isset($_SESSION['user_id'])): ?>
            <nav class="navbar glass-card container" style="margin-top: 2rem;">
                <div class="logo"><strong>WaterMeter</strong></div>
                <div class="nav-links">
                    <a href="/" class="btn">Lịch sử</a>
                    <a href="/meters" class="btn">Đồng hồ</a>
                    <a href="/pricing" class="btn">Chi phí AI</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="/users" class="btn">Thành viên</a>
                    <?php endif; ?>
                    <a href="/profile" class="btn">Cá nhân</a>
                    <a href="/logout" class="btn" style="color: #ef4444;">Đăng xuất</a>
                </div>
            </nav>
        <?php endif; ?>

        <main id="main-content" class="container">
            <?php require $viewPath; ?>
        </main>

        <script src="/assets/js/app.js"></script>
    </body>

    </html>
<?php endif; ?>