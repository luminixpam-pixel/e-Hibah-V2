<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonevFinding extends Model
{
    use HasFactory;

    protected $table = 'monev_findings';

    protected $fillable = [
        'proposal_id',
        'uraian_temuan',
        'rekomendasi',
        'status_temuan',
        'file_rtl',
        'bukti_perbaikan',
    ];

    /**
     * Proposal yang dimonev (hanya proposal berstatus 'Disetujui').
     */
    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
