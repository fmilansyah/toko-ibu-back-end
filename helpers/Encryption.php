<?php
class Encryption {
    const ENCRYPTION_METHOD = 'AES-128-CBC';
    const KEY = 'tokoibu';

    public static function encrypt($text = null) {
        if (!empty($text)) {
            $key = self::KEY;
            $plaintext = $text;
            $ivlen = openssl_cipher_iv_length($cipher = self::ENCRYPTION_METHOD);
            $iv = openssl_random_pseudo_bytes($ivlen);
            $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
            $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
            $ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);
            return str_replace(['+', '/', '='], ['xMl3Jk', 'Por21Ld', 'Ml32'], $ciphertext);
        }
        return null;
    }
    public static function decrypt($encText = null) {
        if (!empty($encText)) {
            try {
                $key = self::KEY;
                $encTextReplaced = str_replace(['xMl3Jk', 'Por21Ld', 'Ml32'], ['+', '/', '='], $encText);
                $c = base64_decode($encTextReplaced);
                $ivlen = openssl_cipher_iv_length($cipher = self::ENCRYPTION_METHOD);
                $iv = substr($c, 0, $ivlen);
                $hmac = substr($c, $ivlen, $sha2len = 32);
                $ciphertext_raw = substr($c, $ivlen + $sha2len);
                $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
                $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
                if (hash_equals($hmac, $calcmac))
                {
                    return $original_plaintext;
                }
            } catch(\Throwable $e) {
                return null;
            }
        }
        return null;
    }
}