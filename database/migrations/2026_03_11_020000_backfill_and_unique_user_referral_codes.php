<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'referral_code')) {
            return;
        }

        $usedCodes = [];
        $hasUpdatedAtColumn = Schema::hasColumn('users', 'updated_at');
        $users = DB::table('users')
            ->select('id', 'referral_code')
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            $referralCode = Str::upper(trim((string) ($user->referral_code ?? '')));

            if ($referralCode === '' || isset($usedCodes[$referralCode])) {
                do {
                    $referralCode = Str::upper(Str::random(8));
                } while (isset($usedCodes[$referralCode]));
            }

            $usedCodes[$referralCode] = true;

            if (($user->referral_code ?? null) === $referralCode) {
                continue;
            }

            $updateData = ['referral_code' => $referralCode];

            if ($hasUpdatedAtColumn) {
                $updateData['updated_at'] = now();
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update($updateData);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('referral_code');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'referral_code')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['referral_code']);
        });
    }
};