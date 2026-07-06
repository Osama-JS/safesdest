<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Form_Field;
use App\Services\GeminiApiService;
use Illuminate\Support\Facades\Log;

class DriverOcrController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiApiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Extract data from a document image using Gemini API based on field prompt.
     */
    public function extract(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'field_id' => 'required|exists:form_fields,id'
        ]);

        try {
            $field = Form_Field::findOrFail($request->field_id);

            // Check if this field actually has an OCR prompt
            if (empty($field->ocr_prompt)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This field does not support OCR extraction.'
                ], 400);
            }

            $image = $request->file('image');
            
            // Extract data using Gemini
            $extractedData = $this->geminiService->extractDataFromImage($image, $field->ocr_prompt);

            if ($extractedData !== null) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data extracted successfully',
                    'data' => $extractedData
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to extract data from image or image is unclear.'
                ], 422);
            }

        } catch (\Exception $e) {
            Log::error('OCR Extraction Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during OCR extraction.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
