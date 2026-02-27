# Database Documentation

## Thông tin kết nối

| Thông số    | Giá trị             |
|-------------|---------------------|
| Host        | `127.0.0.1`         |
| Port        | `3306`              |
| Database    | `capnuoccangio`     |
| Charset     | `utf8mb4`           |

---

## Sơ đồ quan hệ (ERD)

```
chisodhn (1) ──────────────── (N) tn_meter_reading_log
   id  ◄─────────────────────────── id_data

loai_dhn (1) ──────────────── (N) chisodhn
   model_dong_ho ◄────────────────── loaiDongHo_new

gemini_pricing ── dùng để tính chi phí trong Gemini.php

users ── quản lý người dùng / reviewer
```

---

## Bảng: `chisodhn`

**Mô tả:** Dữ liệu chỉ số đồng hồ nước nhập từ hệ thống quản lý CAWACO (trên 85,000 bản ghi).

| Cột | Kiểu | NULL | Mô tả |
|-----|------|------|-------|
| `id` | bigint(20) | NO (PK) | ID tự tăng |
| `soDanhBo` | varchar(50) | YES | Số danh bộ khách hàng |
| `soSerial` | varchar(100) | YES | Số serial đồng hồ |
| `loaiDongHo` | varchar(50) | YES | Loại đồng hồ (cũ) |
| `loaiDongHo_new` | varchar(100) | YES | Loại đồng hồ chính xác — khớp với `loai_dhn.model_dong_ho` |
| `chiSoNuocTN` | bigint(20) | YES | Chỉ số nước **tháng trước** (m³, phần nguyên) |
| `chiSoNuoc` | bigint(20) | YES | Chỉ số nước **tháng hiện tại** do nhân viên ghi (ground truth) |
| `linkHinhDongHo` | varchar(500) | YES | URL ảnh đồng hồ (dùng cho AI đọc) |
| `luongNuocTieuThuThangNay` | int(11) | YES | Lượng tiêu thụ tháng này = `chiSoNuoc - chiSoNuocTN` |
| `luongNuocTieuThuThangTruoc` | int(11) | YES | Lượng tiêu thụ tháng trước (để so sánh) |
| `luongNuocTieuThuTrungBinh3ThangTruoc` | varchar(50) | YES | TB lượng tiêu thụ 3 tháng trước (dùng cho ngưỡng hợp lý) |
| `thang` | int(11) | YES | Tháng ghi chỉ số |
| `nam` | int(11) | YES | Năm ghi chỉ số |
| `created_at` | timestamp | YES | Thời gian tạo bản ghi |

**Chú ý:**
- `linkHinhDongHo` là điều kiện để bản ghi đủ điều kiện gọi AI đọc số.
- `chiSoNuocTN` + `chiSoNuoc` + `luongNuocTieuThuTrungBinh3ThangTruoc` là 3 input chính cho `WaterMeterRationalityChecker`.

---

## Bảng: `tn_meter_reading_log`

**Mô tả:** Log từng lần AI đọc chỉ số đồng hồ — bao gồm kết quả, chi phí, so sánh với nhân viên và điểm đánh giá.

### Nhóm 1: Thông tin cơ bản

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint(20) | PK tự tăng |
| `id_data` | bigint(20) | FK → `chisodhn.id` |
| `model_name` | varchar(100) | Tên model Gemini được chọn (ví dụ: `gemini-flash-lite-latest`) |
| `prompt_version` | varchar(20) | Phiên bản prompt (ví dụ: `1.0`, `1.0-test`) |
| `prompt_text` | text | Nội dung prompt đầy đủ gửi cho AI |
| `img_dhn` | varchar(500) | Đường dẫn lưu ảnh nội bộ (ví dụ: `img_dhn/2026/02/27/meter_123.jpg`) |
| `linkHinhDongHo` | varchar(500) | URL ảnh gốc từ hệ thống CAWACO |
| `created_at` | timestamp | Thời gian tạo (auto) |

### Nhóm 2: Kết quả AI

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `ai_chi_so` | varchar(20) | Chuỗi raw AI trả về cho trường chi_so (ví dụ: `"36539.14"`) |
| `ai_chi_so_parse` | bigint(20) | Chỉ số sau khi parse thành integer (ví dụ: `36539`) |
| `co_ky_tu_x` | tinyint(1) | 1 nếu có ký tự X (chữ số AI không đọc được) |
| `so_ky_tu_x` | tinyint(4) | Số lượng ký tự X |
| `raw_response` | text | Raw text response từ Gemini API |

