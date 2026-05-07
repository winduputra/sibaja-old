@forelse ($data as $item)
  <tr>
    <td><p class="text-sm">{{ $item->kd_nontender }}</p></td>
    <td>
      <a href="{{ route('non-tender.show', ['code' => $item->kd_nontender]) }}" class="text-decoration-none fw-bold text-primary">
        {{ $item->nama_paket }}
      </a>
    </td>
    <td><p class="text-sm">{{ $item->status_nontender ?? '-' }}</p></td>
    <td><p class="text-sm">
      {{ isset($item->hps) ? format_hps_pagu($item->hps) : '-' }}
    </p></td>
    <td><p class="text-sm">
      {{ isset($item->nilai_pdn_kontrak) ? \App\Services\HelperService::moneyFormat($item->nilai_pdn_kontrak) : '-' }}
    </p></td>
    <td><p class="text-sm">
      {{ isset($item->nilai_umk_kontrak) ? \App\Services\HelperService::moneyFormat($item->nilai_umk_kontrak) : '-' }}
    </p></td>
  </tr>
@empty
  <tr>
    <td colspan="7" class="text-center">Tidak ada data ditemukan</td>
  </tr>
@endforelse
