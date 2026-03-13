<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentation_articles', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->text('content');
            $table->string('doc_type');
            $table->string('audience')->default('admin');
            $table->json('front_matter')->nullable();
            $table->foreignId('edited_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('key');
            $table->index('doc_type');
            $table->index('audience');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentation_articles');
    }
};
