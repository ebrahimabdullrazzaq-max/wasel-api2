<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'latitude',
        'longitude',
        'role',       // e.g., 'customer', 'employer', 'admin'
        'status',     // e.g., 'active', 'pending', 'approved', 'rejected'
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * A user can have one address (if needed).
     */
    public function address()
    {
        return $this->hasOne(Address::class);
    }

    /**
     * A user can have many orders (as a customer).
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope to get users where role is 'employer' and status is 'pending'.
     */
    public function scopePendingEmployers($query)
    {
        return $query->where('role', 'employer')->where('status', 'pending');
    }

    /**
     * Scope to get approved employers.
     */
    public function scopeApprovedEmployers($query)
    {
        return $query->where('role', 'employer')->where('status', 'approved');
    }

    /**
     * Scope to get rejected employers.
     */
    public function scopeRejectedEmployers($query)
    {
        return $query->where('role', 'employer')->where('status', 'rejected');
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is an employer.
     */
    public function isEmployer()
    {
        return $this->role === 'employer';
    }

    /**
     * Check if the user is a customer.
     */
    public function isCustomer()
    {
        return $this->role === 'customer';
    }
}