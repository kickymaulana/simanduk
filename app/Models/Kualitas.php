<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kualitas'])]
#[Table('kualitas')]
class Kualitas extends Model
{
    public function merks(): HasMany
    {
        return $this->hasMany(Merk::class);
    }

    public function packings(): HasMany
    {
        return $this->hasMany(Packing::class);
    }
}
