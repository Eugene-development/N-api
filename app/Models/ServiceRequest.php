<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Заявка на услугу
 * 
 * Хранит заявки пользователей на различные услуги:
 * - consultation (консультация дизайнера)
 * - design-project (дизайн-проект)
 * - furniture-project (проект мебели)
 * - assembly (сборка мебели)
 * - measurement (замер помещения)
 */
class ServiceRequest extends Model
{
    use HasFactory, HasUlids;

    /**
     * Таблица модели
     */
    protected $table = 'service_requests';

    /**
     * Поля, доступные для массового заполнения
     */
    protected $fillable = [
        'service_type',
        'name',
        'phone',
        'message',
        'status',
        'ip_address',
        'user_agent',
        'source_url',
    ];

    /**
     * Атрибуты для кастинга
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Значения по умолчанию
     */
    protected $attributes = [
        'status' => 'new',
    ];

    /**
     * Допустимые типы услуг
     */
    public const SERVICE_TYPES = [
        'consultation',
        'design-project',
        'furniture-project',
        'assembly',
        'measurement',
        'partnership',
    ];

    /**
     * Допустимые статусы заявок
     */
    public const STATUSES = [
        'new',       // Новая заявка
        'processed', // Обработана
        'completed', // Завершена
        'cancelled', // Отменена
    ];

    /**
     * Названия типов услуг на русском
     */
    public static function getServiceTypeLabel(string $type): string
    {
        return match ($type) {
            'consultation' => 'Консультация дизайнера',
            'design-project' => 'Дизайн-проект интерьера',
            'furniture-project' => 'Проект мебели',
            'assembly' => 'Сборка мебели',
            'measurement' => 'Замер помещения',
            'partnership' => 'Партнёрство',
            default => $type,
        };
    }
}
