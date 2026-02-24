<div class="fade-in">
    <h1 style="color: white; margin-bottom: 2rem;">Quản lý Thành viên</h1>

    <div class="glass-card" style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tên nhân viên</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <?= $u['ten_nhan_ven'] ?? $u['ten_nhan_vien'] ?>
                        </td>
                        <td>
                            <?= $u['username'] ?>
                        </td>
                        <td>
                            <?= $u['email'] ?>
                        </td>
                        <td>
                            <?= $u['vai_tro'] ?>
                        </td>
                        <td>
                            <span style="color: <?= $u['trang_thai'] === 'hoat_dong' ? '#166534' : '#991b1b' ?>;">
                                ●
                                <?= $u['trang_thai'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>