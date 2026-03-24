<?php

namespace App\Console\Commands;

use App\Models\Review;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class TestCommentValidation extends Command
{
    protected $signature = 'test:comment-validation';
    protected $description = 'Test comment field validation (max 500 chars)';

    public function handle()
    {
        $this->info('Testing comment field validation...\n');

        $rules = Review::rules();
        
        // Test 1: Valid comment with 500 characters
        $data1 = [
            'ride_id' => 1,
            'reviewer_id' => 1,
            'reviewee_id' => 2,
            'rating' => 5,
            'comment' => str_repeat('a', 500),
        ];

        $v1 = Validator::make($data1, $rules);
        $this->line('Test 1: Comment with 500 characters');
        $this->line('  Valid: ' . ($v1->passes() ? '✓ PASS' : '✗ FAIL'));
        if ($v1->fails()) {
            $this->line('  Errors: ' . json_encode($v1->errors()->all()));
        }

        // Test 2: Comment exceeding 500 characters
        $data2 = [
            'ride_id' => 1,
            'reviewer_id' => 1,
            'reviewee_id' => 2,
            'rating' => 5,
            'comment' => str_repeat('a', 501),
        ];

        $v2 = Validator::make($data2, $rules);
        $this->line('\nTest 2: Comment with 501 characters (should fail)');
        $this->line('  Valid: ' . ($v2->passes() ? '✗ FAIL (should have failed)' : '✓ PASS (correctly rejected)'));
        if ($v2->fails()) {
            $this->line('  Errors: ' . json_encode($v2->errors()->all()));
        }

        // Test 3: Null comment (should be allowed)
        $data3 = [
            'ride_id' => 1,
            'reviewer_id' => 1,
            'reviewee_id' => 2,
            'rating' => 5,
            'comment' => null,
        ];

        $v3 = Validator::make($data3, $rules);
        $this->line('\nTest 3: Null comment (should be allowed)');
        $this->line('  Valid: ' . ($v3->passes() ? '✓ PASS' : '✗ FAIL'));
        if ($v3->fails()) {
            $this->line('  Errors: ' . json_encode($v3->errors()->all()));
        }

        // Test 4: Empty string comment (should be allowed)
        $data4 = [
            'ride_id' => 1,
            'reviewer_id' => 1,
            'reviewee_id' => 2,
            'rating' => 5,
            'comment' => '',
        ];

        $v4 = Validator::make($data4, $rules);
        $this->line('\nTest 4: Empty string comment (should be allowed)');
        $this->line('  Valid: ' . ($v4->passes() ? '✓ PASS' : '✗ FAIL'));
        if ($v4->fails()) {
            $this->line('  Errors: ' . json_encode($v4->errors()->all()));
        }

        // Test 5: Short comment
        $data5 = [
            'ride_id' => 1,
            'reviewer_id' => 1,
            'reviewee_id' => 2,
            'rating' => 5,
            'comment' => 'Great ride!',
        ];

        $v5 = Validator::make($data5, $rules);
        $this->line('\nTest 5: Short comment "Great ride!"');
        $this->line('  Valid: ' . ($v5->passes() ? '✓ PASS' : '✗ FAIL'));
        if ($v5->fails()) {
            $this->line('  Errors: ' . json_encode($v5->errors()->all()));
        }

        $this->info('\n✓ All validation tests completed!');
    }
}
