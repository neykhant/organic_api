<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
    
     public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
