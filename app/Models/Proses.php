<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Fillable(['departemen_id', 'urutan', 'proses', 'is_active', 'jenis'])]
#[Table('proses')]
class Proses extends Model
{
    public function departemen()
    {
        return $this->belongsTo(Departemen::class);
    }

    public function trolis()
    {
        return $this->hasMany(Troli::class, 'proses_id', 'id');
    }

}
