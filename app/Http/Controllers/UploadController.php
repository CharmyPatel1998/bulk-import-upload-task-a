<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as InterventionImage;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:5120', // max 5MB
        ]);

        $file = $request->file('file');
        $checksum = md5_file($file->getRealPath());

        // Check duplicates
        $existing = Upload::where('checksum', $checksum)->first();
        if ($existing) {
            return response()->json([
                'message' => 'File already uploaded',
                'upload_id' => $existing->id,
            ]);
        }

        // Store original file
        $path = $file->store('uploads/original', 'public');

        // Create Upload record
        $upload = Upload::create([
            'filename' => basename($path),
            'checksum' => $checksum,
            'completed' => false,
        ]);

        // Generate variants (256, 512, 1024)
        $variants = [];
        $sizes = [256, 512, 1024];
        foreach ($sizes as $width) {
            $variant = InterventionImage::make($file->getRealPath())
                ->resize($width, null, function ($c) {
                    $c->aspectRatio();
                    $c->upsize();
                });

            $variantPath = "uploads/variants/{$upload->id}_{$width}.jpg";
            Storage::disk('public')->put($variantPath, (string) $variant->encode('jpg', 90));
            $variants[$width] = $variantPath;
        }

        // Save Image record with JSON variants
        Image::create([
            'upload_id' => $upload->id,
            'path' => $path,
            'variants' => $variants,
        ]);

        // Mark upload complete
        $upload->completed = true;
        $upload->save();

        return response()->json([
            'message' => 'Upload successful',
            'upload_id' => $upload->id,
        ]);
    }
}
