<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\User;

class SignatureController extends Controller
{
    /**
     * Upload or draw a signature for a customer, driver, or user.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'type' => 'required|in:customer,driver,user',
            'id' => 'required|integer',
            'signature' => 'required', // Base64 string or file
        ]);

        $type = $request->input('type');
        $id = $request->input('id');
        $signature = $request->input('signature');

        // Get the model
        $model = $this->getModel($type, $id);
        if (!$model) {
            return response()->json(['success' => false, 'message' => __('Record not found')], 404);
        }

        try {
            // Check if it's a base64 string or uploaded file
            if ($request->hasFile('signature')) {
                // Handle file upload
                $file = $request->file('signature');
                $filename = "{$type}_{$id}_signature_" . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("public/signatures/{$type}s", $filename);
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

                $filename = "{$type}_{$id}_signature_" . time() . ".{$extension}";
                $directory = "public/signatures/{$type}s";

                // Ensure directory exists
                if (!Storage::exists($directory)) {
                    Storage::makeDirectory($directory);
                }

                $path = "{$directory}/{$filename}";
                Storage::put($path, $imageData);
                $storagePath = str_replace('public/', 'storage/', $path);
            }

            // Delete old signature if exists
            if ($model->signature_image) {
                $oldPath = str_replace('storage/', 'public/', $model->signature_image);
                if (Storage::exists($oldPath)) {
                    Storage::delete($oldPath);
                }
            }

            // Update the model
            $model->signature_image = $storagePath;
            $model->save();

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
     * Get current signature for a record.
     */
    public function get(Request $request)
    {
        $request->validate([
            'type' => 'required|in:customer,driver,user',
            'id' => 'required|integer',
        ]);

        $model = $this->getModel($request->input('type'), $request->input('id'));

        if (!$model) {
            return response()->json(['success' => false, 'message' => __('Record not found')], 404);
        }

        return response()->json([
            'success' => true,
            'signature_url' => $model->signature_image ? asset($model->signature_image) : null
        ]);
    }

    /**
     * Delete signature for a record.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'type' => 'required|in:customer,driver,user',
            'id' => 'required|integer',
        ]);

        $model = $this->getModel($request->input('type'), $request->input('id'));

        if (!$model) {
            return response()->json(['success' => false, 'message' => __('Record not found')], 404);
        }

        // Delete the file
        if ($model->signature_image) {
            $filePath = str_replace('storage/', 'public/', $model->signature_image);
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }
        }

        $model->signature_image = null;
        $model->save();

        return response()->json([
            'success' => true,
            'message' => __('Signature deleted successfully')
        ]);
    }

    /**
     * Get the appropriate model instance.
     */
    private function getModel(string $type, int $id)
    {
        return match ($type) {
            'customer' => Customer::find($id),
            'driver' => Driver::find($id),
            'user' => User::find($id),
            default => null,
        };
    }
}
