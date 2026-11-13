<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Notifications\ResetPassword;
use App\Notifications\MakeMeInvitationNotification;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MakeMeParticipant extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'makeme_participants';

    protected $fillable = [
        'participant_number', 'first_name', 'surname', 'other_name', 'email',
        'mobile_no', 'mobile_no1', 'address', 'gender', 'dob', 'occupation',
        'skill_category', 'image', 'instagram', 'facebook', 'tiktok', 'twitter',
        'status', 'notes', 'approved_at', 'password', 'email_verified_at', 'last_login_at',
        'business_name',
        'profession',
        'age',
        'city',
        'state_of_origin',
        'alternative_email',
        'instagram_handle',
        'tiktok_handle',
        'facebook_handle',
        'x_handle',
        'linkedin_handle',
        'photo_path',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'dob' => 'date',
        'approved_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'age' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->surname ?? ''));
    }    


    public function sendPasswordResetNotification($token)
    {
        $this->notify(
            new MakeMeInvitationNotification($token)
        );
    }

    
    /**
     * Get the contestant's uploaded photograph URL.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (empty($this->photo_path)) {
            return null;
        }

        return asset('storage/' . ltrim($this->photo_path, '/'));
    }


    public function wallet()
    {
        return $this->hasOne(
            \App\Models\MakeMeWallet::class,
            'participant_id'
        );
    }

}
