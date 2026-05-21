<?php

namespace App\Http\Controllers\investor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class InvestorSignatureController extends Controller
{
    /**
     * Upload or draw a signature for the authenticated investor.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'signature' => 'required', // Base64 string or file
        ]);

        $signature = $request->input('signature');
        $investor = Auth::guard('web')->user();

        if (!$investor) {
            return response()->json(['success' => false, 'message' => __('Record not found')], 404);
        }

        try {
            // Check if it's a base64 string or uploaded file
            if ($request->hasFile('signature')) {
                // Handle file upload
                $file = $request->file('signature');
                $filename = "user_{$investor->id}_signature_" . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("public/signatures/users", $filename);
                $storagePath = str_replace('public/', 'storage/', $path);
            } else {
                // Handle base64 string (from canvas)
                $imageData = $signature;

                // Remove data URL prefix if present
                if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
                    $imageData = substr($imageData, strpos($imageData, ',') + 1);
                    $extension = $matches[1];
                } else {
                    $extension = 'png';
                }

                $imageData = base64_decode($imageData);

                if ($imageData === false) {
                    return response()->json(['success' => false, 'message' => __('Invalid signature data')], 400);
                }

                $filename = "user_{$investor->id}_signature_" . time() . ".{$extension}";
                $directory = "public/signatures/users";

                // Ensure directory exists
                if (!Storage::exists($directory)) {
                    Storage::makeDirectory($directory);
                }

                $path = "{$directory}/{$filename}";
                Storage::put($path, $imageData);
                $storagePath = str_replace('public/', 'storage/', $path);
            }

            // Delete old signature if exists
            if ($investor->signature_image) {
                $oldPath = str_replace('storage/', 'public/', $investor->signature_image);
                if (Storage::exists($oldPath)) {
                    Storage::delete($oldPath);
                }
            }

            // Update the model
            $investor->signature_image = $storagePath;
            $investor->save();

            return response()->json([
                'success' => true,
                'message' => __('Signature saved successfully'),
                'signature_url' => asset($storagePath)
            ]);

        } catch (\Exception $e) {
            \Log::error('Signature upload error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Error saving signature')], 500);
        }
    }

    /**
     * Get current signature for the authenticated investor.
     */
    public function get(Request $request)
    {
        $investor = Auth::guard('web')->user();

        if (!$investor) {
            return response()->json(['success' => false, 'message' => __('Record not found')], 404);
        }

        return response()->json([
            'success' => true,
            'signature_url' => $investor->signature_image ? asset($investor->signature_image) : null
        ]);
    }

    /**
     * Delete signature for the authenticated investor.
     */
    public function delete(Request $request)
    {
        $investor = Auth::guard('web')->user();

        if (!$investor) {
            return response()->json(['success' => false, 'message' => __('Record not found')], 404);
        }

        // Delete the file
        if ($investor->signature_image) {
            $filePath = str_replace('storage/', 'public/', $investor->signature_image);
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }
        }

        $investor->signature_image = null;
        $investor->save();

        return response()->json([
            'success' => true,
            'message' => __('Signature deleted successfully')
        ]);
    }
}
