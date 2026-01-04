<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firebase Integration Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .warning { color: #f39c12; }
        .section { margin: 20px 0; padding: 15px; border-left: 4px solid #3498db; background: #f8f9fa; }
        .file-check { margin: 10px 0; padding: 10px; background: #ecf0f1; border-radius: 5px; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔥 Firebase Integration Test</h1>

        <?php
        $baseDir = dirname(__DIR__);

        echo '<div class="section">';
        echo '<h2>1. ✅ ملفات النظام</h2>';

        $files = [
            'Driver App google-services.json' => 'safedest_driver/android/app/google-services.json',
            'Customer App google-services.json' => 'app_safedest_customer/android/app/google-services.json',
            'Firebase Service Class' => 'app/Services/FirebaseService.php',
            'Notification Service' => 'app/Services/NotificationService.php',
            'Test Controller' => 'app/Http/Controllers/TestFirebaseController.php',
            'Laravel .env' => '.env'
        ];

        foreach ($files as $name => $path) {
            $fullPath = $baseDir . '/' . $path;
            echo '<div class="file-check">';
            if (file_exists($fullPath)) {
                echo '<span class="success">✅ ' . $name . ': موجود</span>';
                if ($name === 'Laravel .env') {
                    $envContent = file_get_contents($fullPath);
                    if (strpos($envContent, 'FIREBASE_PROJECT_ID') !== false) {
                        echo ' <span class="success">(مُعد)</span>';
                    } else {
                        echo ' <span class="warning">(يحتاج إعداد)</span>';
                    }
                }
            } else {
                echo '<span class="error">❌ ' . $name . ': مفقود</span>';
            }
            echo '</div>';
        }
        echo '</div>';

        echo '<div class="section">';
        echo '<h2>2. 🔧 إعدادات Firebase</h2>';

        $envPath = $baseDir . '/.env';
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            $firebaseVars = [
                'FIREBASE_PROJECT_ID' => 'معرف المشروع',
                'FIREBASE_CREDENTIALS' => 'مسار ملف الاعتماد',
                'FCM_SERVER_KEY' => 'مفتاح الخادم'
            ];

            foreach ($firebaseVars as $var => $desc) {
                echo '<div class="file-check">';
                if (strpos($envContent, $var) !== false) {
                    // Extract value
                    preg_match('/' . $var . '=(.*)/', $envContent, $matches);
                    $value = isset($matches[1]) ? trim($matches[1]) : '';
                    if (!empty($value) && $value !== 'your-value-here') {
                        echo '<span class="success">✅ ' . $desc . ': مُعد</span>';
                    } else {
                        echo '<span class="warning">⚠️ ' . $desc . ': يحتاج قيمة</span>';
                    }
                } else {
                    echo '<span class="error">❌ ' . $desc . ': مفقود</span>';
                }
                echo '</div>';
            }
        }
        echo '</div>';

        echo '<div class="section">';
        echo '<h2>3. 🔑 ملف اعتماد Laravel (Service Account)</h2>';
        echo '<p>هذا الملف هو الذي يسمح للوحة التحكم (Laravel) بإرسال الإشعارات عبر Firebase.</p>';

        $credentialsPath = $baseDir . '/storage/firebase/service-account-key.json';
        echo '<div class="file-check">';
        if (file_exists($credentialsPath)) {
            echo '<span class="success">✅ ملف الاعتماد: موجود</span><br>';
            $content = file_get_contents($credentialsPath);
            $json = json_decode($content, true);
            if ($json && isset($json['project_id'])) {
                echo '<span class="success">✅ صيغة JSON: صحيحة</span><br>';
                echo '<span class="success">📝 معرف المشروع في الملف: ' . htmlspecialchars($json['project_id']) . '</span>';
            } else {
                echo '<span class="error">❌ صيغة JSON: غير صحيحة أو الملف تالف</span>';
            }
        } else {
            echo '<span class="error">❌ ملف الاعتماد: مفقود</span><br>';
            echo '<span class="warning">📝 المسار المطلوب: storage/firebase/service-account-key.json</span>';
            echo '<br><br><a href="https://console.firebase.google.com/project/_/settings/serviceaccounts/adminsdk" target="_blank" class="btn">احصل على الملف من هنا</a>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="section">';
        echo '<h2>4. 📱 إعدادات Flutter</h2>';

        $pubspecPath = $baseDir . '/safedest_driver/pubspec.yaml';
        if (file_exists($pubspecPath)) {
            $pubspecContent = file_get_contents($pubspecPath);
            $packages = [
                'firebase_core' => 'Firebase Core',
                'firebase_messaging' => 'Firebase Messaging',
                'flutter_local_notifications' => 'Local Notifications'
            ];

            foreach ($packages as $package => $name) {
                echo '<div class="file-check">';
                if (strpos($pubspecContent, $package . ':') !== false && strpos($pubspecContent, '# ' . $package) === false) {
                    echo '<span class="success">✅ ' . $name . ': مُفعل</span>';
                } else {
                    echo '<span class="error">❌ ' . $name . ': معطل أو مفقود</span>';
                }
                echo '</div>';
            }
        }
        echo '</div>';

        // Overall status
        $allGood = true;
        $issues = [];

        if (!file_exists($baseDir . '/safedest_driver/android/app/google-services.json')) {
            $issues[] = 'ملف google-services.json للسائق مفقود';
            $allGood = false;
        }

        if (!file_exists($credentialsPath)) {
            $issues[] = 'ملف اعتماد Firebase مفقود';
            $allGood = false;
        }

        if (!file_exists($envPath) || !strpos(file_get_contents($envPath), 'FIREBASE_PROJECT_ID')) {
            $issues[] = 'إعدادات Firebase في .env مفقودة';
            $allGood = false;
        }

        echo '<div class="section">';
        if ($allGood) {
            echo '<h2 class="success">🎉 النظام جاهز!</h2>';
            echo '<p class="success">جميع المكونات الأساسية موجودة ومُعدة بشكل صحيح.</p>';
            echo '<h3>🚀 الخطوات التالية:</h3>';
            echo '<ol>';
            echo '<li>تشغيل تطبيق Flutter: <code>flutter run</code></li>';
            echo '<li>تسجيل الدخول كسائق لتسجيل FCM token</li>';
            echo '<li>اختبار الإشعارات من لوحة التحكم</li>';
            echo '</ol>';
        } else {
            echo '<h2 class="error">⚠️ يحتاج إعداد</h2>';
            echo '<p class="warning">بعض المكونات مفقودة:</p>';
            echo '<ul>';
            foreach ($issues as $issue) {
                echo '<li class="error">' . $issue . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        ?>

        <div class="section">
            <h2>🔗 روابط الاختبار</h2>
            <a href="/safedestsss/public/test-firebase/connection" class="btn">اختبار الاتصال</a>
            <a href="/safedestsss/public/test-firebase/drivers-with-tokens" class="btn">عرض السائقين</a>
            <a href="#" onclick="testNotification()" class="btn">اختبار إشعار</a>
        </div>
    </div>

    <script>
        function testNotification() {
            alert('سيتم إضافة اختبار الإشعارات قريباً!');
        }
    </script>
</body>
</html>
