<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, HasUlids, SoftDeletes, Sluggable;

    /**
     * Таблица модели
     */
    protected $table = 'categories';

    /**
     * Поля, доступные для массового заполнения
     */
    protected $fillable = [
        'value',
        'slug',
        'rubric_id',
        'description',
        'bg',
        'is_active',
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
     * Связь с проектами мебели
     */
    public function mebelProjects(): HasMany
    {
        return $this->hasMany(MebelProject::class);
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
