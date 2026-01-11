<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * Таблица модели
     */
    protected $table = 'images';

    /**
     * Поля, доступные для массового заполнения
     */
    protected $fillable = [
        'key',
        'is_active',
        'hash',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'path',
        'parentable_type',
        'parentable_id',
        'sort_order',
    ];

    /**
     * Атрибуты для кастинга
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'size' => 'integer',
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
     * Полиморфное отношение к родительской модели
     */
    public function parentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Получить полный URL изображения
     */
    public function getUrlAttribute(): ?string
    {
        if (!$this->path) {
            return null;
        }
        
        // Если путь уже содержит полный URL
        if (str_starts_with($this->path, 'http')) {
            return $this->path;
        }
        
        // Формируем URL для Yandex Cloud Object Storage
        // Формат: https://storage.yandexcloud.net/{bucket}/{path}
        $endpoint = config('filesystems.disks.s3.url') ?? config('filesystems.disks.s3.endpoint') ?? 'https://storage.yandexcloud.net';
        $bucket = config('filesystems.disks.s3.bucket');
        
        if ($bucket) {
            return rtrim($endpoint, '/') . '/' . $bucket . '/' . ltrim($this->path, '/');
        }
        
        return $this->path;
    }

    /**
     * Загрузчик изображений
     */
    protected $appends = ['url'];
}
