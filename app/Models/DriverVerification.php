<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverVerification extends Model
{
    use HasFactory;

    protected $table = 'driver_verifications';

    protected $fillable = [
        'user_id',
        'dl_number',
        'dl_expiry_date',
        'dl_front_image',
        'dl_back_image',
        'rc_number',
        'rc_front_image',
        'rc_back_image',
        'verification_status',
        'rejection_reason',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'dl_expiry_date' => 'date',
            'verified_at' => 'datetime',
            'verification_status' => VerificationStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user associated with this driver verification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
