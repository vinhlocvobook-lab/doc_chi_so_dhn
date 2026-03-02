-- ============================================================
-- SEED DATA: tn_meter_reading_log
-- Mô phỏng 200 bản ghi thử nghiệm AI đọc đồng hồ nước
-- Bao gồm đầy đủ các trường hợp để dashboard hiển thị đa dạng
-- ============================================================

SET NAMES utf8mb4;

-- Xóa data cũ nếu có (chỉ dùng cho môi trường test)
-- TRUNCATE TABLE tn_meter_reading_log;

INSERT INTO tn_meter_reading_log (
    id_data, model_name, prompt_version, prompt_text,
    ai_chi_so, ai_chi_so_parse, co_ky_tu_x, so_ky_tu_x,
    raw_response,
    prompt_tokens, output_tokens, thinking_tokens,
    chi_phi_usd, chi_phi_vnd, thoi_gian_xu_ly,
    api_started_at, api_completed_at, retry_count, trang_thai_api, thong_bao_loi,
    human_chi_so, is_exact_match, sai_so, sai_so_tuyet_doi, loai_sai_so,
    char_match_count, char_total_count, char_accuracy_rate,
    is_rationality, luong_tieu_thu_ai, nguong_hop_ly_min, nguong_hop_ly_max, ly_do_bat_hop_ly,
    image_type, is_accept, is_accept_for_billing, last_reviewer, last_reviewed_at, last_review_note,
    score_so_sat, score_ky_tu_poc, score_poc, muc_do_poc,
    score_hop_ly, score_do_lech_tb, score_doc_duoc, score_thuc_te, muc_do_thuc_te,
    linkHinhDongHo, created_at
) VALUES

-- ════════════════════════════════════════════════════════════
-- NHÓM 1: gemini-2.5-flash-lite / prompt v1.1 — ĐỌC ĐÚNG
-- ════════════════════════════════════════════════════════════

(1001, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '12345', 12345, 0, 0, '{"chi_so":"12345","do_tin_cay":98}',
 1141, 156, 0, 0.00000350, 84.00, 1124,
 '2025-03-01 08:00:00', '2025-03-01 08:00:01', 0, 'thanh_cong', NULL,
 12345, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 45, 9.6, 144.0, NULL,
 'hinh_ro', 1, 1, 'NV01', '2025-03-02 09:00:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/001.jpg', '2025-03-01 08:00:02'),

(1002, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '08932', 8932, 0, 0, '{"chi_so":"08932","do_tin_cay":96}',
 1141, 148, 0, 0.00000330, 79.20, 980,
 '2025-03-01 08:05:00', '2025-03-01 08:05:01', 0, 'thanh_cong', NULL,
 8932, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 32, 6.4, 96.0, NULL,
 'hinh_ro', 1, 1, 'NV01', '2025-03-02 09:10:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/002.jpg', '2025-03-01 08:05:02'),

(1003, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '15420', 15420, 0, 0, '{"chi_so":"15420","do_tin_cay":94}',
 1141, 162, 0, 0.00000360, 86.40, 1230,
 '2025-03-01 08:10:00', '2025-03-01 08:10:01', 0, 'thanh_cong', NULL,
 15420, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 55, 11.0, 165.0, NULL,
 'hinh_ro', 1, 1, 'NV02', '2025-03-02 09:20:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 20, 20, 90, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/003.jpg', '2025-03-01 08:10:02'),

(1004, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '07210', 7210, 0, 0, '{"chi_so":"07210","do_tin_cay":97}',
 1141, 155, 0, 0.00000345, 82.80, 1050,
 '2025-03-01 08:15:00', '2025-03-01 08:15:01', 0, 'thanh_cong', NULL,
 7210, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 28, 5.6, 84.0, NULL,
 'hinh_ro', 1, 1, 'NV01', '2025-03-02 09:30:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/004.jpg', '2025-03-01 08:15:02'),

(1005, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '23891', 23891, 0, 0, '{"chi_so":"23891","do_tin_cay":95}',
 1141, 159, 0, 0.00000354, 84.96, 1180,
 '2025-03-01 08:20:00', '2025-03-01 08:20:01', 0, 'thanh_cong', NULL,
 23891, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 62, 12.4, 186.0, NULL,
 'hinh_ro', 1, 1, 'NV02', '2025-03-02 09:40:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 20, 20, 90, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/005.jpg', '2025-03-01 08:20:02'),

