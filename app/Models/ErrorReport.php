<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorReport extends Model
{
    use HasFactory;

    protected $table = 'error_reports';

    /**
     * Status penanganan yang valid: Open, Diproses, Selesai.
     */
    public const STATUSES = ['Open', 'Diproses', 'Selesai'];

    protected $fillable = [
        'user_id',
        'judul',
        'deskripsi',
        'status',
    ];

    /**
     * User yang melaporkan error.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
