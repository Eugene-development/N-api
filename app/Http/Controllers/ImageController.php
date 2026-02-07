<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Intervention\Image\Laravel\Facades\Image as ImageManager;

class ImageController extends Controller
{
    /**
     * Максимальное количество изображений для одной сущности
     */
    private const MAX_IMAGES = 8;

    /**
     * Загрузить изображения
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array|max:' . self::MAX_IMAGES,
            'files.*' => [
                'required',
                File::image()
                    ->max(10 * 1024) // 10MB max
                    ->types(['jpeg', 'jpg', 'png', 'webp', 'gif']),
            ],
            'parentable_type' => 'required|string',
            'parentable_id' => 'required|string',
        ]);

        $parentableType = $request->input('parentable_type');
        $parentableId = $request->input('parentable_id');

        // Проверяем, сколько изображений уже есть у сущности
        $existingCount = Image::where('parentable_type', $parentableType)
            ->where('parentable_id', $parentableId)
            ->count();

        $filesToUpload = $request->file('files');
        $remainingSlots = self::MAX_IMAGES - $existingCount;

        if ($remainingSlots <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Достигнуто максимальное количество изображений (' . self::MAX_IMAGES . ')',
            ], 422);
        }

        // Ограничиваем количество загружаемых файлов
        $filesToUpload = array_slice($filesToUpload, 0, $remainingSlots);

        $uploadedImages = [];
        $errors = [];
        $disk = Storage::disk('s3');

        // Настройки сжатия
        $maxWidth = 1920;   // Максимальная ширина
        $maxHeight = 1920;  // Максимальная высота
        $quality = 85;      // Качество JPEG/WebP (1-100)

        foreach ($filesToUpload as $index => $file) {
            try {
                // Обрабатываем изображение через Intervention
                $image = ImageManager::read($file->getPathname());
                
                // Получаем размеры
                $width = $image->width();
                $height = $image->height();
                
                // Ресайз если изображение больше максимальных размеров
                if ($width > $maxWidth || $height > $maxHeight) {
                    $image->scaleDown(width: $maxWidth, height: $maxHeight);
                }
                
                // Определяем формат вывода
                $originalExtension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();
                
                // Конвертируем в WebP для лучшего сжатия (кроме GIF с анимацией)
                if ($originalExtension === 'gif') {
                    // GIF оставляем как есть (может быть анимированным)
                    $extension = 'gif';
                    $encodedImage = $image->toGif();
                } else {
                    // Остальные конвертируем в WebP
                    $extension = 'webp';
                    $encodedImage = $image->toWebp($quality);
                }
                
                // Генерируем уникальное имя файла
                $filename = Str::ulid() . '.' . $extension;
                
                // Путь в бакете: images/{parentable_type}/{parentable_id}/{filename}
                $typePath = Str::snake(class_basename($parentableType));
                $path = "images/{$typePath}/{$parentableId}/{$filename}";

                // Получаем размер сжатого изображения
                $compressedContent = (string) $encodedImage;
                $compressedSize = strlen($compressedContent);
                
                // Вычисляем хэш сжатого файла
                $hash = hash('sha256', $compressedContent);

                // Загружаем в S3
                $uploaded = $disk->put($path, $compressedContent, 'public');
                
                if (!$uploaded) {
                    $errors[] = "Failed to upload {$file->getClientOriginalName()}";
                    \Log::error('S3 upload returned false', ['path' => $path]);
                    continue;
                }

                // Получаем следующий sort_order
                $nextSortOrder = Image::where('parentable_type', $parentableType)
                    ->where('parentable_id', $parentableId)
                    ->max('sort_order') + 1;

                // Определяем mime-type для сжатого файла
                $compressedMimeType = $extension === 'webp' ? 'image/webp' : 'image/gif';

                // Создаём запись в БД только после успешной загрузки
                $image = Image::create([
                    'key' => Str::ulid(),
                    'is_active' => true,
                    'hash' => $hash,
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $compressedMimeType,
                    'size' => $compressedSize,  // Размер сжатого файла
                    'path' => $path,
                    'parentable_type' => $parentableType,
                    'parentable_id' => $parentableId,
                    'sort_order' => $nextSortOrder,
                ]);

                // Логируем сжатие
                $originalSize = $file->getSize();
                $compressionRatio = $originalSize > 0 ? round((1 - $compressedSize / $originalSize) * 100, 1) : 0;
                \Log::info('Image compressed', [
                    'original' => $file->getClientOriginalName(),
                    'original_size' => $originalSize,
                    'compressed_size' => $compressedSize,
                    'compression' => $compressionRatio . '%',
                    'format' => $extension,
                ]);

                $uploadedImages[] = $image;

            } catch (\Exception $e) {
                // Логируем ошибку, но продолжаем с другими файлами
                $errors[] = $e->getMessage();
                \Log::error('Image upload failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $file->getClientOriginalName(),
                ]);
            }
        }

        $success = count($uploadedImages) > 0;
        $message = 'Загружено ' . count($uploadedImages) . ' изображений';
        if (count($errors) > 0) {
            $message .= '. Ошибки: ' . implode('; ', $errors);
        }

        return response()->json([
            'success' => $success,
            'message' => $message,
            'images' => $uploadedImages,
            'errors' => $errors,
            'remaining_slots' => self::MAX_IMAGES - $existingCount - count($uploadedImages),
        ], $success ? 200 : 500);
    }

    /**
     * Получить изображения для сущности
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'parentable_type' => 'required|string',
            'parentable_id' => 'required|string',
        ]);

        $images = Image::where('parentable_type', $request->input('parentable_type'))
            ->where('parentable_id', $request->input('parentable_id'))
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'images' => $images,
            'count' => $images->count(),
            'max' => self::MAX_IMAGES,
        ]);
    }

    /**
     * Удалить изображение
     */
    public function destroy(string $id): JsonResponse
    {
        $image = Image::findOrFail($id);

        // Удаляем файл из S3
        if ($image->path) {
            try {
                Storage::disk('s3')->delete($image->path);
            } catch (\Exception $e) {
                \Log::warning('Failed to delete image from S3', [
                    'path' => $image->path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Мягкое удаление записи
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Изображение удалено',
        ]);
    }

    /**
     * Обновить порядок изображений
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|string|exists:images,id',
            'images.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->input('images') as $item) {
            Image::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Порядок обновлён',
        ]);
    }

    /**
     * Переключить активность изображения
     */
    public function toggleActive(string $id): JsonResponse
    {
        $image = Image::findOrFail($id);
        $image->is_active = !$image->is_active;
        $image->save();

        return response()->json([
            'success' => true,
            'is_active' => $image->is_active,
        ]);
    }
}
