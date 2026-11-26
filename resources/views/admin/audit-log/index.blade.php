{{-- resources/views/admin/audit-log/index.blade.php --}}
{{-- Audit Log (AuditLogController@index) — baca saja, admin only --}}
@extends('layouts.app')

@section('title', 'Audit Log')

@section('content')
<div class="container mt-4 mb-5">
    <h4 class="fw-bold mb-1">Audit Log</h4>
    <p class="text-muted">Riwayat perubahan status dan data di sistem (siapa, apa, kapan, dari IP mana). Halaman ini hanya untuk dibaca.</p>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Aktivitas</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->created_at?->format('d M Y H:i') }}</td>
                                <td>{{ $log->user?->name ?? '—' }} <span class="text-muted small">({{ $log->user?->username ?? 'pengguna terhapus' }})</span></td>
                                <td>{{ $log->activity }}</td>
                                <td>{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada aktivitas tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
