<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use Hyperf\Validation\Contract\Rule;

/**
 * 验证图片 Data URL，并限制解码后的图片大小。
 */
class Base64Image implements Rule
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * @var array<string, string>
     */
    private const MIME_TYPES = [
        'png' => 'image/png',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
    ];

    public function passes(string $attribute, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (! is_string($value) || ! preg_match('/^data:(image\/(?:png|jpe?g|gif|webp|bmp));base64,([A-Za-z0-9+\/=]+)$/i', $value, $matches)) {
            return false;
        }

        if (strlen($matches[2]) > 4 * (int) ceil(self::MAX_BYTES / 3)) {
            return false;
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false || $binary === '' || strlen($binary) > self::MAX_BYTES) {
            return false;
        }

        $detected = @getimagesizefromstring($binary);
        if ($detected === false || empty($detected['mime'])) {
            return false;
        }

        $extension = strtolower(substr($matches[1], strpos($matches[1], '/') + 1));
        $expectedMime = self::MIME_TYPES[$extension] ?? null;

        return $expectedMime !== null && $detected['mime'] === $expectedMime;
    }

    public function message(): string
    {
        return '签名图片必须是有效的 PNG/JPG/GIF/WEBP/BMP Base64 图片，且大小不超过5MB';
    }
}