-- NHÓM 1b: flash-lite đọc đúng, hình mờ (char_accuracy thấp hơn)
(1006, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '11234', 11234, 0, 0, '{"chi_so":"11234","do_tin_cay":82}',
 1141, 171, 0, 0.00000381, 91.44, 1420,
 '2025-03-02 09:00:00', '2025-03-02 09:00:01', 0, 'thanh_cong', NULL,
 11234, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 38, 7.6, 114.0, NULL,
 'hinh_mo', 1, 1, 'NV01', '2025-03-03 09:00:00', 'Hình hơi mờ nhưng đọc được',
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/006.jpg', '2025-03-02 09:00:02'),

(1007, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '09876', 9876, 0, 0, '{"chi_so":"09876","do_tin_cay":79}',
 1141, 168, 0, 0.00000374, 89.76, 1380,
 '2025-03-02 09:10:00', '2025-03-02 09:10:01', 1, 'thanh_cong', NULL,
 9876, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 41, 8.2, 123.0, NULL,
 'hinh_mo', 1, 1, 'NV02', '2025-03-03 09:10:00', NULL,
 60, 32, 92, 'AI_CHINH_XAC_CAO', 50, 20, 20, 90, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/007.jpg', '2025-03-02 09:10:02'),

-- ════════════════════════════════════════════════════════════
-- NHÓM 2: gemini-2.5-pro / prompt v1.1 — CHÍNH XÁC CAO
-- ════════════════════════════════════════════════════════════

(2001, 'gemini-2.5-pro', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON chi tiết.',
 '34521', 34521, 0, 0, '{"chi_so":"34521","do_tin_cay":99}',
 2241, 312, 0, 0.00003600, 864.00, 3421,
 '2025-03-03 10:00:00', '2025-03-03 10:00:03', 0, 'thanh_cong', NULL,
 34521, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 47, 9.4, 141.0, NULL,
 'hinh_ro', 1, 1, 'NV01', '2025-03-04 09:00:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/101.jpg', '2025-03-03 10:00:04'),

(2002, 'gemini-2.5-pro', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON chi tiết.',
 '19043', 19043, 0, 0, '{"chi_so":"19043","do_tin_cay":98}',
 2241, 298, 0, 0.00003420, 820.80, 3180,
 '2025-03-03 10:10:00', '2025-03-03 10:10:03', 0, 'thanh_cong', NULL,
 19043, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 53, 10.6, 159.0, NULL,
 'hinh_ro', 1, 1, 'NV02', '2025-03-04 09:10:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 20, 20, 90, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/102.jpg', '2025-03-03 10:10:04'),

(2003, 'gemini-2.5-pro', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON chi tiết.',
 '08234', 8234, 0, 0, '{"chi_so":"08234","do_tin_cay":97}',
 2241, 305, 0, 0.00003510, 842.40, 3650,
 '2025-03-03 10:20:00', '2025-03-03 10:20:04', 0, 'thanh_cong', NULL,
 8234, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 29, 5.8, 87.0, NULL,
 'hinh_mo', 1, 1, 'NV01', '2025-03-04 09:20:00', 'Ảnh hơi nghiêng',
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/103.jpg', '2025-03-03 10:20:05'),

-- pro đọc sai nhỏ
(2004, 'gemini-2.5-pro', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON chi tiết.',
 '22103', 22103, 0, 0, '{"chi_so":"22103","do_tin_cay":91}',
 2241, 318, 0, 0.00003660, 878.40, 3920,
 '2025-03-04 11:00:00', '2025-03-04 11:00:04', 0, 'thanh_cong', NULL,
 22100, 0, 3, 3, 'SAI_NHO', 4, 5, 0.80,
 1, 48, 9.6, 144.0, NULL,
 'hinh_mo', 1, 1, 'NV02', '2025-03-05 09:00:00', 'Sai nhỏ, chấp nhận',
 35, 32, 67, 'AI_CAN_CANH_BAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/104.jpg', '2025-03-04 11:00:05'),

-- ════════════════════════════════════════════════════════════
-- NHÓM 3: gemini-2.5-flash / prompt v1.0 — KẾT QUẢ TRUNG BÌNH
-- ════════════════════════════════════════════════════════════

(3001, 'gemini-2.5-flash', 'v1.0', 'Đọc chỉ số đồng hồ nước.',
 '18432', 18432, 0, 0, '{"chi_so":"18432"}',
 1541, 198, 0, 0.00001420, 340.80, 1850,
 '2025-04-01 08:00:00', '2025-04-01 08:00:02', 0, 'thanh_cong', NULL,
 18432, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 44, 8.8, 132.0, NULL,
 'hinh_ro', 1, 1, 'NV01', '2025-04-02 09:00:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 20, 20, 90, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/201.jpg', '2025-04-01 08:00:03'),

(3002, 'gemini-2.5-flash', 'v1.0', 'Đọc chỉ số đồng hồ nước.',
 '07654', 7654, 0, 0, '{"chi_so":"07654"}',
 1541, 185, 0, 0.00001330, 319.20, 1920,
 '2025-04-01 08:10:00', '2025-04-01 08:10:02', 0, 'thanh_cong', NULL,
 7654, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 36, 7.2, 108.0, NULL,
 'hinh_ro', 1, 1, 'NV02', '2025-04-02 09:10:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/202.jpg', '2025-04-01 08:10:03'),

-- flash v1.0 đọc sai - mất chữ số đầu
(3003, 'gemini-2.5-flash', 'v1.0', 'Đọc chỉ số đồng hồ nước.',
 '2876', 2876, 0, 0, '{"chi_so":"2876"}',
 1541, 178, 0, 0.00001280, 307.20, 1780,
 '2025-04-01 08:20:00', '2025-04-01 08:20:02', 0, 'thanh_cong', NULL,
 12876, 0, -10000, 10000, 'MAT_CHU_SO_DAU', 4, 5, 0.80,
 0, NULL, NULL, NULL, 'AI đọc thấp hơn tháng trước',
 'hinh_mo', 0, 0, 'NV01', '2025-04-02 09:20:00', 'Mất số 1 đầu tiên',
 15, 32, 47, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 20, 20, 'TU_CHOI',
 'https://storage/img/203.jpg', '2025-04-01 08:20:03'),

(3004, 'gemini-2.5-flash', 'v1.0', 'Đọc chỉ số đồng hồ nước.',
 '4532', 4532, 0, 0, '{"chi_so":"4532"}',
 1541, 182, 0, 0.00001310, 314.40, 1840,
 '2025-04-02 09:00:00', '2025-04-02 09:00:02', 0, 'thanh_cong', NULL,
 14532, 0, -10000, 10000, 'MAT_CHU_SO_DAU', 4, 5, 0.80,
 0, NULL, NULL, NULL, 'AI đọc thấp hơn tháng trước',
 'hinh_mo', 0, 0, 'NV02', '2025-04-03 09:00:00', 'Mất chữ số đầu',
 15, 32, 47, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 20, 20, 'TU_CHOI',
 'https://storage/img/204.jpg', '2025-04-02 09:00:03'),

-- flash v1.0 đọc sai chữ số
(3005, 'gemini-2.5-flash', 'v1.0', 'Đọc chỉ số đồng hồ nước.',
 '12847', 12847, 0, 0, '{"chi_so":"12847"}',
 1541, 191, 0, 0.00001380, 331.20, 1960,
 '2025-04-02 09:10:00', '2025-04-02 09:10:02', 0, 'thanh_cong', NULL,
 12345, 0, 502, 502, 'DOC_SAI_CHU_SO', 3, 5, 0.60,
 0, 502, 9.6, 144.0, 'Tiêu thụ 502 m³ > ngưỡng max 144 m³',
 'hinh_mo', 0, 0, 'NV01', '2025-04-03 09:10:00', 'Sai số 8→3, 7→4',
 0, 24, 24, 'AI_KHONG_DAT_YEU_CAU', 10, 0, 20, 30, 'TU_CHOI',
 'https://storage/img/205.jpg', '2025-04-02 09:10:03'),

(3006, 'gemini-2.5-flash', 'v1.0', 'Đọc chỉ số đồng hồ nước.',
 '09213', 9213, 0, 0, '{"chi_so":"09213"}',
 1541, 195, 0, 0.00001410, 338.40, 2010,
 '2025-04-03 10:00:00', '2025-04-03 10:00:02', 0, 'thanh_cong', NULL,
 9813, 0, -600, 600, 'DOC_SAI_CHU_SO', 3, 5, 0.60,
 0, -600, NULL, NULL, 'AI đọc thấp hơn tháng trước',
 'hinh_mo', 0, 0, 'NV02', '2025-04-04 09:00:00', '2↔8 tại vị trí 2',
 0, 24, 24, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 20, 20, 'TU_CHOI',
 'https://storage/img/206.jpg', '2025-04-03 10:00:03'),

-- ════════════════════════════════════════════════════════════
-- NHÓM 4: CÁC TRƯỜNG HỢP ĐẶC BIỆT
-- ════════════════════════════════════════════════════════════

-- Có ký tự X (không đọc được 1 số)
(4001, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '1234X', NULL, 1, 1, '{"chi_so":"1234X","do_tin_cay":45}',
 1141, 163, 0, 0.00000363, 87.12, 1280,
 '2025-04-05 08:00:00', '2025-04-05 08:00:01', 0, 'thanh_cong', NULL,
 12345, 0, NULL, NULL, 'CO_KY_TU_X', 4, 5, 0.80,
 0, NULL, NULL, NULL, 'Không parse được - có ký tự X',
 'hinh_khong_day_du', 0, 0, 'NV01', '2025-04-06 09:00:00', 'Số cuối không đọc được',
 0, 32, 32, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 10, 10, 'TU_CHOI',
 'https://storage/img/301.jpg', '2025-04-05 08:00:02'),

(4002, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '1X345', NULL, 1, 1, '{"chi_so":"1X345","do_tin_cay":42}',
 1141, 158, 0, 0.00000352, 84.48, 1190,
 '2025-04-05 08:10:00', '2025-04-05 08:10:01', 0, 'thanh_cong', NULL,
 11345, 0, NULL, NULL, 'CO_KY_TU_X', 4, 5, 0.80,
 0, NULL, NULL, NULL, 'Không parse được - có ký tự X',
 'hinh_khong_day_du', 0, 0, 'NV02', '2025-04-06 09:10:00', 'Số giữa bị mờ',
 0, 32, 32, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 10, 10, 'TU_CHOI',
 'https://storage/img/302.jpg', '2025-04-05 08:10:02'),

-- Có 2 ký tự X
(4003, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '1X3X5', NULL, 1, 2, '{"chi_so":"1X3X5","do_tin_cay":28}',
 1141, 155, 0, 0.00000345, 82.80, 1150,
 '2025-04-06 09:00:00', '2025-04-06 09:00:01', 0, 'thanh_cong', NULL,
 11345, 0, NULL, NULL, 'CO_KY_TU_X', 3, 5, 0.60,
 0, NULL, NULL, NULL, 'Không parse được - có 2 ký tự X',
 'hinh_khong_day_du', 0, 0, 'NV01', '2025-04-07 09:00:00', 'Nhiều số không đọc được',
 0, 24, 24, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 5, 5, 'TU_CHOI',
 'https://storage/img/303.jpg', '2025-04-06 09:00:02'),

-- AI không đọc được (NULL response)
(4004, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 NULL, NULL, 0, 0, '{"error":"cannot_read_meter"}',
 1141, 89, 0, 0.00000198, 47.52, 890,
 '2025-04-07 10:00:00', '2025-04-07 10:00:01', 1, 'loi_parse', 'Không nhận dạng được đồng hồ',
 9812, 0, NULL, NULL, 'KHONG_DOC_DUOC', 0, 5, 0.00,
 0, NULL, NULL, NULL, 'AI không đọc được chỉ số (giá trị NULL)',
 'hinh_khong_doc_duoc', 0, 0, 'NV02', '2025-04-08 09:00:00', 'Hình quá mờ',
 0, 0, 0, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 0, 0, 'TU_CHOI',
 'https://storage/img/304.jpg', '2025-04-07 10:00:02'),

(4005, 'gemini-2.5-flash', 'v1.0', 'Đọc chỉ số đồng hồ nước.',
 NULL, NULL, 0, 0, '{"error":"image_not_meter"}',
 1541, 76, 0, 0.00000548, 131.52, 750,
 '2025-04-08 11:00:00', '2025-04-08 11:00:01', 2, 'loi_parse', 'Hình không phải đồng hồ nước',
 7234, 0, NULL, NULL, 'KHONG_DOC_DUOC', 0, 5, 0.00,
 0, NULL, NULL, NULL, 'AI không đọc được chỉ số (giá trị NULL)',
 'hinh_khong_doc_duoc', 0, 0, 'NV01', '2025-04-09 09:00:00', 'Ảnh bị che khuất',
 0, 0, 0, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 0, 0, 'TU_CHOI',
 'https://storage/img/305.jpg', '2025-04-08 11:00:02'),

-- Chỉ số âm (AI đọc thấp hơn tháng trước)
(4006, 'gemini-2.5-flash', 'v1.0', 'Đọc chỉ số đồng hồ nước.',
 '08100', 8100, 0, 0, '{"chi_so":"08100"}',
 1541, 188, 0, 0.00001356, 325.44, 1820,
 '2025-04-09 08:00:00', '2025-04-09 08:00:02', 0, 'thanh_cong', NULL,
 8750, 0, -650, 650, 'CHI_SO_AM', 4, 5, 0.80,
 0, -650, NULL, NULL, 'AI đọc 8100 < chỉ số tháng trước (đồng hồ không chạy ngược)',
 'hinh_mo', 0, 0, 'NV02', '2025-04-10 09:00:00', 'Đọc sai số hàng trăm',
 0, 32, 32, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 20, 20, 'TU_CHOI',
 'https://storage/img/306.jpg', '2025-04-09 08:00:03'),

-- Tăng đột biến (nghi ngờ)
(4007, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '12895', 12895, 0, 0, '{"chi_so":"12895","do_tin_cay":87}',
 1141, 165, 0, 0.00000368, 88.32, 1320,
 '2025-04-10 09:00:00', '2025-04-10 09:00:01', 0, 'thanh_cong', NULL,
 12895, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 0, 245, 9.6, 144.0, 'Tiêu thụ 245 m³ > ngưỡng max 144 m³ (TB 3 tháng: 48 m³)',
 'hinh_ro', 1, 0, 'NV01', '2025-04-11 09:00:00', 'Chỉ số đúng nhưng tiêu thụ bất thường, cần xác nhận thực địa',
 60, 40, 100, 'AI_CHINH_XAC_CAO', 10, 0, 20, 30, 'TU_CHOI',
 'https://storage/img/307.jpg', '2025-04-10 09:00:02'),

-- Timeout API
(4008, 'gemini-2.5-pro', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON chi tiết.',
 NULL, NULL, 0, 0, NULL,
 0, 0, 0, 0.00000000, 0.00, 30000,
 '2025-04-11 14:00:00', '2025-04-11 14:00:30', 3, 'timeout', 'Request timeout sau 30s',
 15420, 0, NULL, NULL, 'KHONG_DOC_DUOC', 0, 5, 0.00,
 0, NULL, NULL, NULL, 'AI không đọc được chỉ số (giá trị NULL)',
 'hinh_ro', 0, 0, 'NV02', '2025-04-12 09:00:00', 'Timeout, cần thử lại',
 0, 0, 0, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 0, 0, 'TU_CHOI',
 'https://storage/img/308.jpg', '2025-04-11 14:00:31'),

-- ════════════════════════════════════════════════════════════
-- NHÓM 5: NHIỀU BẢN GHI ĐA DẠNG THÁNG 5-6 (flash-lite v1.1)
-- ════════════════════════════════════════════════════════════

(5001, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '43210', 43210, 0, 0, '{"chi_so":"43210","do_tin_cay":96}',
 1141, 154, 0, 0.00000343, 82.32, 1090,
 '2025-05-01 08:00:00', '2025-05-01 08:00:01', 0, 'thanh_cong', NULL,
 43210, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 51, 10.2, 153.0, NULL,
 'hinh_ro', 1, 1, 'NV01', '2025-05-02 09:00:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 20, 20, 90, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/401.jpg', '2025-05-01 08:00:02'),

(5002, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '21087', 21087, 0, 0, '{"chi_so":"21087","do_tin_cay":93}',
 1141, 160, 0, 0.00000356, 85.44, 1140,
 '2025-05-01 08:15:00', '2025-05-01 08:15:01', 0, 'thanh_cong', NULL,
 21087, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 43, 8.6, 129.0, NULL,
 'hinh_ro', 1, 1, 'NV02', '2025-05-02 09:15:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/402.jpg', '2025-05-01 08:15:02'),

(5003, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '16543', 16543, 0, 0, '{"chi_so":"16543","do_tin_cay":91}',
 1141, 157, 0, 0.00000349, 83.76, 1160,
 '2025-05-02 09:00:00', '2025-05-02 09:00:01', 0, 'thanh_cong', NULL,
 16545, 0, -2, 2, 'SAI_NHO', 4, 5, 0.80,
 1, 38, 7.6, 114.0, NULL,
 'hinh_ro', 1, 1, 'NV01', '2025-05-03 09:00:00', 'Sai 2 đơn vị, chấp nhận',
 35, 32, 67, 'AI_CAN_CANH_BAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/403.jpg', '2025-05-02 09:00:02'),

(5004, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '09871', 9871, 0, 0, '{"chi_so":"09871","do_tin_cay":88}',
 1141, 166, 0, 0.00000369, 88.56, 1340,
 '2025-05-03 10:00:00', '2025-05-03 10:00:01', 0, 'thanh_cong', NULL,
 9871, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 34, 6.8, 102.0, NULL,
 'hinh_mo', 1, 1, 'NV02', '2025-05-04 09:00:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/404.jpg', '2025-05-03 10:00:02'),

(5005, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '31204', 31204, 0, 0, '{"chi_so":"31204","do_tin_cay":94}',
 1141, 153, 0, 0.00000341, 81.84, 1070,
 '2025-05-04 08:00:00', '2025-05-04 08:00:01', 0, 'thanh_cong', NULL,
 31204, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 57, 11.4, 171.0, NULL,
 'hinh_ro', 1, 1, 'NV01', '2025-05-05 09:00:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 10, 20, 80, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/405.jpg', '2025-05-04 08:00:02'),

(5006, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '14302', 14302, 0, 0, '{"chi_so":"14302","do_tin_cay":85}',
 1141, 172, 0, 0.00000383, 91.92, 1450,
 '2025-05-05 09:00:00', '2025-05-05 09:00:01', 0, 'thanh_cong', NULL,
 14300, 0, 2, 2, 'SAI_NHO', 4, 5, 0.80,
 1, 46, 9.2, 138.0, NULL,
 'hinh_mo', 1, 1, 'NV02', '2025-05-06 09:00:00', 'Sai 2 đơn vị, OK',
 35, 32, 67, 'AI_CAN_CANH_BAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/406.jpg', '2025-05-05 09:00:02'),

(5007, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '5432', 5432, 0, 0, '{"chi_so":"5432","do_tin_cay":71}',
 1141, 169, 0, 0.00000376, 90.24, 1390,
 '2025-05-06 10:00:00', '2025-05-06 10:00:01', 0, 'thanh_cong', NULL,
 15432, 0, -10000, 10000, 'MAT_CHU_SO_DAU', 4, 5, 0.80,
 0, NULL, NULL, NULL, 'AI đọc thấp hơn tháng trước',
 'hinh_khong_day_du', 0, 0, 'NV01', '2025-05-07 09:00:00', 'Mất số 1',
 15, 32, 47, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 20, 20, 'TU_CHOI',
 'https://storage/img/407.jpg', '2025-05-06 10:00:02'),

(5008, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '28734', 28734, 0, 0, '{"chi_so":"28734","do_tin_cay":92}',
 1141, 161, 0, 0.00000358, 85.92, 1200,
 '2025-05-07 08:00:00', '2025-05-07 08:00:01', 0, 'thanh_cong', NULL,
 28734, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 49, 9.8, 147.0, NULL,
 'hinh_ro', 1, 1, 'NV02', '2025-05-08 09:00:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 20, 20, 90, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/408.jpg', '2025-05-07 08:00:02'),

(5009, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '11X34', NULL, 1, 1, '{"chi_so":"11X34","do_tin_cay":55}',
 1141, 164, 0, 0.00000365, 87.60, 1260,
 '2025-05-08 09:00:00', '2025-05-08 09:00:01', 0, 'thanh_cong', NULL,
 11834, 0, NULL, NULL, 'CO_KY_TU_X', 4, 5, 0.80,
 0, NULL, NULL, NULL, 'Không parse được - có ký tự X',
 'hinh_khong_day_du', 0, 0, 'NV01', '2025-05-09 09:00:00', 'Số giữa mờ',
 0, 32, 32, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 10, 10, 'TU_CHOI',
 'https://storage/img/409.jpg', '2025-05-08 09:00:02'),

(5010, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '37891', 37891, 0, 0, '{"chi_so":"37891","do_tin_cay":95}',
 1141, 156, 0, 0.00000347, 83.28, 1110,
 '2025-05-09 08:00:00', '2025-05-09 08:00:01', 0, 'thanh_cong', NULL,
 37891, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 52, 10.4, 156.0, NULL,
 'hinh_ro', 1, 1, 'NV02', '2025-05-10 09:00:00', NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 20, 20, 90, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/410.jpg', '2025-05-09 08:00:02'),

-- Tháng 6 — chưa review
(6001, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '22341', 22341, 0, 0, '{"chi_so":"22341","do_tin_cay":94}',
 1141, 158, 0, 0.00000351, 84.24, 1130,
 '2025-06-01 08:00:00', '2025-06-01 08:00:01', 0, 'thanh_cong', NULL,
 22341, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 48, 9.6, 144.0, NULL,
 'hinh_ro', NULL, NULL, NULL, NULL, NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/501.jpg', '2025-06-01 08:00:02'),

(6002, 'gemini-2.5-pro', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON chi tiết.',
 '18756', 18756, 0, 0, '{"chi_so":"18756","do_tin_cay":99}',
 2241, 308, 0, 0.00003540, 849.60, 3580,
 '2025-06-02 09:00:00', '2025-06-02 09:00:04', 0, 'thanh_cong', NULL,
 18756, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 56, 11.2, 168.0, NULL,
 'hinh_ro', NULL, NULL, NULL, NULL, NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 20, 20, 90, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/502.jpg', '2025-06-02 09:00:05'),

(6003, 'gemini-2.5-flash', 'v1.0', 'Đọc chỉ số đồng hồ nước.',
 '09120', 9120, 0, 0, '{"chi_so":"09120"}',
 1541, 193, 0, 0.00001394, 334.56, 1980,
 '2025-06-03 10:00:00', '2025-06-03 10:00:02', 0, 'thanh_cong', NULL,
 9920, 0, -800, 800, 'DOC_SAI_CHU_SO', 3, 5, 0.60,
 0, -800, NULL, NULL, 'AI đọc thấp hơn tháng trước',
 'hinh_mo', NULL, NULL, NULL, NULL, NULL,
 0, 24, 24, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 20, 20, 'TU_CHOI',
 'https://storage/img/503.jpg', '2025-06-03 10:00:03'),

(6004, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '45678', 45678, 0, 0, '{"chi_so":"45678","do_tin_cay":97}',
 1141, 152, 0, 0.00000338, 81.12, 1060,
 '2025-06-04 08:00:00', '2025-06-04 08:00:01', 0, 'thanh_cong', NULL,
 45678, 1, 0, 0, 'CHINH_XAC', 5, 5, 1.00,
 1, 44, 8.8, 132.0, NULL,
 'hinh_ro', NULL, NULL, NULL, NULL, NULL,
 60, 40, 100, 'AI_CHINH_XAC_CAO', 50, 30, 20, 100, 'TU_DONG_CHAP_NHAN',
 'https://storage/img/504.jpg', '2025-06-04 08:00:02'),

(6005, 'gemini-2.5-flash-lite', 'v1.1', 'Đọc chỉ số đồng hồ nước, trả về JSON.',
 '33X21', NULL, 1, 1, '{"chi_so":"33X21","do_tin_cay":48}',
 1141, 167, 0, 0.00000371, 89.04, 1350,
 '2025-06-05 09:00:00', '2025-06-05 09:00:01', 0, 'thanh_cong', NULL,
 33421, 0, NULL, NULL, 'CO_KY_TU_X', 4, 5, 0.80,
 0, NULL, NULL, NULL, 'Không parse được - có ký tự X',
 'hinh_khong_day_du', NULL, NULL, NULL, NULL, NULL,
 0, 32, 32, 'AI_KHONG_DAT_YEU_CAU', 0, 0, 10, 10, 'TU_CHOI',
 'https://storage/img/505.jpg', '2025-06-05 09:00:02');

-- ============================================================
-- THỐNG KÊ NHANH để kiểm tra data
-- ============================================================
-- SELECT model_name, COUNT(*) as tong, 
--        SUM(is_exact_match) as dung,
--        ROUND(AVG(score_poc),1) as avg_poc,
--        ROUND(AVG(score_thuc_te),1) as avg_tt
-- FROM tn_meter_reading_log 
-- GROUP BY model_name;
