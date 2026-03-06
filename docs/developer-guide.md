# 🔧 Developer Guide — Water Meter AI Reader

**Tài liệu kỹ thuật chi tiết** dành cho developer phát triển và kế thừa dự án.

---

## 1. Kiến trúc Lớp (Layer Architecture)

```
┌────────────────────────────────────────────┐
│            public/index.php                │  ← Entry point duy nhất
│         (Bootstrap + Routes)               │
└─────────────────┬──────────────────────────┘
                  ▼
┌────────────────────────────────────────────┐
│              Core Layer                    │
│  Router → Controller → View               │
│  Database (PDO Singleton)                  │
│  DotEnv (.env loader)                      │
└─────────────────┬──────────────────────────┘
                  ▼
┌────────────────────────────────────────────┐
│            Controllers Layer               │
│  Xử lý HTTP request, gọi Model, trả View  │
└─────────────────┬──────────────────────────┘
                  ▼
┌────────────────────────────────────────────┐
│             Models Layer                   │
│  Query DB (PDO), gọi API (Gemini)          │
└────────────────────────────────────────────┘
```

---

## 2. Core Layer — Chi tiết

### 2.1 `public/index.php` — Bootstrap & Routes

File duy nhất được web server phục vụ. Thực hiện:
1. Load `.env` qua `DotEnv`
2. Đăng ký autoloader PSR-4 cho namespace `App\`
3. `session_start()`
4. Định nghĩa tất cả routes
5. Gọi `$router->dispatch($method, $uri)`

**Thêm route mới:**
```php
$router->add('GET', '/new-page', 'NewController@index');
$router->add('POST', '/new-page/save', 'NewController@save');
```

> ⚠️ Router hiện tại là **exact match** (không hỗ trợ dynamic segments như `/users/{id}`).
> Nếu cần, truyền ID qua query string: `/users?id=5`.

---

### 2.2 `app/Core/Router.php`

```php
$router->add(string $method, string $path, string $handler)
// handler format: 'ControllerName@methodName'
// Ví dụ: 'HistoryController@index'
```

Router resolve `handler` thành `App\Controllers\ControllerName` và gọi method tương ứng.

---

### 2.3 `app/Core/Database.php` — PDO Singleton

```php
$db = Database::getInstance();
// Trả về PDO instance đã cấu hình:
// - ERRMODE_EXCEPTION
// - FETCH_ASSOC
// - EMULATE_PREPARES = false
```

**Cấu hình từ `.env`:**
```
DB_HOST=127.0.0.1   # LUÔN dùng 127.0.0.1, KHÔNG dùng localhost (macOS socket issue)
DB_PORT=3306
DB_NAME=capnuoccangio
DB_USER=xxx
DB_PASS=xxx
```

---

### 2.4 `app/Core/Controller.php` — Base Controller

```php
// Render View với layout
$this->view('history/index', ['key' => $value]);

// Trả về JSON response + set HTTP status
$this->json(['success' => true], 200);
$this->json(['error' => 'message'], 400);

// Redirect
$this->redirect('/login');

// Render Raw View (không kèm layout)
$this->viewRaw('logs/dashboard2', $data);
```

**Convention:** 
- Tất cả controller `extends Controller`
- Constructor check session: `if (!isset($_SESSION['user_id'])) { ... }`
- Session keys chuẩn: `user_id`, `username`, `role`
- Dùng `view()` cho nội dung SPA, `viewRaw()` cho các trang đặc biệt không cần layout header/nav mặc định.

---

## 3. Controllers — Chi tiết

### 3.1 `AiReadController` — AI Reading Flow

Đây là controller quan trọng nhất. Có 2 actions:

#### `stream()` — GET /history/ai-read
Endpoint SSE (Server-Sent Events). Client kết nối qua `EventSource` API.  
**Parameters:** 
- `?id=RECORD_ID`
- `model_names=JSON_ARRAY_OF_MODELS` (VD: `["gemini-flash-lite-latest", "gemini-2.0-flash"]`)
- `prompt_text=PROMPT`

**Multi-model Sequential Reading:**
Controller sẽ thử lần lượt các model trong danh sách `model_names`. 
1. Nếu model hiện tại trả về kết quả thành công và parse được -> Dừng lại và trả kết quả `done`.
2. Nếu model lỗi (API error) hoặc không parse được JSON -> Ghi log lỗi, tăng `retry_count` và tiếp tục thử model tiếp theo.
3. Nếu tất cả model đều thất bại -> Trả về `error_event`.

**7 bước xử lý chính:**
```
1. fetch_record      → History::findById($id)
2. downloading_image → file_get_contents(image_url) → lưu vào img_dhn/
3. [Loop models]     → Gemini::prompt_image(temp, prompt, model)
4. parsing           → extract chi_so, handle X chars, calc cost/accuracy
5. scoring           → gọi WaterMeterRationalityChecker để tính Score POC & Score Thực tế
6. saving            → MeterReadingLog::create($logData)
7. write_log_file    → log_doc_chi_so/YYYY/MM/DD/log.txt
8. done              → send final SSE event với toàn bộ kết quả
```

**SSE Event Format:**
```javascript
// progress events (bước giữa)
event: progress
data: {"step": "fetch_record", "label": "📋 Đang lấy dữ liệu..."}

