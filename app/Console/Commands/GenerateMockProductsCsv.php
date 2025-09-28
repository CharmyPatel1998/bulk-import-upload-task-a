<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Writer;
use Illuminate\Support\Facades\Storage;

class GenerateMockProductsCsv extends Command
{
    protected $signature = 'app:generate-products-csv {rows=10000} {images=300}';
    protected $description = 'Generate mock products CSV with optional image filenames';

    public function handle()
    {
        $rows = $this->argument('rows');
        $imagesCount = $this->argument('images');

        $filePath = storage_path('app/products.csv');
        $csv = Writer::createFromPath($filePath, 'w+');
        $csv->insertOne(['sku','name','description','price','image_filename']);

        for ($i = 1; $i <= $rows; $i++) {
            $sku = "SKU" . str_pad($i, 5, '0', STR_PAD_LEFT);
            $name = "Product {$i}";
            $desc = "Description for product {$i}";
            $price = rand(1000, 10000)/100;

            $imgNum = rand(1, $imagesCount);
            $image_filename = "img{$imgNum}.jpg";

            $csv->insertOne([$sku, $name, $desc, $price, $image_filename]);
        }

        $this->info("Generated {$rows} products CSV at storage/app/products.csv");
    }
}
