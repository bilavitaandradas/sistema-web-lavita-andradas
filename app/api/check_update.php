<?php

header('Content-Type: application/json');

echo json_encode([
    'version' => '1.0.4',
    'apk_url' => 'http://201.20.62.200/TCC/app/update/apk/tcc_app_v1.0.4.apk',
    
    'force' => true
]);