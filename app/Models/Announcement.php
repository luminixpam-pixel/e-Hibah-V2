<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $table = 'announcements';

    protected $fillable = [
        'judul',
        'isi',
        'kategori',
        'file',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * User (LPPM / admin) yang membuat pengumuman.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
