<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'password',
        'data',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'data',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'data' => 'encrypted:array',
        ];
    }

    /**
     * Get a value from the data JSON column.
     */
    public function getData(string $key, mixed $default = null): mixed
    {
        return data_get($this->getDataArray(), $key, $default);
    }

    /**
     * Set a value in the data JSON column.
     */
    public function setData(string $key, mixed $value): static
    {
        $data = $this->getDataArray();
        data_set($data, $key, $value);
        $this->setAttribute('data', $data);

        return $this;
    }

    /**
     * Get the full data array, always reading through the cast.
     */
    protected function getDataArray(): array
    {
        try {
            return parent::__get('data') ?? [];
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            $this->attributes['data'] = null;
            $this->save();

            return [];
        }
    }

    /**
     * Proxy attribute access into the data column for known settings keys.
     */
    public function __get($key)
    {
        if (in_array($key, static::dataKeys())) {
            return $this->getData($key);
        }

        return parent::__get($key);
    }

    /**
     * Proxy attribute setting into the data column for known settings keys.
     */
    public function __set($key, $value)
    {
        if (in_array($key, static::dataKeys())) {
            $this->setData($key, $value);

            return;
        }

        parent::__set($key, $value);
    }

    /**
     * Keys stored in the data JSON column.
     */
    public static function dataKeys(): array
    {
        return [
            'catalog_beer_api_key',
            'untappd_client_id',
            'untappd_client_secret',
            'untappd_username',
            'untappd_password',
            'logr_db_token',
            'untappd_rss_feeds',
            'discord_webhooks',
            'discord_bots',
            'geocoding_enabled',
        ];
    }

    /**
     * Parse a comma-separated "Label|URL" env string into an array of items.
     */
    public static function parseEnvList(?string $value, array $defaults = []): array
    {
        if (! $value) {
            return [];
        }

        return collect(explode(',', $value))
            ->map(function (string $entry) use ($defaults) {
                $entry = trim($entry);
                if (! $entry) {
                    return null;
                }

                if (str_contains($entry, '|')) {
                    [$label, $url] = explode('|', $entry, 2);
                    $label = trim($label);
                    $url = trim($url);
                } else {
                    $label = null;
                    $url = trim($entry);
                }

                return array_merge($defaults, [
                    'label' => $label ?: null,
                    'url' => $url,
                ]);
            })
            ->filter()
            ->values()
            ->all();
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }
}
