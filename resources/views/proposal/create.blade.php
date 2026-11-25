
{{-- resources/views/proposal/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Ajukan Proposal')

@section('content')
<div class="proposal-create-page">

    {{-- Header --}}
    <div class="proposal-create-header">
        <div class="proposal-create-header-content">
            <div class="proposal-create-label">
                <i class="bi bi-file-earmark-plus"></i>
                PENGAJUAN PENELITIAN & PENGABDIAN
            </div>

            <h2>Ajukan Proposal Baru</h2>

            <p>
                Lengkapi informasi proposal, tim pengusul, dan dokumen
                untuk memulai proses pengajuan hibah.
            </p>
        </div>

        <div class="proposal-create-header-icon">
            <i class="bi bi-journal-richtext"></i>
        </div>
    </div>

    {{-- Informasi Batas --}}
    <div class="proposal-limit-info">
        <div class="proposal-limit-icon">
            <i class="bi bi-info-circle"></i>
        </div>

        <div>
            <h6>Ketentuan Pengajuan Proposal</h6>
            <p>
                Setiap dosen maksimal terlibat sesuai <strong>kuota skema</strong>
                yang dipilih (sebagai ketua maupun anggota). Total RAB tidak
                boleh melebihi <strong>plafon skema</strong>. Cek sisa kuota Anda
                di <a href="{{ route('dosen.cek-kuota') }}">halaman Cek Kuota</a>.
                Pastikan data dan dokumen yang diunggah telah sesuai.
            </p>
        </div>
    </div>

    {{-- Error --}}
    @if (session('error'))
        <div class="proposal-alert-error">
            <i class="bi bi-exclamation-circle"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="proposal-alert-error">
            <i class="bi bi-exclamation-circle"></i>

            <div>
                <strong>Periksa kembali formulir Anda.</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('proposal.store') }}"
          method="POST"
          enctype="multipart/form-data"
          id="proposalForm">

        @csrf

        {{-- INFORMASI PROPOSAL --}}
        <div class="proposal-form-card">

            <div class="proposal-form-card-header">
                <div class="proposal-form-number">01</div>

                <div>
                    <h4>Informasi Proposal</h4>
                    <p>Masukkan informasi utama proposal yang diajukan.</p>
                </div>
            </div>

            <div class="proposal-form-card-body">

                {{-- Judul --}}
                <div class="proposal-field">
                    <label for="judul">
                        Judul Proposal
                        <span class="required-mark">*</span>
                    </label>

                    <textarea
                        name="judul"
                        id="judul"
                        class="proposal-input"
                        rows="3"
                        required
                        placeholder="Tuliskan judul lengkap proposal Anda..."
                    >{{ old('judul') }}</textarea>

                    <small>
                        Gunakan judul yang jelas, spesifik, dan sesuai dengan isi proposal.
                    </small>
                </div>

                {{-- Ketua --}}
                <div class="proposal-field">
                    <label for="nama_ketua">
                        Nama Ketua Pengusul
                        <span class="required-mark">*</span>
                    </label>

                    <div class="proposal-input-icon">
                        <i class="bi bi-person"></i>

                        <input
                            type="text"
                            name="nama_ketua"
                            id="nama_ketua"
                            class="proposal-input"
                            required
                            value="{{ old('nama_ketua', auth()->user()->name) }}"
                            placeholder="Nama ketua pengusul"
                        >
                    </div>
                </div>

                {{-- Fakultas --}}
                <div class="proposal-field">
                    <label for="fakultas_prodi">
                        Fakultas / Program Studi
                        <span class="required-mark">*</span>
                    </label>

                    <div class="proposal-input-icon">
                        <i class="bi bi-building"></i>

                        <select
                            name="fakultas_prodi"
                            id="fakultas_prodi"
                            class="proposal-input"
                            required
                        >
                            <option value="" disabled
                                {{ old('fakultas_prodi') ? '' : 'selected' }}>
                                Pilih Fakultas...
                            </option>

                            @foreach ($list_fakultas as $f)
                                <option value="{{ $f->id }}"
                                    {{ old('fakultas_prodi') == $f->id ? 'selected' : '' }}>
                                    {{ $f->nama_fakultas }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

            </div>
        </div>

        {{-- SKEMA & RAB --}}
        <div class="proposal-form-card">

            <div class="proposal-form-card-header">
                <div class="proposal-form-number">02</div>

                <div>
                    <h4>Skema &amp; RAB</h4>
                    <p>Pilih skema hibah dan susun rincian biaya (RAB).</p>
                </div>
            </div>

            <div class="proposal-form-card-body">

                {{-- Skema --}}
                <div class="proposal-field">
                    <label for="skema_id">
                        Skema Hibah
                        <span class="required-mark">*</span>
                    </label>

                    <div class="proposal-input-icon">
                        <i class="bi bi-award"></i>

                        <select
                            name="skema_id"
                            id="skema_id"
                            class="proposal-input"
                            required
                        >
                            <option value="" disabled
                                {{ old('skema_id') ? '' : 'selected' }}>
                                Pilih Skema...
                            </option>

                            @foreach ($list_skema as $s)
                                <option value="{{ $s->id }}"
                                    {{ old('skema_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->kategori }} — Plafon Rp {{ number_format($s->plafon, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="skemaInfo" class="proposal-skema-info" style="display: none;">
                        <div><strong>Plafon:</strong> <span id="skemaPlafon"></span></div>
                        <div id="skemaDeskripsi"></div>
                        <div id="skemaSyarat"></div>
                    </div>
                </div>

                {{-- RAB --}}
                <div class="proposal-field">
                    <label>
                        Rincian Biaya (RAB)
                        <span class="required-mark">*</span>
                    </label>

                    <div class="proposal-team-info">
                        <i class="bi bi-calculator"></i>
                        <span>
                            Tambahkan tiap komponen biaya. Total RAB dihitung
                            otomatis dan tidak boleh melebihi plafon skema.
                        </span>
                    </div>

                    <div id="rab-wrap">

                        @php
                            $oldRab = old('rab', [['nama_komponen' => '', 'jumlah' => '', 'keterangan' => '']]);
                        @endphp

                        @foreach ($oldRab as $index => $rab)
                            <div class="proposal-rab-row">
                                <input
                                    type="text"
                                    name="rab[{{ $index }}][nama_komponen]"
                                    class="proposal-input rab-nama"
                                    value="{{ $rab['nama_komponen'] ?? '' }}"
                                    placeholder="Nama komponen"
                                >
                                <input
                                    type="number"
                                    name="rab[{{ $index }}][jumlah]"
                                    class="proposal-input rab-jumlah"
                                    value="{{ $rab['jumlah'] ?? '' }}"
                                    placeholder="Jumlah (Rp)"
                                    min="0"
                                    step="1"
                                >
                                <input
                                    type="text"
                                    name="rab[{{ $index }}][keterangan]"
                                    class="proposal-input rab-keterangan"
                                    value="{{ $rab['keterangan'] ?? '' }}"
                                    placeholder="Keterangan (opsional)"
                                >
                                <button
                                    type="button"
                                    class="proposal-remove-member"
                                    onclick="hapusRab(this)"
                                    title="Hapus komponen"
                                    {{ count($oldRab) <= 1 ? 'disabled' : '' }}
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        @endforeach

                    </div>

                    <button type="button"
                            class="proposal-add-member"
                            onclick="tambahRab()">
                        <i class="bi bi-plus-circle"></i>
                        Tambah Komponen
                    </button>

                    <div class="proposal-rab-total">
                        <span>Total RAB:</span>
                        <strong id="rabTotalDisplay">Rp 0</strong>
                    </div>

                    <div id="rabPlafonWarning" class="proposal-rab-warning" style="display: none;">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span id="rabPlafonWarningText"></span>
                    </div>
                </div>

            </div>
        </div>

        {{-- TIM PENGUSUL --}}
        <div class="proposal-form-card">

            <div class="proposal-form-card-header">
                <div class="proposal-form-number">03</div>

                <div>
                    <h4>Tim Pengusul</h4>
                    <p>Tambahkan anggota tim yang terlibat dalam proposal.</p>
                </div>
            </div>

            <div class="proposal-form-card-body">

                <div class="proposal-team-info">
                    <i class="bi bi-people"></i>
                    <span>
                        Anggota tim bersifat opsional. Anda dapat menambahkan
                        nama anggota sesuai tim pengusul.
                    </span>
                </div>

                <div id="anggota-wrap">

                    @php
                        $oldAnggota = old('anggota', ['']);
                    @endphp

                    @foreach ($oldAnggota as $index => $anggota)
                        <div class="proposal-member-row">

                            <div class="proposal-member-number">
                                <i class="bi bi-person"></i>
                            </div>

                            <input
                                type="text"
                                name="anggota[]"
                                class="proposal-input anggota-input"
                                list="dosen-list"
                                value="{{ $anggota }}"
                                placeholder="Nama anggota {{ $index + 1 }}"
                            >

                            <button
                                type="button"
                                class="proposal-remove-member"
                                onclick="hapusAnggota(this)"
                                title="Hapus anggota"
                                {{ count($oldAnggota) <= 1 ? 'disabled' : '' }}
                            >
                                <i class="bi bi-trash"></i>
                            </button>

                        </div>
                    @endforeach

                </div>

                <button type="button"
                        class="proposal-add-member"
                        onclick="tambahAnggota()">

                    <i class="bi bi-plus-circle"></i>
                    Tambah Anggota
                </button>

            </div>
        </div>

        {{-- DOKUMEN --}}
        <div class="proposal-form-card">

            <div class="proposal-form-card-header">
                <div class="proposal-form-number">04</div>

                <div>
                    <h4>Dokumen Proposal</h4>
                    <p>Unggah file proposal yang akan diajukan.</p>
                </div>
            </div>

            <div class="proposal-form-card-body">

                <div class="proposal-upload-box">

                    <div class="proposal-upload-icon">
                        <i class="bi bi-cloud-arrow-up"></i>
                    </div>

                    <h5>Unggah File Proposal</h5>

                    <p>
                        Pilih file proposal dari perangkat Anda.
                    </p>

                    <input
                        type="file"
                        name="file"
                        id="proposalFile"
                        accept=".pdf,.doc,.docx"
                        required
                    >

                    <label for="proposalFile" class="proposal-upload-button">
                        <i class="bi bi-folder2-open"></i>
                        Pilih File
                    </label>

                    <div id="fileNameDisplay" class="proposal-file-name">
                        <i class="bi bi-file-earmark"></i>
                        <span>Belum ada file dipilih</span>
                    </div>

                    <div class="proposal-upload-requirements">
                        <div>
                            <i class="bi bi-check-circle"></i>
                            PDF, DOC, DOCX
                        </div>

                        <div>
                            <i class="bi bi-check-circle"></i>
                            Maksimal 10 MB
                        </div>
                    </div>

                </div>

            </div>
        </div>

        {{-- ACTION --}}
        <div class="proposal-form-footer">

            <a href="{{ route('proposal.index') }}"
               class="proposal-cancel-button">
                <i class="bi bi-arrow-left"></i>
                Batal
            </a>

            <button type="submit" name="aksi" value="draft" class="proposal-draft-button" id="draftButton">
                <i class="bi bi-save"></i>
                Simpan Draft
            </button>

            <button type="submit" name="aksi" value="submit" class="proposal-submit-button" id="submitButton">
                <i class="bi bi-send"></i>
                Ajukan Proposal
            </button>

        </div>

    </form>

    {{-- Daftar dosen untuk pilihan anggota tim --}}
    <datalist id="dosen-list">
        @foreach ($all_dosen as $d)
            <option value="{{ $d->name }}">
        @endforeach
    </datalist>

</div>

<script>
function tambahAnggota() {
    var wrap = document.getElementById('anggota-wrap');

    var n = wrap.querySelectorAll('.anggota-input').length + 1;

    var row = document.createElement('div');
    row.className = 'proposal-member-row';

    row.innerHTML = `
        <div class="proposal-member-number">
            <i class="bi bi-person"></i>
        </div>

        <input
            type="text"
            name="anggota[]"
            class="proposal-input anggota-input"
            list="dosen-list"
            placeholder="Nama anggota ${n}"
        >

        <button
            type="button"
            class="proposal-remove-member"
            onclick="hapusAnggota(this)"
            title="Hapus anggota"
        >
            <i class="bi bi-trash"></i>
        </button>
    `;

    wrap.appendChild(row);

    updateAnggotaButtons();
}

function hapusAnggota(button) {
    var wrap = document.getElementById('anggota-wrap');

    if (wrap.querySelectorAll('.proposal-member-row').length > 1) {
        button.closest('.proposal-member-row').remove();
    }

    updateAnggotaButtons();
}

function updateAnggotaButtons() {
    var wrap = document.getElementById('anggota-wrap');
    var rows = wrap.querySelectorAll('.proposal-member-row');

    rows.forEach(function(row) {
        var button = row.querySelector('.proposal-remove-member');

        button.disabled = rows.length <= 1;
    });
}

/* File display */

document.getElementById('proposalFile').addEventListener('change', function() {
    var display = document.getElementById('fileNameDisplay');
    var span = display.querySelector('span');

    if (this.files.length > 0) {
        span.textContent = this.files[0].name;
        display.classList.add('selected');
    } else {
        span.textContent = 'Belum ada file dipilih';
        display.classList.remove('selected');
    }
});

/* Skema & RAB */

const SKEMA_INFO = {
    @foreach ($list_skema as $s)
        {{ $s->id }}: {
            plafon: {{ $s->plafon }},
            kategori: @json($s->kategori),
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
    document.getElementById('skemaDeskripsi').textContent = info.deskripsi || '';
    document.getElementById('skemaSyarat').textContent = info.syarat ? 'Syarat: ' + info.syarat : '';
    box.style.display = 'block';

    hitungTotalRab();
}

function hitungTotalRab() {
    var total = 0;

    document.querySelectorAll('.rab-jumlah').forEach(function(input) {
        total += parseFloat(input.value) || 0;
    });

    document.getElementById('rabTotalDisplay').textContent = formatRupiah(total);

    var info = skemaTerpilih();
    var warning = document.getElementById('rabPlafonWarning');
    var submitBtn = document.getElementById('submitButton');

    if (info && info.plafon > 0 && total > info.plafon) {
        document.getElementById('rabPlafonWarningText').textContent =
            'Total RAB melebihi plafon skema ' + info.kategori + ' (' + formatRupiah(info.plafon) + '). Kurangi komponen biaya sebelum mengajukan.';
        warning.style.display = 'flex';
        submitBtn.disabled = true;
    } else {
        warning.style.display = 'none';
        submitBtn.disabled = false;
    }

    return total;
}

let rabIndex = document.querySelectorAll('.proposal-rab-row').length;

function tambahRab() {
    var wrap = document.getElementById('rab-wrap');

    var row = document.createElement('div');
    row.className = 'proposal-rab-row';

    row.innerHTML = `
        <input type="text" name="rab[${rabIndex}][nama_komponen]" class="proposal-input rab-nama" placeholder="Nama komponen">
        <input type="number" name="rab[${rabIndex}][jumlah]" class="proposal-input rab-jumlah" placeholder="Jumlah (Rp)" min="0" step="1">
        <input type="text" name="rab[${rabIndex}][keterangan]" class="proposal-input rab-keterangan" placeholder="Keterangan (opsional)">
        <button type="button" class="proposal-remove-member" onclick="hapusRab(this)" title="Hapus komponen">
            <i class="bi bi-trash"></i>
        </button>
    `;

    rabIndex++;
    wrap.appendChild(row);

    updateRabButtons();
}

function hapusRab(button) {
    var wrap = document.getElementById('rab-wrap');

    if (wrap.querySelectorAll('.proposal-rab-row').length > 1) {
        button.closest('.proposal-rab-row').remove();
    }

    updateRabButtons();
    hitungTotalRab();
}

function updateRabButtons() {
    var wrap = document.getElementById('rab-wrap');
    var rows = wrap.querySelectorAll('.proposal-rab-row');

    rows.forEach(function(row) {
        row.querySelector('.proposal-remove-member').disabled = rows.length <= 1;
    });
}

document.getElementById('skema_id').addEventListener('change', updateSkemaInfo);
document.getElementById('rab-wrap').addEventListener('input', hitungTotalRab);

/* Draft: lewati validasi browser (judul tetap wajib di server). */
document.getElementById('draftButton').addEventListener('click', function() {
    document.getElementById('proposalForm').noValidate = true;
});
document.getElementById('submitButton').addEventListener('click', function() {
    document.getElementById('proposalForm').noValidate = false;
});

/* Inisialisasi tampilan skema & total saat halaman dimuat (termasuk old input). */
updateSkemaInfo();
updateRabButtons();
</script>

@endsection

@section('styles')
<style>

/* ===========================
   PROPOSAL CREATE
=========================== */

.proposal-create-page {
    max-width: 1050px;
    margin: 0 auto;
    padding: 10px 2px 35px;
    color: #24382e;
}

/* Header */

.proposal-create-header {
    background: linear-gradient(135deg, #e4f3e9, #f4faf6);
    border: 1px solid #d4e9db;
    border-radius: 20px;
    padding: 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 22px;
}

.proposal-create-label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.4px;
    color: #46805f;
    margin-bottom: 10px;
}

.proposal-create-label i {
    margin-right: 5px;
}

.proposal-create-header h2 {
    font-size: 26px;
    font-weight: 800;
    color: #20563c;
    margin-bottom: 10px;
}

.proposal-create-header p {
    font-size: 14px;
    color: #61796b;
    margin: 0;
    max-width: 650px;
    line-height: 1.7;
}

.proposal-create-header-icon {
    width: 90px;
    height: 90px;
    border-radius: 24px;
    background: rgba(255,255,255,.65);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #39805a;
    font-size: 42px;
    flex-shrink: 0;
}

/* Info box */

.proposal-limit-info {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    background: #fff;
    border: 1px solid #dcebe1;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 25px;
}

.proposal-limit-icon {
    width: 42px;
    height: 42px;
    border-radius: 13px;
    background: #e8f4ed;
    color: #39805a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.proposal-limit-info h6 {
    font-weight: 800;
    font-size: 14px;
    color: #345b43;
    margin-bottom: 6px;
}

.proposal-limit-info p {
    font-size: 13px;
    color: #718078;
    line-height: 1.7;
    margin: 0;
}

/* Error */

.proposal-alert-error {
    background: #fff1f1;
    border: 1px solid #f2d1d1;
    color: #a33e3e;
    padding: 16px 20px;
    border-radius: 14px;
    margin-bottom: 20px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    font-size: 13px;
}

.proposal-alert-error > i {
    font-size: 20px;
    flex-shrink: 0;
}

/* Form cards */

.proposal-form-card {
    background: #fff;
    border: 1px solid #e7eee9;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 22px;
    box-shadow: 0 4px 16px rgba(32, 86, 60, .035);
}

.proposal-form-card-header {
    padding: 23px 25px;
    border-bottom: 1px solid #edf1ee;
    display: flex;
    align-items: center;
    gap: 15px;
}

.proposal-form-number {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: #e7f4ec;
    color: #39805a;
    font-size: 14px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.proposal-form-card-header h4 {
    font-size: 17px;
    font-weight: 800;
    color: #294736;
    margin-bottom: 5px;
}

.proposal-form-card-header p {
    font-size: 12px;
    color: #8a968e;
    margin: 0;
}

.proposal-form-card-body {
    padding: 25px;
}

/* Field */

.proposal-field {
    margin-bottom: 23px;
}

.proposal-field:last-child {
    margin-bottom: 0;
}

.proposal-field label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #354c3d;
    margin-bottom: 9px;
}

.required-mark {
    color: #dc5555;
}

.proposal-input {
    width: 100%;
    border: 1px solid #dfe8e2;
    border-radius: 12px;
    background: #fff;
    color: #344b3c;
    padding: 13px 15px;
    font-size: 13px;
    outline: none;
    transition: .2s ease;
    font-family: inherit;
}

.proposal-input:focus {
    border-color: #70a987;
    box-shadow: 0 0 0 3px rgba(112,169,135,.12);
}

.proposal-input::placeholder {
    color: #a3aea6;
}

.proposal-field textarea {
    resize: vertical;
    min-height: 100px;
}

.proposal-field small {
    display: block;
    margin-top: 8px;
    color: #8b9890;
    font-size: 11px;
    line-height: 1.6;
}

/* Icon input */

.proposal-input-icon {
    position: relative;
}

.proposal-input-icon > i {
    position: absolute;
    top: 50%;
    left: 15px;
    transform: translateY(-50%);
    color: #7b9b86;
    font-size: 16px;
    z-index: 2;
}

.proposal-input-icon .proposal-input {
    padding-left: 43px;
}

.proposal-input-icon select {
    appearance: auto;
}

/* Team */

.proposal-team-info {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    background: #f5faf6;
    border: 1px solid #e3eee6;
    border-radius: 12px;
    padding: 14px 16px;
    color: #688073;
    font-size: 12px;
    line-height: 1.7;
    margin-bottom: 20px;
}

.proposal-team-info i {
    color: #4b8a62;
    font-size: 17px;
    flex-shrink: 0;
}

.proposal-member-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.proposal-member-number {
    width: 43px;
    height: 43px;
    border-radius: 12px;
    background: #f0f5f2;
    color: #62806c;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.proposal-member-row .proposal-input {
    flex: 1;
    min-width: 0;
}

.proposal-remove-member {
    width: 43px;
    height: 43px;
    border: 1px solid #f0dada;
    border-radius: 12px;
    background: #fff;
    color: #c46b6b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: .2s;
    flex-shrink: 0;
}

.proposal-remove-member:hover:not(:disabled) {
    background: #fff0f0;
    border-color: #e7bcbc;
}

.proposal-remove-member:disabled {
    opacity: .35;
    cursor: not-allowed;
}

.proposal-add-member {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    padding: 11px 16px;
    border: 1px dashed #87b59a;
    background: #f7fbf8;
    color: #39805a;
    font-size: 13px;
    font-weight: 700;
    border-radius: 12px;
    cursor: pointer;
    transition: .2s;
}

.proposal-add-member:hover {
    background: #eaf5ee;
    border-color: #57946f;
}

/* Upload */

.proposal-upload-box {
    border: 2px dashed #c8dfd0;
    border-radius: 18px;
    background: #f8fbf9;
    padding: 35px 20px;
    text-align: center;
}

.proposal-upload-icon {
    width: 72px;
    height: 72px;
    border-radius: 20px;
    background: #e6f4eb;
    color: #39805a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
    margin: 0 auto 17px;
}

.proposal-upload-box h5 {
    font-size: 16px;
    font-weight: 800;
    color: #345b43;
    margin-bottom: 7px;
}

.proposal-upload-box > p {
    font-size: 12px;
    color: #87938b;
    margin-bottom: 20px;
}

.proposal-upload-box input[type="file"] {
    display: none;
}

.proposal-upload-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    background: #28764d;
    color: #fff;
    padding: 12px 22px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.proposal-upload-button:hover {
    background: #205d3c;
}

.proposal-file-name {
    margin: 17px auto 0;
    padding: 12px 15px;
    border: 1px solid #e5ece7;
    background: #fff;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #89958d;
    font-size: 12px;
    max-width: 500px;
    overflow-wrap: anywhere;
}

.proposal-file-name i {
    font-size: 17px;
}

.proposal-file-name.selected {
    color: #39805a;
    background: #f1f8f3;
    border-color: #cce3d4;
}

.proposal-upload-requirements {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 20px;
}

.proposal-upload-requirements div {
    font-size: 11px;
    color: #7d8c82;
}

.proposal-upload-requirements i {
    color: #4d9668;
    margin-right: 5px;
}

/* Skema info & RAB */

.proposal-skema-info {
    margin-top: 12px;
    padding: 14px 16px;
    background: #f5faf6;
    border: 1px solid #e3eee6;
    border-radius: 12px;
    font-size: 12px;
    color: #688073;
    line-height: 1.7;
}

.proposal-rab-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.proposal-rab-row .rab-nama {
    flex: 2;
    min-width: 0;
}

.proposal-rab-row .rab-jumlah {
    flex: 1;
    min-width: 0;
}

.proposal-rab-row .rab-keterangan {
    flex: 2;
    min-width: 0;
}

.proposal-rab-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 18px;
    padding: 14px 16px;
    background: #f0f5f2;
    border-radius: 12px;
    font-size: 13px;
    color: #354c3d;
}

.proposal-rab-total strong {
    font-size: 16px;
    color: #20563c;
}

.proposal-rab-warning {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    margin-top: 12px;
    padding: 14px 16px;
    background: #fff1f1;
    border: 1px solid #f2d1d1;
    border-radius: 12px;
    color: #a33e3e;
    font-size: 12px;
    line-height: 1.7;
}

.proposal-draft-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 13px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    background: #fff;
    border: 1px solid #87b59a;
    color: #28764d;
    cursor: pointer;
    transition: .2s ease;
}

.proposal-draft-button:hover {
    background: #eaf5ee;
}

.proposal-submit-button:disabled {
    opacity: .5;
    cursor: not-allowed;
}

/* Footer */

.proposal-form-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 25px;
}

.proposal-cancel-button,
.proposal-submit-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 13px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: .2s ease;
}

.proposal-cancel-button {
    background: #fff;
    border: 1px solid #e0e8e2;
    color: #718078;
}

.proposal-cancel-button:hover {
    background: #f5f8f6;
    color: #345b43;
}

.proposal-submit-button {
    background: #28764d;
    border: 1px solid #28764d;
    color: #fff;
    cursor: pointer;
}

.proposal-submit-button:hover {
    background: #205d3c;
    border-color: #205d3c;
}

/* Responsive */

@media (max-width: 768px) {

    .proposal-create-header {
        padding: 22px;
    }

    .proposal-create-header h2 {
        font-size: 22px;
    }

    .proposal-create-header-icon {
        display: none;
    }

    .proposal-form-card-header {
        padding: 20px;
    }

    .proposal-form-card-body {
        padding: 20px;
    }

    .proposal-member-row {
        gap: 8px;
    }

    .proposal-member-number,
    .proposal-remove-member {
        width: 38px;
        height: 38px;
    }
}

@media (max-width: 576px) {

    .proposal-create-page {
        padding-left: 0;
        padding-right: 0;
    }

    .proposal-limit-info {
        padding: 16px;
    }

    .proposal-form-card-header {
        gap: 12px;
    }

    .proposal-form-number {
        width: 40px;
        height: 40px;
    }

    .proposal-form-card-header h4 {
        font-size: 15px;
    }

    .proposal-form-card-body {
        padding: 17px;
    }

    .proposal-upload-box {
        padding: 25px 15px;
    }

    .proposal-upload-requirements {
        gap: 10px;
        flex-direction: column;
    }

    .proposal-form-footer {
        flex-direction: column-reverse;
        align-items: stretch;
    }

    .proposal-cancel-button,
    .proposal-submit-button,
    .proposal-draft-button {
        width: 100%;
    }

    .proposal-rab-row {
        flex-wrap: wrap;
    }

    .proposal-rab-row .proposal-input {
        flex: 1 1 100%;
    }
}

</style>
@endsection
