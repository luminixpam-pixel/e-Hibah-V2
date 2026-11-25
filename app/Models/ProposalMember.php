<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalMember extends Model
{
    use HasFactory;

    protected $table = 'proposal_members';

    protected $fillable = [
        'proposal_id',
        'user_id',
        'peran',
        'status_konfirmasi',
    ];

    /**
     * Proposal yang anggotanya ini.
     */
    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * User anggota tim (null kalau anggota belum punya akun).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
