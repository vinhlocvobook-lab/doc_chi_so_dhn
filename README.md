# Dự án: Quản lý Chỉ số Nước (Water Meter MVC SPA)

Ứng dụng Web PHP theo kiến trúc **MVC** với cơ chế **SPA (Single Page Application)** — cho phép quản lý lịch sử chỉ số đồng hồ nước và cấu hình nhận dạng đồng hồ bằng AI.

---

## 1. Kiến trúc Hệ thống

### Backend (PHP MVC)

| Thành phần | File | Chức năng |
|---|---|---|
| **Database** | `Core/Database.php` | Singleton PDO, kết nối MySQL |
| **Router** | `Core/Router.php` | Phân tích URL → Controller |
| **Controller** | `Core/Controller.php` | Base class: `view()`, `json($data, $status)`, `redirect()` |
| **AuthController** | `Controllers/AuthController.php` | Đăng nhập / Đăng xuất |
| **HistoryController** | `Controllers/HistoryController.php` | Lịch sử chỉ số, lọc, phân trang |
| **MeterTypeController** | `Controllers/MeterTypeController.php` | CRUD loại đồng hồ |
| **UserController** | `Controllers/UserController.php` | Danh sách user, hồ sơ |
| **History** | `Models/History.php` | Query `chisodhn`, lọc & đếm |
| **MeterType** | `Models/MeterType.php` | CRUD `loai_dhn` |
| **User** | `Models/User.php` | Xác thực, tìm user |

### Frontend (Vanilla JS SPA)

- **`public/assets/js/app.js`**: Intercept click/submit → `fetch` → cập nhật `#main-content`
- **Partial Update**: Khi tìm kiếm lịch sử, chỉ `#history-results` được cập nhật — form lọc giữ nguyên
- **`pushState`**: URL cập nhật không reload trang

> **Lưu ý quan trọng**: Partial update chỉ được kích hoạt khi điều hướng trong **cùng section** (history → history). Các link đến trang khác luôn dùng full update.

---

## 2. Các Tính năng

### Lịch sử Chỉ số (`/`)
- Lọc: Năm, Tháng, Loại đồng hồ, Số danh bộ, Có/Không có hình ảnh
- Bảng: ID, Chỉ số, Năm, Tháng, Loại ĐH, S/N, Hình (●/○), Thời gian
- Phân trang 10 bản ghi/trang
- **Inline details**: Click "Chi tiết" → mở rộng hàng, hiển thị thông số đầy đủ + hình ảnh
- **Expand All**: Nút "Hiện tất cả chi tiết" mở rộng toàn bộ bảng

### Quản lý Loại Đồng hồ (`/meters`)
- CRUD đầy đủ: Thêm / Sửa (Modal) / Xóa
- Các trường chính: Model, Loại hiển thị, Phần nguyên (digits/màu/nền), Phần thập phân, Quy tắc đọc số
- **Cấu hình AI**:
  - `last_prompt_txt`: Prompt để AI đọc chỉ số
  - `last_llm_models`: JSON danh sách model ưu tiên (VD: `[{"priority":1,"model_name":"gemini-2.5-flash-lite"}]`)
  - `last_prompt_version`: Phiên bản prompt
- Cờ: Mặc định (`la_mac_dinh`), Hoạt động (`is_active`)

### Xác thực & Quản lý User
- Đăng nhập với `password_hash` + session
- Session keys: `$_SESSION['user_id']`, `$_SESSION['username']`, `$_SESSION['role']`
- Admin: truy cập `/users`

---

## 3. Database

### `chisodhn` — Chỉ số đồng hồ nước (dữ liệu gốc)
Các cột lọc chính: `nam`, `thang`, `loaiDongHo`, `soDanhBo`, `linkHinhDongHo`, `chiSoNuoc`, `soSerial`, `created_at`

### `loai_dhn` — Cấu hình loại đồng hồ
| Nhóm | Cột |
|---|---|
| Nhận dạng | `model_dong_ho`, `loai_hien_thi`, `vung_hien_thi` |
| Phần nguyên | `phan_nguyen_digits`, `phan_nguyen_color`, `phan_nguyen_background` |
| Phần thập phân | `phan_thap_phan_digits`, `phan_thap_phan_color`, `phan_thap_phan_background` |
| Quy tắc | `quy_tac_lam_tron`, `quy_tac_bo_sung` |
| AI | `last_prompt_txt`, `last_llm_models` (JSON), `last_prompt_version` |
| Flags | `la_mac_dinh` (UNIQUE), `is_active` |
| Audit | `create_user`, `create_time`, `edit_user`, `edit_time` |

### `users` — Tài khoản
Cột: `id`, `username`, `password_hash`, `vai_tro` (admin/user), `trang_thai`, `created_at`

---

## 4. Cài đặt & Chạy

### Yêu cầu
- PHP 8.0+, MySQL 5.7+

### Cấu hình `.env`
```env
DB_HOST=127.0.0.1      # Dùng 127.0.0.1, KHÔNG dùng localhost (tránh lỗi socket trên macOS)
DB_PORT=3306
DB_NAME=capnuoccangio
DB_USER=your_user
DB_PASS=your_password
APP_DEBUG=true
```

### Chạy
```bash
php -S localhost:8081 -t public
```

### Tạo bảng `loai_dhn`
```bash
php migrate_meters.php
```

---

## 5. Ghi chú Kỹ thuật (cho AI Reference)

- `DB_HOST` luôn dùng `127.0.0.1` tránh Unix socket error trên macOS
- Session keys chuẩn: `user_id`, `username`, `role` (tránh nhầm với `user`)
- `Controller::json($data, $status)` — luôn set HTTP status code
- Partial update `#history-results` chỉ hoạt động khi **cùng section** (kiểm tra `getSection()` trong `app.js`)
- `MeterType::create()` và `update()` nhận mảng associative — keys phải khớp tên cột DB
- `la_mac_dinh` có UNIQUE constraint — dùng `UPDATE SET la_mac_dinh=0` trước khi set `1`
