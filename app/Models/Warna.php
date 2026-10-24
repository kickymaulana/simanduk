<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['warna'])]
#[Table('warna')]
class Warna extends Model
{
    public function packings(): HasMany
    {
        return $this->hasMany(Packing::class);
    }
}
