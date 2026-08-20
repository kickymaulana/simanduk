<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[Fillable(['leader_id', 'jenis', 'shift_id', 'proses_id', 'target', 'tanggal_masuk'])]
#[Table('sesi_kerja')]
class SesiKerja extends Model
{
    protected $appends = ['tanggal'];
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function sesi_kerja_members(): HasMany
    {
        return $this->hasMany(SesiKerjaMember::class, 'sesi_kerja_id');
    }

    public function pengerjaan_produks(): HasMany
    {
        return $this->hasMany(PengerjaanProduk::class, 'sesi_kerja_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
    public function proses(): BelongsTo
    {
        return $this->belongsTo(Proses::class, 'proses_id');
    }


    protected function tanggal(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->tanggal_masuk
                ? \Carbon\Carbon::parse($this->tanggal_masuk)->translatedFormat('d F Y')
                : null,
        );
    }
}
