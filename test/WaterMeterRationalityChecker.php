<?php

/**
 * ============================================================================
 * WaterMeterRationalityChecker
 * ============================================================================
 *
 * Đánh giá tính hợp lý của chỉ số đồng hồ nước được đọc bằng AI LLM.
 * Bao gồm 2 score độc lập cho 2 giai đoạn:
 *
 *   - score_poc      : Giai đoạn 1 (POC)     — có ground truth từ nhân viên
 *   - score_thuc_te  : Giai đoạn 2 (Thực tế) — chỉ dựa vào lịch sử & hợp lý
 *
 * Nguyên tắc: Không hardcode — tất cả ngưỡng truyền vào qua $config.
 * ============================================================================
 */
class WaterMeterRationalityChecker
{
    // =========================================================================
    // HẰNG SỐ
    // =========================================================================

    // ── Mã kết quả hợp lý (dùng cho cả 2 giai đoạn) ─────────────────────────
    const KET_QUA_HOP_LY = 'HOP_LY';
    const KET_QUA_NGHI_NGO_TANG_DOT_BIEN = 'NGHI_NGO_TANG_DOT_BIEN';
    const KET_QUA_NGHI_NGO_GIAM_DOT_BIEN = 'NGHI_NGO_GIAM_DOT_BIEN';
    const KET_QUA_LOI_CHI_SO_AM = 'LOI_CHI_SO_AM';
    const KET_QUA_LOI_TIEU_THU_BANG_0 = 'LOI_TIEU_THU_BANG_0';
    const KET_QUA_KHONG_CO_LICH_SU = 'KHONG_CO_LICH_SU';
    const KET_QUA_AI_KHONG_DOC_DUOC = 'AI_KHONG_DOC_DUOC';

    // ── Mức độ score POC ─────────────────────────────────────────────────────
    const POC_AI_CHINH_XAC_CAO = 'AI_CHINH_XAC_CAO';      // >= 90
    const POC_AI_CHAP_NHAN_DUOC = 'AI_CHAP_NHAN_DUOC';     // 70 - 89
    const POC_AI_CAN_CANH_BAO = 'AI_CAN_CANH_BAO';       // 50 - 69
    const POC_AI_KHONG_DAT_YEU_CAU = 'AI_KHONG_DAT_YEU_CAU';  // < 50

    // ── Mức độ score Thực tế ─────────────────────────────────────────────────
    const TT_TU_DONG_CHAP_NHAN = 'TU_DONG_CHAP_NHAN';     // >= 80
    const TT_CHAP_NHAN_CO_THEO_DOI = 'CHAP_NHAN_CO_THEO_DOI'; // 60 - 79
    const TT_CAN_REVIEW = 'CAN_REVIEW';             // 40 - 59
    const TT_TU_CHOI = 'TU_CHOI';                // < 40

