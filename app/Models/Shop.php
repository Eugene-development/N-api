<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use HasFactory, HasUlids, SoftDeletes, Sluggable;

    /**
     * Таблица модели
     */
    protected $table = 'shops';

    /**
     * Поля, доступные для массового заполнения
     */
    protected $fillable = [
        'value',
        'slug',
        'rubric_id',
        'description',
        'logo',
        'website',
        'phone',
        'email',
        'is_active',
        'sort_order',
    ];

    /**
     * Атрибуты для кастинга
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Значения по умолчанию
     */
    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    /**
     * Связь с рубрикой
     */
    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    /**
     * Связь с категориями магазина
     */
    public function categories(): HasMany
    {
        return $this->hasMany(ShopCategory::class)->orderBy('sort_order');
    }

    /**
     * Связь с брендами магазина
     */
    public function brands(): HasMany
    {
        return $this->hasMany(ShopBrand::class)->orderBy('sort_order');
    }

    /**
     * Связь с городами присутствия магазина
     */
    public function cities(): HasMany
    {
        return $this->hasMany(ShopCity::class)->orderBy('sort_order');
    }

    /**
     * Настройка генерации slug
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'value',
                'onUpdate' => false,
            ]
        ];
    }

    /**
     * Генерация уникального key при создании
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->key)) {
                $model->key = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }
}
