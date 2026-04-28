<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'system_title',
        'system_short_title',
        'system_logo',
        'minilogo',
        'system_favicon',
        'logo',
        'favicon',
        'company_name',
        'company_address',
        'tagline',
        'phone',
        'email',
        'timezone',
        'language',
        'copyright_text',
        'site_name',
        'designer_name',
    ];

    protected $appends = [
        'logo_url',
        'minilogo_url',
        'favicon_url',
        'system_logo_url',
        'system_favicon_url',
    ];

    /**
     * Get cached settings (single row only)
     */
    public static function getCached()
    {
        return Cache::rememberForever('system_settings', function () {
            return static::firstOrCreate(['id' => 1]);
        });
    }

    public static function clearCache()
    {
        Cache::forget('system_settings');
    }

    // ------------------------
    // File URL Accessors
    // ------------------------
    public function getSystemLogoUrlAttribute()
    {
        return $this->getFileUrl('logo', 'backend/assets/images/logo-black.png');
    }

    public function getLogoUrlAttribute()
    {
        return $this->getFileUrl('logo', 'backend/assets/images/logo-black.png');
    }

    public function getMinilogoUrlAttribute()
    {
        return $this->getFileUrl('minilogo', 'backend/assets/images/logo-sm.png');
    }

    public function getSystemFaviconUrlAttribute()
    {
        return $this->getFileUrl('favicon', 'backend/assets/images/favicon.ico');
    }

    public function getFaviconUrlAttribute()
    {
        return $this->getFileUrl('favicon', 'backend/assets/images/favicon.ico');
    }

    private function getFileUrl($field, $fallback)
    {
        if (!empty($this->$field) && file_exists(public_path($this->$field))) {
            return asset($this->$field);
        }
        return asset($fallback);
    }
    public static function getSettings()
    {
        return self::getCached();
    }
}
