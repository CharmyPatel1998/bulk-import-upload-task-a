<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Jobs\ProcessCsvRow;

class ImportProducts extends Command
{
    protected $signature = 'app:import-products {file : The path to the CSV file in storage/app}';
    protected $description = 'Import products from a CSV file using chunked processing';

    public function handle()
    {
        $file = storage_path('app/' . $this->argument('file'));

        if (!file_exists($file)) {
            $this->error("CSV file not found: $file");
            return 1;
        }

        $csv = Reader::createFromPath($file, 'r');
        $csv->setHeaderOffset(0);

        $stmt = Statement::create();
        $records = $stmt->process($csv);

        $total = 0;

        foreach ($records as $record) {
            $total++;
            // Dispatch each row to the queue for processing
            ProcessCsvRow::dispatch($record);
        }

        $this->info("Dispatched $total rows to the queue for processing.");
        return 0;
    }
}
