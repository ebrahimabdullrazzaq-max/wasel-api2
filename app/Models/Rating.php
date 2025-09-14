<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    // Table name (optional if pluralized correctly)
    protected $table = 'ratings';

    // Fillable fields
    protected $fillable = [
        'order_id',
        'customer_id',
        'store_id',
        'rating',
        'review',
    ];

    // Enable timestamps (created_at, updated_at)
    public $timestamps = true;
}