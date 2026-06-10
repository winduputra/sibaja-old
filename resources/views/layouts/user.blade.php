{{-- File: resources/views/layouts/user.blade.php --}}

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiBAJA Provinsi Lampung</title>

    <!-- Fonts & CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/sibaja.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('32x32.png') }}"/>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url('16x16.png') }}"/>
    
    <style>
    body {
        margin: 0;
        padding: 0;
        font-family: 'Public Sans', sans-serif;
        background-color: #ffffff;
    }

    .dropdown-menu {
        background-color: #ffffff !important;
        min-width: 220px;
        border: 1px solid #ddd;
        padding: 0.25rem;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .dropdown-item {
        border-radius: 0.375rem;
        transition: background-color 0.2s ease;
        padding: 0.5rem 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 0.15rem 0;
    }

    .dropdown-item:hover {
        background-color: #f1f1f1;
    }

    .dropdown-toggle::after {
        margin-left: 0.5rem;
        vertical-align: middle;
        border-top: 0.4em solid;
        border-right: 0.4em solid transparent;
        border-left: 0.4em solid transparent;
        transition: transform 0.2s ease;
    }

    .show > .dropdown-toggle::after {
        transform: rotate(-180deg);
    }

    .dropdown-submenu {
        position: relative;
    }

    .dropdown-submenu > .dropdown-item {
        gap: 0.75rem;
    }

    .dropdown-submenu > .dropdown-menu {
        display: none;
        position: absolute;
        left: 100% !important;
        top: 0 !important;
        margin-top: 0;
        margin-left: 0;
    }

    .dropdown-submenu:hover > .dropdown-menu,
    .dropdown-submenu:focus-within > .dropdown-menu,
    .dropdown-submenu > .dropdown-menu.show {
        display: block;
    }

    .dropdown-submenu .submenu-caret {
        margin-left: auto;
        font-size: 0.75rem;
        transition: transform 0.2s ease;
    }

    .dropdown-submenu:hover > .dropdown-item .submenu-caret,
    .dropdown-submenu:focus-within > .dropdown-item .submenu-caret {
        transform: translateX(0.125rem);
    }

    .dropdown-item.active {
        background-color: #ffffff !important;
        color: #000000 !important;
    }

    @media (max-width: 991.98px) {
        .dropdown-menu {
            margin-left: 1rem;
            border: none;
            box-shadow: none;
        }

        .dropdown-submenu > .dropdown-menu {
            display: block !important;
            position: static !important;
            left: auto !important;
            top: auto !important;
            margin-top: 0;
            margin-left: 1.25rem;
            width: auto;
            box-shadow: none;
        }

        .dropdown-submenu > .dropdown-item {
            padding-left: 0.5rem;
        }

        .dropdown-submenu .submenu-caret,
        .dropdown-submenu:hover > .dropdown-item .submenu-caret,
        .dropdown-submenu:focus-within > .dropdown-item .submenu-caret {
            transform: rotate(90deg);
        }

        .dropdown-item {
            padding-left: 0.5rem;
        }
    }

</style>

    @stack('head')
    @stack('style')
    @stack('css')
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top">
  <div class="container-fluid px-4">

    <!-- Logo -->
    <div class="d-flex align-items-center me-4 sibaja-logo-box shadow-sm">
      <img src="{{ asset('images/sibaja-logo.png') }}" alt="Logo" class="me-2">
      <div class="sibaja-header-text">
        <div class="sibaja-header-title">
          <strong>SiBAJA</strong>
          <span class="text-divider">|</span>
          <span>Provinsi Lampung</span>
        </div>
        <hr class="sibaja-divider">
        <div class="sibaja-subtitle">Sistem Informasi Barang dan Jasa</div>
      </div>
    </div>

    <!-- Toggle Mobile -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Navbar Menu -->
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav ms-4 me-auto">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Dashboard</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle {{ request()->routeIs('report.rup', 'monitoring.rup.*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">RUP</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item {{ request()->routeIs('report.rup') ? 'active' : '' }}" href="{{ route('report.rup') }}">Data RUP</a></li>
            <li><a class="dropdown-item {{ request()->routeIs('monitoring.rup.*') ? 'active' : '' }}" href="{{ route('monitoring.rup.index') }}">RUP Monitoring</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown position-relative">
          <a class="nav-link dropdown-toggle {{ request()->routeIs('tender.realization', 'non-tender.realization', 'report.ekatalog', 'report.tokodaring', 'realisasi.*') ? 'active' : '' }}"
             href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Realisasi
          </a>
          <ul class="dropdown-menu shadow rounded-3 p-1 bg-white">
            <li><a class="dropdown-item px-3 py-2 rounded-2 {{ request()->routeIs('tender.realization') ? 'active' : '' }}" href="{{ route('tender.realization') }}">Tender</a></li>
            <li><a class="dropdown-item px-3 py-2 rounded-2 {{ request()->routeIs('non-tender.realization') ? 'active' : '' }}" href="{{ route('non-tender.realization') }}">Non Tender</a></li>
            <li><a class="dropdown-item px-3 py-2 rounded-2 {{ request()->routeIs('report.ekatalog') ? 'active' : '' }}" href="{{ route('report.ekatalog') }}">E-Purchasing</a></li>
            <li><a class="dropdown-item px-3 py-2 rounded-2 {{ request()->routeIs('realisasi.swakelola') ? 'active' : '' }}" href="{{ route('realisasi.swakelola') }}">Swakelola</a></li>
            <li class="dropdown-submenu position-relative">
              <a class="dropdown-item d-flex justify-content-between align-items-center px-3 py-2" href="#" aria-expanded="false">
                <span class="d-flex align-items-center">Pencatatan</span>
                <i class="bi bi-caret-right-fill submenu-caret" aria-hidden="true"></i>
              </a>
              <ul class="dropdown-menu shadow rounded-3 p-1 bg-white">
                <li><a class="dropdown-item px-3 py-2 {{ request()->routeIs('realisasi.pencatatan.non-tender') ? 'active' : '' }}" href="{{ route('realisasi.pencatatan.non-tender') }}">Non Tender</a></li>
                <li><a class="dropdown-item px-3 py-2 {{ request()->routeIs('realisasi.pencatatan.swakelola') ? 'active' : '' }}" href="{{ route('realisasi.pencatatan.swakelola') }}">Swakelola</a></li>
              </ul>
            </li>
            <li><a class="dropdown-item px-3 py-2 rounded-2 {{ request()->routeIs('report.tokodaring') ? 'active' : '' }}" href="{{ route('report.tokodaring') }}">Toko Daring</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown position-relative">
          <a class="nav-link dropdown-toggle {{ request()->routeIs('monitoring.pdn-umk-tracker', 'monitoring.kontrak*', 'monitoring.realisasi.*', 'monitoring.rekap.*', 'monitoring.progress-pengadaan.*') ? 'active' : '' }}"
             href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Monitoring
          </a>
          <ul class="dropdown-menu shadow rounded-3 p-1 bg-white">
            <li><a class="dropdown-item px-3 py-2 rounded-2 {{ request()->routeIs('monitoring.pdn-umk-tracker') ? 'active' : '' }}" href="{{ route('monitoring.pdn-umk-tracker') }}">PDN/UMK Tracker</a></li>
            <li class="dropdown-submenu position-relative">
              <a class="dropdown-item d-flex justify-content-between align-items-center px-3 py-2" href="#" aria-expanded="false">
                <span class="d-flex align-items-center">Kontrak</span>
                <i class="bi bi-caret-right-fill submenu-caret" aria-hidden="true"></i>
              </a>
              <ul class="dropdown-menu shadow rounded-3 p-1 bg-white">
                <li><a class="dropdown-item px-3 py-2 {{ request()->routeIs('monitoring.kontrak') ? 'active' : '' }}" href="{{ route('monitoring.kontrak') }}">Tender</a></li>
                <li><a class="dropdown-item px-3 py-2 {{ request()->routeIs('monitoring.kontrak.non_tender') ? 'active' : '' }}" href="{{ route('monitoring.kontrak.non_tender') }}">Non Tender</a></li>
              </ul>
            </li>
            <li class="dropdown-submenu position-relative">
              <a class="dropdown-item d-flex justify-content-between align-items-center px-3 py-2" href="#" aria-expanded="false">
                <span class="d-flex align-items-center">Progress Pengadaan</span>
                <i class="bi bi-caret-right-fill submenu-caret" aria-hidden="true"></i>
              </a>
              <ul class="dropdown-menu shadow rounded-3 p-1 bg-white">
                <li><a class="dropdown-item px-3 py-2 {{ request()->routeIs('monitoring.realisasi.satker') ? 'active' : '' }}" href="{{ route('monitoring.realisasi.satker') }}">Realisasi Satker</a></li>
                <li><a class="dropdown-item px-3 py-2 {{ request()->routeIs('monitoring.rekap.realisasi-berlangsung') ? 'active' : '' }}" href="{{ route('monitoring.rekap.realisasi-berlangsung') }}">Berlangsung</a></li>
                <li><a class="dropdown-item px-3 py-2 {{ request()->routeIs('monitoring.rekap.realisasi') ? 'active' : '' }}" href="{{ route('monitoring.rekap.realisasi') }}">Selesai</a></li>
              </ul>
            </li>
            <li><a class="dropdown-item px-3 py-2 rounded-2 {{ request()->routeIs('monitoring.progress-pengadaan.penilaian-penyedia') ? 'active' : '' }}" href="{{ route('monitoring.progress-pengadaan.penilaian-penyedia') }}">Penilaian Penyedia</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('rekapitulasi-nasional') ? 'active' : '' }}" href="{{ route('rekapitulasi-nasional') }}">Rekapitulasi Nasional</a>
        </li>
  </ul>

  <!-- Page Specific Filters -->
  <div class="d-flex align-items-center ms-auto">
    @yield('navbar-extra')
  </div>

      <!-- User Icon -->
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle" style="font-size: 1.4rem;"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
            <li><a class="dropdown-item text-danger" href="{{ route('logout') }}">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>



