<?php

/**
 * WaterMeterRationalityChecker
 * 
 * Đánh giá tính hợp lý của chỉ số đồng hồ nước được đọc bằng AI LLM.
 * Các ngưỡng đánh giá được truyền vào từ ngoài, không hardcode.
 */
class WaterMeterRationalityChecker_copy
{
    // ── Mã kết quả ────────────────────────────────────────────────────────────
    const KET_QUA_HOP_LY = 'HOP_LY';
    const KET_QUA_NGHI_NGO_TANG_DOT_BIEN = 'NGHI_NGO_TANG_DOT_BIEN';
    const KET_QUA_NGHI_NGO_GIAM_DOT_BIEN = 'NGHI_NGO_GIAM_DOT_BIEN';
    const KET_QUA_LOI_CHI_SO_AM = 'LOI_CHI_SO_AM';
    const KET_QUA_LOI_TIEU_THU_BANG_0 = 'LOI_TIEU_THU_BANG_0';
    const KET_QUA_KHONG_CO_LICH_SU = 'KHONG_CO_LICH_SU';
    const KET_QUA_AI_KHONG_DOC_DUOC = 'AI_KHONG_DOC_DUOC';

    // ── Cấu hình mặc định (dùng khi không truyền config) ─────────────────────
    private static array $defaultConfig = [
        // Hệ số ngưỡng so với trung bình 3 tháng
        'he_so_nguong_min' => 0.2,   // Ngưỡng dưới = TB3T × 0.2
        'he_so_nguong_max' => 3.0,   // Ngưỡng trên = TB3T × 3.0

        // Hệ số so sánh với tháng trước
        'he_so_tang_vs_thang_truoc' => 2.0,   // Tăng quá TB × 2 → nghi ngờ
        'he_so_giam_vs_thang_truoc' => 0.5,   // Giảm quá TB × 0.5 → nghi ngờ

        // Ngưỡng tiêu thụ tối thiểu (m³) — dùng khi không có lịch sử TB3T
        'luong_tieu_thu_toi_thieu' => 1.0,

        // TRUE: vẫn kiểm tra tầng 3 dù không có TB3T (dùng ngưỡng tối thiểu)
        'kiem_tra_tang3_khi_khong_tb3t' => false,
    ];

