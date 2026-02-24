CREATE TABLE IF NOT EXISTS loai_dhn (
    -- ═══ THÔNG TIN NHẬN DẠNG ═══
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    model_dong_ho           VARCHAR(100),           
                            -- Mã model: MULTIMAG, LOTWMC-01, NBIOT-V01,...
                            -- NULL nếu là record prompt chung

    -- ═══ LOẠI HIỂN THỊ ═══
    loai_hien_thi           VARCHAR(100) NOT NULL,
                            -- "Đồng hồ cơ vòng số"
                            -- "Đồng hồ điện tử, màn hình LCD"
    vung_hien_thi           VARCHAR(500),
                            -- VD: "Dãy số trong khung chữ nhật, có ký hiệu m³"

    -- ═══ PHẦN NGUYÊN ═══
    phan_nguyen_digits      TINYINT,
    phan_nguyen_color       VARCHAR(50),
    phan_nguyen_background  VARCHAR(50),

    -- ═══ PHẦN THẬP PHÂN ═══
    phan_thap_phan_digits   TINYINT DEFAULT 0,      -- 0 = không có thập phân
    phan_thap_phan_color    VARCHAR(50),
    phan_thap_phan_background VARCHAR(50),

    -- ═══ QUY TẮC ĐỌC SỐ ═══
    quy_tac_lam_tron        TEXT,
    quy_tac_bo_sung         TEXT,

    -- ═══ CẤU HÌNH AI ═══
    la_mac_dinh             BOOLEAN DEFAULT FALSE,  
                            -- TRUE: dùng khi chưa xác định loại đồng hồ
    last_prompt_version     VARCHAR(10),            -- Version của prompt hiện tại
    last_prompt_txt         TEXT,                   -- Prompt mới nhất đang dùng
    last_llm_models         JSON,
                            -- [{"priority":1,"model_name":"gemini-2.5-flash-lite"},
                            --  {"priority":2,"model_name":"gemini-2.5-pro"},
                            --  {"priority":3,"model_name":"gemini-2.5-flash"}]

    -- ═══ TRẠNG THÁI ═══
    is_active               BOOLEAN DEFAULT TRUE,

    -- ═══ AUDIT ═══
    create_user             VARCHAR(100),
    create_time             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    edit_user               VARCHAR(100),
    edit_time               TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
                                       ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_model         (model_dong_ho),
    INDEX idx_active        (is_active),
    UNIQUE KEY uq_mac_dinh  (la_mac_dinh)
);