<!-- KONTEN -->
<main class="mt-0">
    <div class="container-fluid py-4">
        @yield('content_header')
        @yield('content')
    </div>
</main>

<!-- INFORMASI KANTOR + JAM -->
<div style="background-color: #1b2141; color: white; padding: 50px 0 30px 0;">
  <div class="container d-flex flex-column flex-md-row justify-content-between align-items-start">
    <!-- Info Kontak -->
    <div class="mb-4 mb-md-0">
      <h5 class="fw-bold text-white mb-3">Kantor Kami</h5>
      <p class="mb-1"><i class="bi bi-geo-alt-fill me-2"></i> Alamat: Jl. Wolter Monginsidi No.69, Talang, Kec. Telukbetung Selatan, Kota Bandar Lampung, Lampung 35221</p>
      <p class="mb-1"><i class="bi bi-telephone-fill me-2"></i> Telepon: (0721) 481107</p>
      <p class="mb-1"><i class="bi bi-envelope-fill me-2"></i> Email: biropbj@lampungprov.go.id</p>
      <div class="d-flex gap-3 mt-3">
        <a href="#" class="text-white fs-4"><i class="bi bi-facebook"></i></a>
        <a href="#" class="text-white fs-4"><i class="bi bi-youtube"></i></a>
        <a href="https://www.instagram.com/biropbj?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" class="text-white fs-4"><i class="bi bi-instagram"></i></a>

      </div>
    </div>

    <!-- Jam Realtime -->
    <div class="d-flex gap-3">
      <div class="bg-primary text-center rounded p-3 shadow" style="min-width: 100px; background-color: #0F2C74 !important;">
        <div id="jam-box" class="fw-bold fs-3">00</div>
        <div class="text-uppercase mt-1" style="font-size: 14px;">JAM</div>
      </div>
      <div class="bg-primary text-center rounded p-3 shadow" style="min-width: 100px; background-color: #0F2C74 !important;">
        <div id="menit-box" class="fw-bold fs-3">00</div>
        <div class="text-uppercase mt-1" style="font-size: 14px;">MENIT</div>
      </div>
      <div class="bg-primary text-center rounded p-3 shadow" style="min-width: 100px; background-color: #0F2C74 !important;">
        <div id="detik-box" class="fw-bold fs-3">00</div>
        <div class="text-uppercase mt-1" style="font-size: 14px;">DETIK</div>
      </div>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer class="text-center p-2 mt-0" style="background-color: #2b3e64; color: white;">
    <p class="mb-0">© {{ date('Y') }} Biro Pengadaan Barang dan Jasa Provinsi Lampung</p>