    // =========================================================================
    // CẤU HÌNH MẶC ĐỊNH
    // =========================================================================
    private static array $defaultConfig = [

        // ── Ngưỡng đánh giá hợp lý ───────────────────────────────────────────
        'he_so_nguong_min' => 0.2,  // Ngưỡng dưới  = TB3T × 0.2
        'he_so_nguong_max' => 3.0,  // Ngưỡng trên  = TB3T × 3.0
        'he_so_tang_vs_thang_truoc' => 2.0,  // Tăng > TT × 2.0 → nghi ngờ
        'he_so_giam_vs_thang_truoc' => 0.5,  // Giảm > TT × 0.5 → nghi ngờ
        'luong_tieu_thu_toi_thieu' => 1.0,  // m³ tối thiểu hợp lý

        // ── Ngưỡng score POC (giai đoạn 1) ───────────────────────────────────
        // Thành phần score_so_sat (tối đa 60 điểm)
        'poc_so_sat_sai_so_0' => 60,   // Đọc đúng tuyệt đối
        'poc_so_sat_sai_so_den_1' => 50,   // |sai_so| <= 1
        'poc_so_sat_sai_so_den_5' => 35,   // |sai_so| <= 5
        'poc_so_sat_sai_so_den_50' => 15,   // |sai_so| <= 50
        'poc_so_sat_sai_so_lon' => 0,    // |sai_so| > 50 hoặc NULL
        // Ngưỡng sai số tuyệt đối
        'poc_nguong_sai_so_rat_nho' => 1,    // <= 1 m³
        'poc_nguong_sai_so_nho' => 5,    // <= 5 m³
        'poc_nguong_sai_so_vua' => 50,   // <= 50 m³
        // Thành phần score_ky_tu_poc (tối đa 40 điểm)
        'poc_trong_so_ky_tu' => 40,
        // Ngưỡng phân loại mức độ score POC
        'poc_nguong_chinh_xac_cao' => 90,   // >= 90 → AI_CHINH_XAC_CAO
        'poc_nguong_chap_nhan_duoc' => 70,   // >= 70 → AI_CHAP_NHAN_DUOC
        'poc_nguong_can_canh_bao' => 50,   // >= 50 → AI_CAN_CANH_BAO

        // ── Ngưỡng score Thực tế (giai đoạn 2) ───────────────────────────────
        // Thành phần score_hop_ly (tối đa 50 điểm)
        'tt_score_hop_ly' => 50,   // HOP_LY
        'tt_score_khong_co_ls' => 30,   // KHONG_CO_LICH_SU
        'tt_score_nghi_ngo' => 10,   // NGHI_NGO_TANG/GIAM
        'tt_score_loi' => 0,    // LOI / AI_KHONG_DOC_DUOC
        // Thành phần score_do_lech_tb (tối đa 30 điểm)
        'tt_do_lech_rat_thap_pct' => 10,   // <= 10% → 30 điểm
        'tt_do_lech_thap_pct' => 30,   // <= 30% → 20 điểm
        'tt_do_lech_vua_pct' => 60,   // <= 60% → 10 điểm
        'tt_score_do_lech_rat_thap' => 30,
        'tt_score_do_lech_thap' => 20,
        'tt_score_do_lech_vua' => 10,
        'tt_score_do_lech_cao' => 0,
        // Thành phần score_doc_duoc (tối đa 20 điểm)
        'tt_score_doc_day_du' => 20,   // Không có ký tự X
        'tt_score_co_1_ky_tu_x' => 10,   // Có 1 ký tự X
        'tt_score_co_nhieu_ky_tu_x' => 5,    // Có >= 2 ký tự X
        'tt_score_khong_doc_duoc' => 0,    // NULL
        // Ngưỡng phân loại mức độ score Thực tế
        'tt_nguong_tu_dong_chap_nhan' => 80,   // >= 80 → TU_DONG_CHAP_NHAN
        'tt_nguong_chap_nhan_theo_doi' => 60,   // >= 60 → CHAP_NHAN_CO_THEO_DOI
        'tt_nguong_can_review' => 40,   // >= 40 → CAN_REVIEW
    ];

    // =========================================================================
    // PUBLIC METHODS
    // =========================================================================

