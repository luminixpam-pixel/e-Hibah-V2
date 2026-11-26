<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Luaran extends Model
{
    use HasFactory;

    protected $table = 'luarans';

    protected $fillable = [
        'proposal_id',
        'jenis',
        'judul',
        'status',
        'file',
    ];

    /**
     * Proposal yang didanai dan menghasilkan luaran ini.
     */
    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
