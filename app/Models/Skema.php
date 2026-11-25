<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skema extends Model
{
    use HasFactory;

    protected $table = 'skemas';

    protected $fillable = [
        'kategori',
        'plafon',
        'kuota',
        'deskripsi',
        'syarat',
    ];

    protected $casts = [
        'plafon' => 'decimal:2',
        'kuota' => 'integer',
    ];

    /**
     * Proposal yang memakai skema ini.
     */
    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'skema_id');
    }
}
