<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('users')->where('role', 'Sales')->update(['role' => 'Admin']);
    }

    public function down(): void
    {
    }
};
