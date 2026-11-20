<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subcategories')) {
            Schema::create('subcategories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained()->restrictOnDelete();
                $table->string('name', 120);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['category_id', 'name']);
            });
        }

        if (! Schema::hasTable('issue_types')) {
            Schema::create('issue_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained()->restrictOnDelete();
                $table->foreignId('subcategory_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name', 160);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['category_id', 'is_active']);
            });
        }

        if (! Schema::hasColumn('tickets', 'subcategory_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreignId('subcategory_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
                $table->foreignId('issue_type_id')->nullable()->after('subcategory_id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasTable('ticket_attachments')) {
            Schema::create('ticket_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained()->restrictOnDelete();
                $table->foreignId('comment_id')->nullable()->constrained('ticket_comments')->nullOnDelete();
                $table->foreignId('user_id')->constrained()->restrictOnDelete();
                $table->string('original_name', 255);
                $table->string('stored_path', 500);
                $table->string('mime_type', 150)->nullable();
                $table->unsignedBigInteger('size');
                $table->timestamps();
                $table->index(['ticket_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('issue_types');
        Schema::dropIfExists('subcategories');
        Schema::dropIfExists('notifications');
        if (Schema::hasColumn('tickets', 'subcategory_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropForeign(['subcategory_id']);
                $table->dropForeign(['issue_type_id']);
                $table->dropColumn(['subcategory_id', 'issue_type_id']);
            });
        }
    }
};