    /**
     * Đánh giá tính hợp lý của chỉ số AI đọc được.
     * Dùng cho cả 2 giai đoạn — là nền tảng để tính score_thuc_te.
     *
     * @param float|null $aiChiSoParse               Chỉ số AI đọc được
     * @param float      $chiSoNuocTN                Chỉ số tháng trước
     * @param float|null $luongNuocTieuThuThangTruoc Lượng tiêu thụ tháng trước
     * @param float|null $luongNuocTieuThuTB3Thang   Lượng TB 3 tháng trước
     * @param array      $config                     Config ghi đè
     *
     * @return array
     */
    public static function danhGia(
        ?float $aiChiSoParse,
        float $chiSoNuocTN,
        ?float $luongNuocTieuThuThangTruoc,
        ?float $luongNuocTieuThuTB3Thang,
        array $config = []
    ): array {
        $cfg = array_merge(self::$defaultConfig, $config);

        // ── Tầng 1: Kiểm tra cơ bản ──────────────────────────────────────────
        if ($aiChiSoParse === null) {
            return self::buildKetQua(
                self::KET_QUA_AI_KHONG_DOC_DUOC,
                false,
                null,
                null,
                null,
                null,
                null,
                'AI không đọc được chỉ số (giá trị NULL)'
            );
        }

        if ($aiChiSoParse < 0) {
            return self::buildKetQua(
                self::KET_QUA_LOI_CHI_SO_AM,
                false,
                null,
                null,
                null,
                null,
                null,
                "Chỉ số AI ({$aiChiSoParse}) là số âm, vô lý"
            );
        }

        if ($aiChiSoParse < $chiSoNuocTN) {
            $chenh = round($aiChiSoParse - $chiSoNuocTN, 4);
            return self::buildKetQua(
                self::KET_QUA_LOI_CHI_SO_AM,
                false,
                $chenh,
                null,
                null,
                $chenh,
                null,
                "AI đọc {$aiChiSoParse} < chỉ số tháng trước {$chiSoNuocTN} (đồng hồ không chạy ngược)"
            );
        }

        $luongThangNay = round($aiChiSoParse - $chiSoNuocTN, 4);

        if ($luongThangNay == 0) {
            return self::buildKetQua(
                self::KET_QUA_LOI_TIEU_THU_BANG_0,
                false,
                $luongThangNay,
                null,
                null,
                null,
                null,
                'Lượng tiêu thụ = 0 m³, cần xác nhận thực tế (nhà bỏ trống?)'
            );
        }

        // ── Tầng 4: Kiểm tra có lịch sử không ───────────────────────────────
        $coTB3Thang = $luongNuocTieuThuTB3Thang !== null && $luongNuocTieuThuTB3Thang > 0;
        $coThangTruoc = $luongNuocTieuThuThangTruoc !== null && $luongNuocTieuThuThangTruoc > 0;

        if (!$coTB3Thang && !$coThangTruoc) {
            return self::buildKetQua(
                self::KET_QUA_KHONG_CO_LICH_SU,
                null,
                $luongThangNay,
                null,
                null,
                null,
                null,
                'Không có lịch sử tiêu thụ để so sánh (khách hàng mới?)'
            );
        }

        // ── Tầng 2: So sánh với trung bình 3 tháng ───────────────────────────
        $nguongMin = null;
        $nguongMax = null;
        $cachTinh = null;

        if ($coTB3Thang) {
            $nguongMin = round($luongNuocTieuThuTB3Thang * $cfg['he_so_nguong_min'], 4);
            $nguongMax = round($luongNuocTieuThuTB3Thang * $cfg['he_so_nguong_max'], 4);
            $cachTinh = "TB3T × {$cfg['he_so_nguong_min']} ~ TB3T × {$cfg['he_so_nguong_max']}";

            if ($luongThangNay > $nguongMax) {
                return self::buildKetQua(
                    self::KET_QUA_NGHI_NGO_TANG_DOT_BIEN,
                    false,
                    $luongThangNay,
                    $nguongMin,
                    $nguongMax,
                    null,
                    $cachTinh,
                    "Tiêu thụ {$luongThangNay} m³ > ngưỡng max {$nguongMax} m³ (TB 3 tháng: {$luongNuocTieuThuTB3Thang} m³)"
                );
            }

            if ($luongThangNay < $nguongMin) {
                return self::buildKetQua(
                    self::KET_QUA_NGHI_NGO_GIAM_DOT_BIEN,
                    false,
                    $luongThangNay,
                    $nguongMin,
                    $nguongMax,
                    null,
                    $cachTinh,
                    "Tiêu thụ {$luongThangNay} m³ < ngưỡng min {$nguongMin} m³ (TB 3 tháng: {$luongNuocTieuThuTB3Thang} m³)"
                );
            }
        }

        // ── Tầng 3: So sánh với tháng trước ──────────────────────────────────
        $chenhLechThangTruoc = null;

        if ($coThangTruoc) {
            $chenhLechThangTruoc = round($luongThangNay - $luongNuocTieuThuThangTruoc, 4);
            $nguongTangMax = round($luongNuocTieuThuThangTruoc * $cfg['he_so_tang_vs_thang_truoc'], 4);
            $nguongGiamMax = round($luongNuocTieuThuThangTruoc * $cfg['he_so_giam_vs_thang_truoc'], 4);

            if ($chenhLechThangTruoc > $nguongTangMax) {
                return self::buildKetQua(
                    self::KET_QUA_NGHI_NGO_TANG_DOT_BIEN,
                    false,
                    $luongThangNay,
                    $nguongMin,
                    $nguongMax,
                    $chenhLechThangTruoc,
                    $cachTinh ?? "Tháng trước × {$cfg['he_so_tang_vs_thang_truoc']}",
                    "Tăng " . abs($chenhLechThangTruoc) . " m³ so với tháng trước ({$luongNuocTieuThuThangTruoc} m³), vượt ngưỡng {$nguongTangMax} m³"
                );
            }

            if ($chenhLechThangTruoc < -$nguongGiamMax) {
                return self::buildKetQua(
                    self::KET_QUA_NGHI_NGO_GIAM_DOT_BIEN,
                    false,
                    $luongThangNay,
                    $nguongMin,
                    $nguongMax,
                    $chenhLechThangTruoc,
                    $cachTinh ?? "Tháng trước × {$cfg['he_so_giam_vs_thang_truoc']}",
                    "Giảm " . abs($chenhLechThangTruoc) . " m³ so với tháng trước ({$luongNuocTieuThuThangTruoc} m³), vượt ngưỡng {$nguongGiamMax} m³"
                );
            }
        }

        // ── Hợp lý ────────────────────────────────────────────────────────────
        return self::buildKetQua(
            self::KET_QUA_HOP_LY,
            true,
            $luongThangNay,
            $nguongMin,
            $nguongMax,
            $chenhLechThangTruoc,
            $cachTinh,
            "Tiêu thụ {$luongThangNay} m³ nằm trong ngưỡng hợp lý"
            . ($nguongMin !== null ? " [{$nguongMin} – {$nguongMax}]" : '')
        );
    }

