<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        
        'user_id',
        'store_id',
        'employer_id', // ✅ ADD THIS
        'address',
        'latitude',
        'longitude',
        'status',
        'subtotal',
        'delivery_fee',
        'total',
        'payment_method',
        'notes',
        'phone',
        'rating',
        'review',
        'rated_at',
        'is_rated',
        'confirmed_at',
        'preparing_at',
        'on_the_way_at',
        'delivered_at',
        'canceled_at',
        'assigned_at', // ✅ ADD THIS
    ];

    protected $casts = [
        'is_rated' => 'boolean',
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'preparing_at' => 'datetime',
        'on_the_way_at' => 'datetime',
        'delivered_at' => 'datetime',
        'canceled_at' => 'datetime',
        'rated_at' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // ✅ ADD THIS RELATIONSHIP
    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeRated($query)
    {
        return $query->whereNotNull('rating');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function updateStatus($status)
    {
        $this->status = $status;
        
        switch ($status) {
            case 'confirmed':
                $this->confirmed_at = now();
                break;
            case 'preparing':
                $this->preparing_at = now();
                break;
            case 'on_the_way':
                $this->on_the_way_at = now();
                break;
            case 'delivered':
                $this->delivered_at = now();
                break;
            case 'canceled':
                $this->canceled_at = now();
                break;
        }

        $this->save();
    }
}