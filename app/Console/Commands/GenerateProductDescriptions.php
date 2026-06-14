<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GenerateProductDescriptions extends Command
{
    protected $signature = 'products:generate-descriptions
                            {--only-empty : Only generate for products with short/missing descriptions}
                            {--id=        : Generate for a single product ID only}
                            {--dry-run    : Show what would be updated without saving}';

    protected $description = 'Generate rich 5-paragraph HTML descriptions for products (Uganda context)';

    public function handle()
    {
        $query = Product::query();

        if ($this->option('id')) {
            $query->where('id', $this->option('id'));
        } elseif ($this->option('only-empty')) {
            $query->where(function ($q) {
                $q->whereNull('description')
                  ->orWhere('description', '')
                  ->orWhereRaw('CHAR_LENGTH(description) < 100');
            });
        }

        $products = $query->orderBy('id')->get();

        if ($products->isEmpty()) {
            $this->info('No products found matching the criteria.');
            return 0;
        }

        $this->info("Found {$products->count()} product(s) to process.");
        $apiKey  = env('ANTHROPIC_API_KEY');
        $useApi  = !empty($apiKey);
        $dryRun  = $this->option('dry-run');

        if ($useApi) {
            $this->info('Using Claude API for generation.');
        } else {
            $this->warn('ANTHROPIC_API_KEY not set — using built-in curated descriptions.');
        }

        $done   = 0;
        $failed = 0;

        foreach ($products as $product) {
            $this->line("  → [{$product->id}] {$product->name}");

            try {
                $description = $useApi
                    ? $this->generateViaApi($product, $apiKey)
                    : $this->generateFallback($product);

                if ($dryRun) {
                    $this->line('     [dry-run] Would save ' . strlen($description) . ' chars');
                } else {
                    $product->description = $description;
                    $product->save();
                    $this->line('     ✓ Saved (' . strlen($description) . ' chars)');
                }
                $done++;
            } catch (\Exception $e) {
                $this->error("     ✗ Failed: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("\nDone. Updated: {$done}, Failed: {$failed}");
        return 0;
    }

    protected function generateViaApi(Product $product, string $apiKey): string
    {
        $prompt = "You are a professional health product copywriter for DTEHM Health Ministries in Uganda. "
            . "Write a rich, engaging product description for \"{$product->name}\" priced at UGX " . number_format($product->price_1, 0) . ". "
            . "Write exactly 5 paragraphs using HTML <p> tags only (no other HTML tags). "
            . "The audience is Ugandan families seeking natural Ayurvedic health solutions. "
            . "Cover: (1) what the product is and its primary benefit, "
            . "(2) how it addresses a common health challenge in Uganda, "
            . "(3) how to use it and expected results, "
            . "(4) why DTEHM and where to get it across Uganda, "
            . "(5) a warm call-to-action. "
            . "Be specific, warm, and professional. Output ONLY the 5 <p> tags, nothing else.";

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(45)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 1200,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Claude API HTTP ' . $response->status() . ': ' . $response->body());
        }

        $text = $response->json('content.0.text', '');
        if (empty($text)) {
            throw new \Exception('Empty response from Claude API');
        }

        return $text;
    }

    protected function generateFallback(Product $product): string
    {
        $name  = htmlspecialchars($product->name);
        $price = 'UGX ' . number_format($product->price_1, 0);
        $l     = strtolower($product->name);

        $benefit = match(true) {
            str_contains($l, 'heart') || str_contains($l, 'cardiac')
                => 'cardiovascular health and healthy blood circulation',
            str_contains($l, 'liver') || str_contains($l, 'livex')
                => 'liver detoxification and hepatic regeneration',
            str_contains($l, 'bone') || str_contains($l, 'joint') || str_contains($l, 'one joint')
                => 'bone density, joint flexibility, and musculoskeletal wellness',
            str_contains($l, 'weight') || str_contains($l, 'figor')
                => 'healthy weight management and metabolic balance',
            str_contains($l, 'immune') || str_contains($l, 'daily health')
                => 'daily immune system support and overall vitality',
            str_contains($l, 'eye') || str_contains($l, 'vision')
                => 'eye health, vision clarity, and ocular protection',
            str_contains($l, 'fertility') || str_contains($l, 'ovarin') || str_contains($l, 'vega') || str_contains($l, 'gyno')
                => 'reproductive health and hormonal balance',
            str_contains($l, 'infection')
                => 'fighting infections and boosting the body\'s natural defenses',
            str_contains($l, 'cough') || str_contains($l, 'crux') || str_contains($l, 'brovo')
                => 'respiratory health and relief from coughs and chest congestion',
            str_contains($l, 'glucon')
                => 'blood sugar regulation and metabolic wellness',
            str_contains($l, 'acid') || str_contains($l, 'colic') || str_contains($l, 'fibo')
                => 'digestive comfort, gut health, and gastrointestinal balance',
            str_contains($l, 'pros')
                => 'prostate health and male urinary wellness',
            str_contains($l, 'rhue') || str_contains($l, 'rub')
                => 'muscle relief, pain management, and anti-inflammatory support',
            str_contains($l, 'calcuro')
                => 'kidney and urinary tract health',
            str_contains($l, 'carespice') || str_contains($l, 'tyfi') || str_contains($l, 'tea')
                => 'digestive wellness, immunity, and daily detoxification',
            str_contains($l, 'six')
                => 'comprehensive multi-nutrient support and whole-body wellness',
            str_contains($l, 'force vital') || str_contains($l, 'vital')
                => 'energy, vitality, and physical strength restoration',
            str_contains($l, 'malawipe')
                => 'cleansing, detoxification, and internal purification',
            default
                => 'overall health, vitality, and natural wellness',
        };

        $form = str_contains($l, 'syrup') || str_contains($l, 'tonic') ? 'syrup'
              : (str_contains($l, 'tea') ? 'herbal tea blend'
              : (str_contains($l, 'rub') ? 'topical rub'
              : 'herbal supplement'));

        return "<p><strong>{$name}</strong> is a premium Ayurvedic {$form} formulated by DTEHM Health Ministries to support {$benefit} in Ugandan families. Drawing on centuries of Ayurvedic wisdom and modern herbal science, this product delivers a powerful blend of nature's finest botanicals, carefully selected and combined to provide maximum therapeutic benefit. Unlike synthetic pharmaceutical products, {$name} works gently and holistically with the body's own natural healing systems.</p>

<p>Across Uganda, health challenges related to {$benefit} affect millions of people in both urban centres like Kampala, Jinja, and Mbale, and rural communities in the western, northern, and eastern regions. Many Ugandans are turning to Ayurvedic solutions because they are effective, affordable, and free from the harsh side effects associated with conventional medications. {$name} was specifically designed with these communities in mind — offering a trusted, natural alternative that brings real results to real Ugandan families.</p>

<p>For best results, use {$name} consistently as directed. Adults should follow the recommended dosage guidelines on the packaging, which have been calibrated for optimal absorption and efficacy. Most users begin noticing positive changes within the first few weeks of regular use, with more significant improvements typically experienced after a full course of treatment. It is recommended to maintain a healthy diet, drink adequate water, and engage in regular light exercise alongside your DTEHM supplement regimen.</p>

<p>DTEHM Health Ministries is one of Uganda's fastest-growing Ayurvedic health networks, with a mission to make quality natural healthcare accessible to every Ugandan household. {$name} is stocked at authorised DTEHM stockists across the country, from major cities to district towns. Members of the DTEHM network also enjoy exclusive member pricing and earn commission points on referrals — making health and financial wellness go hand in hand. Download the DTEHM Members App to access your account, track your health journey, and reorder with ease.</p>

<p>Don't let health challenges hold you or your family back from living your fullest life. <strong>{$name}</strong> is available today at the accessible price of <strong>{$price}</strong>. Contact your nearest DTEHM stockist, call our customer care team, or visit <strong>dtehmhealth.com</strong> to order online. Join thousands of Ugandan families who have already made {$name} a cornerstone of their daily health routine — because your health is your most valuable investment.</p>";
    }
}
