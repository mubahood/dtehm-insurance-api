<?php

namespace App\Admin\Controllers;

use App\Models\Product;
use App\Models\Utils;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductController extends AdminController
{
    protected $title = 'Products';

    protected function grid()
    {
        $grid = new Grid(new Product());
        $grid->disableBatchActions();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableExport();
        $grid->quickSearch('name')->placeholder('Search by product name...');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('name', 'Product Name');
            $cats = \App\Models\ProductCategory::all();
            $filter->equal('category', 'Category')->select($cats->pluck('category', 'id'));
            $filter->between('price_1', 'App Price (UGX)');
            $filter->between('price_2', 'Website Price (UGX)');
            $filter->between('created_at', 'Created Date')->datetime();
        });
        $grid->model()->orderBy('id', 'asc');
        $grid->column('feature_photo', __('Photo'))->lightbox(['width' => 80, 'height' => 80]);
        $grid->column('name', __('Product Name'))->sortable();
        $grid->column('price_1', __('App Price'))->display(function ($price) {
            return "<span style='color:#1a73e8;font-weight:600;'>UGX " . number_format($price, 0) . "</span>";
        })->sortable();
        $grid->column('price_2', __('Website Price'))->display(function ($price) {
            if (empty($price)) {
                return "<span class='text-muted'>—</span>";
            }
            return "<span style='color:#28a745;font-weight:600;'>UGX " . number_format($price, 0) . "</span>";
        })->sortable();
        $grid->column('description', __('Description'))->display(function ($desc) {
            $text = strip_tags($desc ?? '');
            if (strlen($text) < 50) {
                return "<span class='label label-warning'>Short — needs AI content</span>";
            }
            return "<span class='label label-success'>✓ Has description</span><br><small class='text-muted'>" . mb_substr($text, 0, 60) . "…</small>";
        })->width(200);
        $grid->column('sell', __('Actions'))->display(function () {
            $buyUrl = admin_url('ordered-items/create?product_id=' . $this->id);
            return '<a href="' . $buyUrl . '" class="btn btn-xs btn-success" style="margin-right:4px;"><i class="fa fa-shopping-cart"></i> Sell</a>'
                 . '<a href="' . admin_url('products/' . $this->id . '/generate-description') . '" class="btn btn-xs btn-primary" onclick="return confirm(\'Generate AI description for this product?\')"><i class="fa fa-magic"></i> AI Desc</a>';
        })->width(160);
        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Product::findOrFail($id));
        $show->field('id', __('ID'));
        $show->field('name', __('Product Name'));
        $show->field('price_1', __('App Price (UGX)'))->as(function ($p) { return 'UGX ' . number_format($p, 0); });
        $show->field('price_2', __('Website Price (UGX)'))->as(function ($p) { return $p ? 'UGX ' . number_format($p, 0) : 'Same as App Price'; });
        $show->field('description', __('Description'));
        $show->field('feature_photo', __('Feature Photo'))->image();
        $show->field('category', __('Category'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));
        return $show;
    }

    protected function form()
    {
        $form = new Form(new Product());
        $form->hidden('local_id')->value(Utils::get_unique_text());
        $form->hidden('currency')->default('UGX');
        $form->hidden('p_type')->default('product');

        // ── Basic Info ───────────────────────────────────────────────────────
        $form->divider('Basic Information');
        $form->text('name', __('Product Name'))
            ->rules('required|max:255')
            ->placeholder('e.g. Livex Syrup');

        $form->image('feature_photo', __('Feature Photo'))
            ->uniqueName()
            ->help('Main product image shown in listings and on the website');

        $cats = \App\Models\ProductCategory::all();
        $form->select('category', __('Category'))
            ->options($cats->pluck('category', 'id'))
            ->rules('required');

        $form->tags('tags', __('Tags'))
            ->placeholder('e.g. liver, detox, herbal, syrup');

        $form->text('metric', __('Unit / Metric'))
            ->placeholder('e.g. bottle, pack, piece')
            ->default('piece');

        // ── Status & Stock ───────────────────────────────────────────────────
        $form->divider('Status & Availability');
        $form->radio('status', __('Status'))
            ->options(['Active' => 'Active', 'Inactive' => 'Inactive'])
            ->default('Active');

        $form->radio('in_stock', __('In Stock'))
            ->options(['Yes' => 'Yes', 'No' => 'No'])
            ->default('Yes');

        // ── Pricing ──────────────────────────────────────────────────────────
        $form->divider('Pricing');
        $form->html('<div class="alert alert-info" style="margin:0 0 14px;">
            <i class="fa fa-info-circle"></i> <strong>Two prices:</strong>
            <b>App Price</b> = members-only price in the mobile app.
            <b>Website Price</b> = price shown publicly on the website (defaults to App Price if left blank).
        </div>');

        $form->text('price_1', __('App Price (UGX)'))
            ->rules('required|numeric|min:0')
            ->placeholder('e.g. 143000')
            ->help('Price shown in the DTEHM mobile app (members only)');

        $form->text('price_2', __('Website Price (UGX)'))
            ->rules('nullable|numeric|min:0')
            ->placeholder('Leave blank to use App Price')
            ->help('Price shown on the public website. Leave blank to default to App Price.');

        $form->text('points', __('Points Awarded'))
            ->rules('nullable|numeric|min:0')
            ->placeholder('e.g. 50')
            ->help('Loyalty points members earn when purchasing this product');

        // ── Description ──────────────────────────────────────────────────────
        $form->divider('Description');

        if (!request()->is('*/create')) {
            $productId = last(explode('/', rtrim(request()->path(), '/edit')));
            if (is_numeric($productId)) {
                $aiUrl = admin_url('products/' . $productId . '/generate-description');
                $form->html('
                    <div class="alert alert-success" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
                        <span><i class="fa fa-magic"></i> <strong>AI Description Generator</strong> — Auto-write a rich 5-paragraph description in Ugandan context.</span>
                        <a href="' . $aiUrl . '" class="btn btn-success btn-sm" onclick="return confirm(\'Generate AI description? This will replace the current description.\')">
                            <i class="fa fa-magic"></i> Generate AI Description
                        </a>
                    </div>
                ');
            }
        }

        $form->quill('description', __('Description'))
            ->placeholder('Enter product description, or use the AI generator above...');

        // ── Variants ─────────────────────────────────────────────────────────
        $form->divider('Color & Size Options');
        $form->radio('has_colors', __('Does it have colour options?'))
            ->options(['Yes' => 'Yes', 'No' => 'No'])
            ->when('Yes', function (Form $form) {
                $colors = ['Red'=>'Red','Blue'=>'Blue','Yellow'=>'Yellow','Green'=>'Green',
                    'Orange'=>'Orange','Purple'=>'Purple','Brown'=>'Brown','Pink'=>'Pink',
                    'Black'=>'Black','White'=>'White','Gray'=>'Gray','Cyan'=>'Cyan',
                    'Navy'=>'Navy','Maroon'=>'Maroon','Olive'=>'Olive','Silver'=>'Silver'];
                $form->tags('colors', 'Select Colours')->options($colors);
            })->default('No');

        $form->radio('has_sizes', __('Does it have size options?'))
            ->options(['Yes' => 'Yes', 'No' => 'No'])
            ->when('Yes', function (Form $form) {
                $sizes = ['XS'=>'XS','S'=>'S','M'=>'M','L'=>'L','XL'=>'XL',
                    'XXL'=>'XXL','XXXL'=>'XXXL'];
                $form->tags('sizes', 'Select Sizes')->options($sizes);
            })->default('No');

        // ── Additional Images ────────────────────────────────────────────────
        $form->divider('Additional Images');
        $form->hasMany('images', 'Product Images', function (Form\NestedForm $form) {
            $u = Auth::user();
            $form->image('src', 'Image')->uniqueName();
            $form->hidden('administrator_id')->value($u ? $u->id : 0);
        });

        // ── Specifications ───────────────────────────────────────────────────
        $form->divider('Specifications');
        $form->hasMany('specifications', 'Specifications', function (Form\NestedForm $form) {
            $form->text('name', 'Specification')->placeholder('e.g. Volume, Dosage, Ingredients')->rules('required|max:255');
            $form->text('value', 'Value')->placeholder('e.g. 500ml, 2 capsules daily, Ayurvedic herbs');
        });

        // ── Homepage Sections ─────────────────────────────────────────────────
        $form->divider('Homepage Feature Sections');
        $form->radio('home_section_1', __('Flash Sales Section'))
            ->options(['Yes' => 'Yes — Show here', 'No' => 'No'])
            ->default('No');
        $form->radio('home_section_2', __('Super Buyer Section'))
            ->options(['Yes' => 'Yes — Show here', 'No' => 'No'])
            ->default('No');
        $form->radio('home_section_3', __('Top Products Section'))
            ->options(['Yes' => 'Yes — Show here', 'No' => 'No'])
            ->default('No');

        // ── Saving hook ───────────────────────────────────────────────────────
        $form->saving(function (Form $form) {
            $user = Auth::user();
            if ($user) {
                $form->user = $user->id;
            }
            if ($form->isCreating()) {
                $form->date_added = now();
            }
            $form->date_updated = now();
            if (empty($form->price_2)) {
                $form->price_2 = $form->price_1;
            }
            if (empty($form->metric)) {
                $form->metric = 'piece';
            }
            if (empty($form->tags)) {
                $words = explode(' ', strtolower($form->name ?? ''));
                $form->tags = implode(',', array_slice($words, 0, 5));
            }
        });

        $form->disableViewCheck();
        $form->disableCreatingCheck();
        $form->disableEditingCheck();

        return $form;
    }

    /**
     * Generate AI description for a single product via admin action.
     */
    public function generateDescription(int $id)
    {
        $product = Product::findOrFail($id);
        $description = $this->buildAiDescription($product);

        $product->description = $description;
        $product->save();

        admin_toastr("AI description generated for \"{$product->name}\".", 'success');
        return redirect(admin_url('products/' . $id . '/edit'));
    }

    /**
     * Build a rich 5-paragraph HTML description for a product.
     * Uses Claude API if ANTHROPIC_API_KEY is set, otherwise uses curated content.
     */
    protected function buildAiDescription(Product $product): string
    {
        $apiKey = config('services.anthropic.key', env('ANTHROPIC_API_KEY'));

        if ($apiKey) {
            try {
                return $this->generateViaClaudeApi($product, $apiKey);
            } catch (\Exception $e) {
                \Log::warning('Claude API description generation failed, using fallback', [
                    'product' => $product->name,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $this->generateFallbackDescription($product);
    }

    protected function generateViaClaudeApi(Product $product, string $apiKey): string
    {
        $prompt = "You are a health product copywriter for DTEHM Health Ministries in Uganda. "
            . "Write a rich, engaging product description for \"{$product->name}\" (UGX " . number_format($product->price_1, 0) . "). "
            . "Write exactly 5 paragraphs in HTML format (use <p> tags only). "
            . "Context: This is an Ayurvedic/herbal health supplement sold across Uganda. "
            . "Mention benefits, ingredients concept, how it helps Ugandan families, dosage guidance, and call to action. "
            . "Be warm, professional, and specific. No markdown — pure HTML <p> tags only.";

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 1024,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('API error: ' . $response->status());
        }

        return $response->json('content.0.text', '');
    }

    protected function generateFallbackDescription(Product $product): string
    {
        $name    = htmlspecialchars($product->name);
        $price   = 'UGX ' . number_format($product->price_1, 0);
        $lname   = strtolower($product->name);

        // Detect product type for contextual copy
        $isSyrup      = str_contains($lname, 'syrup') || str_contains($lname, 'tonic');
        $isTea        = str_contains($lname, 'tea');
        $isEye        = str_contains($lname, 'eye') || str_contains($lname, 'vision');
        $isHeart      = str_contains($lname, 'heart') || str_contains($lname, 'cardiac');
        $isLiver      = str_contains($lname, 'liver') || str_contains($lname, 'livex');
        $isBone       = str_contains($lname, 'bone') || str_contains($lname, 'joint');
        $isWeight     = str_contains($lname, 'weight') || str_contains($lname, 'figor');
        $isImmune     = str_contains($lname, 'immune') || str_contains($lname, 'daily health');
        $isFertility  = str_contains($lname, 'fertility') || str_contains($lname, 'ovarin') || str_contains($lname, 'vega') || str_contains($lname, 'gyno');
        $isInfection  = str_contains($lname, 'infection');
        $isCough      = str_contains($lname, 'cough') || str_contains($lname, 'crux');
        $isDiabetes   = str_contains($lname, 'glucon') || str_contains($lname, 'diabet');
        $isDig        = str_contains($lname, 'acid') || str_contains($lname, 'colic') || str_contains($lname, 'fibo') || str_contains($lname, 'digest');
        $isProstate   = str_contains($lname, 'pros');

        $form   = $isSyrup ? 'syrup' : ($isTea ? 'herbal tea' : 'capsule');
        $benefit = match(true) {
            $isHeart     => 'cardiovascular health and heart wellness',
            $isLiver     => 'liver detoxification and hepatic function',
            $isBone      => 'bone density, joint flexibility, and musculoskeletal health',
            $isWeight    => 'healthy weight management and metabolic balance',
            $isImmune    => 'daily immune system support and overall vitality',
            $isEye       => 'eye health, vision clarity, and ocular wellness',
            $isFertility => 'reproductive health and hormonal balance',
            $isInfection => 'fighting infections and strengthening the body\'s natural defenses',
            $isCough     => 'respiratory health and relief from coughs and chest congestion',
            $isDiabetes  => 'blood sugar regulation and metabolic wellness',
            $isDig       => 'digestive comfort, gut health, and gastrointestinal balance',
            $isProstate  => 'prostate health and male urinary wellness',
            default      => 'overall health, vitality, and natural wellness',
        };

        return "
<p><strong>{$name}</strong> is a premium Ayurvedic health {$form} formulated by DTEHM Health Ministries to support {$benefit} among Ugandan families. Crafted from a careful blend of natural herbs and botanical extracts, this product brings the ancient wisdom of Ayurveda into modern Ugandan homes, offering a safe and effective approach to health management without harsh chemicals or synthetic additives.</p>

<p>In Uganda, many families struggle with health challenges that are often manageable through the right nutritional support. {$name} was developed in response to this need — providing a scientifically inspired, plant-based formula that works in harmony with the body's natural processes. Each batch is produced to strict quality standards to ensure purity, potency, and consistency so that every member of your family receives the full benefits with every dose.</p>

<p>Regular use of {$name} helps to restore balance and support the body's innate healing capacity. Whether you are managing a specific health concern or looking to maintain optimal wellness as part of your daily routine, this product serves as a trusted partner. It is suitable for adults of all ages and can be incorporated into a healthy lifestyle alongside a balanced diet and regular physical activity, which is especially important in the Ugandan context of active community life.</p>

<p>DTEHM Health Ministries is proud to bring this premium health solution to communities across Uganda — from Kampala and Kasese to Mbale, Gulu, and beyond. {$name} is available through DTEHM stockists and authorised distributors nationwide, making quality health care accessible to all Ugandans. Members of the DTEHM network also benefit from exclusive pricing through the DTEHM mobile app, where ordering is fast, secure, and delivered to your doorstep.</p>

<p>Invest in your health today with {$name} — priced at only <strong>{$price}</strong>. To order, contact your nearest DTEHM stockist, call our customer care line, or download the DTEHM Members App for seamless online ordering. Your health is your greatest wealth, and with {$name}, you have a natural, trusted solution by your side every step of the way.</p>
        ";
    }
}
