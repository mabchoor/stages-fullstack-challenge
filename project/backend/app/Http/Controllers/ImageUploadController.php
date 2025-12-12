<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;

class ImageUploadController extends Controller
{
    /**
     * Handle image upload with optimization.
     * PERF-002: Resize, compress, and generate WebP versions
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:20480',
        ]);

        if (!$request->hasFile('image')) {
            return response()->json(['error' => 'No image provided'], 400);
        }

        $uploadedFile = $request->file('image');
        $originalSize = $uploadedFile->getSize();
        $baseFilename = Str::random(20);
        
        // PERF-002: Optimize and save multiple versions
        $optimizedImages = $this->optimizeImage($uploadedFile, $baseFilename);
        
        return response()->json([
            'message' => 'Image uploaded and optimized successfully',
            'original_size' => $originalSize,
            'optimized_size' => $optimizedImages['large']['size'],
            'reduction_percent' => round((1 - $optimizedImages['large']['size'] / $originalSize) * 100, 1),
            'images' => $optimizedImages,
            'url' => '/storage/images/' . $optimizedImages['large']['filename'],
        ], 201);
    }
    
    /**
     * Optimize image: resize, compress, generate WebP and thumbnails.
     * PERF-002 implementation
     */
    private function optimizeImage($file, $baseFilename)
    {
        $results = [];
        $storagePath = storage_path('app/public/images');
        
        // Ensure directory exists
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        
        // Configure Intervention Image to use Imagick if available, fallback to GD
        try {
            Image::configure(['driver' => 'imagick']);
            $img = Image::make($file);
        } catch (\Exception $e) {
            Image::configure(['driver' => 'gd']);
            $img = Image::make($file);
        }
        
        // PERF-002: Generate large version (1200px max, 80% quality)
        $largeFilename = $baseFilename . '.jpg';
        $largeImg = clone $img;
        $largeImg->resize(1200, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode('jpg', 80);
        $largeImg->save($storagePath . '/' . $largeFilename);
        $results['large'] = [
            'filename' => $largeFilename,
            'size' => filesize($storagePath . '/' . $largeFilename),
            'width' => $largeImg->width(),
            'height' => $largeImg->height(),
        ];
        
        // PERF-002: Generate WebP version
        $webpFilename = $baseFilename . '.webp';
        $webpImg = clone $img;
        $webpImg->resize(1200, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode('webp', 80);
        $webpImg->save($storagePath . '/' . $webpFilename);
        $results['webp'] = [
            'filename' => $webpFilename,
            'size' => filesize($storagePath . '/' . $webpFilename),
        ];
        
        // PERF-002: Generate medium version (600px)
        $mediumFilename = $baseFilename . '-medium.jpg';
        $mediumImg = clone $img;
        $mediumImg->resize(600, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode('jpg', 80);
        $mediumImg->save($storagePath . '/' . $mediumFilename);
        $results['medium'] = [
            'filename' => $mediumFilename,
            'size' => filesize($storagePath . '/' . $mediumFilename),
            'width' => $mediumImg->width(),
            'height' => $mediumImg->height(),
        ];
        
        // PERF-002: Generate thumbnail (300px)
        $thumbFilename = $baseFilename . '-thumb.jpg';
        $thumbImg = clone $img;
        $thumbImg->resize(300, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode('jpg', 80);
        $thumbImg->save($storagePath . '/' . $thumbFilename);
        $results['thumbnail'] = [
            'filename' => $thumbFilename,
            'size' => filesize($storagePath . '/' . $thumbFilename),
            'width' => $thumbImg->width(),
            'height' => $thumbImg->height(),
        ];
        
        return $results;
    }

    /**
     * Delete an uploaded image.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return response()->json(['message' => 'Image deleted successfully']);
        }

        return response()->json(['error' => 'Image not found'], 404);
    }
}

