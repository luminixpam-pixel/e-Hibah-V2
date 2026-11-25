<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    use HasFactory;

    protected $table = 'verifications';

    protected $fillable = [
        'proposal_id',
        'verifikator_id',
        'hasil',
        'catatan',
    ];

    /**
     * Proposal yang diperiksa.
     */
    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Verifikator yang memeriksa.
     */
    public function verifikator()
    {
        return $this->belongsTo(User::class, 'verifikator_id');
    }
}
