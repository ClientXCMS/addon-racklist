<?php

declare(strict_types=1);

namespace App\Addons\RackList\Services;

use App\Models\Admin\Setting;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * Typed access to the addon settings stored in the core settings table.
 */
final class RackListSettings
{
    public const KEY_API_KEY = 'racklist_api_key';

    public const KEY_AUTO_SYNC = 'racklist_auto_sync';

    public const KEY_LAST_REPORT = 'racklist_last_report';

    // Format documented for BillingSyncKey: "rk_" followed by 64 hex characters.
    public const API_KEY_PATTERN = '/^rk_[a-f0-9]{64}$/i';

    public function getApiKey(): ?string
    {
        $stored = setting(self::KEY_API_KEY);
        if (! is_string($stored) || $stored === '') {
            return null;
        }

        // The core encrypted-settings list is not extensible, so the key is encrypted here.
        try {
            return decrypt($stored, false);
        } catch (DecryptException) {
            return null;
        }
    }

    public function hasApiKey(): bool
    {
        return $this->getApiKey() !== null;
    }

    public function setApiKey(?string $apiKey): void
    {
        $value = ($apiKey === null || $apiKey === '') ? null : encrypt($apiKey, false);
        Setting::updateSettings([self::KEY_API_KEY => $value], null, false);
    }

    public function getMaskedApiKey(): ?string
    {
        $apiKey = $this->getApiKey();

        return $apiKey === null ? null : substr($apiKey, 0, 8).str_repeat('•', 8);
    }

    public function isAutoSyncEnabled(): bool
    {
        return filter_var(setting(self::KEY_AUTO_SYNC, true), FILTER_VALIDATE_BOOLEAN) && $this->hasApiKey();
    }

    public function setAutoSync(bool $enabled): void
    {
        Setting::updateSettings([self::KEY_AUTO_SYNC => $enabled ? 'true' : 'false'], null, false);
    }

    public function getLastReport(): ?array
    {
        $raw = setting(self::KEY_LAST_REPORT);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($decoded) ? $decoded : null;
    }

    public function storeReport(array $report): void
    {
        Setting::updateSettings([self::KEY_LAST_REPORT => json_encode($report)], null, false);
    }
}
