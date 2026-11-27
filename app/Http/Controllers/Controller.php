<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * MIME type yang bisa di-preview inline di browser (PDF dan gambar).
     */
    protected const PREVIEWABLE_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Tampilkan file dari disk public di dalam browser bila tipenya
     * bisa di-preview (PDF / gambar); bila tidak, arahkan ke route
     * download yang diberikan.
     *
     * $path hanya berasal dari kolom database — tidak pernah dari
     * input user — sehingga tidak ada risiko path traversal.
     */
    protected function previewStoredFile(string $path, string $displayName, string $downloadRoute, array $downloadParams = []): \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\RedirectResponse
    {
        if (! Storage::disk('public')->exists($path)) {
            return back()->with('error', 'File tidak ditemukan di server.');
        }

        $mimeType = null;

        try {
            $mimeType = Storage::disk('public')->mimeType($path) ?: null;
        } catch (\Throwable $e) {
            $mimeType = null;
        }

        if (! $mimeType) {
            $mimeType = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                'pdf' => 'application/pdf',
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                default => null,
            };
        }

        if ($mimeType && in_array($mimeType, self::PREVIEWABLE_MIME_TYPES, true)) {
            $safeName = str_replace(['"', "\r", "\n"], '', $displayName);

            return Storage::disk('public')->response($path, $displayName, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="'.$safeName.'"',
            ]);
        }

        return redirect()->route($downloadRoute, $downloadParams)
            ->with('success', 'Preview tidak tersedia untuk tipe file ini. File diunduh sebagai gantinya.');
    }
}
