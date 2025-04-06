<?php

namespace App\Models;

use App\Models\Customer;
use App\Models\User;
use App\Models\InvoiceProduct;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['total','discount','vat','payable','user_id','customer_id'];
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    } //end method

    public function invoiceProduct()
    {
        return $this->hasMany(InvoiceProduct::class);
    }



}
