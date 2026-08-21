<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Guarded([])]
#[Table('produk')]

class Produk extends Model
{
    public function proses(): BelongsTo
    {
        return $this->belongsTo(Proses::class, 'proses_id');
    }

    public function pengerjaan_produks(): HasMany
    {
        return $this->hasMany(PengerjaanProduk::class, 'produk_id');
    }

    public function latestPengerjaan(): HasOne
    {
        return $this->hasOne(PengerjaanProduk::class)->latestOfMany();
    }

    public function kualitas(): BelongsTo
    {
        return $this->belongsTo(Kualitas::class, 'kualitas_id');
    }

    public function warna(): BelongsTo
    {
        return $this->belongsTo(Warna::class, 'warna_id');
    }

}