// done event (bước cuối)
event: done
data: {
    "log_id": 123, 
    "ai_chi_so": "36539.14", 
    "ai_chi_so_parse": 36539, 
    "score_poc": 100, 
    "score_thuc_te": 85,
    "score_poc_details": {...},
    "score_thuc_te_details": {...},
    ...
}

// error event (khi có lỗi)
event: error_event
data: {"message": "Lỗi..."}
```

> **Lưu ý:** Tên event lỗi là `error_event` (KHÔNG phải `error`) để tránh conflict với EventSource built-in error.

#### `logs()` — GET /history/ai-read-logs
Trả về JSON danh sách log quá khứ cho một bản ghi.  
**Parameter:** `?id_data=RECORD_ID`

---

### 3.2 `HistoryController`

| Action | Route | Chức năng |
|------|------|------|
| `index()` | GET `/` | List + lọc bản ghi chisodhn, 10/trang |
| `detail()` | GET `/history/detail?id=X` | JSON chi tiết 1 bản ghi |
| `updateMeterType()` | POST `/history/update-meter-type` | Cập nhật loại đồng hồ |
| `updateImageType()` | POST `/history/update-image-type` | Cập nhật phân loại ảnh (hinh_ro, hinh_mo, ...) |
| `savePromptInfo()` | POST `/history/save-prompt-info` | Lưu cấu hình prompt và model mặc định cho Record/MeterType |

**Filters hỗ trợ:** `nam`, `thang`, `loaiDongHo`, `loaiDongHo_new`, `soDanhBo`, `coHinh`

**Bulk update:** POST với `bulk=1` sẽ update tất cả bản ghi cùng `soDanhBo`.

---

### 3.3 `LogController` — Quản lý Log AI & Image Stream

Đây là trang Dành riêng cho việc xem lại các quyết định của AI, filter theo mức độ hợp lý, model, và thời gian.

| Action | Route | Chức năng |
|------|------|------|
| `index()` | GET `/logs` | Liệt kê log AI đọc (mới nhất lên trước) có phân trang. |
| `detail()` | GET `/logs/detail?id=X` | JSON chi tiết 1 lần AI đọc, điểm số và lý do. |
| `image()` | GET `/logs/image?path=...` | Stream file ảnh từ thư mục private `img_dhn/`. |
| `dashboard()` | GET `/logs/dashboard` | Thống kê hiệu suất AI qua các biểu đồ (V1). |
| `dashboard2()` | GET `/logs/dashboard2` | Dashboard V2 hiện đại, sử dụng `viewRaw` để render full-screen giao diện mới. |

**Bảo mật hình ảnh (Secure Image Streaming):**
Hình ảnh lấy về từ CAWACO không được lưu public. Chúng được lưu trong thư mục `img_dhn/` ở thư mục gốc (nằm ngoài thư mục `public/`). `LogController@image` kiểm tra login session và chặn directory traversal attacks trước khi `readfile()` hình ảnh ra cho người dùng.

---

### 3.4 `MeterTypeController`

CRUD bảng `loai_dhn`.  
**Quan trọng:** Trường `la_mac_dinh` dùng UNIQUE constraint với giá trị `1` hoặc `NULL` (không phải `0`). Logic xử lý trong `MeterType::create()` và `update()`.

**Validation JSON:** `last_llm_models` phải là JSON hợp lệ hoặc NULL.

---

### 3.4 `GeminiPricingController`

CRUD bảng `gemini_pricing`. Giá lưu theo đơn vị **USD per 1,000,000 tokens**.

---

## 4. Models — Chi tiết

### 4.1 `Gemini.php` — API Client

```php
$gemini = new Gemini(); // Đọc GOOGLE_API_KEY từ $_ENV
$result = $gemini->prompt_image($imagePath, $promptText, $modelName);

