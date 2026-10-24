<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['gudang', 'is_active'])]
#[Table('gudang')]
class Gudang extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function packings(): HasMany
    {
        return $this->hasMany(Packing::class);
    }
}
