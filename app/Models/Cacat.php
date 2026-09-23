<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['cacat', 'jenis'])]
#[Table('cacat')]
class Cacat extends Model
{
    public function aturan_penolakans(): HasMany
    {
        return $this->hasMany(AturanPenolakan::class, 'cacat_id');
    }
}
