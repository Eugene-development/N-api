<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mebel extends Model
{
    use HasFactory, HasUlids, SoftDeletes, Sluggable;

    /**
     * Таблица модели
     */
    protected $table = 'mebel';

    /**
     * Поля, доступные для массового заполнения
     */
    protected $fillable = [
        'value',
        'slug',
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
     * Настройка генерации slug
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'value',
                'onUpdate' => true,
            ]
        ];
    }
}
