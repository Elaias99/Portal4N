<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Role::firstOrCreate(['name' => 'jefe']);
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::where('name', 'jefe')->delete();
        
    }
};
