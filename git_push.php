<?php

/**
 * Script tự động Git Push
 * Cách dùng: php git_push.php "Nội dung commit của bạn"
 */
//alias gpush='git add . && git commit -m "update" && git push'
//php git_push.php "gemini cost"

// 1. Lấy nội dung commit từ tham số dòng lệnh hoặc mặc định
$commitMessage = $argv[1] ?? "Update: " . date("Y-m-d H:i:s");

echo "🚀 Bắt đầu quá trình đẩy code lên GitHub...\n";

// 2. Chạy các lệnh Git
// git add .
echo "--- Đang add files... \n";
shell_exec("git add .");

// git commit -m "message"
echo "--- Đang commit với nội dung: '$commitMessage'... \n";
$commitOutput = shell_exec("git commit -m " . escapeshellarg($commitMessage));
echo $commitOutput . "\n";

// git push
echo "--- Đang push lên GitHub... \n";
$pushOutput = shell_exec("git push 2>&1"); // 2>&1 để bắt được cả thông báo lỗi nếu có
echo $pushOutput . "\n";

echo "✅ Hoàn thành!\n";