<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default(UserRole::User->value)->after('password')->index();
            }
        });

        if (Schema::hasColumn('users', 'is_admin')) {
            DB::table('users')
                ->where('is_admin', true)
                ->update(['role' => UserRole::Admin->value]);

            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropIndex(['is_admin']);
                } catch (Throwable) {
                    //
                }

                $table->dropColumn('is_admin');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_admin')) {
                $table->boolean('is_admin')->default(false)->after('password')->index();
            }
        });

        if (Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('role', UserRole::Admin->value)
                ->update(['is_admin' => true]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
