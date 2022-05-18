<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    public function services()
    {
        return $this->hasMany(InvoiceService::class, 'staff_id');
    }
    
    public function daily_fees(){
        return $this->hasMany(DailyFee::class,"staff_id");
    }
    
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }
}