### Nhóm 3: Token & Chi phí

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `prompt_tokens` | int(11) | Số token đầu vào (ảnh + prompt) |
| `output_tokens` | int(11) | Số token đầu ra |
| `thinking_tokens` | int(11) | Số token tư duy (Gemini 2.5+), mặc định 0 |
| `total_tokens` | int(11) | Tổng token |
| `chi_phi_usd` | decimal(14,8) | Chi phí USD (ví dụ: `0.00010360`) |
| `chi_phi_vnd` | decimal(15,2) | Chi phí VND tương đương (tỷ giá × chi_phi_usd) |
| `thoi_gian_xu_ly` | int(11) | Thời gian gọi API (ms) |

### Nhóm 4: Trạng thái API

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `api_started_at` | timestamp | Thời điểm bắt đầu gọi API |
| `api_completed_at` | timestamp | Thời điểm kết thúc gọi API |
| `retry_count` | tinyint(4) | Số lần thử lại (mặc định 0) |
| `trang_thai_api` | enum | `thanh_cong` / `timeout` / `loi_api` / `loi_parse` / `rate_limit` |
| `thong_bao_loi` | text | Thông báo lỗi nếu có |

### Nhóm 5: So sánh AI vs Nhân viên

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `human_chi_so` | bigint(20) | Chỉ số nhân viên ghi (= `chisodhn.chiSoNuoc`) |
| `is_exact_match` | tinyint(1) | 1 nếu AI parse == nhân viên ghi |
| `sai_so` | bigint(20) | `ai_chi_so_parse - human_chi_so` (có dấu) |
| `sai_so_tuyet_doi` | bigint(20) | `\|sai_so\|` |
| `loai_sai_so` | varchar(50) | Phân loại sai số (tùy chỉnh) |
| `char_match_count` | tinyint(4) | Số ký tự khớp (tính từng chữ số) |
| `char_total_count` | tinyint(4) | Tổng số chữ số cần so sánh |
| `char_accuracy_rate` | decimal(5,2) | Tỷ lệ ký tự khớp = `char_match_count / char_total_count` |

### Nhóm 6: Đánh giá tính hợp lý

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `is_rationality` | tinyint(1) | 1=hợp lý, 0=bất hợp lý, NULL=không có lịch sử |
| `luong_tieu_thu_ai` | bigint(20) | Lượng tiêu thụ AI tính được = `ai_chi_so_parse - chiSoNuocTN` |
| `nguong_hop_ly_min` | decimal(10,2) | Ngưỡng min = TB3T × hệ số min |
| `nguong_hop_ly_max` | decimal(10,2) | Ngưỡng max = TB3T × hệ số max |
| `ly_do_bat_hop_ly` | varchar(200) | Lý do nếu bất hợp lý |

### Nhóm 7: Score POC — Giai đoạn 1 (có đối chứng nhân viên)

> Dùng khi POC (Proof of Concept) — có ground truth từ nhân viên.

| Cột | Kiểu | Điểm tối đa | Mô tả |
|-----|------|-------------|-------|
| `score_so_sat` | tinyint(4) | 60 | Điểm so sánh trực tiếp AI vs Nhân viên. 60=đúng tuyệt đối, 50=sai ≤1, 35=sai ≤5, 15=sai ≤50, 0=sai >50 |
| `score_ky_tu_poc` | tinyint(4) | 40 | Điểm tỷ lệ ký tự khớp = `char_accuracy_rate × 40` |
| `score_poc` | tinyint(4) | **100** | Tổng điểm POC = `score_so_sat + score_ky_tu_poc` |
| `muc_do_poc` | varchar(30) | — | Phân loại: xem bảng mức độ bên dưới |

**Mức độ `muc_do_poc`:**

| Giá trị | Điểm | Ý nghĩa |
|---------|------|---------|
| `AI_CHINH_XAC_CAO` | ≥ 90 | Chính xác cao — có thể triển khai |
| `AI_CHAP_NHAN_DUOC` | 70–89 | Chấp nhận được — cần cải thiện thêm |
| `AI_CAN_CANH_BAO` | 50–69 | Cần cảnh báo — review kỹ trước khi dùng |
| `AI_KHONG_DAT_YEU_CAU` | < 50 | Không đạt yêu cầu — chưa đủ tin cậy |

### Nhóm 8: Score Thực tế — Giai đoạn 2 (vận hành, không cần nhân viên)

> Dùng khi triển khai thực tế — chỉ dựa vào lịch sử & tính hợp lý.

| Cột | Kiểu | Điểm tối đa | Mô tả |
|-----|------|-------------|-------|
| `score_hop_ly` | tinyint(4) | 50 | Điểm đánh giá tính hợp lý. 50=HOP_LY, 30=KHONG_CO_LICH_SU, 10=NGHI_NGO, 0=LOI |
| `score_do_lech_tb` | tinyint(4) | 30 | Điểm độ lệch so với TB3T. 30=lệch ≤10%, 20=lệch ≤30%, 10=lệch ≤60%, 0=lệch >60% |
| `score_doc_duoc` | tinyint(4) | 20 | Điểm khả năng đọc. 20=không có X, 10=có 1X, 5=có ≥2X, 0=NULL |
| `score_thuc_te` | tinyint(4) | **100** | Tổng điểm thực tế = 3 thành phần trên |
| `muc_do_thuc_te` | varchar(30) | — | Quyết định: xem bảng mức độ bên dưới |

