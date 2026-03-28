<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'firebase_uid',
        'name',
        'display_name',
        'phone',
        'email',
        'role',
        'is_active',
        'is_verified',
        'gender',
        'bio',
        'profile_photo_url',
        'user_preference',
        'date_of_birth',
        'onboarding_completed',
        'profile_completed',
        'profile_visibility',
        'show_phone',
        'show_email',
        'allow_messages',
        'language',
        'theme',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'gender' => 'string',
            'user_preference' => 'string',
            'date_of_birth' => 'date',
            'onboarding_completed' => 'boolean',
            'profile_completed' => 'boolean',
            'profile_visibility' => 'string',
            'show_phone' => 'boolean',
            'show_email' => 'boolean',
            'allow_messages' => 'boolean',
            'language' => 'string',
            'theme' => 'string',
        ];
    }

    /**
     * Get the driver profile associated with this user
     */
    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class);
    }

    /**
     * Get the driver verification associated with this user
     */
    public function driverVerification(): HasOne
    {
        return $this->hasOne(DriverVerification::class);
    }

    /**
     * Get the vehicles associated with this user
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get the bookings made by this user as a passenger
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'passenger_id');
    }

    /**
     * Get the saved routes for this user
     */
    public function savedRoutes(): HasMany
    {
        return $this->hasMany(SavedRoute::class);
    }

    /**
     * Get the FCM tokens for this user
     */
    public function fcmTokens(): HasMany
    {
        return $this->hasMany(FcmToken::class);
    }

    /**
     * Get the payment methods for this user
     */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }
}
