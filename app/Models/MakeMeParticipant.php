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
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'dob' => 'date',
        'approved_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
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

    public function wallet()
    {
        return $this->hasOne(
            \App\Models\MakeMeWallet::class,
            'participant_id'
        );
    }

}
