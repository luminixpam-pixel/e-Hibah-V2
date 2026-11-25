<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RabItem extends Model
{
    use HasFactory;

    protected $table = 'rab_items';

    protected $fillable = [
        'proposal_id',
        'nama_komponen',
        'jumlah',
        'keterangan',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    /**
     * Proposal pemilik item RAB ini.
     */
    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
