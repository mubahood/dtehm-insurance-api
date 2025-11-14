<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderedItem extends Model
{
    use HasFactory;
    
    protected $table = 'ordered_items';
    
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order',
        'product',
        'qty',
        'amount',
        'unit_price',
        'subtotal',
        'color',
        'size',
    ];

    /**
     * Belongs to Product
     */
    public function pro()
    {
        return $this->belongsTo(Product::class, 'product')->withDefault([
            'name' => 'Product Not Found',
            'price_1' => 0,
            'feature_photo' => null,
        ]);
    }
    
    /**
     * Belongs to Order
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order')->withDefault();
    }
}
