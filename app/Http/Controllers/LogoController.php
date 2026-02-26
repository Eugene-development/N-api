<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Intervention\Image\Laravel\Facades\Image as ImageManager;

class LogoController extends Controller
{
    /**
     * Целевая папка в бакете
     */
    private const LOGO_PATH = 'logo';

    /**
     * Максимальный размер стороны логотипа
     */
    private const MAX_SIZE = 800;

    /**
     * Качество WebP
     */
    private const QUALITY = 90;

    /**
     * Загрузить логотип
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                File::image()
                    ->max(5 * 1024) // 5MB max
                    ->types(['jpeg', 'jpg', 'png', 'webp', 'gif', 'svg+xml']),
            ],
        ]);

        $file = $request->file('file');

        try {
            $disk = Storage::disk('s3');

            // Определяем формат
            $originalExtension = strtolower($file->getClientOriginalExtension());

            // SVG оставляем как есть
            if ($originalExtension === 'svg') {
                $content = file_get_contents($file->getPathname());
                $extension = 'svg';
                $mimeType = 'image/svg+xml';
            } elseif ($originalExtension === 'gif') {
                // GIF оставляем без изменений
                $image = ImageManager::read($file->getPathname());
                $encoded = $image->toGif();
                $content = (string) $encoded;
                $extension = 'gif';
                $mimeType = 'image/gif';
            } else {
                // Остальные конвертируем в WebP
                $image = ImageManager::read($file->getPathname());

                $width = $image->width();
                $height = $image->height();

                if ($width > self::MAX_SIZE || $height > self::MAX_SIZE) {
                    $image->scaleDown(width: self::MAX_SIZE, height: self::MAX_SIZE);
                }

                $encoded = $image->toWebp(self::QUALITY);
                $content = (string) $encoded;
                $extension = 'webp';
                $mimeType = 'image/webp';
            }

            // Генерируем уникальное имя
            $filename = Str::ulid() . '.' . $extension;
            $path = self::LOGO_PATH . '/' . $filename;

            // Загружаем в S3
            $uploaded = $disk->put($path, $content, 'public');

            if (!$uploaded) {
                \Log::error('Logo S3 upload returned false', ['path' => $path]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка загрузки логотипа в хранилище',
                ], 500);
            }

            // Формируем публичный URL
            $endpoint = config('filesystems.disks.s3.url')
                ?? config('filesystems.disks.s3.endpoint')
                ?? 'https://storage.yandexcloud.net';
            $bucket = config('filesystems.disks.s3.bucket');
            $url = rtrim($endpoint, '/') . '/' . $bucket . '/' . $path;

            \Log::info('Logo uploaded', [
                'original' => $file->getClientOriginalName(),
                'path' => $path,
                'url' => $url,
                'size' => strlen($content),
            ]);

            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path,
                'filename' => $filename,
            ]);

        } catch (\Exception $e) {
            \Log::error('Logo upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить логотип из хранилища (опционально)
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        // Безопасность: разрешаем удалять только файлы из папки logo/
        if (!str_starts_with($path, self::LOGO_PATH . '/')) {
            return response()->json([
                'success' => false,
                'message' => 'Недопустимый путь',
            ], 403);
        }

        try {
            Storage::disk('s3')->delete($path);

            return response()->json([
                'success' => true,
                'message' => 'Логотип удалён',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления: ' . $e->getMessage(),
            ], 500);
        }
    }
}
