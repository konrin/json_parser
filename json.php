<?php

define('JSON_VAL_TYPE', [
    "STRING" => 0,
    "OBJECT" => 1,
    "ARRAY" => 2
]);

define('JSON_STEP', [
    "BEFORE_KEY" => 0,
    "KEY" => 1,
    "AFTER_KEY" => 2,
    "BEFORE_VAL" => 3,
    "VAL" => 4
]);

define('JSON_PARSE_TYPE', [
    'OBJECT' => 0,
    'ARRAY' => 1
]);

/**
 * JSON parser
 *
 * @param string $json String in JSON format
 * @param bool $is_loop
 * @return void
 */
function user_json_decode(string $json, bool $is_loop = false)
{
    $json = trim($json);
    $json_len = strlen($json);

    if ($json_len == 0) {
        throw new Exception('JSON empty');
    }

    if (is_numeric($json)) {
        return (int) $json;
    }

    if (array_search($json, ['true', 'false']) > -1) {
        return $json === "true";
    }

    if ($json === 'null') {
        return null;
    }

    $flags = [
        "step" => JSON_STEP["BEFORE_KEY"],
        "val_type" => JSON_VAL_TYPE["STRING"],
        "parse_type" => JSON_PARSE_TYPE["OBJECT"],
        "open_brackets" => 0,
        "current_key" => null,
        "buff_empty" => true
    ];

    $buffer = "";
    $struct = [];
    $struct_length = 0;

    $setBuffer = function ($val) use (&$buffer, &$flags) {
        if ($val === null) {
            $buffer = '';
            $flags['buff_empty'] = true;
        }

        if ($flags['buff_empty'] || strlen($val) > 0) {
            $flags['buff_empty'] = false;
        }

        $buffer = $buffer . $val;
    };

    $beforeVal = function (int $code, int $index) use ($setBuffer, &$val, &$flags) {
        switch ($code) {
            case 32: //  пробел
                break;
            default:
                if (array_search($code, [44]) > -1) {
                    break;
                }

                $flags['step'] = JSON_STEP['VAL'];

                if (array_search($code, [123, 91]) > -1) {
                    $flags["open_brackets"] = 1;
                    $setBuffer(chr($code));
                }

                switch ($code) {
                    case 123: // {
                        $flags["val_type"] = JSON_VAL_TYPE["OBJECT"];
                        break;
                    case 91: // [
                        $flags["val_type"] = JSON_VAL_TYPE["ARRAY"];
                        break;
                    default:
                        $flags["val_type"] = JSON_VAL_TYPE["STRING"];

                        if ($code !== 34) {
                            $val($code, $index);
                        }
                        
                        break;
                }
        }
    };

    $val = function (int $code, int $index) use ($setBuffer, &$flags, &$struct_length, &$struct, &$buffer) {
        $close = function () use (&$setBuffer, &$flags, &$struct, &$buffer) {
            if ($flags['parse_type'] === JSON_PARSE_TYPE["OBJECT"]) {
                $struct[trim($flags['current_key'])] = user_json_decode($buffer, true);
                $flags['step'] = JSON_STEP['BEFORE_KEY'];
                $flags['current_key'] = null;
            } else {
                $struct[] = user_json_decode($buffer, true);
                $flags['step'] = JSON_STEP['BEFORE_VAL'];
            }

            $flags['val_type'] = JSON_VAL_TYPE["STRING"];
            $setBuffer(null);
        };

        if ($flags["val_type"] !== JSON_VAL_TYPE["STRING"]) {
            $setBuffer(chr($code));

            switch ($code) {
                case 123: // {
                case 91: // [
                    $flags["open_brackets"]++;
                    break;
                case 125: // }
                case 93: // ]
                    $flags["open_brackets"]--;
                    break;
            }

            if ($flags["open_brackets"] === 0) {
                $close();
            }

            return;
        }

        $isEnd = ($struct_length - 1) === $index;

        if ($code === 34 && !$isEnd) {
            return;
        }

        if ($code !== 34 && $isEnd) {
            $setBuffer(chr($code));
        }


        if ($code === 44 || $isEnd) { // ,
            $close();

            return;
        }

        $setBuffer(chr($code));
    };
    
    if (($json[0] === '{' && $json[$json_len - 1] === '}')) {
        $str = substr($json, 1, $json_len - 2);
        
        $struct_length = strlen($str);

        $beforeKey = function (int $code) use (&$flags) {
            switch ($code) {
                case 32: //  пробел
                case 44: // ,
                    break;
                case 34: // "
                    $flags['step'] = JSON_STEP["KEY"];
                    break;
                default:
                    throw new Exception('JSON invalid');
            }
        };

        $key = function (int $code) use (&$flags, &$buffer, &$setBuffer) {
            if (!$flags['buff_empty'] && $code === 34) {
                $flags['current_key'] = $buffer;
                $flags['step'] = JSON_STEP["AFTER_KEY"];
                $setBuffer(null);

                return;
            }

            $setBuffer(chr($code));
        };

        $afterKey = function (int $code) use (&$flags) {
            switch ($code) {
                case 32:
                    break;
                case 58: // :
                    $flags['step'] = JSON_STEP['BEFORE_VAL'];
                    break;
                default:
                    throw new Exception('JSON invalid');
            }
        };

        for ($i=0; $i < $struct_length; $i++) {
            $code = ord($str[$i]); // "key"   : "dfg"

            switch ($flags['step']) {
                case JSON_STEP["BEFORE_KEY"]:
                    $beforeKey($code);
                    break;
                case JSON_STEP["KEY"]:
                    $key($code);
                    break;
                case JSON_STEP["AFTER_KEY"]:
                    $afterKey($code);
                    break;
                case JSON_STEP["BEFORE_VAL"]:
                    $beforeVal($code, $i);
                    break;
                case JSON_STEP["VAL"]:
                    $val($code, $i);
                    break;
                default:
                    throw new Exception('JSON invalid');
            }
        }

        return $struct;
    }

    if (($json[0] === '[' && $json[$json_len - 1] === ']')) {
        $str = substr($json, 1, $json_len - 2);
        
        $struct_length = strlen($str);

        $flags['parse_type'] = JSON_PARSE_TYPE['ARRAY'];
        $flags['step'] = JSON_STEP["BEFORE_VAL"];

        for ($i=0; $i < $struct_length; $i++) {
            $code = ord($str[$i]);
            
            switch ($flags['step']) {
                case JSON_STEP["BEFORE_VAL"]:
                    $beforeVal($code, $i);
                    break;
                case JSON_STEP['VAL']:
                    $val($code, $i);
                    break;
                default:
                    throw new Exception('JSON invalid');
            }
        }

        return $struct;
    }

    if (!$is_loop) {
        throw new Exception('JSON invalid');
    }
    
    return $json;
}