    // -------------------------------------------------------------------------

    /**
     * Tính score POC — Giai đoạn 1.
     * Yêu cầu có ground truth (humanChiSo) từ nhân viên.
     *
     * @param float|null $aiChiSoParse    Chỉ số AI đọc được
     * @param float|null $humanChiSo      Chỉ số nhân viên ghi nhận (ground truth)
     * @param float|null $charAccuracyRate Tỷ lệ ký tự đúng (0.0 - 1.0)
     * @param array      $config          Config ghi đè
     *
     * @return array {
     *   score_so_sat      : int        -- Điểm thành phần 1 (0-60)
     *   score_ky_tu_poc   : int        -- Điểm thành phần 2 (0-40)
     *   score_poc         : int        -- Tổng điểm POC (0-100)
     *   muc_do_poc        : string     -- Mức độ đánh giá
     *   sai_so_tuyet_doi  : float|null -- Sai số tuyệt đối
     *   chi_tiet          : array      -- Chi tiết từng thành phần để ghi log
     * }
     */
    public static function tinhScorePoc(
        ?float $aiChiSoParse,
        ?float $humanChiSo,
        ?float $charAccuracyRate,
        array $config = []
    ): array {
        $cfg = array_merge(self::$defaultConfig, $config);

        // ── Thành phần 1: score_so_sat (0 - 60) ─────────────────────────────
        $scoreSoSat = 0;
        $saiSoTuyetDoi = null;
        $lyDoSoSat = '';

        if ($aiChiSoParse === null || $humanChiSo === null) {
            $scoreSoSat = $cfg['poc_so_sat_sai_so_lon'];
            $lyDoSoSat = 'Không có giá trị để so sánh (NULL)';
        } else {
            $saiSoTuyetDoi = abs(round($aiChiSoParse - $humanChiSo, 4));

            if ($saiSoTuyetDoi == 0) {
                $scoreSoSat = $cfg['poc_so_sat_sai_so_0'];
                $lyDoSoSat = 'Đọc đúng tuyệt đối (sai số = 0)';
            } elseif ($saiSoTuyetDoi <= $cfg['poc_nguong_sai_so_rat_nho']) {
                $scoreSoSat = $cfg['poc_so_sat_sai_so_den_1'];
                $lyDoSoSat = "Sai số rất nhỏ ({$saiSoTuyetDoi} ≤ {$cfg['poc_nguong_sai_so_rat_nho']})";
            } elseif ($saiSoTuyetDoi <= $cfg['poc_nguong_sai_so_nho']) {
                $scoreSoSat = $cfg['poc_so_sat_sai_so_den_5'];
                $lyDoSoSat = "Sai số nhỏ ({$saiSoTuyetDoi} ≤ {$cfg['poc_nguong_sai_so_nho']})";
            } elseif ($saiSoTuyetDoi <= $cfg['poc_nguong_sai_so_vua']) {
                $scoreSoSat = $cfg['poc_so_sat_sai_so_den_50'];
                $lyDoSoSat = "Sai số vừa ({$saiSoTuyetDoi} ≤ {$cfg['poc_nguong_sai_so_vua']})";
            } else {
                $scoreSoSat = $cfg['poc_so_sat_sai_so_lon'];
                $lyDoSoSat = "Sai số lớn ({$saiSoTuyetDoi} > {$cfg['poc_nguong_sai_so_vua']})";
            }
        }

        // ── Thành phần 2: score_ky_tu_poc (0 - 40) ──────────────────────────
        $tyLeKyTu = max(0.0, min(1.0, $charAccuracyRate ?? 0.0));
        $scoreKyTu = (int) round($tyLeKyTu * $cfg['poc_trong_so_ky_tu']);
        $tyLePct = round($tyLeKyTu * 100, 1);
        $lyDoKyTu = "Tỷ lệ ký tự đúng: {$tyLePct}% × {$cfg['poc_trong_so_ky_tu']} điểm";

        // ── Tổng hợp ─────────────────────────────────────────────────────────
        $scorePoc = $scoreSoSat + $scoreKyTu;
        $mucDo = self::phanLoaiScorePoc($scorePoc, $cfg);

        return [
            'score_so_sat' => $scoreSoSat,
            'score_ky_tu_poc' => $scoreKyTu,
            'score_poc' => $scorePoc,
            'muc_do_poc' => $mucDo,
            'sai_so_tuyet_doi' => $saiSoTuyetDoi,
            'chi_tiet' => [
                'so_sat' => ['diem' => $scoreSoSat, 'toi_da' => 60, 'ly_do' => $lyDoSoSat],
                'ky_tu' => ['diem' => $scoreKyTu, 'toi_da' => 40, 'ly_do' => $lyDoKyTu],
            ],
        ];
    }

