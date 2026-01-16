<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopCity extends Model
{
    use HasFactory, HasUlids;

    /**
     * Таблица модели
     */
    protected $table = 'shop_cities';

    /**
     * Поля, доступные для массового заполнения
     */
    protected $fillable = [
        'shop_id',
        'city_name',
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
     * Связь с магазином
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