**Mức độ `muc_do_thuc_te`:**

| Giá trị | Điểm | Ý nghĩa |
|---------|------|---------|
| `TU_DONG_CHAP_NHAN` | ≥ 80 | Tự động chấp nhận — không cần review |
| `CHAP_NHAN_CO_THEO_DOI` | 60–79 | Chấp nhận, có theo dõi — ghi log |
| `CAN_REVIEW` | 40–59 | Cần review — chuyển nhân viên xác nhận |
| `TU_CHOI` | < 40 | Từ chối — yêu cầu chụp lại hoặc nhân viên đọc |

### Nhóm 9: Review thủ công

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `image_type` | enum | `hinh_ro` / `hinh_mo` / `hinh_khong_day_du` / `hinh_khong_doc_duoc` |
| `is_accept` | tinyint(1) | 1 nếu kết quả AI được chấp nhận |
| `is_accept_for_billing` | tinyint(1) | 1 nếu dùng được cho tính tiền |
| `last_reviewer` | varchar(100) | Người review cuối cùng |
| `last_reviewed_at` | datetime | Thời gian review |
| `last_review_note` | text | Ghi chú của reviewer |

---

## Bảng: `gemini_pricing`

**Mô tả:** Bảng giá API Gemini — dùng để tính chi phí thực của mỗi lần gọi API.

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint unsigned | PK |
| `model_name` | varchar(100) | Tên model (ví dụ: `gemini-flash-lite-latest`) |
| `input_price_low_context` | decimal(10,8) | Giá input context ngắn (USD / 1M tokens) |
| `input_price_high_context` | decimal(10,8) | Giá input context dài (USD / 1M tokens) |
| `output_price_low_context` | decimal(10,8) | Giá output context ngắn (USD / 1M tokens) |
| `output_price_high_context` | decimal(10,8) | Giá output context dài (USD / 1M tokens) |
| `context_threshold` | int(11) | Ngưỡng token chia low/high context |
| `unit_amount` | int(11) | Luôn là `1000000` (giá per 1M tokens) |
| `currency` | varchar(10) | `USD` |
| `updated_at` | timestamp | Thời gian cập nhật giá |

**Công thức tính chi phí:**
```
chi_phi_usd = (input_price_low_context × prompt_tokens / 1,000,000)
            + (output_price_low_context × (output_tokens + thinking_tokens) / 1,000,000)

chi_phi_vnd = chi_phi_usd × TY_GIA_USD_VND  (26,380 VND/USD, hardcode trong Gemini.php)
```

**Giá hiện tại (mẫu):**

| Model | Input (USD/1M) | Output (USD/1M) |
|-------|----------------|-----------------|
| gemini-2.5-flash-lite | 0.10 | 0.40 |
| gemini-2.5-flash | 0.30 | 2.50 |
| gemini-2.5-pro | 1.25 | 2.50 |
| gemini-3-flash-preview | 0.50 | 3.00 |
| gemini-3-pro-preview | 2.00 | 12.00 |

---

## Bảng: `loai_dhn`

**Mô tả:** Danh mục loại đồng hồ nước — lưu quy tắc đọc số, prompt tối ưu, và cấu hình LLM cho từng loại.

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) | PK |
| `model_dong_ho` | varchar(100) | Tên model đồng hồ (FK từ `chisodhn.loaiDongHo_new`) |
| `loai_hien_thi` | varchar(100) | Kiểu hiển thị (LCD, cơ học, con lăn...) |
| `vung_hien_thi` | varchar(500) | Mô tả vùng hiển thị chỉ số trên mặt đồng hồ |
| `phan_nguyen_digits` | tinyint(4) | Số chữ số phần nguyên |
| `phan_nguyen_color` | varchar(50) | Màu chữ số phần nguyên |
| `phan_nguyen_background` | varchar(50) | Màu nền phần nguyên |
| `phan_thap_phan_digits` | tinyint(4) | Số chữ số phần thập phân |
| `phan_thap_phan_color` | varchar(50) | Màu chữ số phần thập phân |
| `phan_thap_phan_background` | varchar(50) | Màu nền phần thập phân |
| `quy_tac_lam_tron` | text | Quy tắc làm tròn chỉ số |
| `quy_tac_bo_sung` | text | Quy tắc bổ sung |
| `la_mac_dinh` | tinyint(1) | 1 nếu là loại mặc định |
| `last_prompt_version` | varchar(10) | Phiên bản prompt tối ưu nhất |
| `last_prompt_txt` | text | Nội dung prompt tối ưu cho loại này |
| `last_llm_models` | longtext | Danh sách model Gemini phù hợp (JSON) |
| `is_active` | tinyint(1) | 1 nếu đang sử dụng |
| `create_user` | varchar(100) | Người tạo |
| `create_time` | timestamp | Thời gian tạo |
| `edit_user` | varchar(100) | Người sửa cuối |
| `edit_time` | timestamp | Thời gian sửa cuối |

