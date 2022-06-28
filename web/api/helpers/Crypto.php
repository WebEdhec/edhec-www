<?php

/**
 * @package   Edhec Connector API
 * @author Aurone <info@aurone.com>
 * @copyright Copyright (c)2007-2022 Aurone <https://aurone.com>
 * @license   GNU General Public License version 3, or later
 *
 * Helper functions
 */
class EdhecCrypto
{
    private static $_secretKey = "__^%&Q@$&*!@#$%^&*^__";

    /**
     * @param string $string
     *
     * @return string
     */
    public static function encrypt($string)
    {
        $cipher = 'AES-256-CBC';
        $options = OPENSSL_RAW_DATA;
        $hash_algo = 'sha256';
        $sha2len = 32;
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($string, $cipher, self::$_secretKey, $options, $iv);
        $hmac = hash_hmac($hash_algo, $ciphertext_raw, self::$_secretKey, true);

        $encrypted = $iv . $hmac . $ciphertext_raw;

        return base64_encode($encrypted);
    }

    /**
     * @param string $encryptedString
     *
     * @return string
     */
    public static function decrypt($encryptedString)
    {
        $encryptedString = base64_decode($encryptedString);

        $cipher = 'AES-256-CBC';
        $options = OPENSSL_RAW_DATA;
        $hash_algo = 'sha256';
        $sha2len = 32;
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($encryptedString, 0, $ivlen);
        $hmac = substr($encryptedString, $ivlen, $sha2len);
        $ciphertext_raw = substr($encryptedString, $ivlen + $sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, self::$_secretKey, $options, $iv);
        $calcmac = hash_hmac($hash_algo, $ciphertext_raw, self::$_secretKey, true);

        if (function_exists('hash_equals')) {
            if (hash_equals($hmac, $calcmac)) {
                return $original_plaintext;
            }

        } else {
            if (self::_hashEqualsCustom($hmac, $calcmac)) {
                return $original_plaintext;
            }

        }
    }

    /**
     * Generate easy password
     *
     * @param integer $length Length you want from 1 to 32
     *
     * @return string
     */
    public static function generateEasyPassword($length = 8)
    {
        return substr(
            md5(time() . rand(0, PHP_INT_MAX) . time()),
            0,
            $length
        );
    }

    /**
     * (Optional)
     * hash_equals() function polyfilling.
     * PHP 5.6+ timing attack safe comparison
     *
     * @param string $knownString
     * @param string $userString
     *
     * @return boolean
     */
    private static function _hashEqualsCustom($knownString, $userString)
    {
        if (function_exists('mb_strlen')) {
            $kLen = mb_strlen($knownString, '8bit');
            $uLen = mb_strlen($userString, '8bit');
        } else {
            $kLen = strlen($knownString);
            $uLen = strlen($userString);
        }

        if ($kLen !== $uLen) {
            return false;
        }

        $result = 0;

        for ($i = 0; $i < $kLen; $i++) {
            $result |= (ord($knownString[$i]) ^ ord($userString[$i]));
        }

        return 0 === $result;
    }
}