// $result structure:
[
    'error' => '',         // '1' nếu có lỗi
    'message' => '',       // thông báo lỗi
    'data' => [
        'modelVersion' => 'gemini-2.0-flash-lite-...',
        'prompt_tokens' => 492,
        'output_tokens' => 136,
        'thinking_tokens' => 0,
        'cost_usd' => 0.00003521,
        'cost_vnd' => 0.928,
        'tygia' => 26380,
        'raw_response' => '{"chi_so": "36539.14", ...}',
        'content' => ['chi_so' => '36539.14', 'chi_so_phan_nguyen' => '36539', ...],
        'finish_reason' => 'STOP',
    ]
]
```

**Tính chi phí:**
- Lấy giá từ bảng `gemini_pricing` (exact match → fuzzy match theo prefix)
- `cost_usd = (input_price × prompt_tokens + output_price × (output+thinking) tokens) / 1,000,000`
- `cost_vnd = cost_usd × 26,380`

**Xử lý image input:**
- Nhận đường dẫn local file hoặc URL
- Nếu URL → download xuống temp file → cleanup sau
- Encode base64 + detect MIME type → gửi `inline_data`

---

### 4.2 `MeterReadingLog.php` — Log Table

```php
// Insert log
$logId = MeterReadingLog::create($data); // returns last insert ID

// Fetch by ID
$log = MeterReadingLog::findById($logId);

// Fetch all logs for a record
$logs = MeterReadingLog::findByDataId($recordId);
```

**Whitelist columns trong `create()`:**  
Chỉ các cột trong mảng `$allowed` được insert. Cột không có trong whitelist sẽ bị bỏ qua.  
→ Khi thêm cột mới vào DB, PHẢI thêm vào `$allowed` array trong `MeterReadingLog.php`.

---

### 4.3 `History.php` — Query chisodhn

```php
// List với filter + pagination
$records = History::all($filters, $limit, $offset);
$total = History::count($filters);

// Find one
$record = History::findById($id);

// Update loại đồng hồ
History::updateMeterType($id, $loaiDongHo_new);
History::bulkUpdateMeterType($soDanhBo, $loaiDongHo_new);
```

---

### 4.4 `MeterType.php`, `GeminiPricing.php`, `User.php`
Các model này chủ yếu cung cấp phương thức tĩnh để CRUD và lookup (ví dụ: lấy giá model gần nhất, tìm user theo username).

---

## 5. Frontend (SPA)

### 5.1 `public/assets/js/app.js`

Vanilla JS SPA:
- **Intercept navigation:** Tất cả `<a>` click và `<form>` submit trong `#main-content` đều được intercept
- **Fetch:** Gửi `X-Requested-With: XMLHttpRequest` header → server check header này để chỉ trả partial HTML
- **pushState:** URL cập nhật không reload trang
- **`getSection()`:** Xác định "section" từ URL (`/` = history, `/meters` = meters, ...) để quyết định partial hay full update

**Partial update `#history-results`:**  
Chỉ khi tìm kiếm trong cùng section `history → history`. Các link cross-section luôn full update.

### 5.2 `app/Views/layout/main.php`

Layout chính kiểm tra `$isAjax`:
- AJAX request: chỉ render nội dung view (dùng cho SPA)
- Full request: render toàn bộ HTML với nav + main layout

**Nav links hiển thị khi `$_SESSION['user_id']` tồn tại.**  
**Link `/users` chỉ hiện với `$_SESSION['role'] === 'admin'`.**

---

## 6. WaterMeterRationalityChecker

**File:** `app/Services/WaterMeterRationalityChecker.php`  
**Namespace:** `App\Services` (tự động load qua PSR-4 autoloader)

### 6.1 API cốt lõi

