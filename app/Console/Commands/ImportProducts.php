<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:products {--dry-run : Run without saving to database} {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from dtehm_products.csv file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->info('║          DTEHM PRODUCTS IMPORT - CSV → DATABASE              ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Check if file exists
        $filePath = base_path('dtehm_products.csv');
        if (!file_exists($filePath)) {
            $this->error("❌ CSV file not found at: {$filePath}");
            return 1;
        }

        // Load and parse CSV
        $this->info('📂 Loading CSV file...');
        $products = $this->loadCsvData($filePath);
        
        if (empty($products)) {
            $this->error('❌ No products found in CSV file');
            return 1;
        }

        $count = count($products);
        $this->info("   ✓ Found {$count} products");
        $this->newLine();

        // Display sample products
        $this->info('📋 Sample products:');
        foreach (array_slice($products, 0, 5) as $product) {
            $this->line("   - {$product['name']} | {$product['case_size']} | UGX " . number_format($product['price']));
        }
        $this->newLine();

        // Confirmation
        if (!$force && !$isDryRun) {
            $this->warn('⚠️  WARNING: This will TRUNCATE the products table and delete all existing products!');
            if (!$this->confirm('Do you want to proceed?', false)) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        if ($isDryRun) {
            $this->warn('⚠️  Running in DRY-RUN mode - no changes will be saved');
            $this->newLine();
        }

        try {
            // Truncate products table (before transaction since truncate auto-commits)
            if (!$isDryRun) {
                $this->info('🗑️  Truncating products table...');
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::table('products')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                $this->info('   ✓ Products table cleared');
            } else {
                $this->info('🔄 DRY RUN: Would truncate products table');
            }

            // Start transaction for imports
            DB::beginTransaction();

            // Import products
            $this->info('📦 Importing products...');
            $bar = $this->output->createProgressBar(count($products));
            $bar->start();

            $imported = 0;
            $failed = 0;
            $errors = [];

            foreach ($products as $productData) {
                try {
                    if (!$isDryRun) {
                        // Use DB::table to bypass fillable restrictions
                        DB::table('products')->insert([
                            'name' => $productData['name'],
                            'metric' => $productData['case_size'],
                            'price_1' => $productData['price'],
                            'currency' => 'UGX',
                            'status' => 'Active',
                            'in_stock' => 'Yes',
                            'p_type' => 'product',
                            'local_id' => 'PROD-' . str_pad($productData['id'], 5, '0', STR_PAD_LEFT),
                            'description' => $productData['name'] . ' - ' . $productData['case_size'],
                            'points' => 10, // Default points
                            'review_count' => 0,
                            'average_rating' => 0.00,
                            'home_section_1' => 'No',
                            'home_section_2' => 'No',
                            'home_section_3' => 'No',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $imported++;
                    } else {
                        $imported++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Product {$productData['name']}: {$e->getMessage()}";
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            if ($isDryRun) {
                $this->info('🔄 DRY RUN: Rolling back transaction...');
                DB::rollBack();
            } else {
                DB::commit();
                $this->info('✅ Transaction committed');
            }

            // Display results
            $this->newLine();
            $this->info('╔═══════════════════════════════════════════════════════════════╗');
            $this->info('║                     IMPORT SUMMARY                            ║');
            $this->info('╚═══════════════════════════════════════════════════════════════╝');
            $totalCount = count($products);
            $this->info("   Total products processed:  {$totalCount}");
            $this->info("   Successfully imported:     {$imported}");
            $this->info("   Failed:                    {$failed}");

            if (!empty($errors)) {
                $this->newLine();
                $this->warn('⚠️  Errors encountered:');
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->line("   - {$error}");
                }
                if (count($errors) > 10) {
                    $this->line("   ... and " . (count($errors) - 10) . " more errors");
                }
            }

            if ($isDryRun) {
                $this->newLine();
                $this->warn('🔄 DRY RUN COMPLETE: No data was saved to the database');
                $this->info('   Run without --dry-run flag to perform actual import');
            }

            $this->newLine();
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Import failed: ' . $e->getMessage());
            Log::error('Product import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Load and parse CSV data
     *
     * @param string $filePath
     * @return array
     */
    protected function loadCsvData($filePath)
    {
        $products = [];
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Read header
            $header = fgetcsv($handle);
            
            // Process each row
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 4) {
                    continue; // Skip invalid rows
                }

                // Parse price - remove commas and quotes
                $priceStr = str_replace([',', '"'], '', $row[3]);
                $price = is_numeric($priceStr) ? (float)$priceStr : 0;

                $products[] = [
                    'id' => $row[0],
                    'name' => trim($row[1]),
                    'case_size' => trim($row[2]),
                    'price' => $price,
                ];
            }
            fclose($handle);
        }

        return $products;
    }
}
