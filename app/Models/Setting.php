<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return match($setting->type) {
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, $value, string $type = 'string', ?string $description = null): bool
    {
        try {
            $setting = self::where('key', $key)->first();

            $valueToStore = match($type) {
                'boolean' => $value ? '1' : '0',
                'integer' => (string) $value,
                'json' => json_encode($value),
                default => (string) $value,
            };

            if ($setting) {
                $setting->value = $valueToStore;
                $setting->type = $type;
                if ($description) {
                    $setting->description = $description;
                }
                $saved = $setting->save();
                
                if (!$saved) {
                    \Log::error("Failed to update setting: {$key}");
                    return false;
                }
            } else {
                $created = self::create([
                    'key' => $key,
                    'value' => $valueToStore,
                    'type' => $type,
                    'description' => $description,
                ]);
                
                if (!$created || !$created->id) {
                    \Log::error("Failed to create setting: {$key}");
                    return false;
                }
            }
            
            // Clear any potential cache
            Cache::forget("setting_{$key}");
            
            return true;
        } catch (\Exception $e) {
            \Log::error("Error setting {$key}: " . $e->getMessage());
            return false;
        }
    }
}
