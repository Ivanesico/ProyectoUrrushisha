<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('flavors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name', 180);
            $table->text('description')->nullable();

            $table->enum('tobacco_type', ['rubio', 'negro', 'herbal', 'sin_nicotina'])->nullable();

            $table->string('ingredients_text', 500)->nullable();
            $table->string('image_url', 500)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_public')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('flavors');
    }
};
