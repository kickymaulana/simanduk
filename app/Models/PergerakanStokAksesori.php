<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['aksesori_id', 'packing_id', 'user_id', 'tipe', 'quantity', 'stok_sebelum', 'stok_sesudah', 'keterangan'])]
#[Table('pergerakan_stok_aksesori')]
class PergerakanStokAksesori extends Model
{
    public const MASUK = 'MASUK';

    public const KELUAR_PACKING = 'KELUAR_PACKING';

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stok_sebelum' => 'integer',
            'stok_sesudah' => 'integer',
        ];
    }

    public function aksesori(): BelongsTo
    {
        return $this->belongsTo(Aksesori::class);
    }

    public function packing(): BelongsTo
    {
        return $this->belongsTo(Packing::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
