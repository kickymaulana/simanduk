<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['aksesori', 'part_number', 'description', 'stok', 'is_active'])]
#[Table('aksesori')]
class Aksesori extends Model
{
    protected function casts(): array
    {
        return [
            'stok' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function packingAccessories(): HasMany
    {
        return $this->hasMany(PackingAccessory::class);
    }

    public function pergerakanStok(): HasMany
    {
        return $this->hasMany(PergerakanStokAksesori::class);
    }
}
