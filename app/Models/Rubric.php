<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Rubric extends Model
{
    use HasFactory, HasUlids, SoftDeletes, Sluggable;

    /**
     * Таблица модели
     */
    protected $table = 'rubrics';

    /**
     * Поля, доступные для массового заполнения
     */
    protected $fillable = [
        'key',
        'value',
        'slug',
        'description',
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
     * Связь с категориями
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
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