</footer>


<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>AOS.init();</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.dropdown-submenu > a').forEach(function (submenuToggle) {
    submenuToggle.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const submenu = this.nextElementSibling;
      if (!submenu) return;

      const openSubmenus = this.closest('.dropdown-menu')?.querySelectorAll('.dropdown-menu.show') || [];
      openSubmenus.forEach(function (el) {
        if (el !== submenu) {
          el.classList.remove('show');
          el.previousElementSibling?.setAttribute('aria-expanded', 'false');
        }
      });

      const isOpen = submenu.classList.toggle('show');
      this.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  });

  document.querySelectorAll('.nav-item.dropdown').forEach(function (dropdown) {
    dropdown.addEventListener('hidden.bs.dropdown', function () {
      this.querySelectorAll('.dropdown-submenu .dropdown-menu.show').forEach(function (submenu) {
        submenu.classList.remove('show');
      });

      this.querySelectorAll('.dropdown-submenu > a[aria-expanded="true"]').forEach(function (submenuToggle) {
        submenuToggle.setAttribute('aria-expanded', 'false');
      });
    });
  });

  window.addEventListener('click', function (e) {
    if (e.target.closest('.dropdown-submenu')) return;

    document.querySelectorAll('.dropdown-submenu .dropdown-menu.show').forEach(function (submenu) {
      submenu.classList.remove('show');
    });

    document.querySelectorAll('.dropdown-submenu > a[aria-expanded="true"]').forEach(function (submenuToggle) {
      submenuToggle.setAttribute('aria-expanded', 'false');
    });
  });
});

  function closeSubmenu(button) {
    // Menutup dropdown terdekat (parent <ul>)
    const dropdownMenu = button.closest('.dropdown-menu');
    if (dropdownMenu) {
      dropdownMenu.classList.remove('show');
      // Menutup menu utama jika perlu
      const parentToggle = dropdownMenu.parentElement.querySelector('[data-bs-toggle="dropdown"]');
      if (parentToggle) {
        parentToggle.setAttribute('aria-expanded', 'false');
      }
    }
  }

</script>

@stack('scripts')
@stack('script')
@stack('js')
</body>
</html>
