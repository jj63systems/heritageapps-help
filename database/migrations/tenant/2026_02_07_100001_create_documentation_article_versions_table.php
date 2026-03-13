<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentation_article_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documentation_article_id')->constrained('documentation_articles')->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('title');
            $table->text('content');
            $table->string('change_summary')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['documentation_article_id', 'version_number']);
            $table->index('documentation_article_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentation_article_versions');
    }
};
