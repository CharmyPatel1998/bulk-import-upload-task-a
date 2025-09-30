<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function showForm()
    {
        return view('upload-form'); // Your upload form view
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:5120', // max 5MB
        ]);

        $file = $request->file('file');

        // Store original file
        $originalPath = $file->store('uploads/original', 'public');

        // Create Upload record
        $upload = Upload::create([
            'filename' => $file->hashName(),
            'checksum' => md5_file($file->getRealPath()),
            'completed' => false,
        ]);

        // Load image with GD
        $imageResource = $this->createImageResource($file->getRealPath());
        if (!$imageResource) {
            return response()->json(['message' => 'Invalid image file'], 422);
        }

        // Generate variants
        $sizes = [256, 512, 1024];
        $variants = [];
        foreach ($sizes as $size) {
            $variants[$size] = $this->resizeAndSave($imageResource, $size, $upload->id, $file->hashName());
        }

        // Save Image record
        Image::create([
            'upload_id' => $upload->id,
            'path' => $originalPath,
            'variants' => $variants,
        ]);

        // Mark upload complete
        $upload->completed = true;
        $upload->save();

        return back()->with('success', 'Upload successful!');
    }

    /**
     * Create GD image resource from file.
     */
    private function createImageResource(string $filePath)
    {
        $info = getimagesize($filePath);
        if (!$info) return null;

        [$width, $height, $type] = $info;

        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG  => imagecreatefrompng($filePath),
            IMAGETYPE_GIF  => imagecreatefromgif($filePath),
            default        => null,
        };
    }

    /**
     * Resize image resource and save variant.
     */
    private function resizeAndSave($resource, int $newWidth, int $uploadId, string $originalName): string
    {
        $width = imagesx($resource);
        $height = imagesy($resource);

        $ratio = $height / $width;
        $newHeight = intval($newWidth * $ratio);

        $tmp = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($tmp, $resource, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $variantPath = "uploads/variants/{$uploadId}_{$newWidth}_{$originalName}";
        $fullPath = storage_path("app/public/{$variantPath}");

        // Ensure directory exists
        $dir = dirname($fullPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        imagejpeg($tmp, $fullPath, 90); // Save as JPEG
        imagedestroy($tmp);

        return $variantPath;
    }

}
