<?php

namespace App\Services;

use InvalidArgumentException;

class GoogleOtpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 32): string
    {
        $characters = self::BASE32_ALPHABET;
        $maxIndex = strlen($characters) - 1;
        $secret = '';

        for ($index = 0; $index < $length; $index++) {
            $secret .= $characters[random_int(0, $maxIndex)];
        }

        return $secret;
    }

    public function buildOtpAuthUrl(string $issuer, string $accountName, string $secret): string
    {
        $issuer = trim($issuer) !== '' ? trim($issuer) : config('app.name', 'Codecartel Telecom');
        $label = rawurlencode($issuer . ':' . $accountName);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            $label,
            rawurlencode($secret),
            rawurlencode($issuer),
        );
    }

    public function verifyCode(string $secret, ?string $code, int $window = 1, ?int $timestamp = null): bool
    {
        $normalizedCode = preg_replace('/[^0-9]/', '', (string) $code);

        if (strlen($normalizedCode) !== 6) {
            return false;
        }

        $timestamp ??= time();
        $timeSlice = (int) floor($timestamp / 30);

        try {
            for ($offset = -$window; $offset <= $window; $offset++) {
                if (hash_equals($this->generateCode($secret, $timeSlice + $offset), $normalizedCode)) {
                    return true;
                }
            }
        } catch (InvalidArgumentException) {
            return false;
        }

        return false;
    }

    public function currentCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        return $this->generateCode($secret, (int) floor($timestamp / 30));
    }

    public function maskSecret(?string $secret): ?string
    {
        if (blank($secret)) {
            return null;
        }

        $secret = (string) $secret;

        return substr($secret, 0, 4) . str_repeat('•', max(strlen($secret) - 8, 0)) . substr($secret, -4);
    }

    private function generateCode(string $secret, int $timeSlice): string
    {
        $binarySecret = $this->decodeBase32($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $binarySecret, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function decodeBase32(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));
        $characterMap = array_flip(str_split(self::BASE32_ALPHABET));
        $bits = '';

        foreach (str_split($secret) as $character) {
            if (! array_key_exists($character, $characterMap)) {
                throw new InvalidArgumentException('Invalid base32 secret.');
            }

            $bits .= str_pad(decbin($characterMap[$character]), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';

        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $binary .= chr(bindec($byte));
            }
        }

        return $binary;
    }
}