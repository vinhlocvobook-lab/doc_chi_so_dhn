<?php

namespace App\Models;

use Exception;

/**
 * Gemini API Client — sends image + prompt to Google Gemini,
 * returns parsed JSON result with token usage and cost (from DB pricing).
 */
class Gemini
{
    private $apiKey;
    private const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private const TY_GIA_USD_VND = 26380;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?: $_ENV['GOOGLE_API_KEY'] ?? '';
        if (empty($this->apiKey)) {
            throw new Exception('GOOGLE_API_KEY chưa được cấu hình trong .env');
        }
    }

    /**
     * Send image + prompt to Gemini API.
     *
     * @param string $imageInput  Local file path or URL
     * @param string $prompt      Prompt text
     * @param string $modelName   Gemini model name
     * @return array              ['data' => [...], 'error' => '', 'message' => '']
     */
    public function prompt_image(string $imageInput, string $prompt, string $modelName = 'gemini-2.5-flash'): array
    {
        // If URL → download to temp file
        $tempFile = null;
        if (preg_match('/^https?:\/\//', $imageInput)) {
            $imageData = @file_get_contents($imageInput);
            if ($imageData === false) {
                throw new Exception("Không thể tải ảnh từ URL: {$imageInput}");
            }
            $ext = pathinfo(parse_url($imageInput, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
            $tempFile = sys_get_temp_dir() . '/gemini_' . md5($imageInput . time()) . '.' . $ext;
            file_put_contents($tempFile, $imageData);
            $imagePath = $tempFile;
        } else {
            $imagePath = $imageInput;
        }

        if (!file_exists($imagePath)) {
            throw new Exception("File ảnh không tồn tại: {$imagePath}");
        }

        try {
            $base64 = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);

            $body = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64]]
                        ]
                    ]
                ]
            ];

            return $this->sendRequest($modelName, $body);
        } finally {
            // Cleanup temp file
            if ($tempFile && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * Parse markdown-wrapped JSON from Gemini response.
     */
    private function parseJsonResponse(string $text): ?array
    {
        $clean = str_replace(["```json\n", "\n```", "```json", "```"], '', $text);
        $clean = trim($clean);
        $parsed = json_decode($clean, true);
        // If Gemini returns just a number or string, wrap it
        if ($parsed !== null && !is_array($parsed)) {
            return ['chi_so' => (string) $parsed];
        }
        return $parsed;
    }

    /**
     * Get pricing from gemini_pricing table.
     */
    private function getPricingFromDB(string $modelName): array
    {
        $default = ['input_price' => 0, 'output_price' => 0];
        try {
            $db = \App\Core\Database::getInstance();
            // Try exact match first
            $stmt = $db->prepare("SELECT * FROM gemini_pricing WHERE model_name = ?");
            $stmt->execute([$modelName]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                // Try fuzzy match (e.g., model version "gemini-2.5-flash-preview-04-17" → base "gemini-2.5-flash")
                $stmt = $db->prepare("SELECT * FROM gemini_pricing WHERE ? LIKE CONCAT(model_name, '%') ORDER BY LENGTH(model_name) DESC LIMIT 1");
                $stmt->execute([$modelName]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            }

            if ($row) {
                // The prices in DB are USD per 1,000,000 tokens (unit_amount)
                return [
                    'input_price' => (float) ($row['input_price_low_context'] ?? 0),
                    'output_price' => (float) ($row['output_price_low_context'] ?? 0),
                ];
            }
        } catch (\Exception $e) {
            // fallback
        }
        return $default;
    }

    private function sendRequest(string $modelName, array $body): array
    {
        $kq = ['error' => '', 'message' => '', 'data' => []];

        $url = self::API_BASE_URL . "{$modelName}:generateContent?key={$this->apiKey}";
        $jsonBody = json_encode($body);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception('Lỗi cURL: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode >= 400) {
            $errorBody = json_decode($response, true);
            $errorMsg = $errorBody['error']['message'] ?? $response;
            throw new Exception("Lỗi HTTP {$httpCode}: " . $errorMsg);
        }

        $responseBody = json_decode($response, true);

        // Token usage
        $usage = $responseBody['usageMetadata'] ?? [];
        $promptTokens = $usage['promptTokenCount'] ?? 0;
        $outputTokens = $usage['candidatesTokenCount'] ?? 0;
        $thinkingTokens = $usage['thoughtsTokenCount'] ?? 0;
        $modelVersion = $responseBody['modelVersion'] ?? $modelName;

        // Cost from DB pricing
        $pricing = $this->getPricingFromDB($modelVersion);
        $costUSD = ($pricing['input_price'] * $promptTokens / 1e6)
            + ($pricing['output_price'] * ($outputTokens + $thinkingTokens) / 1e6);
        $costVND = $costUSD * self::TY_GIA_USD_VND;

        $kq['data'] = [
            'modelVersion' => $modelVersion,
            'prompt_tokens' => $promptTokens,
            'output_tokens' => $outputTokens,
            'thinking_tokens' => $thinkingTokens,
            'cost_usd' => $costUSD,
            'cost_vnd' => $costVND,
            'tygia' => self::TY_GIA_USD_VND,
        ];

        // Parse response content
        if (isset($responseBody['candidates'][0]['content']['parts'][0]['text'])) {
            $rawText = $responseBody['candidates'][0]['content']['parts'][0]['text'];
            $kq['data']['raw_response'] = $rawText;
            $kq['data']['finish_reason'] = $responseBody['candidates'][0]['finishReason'] ?? '';
            $kq['data']['content'] = $this->parseJsonResponse($rawText);
        } else {
            $kq['error'] = '1';
            $kq['message'] = 'Không nhận được nội dung hợp lệ từ API.';
            $kq['data']['raw_response'] = json_encode($responseBody);
        }

        return $kq;
    }
}
