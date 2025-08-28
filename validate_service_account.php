<?php

echo "🔑 Firebase Service Account Key Validator\n";
echo str_repeat("=", 50) . "\n";

$credentialsPath = 'storage/firebase/service-account-key.json';

echo "1. Checking file existence...\n";
if (file_exists($credentialsPath)) {
    echo "   ✅ File exists: {$credentialsPath}\n";
    
    echo "\n2. Checking file size...\n";
    $fileSize = filesize($credentialsPath);
    echo "   📊 File size: {$fileSize} bytes\n";
    
    if ($fileSize > 0) {
        echo "   ✅ File is not empty\n";
        
        echo "\n3. Validating JSON format...\n";
        $content = file_get_contents($credentialsPath);
        $json = json_decode($content, true);
        
        if ($json === null) {
            echo "   ❌ Invalid JSON format\n";
            echo "   📝 JSON Error: " . json_last_error_msg() . "\n";
        } else {
            echo "   ✅ Valid JSON format\n";
            
            echo "\n4. Checking required fields...\n";
            $requiredFields = [
                'type' => 'Service account type',
                'project_id' => 'Project ID',
                'private_key_id' => 'Private key ID',
                'private_key' => 'Private key',
                'client_email' => 'Client email',
                'client_id' => 'Client ID',
                'auth_uri' => 'Auth URI',
                'token_uri' => 'Token URI'
            ];
            
            $allFieldsPresent = true;
            foreach ($requiredFields as $field => $description) {
                if (isset($json[$field]) && !empty($json[$field])) {
                    echo "   ✅ {$description}: Present\n";
                } else {
                    echo "   ❌ {$description}: Missing or empty\n";
                    $allFieldsPresent = false;
                }
            }
            
            if ($allFieldsPresent) {
                echo "\n🎉 SUCCESS: Service account key is valid!\n";
                echo "📝 Project ID: " . $json['project_id'] . "\n";
                echo "📝 Client Email: " . $json['client_email'] . "\n";
                
                echo "\n✅ You can now run the full test:\n";
                echo "   php simple_test.php\n";
                
            } else {
                echo "\n❌ INVALID: Some required fields are missing\n";
                echo "🔧 Please download a new service account key from Firebase Console\n";
            }
        }
    } else {
        echo "   ❌ File is empty\n";
    }
    
} else {
    echo "   ❌ File not found: {$credentialsPath}\n";
    echo "\n🔧 HOW TO GET THE FILE:\n";
    echo "1. Go to https://console.firebase.google.com/\n";
    echo "2. Select your project\n";
    echo "3. Go to Project Settings > Service Accounts\n";
    echo "4. Click 'Generate new private key'\n";
    echo "5. Download the JSON file\n";
    echo "6. Rename it to 'service-account-key.json'\n";
    echo "7. Place it in: {$credentialsPath}\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎯 Validation completed!\n";
