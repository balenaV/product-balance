<?php

namespace App\Models;

use App\Models\ProductBalanceLine;
use App\Models\ProductBalanceLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBalance extends Model
{
    /**
     * Representa os Produtos na listagem do Balanço
     *
     * @return HasMany
     */
    public function lines() : HasMany
    {
        return $this->hasMany(ProductBalanceLine::class);
    }

    /**
     * Representa os Logs da listagem do Balanço
     *
     * @return HasMany
     */
    public function logs() : HasMany
    {
        return $this->hasMany(ProductBalanceLog::class);
    }
}
