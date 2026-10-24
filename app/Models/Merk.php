<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kualitas_id', 'merk', 'is_active'])]
#[Table('merk')]
class Merk extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function kualitas(): BelongsTo
    {
        return $this->belongsTo(Kualitas::class);
    }

    public function packings(): HasMany
    {
        return $this->hasMany(Packing::class);
    }

    public function produks(): HasMany
    {
        return $this->hasMany(Produk::class);
    }

    public function pengerjaanProduks(): HasMany
    {
        return $this->hasMany(PengerjaanProduk::class);
    }
}
