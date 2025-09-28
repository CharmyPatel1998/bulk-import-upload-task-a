<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Upload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCsvRow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $record;

    /**
     * Create a new job instance.
     */
    public function __construct(array $record)
    {
        $this->record = $record;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // 1. Validate required columns
        $sku  = $this->record['sku'] ?? null;
        $name = $this->record['name'] ?? null;

        // 2. Upsert Product
        $product = Product::updateOrCreate(
            ['sku' => $sku],
            [
                'name'        => $name,
                'description' => $this->record['description'] ?? null,
                'price'       => $this->record['price'] ?? null,
            ]
        );

        // 3. Link primary image if image_filename exists
        if (!empty($this->record['image_filename'])) {
           $upload = Upload::where('filename', $this->record['image_filename'])->first();

            if (!$upload) {
                Log::info("Upload not found or not completed for filename: {$filename}");
                return;
            }

            if (!$upload->images()->exists()) {
                Log::info("No images linked to upload id {$upload->id} for filename: {$filename}");
                return;
            }

            $imageId = $upload->images()->first()->id;

            // 4. No-op if already attached
            if ($product->primary_image_id !== $imageId) {
                $product->primary_image_id = $imageId;
                $product->save();
                Log::info("Linked image {$imageId} to product {$product->sku}");
            } else {
                Log::info("Image already linked to product {$product->sku}, skipping re-attach.");
            }
        }
    }
}
