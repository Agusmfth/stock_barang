<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    protected $fillable = ['sales_return_id', 'stock_transaction_item_id', 'product_id', 'qty', 'price', 'cost_price'];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function soldItem()
    {
        return $this->belongsTo(StockTransactionItem::class, 'stock_transaction_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
