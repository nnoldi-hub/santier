<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InvoiceOcrService
{
    public function extractText(string $absoluteFilePath): array
    {
        $driver = (string) config('services.invoice_ocr.driver', 'mock');

        if ($driver === 'ocrspace') {
            return $this->extractWithOcrSpace($absoluteFilePath);
        }

        return [
            'provider' => 'mock',
            'text' => '',
            'confidence' => 0.0,
        ];
    }

    private function extractWithOcrSpace(string $absoluteFilePath): array
    {
        $apiKey = (string) config('services.invoice_ocr.ocrspace_api_key', '');

        if ($apiKey === '' || !is_file($absoluteFilePath)) {
            return [
                'provider' => 'ocrspace',
                'text' => '',
                'confidence' => 0.0,
            ];
        }

        try {
            $response = Http::timeout(25)
                ->asMultipart()
                ->attach('file', file_get_contents($absoluteFilePath), basename($absoluteFilePath))
                ->post('https://api.ocr.space/parse/image', [
                    'apikey' => $apiKey,
                    'language' => 'eng',
                    'isOverlayRequired' => 'false',
                    'OCREngine' => '2',
                ]);

            if (!$response->ok()) {
                return [
                    'provider' => 'ocrspace',
                    'text' => '',
                    'confidence' => 0.0,
                ];
            }

            $json = $response->json();
            $results = $json['ParsedResults'] ?? [];
            $text = '';

            foreach ($results as $item) {
                $text .= "\n" . (string) ($item['ParsedText'] ?? '');
            }

            return [
                'provider' => 'ocrspace',
                'text' => trim($text),
                'confidence' => trim($text) !== '' ? 0.75 : 0.0,
            ];
        } catch (\Throwable $e) {
            Log::warning('Invoice OCR failed', ['message' => $e->getMessage()]);

            return [
                'provider' => 'ocrspace',
                'text' => '',
                'confidence' => 0.0,
            ];
        }
    }
}
