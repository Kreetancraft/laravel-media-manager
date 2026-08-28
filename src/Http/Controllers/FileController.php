<?php

namespace Kreetancraft\Media\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use LivewireFilemanager\Filemanager\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FileController
{
    /**
     * Serve direct or WebP optimized files via path mapping.
     */
    public function show(Request $request, string $path): mixed
    {
        // Release session file lock immediately to prevent head-of-line blocking for concurrent requests
        if (session()->isStarted()) {
            session()->save();
        }

        $segments = explode('/', $path);
        $fileName = array_pop($segments);
        $folderPath = implode('/', $segments);

        $folder = Folder::where('slug', '=', end($segments))->firstOrFail();
        $fullFolderPath = buildFolderPath($folder->id);

        if ($folderPath !== $fullFolderPath) {
            abort(404);
        }

        // Find the media item inside the folder matching the filename
        $file = $folder->media()
            ->where('model_id', $folder->id)
            ->where(function ($query) use ($fileName) {
                $query->where('file_name', $fileName)
                    ->orWhere('name', $fileName);
            })
            ->firstOrFail();

        $filePath = $file->getPath();
        $disk = Storage::disk($file->disk);

        // 1. If a generated conversion is requested specifically
        $conversion = (string) $request->query('conversion', '');
        if ($conversion !== '') {
            // Responsive WebP variants (thumbnail/medium/large) are stored
            // under the media's `responsive` custom property.
            $responsivePath = $file->getCustomProperty("responsive.paths.{$conversion}");
            if ($responsivePath !== null && $disk->exists($responsivePath)) {
                $filePath = $disk->path($responsivePath);
            } elseif ($file->hasGeneratedConversion($conversion)) {
                $filePath = $file->getPath($conversion);
            }
        }
        // 2. Default to serving WebP optimized version for images if it exists
        else {
            $webpPath = $file->getCustomProperty('webp_path');
            if ($webpPath !== null && $disk->exists($webpPath)) {
                $filePath = $disk->path($webpPath);
            }
        }

        if (! file_exists($filePath)) {
            abort(404, 'File not found on server');
        }

        $fileMimeType = mime_content_type($filePath);

        // Serve with local client caching headers for butter-smooth loading
        if (in_array($fileMimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'])) {
            return response()->file($filePath, [
                'Content-Type' => $fileMimeType,
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        return response()->download($filePath, $fileName);
    }
}
