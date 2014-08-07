<?php
namespace Hand\Helpers;

class Secure {
    public static function randomString($length = 8) {
        $characters = "abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ+-*#&@!?";
        $char_length = strlen($characters);

        $result = [];

        for ($i=0; $i<$length; $i++) {
            $index = mt_rand(0, $char_length - 1);
            $result[] = $characters[$index];
        }

        return implode("", $result);
    }

    public static function createKey($user_id, $signin_token, $cookie_secret_key) {
        $user_id      = dechex($user_id);
        $signin_token = hash('sha256', $signin_token.$cookie_secret_key);
        $auth_key     = hash('sha256', $user_id.$signin_token.$cookie_secret_key);
        $auth_string  = static::makeAuth("$user_id:$signin_token:$auth_key");
        return $auth_string;
    }

    public static function makeAuth($string, $operation = 'ENCODE') {
        $string = $operation == 'DECODE' ? base64_decode($string) : base64_encode($string);
        return $string;
    }

    public static function shortUsername($input) {
        $chars = 'abcdefghijklmnopqrstuxyvwz0123456789_';

        $encrypt_string        = hash('sha256', $input);
        $encrypt_string_length = strlen($encrypt_string);
        $encrypt_string_times  = $encrypt_string_length / 8;

        $output = [];

        for ($i=0; $i<$encrypt_string_times; $i++) {
            $encrypt_string_sub = substr($encrypt_string, $i * 8, 8);
            $encrypt_string_int = 0x3FFFFFFF & (1 * ('0x'.$encrypt_string_sub));

            $output_char = '';

            for ($j=0; $j<6; $j++) {
                $result_value = 0x0000001F & $encrypt_string_int;
                $output_char .= $chars{$result_value};

                $encrypt_string_int = $encrypt_string_int >> 5;
            }

            $output[] = $output_char;
        }

        return $output;
    }
}
