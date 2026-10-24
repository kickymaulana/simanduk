<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['packing_id', 'aksesori_id', 'quantity'])]
#[Table('packing_accessories')]
class PackingAccessory extends Model
{
    public function packing(): BelongsTo
    {
        return $this->belongsTo(Packing::class);
    }

    public function aksesori(): BelongsTo
    {
        return $this->belongsTo(Aksesori::class);
    }
}
