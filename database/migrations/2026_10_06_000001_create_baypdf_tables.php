<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('baypdf_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('document_type', 80);
            $table->timestamps();
        });
        Schema::create('baypdf_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('baypdf_templates')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->unsignedInteger('lock_version')->default(1);
            $table->json('document');
            $table->json('variables');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['template_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baypdf_versions');
        Schema::dropIfExists('baypdf_templates');
    }
};
