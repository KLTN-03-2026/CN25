<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'is_active',
        'is_email_verified',
        'birthday',
        'gender',
        'country',
        'city',
        'timezone',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_email_verified' => 'boolean',
            'birthday' => 'date',
            'last_login_at' => 'datetime',
        ];
    }

    public function enrolledCourses(): HasMany
    {
        return $this->hasMany(UserCourse::class);
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function exerciseScores(): HasMany
    {
        return $this->hasMany(ExerciseScore::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isVerified(): bool
    {
        return (bool) $this->is_email_verified;
    }

    public function getAge(): ?int
    {
        if (!$this->birthday) return null;
        return $this->birthday->age;
    }

    public function getFullAddress(): ?string
    {
        $parts = array_filter([$this->city, $this->country]);
        return $parts ? implode(', ', $parts) : null;
    }
}
