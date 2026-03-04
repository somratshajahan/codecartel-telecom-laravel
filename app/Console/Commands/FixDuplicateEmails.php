<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FixDuplicateEmails extends Command
{
    protected $signature = 'fix:duplicate-emails';
    protected $description = 'Fix duplicate emails by appending suffix to non-admin accounts';

    public function handle()
    {
        $duplicates = DB::table('users')
            ->select('email', DB::raw('COUNT(*) as count'))
            ->groupBy('email')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate emails found.');
            return;
        }

        foreach ($duplicates as $duplicate) {
            $users = User::where('email', $duplicate->email)->get();
            $counter = 1;
            
            foreach ($users as $user) {
                if (!$user->is_admin) {
                    $newEmail = str_replace('@', '_user' . $counter . '@', $user->email);
                    $user->email = $newEmail;
                    $user->save();
                    $this->info("Updated user ID {$user->id}: {$duplicate->email} -> {$newEmail}");
                    $counter++;
                }
            }
        }

        $this->info('Duplicate emails fixed successfully!');
    }
}
