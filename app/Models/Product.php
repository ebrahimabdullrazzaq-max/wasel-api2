<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

   protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'category_id',           // ✅ Must be here
        'store_id',
        'store_category_id',
        'image',
    ];
    // Relationships
    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function storeCategory()
    {
        return $this->belongsTo(\App\Models\StoreCategory::class);
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }
}
