# 📋 Tổng quan Dự án: Water Meter AI Reader

**Tên project:** `readdb_water_meter1meter`  
**Phiên bản tài liệu:** 2026-02-27  
**Mục tiêu:** Đọc tự động chỉ số đồng hồ nước bằng AI (Google Gemini), đánh giá độ chính xác và lưu log để phục vụ vận hành thực tế cho CAWACO Cần Giờ.

---

## 1. Mục tiêu & Bài toán

Nhân viên đọc đồng hồ nước hiện nay chụp ảnh đồng hồ và ghi số thủ công. Dự án này thay thế/hỗ trợ bước ghi số bằng cách:

1. **Lấy ảnh đồng hồ** từ URL lưu trong bảng `chisodhn`
2. **Gọi Google Gemini API** để nhận dạng chỉ số từ ảnh
3. **So sánh kết quả AI** với chỉ số nhân viên ghi (ground truth)
4. **Đánh giá tính hợp lý** dựa trên lịch sử tiêu thụ
5. **Chấm điểm** và ra quyết định: tự động chấp nhận / cần review / từ chối

---

## 2. Kiến trúc Tổng thể

```
┌─────────────────────────────────────────────────────────┐
│                    Web Application                       │
│              PHP MVC + SPA (Vanilla JS)                  │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │ History  │  │ Meters   │  │ Pricing  │  │ AI Logs │ │
│  │ (chisodhn│  │(loai_dhn)│  │(gemini_  │  │ (Logs   │ │
│  │ viewer)  │  │  CRUD)   │  │ pricing) │  │ viewer) │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
│                     ▼ SSE Stream                         │
│              ┌──────────────┐                            │
│              │ AiReadController (stream)                  │
│              └──────┬───────┘                            │
└─────────────────────┼───────────────────────────────────┘
                       ▼
           ┌──────────────────────┐
           │   Google Gemini API  │
           │ (vision + JSON output)│
           └──────────────────────┘
                       ▼
      ┌────────────────────────────────┐
      │  WaterMeterRationalityChecker  │
      │  - danhGia() → hợp lý/bất hợp │
      │  - tinhScorePoc() → 0-100      │
      │  - tinhScoreThucTe() → 0-100   │
      └────────────────────────────────┘
                       ▼
           ┌──────────────────────┐
           │  MySQL: capnuoccangio│
           │  tn_meter_reading_log│
           └──────────────────────┘
```

---

## 3. Stack Công nghệ

| Thành phần | Công nghệ |
|------|------|
| Backend | PHP 8.0+, MVC từ scratch (không dùng framework) |
| Frontend | Vanilla JS SPA (fetch API + SSE) |
| Database | MySQL 5.7+ |
| AI | Google Gemini API (vision) |
| Web server | PHP Built-in Dev Server (`php -S`) |
| Styling | Vanilla CSS + Google Fonts (Inter) |

---

## 4. Tính năng Chính

### 4.1 Đọc số đồng hồ bằng AI (SSE Real-time)
- Người dùng chọn bản ghi có ảnh → chọn model + prompt → click "Đọc AI"
- Kết quả được stream về từng bước qua **Server-Sent Events (SSE)**
- 7 bước xử lý: Fetch record → Download image → Call API → Parse → Save DB → Write log → Done

### 4.2 Đánh giá tính hợp lý (WaterMeterRationalityChecker)
- So sánh lượng tiêu thụ AI tính với:
  - Ngưỡng TB 3 tháng trước (×0.2 min ~ ×3.0 max)
  - Lượng tiêu thụ tháng trước (×2.0 tăng, ×0.5 giảm)
- Kết quả: HOP_LY / NGHI_NGO_TANG/GIAM / LOI / KHONG_CO_LICH_SU

### 4.3 Hệ thống chấm điểm 2 giai đoạn
| Giai đoạn | Tên | Khi nào dùng | Điểm tối đa |
|------|------|------|------|
| 1 | Score POC | POC — có nhân viên đối chứng | 100 |
| 2 | Score Thực tế | Vận hành — không cần nhân viên | 100 |

