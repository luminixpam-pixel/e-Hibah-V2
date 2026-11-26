<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HibahPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Proposal yang diajukan pada periode ini.
     */
    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'periode_id');
    }
}
