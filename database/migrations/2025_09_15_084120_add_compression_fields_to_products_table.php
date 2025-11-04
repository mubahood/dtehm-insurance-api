<?php

use App\Models\TinifyModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompressionFieldsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Compression status and metadata
            if (!Schema::hasColumn('products', 'is_compressed')) {
                $table->string('is_compressed')->nullable()->default('no');
            }
            if (!Schema::hasColumn('products', 'compress_status')) {
                $table->string('compress_status')->nullable(); // pending, completed, failed
            }
            if (!Schema::hasColumn('products', 'compress_status_message')) {
                $table->text('compress_status_message')->nullable();
            }
            if (!Schema::hasColumn('products', 'original_size')) {
                $table->decimal('original_size', 15, 2)->nullable()->default(0);
            }
            if (!Schema::hasColumn('products', 'compressed_size')) {
                $table->decimal('compressed_size', 15, 2)->nullable()->default(0);
            }
            if (!Schema::hasColumn('products', 'compression_ratio')) {
                $table->decimal('compression_ratio', 8, 4)->nullable();
            }
            if (!Schema::hasColumn('products', 'compression_method')) {
                $table->string('compression_method')->nullable();
            }
            if (!Schema::hasColumn('products', 'original_image_url')) {
                $table->text('original_image_url')->nullable();
            }
            if (!Schema::hasColumn('products', 'compressed_image_url')) {
                $table->text('compressed_image_url')->nullable();
            }
            if (!Schema::hasColumn('products', 'tinify_model_id')) {
                $table->unsignedBigInteger('tinify_model_id')->nullable();
            }
            if (!Schema::hasColumn('products', 'compression_started_at')) {
                $table->timestamp('compression_started_at')->nullable();
            }
            if (!Schema::hasColumn('products', 'compression_completed_at')) {
                $table->timestamp('compression_completed_at')->nullable();
            }

            // Foreign key to tinify_models (already added above as unsignedBigInteger)

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['tinify_model_id']);
            $table->dropColumn([
                'is_compressed',
                'compress_status',
                'compress_status_message',
                'original_size',
                'compressed_size',
                'compression_ratio',
                'compression_method',
                'original_image_url',
                'compressed_image_url',
                'tinify_model_id',
                'compression_started_at',
                'compression_completed_at'
            ]);
        });
    }
}