### 4.4 Quản lý Loại Đồng hồ (CRUD)
- Mỗi loại có: prompt riêng, danh sách model LLM ưu tiên, quy tắc đọc số
- Cấu hình phần nguyên/thập phân (số chữ số, màu sắc, nền)

### 4.5 Quản lý Giá API Gemini
- Lưu đơn giá per 1M tokens cho từng model
- Tự động tính chi phí mỗi lần gọi API (USD + VND)

### 4.6 Lịch sử Chỉ số
- Xem, lọc, phân trang bản ghi từ `chisodhn` (85,000+ records)
- Inline detail: click mở rộng hàng, xem ảnh, lịch sử AI đọc
- Cập nhật `loaiDongHo_new` theo lô (bulk update cùng số danh bộ)

### 4.7 Quản lý Log AI & Phân tích
- Trang danh sách log các lần AI đọc (`tn_meter_reading_log`).
- Xem chi tiết từng log: so sánh AI vs Nhân viên, chấm điểm độ lệch, xem Raw JSON.
- **Lưu trữ hình ảnh an toàn**: Hình ảnh đồng hồ nước được lưu vĩnh viễn trong thư mục riêng tư `img_dhn/YYYY/MM/DD/` và chỉ có thể truy cập qua route xác thực `/logs/image`, bảo vệ tính riêng tư của dữ liệu người dùng.

---

## 5. Cấu trúc Thư mục

```
readdb_water_meter1meter/
├── public/
│   ├── index.php              ← Entry point, định nghĩa routes
│   └── assets/
│       ├── css/style.css
│       └── js/app.js          ← SPA: intercept click/submit → fetch
├── app/
│   ├── Core/
│   │   ├── Router.php         ← URL dispatcher
│   │   ├── Database.php       ← PDO Singleton
│   │   ├── DotEnv.php         ← .env loader
│   │   └── Controller.php     ← Base: view(), json(), redirect()
│   ├── Controllers/
│   │   ├── AiReadController.php      ← SSE stream + logs
│   │   ├── HistoryController.php     ← index, detail, updateMeterType
│   │   ├── MeterTypeController.php   ← CRUD loai_dhn
│   │   ├── GeminiPricingController.php ← CRUD gemini_pricing
│   │   ├── LogController.php         ← Quản lý Log AI & Image stream
│   │   ├── AuthController.php        ← login/logout
│   │   └── UserController.php        ← index/profile
│   ├── Models/
│   │   ├── Gemini.php          ← API client Gemini
│   │   ├── History.php         ← Query chisodhn
│   │   ├── MeterReadingLog.php ← CRUD tn_meter_reading_log
│   │   ├── MeterType.php       ← CRUD loai_dhn
│   │   ├── GeminiPricing.php   ← CRUD gemini_pricing
│   │   └── User.php            ← Auth query users
│   └── Views/
│       ├── layout/main.php     ← Layout chính (AJAX-aware)
│       ├── auth/login.php
│       ├── history/index.php
│       ├── meters/index.php
│       ├── pricing/index.php
│       └── users/
├── test/
│   └── WaterMeterRationalityChecker.php  ← Thư viện chấm điểm
├── database/loai_dhn.sql       ← DDL bảng loai_dhn
├── docs/
│   ├── README.md               ← File này
│   ├── developer-guide.md      ← Hướng dẫn phát triển chi tiết
│   └── database.md             ← Schema chi tiết toàn bộ DB
├── log_doc_chi_so/             ← Log file theo YYYY/MM/DD/log.txt
├── img_dhn/                    ← Thư mục lưu ảnh phân giải (Private)
├── test_ai_read.php            ← CLI test suite (11 test cases)
├── test_ai_read_my_debug_1.0.php ← Debug version cũ
├── migrate_pricing.php         ← Tạo bảng gemini_pricing
├── migrate_meters.php          ← Tạo bảng loai_dhn
├── .env                        ← Cấu hình local (KHÔNG commit)
└── .env.example                ← Template cấu hình
```

---

