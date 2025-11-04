<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['ongoing', 'completed', 'on_hold'])->default('ongoing');
            $table->decimal('share_price', 15, 2)->default(0);
            $table->integer('total_shares')->default(0)->comment('Computed: total shares sold');
            $table->integer('shares_sold')->default(0)->comment('Computed: same as total_shares');
            $table->string('image')->nullable();
            $table->decimal('total_investment', 15, 2)->default(0)->comment('Computed: sum of share purchases');
            $table->decimal('total_returns', 15, 2)->default(0)->comment('Computed: sum of returns distributed');
            $table->decimal('total_expenses', 15, 2)->default(0)->comment('Computed: sum of expenses');
            $table->decimal('total_profits', 15, 2)->default(0)->comment('Computed: sum of profits');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('created_by_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
}