---

## Bảng: `users`

**Mô tả:** Tài khoản người dùng của hệ thống.

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | int(11) | PK |
| `username` | varchar(50) | Tên đăng nhập (unique) |
| `email` | varchar(100) | Email (unique) |
| `password_hash` | varchar(255) | Mật khẩu đã hash (bcrypt) |
| `ten_nhan_vien` | varchar(100) | Tên hiển thị |
| `vai_tro` | enum | `admin` / `nhan_vien` / `chi_doc` |
| `trang_thai` | enum | `hoat_dong` / `bi_khoa` |
| `last_login_at` | datetime | Thời gian đăng nhập gần nhất |
| `last_login_ip` | varchar(45) | IP đăng nhập gần nhất |
| `created_at` | timestamp | Thời gian tạo |
| `updated_at` | timestamp | Thời gian cập nhật |

---

## File Log (Filesystem)

Ngoài DB, hệ thống còn ghi log ra file theo cấu trúc:

```
log_doc_chi_so/
└── YYYY/
    └── MM/
        └── DD/
            └── log.txt   ← Ghi nối cuối file, mỗi lần đọc 1 entry
```

**Nội dung mỗi entry:**
```
============================================================
[2026-02-26 15:44:56] ID: 85137 | SDB: 23061091000 | Model: gemini-flash-lite-latest
Status: thanh_cong | Time: 2890ms | Log ID: #11
AI Chi So: 36539.14 | Parse: 36539
Human Chi So: 36539 | Match: 1
Score POC: 100/100 [AI_CHINH_XAC_CAO]
Score TT : 80/100 [TU_DONG_CHAP_NHAN]
Tokens: P=492 O=136 T=0
Cost: 0.000000 VND
============================================================
```

---

## Queries hữu ích

### Xem log AI gần nhất theo bản ghi
```sql
SELECT * FROM tn_meter_reading_log
WHERE id_data = 85137
ORDER BY created_at DESC LIMIT 10;
```

### Thống kê score POC theo model
```sql
SELECT
    model_name,
    COUNT(*) AS total,
    ROUND(AVG(score_poc), 1) AS avg_score_poc,
    SUM(is_exact_match) AS exact_match,
    ROUND(AVG(char_accuracy_rate) * 100, 1) AS avg_char_accuracy_pct
FROM tn_meter_reading_log
WHERE trang_thai_api = 'thanh_cong'
GROUP BY model_name
ORDER BY avg_score_poc DESC;
```

### Danh sách cần review (score thực tế thấp)
```sql
SELECT
    l.id, l.id_data, c.soDanhBo,
    l.model_name, l.ai_chi_so_parse, l.human_chi_so, l.sai_so,
    l.score_poc, l.score_thuc_te, l.muc_do_thuc_te,
    l.ly_do_bat_hop_ly
FROM tn_meter_reading_log l
JOIN chisodhn c ON c.id = l.id_data
WHERE l.score_thuc_te < 60
  AND l.trang_thai_api = 'thanh_cong'
ORDER BY l.score_thuc_te ASC, l.created_at DESC;
```

### Tỷ lệ tự động chấp nhận theo model
```sql
SELECT
    model_name,
    COUNT(*) AS total,
    SUM(muc_do_thuc_te = 'TU_DONG_CHAP_NHAN') AS tu_dong,
    ROUND(SUM(muc_do_thuc_te = 'TU_DONG_CHAP_NHAN') / COUNT(*) * 100, 1) AS `%_tu_dong`
FROM tn_meter_reading_log
WHERE trang_thai_api = 'thanh_cong'
GROUP BY model_name;
```

### Tổng chi phí theo ngày
```sql
SELECT
    DATE(created_at) AS ngay,
    COUNT(*) AS so_lan_goi,
    ROUND(SUM(chi_phi_vnd), 2) AS tong_chi_phi_vnd,
    ROUND(SUM(chi_phi_usd), 6) AS tong_chi_phi_usd
FROM tn_meter_reading_log
WHERE trang_thai_api = 'thanh_cong'
GROUP BY DATE(created_at)
ORDER BY ngay DESC;
```

---

*Cập nhật lần cuối: 2026-02-27 | Người viết: AI Assistant*
