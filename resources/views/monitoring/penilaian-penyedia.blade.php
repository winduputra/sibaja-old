@extends('layouts.user')

@section('content')
<div class="bg-white min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Penilaian Penyedia</h4>
    </div>

    <div class="ratio ratio-16x9 bg-white">
        <iframe
            title="Penilaian Penyedia"
            src="https://datastudio.google.com/embed/u/0/reporting/998e9dad-3b4a-425c-aea9-5269510bcc02/page/88QaD?params=%7B%22df15%22:%22include%25EE%2580%25800%25EE%2580%2580IN%25EE%2580%2580Pemerintah%2520Daerah%2520Provinsi%2520Lampung%22%7D"
            frameborder="0"
            style="border:0;"
            allowfullscreen
            loading="lazy"
            sandbox="allow-storage-access-by-user-activation allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox">
        </iframe>
    </div>
</div>
@endsection
