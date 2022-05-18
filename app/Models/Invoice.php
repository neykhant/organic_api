<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }
    public function invoiceServices()
    {
        return $this->hasMany(InvoiceService::class, 'invoice_id');
    }
    
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
