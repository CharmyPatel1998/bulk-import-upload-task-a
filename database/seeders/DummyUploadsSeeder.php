<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Upload;
use App\Models\Image;

class DummyUploadsSeeder extends Seeder
{
    public function run()
    {
        $imagesCount = 300;

        for ($i = 1; $i <= $imagesCount; $i++) {
            $filename = "img{$i}.jpg";

            $upload = Upload::create([
                'filename' => $filename,
                'completed' => true,
            ]);

            Image::create([
                'upload_id' => $upload->id,
                'path' => "uploads/{$filename}",
            ]);
        }

        $this->command->info("Seeded {$imagesCount} dummy uploads and images.");
    }
}
