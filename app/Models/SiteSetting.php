<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    /** @return list<string> */
    public static function booleanKeys(): array
    {
        return [
            'maintenance_mode',
            'xp_system_enabled',
            'require_email_verification',
            'sponsor_border_effect',
            'sponsor_click_modal',
            'crack_background',
            'chat_enabled',
            'powered_by_enabled',
            'footer_show_social',
        ];
    }

    public static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'off', 'no', ''], true)) {
                return false;
            }
        }

        return (bool) $value;
    }

    public static function normalizeData(array $data): array
    {
        foreach (self::booleanKeys() as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = self::toBool($data[$key]);
            }
        }

        if (isset($data['background_image']) && ! is_string($data['background_image'])) {
            $data['background_image'] = null;
        }

        if (isset($data['chat_bot_avatar']) && ! is_string($data['chat_bot_avatar'])) {
            $data['chat_bot_avatar'] = null;
        }

        if (isset($data['powered_by_logo']) && ! is_string($data['powered_by_logo'])) {
            $data['powered_by_logo'] = null;
        }

        foreach (['remove_site_logo', 'remove_site_favicon', 'remove_opening_gif', 'remove_opening_gif_mobile', 'remove_background_image', 'remove_powered_by_logo'] as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    public static function current(): array
    {
        $data = static::query()->first()?->data ?? [];

        return self::normalizeData($data);
    }
}
