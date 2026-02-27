<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function maintenanceEnabled(): bool
    {
        $value = static::query()->where('key', 'maintenance_enabled')->value('value');

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public static function maintenanceMessage(): ?string
    {
        $value = static::query()->where('key', 'maintenance_message')->value('value');

        return is_string($value) && $value !== '' ? $value : null;
    }
}
