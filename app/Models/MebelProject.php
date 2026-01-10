<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MebelProject extends Model
{
    use HasFactory, HasUlids, SoftDeletes, Sluggable;

    /**
     * Таблица модели
     */
    protected $table = 'mebel_projects';

    /**
     * Поля, доступные для массового заполнения
     */
    protected $fillable = [
        'key',
        'category_id',
        'value',
        'slug',
        'description',
        'short_description',
        'price',
        'old_price',
        'meta',
        'is_active',
        'is_featured',
        'is_new',
        'sort_order',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Атрибуты для кастинга
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'sort_order' => 'integer',
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'meta' => 'array',
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
        'is_featured' => false,
        'is_new' => false,
        'sort_order' => 0,
    ];

    /**
     * Boot метод для автогенерации key
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->key)) {
                $model->key = (string) Str::ulid();
            }
        });
    }

    /**
     * Связь с категорией
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Настройка генерации slug
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'value',
                'onUpdate' => false,  // Не перезаписывать slug при обновлении
            ]
        ];
    }
}
