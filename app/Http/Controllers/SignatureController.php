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


    public function sendSignature(SignitService $signit)
    {
        $result = $signit->createSignatureRequest(
            storage_path('app/documents/test.pdf'), // مسار الملف
            'client@example.com', // بريد الموقّع
            'محمد أحمد' // اسم الموقّع
        );

        return response()->json($result);
    }

    public function checkStatus(SignitService $signit, $id)
    {
        $status = $signit->getSignatureStatus($id);

        return response()->json($status);
    }
}
