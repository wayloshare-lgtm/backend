<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * PaymentEncryptionService
 * 
 * Handles encryption and decryption of sensitive payment data.
 * Uses Laravel's built-in encryption (AES-256-CBC by default).
 * 
 * Security Features:
 * - Automatic encryption/decryption via model casts
 * - Sensitive data never logged in plain text
 * - Secure key management via .env
 * - Support for payment data masking
 */
class PaymentEncryptionService
{
    /**
     * Encrypt sensitive payment data
     * 
     * @param array $paymentDetails
     * @return string Encrypted JSON string
     */
    public static function encrypt(array $paymentDetails): string
    {
        try {
            $json = json_encode($paymentDetails);
            return Crypt::encryptString($json);
        } catch (\Exception $e) {
            Log::error('Payment encryption failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Decrypt payment data
     * 
     * @param string $encryptedData
     * @return array Decrypted payment details
     */
    public static function decrypt(string $encryptedData): array
    {
        try {
            $json = Crypt::decryptString($encryptedData);
            return json_decode($json, true);
        } catch (\Exception $e) {
            Log::error('Payment decryption failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mask sensitive payment information for display
     * 
     * @param array $paymentDetails
     * @param string $paymentType
     * @return array Masked payment details
     */
    public static function maskSensitiveData(array $paymentDetails, string $paymentType): array
    {
        $masked = $paymentDetails;

        switch ($paymentType) {
            case 'card':
                if (isset($masked['card_number'])) {
                    $cardNumber = $masked['card_number'];
                    // Show only last 4 digits
                    $masked['card_number'] = '****' . substr($cardNumber, -4);
                }
                if (isset($masked['cvv'])) {
                    unset($masked['cvv']); // Never expose CVV
                }
                break;

            case 'upi':
                if (isset($masked['upi_id'])) {
                    $upiId = $masked['upi_id'];
                    // Mask email part of UPI ID
                    $parts = explode('@', $upiId);
                    if (count($parts) === 2) {
                        $masked['upi_id'] = substr($parts[0], 0, 2) . '****@' . $parts[1];
                    }
                }
                break;

            case 'wallet':
                if (isset($masked['wallet_id'])) {
                    $walletId = $masked['wallet_id'];
                    // Show only last 4 characters
                    $masked['wallet_id'] = '****' . substr($walletId, -4);
                }
                break;
        }

        return $masked;
    }

    /**
     * Validate payment details structure
     * 
     * @param array $paymentDetails
     * @param string $paymentType
     * @return bool
     */
    public static function validatePaymentDetails(array $paymentDetails, string $paymentType): bool
    {
        switch ($paymentType) {
            case 'card':
                return isset($paymentDetails['card_number']) &&
                       isset($paymentDetails['expiry']) &&
                       isset($paymentDetails['holder_name']);

            case 'upi':
                return isset($paymentDetails['upi_id']);

            case 'wallet':
                return isset($paymentDetails['wallet_id']);

            default:
                return false;
        }
    }

    /**
     * Log payment operation securely (without exposing sensitive data)
     * 
     * @param string $operation
     * @param int $userId
     * @param string $paymentType
     * @param string $status
     * @return void
     */
    public static function logPaymentOperation(
        string $operation,
        int $userId,
        string $paymentType,
        string $status
    ): void {
        Log::info('Payment operation', [
            'operation' => $operation,
            'user_id' => $userId,
            'payment_type' => $paymentType,
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Check if encryption key is properly configured
     * 
     * @return bool
     */
    public static function isEncryptionConfigured(): bool
    {
        $appKey = config('app.key');
        return !empty($appKey) && $appKey !== 'base64:' . base64_encode('');
    }

    /**
     * Get encryption algorithm
     * 
     * @return string
     */
    public static function getEncryptionAlgorithm(): string
    {
        return config('app.cipher', 'AES-256-CBC');
    }
}
