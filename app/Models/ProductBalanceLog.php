<?php

namespace App\Models;

use App\Models\Product;
use App\Models\ProductBalance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBalanceLog extends Model
{
    public function productBalance(): BelongsTo
    {
        return $this->belongsTo(ProductBalance::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
