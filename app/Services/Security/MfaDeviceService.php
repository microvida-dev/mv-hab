<?php

namespace App\Services\Security;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\MfaDevice;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class MfaDeviceService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function __construct(private readonly AuditTrailService $audit) {}

    public function createTotpDevice(User $user, string $name = 'Aplicação autenticadora'): MfaDevice
    {
        $device = MfaDevice::query()->create([
            'user_id' => $user->id,
            'type' => 'totp',
            'name' => $name,
            'secret_encrypted' => $this->generateBase32Secret(),
        ]);

        $this->audit->record('mfa.device.created', $device, AuditEventCategory::Security, AuditEventSeverity::Notice, 'Dispositivo MFA criado.', subject: $user, actor: $user);

        return $device;
    }

    public function confirm(MfaDevice $device, string $code, ?User $actor = null): bool
    {
        if (! $this->verifyTotp($device->secret_encrypted, $code)) {
            return false;
        }

        $subject = $device->user;
        if (! $subject instanceof User) {
            return false;
        }

        $device->forceFill(['confirmed_at' => now(), 'last_used_at' => now()])->save();
        $this->audit->record('mfa.device.confirmed', $device, AuditEventCategory::Security, AuditEventSeverity::Security, 'MFA ativado.', subject: $subject, actor: $actor ?? $subject);

        return true;
    }

    public function disable(MfaDevice $device, User $actor): void
    {
        $subject = $device->user;
        if (! $subject instanceof User) {
            throw new RuntimeException('Dispositivo MFA sem utilizador associado.');
        }

        $device->forceFill(['disabled_at' => now()])->save();
        $this->audit->record('mfa.device.disabled', $device, AuditEventCategory::Security, AuditEventSeverity::Warning, 'MFA desativado.', subject: $subject, actor: $actor);
    }

    /**
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $user, int $count = 8): array
    {
        $user->mfaRecoveryCodes()->delete();

        $codes = [];

        foreach (range(1, $count) as $item) {
            $codes[] = Str::upper(Str::random(5).'-'.Str::random(5));
        }

        foreach ($codes as $code) {
            $user->mfaRecoveryCodes()->create(['code_hash' => Hash::make($code)]);
        }

        $this->audit->record('mfa.recovery_codes.regenerated', null, AuditEventCategory::Security, AuditEventSeverity::Security, 'Recovery codes regenerados.', subject: $user, actor: $user);

        return $codes;
    }

    public function useRecoveryCode(User $user, string $code): bool
    {
        foreach ($user->mfaRecoveryCodes()->whereNull('used_at')->get() as $recoveryCode) {
            $hash = $recoveryCode->getAttribute('code_hash');

            if (is_string($hash) && Hash::check($code, $hash)) {
                $recoveryCode->forceFill(['used_at' => now()])->save();

                return true;
            }
        }

        return false;
    }

    public function verifyTotp(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if ($code === null) {
            return false;
        }

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->totp($secret, $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    public function totp(string $secret, int $offset = 0): string
    {
        $counter = intdiv(time(), 30) + $offset;
        $binaryCounter = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $this->base32Decode($secret), true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $unpacked = unpack('N', substr($hash, $offset, 4));
        if ($unpacked === false) {
            throw new RuntimeException('Não foi possível calcular código TOTP.');
        }

        $truncated = $unpacked[1] & 0x7FFFFFFF;

        return str_pad((string) ($truncated % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function generateBase32Secret(int $length = 32): string
    {
        return collect(range(1, $length))
            ->map(fn () => self::BASE32_ALPHABET[random_int(0, strlen(self::BASE32_ALPHABET) - 1)])
            ->implode('');
    }

    private function base32Decode(string $secret): string
    {
        $secret = rtrim(strtoupper($secret), '=');
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';

        foreach (str_split($secret) as $char) {
            $value = strpos(self::BASE32_ALPHABET, $char);
            if ($value === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $result;
    }
}
