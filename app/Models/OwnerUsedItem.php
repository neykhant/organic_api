<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerUsedItem extends Model
{
    use HasFactory;

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
    
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }
}
