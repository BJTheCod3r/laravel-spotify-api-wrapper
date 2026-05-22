<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('spotify_user_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('user_id')->unique();
            $table->string('spotify_user_id')->nullable()->index();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->string('token_type', 32)->default('Bearer');
            $table->json('scopes');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spotify_user_tokens');
    }
};
