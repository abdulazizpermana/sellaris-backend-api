<?php

namespace App\Services;

use App\Models\AiContent;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiContentService
{
    private string $apiKey;
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key');

        if (empty($this->apiKey)) {
            throw new \Exception('Gemini API key belum diatur. Cek file .env dan config/services.php');
        }
    }

    public function generateContent(Product $product, string $type = 'caption'): AiContent
    {
        $prompt = $this->buildPrompt($product, $type);
        $rawResponse = $this->callGeminiApi($prompt);
        $content = $this->parseResponse($rawResponse);

        return AiContent::updateOrCreate(
            [
                'product_id' => $product->id,
                'type'       => $type,
            ],
            [
                'user_id'          => $product->user_id,
                'generated_content' => $content,
            ]
        );
    }

    private function buildPrompt(Product $product, string $type): string
    {
        $price = number_format($product->price, 0, ',', '.');
        $description = trim((string) ($product->description ?? ''));
        $targetMarket = trim((string) ($product->target_market ?? 'UMKM lokal Indonesia'));

        if ($product->product_name === null || trim((string) $product->product_name) === '') {
            throw new \InvalidArgumentException('Nama produk belum tersedia.');
        }

        $hasCoreData = ! empty($description) || ! empty($targetMarket) || ! empty($price);
        if (! $hasCoreData) {
            throw new \InvalidArgumentException(
                'Produk belum memiliki data dasar untuk membuat konten AI.'
            );
        }

        return match ($type) {
            'caption' => <<<PROMPT
Anda adalah AI marketing assistant untuk UMKM Indonesia.

Buatkan caption Instagram yang menarik dan persuasif dalam 1-2 kalimat dengan emoji.

Produk: {$product->product_name}
Harga: Rp{$price}
Target Market: {$targetMarket}
Deskripsi: {$description}

Balas hanya dengan teks caption tanpa penjelasan tambahan.
PROMPT,
            'marketplace' => <<<PROMPT
Anda adalah AI marketing assistant untuk UMKM Indonesia.

Buatkan deskripsi marketplace profesional dan persuasif untuk produk berikut:

Produk: {$product->product_name}
Harga: Rp{$price}
Target Market: {$targetMarket}
Deskripsi: {$description}

Tulis minimal 5 kalimat, sertakan bullet point keunggulan produk, dan jangan sertakan markdown.
PROMPT,
            'hashtag' => <<<PROMPT
Anda adalah AI marketing assistant untuk UMKM Indonesia.

Buatkan daftar hashtag pemasaran untuk produk berikut. Hasilkan 8-10 hashtag relevan.

Produk: {$product->product_name}
Target Market: {$targetMarket}
Deskripsi: {$description}

Balas hanya dengan hashtag yang dipisahkan oleh spasi.
PROMPT,
            'promo' => <<<PROMPT
Anda adalah AI marketing assistant untuk UMKM Indonesia.

Buatkan teks promo singkat untuk WhatsApp atau story, maksimal 2 kalimat.

Produk: {$product->product_name}
Harga: Rp{$price}
Target Market: {$targetMarket}

Balas hanya dengan teks promo tanpa penjelasan tambahan.
PROMPT,
            'smart_reply' => <<<PROMPT
Anda adalah AI customer service assistant untuk UMKM Indonesia.

Buatkan balasan cerdas kepada calon pembeli yang menanyakan tentang produk berikut.

Produk: {$product->product_name}
Harga: Rp{$price}
Target Market: {$targetMarket}
Deskripsi: {$description}

Balas sebagai respons ramah dan persuasif, hanya teks jawaban.
PROMPT,
            'translate' => <<<PROMPT
Anda adalah AI translator untuk konten pemasaran UMKM.

Terjemahkan deskripsi produk berikut ke dalam bahasa Inggris dengan tone persuasif.

Produk: {$product->product_name}
Harga: Rp{$price}
Target Market: {$targetMarket}
Deskripsi: {$description}

Balas hanya dengan hasil terjemahan tanpa penjelasan tambahan.
PROMPT,
            default => throw new \InvalidArgumentException('Jenis konten AI tidak didukung.'),
        };
    }

    private function callGeminiApi(string $prompt): string
    {
        $response = Http::timeout(60)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($this->apiUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,   // ← hanya 'text', tidak ada 'type'
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature'     => 0.8,
                    'maxOutputTokens' => 512,
                ],
            ]);

        if ($response->failed()) {
            Log::error('Gemini API error', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);

            throw new \Exception('Gagal menghubungi Gemini AI service.');
            throw new \Exception('Gemini Error: ' . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text', $response->body());
    }

    private function parseResponse(string $raw): string
    {
        $cleaned = preg_replace('/```(?:json)?\s*|\s*```/', '', $raw);
        $cleaned = trim($cleaned);

        return $cleaned;
    }
}
