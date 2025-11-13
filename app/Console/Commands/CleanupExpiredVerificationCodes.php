<?php

namespace App\Console\Commands;

use App\Models\VerificationCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredVerificationCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verification-codes:cleanup {--days=7 : Number of days to keep codes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired verification codes older than specified days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');

        $this->info("Cleaning up verification codes older than {$days} days...");

        $count = VerificationCode::where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$count} expired verification codes.");

        Log::info("Cleanup command executed: {$count} verification codes deleted");

        return Command::SUCCESS;
    }
}