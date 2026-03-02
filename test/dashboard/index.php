<?php
/**
 * ============================================================
 * Dashboard: Đánh giá AI LLM đọc chỉ số đồng hồ nước
 * Kết nối MariaDB / MySQL — tn_meter_reading_log
 * ============================================================
 */

// ── LOAD .ENV ───────────────────────────────────────────────
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
  foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    [$key, $val] = array_map('trim', explode('=', $line, 2));
    $val = trim($val, '"\' ');
    $_ENV[$key] = $val;
    putenv("$key=$val");
  }
}

// ── CẤU HÌNH DATABASE ────────────────────────────────────────
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', (int) ($_ENV['DB_PORT'] ?? 3306));
define('DB_NAME', $_ENV['DB_NAME'] ?? 'capnuoccangio');
define('DB_USER', $_ENV['DB_USER'] ?? 'capnuoc_cangio_user');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHAR', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// ── HÀM KẾT NỐI ─────────────────────────────────────────────
function getDB(): PDO
{
  static $pdo = null;
  if ($pdo === null) {
    $dsn = sprintf(
      'mysql:host=%s;port=%d;dbname=%s;charset=%s',
      DB_HOST,
      DB_PORT,
      DB_NAME,
      DB_CHAR
    );
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);
  }
  return $pdo;
}

// ── LẤY FILTER TỪ REQUEST ────────────────────────────────────
$filterModel = isset($_GET['model']) && is_array($_GET['model']) ? $_GET['model'] : [];
$filterPrompt = isset($_GET['prompt']) && is_array($_GET['prompt']) ? $_GET['prompt'] : [];
$filterImgType = isset($_GET['imgtype']) && is_array($_GET['imgtype']) ? $_GET['imgtype'] : [];
$filterDateFrom = $_GET['from'] ?? '2025-01-01';
$filterDateTo = $_GET['to'] ?? date('Y-12-31');


// Build WHERE clause
function buildWhere(array &$params): string
{
  global $filterModel, $filterPrompt, $filterImgType, $filterDateFrom, $filterDateTo;
  $where = ['1=1'];
  
  // Model filter (multi)
  if (!empty($filterModel)) {
    $modelPlaceholders = [];
    foreach ($filterModel as $i => $v) {
      $key = ":model_$i";
      $modelPlaceholders[] = $key;
      $params[$key] = $v;
    }
    $where[] = 'model_name IN (' . implode(',', $modelPlaceholders) . ')';
  }
  
  // Prompt filter (multi)
  if (!empty($filterPrompt)) {
    $promptPlaceholders = [];
    foreach ($filterPrompt as $i => $v) {
      $key = ":prompt_$i";
      $promptPlaceholders[] = $key;
      $params[$key] = $v;
    }
    $where[] = 'prompt_version IN (' . implode(',', $promptPlaceholders) . ')';
  }
  
  // Image type filter (multi)
  if (!empty($filterImgType)) {
    $imgPlaceholders = [];
    foreach ($filterImgType as $i => $v) {
      $key = ":imgtype_$i";
      $imgPlaceholders[] = $key;
      $params[$key] = $v;
    }
    $where[] = 'image_type IN (' . implode(',', $imgPlaceholders) . ')';
  }
  
  $where[] = 'DATE(created_at) BETWEEN :from AND :to';
  $params[':from'] = $filterDateFrom;
  $params[':to'] = $filterDateTo;
  return implode(' AND ', $where);
}

// ── QUERY HELPERS ────────────────────────────────────────────
function queryOne(string $sql, array $params = []): array
{
  $st = getDB()->prepare($sql);
  $st->execute($params);
  return $st->fetch() ?: [];
}
function queryAll(string $sql, array $params = []): array
{
  $st = getDB()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll();
}

