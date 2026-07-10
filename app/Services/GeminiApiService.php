<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiApiService
{
    /**
     * Call Gemini API with an image and a prompt to extract JSON data.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @param string $prompt
     * @return array|null The extracted JSON array or null on failure.
     */
    public function extractDataFromImage($image, string $prompt): ?array
    {
        $apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        
        if (empty($apiKey)) {
            Log::error('Gemini API key is not set.');
            throw new Exception('Gemini API key is missing.');
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

        // Ensure we explicitly ask for JSON response format in the prompt
        $fullPrompt = $prompt . "\n\n" .
            "CRITICAL INSTRUCTIONS:\n" .
            "1. You MUST return ONLY a valid JSON object. Do NOT include markdown tags like ```json or any text outside the JSON.\n" .
            "2. If you need to return an error message to the user (as requested in the prompt), you MUST return a JSON object with a single key 'error'. Example: {\"error\": \"Your error message here\"}\n" .
            "3. Read ALL digits and characters with extreme precision - pay special attention to digits like 0,1,4,6,8,9 that may look similar.\n" .
            "4. For numbers and IDs, read character by character and include EVERY digit without skipping any.\n" .
            "5. If a field value is NOT found or unclear in the document, set it to null - do NOT guess.\n" .
            "6. Return exact values as they appear in the document, without modification.";

        $base64Image = base64_encode(file_get_contents($image->getRealPath()));
        $mimeType = $image->getMimeType();

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $fullPrompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Image
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
            ]
        ];

        try {
            $response = Http::timeout(30)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $responseText = $data['candidates'][0]['content']['parts'][0]['text'];
                    
                    // Clean up potential markdown formatting
                    $responseText = str_replace(['```json', '```'], '', $responseText);
                    $responseText = trim($responseText);
                    
                    $jsonResult = json_decode($responseText, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $jsonResult;
                    } else {
                        Log::error('Gemini API returned invalid JSON', ['response' => $responseText]);
                    }
                }
            } else {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (Exception $e) {
            Log::error('Gemini API exception', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
