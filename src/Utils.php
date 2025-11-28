<?php
namespace Grok\Socket;

/**
 * Utils Class
 * 
 * Helper functions for encryption, etc.
 * Why: Centralize shared logic.
 * From: PHP OpenSSL for AES-256-CBC (standard encryption).
 * Benefits: Secure by default; easy to toggle.
 * Usage: Static; set key on server.
 */
class Utils {
    private static ?string $encryptionKey = null;

    /**
     * Set encryption key.
     * @param string $key Key
     */
    public static function setEncryptionKey(string $key): void {
        // Store raw binary 32-byte key for AES-256
        self::$encryptionKey = hash('sha256', $key, true);
    }

    /**
     * Get encryption key.
     * @return ?string Key or null
     */
    public static function getEncryptionKey(): ?string {
        return self::$encryptionKey;
    }

    /**
     * Encrypt data.
     * Why: Protect messages from eavesdropping.
     * Benefits: Compliance (e.g., GDPR); uses IV for uniqueness.
     * @param string $data Data
     * @return string Encrypted
     */
    public static function encrypt(string $data): string {
        if (!self::$encryptionKey) return $data;
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', self::$encryptionKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data.
     * @param string $data Encrypted
     * @return string Decrypted
     */
    public static function decrypt(string $data): string {
        if (!self::$encryptionKey) return $data;
        // Strict base64 decode; if input is not valid base64 or too short, assume it's plain text
        $decoded = base64_decode($data, true);
        if ($decoded === false || strlen($decoded) < 16) {
            return $data;
        }

        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);

        // Ensure IV length is exactly 16 bytes before decrypting
        if (strlen($iv) !== 16) {
            return $data;
        }

        $result = openssl_decrypt($encrypted, 'AES-256-CBC', self::$encryptionKey, 0, $iv);
        // If decryption fails, return original input to avoid breaking flow
        return $result === false ? $data : $result;
    }
}