## 6. Cài đặt Nhanh

```bash
# 1. Clone / copy project vào thư mục
cd readdb_water_meter1meter

# 2. Cấu hình môi trường
cp .env.example .env
# Chỉnh sửa .env: DB_HOST=127.0.0.1, DB_NAME, DB_USER, DB_PASS, GOOGLE_API_KEY

# 3. Tạo bảng (nếu chưa có)
php migrate_meters.php
php migrate_pricing.php

# 4. Chạy server
php -S localhost:8081 -t public

# 5. Truy cập
open http://localhost:8081
```

**Lưu ý:** Dùng `DB_HOST=127.0.0.1` (KHÔNG dùng `localhost`) để tránh lỗi Unix socket trên macOS.

---

## 7. Chạy Test CLI

```bash
# Test với bản ghi cụ thể
php test_ai_read.php 85137 gemini-flash-lite-latest

# Test với bản ghi auto-pick (lấy bản ghi có ảnh đầu tiên)
php test_ai_read.php
```

Test suite chạy 11 test cases bao gồm: API key check → init Gemini → fetch record → download image → call API → parse → rationality check → scoring → save DB → verify DB → write log file.

---

## 8. Routing Table

| Method | URL | Controller@Action | Mô tả |
|------|------|------|------|
| GET | `/` | HistoryController@index | Trang lịch sử chỉ số |
| GET | `/login` | AuthController@showLogin | Form đăng nhập |
| POST | `/login` | AuthController@login | Xử lý đăng nhập |
| GET | `/logout` | AuthController@logout | Đăng xuất |
| GET | `/meters` | MeterTypeController@index | Quản lý loại đồng hồ |
| POST | `/meters/save` | MeterTypeController@save | Thêm/sửa loại đồng hồ |
| POST | `/meters/delete` | MeterTypeController@delete | Xóa loại đồng hồ |
| GET | `/pricing` | GeminiPricingController@index | Quản lý giá AI |
| POST | `/pricing/save` | GeminiPricingController@save | Thêm/sửa giá |
| POST | `/pricing/delete` | GeminiPricingController@delete | Xóa giá |
| GET | `/history/detail` | HistoryController@detail | Chi tiết 1 bản ghi (JSON) |
| POST | `/history/update-meter-type` | HistoryController@updateMeterType | Cập nhật loại đồng hồ |
| GET | `/history/ai-read` | AiReadController@stream | SSE stream đọc AI |
| GET | `/history/ai-read-logs` | AiReadController@logs | Lịch sử log AI (JSON) |
| GET | `/logs` | LogController@index | Trang quản lý AI Logs |
| GET | `/logs/detail` | LogController@detail | JSON chi tiết 1 log AI |
| GET | `/logs/image` | LogController@image | Stream ảnh nội bộ bảo mật |
| GET | `/users` | UserController@index | Danh sách user (admin) |
| GET | `/profile` | UserController@profile | Hồ sơ cá nhân |

---

## 9. Database Tóm tắt

| Bảng | Mô tả | Bản ghi |
|------|------|------|
| `chisodhn` | Dữ liệu chỉ số đồng hồ gốc từ CAWACO | ~85,000+ |
| `tn_meter_reading_log` | Log AI đọc số mỗi lần gọi | tăng dần |
| `loai_dhn` | Cấu hình loại đồng hồ + prompt AI | ít, quản lý thủ công |
| `gemini_pricing` | Bảng giá model Gemini | ~5-10 models |
| `users` | Tài khoản đăng nhập | ít |

→ Xem thêm: [database.md](./database.md)

---

## 10. Người liên hệ & Ghi chú

- **Khách hàng:** CAWACO Cần Giờ
- **Database:** `capnuoccangio` trên MySQL local/production
- **Tỷ giá USD/VND:** hardcode 26,380 trong `Gemini.php` (cập nhật khi cần)
- **Model mặc định:** `gemini-flash-lite-latest` (kinh tế nhất, phù hợp đọc số)

→ Xem thêm: [developer-guide.md](./developer-guide.md)
