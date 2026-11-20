<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('departments', 'is_active')) {
            Schema::table('departments', function (Blueprint $table): void {
                $table->boolean('is_active')->default(true)->after('code');
            });
        }

        DB::table('departments')->where('code', 'FIN')->update(['name' => 'Akunting', 'is_active' => true]);
        DB::table('departments')->where('code', 'SLS')->update(['is_active' => false]);
    }

    public function down(): void
    {
        DB::table('departments')->where('code', 'FIN')->update(['name' => 'Finance']);
        DB::table('departments')->where('code', 'SLS')->update(['is_active' => true]);

        if (Schema::hasColumn('departments', 'is_active')) {
            Schema::table('departments', function (Blueprint $table): void {
                $table->dropColumn('is_active');
            });
        }
    }
};
