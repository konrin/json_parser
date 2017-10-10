<?php

if (count($argv) < 2 || strlen($argv[1]) === 0) {
    echo (
        "JSON parser" . PHP_EOL
        . "Usage: cli.php [json source] [path]" . PHP_EOL
        . "Example: cli.php '{\"key1\":{\"key2\":true}}' key1.key2" . PHP_EOL
    );

    return;
}

include 'json.php';

try {
    $struct = user_json_decode($argv[1]);

    do {
        if (count($argv) !== 3 || !is_string($argv[2])) {
            break;
        }

        $keys = explode('.', $argv[2]);
        
        if (count($keys) === 0) {
            break;
        }
        
        foreach ($keys as $key) {
            if (strlen($key) === 0) {
                continue;
            }
                    
            if (!array_key_exists($key, $struct)) {
                throw new Exception('Path error');
            }
        
            $struct = $struct[$key];
        }
    } while (0);

    echo json_encode($struct) . PHP_EOL;
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}
