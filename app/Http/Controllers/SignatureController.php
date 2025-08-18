<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SignitService;

class SignatureController extends Controller
{
    public function testOAuth(SignitService $signit)
    {
        $result = $signit->getAccessToken();
        return response()->json($result);
    }


    public function testSignatureRequest(SignitService $signit)
    {
        try {
            // مسار ملف PDF للتجربة
            $filePath = storage_path('app/documents/test.pdf');

            // بيانات الموقّع
            $signerEmail = 'osama.samomy@gmail.com';
            $signerName = 'John Doe';
            $customFields = [
                'employee_id' => '12345',
                'priority' => 'Medium'
            ];

            // إرسال الطلب
            $response = $signit->createSignatureRequest($filePath, $signerEmail, $signerName, $customFields);

            return response()->json([
                'status' => 'success',
                'data' => $response
            ]);

        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function sendSignature(SignitService $signit)
    {
        $result = $signit->createSignatureRequest(
            storage_path('app/documents/test.pdf'), // مسار الملف
            'osama.samomy@gmail.com.com', // بريد الموقّع
            'Osama Samomy' // اسم الموقّع
        );

        return response()->json($result);
    }

    public function checkStatus(SignitService $signit, $id)
    {
        $status = $signit->getSignatureStatus($id);

        return response()->json($status);
    }
}
