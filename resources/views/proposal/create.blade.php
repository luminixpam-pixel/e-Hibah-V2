{{-- resources/views/proposal/create.blade.php --}}
{{-- Form pengajuan proposal baru (Route GET /proposal/create) --}}
{{-- Dibuat saat perapian: route ini dulu memanggil method create() yang tidak ada (500). --}}
@extends('layouts.app')

@section('title', 'Ajukan Proposal')

@section('content')
<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold">Ajukan Proposal Baru</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Setiap dosen maksimal terlibat dalam <strong>3 proposal</strong> (sebagai ketua atau anggota). File proposal wajib PDF/DOC/DOCX, maksimal 10 MB.</p>

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('proposal.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold">Judul Proposal *</label>
                            <textarea name="judul" class="form-control" rows="2" required>{{ old('judul') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Ketua *</label>
                            <input type="text" name="nama_ketua" class="form-control" required value="{{ old('nama_ketua', auth()->user()->name) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Fakultas / Program Studi *</label>
                            <select name="fakultas_prodi" class="form-select" required>
                                <option value="" disabled {{ old('fakultas_prodi') ? '' : 'selected' }}>Pilih Fakultas / Prodi...</option>
                                @foreach ($list_fakultas as $f)
                                    <option value="{{ $f->id }}" {{ old('fakultas_prodi') == $f->id ? 'selected' : '' }}>{{ $f->nama_fakultas }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Biaya (Rp) *</label>
                            <input type="text" name="biaya" class="form-control" required value="{{ old('biaya') }}" placeholder="contoh: 4000000">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Anggota Tim</label>
                            <div id="anggota-wrap">
                                <input type="text" name="anggota[]" class="form-control mb-2" placeholder="Nama anggota 1">
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="tambahAnggota()">+ Tambah Anggota</button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">File Proposal (PDF/DOC/DOCX, maks 10 MB) *</label>
                            <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Ajukan Proposal</button>
                        <a href="{{ route('proposal.index') }}" class="btn btn-light">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function tambahAnggota() {
    var wrap = document.getElementById('anggota-wrap');
    var n = wrap.querySelectorAll('input').length + 1;
    var input = document.createElement('input');
    input.type = 'text';
    input.name = 'anggota[]';
    input.className = 'form-control mb-2';
    input.placeholder = 'Nama anggota ' + n;
    wrap.appendChild(input);
}
</script>
@endsection