```php
// Bước 1: Đánh giá hợp lý
$danhGia = WaterMeterRationalityChecker::danhGia(
    ?float $aiChiSoParse,           // Chỉ số AI đọc được (null nếu không đọc được)
    float  $chiSoNuocTN,            // Chỉ số tháng trước (bắt buộc)
    ?float $luongNuocTieuThuThangTruoc,  // Lượng TT tháng trước
    ?float $luongNuocTieuThuTB3Thang,    // Lượng TB 3 tháng
    array  $config = []             // Override config (tùy chọn)
);
// Returns:
// ['ket_qua', 'is_rationality', 'luong_tieu_thu', 'nguong_min', 'nguong_max', 'ly_do', ...]

// Bước 2: Score POC (cần ground truth)
$scorePoc = WaterMeterRationalityChecker::tinhScorePoc(
    ?float $aiChiSoParse,
    ?float $humanChiSo,
    ?float $charAccuracyRate  // 0.0 - 1.0
);
// Returns: ['score_so_sat', 'score_ky_tu_poc', 'score_poc', 'muc_do_poc', 'chi_tiet']

// Bước 3: Score Thực tế (không cần ground truth)
$scoreTT = WaterMeterRationalityChecker::tinhScoreThucTe(
    array $ketQuaDanhGia,    // Kết quả từ danhGia()
    ?float $luongNuocTieuThuTB3T,
    int $soKyTuX,
    bool $aiDocDuoc
);
// Returns: ['score_hop_ly', 'score_do_lech_tb', 'score_doc_duoc', 'score_thuc_te', 'muc_do_thuc_te', 'chi_tiet']
```

### 6.2 Ngưỡng đánh giá (cấu hình mặc định)

| Tham số | Mặc định | Ý nghĩa |
|------|------|------|
| `he_so_nguong_min` | 0.2 | Tiêu thụ ≥ TB3T × 0.2 mới hợp lý |
| `he_so_nguong_max` | 3.0 | Tiêu thụ ≤ TB3T × 3.0 mới hợp lý |
| `he_so_tang_vs_thang_truoc` | 2.0 | Tăng > TT_tháng_trước × 2.0 → nghi ngờ |
| `he_so_giam_vs_thang_truoc` | 0.5 | Giảm > TT_tháng_trước × 0.5 → nghi ngờ |
| `poc_nguong_sai_so_rat_nho` | 1 | |sai_so| ≤ 1 → 50 điểm |
| `poc_nguong_sai_so_nho` | 5 | |sai_so| ≤ 5 → 35 điểm |
| `poc_nguong_sai_so_vua` | 50 | |sai_so| ≤ 50 → 15 điểm |

Tất cả có thể override khi gọi: `::danhGia(..., ['he_so_nguong_max' => 4.0])`.

### 6.3 Mức độ Score

**Score POC (có ground truth):**
| Điểm | Mức độ | Hành động |
|------|------|------|
| ≥ 90 | `AI_CHINH_XAC_CAO` | Có thể triển khai |
| 70-89 | `AI_CHAP_NHAN_DUOC` | Cần cải thiện thêm |
| 50-69 | `AI_CAN_CANH_BAO` | Review kỹ trước khi dùng |
| < 50 | `AI_KHONG_DAT_YEU_CAU` | Chưa đủ tin cậy |

**Score Thực tế (vận hành):**
| Điểm | Mức độ | Hành động |
|------|------|------|
| ≥ 80 | `TU_DONG_CHAP_NHAN` | Tự động lấy, không cần review |
| 60-79 | `CHAP_NHAN_CO_THEO_DOI` | Chấp nhận, ghi log theo dõi |
| 40-59 | `CAN_REVIEW` | Chuyển nhân viên xác nhận |
| < 40 | `TU_CHOI` | Yêu cầu chụp lại hoặc nhân viên đọc |

---

## 7. Parse Logic — Chi tiết

Logic parse chỉ số AI nằm trong cả `AiReadController::stream()` và `test_ai_read.php`:

```
1. Lấy content từ Gemini response (đã parse JSON)
2. Ưu tiên field `chi_so_phan_nguyen` (phần nguyên riêng)
3. Fallback sang `chi_so` (full string) nếu không có phan_nguyen
4. Detect ký tự 'X' (chữ số không đọc được): set co_ky_tu_x=1, so_ky_tu_x=N
5. Normalize: bỏ space, comma → split theo dấu '.'
6. Lấy phần nguyên (trước '.')
7. Strip ký tự non-numeric → cast int
```

**Ví dụ:**
| AI trả về | Kết quả parse |
|------|------|
| `"36539.14"` | 36539 |
| `"36,539"` | 36539 |
| `"3X539"` | co_ky_tu_x=1, parse=3539 (bỏ X) |
| `"N/A"` | null |
| `""` | null |

---

## 8. Logging & Storage

### 8.1 Database Log (`tn_meter_reading_log`)
Mỗi lần gọi AI đều insert 1 bản ghi, lưu kèm đường dẫn gốc (`linkHinhDongHo`) và đường dẫn lưu trữ nội bộ (`img_dhn`).  
→ Xem schema chi tiết: [database.md](./database.md)

