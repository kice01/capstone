<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOpenFoodFacts extends Command
{
    protected $signature = 'openfoodfacts:import {--path= : Path to the compressed Open Food Facts TSV/CSV dump}';

    protected $description = 'Import Open Food Facts barcode names and categories into the local lookup cache';

    public function handle(): int
    {
        $path = $this->option('path') ?: storage_path('app/openfoodfacts/openfoodfacts-products.csv.gz');
        $stream = @gzopen($path, 'rb');

        if (!$stream) {
            $this->error("Cannot open compressed dataset: {$path}");
            return self::FAILURE;
        }

        $header = fgetcsv($stream, 0, "\t", '"', '\\');
        if (!$header) {
            gzclose($stream);
            $this->error('The dataset is empty or is not a valid gzip TSV file.');
            return self::FAILURE;
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $columns = array_flip($header);
        $codeIndex = $columns['code'] ?? $columns['_id'] ?? null;
        $nameIndex = $columns['product_name'] ?? $columns['product_name_en'] ?? null;
        $categoryIndex = $columns['categories_en'] ?? $columns['categories'] ?? $columns['categories_tags'] ?? null;
        $brandIndex = $columns['brands'] ?? null;

        if ($codeIndex === null || $nameIndex === null) {
            gzclose($stream);
            $this->error('The dump does not contain the expected barcode and product name columns.');
            return self::FAILURE;
        }

        $batch = [];
        $imported = 0;
        $importedAt = now();
        while (($row = fgetcsv($stream, 0, "\t", '"', '\\')) !== false) {
            $barcode = trim((string) ($row[$codeIndex] ?? ''));
            $name = trim((string) ($row[$nameIndex] ?? ''));
            if ($barcode === '' || $name === '') {
                continue;
            }

            $brand = trim((string) ($brandIndex !== null ? ($row[$brandIndex] ?? '') : ''));
            if ($brand !== '' && stripos($name, $brand) === false) {
                $name = $brand . ' ' . $name;
            }

            $categoryValue = trim((string) ($categoryIndex !== null ? ($row[$categoryIndex] ?? '') : ''));
            $categories = array_values(array_unique(array_filter(array_map(
                'trim',
                preg_split('/[,;]/', $categoryValue) ?: []
            ))));

            $batch[] = [
                'barcode' => $barcode,
                'product_name' => mb_substr($name, 0, 255),
                'categories' => json_encode($categories, JSON_UNESCAPED_UNICODE),
                'found' => true,
                'created_at' => $importedAt,
                'updated_at' => $importedAt,
            ];

            if (count($batch) >= 500) {
                $imported += $this->insertMissing($batch);
                $batch = [];
                if ($imported > 0 && $imported % 50000 < 500) {
                    $this->line("Imported {$imported} products...");
                }
            }
        }

        if ($batch) {
            $imported += $this->insertMissing($batch);
        }
        gzclose($stream);

        $this->info("Import complete. Added {$imported} barcode records to the local cache.");
        return self::SUCCESS;
    }

    private function insertMissing(array $rows): int
    {
        return DB::table('open_food_facts_cache')->insertOrIgnore($rows);
    }
}
