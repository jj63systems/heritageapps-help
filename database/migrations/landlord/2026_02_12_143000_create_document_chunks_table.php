<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('file_path')->index();
            $table->string('section_title')->nullable();
            $table->text('content');
            $table->json('embedding');
            $table->integer('content_length');
            $table->string('content_hash');
            $table->timestamp('file_modified_at')->nullable();
            $table->timestamps();

            $table->index(['file_path', 'content_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
