<?php

namespace App\Models;

use App\Models\ProductBalanceLine;
use App\Models\ProductBalanceLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public function productBalanceLines(): HasMany
    {
        return $this->hasMany(ProductBalanceLine::class);
    }

    public function productBalanceLogs(): HasMany
    {
        return $this->hasMany(ProductBalanceLog::class);
    }
}