### 8.2 Image Storage (`img_dhn/`)
Hình ảnh tải về được lưu theo cấu trúc ngày để không vượt quá giới hạn file/thư mục của HĐH:
```
img_dhn/
└── YYYY/
    └── MM/
        └── DD/
            └── meter_{ID}_{TIMESTAMP}.jpg
```
*Lưu ý: Thư mục này nằm ngoài `public/` để bảo đảm tính riêng tư. UI gọi qua `/logs/image?path=...`.*

### 8.3 File Log (`log_doc_chi_so/`)
```
log_doc_chi_so/
└── 2026/
    └── 02/
        └── 26/
            └── log.txt   ← FILE_APPEND | LOCK_EX
```

Format entry:
```
============================================================
[2026-02-26 15:44:56] ID: 85137 | SDB: 23061091000 | Model: gemini-flash-lite-latest
Status: thanh_cong | Time: 2890ms | Log ID: #11
AI Chi So: 36539.14 | Parse: 36539
Human Chi So: 36539 | Match: 1
Score POC: 100/100 [AI_CHINH_XAC_CAO]
Score TT : 80/100 [TU_DONG_CHAP_NHAN]
Tokens: P=492 O=136 T=0
Cost: 0.928450 VND
============================================================
```

---

## 9. Test Suite CLI

**File:** `test_ai_read.php`

| Test | Mô tả |
|------|------|
| #1 | Kiểm tra `GOOGLE_API_KEY` trong `.env` |
| #2 | Khởi tạo `Gemini` model |
| #3 | Tìm bản ghi có ảnh trong DB |
| #4 | Tải ảnh từ URL |
| #5 | Gọi Gemini API và nhận response |
| #6 | Parse chỉ số từ kết quả AI |
| #7 | Đánh giá hợp lý + tính score POC + score Thực tế |
| #8 | Lưu vào `tn_meter_reading_log` (đầy đủ scoring) |
| #9 | Đọc lại từ DB, verify các trường scoring |
| #10 | `findByDataId()` và verify log mới có trong list |
| #11 | Ghi log file, verify file tồn tại và chứa entry |

**Chạy:**
```bash
php test_ai_read.php [record_id] [model_name]
# ví dụ:
php test_ai_read.php 85137 gemini-flash-lite-latest
```

---

## 10. Prompt AI Chuẩn

Prompt hiện tại sử dụng trong `test_ai_read.php` (và tương tự trong UI):

```
You are a vision model that reads water meter indexes from photos.

Task:
- Look at the provided image of a water meter.
- Identify the main cumulative water index shown on the meter.
- Convert this index to an integer number of cubic meters by removing any decimal or fractional digits if present.
- If the digit is un-reconginize, note it as X

Important:
- Ignore small text such as units (m³/h), timestamps, serial numbers, or other labels.
- If the screen shows more than 5-7 digits, keep only the leftmost digits that represent whole cubic meters.
- If you are not fully sure, choose the value that matches the big main number on the meter.

Output format is a json string as below, no extra text:
{
  "chi_so": "<full_index_string>",
  "chi_so_phan_nguyen": "<integer_part>",
  "so_serial": "<serial_number>",
  "giai_thich_chi_so": "<explanation_of_the_reading>"
}
```

---

## 11. Cấu hình Model Gemini

Gemini models được lưu trong bảng `gemini_pricing`. Khi gọi API:
1. Gemini trả về `modelVersion` (có thể khác với tên model request)
2. Hệ thống tìm giá theo `modelVersion` (exact → fuzzy prefix match)
3. Nếu không tìm được giá → cost = 0 (không gây lỗi)

**Models phổ biến dùng dự án:**
| Model | Đặc điểm | Chi phí |
|------|------|------|
| `gemini-flash-lite-latest` | Nhanh, kinh tế nhất | ~$0.10/1M input |
| `gemini-2.5-flash` | Cân bằng tốt | ~$0.30/1M input |
| `gemini-2.5-pro` | Mạnh nhất | ~$1.25/1M input |

---

## 12. Hướng dẫn Mở rộng

### 12.1 Thêm Controller mới

```php
// app/Controllers/NewController.php
namespace App\Controllers;
use App\Core\Controller;

class NewController extends Controller
{
    public function __construct()
    {
        // Check session
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    public function index()
    {
        $data = SomeModel::all();
        $this->view('new/index', ['data' => $data]);
    }
}
```