    /**
     * Đánh giá tính hợp lý của chỉ số AI đọc được.
     *
     * @param float|null $aiChiSoParse               Chỉ số AI đọc được (NULL nếu không đọc được)
     * @param float      $chiSoNuocTN                Chỉ số tháng trước
     * @param float|null $luongNuocTieuThuThangTruoc Lượng tiêu thụ tháng trước (NULL nếu chưa có)
     * @param float|null $luongNuocTieuThuTB3Thang   Lượng tiêu thụ trung bình 3 tháng (NULL nếu chưa có)
     * @param array      $config                     Cấu hình ngưỡng (ghi đè defaultConfig)
     *
     * @return array {
     *   ket_qua          : string       -- Mã kết quả
     *   is_rationality   : bool|null    -- TRUE=hợp lý, FALSE=không hợp lý, NULL=không xác định
     *   luong_tieu_thu   : float|null   -- Lượng tiêu thụ tính được
     *   nguong_min       : float|null   -- Ngưỡng dưới đã dùng
     *   nguong_max       : float|null   -- Ngưỡng trên đã dùng
     *   chenh_lech_thang_truoc : float|null -- Chênh lệch so với tháng trước
     *   tang_truoc_cach_tinh   : string|null -- Cách tính ngưỡng đã dùng
     *   ly_do            : string       -- Mô tả chi tiết lý do
     * }
     */
    public static function danhGia(
        ?float $aiChiSoParse,
        float $chiSoNuocTN,
        ?float $luongNuocTieuThuThangTruoc,
        ?float $luongNuocTieuThuTB3Thang,
        array $config = []
    ): array {
        // Merge config với default
        $cfg = array_merge(self::$defaultConfig, $config);

        // ── Tầng 1: Kiểm tra cơ bản ──────────────────────────────────────────

        if ($aiChiSoParse === null) {
            return self::ketQua(
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
            return self::ketQua(
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
            $chenh = $aiChiSoParse - $chiSoNuocTN;
            return self::ketQua(
                self::KET_QUA_LOI_CHI_SO_AM,
                false,
                $chenh,
                null,
                null,
                $chenh,
                null,
                "AI đọc {$aiChiSoParse} < chỉ số tháng trước {$chiSoNuocTN} "
                . "(đồng hồ không chạy ngược)"
            );
        }

        $luongThangNay = round($aiChiSoParse - $chiSoNuocTN, 4);

        if ($luongThangNay == 0) {
            return self::ketQua(
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
            return self::ketQua(
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
            $cachTinh = "TB3T x {$cfg['he_so_nguong_min']} ~ TB3T x {$cfg['he_so_nguong_max']}";

            if ($luongThangNay > $nguongMax) {
                return self::ketQua(
                    self::KET_QUA_NGHI_NGO_TANG_DOT_BIEN,
                    false,
                    $luongThangNay,
                    $nguongMin,
                    $nguongMax,
                    null,
                    $cachTinh,
                    "Tiêu thụ {$luongThangNay} m³ > ngưỡng max {$nguongMax} m³ "
                    . "(TB 3 tháng: {$luongNuocTieuThuTB3Thang} m³)"
                );
            }

            if ($luongThangNay < $nguongMin) {
                return self::ketQua(
                    self::KET_QUA_NGHI_NGO_GIAM_DOT_BIEN,
                    false,
                    $luongThangNay,
                    $nguongMin,
                    $nguongMax,
                    null,
                    $cachTinh,
                    "Tiêu thụ {$luongThangNay} m³ < ngưỡng min {$nguongMin} m³ "
                    . "(TB 3 tháng: {$luongNuocTieuThuTB3Thang} m³)"
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
                return self::ketQua(
                    self::KET_QUA_NGHI_NGO_TANG_DOT_BIEN,
                    false,
                    $luongThangNay,
                    $nguongMin,
                    $nguongMax,
                    $chenhLechThangTruoc,
                    $cachTinh ?? "Tháng trước × {$cfg['he_so_tang_vs_thang_truoc']}",
                    "Tăng " . abs($chenhLechThangTruoc) . " m³ so với tháng trước "
                    . "({$luongNuocTieuThuThangTruoc} m³), vượt ngưỡng {$nguongTangMax} m³"
                );
            }

            if ($chenhLechThangTruoc < -$nguongGiamMax) {
                return self::ketQua(
                    self::KET_QUA_NGHI_NGO_GIAM_DOT_BIEN,
                    false,
                    $luongThangNay,
                    $nguongMin,
                    $nguongMax,
                    $chenhLechThangTruoc,
                    $cachTinh ?? "Tháng trước × {$cfg['he_so_giam_vs_thang_truoc']}",
                    "Giảm " . abs($chenhLechThangTruoc) . " m³ so với tháng trước "
                    . "({$luongNuocTieuThuThangTruoc} m³), vượt ngưỡng {$nguongGiamMax} m³"
                );
            }
        }

        // ── Hợp lý ────────────────────────────────────────────────────────────

        return self::ketQua(
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

    // ── Helper: tạo cấu trúc kết quả chuẩn ───────────────────────────────────

    private static function ketQua(
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

    /**
     * Trả về mô tả tiếng Việt của mã kết quả.
     */
    public static function moTaKetQua(string $ketQua): string
    {
        return match ($ketQua) {
            self::KET_QUA_HOP_LY => 'Hợp lý',
            self::KET_QUA_NGHI_NGO_TANG_DOT_BIEN => 'Nghi ngờ - Tăng đột biến',
            self::KET_QUA_NGHI_NGO_GIAM_DOT_BIEN => 'Nghi ngờ - Giảm đột biến',
            self::KET_QUA_LOI_CHI_SO_AM => 'Lỗi - Chỉ số âm hoặc nhỏ hơn tháng trước',
            self::KET_QUA_LOI_TIEU_THU_BANG_0 => 'Lỗi - Lượng tiêu thụ bằng 0',
            self::KET_QUA_KHONG_CO_LICH_SU => 'Không có lịch sử để so sánh',
            self::KET_QUA_AI_KHONG_DOC_DUOC => 'AI không đọc được chỉ số',
            default => 'Không xác định',
        };
    }
}


// ══════════════════════════════════════════════════════════════════════════════
// VÍ DỤ SỬ DỤNG
// ══════════════════════════════════════════════════════════════════════════════

$cases = [
    [
        'mo_ta' => 'Trường hợp 1: Hợp lý bình thường',
        'aiChiSo' => 12345,
        'chiSoTN' => 12300,
        'luongThangTruoc' => 50.0,
        'luongTB3Thang' => 48.0,
    ],
    [
        'mo_ta' => 'Trường hợp 2: AI đọc thấp hơn tháng trước (lỗi)',
        'aiChiSo' => 12200,
        'chiSoTN' => 12300,
        'luongThangTruoc' => 50.0,
        'luongTB3Thang' => 48.0,
    ],
    [
        'mo_ta' => 'Trường hợp 3: Tăng đột biến so với TB 3 tháng',
        'aiChiSo' => 12600,
        'chiSoTN' => 12300,
        'luongThangTruoc' => 50.0,
        'luongTB3Thang' => 48.0,
    ],
    [
        'mo_ta' => 'Trường hợp 4: Mất chữ số đầu (2345 thay vì 12345)',
        'aiChiSo' => 2345,
        'chiSoTN' => 12300,
        'luongThangTruoc' => 50.0,
        'luongTB3Thang' => 48.0,
    ],
    [
        'mo_ta' => 'Trường hợp 5: AI không đọc được (NULL)',
        'aiChiSo' => null,
        'chiSoTN' => 12300,
        'luongThangTruoc' => 50.0,
        'luongTB3Thang' => 48.0,
    ],
    [
        'mo_ta' => 'Trường hợp 6: Khách hàng mới, không có lịch sử',
        'aiChiSo' => 12345,
        'chiSoTN' => 12300,
        'luongThangTruoc' => null,
        'luongTB3Thang' => null,
    ],
    [
        'mo_ta' => 'Trường hợp 7: Dùng config tùy chỉnh (ngưỡng chặt hơn)',
        'aiChiSo' => 12390,
        'chiSoTN' => 12300,
        'luongThangTruoc' => 50.0,
        'luongTB3Thang' => 48.0,
        'config' => [
            'he_so_nguong_min' => 0.5,  // Chặt hơn default 0.2
            'he_so_nguong_max' => 2.0,  // Chặt hơn default 3.0
        ],
    ],
];

foreach ($cases as $case) {
    $config = $case['config'] ?? [];
    $result = WaterMeterRationalityChecker::danhGia(
        $case['aiChiSo'],
        $case['chiSoTN'],
        $case['luongThangTruoc'],
        $case['luongTB3Thang'],
        $config
    );

    echo "─────────────────────────────────────────────────────\n";
    echo "📋 {$case['mo_ta']}\n";
    echo "   AI đọc      : " . ($case['aiChiSo'] ?? 'NULL') . "\n";
    echo "   Chỉ số TN   : {$case['chiSoTN']}\n";
    echo "   Kết quả     : {$result['ket_qua']}\n";
    echo "   Mô tả       : " . WaterMeterRationalityChecker::moTaKetQua($result['ket_qua']) . "\n";
    echo "   Hợp lý      : " . ($result['is_rationality'] === null ? 'NULL' : ($result['is_rationality'] ? 'TRUE' : 'FALSE')) . "\n";
    echo "   Tiêu thụ    : " . ($result['luong_tieu_thu'] ?? 'NULL') . " m³\n";
    if ($result['nguong_min'] !== null) {
        echo "   Ngưỡng      : [{$result['nguong_min']} – {$result['nguong_max']}] ({$result['cach_tinh_nguong']})\n";
    }
    echo "   Lý do       : {$result['ly_do']}\n";
    echo "\n";
}