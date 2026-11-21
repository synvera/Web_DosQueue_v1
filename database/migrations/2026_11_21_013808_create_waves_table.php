<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waves', function(Blueprint $table){
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedBigInteger('max_quota');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waves');
    }
};
