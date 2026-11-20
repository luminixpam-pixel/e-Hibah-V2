{{-- resources/views/admin/daftar_review.blade.php --}}
{{-- Daftar proposal yang sedang dalam tahap review (ProposalController@daftarReview) --}}
{{-- Dibuat saat perapian: method ini dulu memanggil view 'admin.daftar_review' yang tidak ada di disk (500). --}}
@extends('layouts.app')

@section('title', 'Daftar Review')

@section('content')
<div class="container mt-4 mb-5">
    <h4 class="fw-bold mb-3">Daftar Proposal dalam Review</h4>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Judul</th>
                            <th>Ketua</th>
                            <th>Status</th>
                            <th>Reviewer</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($proposals as $index => $p)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $p->judul }}</strong></td>
                                <td>{{ $p->nama_ketua }}</td>
                                <td><span class="badge bg-info">{{ $p->status }}</span></td>
                                <td>
                                    @forelse ($p->reviewers as $rv)
                                        <div>{{ $rv->name ?? '-' }}</div>
                                    @empty
                                        <span class="text-muted">Belum ditugaskan</span>
                                    @endforelse
                                </td>
                                <td>
                                    <a href="{{ route('proposal.tinjau', $p->id) }}" class="btn btn-sm btn-primary">Tinjau</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Tidak ada proposal dalam tahap review.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <a href="{{ route('proposal.index') }}" class="btn btn-outline-primary mt-3">Kembali ke Daftar Proposal</a>
</div>
@endsection
