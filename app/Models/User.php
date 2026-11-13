<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\PermissionRegistrar;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'user_status',
        'surname',
        'first_name',
        'other_name',
        'address',
        'mobile_no',
        'gender',
        'dob',
        'mobile_no1',
        'interest',
        'current_stage',
        'instagram',
        'facebook',
        'snapchat',
        'twitter',
        'tiktok',
        'occupation',
        'qst1',
        'qst2',
        'qst3',
        'if_yes_qst2',
        'image',
        'reg_date',
        'email_verified_at',
        'user_type',
        'login_attempts',
        'email_verified_status',
        'remember_token',
        'interest_fashion',
        'applicant_type',
        'about_nyfew',
        'about_nyfew_ans',
        'full_length_image',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function makeMeParticipant()
    {
        return $this->hasOne(
            \App\Models\MakeMeParticipant::class,
            'user_id'
        );
    }

    

}
