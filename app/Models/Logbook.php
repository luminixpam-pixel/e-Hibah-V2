<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logbook extends Model
{
    use HasFactory;

    protected $table = 'logbooks';

    protected $fillable = [
        'proposal_id',
        'tanggal',
        'uraian_kegiatan',
        'file_bukti',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Proposal yang didanai dan dicatat logbook-nya.
     */
    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
