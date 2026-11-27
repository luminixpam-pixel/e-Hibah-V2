@extends('layouts.app')

@section('content')

 @if ($errors->any())
                    <div class="alert alert-danger">{{ implode('', $errors->all(':message')) }}</div>
                @endif

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
                  @if ($errors->any())
                    <div class="alert alert-danger">{{ implode('', $errors->all(':message')) }}</div>
                @endif

            {{-- Card Header & Container --}}
            <div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
                <div class="card-header p-4 border-0" style="background-color: #fff9c4;">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                             style="background-color: #fbc02d; width: 45px; height: 45px;">
                            <i class="bi bi-pencil-square text-white fs-4"></i>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-0 fw-bold" style="color: #827717;">Edit & Revisi Proposal</h4>
                            <p class="text-muted small mb-0">Perbarui informasi proposal Anda di sini</p>
                        </div>
                    </div>
                </div>


                <div class="card-body p-4 p-md-5 bg-white">
                    {{-- Alert untuk Error --}}
                    @if(session('error'))
                        <div class="alert alert-danger border-0 shadow-sm" style="border-radius: 12px;">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('proposal.update', $proposal->id) }}" method="POST" enctype="multipart/form-data" id="editProposalForm">
                        @csrf
                    @method('PUT')

                     {{-- Input Hidden yang sangat penting agar validasi tidak gagal --}}
                    <input type="hidden" name="fakultas_prodi" value="{{ $proposal->fakultas_prodi }}">
                    <input type="hidden" name="nama_ketua" value="{{ $proposal->nama_ketua }}">

                        <div class="row g-4">
                            {{-- NAMA KETUA --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Nama Ketua</label>
                                <div class="input-group">
                                    <span class="input-group-text border-0 bg-light"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" name="nama_ketua" class="form-control border-0 bg-light"
                                           value="{{ auth()->user()->name }}" readonly style="border-radius: 0 10px 10px 0;">
                                </div>
                                <small class="text-muted ms-1">Otomatis sesuai akun</small>
                            </div>

                            {{-- BIAYA (total RAB — dihitung otomatis dari rincian di bawah) --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Estimasi Biaya (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text border-0 bg-light" style="color: #2e7d32;"><b>Rp</b></span>
                                    <input type="text" id="biaya_display" class="form-control border-0 bg-light"
                                           value="{{ number_format($proposal->biaya, 0, ',', '.') }}" readonly
                                           style="border-radius: 0 10px 10px 0; font-weight: bold; color: #2e7d32;">
                                    <input type="hidden" name="biaya" id="biaya_real" value="{{ $proposal->biaya }}">
                                </div>
                                <small class="text-muted ms-1">Total dari rincian RAB di bawah</small>
                            </div>

                            {{-- JUDUL PROPOSAL --}}
                            <div class="col-12">
                                <label class="form-label fw-bold text-secondary">Judul Proposal</label>
                                <textarea name="judul" class="form-control border-0 bg-light p-3" rows="2"
                                          style="border-radius: 12px;" placeholder="Masukkan judul lengkap..." required>{{ old('judul', $proposal->judul) }}</textarea>
                            </div>

                            {{-- SKEMA --}}
                            <div class="col-12">
                                <label class="form-label fw-bold text-secondary">Skema Hibah</label>
                                <select name="skema_id" id="skema_id" class="form-control border-0 bg-light p-3" style="border-radius: 12px;">
                                    <option value="">Pilih Skema...</option>
                                    @foreach($list_skema as $s)
                                        <option value="{{ $s->id }}" {{ old('skema_id', $proposal->skema_id) == $s->id ? 'selected' : '' }}>
                                            {{ $s->label() }} — Plafon Rp {{ number_format($s->plafon, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="skemaInfo" class="mt-2 p-3 rounded-4 border bg-light-subtle small text-muted" style="display: none;">
                                    <div><strong>Plafon:</strong> <span id="skemaPlafon"></span></div>
                                    <div id="skemaBatasTim"></div>
                                    <div id="skemaDeskripsi"></div>
                                    <div id="skemaSyarat"></div>
                                </div>
                            </div>

                            {{-- RAB --}}
                            <div class="col-12">
                                <div class="p-3 rounded-4 border border-light-subtle bg-light-subtle">
                                    <label class="form-label fw-bold text-secondary d-flex justify-content-between align-items-center">
                                        Rincian Biaya (RAB)
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" id="addRabBtn">
                                            <i class="bi bi-plus-circle me-1"></i> Tambah
                                        </button>
                                    </label>

                                    @php
                                        $rabRows = old('rab', $proposal->rabItems->toArray());
                                        if (empty($rabRows)) $rabRows = [['nama_komponen' => '', 'jumlah' => '', 'keterangan' => '']];
                                    @endphp

                                    <div id="rab-container">
                                        @foreach($rabRows as $i => $item)
                                            <div class="d-flex gap-2 mb-2 rab-row">
                                                <input type="text" name="rab[{{ $i }}][nama_komponen]" class="form-control border-0 shadow-sm"
                                                       list="komponen-rab-list"
                                                       value="{{ $item['nama_komponen'] ?? '' }}" placeholder="Nama komponen" style="border-radius: 10px;">
                                                <input type="number" name="rab[{{ $i }}][jumlah]" class="form-control border-0 shadow-sm rab-jumlah"
                                                       value="{{ $item['jumlah'] ?? '' }}" placeholder="Jumlah (Rp)" min="0" step="1" style="border-radius: 10px; max-width: 170px;">
                                                <input type="text" name="rab[{{ $i }}][keterangan]" class="form-control border-0 shadow-sm"
                                                       value="{{ $item['keterangan'] ?? '' }}" placeholder="Keterangan (opsional)" style="border-radius: 10px;">
                                                <button type="button" class="btn btn-danger-soft remove-rab" style="border-radius: 10px; width: 45px;">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                                        <span class="fw-bold text-secondary">Total RAB:</span>
                                        <strong id="rabTotalDisplay" style="color: #2e7d32;">Rp 0</strong>
                                    </div>
                                    <div id="rabPlafonWarning" class="alert alert-danger mt-2 mb-0 py-2 small" style="display: none;">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i><span id="rabPlafonWarningText"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- ANGGOTA --}}
                            <div class="col-12">
                                <div class="p-3 rounded-4 border border-light-subtle bg-light-subtle">
                                    <label class="form-label fw-bold text-secondary d-flex justify-content-between align-items-center">
                                        Anggota Tim
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" id="addAnggotaBtn">
                                            <i class="bi bi-plus-circle me-1"></i> Tambah
                                        </button>
                                    </label>

                                    @php
                                        $anggota = is_array($proposal->anggota) ? $proposal->anggota : (json_decode($proposal->anggota, true) ?? []);
                                    @endphp

                                    <div id="anggota-container">
                                        @foreach($anggota as $a)
                                            <div class="d-flex gap-2 mb-2 anggota-row animate__animated animate__fadeIn">
                                                <input type="text" name="anggota[]" class="form-control border-0 shadow-sm"
                                                       value="{{ $a }}" list="user-list" placeholder="Nama anggota..." style="border-radius: 10px;">
                                                <button type="button" class="btn btn-danger-soft remove-anggota"
                                                        style="border-radius: 10px; width: 45px;">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- UPDATE FILE --}}
                            <div class="col-12">
                                <div class="p-4 border-2 border-dashed rounded-4 text-center" style="border-style: dashed !important; border-color: #dee2e6 !important;">
                                    <label class="form-label fw-bold text-secondary">Berkas Proposal (PDF)</label>
                                    <div class="d-flex justify-content-center mb-2">
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
                                    </div>
                                    <input type="file" name="file" class="form-control shadow-sm mx-auto" style="max-width: 400px; border-radius: 10px;" accept=".pdf">
                                    <p class="mt-2 small text-muted mb-0">
                                        <span class="badge bg-info-subtle text-info">File saat ini:</span>
                                        {{ basename($proposal->file_path) ?: 'Belum ada file' }}
                                        {{-- Tautan unduh agar pengusul bisa memastikan berkas
                                             tersimpan tetap ada setelah validasi gagal
                                             (input file tidak bisa diisi ulang browser, bug #7). --}}
                                        @if($proposal->file_path)
                                            <a href="{{ route('proposal.download', $proposal->id) }}" class="ms-1">Unduh file saat ini</a>
                                        @endif
                                    </p>
                                    <small class="text-danger mt-1 d-block font-italic">*Kosongkan jika tidak ingin mengganti file.</small>
                                </div>
                            </div>
                        </div>

                        {{-- BUTTONS --}}
                        <div class="mt-5 d-flex gap-3 justify-content-end">
                            <a href="{{ route('monitoring.proposalDikirim') }}" class="btn btn-light px-4 py-2 fw-bold text-secondary" style="border-radius: 12px;">
                                Batal
                            </a>
                            @if(in_array($proposal->status, ['Draft', 'Revisi Administrasi'], true))
                                <button type="submit" name="aksi" value="draft" class="btn btn-outline-success px-4 py-2 fw-bold shadow-sm" style="border-radius: 12px;" id="draftButton">
                                    <i class="bi bi-save me-2"></i>Simpan Draft
                                </button>
                                <button type="submit" name="aksi" value="submit" class="btn btn-success px-5 py-2 fw-bold shadow-sm" style="border-radius: 12px; background-color: #2e7d32;" id="submitButton">
                                    <i class="bi bi-send-check-fill me-2"></i>Kirim Pengajuan
                                </button>
                            @else
                                {{-- Status lain tidak bisa dikirim (mis. Dikirim / Lolos
                                     Administrasi): tombol ini hanya menyimpan perubahan,
                                     jangan dilabeli "Kirim" agar tidak menyesatkan. --}}
                                <button type="submit" name="aksi" value="draft" class="btn btn-success px-5 py-2 fw-bold shadow-sm" style="border-radius: 12px; background-color: #2e7d32;">
                                    <i class="bi bi-save me-2"></i>Simpan Perubahan
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Data List --}}
<datalist id="user-list">
    @foreach($users as $user)
        <option value="{{ $user->name }}">
    @endforeach
</datalist>

{{-- Daftar komponen RAB (bawaan + kustom dari setelan admin) untuk pilihan nama komponen --}}
<datalist id="komponen-rab-list">
    @foreach($daftarKomponenRab as $komponen)
        <option value="{{ $komponen }}">
    @endforeach
</datalist>

<style>
    /* Custom Styling Nuansa Soft */
    body { background-color: #f8f9fa; }
    .form-control:focus {
        background-color: #fff !important;
        box-shadow: 0 0 0 0.25rem rgba(251, 192, 45, 0.2) !important;
        border: 1px solid #fbc02d !important;
    }
    .btn-danger-soft {
        background-color: #ffebee;
        color: #c62828;
        border: none;
    }
    .btn-danger-soft:hover {
        background-color: #ef5350;
        color: white;
    }
    .animate__animated { --animate-duration: 0.5s; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const biayaDisplay = document.getElementById('biaya_display');
    const biayaReal = document.getElementById('biaya_real');
    const biayaAwal = parseFloat(biayaReal.value) || 0;

    // 1. Skema & RAB: total RAB dihitung otomatis, biaya tampil mengikuti total.
    const SKEMA_INFO = {
        @foreach($list_skema as $s)
            {{ $s->id }}: {
                plafon: {{ $s->plafon }},
                kategori: @json($s->label()),
                maksTim: {{ $s->maksUkuranTim() ?? 'null' }},
                deskripsi: @json($s->deskripsi),
                syarat: @json($s->syarat),
            },
        @endforeach
    };

    function formatRupiah(n) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
    }

    function skemaTerpilih() {
        var sel = document.getElementById('skema_id');
        return sel.value ? SKEMA_INFO[sel.value] : null;
    }

    function updateSkemaInfo() {
        var info = skemaTerpilih();
        var box = document.getElementById('skemaInfo');
        if (!info) {
            box.style.display = 'none';
            return;
        }
        document.getElementById('skemaPlafon').textContent = formatRupiah(info.plafon);
        document.getElementById('skemaBatasTim').textContent = info.maksTim
            ? 'Batas tim: maks ' + info.maksTim + ' orang (ketua + anggota).'
            : '';
        document.getElementById('skemaDeskripsi').textContent = info.deskripsi || '';
        document.getElementById('skemaSyarat').textContent = info.syarat ? 'Syarat: ' + info.syarat : '';
        box.style.display = 'block';
        hitungTotalRab();
    }

    function hitungTotalRab() {
        var total = 0;
        var adaIsi = false;
        document.querySelectorAll('#rab-container .rab-row').forEach(function(row) {
            var nama = row.querySelector('input[name*="[nama_komponen]"]').value.trim();
            var jumlah = parseFloat(row.querySelector('.rab-jumlah').value) || 0;
            if (nama !== '' || jumlah > 0) adaIsi = true;
            total += jumlah;
        });

        // Bila belum ada rincian RAB yang terisi, tampilkan biaya lama.
        var tampil = adaIsi ? total : biayaAwal;
        document.getElementById('rabTotalDisplay').textContent = formatRupiah(tampil);
        biayaDisplay.value = new Intl.NumberFormat('id-ID').format(tampil);
        biayaReal.value = tampil;

        var info = skemaTerpilih();
        var warning = document.getElementById('rabPlafonWarning');
        var submitBtn = document.getElementById('submitButton');
        if (adaIsi && info && info.plafon > 0 && total > info.plafon) {
            document.getElementById('rabPlafonWarningText').textContent =
                'Total RAB melebihi plafon skema ' + info.kategori + ' (' + formatRupiah(info.plafon) + '). Kurangi komponen biaya sebelum mengirim.';
            warning.style.display = 'block';
            if (submitBtn) submitBtn.disabled = true;
        } else {
            warning.style.display = 'none';
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    document.getElementById('skema_id').addEventListener('change', updateSkemaInfo);

    // 2. Tambah baris RAB secara dinamis
    const rabContainer = document.getElementById('rab-container');
    const addRabBtn = document.getElementById('addRabBtn');
    let rabIndex = document.querySelectorAll('#rab-container .rab-row').length;

    addRabBtn.addEventListener('click', function() {
        const div = document.createElement('div');
        div.className = 'd-flex gap-2 mb-2 rab-row';
        div.innerHTML = `
            <input type="text" name="rab[${rabIndex}][nama_komponen]" class="form-control border-0 shadow-sm"
                   list="komponen-rab-list"
                   placeholder="Nama komponen" style="border-radius: 10px;">
            <input type="number" name="rab[${rabIndex}][jumlah]" class="form-control border-0 shadow-sm rab-jumlah"
                   placeholder="Jumlah (Rp)" min="0" step="1" style="border-radius: 10px; max-width: 170px;">
            <input type="text" name="rab[${rabIndex}][keterangan]" class="form-control border-0 shadow-sm"
                   placeholder="Keterangan (opsional)" style="border-radius: 10px;">
            <button type="button" class="btn btn-danger-soft remove-rab" style="border-radius: 10px; width: 45px;">
                <i class="bi bi-trash3-fill"></i>
            </button>
        `;
        rabIndex++;
        rabContainer.appendChild(div);
    });

    // 3. Hapus baris RAB & hitung ulang total saat input berubah
    rabContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-rab')) {
            e.target.closest('.rab-row').remove();
            hitungTotalRab();
        }
    });
    rabContainer.addEventListener('input', hitungTotalRab);

    // 4. Draft: lewati validasi browser (judul tetap wajib di server).
    const draftBtn = document.getElementById('draftButton');
    const form = document.getElementById('editProposalForm');
    if (draftBtn) {
        draftBtn.addEventListener('click', function() {
            form.noValidate = true;
        });
    }
    const submitBtn = document.getElementById('submitButton');
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            form.noValidate = false;
        });
    }

    updateSkemaInfo();

    // 5. Tambah Anggota Secara Dinamis (kode lama, dipertahankan)
    const container = document.getElementById('anggota-container');
    const addBtn = document.getElementById('addAnggotaBtn');

    addBtn.addEventListener('click', function() {
        const div = document.createElement('div');
        div.className = 'd-flex gap-2 mb-2 anggota-row animate__animated animate__fadeIn';
        div.innerHTML = `
            <input type="text" name="anggota[]" class="form-control border-0 shadow-sm"
                   list="user-list" placeholder="Nama anggota..." style="border-radius: 10px;">
            <button type="button" class="btn btn-danger-soft remove-anggota" style="border-radius: 10px; width: 45px;">
                <i class="bi bi-trash3-fill"></i>
            </button>
        `;
        container.appendChild(div);
    });

    // 6. Hapus Anggota
    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-anggota')) {
            e.target.closest('.anggota-row').remove();
        }
    });
});
</script>
@endsection
