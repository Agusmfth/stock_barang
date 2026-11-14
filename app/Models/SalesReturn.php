<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    protected $fillable = ['return_no', 'stock_transaction_id', 'user_id', 'returned_at', 'reason', 'refund_total'];

    protected $casts = [
        'returned_at' => 'datetime',
        'refund_total' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(StockTransaction::class, 'stock_transaction_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SalesReturnItem::class);
    }
}
