<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderedItem extends Model
{
    use HasFactory;
    protected $table = 'ordered_items';


    public function pro()
    {
        return $this->belongsTo(Product::class, 'product');
    }
}