// ── HELPER FUNCTIONS ──────────────────────────────────────────
function nf(mixed $num, int $decimals = 0): string {
  return number_format((float) $num, $decimals, '.', ',');
}
function hs(?string $str): string {
  return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// ════════════════════════════════════════════════════════════
// SECTION 1: KPI TỔNG QUAN
// ════════════════════════════════════════════════════════════
$p = [];
$w = buildWhere($p);

$kpi = queryOne("
    SELECT
        COUNT(*)                                            AS tong_anh,
        SUM(is_exact_match)                                 AS exact_match,
        ROUND(100.0 * SUM(is_exact_match) / COUNT(*), 1)   AS ty_le_chinh_xac,
        ROUND(AVG(score_poc), 1)                            AS avg_score_poc,
        ROUND(AVG(score_thuc_te), 1)                        AS avg_score_tt,
        SUM(CASE WHEN muc_do_thuc_te IN ('CAN_REVIEW') THEN 1 ELSE 0 END)  AS can_review,
        SUM(CASE WHEN loai_sai_so IN ('CHI_SO_AM','DOC_SAI_CHU_SO') AND sai_so_tuyet_doi > 50 THEN 1 ELSE 0 END) AS loi_nghiem_trong,
        ROUND(SUM(chi_phi_vnd), 0)                          AS tong_chi_phi_vnd,
        ROUND(AVG(chi_phi_vnd), 0)                          AS avg_chi_phi_vnd,
        ROUND(AVG(thoi_gian_xu_ly), 0)                      AS avg_tg_xu_ly,
        COUNT(DISTINCT model_name)                           AS so_model,
        COUNT(DISTINCT prompt_version)                       AS so_prompt
    FROM tn_meter_reading_log
    WHERE {$w}
", $p);

// ════════════════════════════════════════════════════════════
// SECTION 2: DANH SÁCH MODEL & PROMPT (cho filter)
// ════════════════════════════════════════════════════════════
$modelList = queryAll("SELECT DISTINCT model_name   FROM tn_meter_reading_log ORDER BY model_name");
$promptList = queryAll("SELECT DISTINCT prompt_version FROM tn_meter_reading_log ORDER BY prompt_version");

// ════════════════════════════════════════════════════════════
// SECTION 3: SCORE POC — Phân loại mức độ
// ════════════════════════════════════════════════════════════
$p2 = [];
$w2 = buildWhere($p2);

$scorePocDist = queryOne("
    SELECT
        ROUND(AVG(score_so_sat), 1)     AS avg_so_sat,
        ROUND(AVG(score_ky_tu_poc), 1)  AS avg_ky_tu,
        ROUND(AVG(score_poc), 1)        AS avg_poc,
        SUM(CASE WHEN muc_do_poc = 'AI_CHINH_XAC_CAO'     THEN 1 ELSE 0 END) AS cnt_cao,
        SUM(CASE WHEN muc_do_poc = 'AI_CHAP_NHAN_DUOC'    THEN 1 ELSE 0 END) AS cnt_chap,
        SUM(CASE WHEN muc_do_poc = 'AI_CAN_CANH_BAO'      THEN 1 ELSE 0 END) AS cnt_canh,
        SUM(CASE WHEN muc_do_poc = 'AI_KHONG_DAT_YEU_CAU' THEN 1 ELSE 0 END) AS cnt_fail,
        COUNT(*) AS tong
    FROM tn_meter_reading_log WHERE {$w2}
", $p2);

// ════════════════════════════════════════════════════════════
// SECTION 4: SCORE THỰC TẾ — Phân loại quyết định
// ════════════════════════════════════════════════════════════
$p3 = [];
$w3 = buildWhere($p3);

$scoreTTDist = queryOne("
    SELECT
        ROUND(AVG(score_hop_ly), 1)     AS avg_hop_ly,
        ROUND(AVG(score_do_lech_tb), 1) AS avg_do_lech,
        ROUND(AVG(score_doc_duoc), 1)   AS avg_doc_duoc,
        ROUND(AVG(score_thuc_te), 1)    AS avg_tt,
        SUM(CASE WHEN muc_do_thuc_te = 'TU_DONG_CHAP_NHAN'     THEN 1 ELSE 0 END) AS cnt_auto,
        SUM(CASE WHEN muc_do_thuc_te = 'CHAP_NHAN_CO_THEO_DOI' THEN 1 ELSE 0 END) AS cnt_watch,
        SUM(CASE WHEN muc_do_thuc_te = 'CAN_REVIEW'            THEN 1 ELSE 0 END) AS cnt_review,
        SUM(CASE WHEN muc_do_thuc_te = 'TU_CHOI'               THEN 1 ELSE 0 END) AS cnt_reject,
        COUNT(*) AS tong
    FROM tn_meter_reading_log WHERE {$w3}
", $p3);

// ════════════════════════════════════════════════════════════
// SECTION 5: SO SÁNH MODEL
// ════════════════════════════════════════════════════════════
$p4 = [];
$w4 = buildWhere($p4);

$modelCompare = queryAll("
    SELECT
        model_name,
        prompt_version,
        COUNT(*)                                            AS tong_anh,
        SUM(is_exact_match)                                 AS exact_count,
        ROUND(100.0 * SUM(is_exact_match) / COUNT(*), 1)   AS ty_le_exact,
        ROUND(AVG(score_poc), 1)                            AS avg_poc,
        ROUND(AVG(score_thuc_te), 1)                        AS avg_tt,
        ROUND(AVG(chi_phi_vnd), 0)                          AS avg_chi_phi,
        ROUND(SUM(chi_phi_vnd), 0)                          AS tong_chi_phi,
        ROUND(AVG(thoi_gian_xu_ly), 0)                      AS avg_tg,
        ROUND(AVG(char_accuracy_rate), 2)                   AS avg_char_acc,
        SUM(CASE WHEN trang_thai_api != 'thanh_cong' THEN 1 ELSE 0 END) AS loi_api,
        SUM(CASE WHEN co_ky_tu_x = 1 THEN 1 ELSE 0 END)    AS co_x
    FROM tn_meter_reading_log
    WHERE {$w4}
    GROUP BY model_name, prompt_version
    ORDER BY avg_poc DESC
", $p4);

// ════════════════════════════════════════════════════════════
// SECTION 6: PHÂN LOẠI LỖI
// ════════════════════════════════════════════════════════════
$p5 = [];
$w5 = buildWhere($p5);

$errorDist = queryAll("
    SELECT
        COALESCE(loai_sai_so, 'CHUA_PHAN_LOAI') AS loai,
        COUNT(*) AS so_luong,
        ROUND(100.0 * COUNT(*) / SUM(COUNT(*)) OVER(), 1) AS pct
    FROM tn_meter_reading_log
    WHERE {$w5}
    GROUP BY loai_sai_so
    ORDER BY so_luong DESC
", $p5);

// ════════════════════════════════════════════════════════════
// SECTION 7: CHẤT LƯỢNG ẢNH vs ĐỘ CHÍNH XÁC
// ════════════════════════════════════════════════════════════
$p6 = [];
$w6 = buildWhere($p6);

$imgQuality = queryAll("
    SELECT
        COALESCE(image_type, 'chua_review') AS loai_anh,
        COUNT(*)                                          AS so_luong,
        ROUND(100.0 * SUM(is_exact_match) / COUNT(*), 1) AS ty_le_chinh_xac,
        ROUND(AVG(score_poc), 1)                          AS avg_poc,
        ROUND(AVG(score_thuc_te), 1)                      AS avg_tt
    FROM tn_meter_reading_log
    WHERE {$w6}
    GROUP BY image_type
    ORDER BY FIELD(image_type,'hinh_ro','hinh_mo','hinh_khong_day_du','hinh_khong_doc_duoc')
", $p6);

// ════════════════════════════════════════════════════════════
// SECTION 8: ROI / CHI PHÍ
// ════════════════════════════════════════════════════════════
$p7 = [];
$w7 = buildWhere($p7);

$roi = queryOne("
    SELECT
        ROUND(SUM(chi_phi_vnd), 0)                          AS tong_chi_phi,
        ROUND(AVG(chi_phi_vnd), 0)                          AS avg_chi_phi,
        SUM(prompt_tokens)                                   AS tong_prompt_tokens,
        SUM(output_tokens)                                   AS tong_output_tokens,
        ROUND(100.0 * SUM(CASE WHEN muc_do_thuc_te = 'TU_DONG_CHAP_NHAN' THEN 1 ELSE 0 END) / COUNT(*), 1) AS pct_tu_dong,
        ROUND(100.0 * SUM(is_accept) / COUNT(*), 1)         AS pct_chap_nhan,
        ROUND(100.0 * SUM(is_accept_for_billing) / COUNT(*), 1) AS pct_billing
    FROM tn_meter_reading_log
    WHERE {$w7}
", $p7);

// ════════════════════════════════════════════════════════════
// SECTION 9: LOG GẦN NHẤT
// ════════════════════════════════════════════════════════════
$p8 = [];
$w8 = buildWhere($p8);

$recentLogs = queryAll("
    SELECT
        id, id_data, model_name, prompt_version,
        ai_chi_so, ai_chi_so_parse, human_chi_so,
        sai_so, sai_so_tuyet_doi, loai_sai_so,
        score_poc, muc_do_poc, score_thuc_te, muc_do_thuc_te,
        image_type, is_accept, is_accept_for_billing,
        chi_phi_vnd, thoi_gian_xu_ly, trang_thai_api,
        co_ky_tu_x, so_ky_tu_x,
        last_reviewed_at, linkHinhDongHo,
        created_at
    FROM tn_meter_reading_log
    WHERE {$w8}
    ORDER BY created_at DESC
    LIMIT 15
", $p8);

// ── JSON cho JS ──────────────────────────────────────────────
$jsonModelCompare = json_encode($modelCompare, JSON_UNESCAPED_UNICODE);
$jsonErrorDist = json_encode($errorDist, JSON_UNESCAPED_UNICODE);
$jsonImgQuality = json_encode($imgQuality, JSON_UNESCAPED_UNICODE);

// ── HELPERS HIỂN THỊ ─────────────────────────────────────────
function scoreClass(int $score): string
{
  if ($score >= 90)
    return 's-high';
  if ($score >= 60)
    return 's-mid';
  return 's-low';
}
function mucDoPocLabel(string $mucDo): string
{
  return match ($mucDo) {
    'AI_CHINH_XAC_CAO' => '✓ Chính xác cao',
    'AI_CHAP_NHAN_DUOC' => '~ Chấp nhận',
    'AI_CAN_CANH_BAO' => '⚠ Cảnh báo',
    'AI_KHONG_DAT_YEU_CAU' => '✗ Không đạt',
    default => $mucDo,
  };
}
function mucDoTTLabel(string $mucDo): string
{
  return match ($mucDo) {
    'TU_DONG_CHAP_NHAN' => '✅ Tự động',
    'CHAP_NHAN_CO_THEO_DOI' => '👁 Theo dõi',
    'CAN_REVIEW' => '🔍 Review',
    'TU_CHOI' => '❌ Từ chối',
    default => $mucDo,
  };
}
function imgTypeLabel(string $t): string
{
  return match ($t) {
    'hinh_ro' => '🟢 Rõ',
    'hinh_mo' => '🟡 Mờ',
    'hinh_khong_day_du' => '🟠 Không đủ',
    'hinh_khong_doc_duoc' => '🔴 Không đọc được',
    default => '⬜ Chưa review',
  };
}
function loaiSaiSoTag(string $loai): string
{
  return match ($loai) {
    'CHINH_XAC' => '<span class="tag tag-ok">CHÍNH XÁC</span>',
    'SAI_NHO' => '<span class="tag tag-info">SAI NHỎ</span>',
    'MAT_CHU_SO_DAU' => '<span class="tag tag-warn">MẤT SỐ ĐẦU</span>',
    'DOC_SAI_CHU_SO' => '<span class="tag tag-warn">SAI CHỮ SỐ</span>',
    'CO_KY_TU_X' => '<span class="tag tag-err">CÓ X</span>',
    'CHI_SO_AM' => '<span class="tag tag-err">CHỈ SỐ ÂM</span>',
    'KHONG_DOC_DUOC' => '<span class="tag tag-err">KHÔNG ĐỌC</span>',
    default => '<span class="tag">' . hs($loai) . '</span>',
  };
}
function apiStatusTag(string $s): string
{
  return match ($s) {
    'thanh_cong' => '<span class="tag tag-ok">OK</span>',
    'timeout' => '<span class="tag tag-err">TIMEOUT</span>',
    'loi_api' => '<span class="tag tag-err">LỖI API</span>',
    'loi_parse' => '<span class="tag tag-warn">LỖI PARSE</span>',
    'rate_limit' => '<span class="tag tag-warn">RATE LIMIT</span>',
    default => '<span class="tag">' . $s . '</span>',
  };
}

// Tính tỷ lệ % an toàn
function pct(int|float $a, int|float $b): float
{
  return $b > 0 ? round($a / $b * 100, 1) : 0;
}

$tong = (int) ($kpi['tong_anh'] ?? 0);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Đánh Giá AI Đọc Đồng Hồ Nước</title>
  <link
    href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap"
    rel="stylesheet">
  <style>
    :root {
      --bg: #0b0f14;
      --surface: #111820;
      --border: #1e2d3d;
      --border2: #243447;
      --text: #c8d8e8;
      --muted: #4a6478;
      --accent: #00e5ff;
      --accent2: #00b4d8;
      --green: #00e676;
      --yellow: #ffd740;
      --red: #ff5252;
      --orange: #ff9100;
      --mono: 'IBM Plex Mono', monospace;
      --sans: 'IBM Plex Sans Thai', sans-serif;
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: var(--sans);
      font-size: 14px;
      line-height: 1.6;
    }

    /* HEADER */
    .header {
      border-bottom: 1px solid var(--border);
      padding: 16px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: var(--surface);
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .header-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .logo-icon {
      width: 34px;
      height: 34px;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 17px;
    }

    .header-title {
      font-size: 15px;
      font-weight: 600;
    }

    .header-sub {
      font-size: 11px;
      color: var(--muted);
      font-family: var(--mono);
    }

    .live-badge {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      font-family: var(--mono);
      color: var(--green);
    }

    .live-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--green);
      box-shadow: 0 0 6px var(--green);
      animation: pulse 2s infinite;
    }

    @keyframes pulse {

      0%,
      100% {
        opacity: 1
      }

      50% {
        opacity: .4
      }
    }

    /* LAYOUT */
    .main {
      padding: 24px 28px;
      max-width: 1440px;
      margin: 0 auto;
    }

    .row {
      display: grid;
      gap: 14px;
      margin-bottom: 24px;
    }

    .row-2 {
      grid-template-columns: 1fr 1fr;
    }

    .row-3 {
      grid-template-columns: 2fr 1fr;
    }

    @media(max-width:900px) {

      .row-2,
      .row-3 {
        grid-template-columns: 1fr;
      }
    }

    /* FILTER */
    .filter-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 24px;
      padding: 14px 18px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
    }

    .filter-label {
      font-size: 11px;
      color: var(--muted);
      font-family: var(--mono);
      text-transform: uppercase;
      letter-spacing: .08em;
    }

    .filter-select,
    .filter-input {
      background: var(--bg);
      border: 1px solid var(--border);
      color: var(--text);
      font-family: var(--mono);
      font-size: 12px;
      padding: 6px 10px;
      border-radius: 6px;
      outline: none;
      cursor: pointer;
    }

    .filter-select:hover,
    .filter-input:hover {
      border-color: var(--accent2);
    }

    /* Multi-select checkbox filters */
    .filter-group {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      align-items: center;
    }

    .filter-checkbox {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      font-family: var(--mono);
      cursor: pointer;
      padding: 3px 8px;
      border-radius: 4px;
      background: var(--bg);
      border: 1px solid var(--border);
      color: var(--text);
      transition: all .15s;
    }

    .filter-checkbox:hover {
      border-color: var(--accent2);
    }

    .filter-checkbox input {
      accent-color: var(--accent);
      cursor: pointer;
    }

    .filter-checkbox.active {
      background: rgba(0, 229, 255, .1);
      border-color: var(--accent);
    }

    .filter-divider {
      width: 1px;
      height: 18px;
      background: var(--border);
    }

    .btn {
      padding: 6px 14px;
      border-radius: 6px;
      font-size: 12px;
      font-family: var(--mono);
      font-weight: 600;
      cursor: pointer;
      border: none;
      transition: all .2s;
    }

    .btn-primary {
      background: var(--accent);
      color: var(--bg);
    }

    .btn-primary:hover {
      background: #33eaff;
    }

    .btn-ghost {
      background: transparent;
      color: var(--muted);
      border: 1px solid var(--border);
    }

    .btn-ghost:hover {
      color: var(--text);
    }

    /* SECTION TITLE */
    .section-title {
      font-size: 11px;
      font-family: var(--mono);
      text-transform: uppercase;
      letter-spacing: .12em;
      color: var(--muted);
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .section-title::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    /* KPI */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 12px;
      margin-bottom: 24px;
    }

    @media(max-width:1100px) {
      .kpi-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    .kpi-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 16px 18px;
      position: relative;
      overflow: hidden;
    }

    .kpi-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
    }

    .kpi-card.c-accent::before {
      background: var(--accent);
    }

    .kpi-card.c-green::before {
      background: var(--green);
    }

    .kpi-card.c-yellow::before {
      background: var(--yellow);
    }

    .kpi-card.c-orange::before {
      background: var(--orange);
    }

    .kpi-card.c-red::before {
      background: var(--red);
    }

    .kpi-label {
      font-size: 10px;
      color: var(--muted);
      font-family: var(--mono);
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: .05em;
    }

    .kpi-value {
      font-size: 30px;
      font-weight: 600;
      font-family: var(--mono);
      line-height: 1;
    }

    .kpi-card.c-accent .kpi-value {
      color: var(--accent);
    }

    .kpi-card.c-green .kpi-value {
      color: var(--green);
    }

    .kpi-card.c-yellow .kpi-value {
      color: var(--yellow);
    }

    .kpi-card.c-orange .kpi-value {
      color: var(--orange);
    }

    .kpi-card.c-red .kpi-value {
      color: var(--red);
    }

    .kpi-meta {
      font-size: 11px;
      color: var(--muted);
      margin-top: 6px;
    }

    /* CARD */
    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 18px 20px;
    }

    .card-title {
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 7px;
    }

    /* GAUGE */
    .gauge-row {
      display: flex;
      align-items: flex-start;
      gap: 24px;
    }

    .gauge-wrap {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 5px;
      min-width: 120px;
    }

    .gauge-ring {
      position: relative;
      width: 110px;
      height: 110px;
    }

    .gauge-ring svg {
      width: 110px;
      height: 110px;
      transform: rotate(-90deg);
    }

    .gauge-ring circle {
      fill: none;
      stroke-width: 9;
      stroke-linecap: round;
    }

    .gauge-ring .track {
      stroke: var(--border);
    }

    .gauge-ring .fill {
      transition: stroke-dashoffset 1.2s cubic-bezier(.4, 0, .2, 1);
    }

    .gauge-center {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .gauge-score {
      font-size: 22px;
      font-weight: 600;
      font-family: var(--mono);
    }

    .gauge-max {
      font-size: 10px;
      color: var(--muted);
      font-family: var(--mono);
    }

    .gauge-label {
      font-size: 11px;
      color: var(--muted);
      text-align: center;
    }

    .gauge-level {
      font-size: 10px;
      font-weight: 600;
      text-align: center;
      font-family: var(--mono);
    }

    /* BARS */
    .bar-list {
      display: flex;
      flex-direction: column;
      gap: 9px;
      flex: 1;
    }

    .bar-item-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 4px;
      font-size: 12px;
    }

    .bar-track {
      height: 6px;
      background: var(--border);
      border-radius: 3px;
      overflow: hidden;
    }

    .bar-fill {
      height: 100%;
      border-radius: 3px;
      transition: width 1s cubic-bezier(.4, 0, .2, 1);
    }

    .sub-bar-list {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    /* DECISION GRID */
    .decision-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
    }

    @media(max-width:700px) {
      .decision-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    .decision-card {
      border-radius: 8px;
      padding: 14px;
      text-align: center;
      border: 1px solid transparent;
    }

    .d-auto {
      background: rgba(0, 230, 118, .07);
      border-color: rgba(0, 230, 118, .2);
    }

    .d-watch {
      background: rgba(0, 229, 255, .07);
      border-color: rgba(0, 229, 255, .2);
    }

    .d-review {
      background: rgba(255, 215, 64, .07);
      border-color: rgba(255, 215, 64, .2);
    }

    .d-reject {
      background: rgba(255, 82, 82, .07);
      border-color: rgba(255, 82, 82, .2);
    }

    .decision-icon {
      font-size: 20px;
      margin-bottom: 4px;
    }

    .decision-num {
      font-size: 26px;
      font-weight: 600;
      font-family: var(--mono);
    }

    .d-auto .decision-num {
      color: var(--green);
    }

    .d-watch .decision-num {
      color: var(--accent);
    }

    .d-review .decision-num {
      color: var(--yellow);
    }

    .d-reject .decision-num {
      color: var(--red);
    }

    .decision-pct {
      font-size: 11px;
      color: var(--muted);
      font-family: var(--mono);
    }

    .decision-label {
      font-size: 10px;
      color: var(--muted);
      margin-top: 3px;
    }

    /* ERROR GRID */
    .error-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
    }

    @media(max-width:700px) {
      .error-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    .error-card {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 12px;
      text-align: center;
    }

    .error-count {
      font-size: 24px;
      font-weight: 600;
      font-family: var(--mono);
    }

    .error-name {
      font-size: 10px;
      color: var(--muted);
      margin-top: 3px;
      line-height: 1.4;
    }

    .error-pct {
      font-size: 11px;
      font-family: var(--mono);
      margin-top: 4px;
    }

    /* MODEL TABLE */
    .mtable {
      width: 100%;
      border-collapse: collapse;
    }

    .mtable th {
      text-align: left;
      padding: 7px 10px;
      font-size: 10px;
      font-family: var(--mono);
      text-transform: uppercase;
      letter-spacing: .07em;
      color: var(--muted);
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }

    .mtable td {
      padding: 10px;
      font-size: 12px;
      border-bottom: 1px solid rgba(30, 45, 61, .7);
      vertical-align: middle;
    }

    .mtable tr:last-child td {
      border-bottom: none;
    }

    .mtable tr:hover td {
      background: rgba(255, 255, 255, .02);
    }

    .model-badge {
      display: inline-flex;
      align-items: center;
      background: rgba(0, 229, 255, .1);
      border: 1px solid rgba(0, 229, 255, .2);
      border-radius: 5px;
      padding: 2px 7px;
      font-size: 10px;
      font-family: var(--mono);
      color: var(--accent);
      white-space: nowrap;
    }

    .mini-bar-wrap {
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .mini-bar {
      flex: 1;
      height: 4px;
      background: var(--border);
      border-radius: 2px;
      overflow: hidden;
    }

    .mini-bar-fill {
      height: 100%;
      border-radius: 2px;
    }

    /* LOG TABLE */
    .ltable {
      width: 100%;
      border-collapse: collapse;
      min-width: 900px;
    }

    .ltable th {
      text-align: left;
      padding: 7px 8px;
      font-size: 10px;
      font-family: var(--mono);
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--muted);
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }

    .ltable td {
      padding: 8px;
      font-size: 11px;
      border-bottom: 1px solid rgba(30, 45, 61, .5);
      vertical-align: middle;
    }

    .ltable tr:last-child td {
      border-bottom: none;
    }

    .ltable tr:hover td {
      background: rgba(255, 255, 255, .02);
    }

    /* TAGS & PILLS */
    .tag {
      display: inline-block;
      padding: 1px 6px;
      border-radius: 4px;
      font-size: 10px;
      font-family: var(--mono);
      font-weight: 600;
    }

    .tag-ok {
      background: rgba(0, 230, 118, .12);
      color: var(--green);
    }

    .tag-warn {
      background: rgba(255, 215, 64, .12);
      color: var(--yellow);
    }

    .tag-err {
      background: rgba(255, 82, 82, .12);
      color: var(--red);
    }

    .tag-info {
      background: rgba(0, 229, 255, .12);
      color: var(--accent);
    }

    .score-pill {
      display: inline-block;
      padding: 2px 7px;
      border-radius: 10px;
      font-size: 11px;
      font-family: var(--mono);
      font-weight: 600;
    }

    .s-high {
      background: rgba(0, 230, 118, .15);
      color: var(--green);
    }

    .s-mid {
      background: rgba(255, 215, 64, .15);
      color: var(--yellow);
    }

    .s-low {
      background: rgba(255, 82, 82, .15);
      color: var(--red);
    }

    .mono {
      font-family: var(--mono);
    }

    /* IMG QUALITY */
    .imgq-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
      margin-bottom: 14px;
    }

    @media(max-width:700px) {
      .imgq-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    .imgq-card {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 12px;
      text-align: center;
    }

    .imgq-count {
      font-size: 22px;
      font-weight: 600;
      font-family: var(--mono);
    }

    .imgq-name {
      font-size: 10px;
      color: var(--muted);
      margin-top: 3px;
    }

    .imgq-acc {
      font-size: 12px;
      font-family: var(--mono);
      margin-top: 5px;
      font-weight: 600;
    }

    /* ROI */
    .roi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
    }

    @media(max-width:700px) {
      .roi-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    .roi-card {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 14px 16px;
    }

    .roi-label {
      font-size: 10px;
      color: var(--muted);
      font-family: var(--mono);
      margin-bottom: 6px;
      text-transform: uppercase;
    }

    .roi-value {
      font-size: 20px;
      font-weight: 600;
      font-family: var(--mono);
      color: var(--accent);
    }

    .roi-note {
      font-size: 10px;
      color: var(--muted);
      margin-top: 4px;
    }

    .scroll-wrap {
      overflow-x: auto;
    }

    .info-box {
      padding: 10px 12px;
      border-radius: 7px;
      font-size: 11px;
      line-height: 1.7;
      margin-top: 12px;
    }

    .info-box.green {
      background: rgba(0, 230, 118, .06);
      border: 1px solid rgba(0, 230, 118, .15);
    }

    .info-box.yellow {
      background: rgba(255, 215, 64, .06);
      border: 1px solid rgba(255, 215, 64, .15);
    }
  </style>
</head>

<body>

  <!-- ── HEADER ── -->
  <header class="header">
    <div class="header-left">
      <div class="logo-icon">💧</div>
      <div>
        <div class="header-title">AI Meter Reading — POC Dashboard</div>
        <div class="header-sub">tn_meter_reading_log · <?= DB_NAME ?> · <?= date('d/m/Y H:i') ?></div>
      </div>
    </div>
    <div class="live-badge">
      <div class="live-dot"></div>LIVE DATA
    </div>
  </header>

  <div class="main">

    <!-- ── FILTER BAR ── -->
    <form method="GET" style="margin-bottom:0">
      <div class="filter-bar">
        <span class="filter-label">Lọc</span>

        <span class="filter-label" style="margin-left:8px">Model</span>
        <div class="filter-group">
          <?php foreach ($modelList as $m): ?>
            <label class="filter-checkbox <?= in_array($m['model_name'], $filterModel) ? 'active' : '' ?>">
              <input type="checkbox" name="model[]" value="<?= hs($m['model_name']) ?>" <?= in_array($m['model_name'], $filterModel) ? 'checked' : '' ?>>
              <?= hs($m['model_name']) ?>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="filter-divider"></div>

        <span class="filter-label">Prompt</span>
        <div class="filter-group">
          <?php foreach ($promptList as $pv): ?>
            <label class="filter-checkbox <?= in_array($pv['prompt_version'], $filterPrompt) ? 'active' : '' ?>">
              <input type="checkbox" name="prompt[]" value="<?= hs($pv['prompt_version'] ?? '') ?>" <?= in_array($pv['prompt_version'], $filterPrompt) ? 'checked' : '' ?>>
              <?= hs($pv['prompt_version'] ?? '') ?>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="filter-divider"></div>

        <span class="filter-label">Ảnh</span>
        <div class="filter-group">
          <label class="filter-checkbox <?= in_array('hinh_ro', $filterImgType) ? 'active' : '' ?>">
            <input type="checkbox" name="imgtype[]" value="hinh_ro" <?= in_array('hinh_ro', $filterImgType) ? 'checked' : '' ?>>🟢 Rõ
          </label>
          <label class="filter-checkbox <?= in_array('hinh_mo', $filterImgType) ? 'active' : '' ?>">
            <input type="checkbox" name="imgtype[]" value="hinh_mo" <?= in_array('hinh_mo', $filterImgType) ? 'checked' : '' ?>>🟡 Mờ
          </label>
          <label class="filter-checkbox <?= in_array('hinh_khong_day_du', $filterImgType) ? 'active' : '' ?>">
            <input type="checkbox" name="imgtype[]" value="hinh_khong_day_du" <?= in_array('hinh_khong_day_du', $filterImgType) ? 'checked' : '' ?>>🟠 Thiếu
          </label>
          <label class="filter-checkbox <?= in_array('hinh_khong_doc_duoc', $filterImgType) ? 'active' : '' ?>">
            <input type="checkbox" name="imgtype[]" value="hinh_khong_doc_duoc" <?= in_array('hinh_khong_doc_duoc', $filterImgType) ? 'checked' : '' ?>>🔴 Không đọc
          </label>
        </div>

        <div class="filter-divider"></div>
        <input type="date" name="from" class="filter-input" value="<?= hs($filterDateFrom) ?>">
        <span style="color:var(--muted);font-size:12px">→</span>
        <input type="date" name="to" class="filter-input" value="<?= hs($filterDateTo) ?>">
        <div class="filter-divider"></div>

        <button type="submit" class="btn btn-primary">Áp dụng</button>
        <a href="?" class="btn btn-ghost" style="text-decoration:none">Reset</a>
      </div>
    </form>

    <!-- ── KPI ── -->
    <div class="section-title">Tổng quan</div>
    <div class="kpi-grid">
      <div class="kpi-card c-accent">
        <div class="kpi-label">Tổng ảnh thử nghiệm</div>
        <div class="kpi-value"><?= nf($kpi['tong_anh']) ?></div>
        <div class="kpi-meta"><?= $kpi['so_model'] ?> model · <?= $kpi['so_prompt'] ?> prompt version</div>
      </div>
      <div class="kpi-card c-green">
        <div class="kpi-label">Tỷ lệ chính xác (exact)</div>
        <div class="kpi-value"><?= $kpi['ty_le_chinh_xac'] ?><span style="font-size:16px">%</span></div>
        <div class="kpi-meta"><?= nf($kpi['exact_match']) ?> / <?= nf($tong) ?> ảnh đúng</div>
      </div>
      <div class="kpi-card c-yellow">
        <div class="kpi-label">TB Score POC</div>
        <div class="kpi-value"><?= $kpi['avg_score_poc'] ?></div>
        <div class="kpi-meta">TB Score Thực tế: <?= $kpi['avg_score_tt'] ?></div>
      </div>
      <div class="kpi-card c-orange">
        <div class="kpi-label">Cần review</div>
        <div class="kpi-value"><?= nf($kpi['can_review']) ?></div>
        <div class="kpi-meta"><?= pct($kpi['can_review'], $tong) ?>% tổng ảnh</div>
      </div>
      <div class="kpi-card c-red">
        <div class="kpi-label">Lỗi nghiêm trọng</div>
        <div class="kpi-value"><?= nf($kpi['loi_nghiem_trong']) ?></div>
        <div class="kpi-meta">sai_so > 50 · chỉ số âm</div>
      </div>
    </div>

    <!-- ── SCORE SECTION ── -->
    <div class="section-title">Phân tích Score</div>
    <div class="row row-2">

      <!-- SCORE POC -->
      <div class="card">
        <div class="card-title">🎯 Score POC — Giai đoạn 1 (có ground truth)</div>
        <div class="gauge-row">
          <div class="gauge-wrap">
            <div class="gauge-ring">
              <svg viewBox="0 0 110 110">
                <circle class="track" cx="55" cy="55" r="46" />
                <circle class="fill" id="gpoc" cx="55" cy="55" r="46" stroke="var(--green)" stroke-dasharray="289"
                  stroke-dashoffset="289" />
              </svg>
              <div class="gauge-center">
                <div class="gauge-score" style="color:var(--green)"><?= $scorePocDist['avg_poc'] ?? 0 ?></div>
                <div class="gauge-max">/100</div>
              </div>
            </div>
            <div class="gauge-label">TB Score POC</div>
            <?php
            $avgPoc = (float) ($scorePocDist['avg_poc'] ?? 0);
            $pocLevelColor = $avgPoc >= 90 ? 'var(--green)' : ($avgPoc >= 70 ? 'var(--yellow)' : ($avgPoc >= 50 ? 'var(--orange)' : 'var(--red)'));
            ?>
            <div class="gauge-level" style="color:<?= $pocLevelColor ?>">
              <?= $avgPoc >= 90 ? 'AI_CHINH_XAC_CAO' : ($avgPoc >= 70 ? 'AI_CHAP_NHAN_DUOC' : ($avgPoc >= 50 ? 'AI_CAN_CANH_BAO' : 'KHONG_DAT')) ?>
            </div>
          </div>
          <div style="flex:1">
            <div style="font-size:11px;color:var(--muted);font-family:var(--mono);margin-bottom:10px">PHÂN BỔ ĐIỂM TB
            </div>
            <div class="sub-bar-list" style="margin-bottom:16px">
              <div>
                <div class="bar-item-header">
                  <span>score_so_sat <span style="color:var(--muted)">(max 60)</span></span>
                  <span style="color:var(--green);font-family:var(--mono)"><?= $scorePocDist['avg_so_sat'] ?> /
                    60</span>
                </div>
                <div class="bar-track">
                  <div class="bar-fill"
                    style="width:<?= pct($scorePocDist['avg_so_sat'], 60) ?>%;background:var(--green)"></div>
                </div>
              </div>
              <div>
                <div class="bar-item-header">
                  <span>score_ky_tu_poc <span style="color:var(--muted)">(max 40)</span></span>
                  <span style="color:var(--accent);font-family:var(--mono)"><?= $scorePocDist['avg_ky_tu'] ?> /
                    40</span>
                </div>
                <div class="bar-track">
                  <div class="bar-fill"
                    style="width:<?= pct($scorePocDist['avg_ky_tu'], 40) ?>%;background:var(--accent)"></div>
                </div>
              </div>
            </div>
            <div style="font-size:11px;color:var(--muted);font-family:var(--mono);margin-bottom:8px">PHÂN LOẠI MỨC ĐỘ
            </div>
            <div class="bar-list">
              <?php
              $pocRows = [
                ['cnt_cao', 'AI_CHINH_XAC_CAO ≥90', 'var(--green)'],
                ['cnt_chap', 'AI_CHAP_NHAN_DUOC 70-89', 'var(--yellow)'],
                ['cnt_canh', 'AI_CAN_CANH_BAO 50-69', 'var(--orange)'],
                ['cnt_fail', 'KHONG_DAT_YEU_CAU <50', 'var(--red)'],
              ];
              foreach ($pocRows as [$key, $label, $color]):
                $cnt = (int) ($scorePocDist[$key] ?? 0);
                $p_ = pct($cnt, $tong);
                ?>
                <div>
                  <div class="bar-item-header">
                    <span style="color:<?= $color ?>"><?= $label ?></span>
                    <span style="font-family:var(--mono);color:<?= $color ?>"><?= $cnt ?> <span
                        style="color:var(--muted)"><?= $p_ ?>%</span></span>
                  </div>
                  <div class="bar-track">
                    <div class="bar-fill" style="width:<?= $p_ ?>%;background:<?= $color ?>"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- SCORE THỰC TẾ -->
      <div class="card">
        <div class="card-title">⚡ Score Thực tế — Giai đoạn 2 (không cần ground truth)</div>
        <div class="gauge-row">
          <div class="gauge-wrap">
            <div class="gauge-ring">
              <svg viewBox="0 0 110 110">
                <circle class="track" cx="55" cy="55" r="46" />
                <circle class="fill" id="gtt" cx="55" cy="55" r="46" stroke="var(--accent)" stroke-dasharray="289"
                  stroke-dashoffset="289" />
              </svg>
              <div class="gauge-center">
                <div class="gauge-score" style="color:var(--accent)"><?= $scoreTTDist['avg_tt'] ?? 0 ?></div>
                <div class="gauge-max">/100</div>
              </div>
            </div>
            <div class="gauge-label">TB Score Thực tế</div>
            <?php
            $avgTT = (float) ($scoreTTDist['avg_tt'] ?? 0);
            $ttLevelColor = $avgTT >= 80 ? 'var(--green)' : ($avgTT >= 60 ? 'var(--accent)' : ($avgTT >= 40 ? 'var(--yellow)' : 'var(--red)'));
            ?>
            <div class="gauge-level" style="color:<?= $ttLevelColor ?>">
              <?= $avgTT >= 80 ? 'TU_DONG_CHAP_NHAN' : ($avgTT >= 60 ? 'THEO_DOI' : ($avgTT >= 40 ? 'CAN_REVIEW' : 'TU_CHOI')) ?>
            </div>
          </div>
          <div style="flex:1">
            <div style="font-size:11px;color:var(--muted);font-family:var(--mono);margin-bottom:10px">PHÂN BỔ ĐIỂM TB
            </div>
            <div class="sub-bar-list" style="margin-bottom:14px">
              <?php
              $ttBars = [
                [$scoreTTDist['avg_hop_ly'], 'score_hop_ly (max 50)', 50, 'var(--green)'],
                [$scoreTTDist['avg_do_lech'], 'score_do_lech_tb (max 30)', 30, 'var(--yellow)'],
                [$scoreTTDist['avg_doc_duoc'], 'score_doc_duoc (max 20)', 20, 'var(--accent)'],
              ];
              foreach ($ttBars as [$val, $lbl, $max, $color]):
                ?>
                <div>
                  <div class="bar-item-header">
                    <span><?= $lbl ?></span>
                    <span style="color:<?= $color ?>;font-family:var(--mono)"><?= $val ?> / <?= $max ?></span>
                  </div>
                  <div class="bar-track">
                    <div class="bar-fill" style="width:<?= pct($val, $max) ?>%;background:<?= $color ?>"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div style="font-size:11px;color:var(--muted);font-family:var(--mono);margin-bottom:8px">QUYẾT ĐỊNH TỰ ĐỘNG
            </div>
            <div class="decision-grid">
              <div class="decision-card d-auto">
                <div class="decision-icon">✅</div>
                <div class="decision-num"><?= $scoreTTDist['cnt_auto'] ?></div>
                <div class="decision-pct"><?= pct($scoreTTDist['cnt_auto'], $tong) ?>%</div>
                <div class="decision-label">Tự động chấp nhận</div>
              </div>
              <div class="decision-card d-watch">
                <div class="decision-icon">👁</div>
                <div class="decision-num"><?= $scoreTTDist['cnt_watch'] ?></div>
                <div class="decision-pct"><?= pct($scoreTTDist['cnt_watch'], $tong) ?>%</div>
                <div class="decision-label">Chấp nhận theo dõi</div>
              </div>
              <div class="decision-card d-review">
                <div class="decision-icon">🔍</div>
                <div class="decision-num"><?= $scoreTTDist['cnt_review'] ?></div>
                <div class="decision-pct"><?= pct($scoreTTDist['cnt_review'], $tong) ?>%</div>
                <div class="decision-label">Cần review</div>
              </div>
              <div class="decision-card d-reject">
                <div class="decision-icon">❌</div>
                <div class="decision-num"><?= $scoreTTDist['cnt_reject'] ?></div>
                <div class="decision-pct"><?= pct($scoreTTDist['cnt_reject'], $tong) ?>%</div>
                <div class="decision-label">Từ chối / chụp lại</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── MODEL COMPARISON ── -->
    <div class="section-title">So sánh Model & Prompt</div>
    <div class="row row-3">
      <div class="card">
        <div class="card-title">🤖 Hiệu suất theo Model AI</div>
        <div class="scroll-wrap">
          <table class="mtable">
            <thead>
              <tr>
                <th>Model</th>
                <th>Prompt</th>
                <th>Ảnh</th>
                <th>Exact match</th>
                <th>Score POC</th>
                <th>Score TT</th>
                <th>Chi phí TB</th>
                <th>Tốc độ (ms)</th>
                <th>Lỗi API</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($modelCompare as $m):
                $color = $m['ty_le_exact'] >= 90 ? 'var(--green)' : ($m['ty_le_exact'] >= 75 ? 'var(--yellow)' : 'var(--red)');
                ?>
                <tr>
                  <td><span class="model-badge"><?= hs($m['model_name']) ?></span></td>
                  <td><span class="mono" style="color:var(--muted)"><?= $m['prompt_version'] ?></span></td>
                  <td class="mono"><?= nf($m['tong_anh']) ?></td>
                  <td>
                    <div class="mini-bar-wrap">
                      <div class="mini-bar">
                        <div class="mini-bar-fill" style="width:<?= $m['ty_le_exact'] ?>%;background:<?= $color ?>"></div>
                      </div>
                      <span class="mono" style="color:<?= $color ?>;width:36px"><?= $m['ty_le_exact'] ?>%</span>
                    </div>
                  </td>
                  <td><span class="score-pill <?= scoreClass((int) $m['avg_poc']) ?>"><?= $m['avg_poc'] ?></span></td>
                  <td><span class="score-pill <?= scoreClass((int) $m['avg_tt']) ?>"><?= $m['avg_tt'] ?></span></td>
                  <td class="mono"
                    style="color:<?= $m['avg_chi_phi'] < 50 ? 'var(--green)' : ($m['avg_chi_phi'] < 100 ? 'var(--yellow)' : 'var(--red)') ?>">
                    ₫<?= nf($m['avg_chi_phi']) ?>
                  </td>
                  <td class="mono"><?= nf($m['avg_tg']) ?></td>
                  <td class="mono" style="color:<?= $m['loi_api'] > 0 ? 'var(--red)' : 'var(--muted)' ?>">
                    <?= $m['loi_api'] ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ROI -->
      <div class="card">
        <div class="card-title">💰 Chi phí & ROI</div>
        <div class="roi-grid">
          <div class="roi-card">
            <div class="roi-label">Tổng chi phí API</div>
            <div class="roi-value" style="font-size:16px">₫<?= nf($roi['tong_chi_phi']) ?></div>
            <div class="roi-note"><?= nf($tong) ?> ảnh</div>
          </div>
          <div class="roi-card">
            <div class="roi-label">Chi phí / ảnh TB</div>
            <div class="roi-value" style="font-size:16px">₫<?= nf($roi['avg_chi_phi']) ?></div>
          </div>
          <div class="roi-card">
            <div class="roi-label">Tự động 100%</div>
            <div class="roi-value" style="color:var(--green);font-size:16px"><?= $roi['pct_tu_dong'] ?>%</div>
            <div class="roi-note">không cần review</div>
          </div>
          <div class="roi-card">
            <div class="roi-label">Chấp nhận billing</div>
            <div class="roi-value" style="color:var(--yellow);font-size:16px"><?= $roi['pct_billing'] ?>%</div>
            <div class="roi-note">dùng tính tiền được</div>
          </div>
        </div>
        <div class="info-box green" style="margin-top:12px">
          <strong style="color:var(--green)">📊 Nhận định:</strong><br>
          <?php
          $bestModel = $modelCompare[0] ?? null;
          if ($bestModel):
            echo "Model <strong style='color:var(--text)'>{$bestModel['model_name']} {$bestModel['prompt_version']}</strong>";
            echo " đạt exact match <strong>{$bestModel['ty_le_exact']}%</strong>";
            echo ", chi phí <strong>₫" . nf($bestModel['avg_chi_phi']) . "/ảnh</strong>.";
          endif;
          ?>
          Ước tính tiết kiệm <strong style="color:var(--green)"><?= $roi['pct_tu_dong'] ?>%</strong> công nhân viên.
        </div>
      </div>
    </div>

    <!-- ── ERROR & IMAGE QUALITY ── -->
    <div class="section-title">Phân tích lỗi & Chất lượng ảnh</div>
    <div class="row row-2">

      <div class="card">
        <div class="card-title">🔬 Phân loại lỗi (loai_sai_so)</div>
        <div class="error-grid">
          <?php
          $errorColors = [
            'CHINH_XAC' => ['var(--green)', '✓'],
            'SAI_NHO' => ['var(--accent)', '~'],
            'MAT_CHU_SO_DAU' => ['var(--yellow)', '↓'],
            'DOC_SAI_CHU_SO' => ['var(--orange)', '≠'],
            'CO_KY_TU_X' => ['var(--red)', 'X'],
            'CHI_SO_AM' => ['var(--red)', '−'],
            'KHONG_DOC_DUOC' => ['var(--muted)', '?'],
            'CHUA_PHAN_LOAI' => ['var(--muted)', '⬜'],
          ];
          foreach ($errorDist as $e):
            $loai = $e['loai'];
            [$c, $icon] = $errorColors[$loai] ?? ['var(--muted)', '?'];
            ?>
            <div class="error-card">
              <div class="error-count" style="color:<?= $c ?>"><?= nf($e['so_luong']) ?></div>
              <div class="error-name"><?= hs($loai) ?></div>
              <div class="error-pct" style="color:<?= $c ?>"><?= $e['pct'] ?>%</div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-title">📸 Tác động của chất lượng ảnh</div>
        <div class="imgq-grid">
          <?php
          $imgColors = [
            'hinh_ro' => ['🟢', 'var(--green)'],
            'hinh_mo' => ['🟡', 'var(--yellow)'],
            'hinh_khong_day_du' => ['🟠', 'var(--orange)'],
            'hinh_khong_doc_duoc' => ['🔴', 'var(--red)'],
            'chua_review' => ['⬜', 'var(--muted)'],
          ];
          foreach ($imgQuality as $iq):
            [$icon, $color] = $imgColors[$iq['loai_anh']] ?? ['⬜', 'var(--muted)'];
            ?>
            <div class="imgq-card">
              <div style="font-size:18px"><?= $icon ?></div>
              <div class="imgq-count"><?= $iq['so_luong'] ?></div>
              <div class="imgq-name"><?= hs($iq['loai_anh']) ?></div>
              <div class="imgq-acc" style="color:<?= $color ?>"><?= $iq['ty_le_chinh_xac'] ?>%</div>
            </div>
          <?php endforeach; ?>
        </div>
        <div style="font-size:11px;color:var(--muted);font-family:var(--mono);margin-bottom:8px">ĐỘ CHÍNH XÁC THEO LOẠI
          ẢNH</div>
        <div class="bar-list">
          <?php foreach ($imgQuality as $iq):
            [$icon, $color] = $imgColors[$iq['loai_anh']] ?? ['⬜', 'var(--muted)'];
            ?>
            <div>
              <div class="bar-item-header">
                <span><?= $icon ?>   <?= hs($iq['loai_anh']) ?></span>
                <span style="font-family:var(--mono);color:<?= $color ?>"><?= $iq['ty_le_chinh_xac'] ?>%</span>
              </div>
              <div class="bar-track">
                <div class="bar-fill" style="width:<?= $iq['ty_le_chinh_xac'] ?>%;background:<?= $color ?>"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="info-box yellow">
          💡 <strong style="color:var(--yellow)">Khuyến nghị:</strong>
          Đào tạo nhân viên chụp ảnh đúng góc &amp; đủ sáng có thể nâng độ chính xác thêm <strong>10–15%</strong>.
        </div>
      </div>
    </div>

    <!-- ── RECENT LOG ── -->
    <div class="section-title">Log gần nhất</div>
    <div class="card" style="margin-bottom:32px">
      <div class="card-title">📋 15 bản ghi mới nhất</div>
      <div class="scroll-wrap">
        <table class="ltable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Model</th>
              <th>Prompt</th>
              <th>AI đọc</th>
              <th>Nhân viên</th>
              <th>Sai số</th>
              <th>Loại lỗi</th>
              <th>Score POC</th>
              <th>Score TT</th>
              <th>Quyết định</th>
              <th>Loại ảnh</th>
              <th>API (ms)</th>
              <th>Chi phí</th>
              <th>API status</th>
              <th>Thời gian</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentLogs as $log): ?>
              <tr>
                <td class="mono" style="color:var(--muted)"><?= $log['id'] ?></td>
                <td><span class="model-badge"
                    style="font-size:9px"><?= hs(str_replace('gemini-2.5-', 'g2.5-', $log['model_name'])) ?></span>
                </td>
                <td class="mono" style="color:var(--muted)"><?= $log['prompt_version'] ?></td>
                <td class="mono">
                  <?= $log['ai_chi_so'] ?? '—' ?>
                  <?= $log['co_ky_tu_x'] ? ' <span style="color:var(--yellow);font-size:10px">⚠X</span>' : '' ?>
                </td>
                <td class="mono"><?= $log['human_chi_so'] ?? '—' ?></td>
                <td class="mono"
                  style="color:<?= $log['sai_so'] == 0 ? 'var(--green)' : ($log['sai_so'] > 0 ? 'var(--yellow)' : 'var(--red)') ?>">
                  <?= $log['sai_so'] !== null ? ($log['sai_so'] > 0 ? '+' : '') . $log['sai_so'] : '—' ?>
                </td>
                <td><?= loaiSaiSoTag($log['loai_sai_so'] ?? '') ?></td>
                <td><span class="score-pill <?= scoreClass((int) $log['score_poc']) ?>"><?= $log['score_poc'] ?></span>
                </td>
                <td><span
                    class="score-pill <?= scoreClass((int) $log['score_thuc_te']) ?>"><?= $log['score_thuc_te'] ?></span>
                </td>
                <td style="white-space:nowrap"><?= mucDoTTLabel($log['muc_do_thuc_te'] ?? '') ?></td>
                <td><?= imgTypeLabel($log['image_type'] ?? '') ?></td>
                <td class="mono"><?= nf($log['thoi_gian_xu_ly']) ?></td>
                <td class="mono" style="color:<?= $log['chi_phi_vnd'] < 50 ? 'var(--green)' : 'var(--yellow)' ?>">
                  ₫<?= nf($log['chi_phi_vnd'], 0) ?></td>
                <td><?= apiStatusTag($log['trang_thai_api'] ?? '') ?></td>
                <td class="mono" style="color:var(--muted);white-space:nowrap">
                  <?= date('d/m H:i', strtotime($log['created_at'])) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /main -->

  <script>
    // Animate gauges
    document.addEventListener('DOMContentLoaded', () => {
      animateGauge('gpoc', <?= (float) ($scorePocDist['avg_poc'] ?? 0) ?>);
      animateGauge('gtt', <?= (float) ($scoreTTDist['avg_tt'] ?? 0) ?>);
    });
    function animateGauge(id, target) {
      const el = document.getElementById(id);
      if (!el) return;
      const circ = 2 * Math.PI * 46;
      let cur = 0, step = target / 60;
      const t = setInterval(() => {
        cur = Math.min(cur + step, target);
        el.style.strokeDashoffset = circ - (cur / 100) * circ;
        if (cur >= target) clearInterval(t);
      }, 16);
    }
  </script>
</body>

</html>