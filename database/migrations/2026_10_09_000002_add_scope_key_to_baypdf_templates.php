<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('baypdf_templates', function (Blueprint $table): void {
            $table->string('scope_key', 191)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('baypdf_templates', function (Blueprint $table): void {
            $table->dropIndex(['scope_key']);
            $table->dropColumn('scope_key');
        });
    }
};