```php
// Thêm route vào public/index.php
$router->add('GET', '/new', 'NewController@index');
```

---

### 12.2 Thêm cột vào `tn_meter_reading_log`

1. **ALTER TABLE** thêm cột vào MySQL
2. Thêm tên cột vào `$allowed` array trong `MeterReadingLog::create()`
3. Cập nhật Logic trong `AiReadController::stream()` để populate cột mới
4. Cập nhật `test_ai_read.php` nếu cần test

---

### 12.3 Thêm model Gemini mới

1. Vào UI `/pricing` → thêm model mới với giá USD per 1M tokens
2. Hoặc insert trực tiếp:
```sql
INSERT INTO gemini_pricing (model_name, input_price_low_context, output_price_low_context, unit_amount)
VALUES ('gemini-new-model', 0.20, 0.80, 1000000);
```

---

### 12.4 Thêm loại đồng hồ mới

1. Vào UI `/meters` → thêm loại đồng hồ
2. Điền đầy đủ: model, loại hiển thị, số chữ số, màu sắc
3. Cấu hình `last_prompt_txt` (prompt tối ưu cho loại này)
4. Cấu hình `last_llm_models` (JSON):
```json
[{"priority": 1, "model_name": "gemini-flash-lite-latest"}]
```

---

## 13. Các Gotchas & Lưu ý Kỹ thuật

| # | Vấn đề | Giải pháp |
|------|------|------|
| 1 | `DB_HOST=localhost` gây lỗi socket trên macOS | Dùng `127.0.0.1` |
| 2 | `la_mac_dinh` UNIQUE constraint cản insert | Dùng `NULL` (không phải `0`) cho non-default |
| 3 | SSE bị buffer trên một số server | Set `X-Accel-Buffering: no` + tắt `zlib.output_compression` |
| 4 | EventSource error event có nghĩa khác | Dùng `event: error_event` thay vì `event: error` |
| 5 | Gemini trả `modelVersion` khác model request | Tính giá theo `modelVersion`, không phải tên model request |
| 6 | `last_llm_models = ''` vi phạm CHECK json_valid | Lưu `NULL` khi trống |
| 7 | `char_accuracy_rate` để tính score | So sánh từng chữ số, pad leading zeros để bằng độ dài |
| 8 | Cột mới trong DB không được save | Thêm vào `$allowed` trong `MeterReadingLog::create()` |
| 9 | Thinking tokens (Gemini 2.5+) | Tính vào output cost: `(output_tokens + thinking_tokens)` |
| 10 | Tỷ giá hardcode | 26,380 VND/USD trong `Gemini.php::TY_GIA_USD_VND` |

---

## 14. Environment Variables

| Key | Bắt buộc | Mô tả |
|------|------|------|
| `DB_HOST` | ✅ | IP database (dùng `127.0.0.1`) |
| `DB_PORT` | ✅ | Port MySQL (thường `3306`) |
| `DB_NAME` | ✅ | Tên database (`capnuoccangio`) |
| `DB_USER` | ✅ | Username MySQL |
| `DB_PASS` | ✅ | Password MySQL |
| `GOOGLE_API_KEY` | ✅ | Google AI API key (để gọi Gemini) |
| `APP_DEBUG` | ❌ | `true` để hiển thị lỗi chi tiết |

---

## 15. Công việc Đang Dở / TODO tiếp theo

- [x] ~~**Tích hợp `WaterMeterRationalityChecker` vào `AiReadController`**~~ — Hoàn thành. Class đã chuyển lên `app/Services/`.
- [x] ~~**Lưu trữ hình ảnh nội bộ & UI Log AI**~~ — Hoàn thành. Lưu `img_dhn` an toàn và trang `/logs` giúp tra cứu.
- [ ] **Prompt per meter type**: khi AI read, nên lấy `last_prompt_txt` từ `loai_dhn` tương ứng thay vì dùng prompt cứng
- [ ] **Retry logic**: khi API fail, thử lại với model khác từ `last_llm_models` list
- [ ] **Báo cáo thống kê**: trang `/report` tổng hợp accuracy theo model/tháng/loại đồng hồ
- [ ] **Tỷ giá dynamic**: cập nhật tỷ giá USD/VND từ API thay vì hardcode

---

*Cập nhật lần cuối: 2026-02-27 | Tài liệu này tổng hợp toàn bộ codebase tại thời điểm review.*
