<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use App\Models\Upload;
use App\Models\Image;
use App\Jobs\ProcessCsvRow;
use PHPUnit\Framework\Attributes\Test;

class ProcessCsvRowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Fake the public storage
        Storage::fake('public');
    }

    #[Test]
    public function it_upserts_product_and_links_image()
    {
        // 1. Create dummy upload and image
        $upload = Upload::create([
            'filename' => 'test1.jpg',
            'checksum' => md5('test1'),
            'completed' => true,
        ]);

        $originalPath = 'uploads/original/test1.jpg';
        Storage::disk('public')->put($originalPath, 'dummy content');

        $image = Image::create([
            'upload_id' => $upload->id,
            'path' => $originalPath,
            'variants' => [
                256 => 'uploads/variants/test1_256.jpg',
                512 => 'uploads/variants/test1_512.jpg',
                1024 => 'uploads/variants/test1_1024.jpg',
            ],
        ]);

        // 2. CSV row
        $record = [
            'sku' => 'SKU10001',
            'name' => 'Test Product',
            'description' => 'Description here',
            'price' => '99.99',
            'image_filename' => 'test1.jpg',
        ];

        // 3. Dispatch job inline
        $job = new ProcessCsvRow($record);
        $job->handle();

        // 4. Assertions
        $product = Product::where('sku', 'SKU10001')->first();
        $this->assertNotNull($product, 'Product should exist after upsert');
        $this->assertEquals($image->id, $product->primary_image_id, 'Primary image should be linked');

        $image->refresh();
        $this->assertArrayHasKey(256, $image->variants);
        $this->assertArrayHasKey(512, $image->variants);
        $this->assertArrayHasKey(1024, $image->variants);
    }

    #[Test]
    public function it_updates_existing_product_without_duplicate()
    {
        // 1. Existing product
        $existingProduct = Product::create([
            'sku' => 'SKU10002',
            'name' => 'Old Name',
            'description' => 'Old desc',
            'price' => 5.00,
        ]);

        // 2. CSV row with updated data
        $record = [
            'sku' => 'SKU10002',
            'name' => 'Updated Name',
            'description' => 'Updated desc',
            'price' => 12.50,
        ];

        // 3. Dispatch job inline
        $job = new ProcessCsvRow($record);
        $job->handle();

        // 4. Assertions
        $product = Product::where('sku', 'SKU10002')->first();
        $this->assertEquals('Updated Name', $product->name, 'Product name should be updated');
        $this->assertEquals('Updated desc', $product->description, 'Product description should be updated');
        $this->assertEquals(12.50, $product->price, 'Product price should be updated');
    }
}
