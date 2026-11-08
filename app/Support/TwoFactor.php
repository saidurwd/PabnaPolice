<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

/** Time-based one-time passwords (Google Authenticator, Microsoft Authenticator, Authy, ...). */
class TwoFactor
{
    public function __construct(private Google2FA $google2fa) {}

    public function newSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        // Window of 1 = accept the previous/next 30-second code too (clock drift).
        return strlen($code) === 6 && (bool) $this->google2fa->verifyKey($secret, $code, 1);
    }

    /** Inline SVG of the otpauth:// QR code to scan. */
    public function qrSvg(string $account, string $secret): string
    {
        $uri = $this->google2fa->getQRCodeUrl(config('app.name'), $account, $secret);

        return (new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd)))->writeString($uri);
    }

    /** @return array<int, string> */
    public function recoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => strtolower(bin2hex(random_bytes(4)).'-'.bin2hex(random_bytes(4))))
            ->all();
    }
}
