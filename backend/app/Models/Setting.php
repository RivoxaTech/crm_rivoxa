<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    public const DEFAULTS = [
        'company_name' => 'Rivoxa',
        'company_site' => 'rivoxa.tech',
        'company_email' => '',
        'company_phone' => '',
        'invoice_prefix' => 'RVX',
        'country_code' => '92',
        'renewal_alert_days' => '30',
        'auto_email_recurring' => '0',
    ];

    private static ?array $cache = null;

    public static function map(): array
    {
        return self::$cache ??= array_merge(self::DEFAULTS, self::pluck('value', 'key')->all());
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::map()[$key] ?? $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS)) {
                self::updateOrCreate(['key' => $key], ['value' => (string) $value]);
            }
        }
        self::$cache = null;
    }
}
