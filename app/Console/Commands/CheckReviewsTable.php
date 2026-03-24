<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckReviewsTable extends Command
{
    protected $signature = 'check:reviews-table';
    protected $description = 'Check if reviews table exists and show its structure';

    public function handle()
    {
        if (Schema::hasTable('reviews')) {
            $this->info('✓ Reviews table exists!');
            
            $columns = DB::select("DESCRIBE reviews");
            $this->info("\nReviews table columns:");
            foreach ($columns as $col) {
                $this->line("  - {$col->Field} ({$col->Type})");
            }
            
            // Check specifically for comment field
            $hasComment = collect($columns)->contains(fn($col) => $col->Field === 'comment');
            if ($hasComment) {
                $this->info("\n✓ Comment field exists!");
            } else {
                $this->error("\n✗ Comment field NOT found!");
            }
        } else {
            $this->error('✗ Reviews table does NOT exist yet.');
        }
    }
}
