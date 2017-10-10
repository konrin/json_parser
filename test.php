<?php

include 'json.php';

function generate_json($row_count, $loop_count)
{
    $json_struct = [
        'key1' => 'value1',
        'items' => [
            'item1' => [1,2,3,4,5,6,true,-1,false,[],"string"],
            'item2' => 'value 2',
            'item3' => 'value3',
            'item4' => true,
            'item5' => false,
            'item5' => "string"
        ]
    ];

    $loop = function ($count) use ($json_struct, &$loop) {
        if ($count < 2) {
            return $json_struct;
        }

        $json_struct['loop' . $count] = $loop($count - 1);

        return $json_struct;
    };

    $json_struct = $loop($loop_count);

    $rows = function ($count) use ($json_struct) {
        if ($count < 2) {
            return $json_struct;
        }

        $data = [];

        for ($i=0; $i < $count; $i++) {
            $data['row'.$i] = $json_struct;
        }

        return $data;
    };

    return json_encode($rows($row_count));
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' ГБ';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' МБ';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' КБ';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' байт';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' байт';
    } else {
        $bytes = '0 байт';
    }

    return $bytes;
}

$json = generate_json(array_key_exists(1, $argv) ? $argv[1] : 1, array_key_exists(2, $argv) ? $argv[2] : 1);

$u_m = memory_get_usage();
$u_start = microtime(true);
$user_decode = user_json_decode($json);
$u_time = microtime(true) - $u_start;
$u_m_r = memory_get_usage() - $u_m;

unset($u_start, $user_decode);

$m = memory_get_usage();
$start = microtime(true);
$decode = json_decode($json);
$time = microtime(true) - $start;
$m_r = memory_get_usage() - $m;

unset($start, $decode);

printf("json_decode %.4F сек %s" . PHP_EOL, $time, formatBytes($m_r));
printf("user_json_decode %.4F сек %s" . PHP_EOL, $u_time, formatBytes($u_m_r));
