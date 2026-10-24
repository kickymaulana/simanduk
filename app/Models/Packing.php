<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['qrcode_kardus', 'jenis', 'kualitas_id', 'merk_id', 'warna_id', 'gudang_id', 'user_id'])]
#[Table('packing')]
class Packing extends Model
{
    public function kualitas(): BelongsTo
    {
        return $this->belongsTo(Kualitas::class);
    }

    public function merk(): BelongsTo
    {
        return $this->belongsTo(Merk::class);
    }

    public function warna(): BelongsTo
    {
        return $this->belongsTo(Warna::class);
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accessories(): HasMany
    {
        return $this->hasMany(PackingAccessory::class);
    }

    public function pergerakanStokAksesori(): HasMany
    {
        return $this->hasMany(PergerakanStokAksesori::class);
    }
}