    // -------------------------------------------------------------------------

    /**
     * Tính score Thực tế — Giai đoạn 2.
     * Không cần ground truth, dựa vào kết quả danhGia() và thông tin đọc số.
     *
     * @param array      $ketQuaDanhGia        Kết quả từ danhGia()
     * @param float|null $luongNuocTieuThuTB3T Lượng TB 3 tháng (để tính độ lệch)
     * @param int        $soKyTuX              Số ký tự X trong ai_chi_so
     * @param bool       $aiDocDuoc            FALSE nếu AI trả về NULL hoàn toàn
     * @param array      $config               Config ghi đè
     *
     * @return array {
     *   score_hop_ly      : int    -- Điểm thành phần 1 (0-50)
     *   score_do_lech_tb  : int    -- Điểm thành phần 2 (0-30)
     *   score_doc_duoc    : int    -- Điểm thành phần 3 (0-20)
     *   score_thuc_te     : int    -- Tổng điểm Thực tế (0-100)
     *   muc_do_thuc_te    : string -- Mức độ ra quyết định
     *   do_lech_tb_pct    : float|null
     *   chi_tiet          : array  -- Chi tiết từng thành phần để ghi log
     * }
     */
    public static function tinhScoreThucTe(
        array $ketQuaDanhGia,
        ?float $luongNuocTieuThuTB3T,
        int $soKyTuX = 0,
        bool $aiDocDuoc = true,
        array $config = []
    ): array {
        $cfg = array_merge(self::$defaultConfig, $config);

        // ── Thành phần 1: score_hop_ly (0 - 50) ─────────────────────────────
        $scoreHopLy = match ($ketQuaDanhGia['ket_qua']) {
            self::KET_QUA_HOP_LY => $cfg['tt_score_hop_ly'],
            self::KET_QUA_KHONG_CO_LICH_SU => $cfg['tt_score_khong_co_ls'],
            self::KET_QUA_NGHI_NGO_TANG_DOT_BIEN,
            self::KET_QUA_NGHI_NGO_GIAM_DOT_BIEN => $cfg['tt_score_nghi_ngo'],
            default => $cfg['tt_score_loi'],
        };
        $lyDoHopLy = self::moTaKetQua($ketQuaDanhGia['ket_qua']) . " → {$scoreHopLy} điểm";

        // ── Thành phần 2: score_do_lech_tb (0 - 30) ─────────────────────────
        $scoreDoLech = 0;
        $lyDoDoLech = '';
        $doLechPct = null;

        $luongThangNay = $ketQuaDanhGia['luong_tieu_thu'];
        $coTB3T = $luongNuocTieuThuTB3T !== null && $luongNuocTieuThuTB3T > 0;
        $coLuong = $luongThangNay !== null && $luongThangNay > 0;

        if ($coTB3T && $coLuong) {
            $doLechPct = round(
                abs($luongThangNay - $luongNuocTieuThuTB3T) / $luongNuocTieuThuTB3T * 100,
                2
            );

            if ($doLechPct <= $cfg['tt_do_lech_rat_thap_pct']) {
                $scoreDoLech = $cfg['tt_score_do_lech_rat_thap'];
                $lyDoDoLech = "Lệch {$doLechPct}% ≤ {$cfg['tt_do_lech_rat_thap_pct']}% so với TB3T";
            } elseif ($doLechPct <= $cfg['tt_do_lech_thap_pct']) {
                $scoreDoLech = $cfg['tt_score_do_lech_thap'];
                $lyDoDoLech = "Lệch {$doLechPct}% ≤ {$cfg['tt_do_lech_thap_pct']}% so với TB3T";
            } elseif ($doLechPct <= $cfg['tt_do_lech_vua_pct']) {
                $scoreDoLech = $cfg['tt_score_do_lech_vua'];
                $lyDoDoLech = "Lệch {$doLechPct}% ≤ {$cfg['tt_do_lech_vua_pct']}% so với TB3T";
            } else {
                $scoreDoLech = $cfg['tt_score_do_lech_cao'];
                $lyDoDoLech = "Lệch {$doLechPct}% > {$cfg['tt_do_lech_vua_pct']}% so với TB3T";
            }
        } else {
            $lyDoDoLech = 'Không có TB3T hoặc lượng tiêu thụ để tính độ lệch';
        }

        // ── Thành phần 3: score_doc_duoc (0 - 20) ───────────────────────────
        if (!$aiDocDuoc) {
            $scoreDocDuoc = $cfg['tt_score_khong_doc_duoc'];
            $lyDoDocDuoc = 'AI không đọc được (NULL)';
        } elseif ($soKyTuX === 0) {
            $scoreDocDuoc = $cfg['tt_score_doc_day_du'];
            $lyDoDocDuoc = 'Đọc đầy đủ, không có ký tự X';
        } elseif ($soKyTuX === 1) {
            $scoreDocDuoc = $cfg['tt_score_co_1_ky_tu_x'];
            $lyDoDocDuoc = 'Có 1 ký tự X (1 chữ số không đọc được)';
        } else {
            $scoreDocDuoc = $cfg['tt_score_co_nhieu_ky_tu_x'];
            $lyDoDocDuoc = "Có {$soKyTuX} ký tự X (nhiều chữ số không đọc được)";
        }

        // ── Tổng hợp ─────────────────────────────────────────────────────────
        $scoreThucTe = $scoreHopLy + $scoreDoLech + $scoreDocDuoc;
        $mucDo = self::phanLoaiScoreThucTe($scoreThucTe, $cfg);

        return [
            'score_hop_ly' => $scoreHopLy,
            'score_do_lech_tb' => $scoreDoLech,
            'score_doc_duoc' => $scoreDocDuoc,
            'score_thuc_te' => $scoreThucTe,
            'muc_do_thuc_te' => $mucDo,
            'do_lech_tb_pct' => $doLechPct,
            'chi_tiet' => [
                'hop_ly' => ['diem' => $scoreHopLy, 'toi_da' => 50, 'ly_do' => $lyDoHopLy],
                'do_lech' => ['diem' => $scoreDoLech, 'toi_da' => 30, 'ly_do' => $lyDoDoLech],
                'doc_duoc' => ['diem' => $scoreDocDuoc, 'toi_da' => 20, 'ly_do' => $lyDoDocDuoc],
            ],
        ];
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private static function buildKetQua(
        string $ketQua,
        ?bool $isRationality,
        ?float $luongTieuThu,
        ?float $nguongMin,
        ?float $nguongMax,
        ?float $chenhLechThangTruoc,
        ?string $cachTinhNguong,
        string $lyDo
    ): array {
        return [
            'ket_qua' => $ketQua,
            'is_rationality' => $isRationality,
            'luong_tieu_thu' => $luongTieuThu,
            'nguong_min' => $nguongMin,
            'nguong_max' => $nguongMax,
            'chenh_lech_thang_truoc' => $chenhLechThangTruoc,
            'cach_tinh_nguong' => $cachTinhNguong,
            'ly_do' => $lyDo,
        ];
    }

    private static function phanLoaiScorePoc(int $score, array $cfg): string
    {
        if ($score >= $cfg['poc_nguong_chinh_xac_cao'])
            return self::POC_AI_CHINH_XAC_CAO;
        if ($score >= $cfg['poc_nguong_chap_nhan_duoc'])
            return self::POC_AI_CHAP_NHAN_DUOC;
        if ($score >= $cfg['poc_nguong_can_canh_bao'])
            return self::POC_AI_CAN_CANH_BAO;
        return self::POC_AI_KHONG_DAT_YEU_CAU;
    }

    private static function phanLoaiScoreThucTe(int $score, array $cfg): string
    {
        if ($score >= $cfg['tt_nguong_tu_dong_chap_nhan'])
            return self::TT_TU_DONG_CHAP_NHAN;
        if ($score >= $cfg['tt_nguong_chap_nhan_theo_doi'])
            return self::TT_CHAP_NHAN_CO_THEO_DOI;
        if ($score >= $cfg['tt_nguong_can_review'])
            return self::TT_CAN_REVIEW;
        return self::TT_TU_CHOI;
    }

    // =========================================================================
    // MÔ TẢ TIẾNG VIỆT
    // =========================================================================

    public static function moTaKetQua(string $ketQua): string
    {
        return match ($ketQua) {
            self::KET_QUA_HOP_LY => 'Hợp lý',
            self::KET_QUA_NGHI_NGO_TANG_DOT_BIEN => 'Nghi ngờ - Tăng đột biến',
            self::KET_QUA_NGHI_NGO_GIAM_DOT_BIEN => 'Nghi ngờ - Giảm đột biến',
            self::KET_QUA_LOI_CHI_SO_AM => 'Lỗi - Chỉ số nhỏ hơn tháng trước',
            self::KET_QUA_LOI_TIEU_THU_BANG_0 => 'Lỗi - Lượng tiêu thụ bằng 0',
            self::KET_QUA_KHONG_CO_LICH_SU => 'Không có lịch sử để so sánh',
            self::KET_QUA_AI_KHONG_DOC_DUOC => 'AI không đọc được chỉ số',
            default => 'Không xác định',
        };
    }

    public static function moTaMucDoPoc(string $mucDo): string
    {
        return match ($mucDo) {
            self::POC_AI_CHINH_XAC_CAO => 'Chính xác cao — có thể triển khai',
            self::POC_AI_CHAP_NHAN_DUOC => 'Chấp nhận được — cần cải thiện thêm',
            self::POC_AI_CAN_CANH_BAO => 'Cần cảnh báo — review kỹ trước khi dùng',
            self::POC_AI_KHONG_DAT_YEU_CAU => 'Không đạt yêu cầu — chưa đủ tin cậy',
            default => 'Không xác định',
        };
    }

    public static function moTaMucDoThucTe(string $mucDo): string
    {
        return match ($mucDo) {
            self::TT_TU_DONG_CHAP_NHAN => 'Tự động chấp nhận — không cần review',
            self::TT_CHAP_NHAN_CO_THEO_DOI => 'Chấp nhận, có theo dõi — ghi log',
            self::TT_CAN_REVIEW => 'Cần review — chuyển nhân viên xác nhận',
            self::TT_TU_CHOI => 'Từ chối — yêu cầu chụp lại hoặc nhân viên đọc',
            default => 'Không xác định',
        };
    }
}


// ============================================================================
// VÍ DỤ SỬ DỤNG
// ============================================================================

$cases = [
    [
        'mo_ta' => 'Case 1: Đọc đúng hoàn toàn',
        'aiChiSo' => 12345.0,
        'humanChiSo' => 12345.0,
        'chiSoTN' => 12295.0,
        'luongTT' => 50.0,
        'luongTB3T' => 48.0,
        'charAccuracy' => 1.0,
        'soKyTuX' => 0,
    ],
    [
        'mo_ta' => 'Case 2: Sai nhỏ (sai 3 m³)',
        'aiChiSo' => 12348.0,
        'humanChiSo' => 12345.0,
        'chiSoTN' => 12295.0,
        'luongTT' => 50.0,
        'luongTB3T' => 48.0,
        'charAccuracy' => 0.8,
        'soKyTuX' => 0,
    ],
    [
        'mo_ta' => 'Case 3: Mất chữ số đầu (2345 thay vì 12345)',
        'aiChiSo' => 2345.0,
        'humanChiSo' => 12345.0,
        'chiSoTN' => 12295.0,
        'luongTT' => 50.0,
        'luongTB3T' => 48.0,
        'charAccuracy' => 0.8,
        'soKyTuX' => 0,
    ],
    [
        'mo_ta' => 'Case 4: Đọc sai lớn (12847 thay vì 12345)',
        'aiChiSo' => 12847.0,
        'humanChiSo' => 12345.0,
        'chiSoTN' => 12295.0,
        'luongTT' => 50.0,
        'luongTB3T' => 48.0,
        'charAccuracy' => 0.6,
        'soKyTuX' => 0,
    ],
    [
        'mo_ta' => 'Case 5: Có ký tự X (1 số không đọc được)',
        'aiChiSo' => null,
        'humanChiSo' => 12345.0,
        'chiSoTN' => 12295.0,
        'luongTT' => 50.0,
        'luongTB3T' => 48.0,
        'charAccuracy' => 0.8,
        'soKyTuX' => 1,
        'aiDocDuoc' => true,
    ],
    [
        'mo_ta' => 'Case 6: Tăng đột biến so với lịch sử',
        'aiChiSo' => 12450.0,
        'humanChiSo' => 12450.0,
        'chiSoTN' => 12295.0,
        'luongTT' => 50.0,
        'luongTB3T' => 48.0,
        'charAccuracy' => 1.0,
        'soKyTuX' => 0,
    ],
    [
        'mo_ta' => 'Case 7: Khách hàng mới (không có lịch sử)',
        'aiChiSo' => 12345.0,
        'humanChiSo' => 12345.0,
        'chiSoTN' => 12295.0,
        'luongTT' => null,
        'luongTB3T' => null,
        'charAccuracy' => 1.0,
        'soKyTuX' => 0,
    ],
];

foreach ($cases as $i => $c) {
    $aiDocDuoc = $c['aiDocDuoc'] ?? ($c['aiChiSo'] !== null);

    // Bước 1: Đánh giá hợp lý
    $danhGia = WaterMeterRationalityChecker::danhGia(
        $c['aiChiSo'],
        $c['chiSoTN'],
        $c['luongTT'],
        $c['luongTB3T']
    );

    // Bước 2: Score POC (giai đoạn 1 — có ground truth)
    $scorePoc = WaterMeterRationalityChecker::tinhScorePoc(
        $c['aiChiSo'],
        $c['humanChiSo'],
        $c['charAccuracy']
    );

    // Bước 3: Score Thực tế (giai đoạn 2 — không có ground truth)
    $scoreTT = WaterMeterRationalityChecker::tinhScoreThucTe(
        $danhGia,
        $c['luongTB3T'],
        $c['soKyTuX'],
        $aiDocDuoc
    );

    $no = $i + 1;
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  Case {$no}: {$c['mo_ta']}\n";
    echo "───────────────────────────────────────────────────────────────\n";
    echo "  INPUT\n";
    echo "    AI đọc    : " . ($c['aiChiSo'] ?? 'NULL') . "  |  "
        . "Nhân viên: " . ($c['humanChiSo'] ?? 'NULL') . "  |  "
        . "Chỉ số TN: {$c['chiSoTN']}\n";
    echo "    Tháng trước: " . ($c['luongTT'] ?? 'NULL') . " m³  |  "
        . "TB3T: " . ($c['luongTB3T'] ?? 'NULL') . " m³\n";
    echo "\n";

    echo "  [ĐÁNH GIÁ HỢP LÝ]\n";
    $rational = $danhGia['is_rationality'] === null ? 'NULL'
        : ($danhGia['is_rationality'] ? 'TRUE' : 'FALSE');
    echo "    {$danhGia['ket_qua']} (is_rationality: {$rational})\n";
    echo "    {$danhGia['ly_do']}\n";
    echo "\n";

    echo "  [SCORE POC — Giai đoạn 1, có ground truth]\n";
    echo "    score_so_sat    : {$scorePoc['score_so_sat']}/60"
        . "  » {$scorePoc['chi_tiet']['so_sat']['ly_do']}\n";
    echo "    score_ky_tu_poc : {$scorePoc['score_ky_tu_poc']}/40"
        . "  » {$scorePoc['chi_tiet']['ky_tu']['ly_do']}\n";
    echo "    ─────────────────────────────────────────\n";
    echo "    score_poc       : {$scorePoc['score_poc']}/100"
        . "  → [{$scorePoc['muc_do_poc']}]\n";
    echo "    Quyết định      : "
        . WaterMeterRationalityChecker::moTaMucDoPoc($scorePoc['muc_do_poc']) . "\n";
    echo "\n";

    echo "  [SCORE THỰC TẾ — Giai đoạn 2, không có ground truth]\n";
    echo "    score_hop_ly    : {$scoreTT['score_hop_ly']}/50"
        . "  » {$scoreTT['chi_tiet']['hop_ly']['ly_do']}\n";
    echo "    score_do_lech   : {$scoreTT['score_do_lech_tb']}/30"
        . "  » {$scoreTT['chi_tiet']['do_lech']['ly_do']}\n";
    echo "    score_doc_duoc  : {$scoreTT['score_doc_duoc']}/20"
        . "  » {$scoreTT['chi_tiet']['doc_duoc']['ly_do']}\n";
    echo "    ─────────────────────────────────────────\n";
    echo "    score_thuc_te   : {$scoreTT['score_thuc_te']}/100"
        . "  → [{$scoreTT['muc_do_thuc_te']}]\n";
    echo "    Quyết định      : "
        . WaterMeterRationalityChecker::moTaMucDoThucTe($scoreTT['muc_do_thuc_te']) . "\n";
    echo "\n";
}