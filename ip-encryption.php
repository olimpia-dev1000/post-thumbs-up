<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class IP_Encryption
{
    /**
     * Encrypt an IP address
     * 
     * @param string $ip IP address to encrypt
     * @return string Encrypted IP
     */
    public static function encrypt_ip($ip)
    {
        // Generate a secure encryption key
        $key = wp_salt('auth'); // Uses WordPress's built-in salt for added security

        // Use OpenSSL for encryption
        $method = 'aes-256-cbc';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));

        $encrypted = openssl_encrypt($ip, $method, $key, 0, $iv);

        // Combine IV and encrypted data
        $combined = base64_encode($iv . $encrypted);

        return $combined;
    }

    /**
     * Decrypt an encrypted IP address
     * 
     * @param string $encrypted_ip Encrypted IP to decrypt
     * @return string|false Decrypted IP or false on failure
     */

    public static function decrypt_ip($encrypted_ip)
    {
        // Use the same key used for encryption
        $key = wp_salt('auth');
        $method = 'aes-256-cbc';

        // Decode the combined string
        $combined = base64_decode($encrypted_ip);

        // Extract IV and encrypted data
        $iv_length = openssl_cipher_iv_length($method);
        $iv = substr($combined, 0, $iv_length);
        $encrypted_data = substr($combined, $iv_length);

        // Decrypt
        $decrypted = openssl_decrypt($encrypted_data, $method, $key, 0, $iv);

        return $decrypted;
    }
}
