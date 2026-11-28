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
        'created_by',
    ];

    /**
     * Proposal yang didanai dan menghasilkan luaran ini.
     */
    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * User yang membuat luaran ini (butir 6: anggota hanya boleh
     * mengelola luaran buatannya sendiri).
     */
    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
