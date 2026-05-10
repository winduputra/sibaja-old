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

/* Toggle caret */
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

/* Submenu styling */
.dropdown-submenu {
    position: relative;
}

.dropdown-submenu > .dropdown-item::after {
    content: "";
    display: inline-block;
    margin-left: 0.5rem;
    vertical-align: middle;
    border-left: 0.4em solid transparent;
    border-right: 0.4em solid transparent;
    border-top: 0.4em solid;
    transition: transform 0.2s ease;
}

/* Rotate caret on open */
.dropdown-submenu.show > .dropdown-item::after {
    transform: rotate(90deg);
}

.dropdown-submenu .dropdown-menu {
    top: 100% !important;
    left: 0 !important;
    margin-top: 0;
    background-color: #ffffff !important;
    display: none; /* default: hidden */
}



    .dropdown-submenu .dropdown-menu {
        position: relative !important;
        left: 0 !important;
        top: auto !important;
        margin-left: 0 !important;
        width: 100%;
        box-shadow: none;
    }

    .dropdown-submenu > .dropdown-item {
        padding-left: 1.25rem;
    }

    .dropdown-item {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .dropdown-submenu > .dropdown-item i {
        margin-left: auto;
    }
}


/* Tampilkan submenu sebagai dropdown biasa di mobile */
@media (max-width: 991.98px) {
    .dropdown-menu {
        margin-left: 1rem;
        border: none;
        box-shadow: none;
    }

    .dropdown-submenu > .dropdown-menu {
        display: block !important;
        position: relative;
        top: auto;
        left: auto;
        margin-left: 1.5rem;
    }

    .dropdown-submenu > .dropdown-item::after {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
    }

    .dropdown-submenu.show > .dropdown-item::after {
        transform: translateY(-50%) rotate(90deg);
    }

    .dropdown-item {
        padding-left: 0.5rem;
    }
}
.dropdown-item.active {
    background-color: #ffffff !important;
    color: #000000 !important;
}
.dropdown-submenu > .dropdown-item i {
  margin-left: auto;
  font-size: 0.75rem;
}

.dropdown-toggle > i {
  font-size: 0.75rem;
}

  /* Ikon panah rotate saat submenu aktif */
  .dropdown-submenu .dropdown-toggle[aria-expanded="true"] i {
    transform: rotate(90deg); /* dari kanan ke bawah */
  }

  .dropdown-submenu .dropdown-toggle i {
    transition: transform 0.3s ease;
  }
  .transition {
  transition: transform 0.3s ease;
}

.rotate-90 {
  transform: rotate(90deg);
}
/* Pastikan panah default tidak muncul */
.dropdown-submenu > .dropdown-item::after {
  content: none !important;
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
          <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('tender.list') ? 'active' : '' }}" href="{{ route('tender.list') }}">Tender</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('non-tender.list') ? 'active' : '' }}" href="{{ route('non-tender.list') }}">Non Tender</a>
        </li>

        <!-- E-Purchasing Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle {{ request()->is('report/ekatalog*') || request()->is('report/tokodaring*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">E-Purchasing</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('report.ekatalog') }}">E-Katalog</a></li>
            <li><a class="dropdown-item" href="{{ route('report.tokodaring') }}">Toko Daring</a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('report.rup') ? 'active' : '' }}" href="{{ route('report.rup') }}">RUP</a>
        </li>

        <!-- Summary Report Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle {{ request()->routeIs('non-tender.realization', 'tender.realization') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">Summary Report</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item {{ request()->routeIs('non-tender.realization') ? 'active' : '' }}" href="{{ route('non-tender.realization') }}">Realisasi Non Tender</a></li>
            <li><a class="dropdown-item {{ request()->routeIs('tender.realization') ? 'active' : '' }}" href="{{ route('tender.realization') }}">Realisasi Tender</a></li>
          </ul>
        </li>

   <!-- Monitoring Dropdown -->
<li class="nav-item dropdown position-relative">
  <a class="nav-link dropdown-toggle {{ request()->is('monitoring*') ? 'active' : '' }}"
     href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    Monitoring
  </a>

  <ul class="dropdown-menu shadow rounded-3 p-1 bg-white">
    <li>
      <a class="dropdown-item px-3 py-2 rounded-2" href="{{ route('monitoring.realisasi.satker') }}">
        Realisasi Pengadaan
      </a>
    </li>
    <li>
      <a class="dropdown-item px-3 py-2 rounded-2" href="{{ route('monitoring.rekap.realisasi-berlangsung') }}">
        Realisasi Berlangsung
      </a>
    </li>
    <li>
      <a class="dropdown-item px-3 py-2 rounded-2" href="{{ route('monitoring.rekap.realisasi') }}">
        Realisasi Selesai
      </a>
    </li>
    <li>
      <a class="dropdown-item px-3 py-2 rounded-2 {{ request()->routeIs('monitoring.rup.index', 'monitoring.rup.detail', 'monitoring.rup.export-pdf') ? 'active' : '' }}" 
         href="{{ route('monitoring.rup.index') }}">
        RUP Monitoring
      </a>
    </li>

    <li class="dropdown-submenu position-relative">
  <a class="dropdown-item d-flex justify-content-between align-items-center px-3 py-2"
     href="#" aria-expanded="false" id="submenuToggle">
    <span class="d-flex align-items-center">
      Monitoring Belum Input
      <i class="bi bi-caret-down-fill ms-1 transition" id="arrowBelumInput"></i>
    </span>
  </a>

  <ul class="collapse list-unstyled bg-white" id="submenuBelumInput">
    <li>
      <a class="dropdown-item px-3 py-2 {{ request()->routeIs('monitoring.kontrak') ? 'active' : '' }}" 
         href="{{ route('monitoring.kontrak') }}">
        Kontrak Tender
      </a>
    </li>
    <li>
      <a class="dropdown-item px-3 py-2 {{ request()->routeIs('monitoring.kontrak.non_tender') ? 'active' : '' }}" 
         href="{{ route('monitoring.kontrak.non_tender') }}">
        Kontrak Non Tender
      </a>
    </li>
  </ul>
</li>



      </ul>
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
  // Hover behavior untuk submenu
  document.querySelectorAll('.dropdown-submenu').forEach(function (submenu) {
    const toggle = submenu.querySelector('[data-bs-toggle="dropdown"]');

    submenu.addEventListener('mouseenter', () => {
      if (toggle) {
        bootstrap.Dropdown.getOrCreateInstance(toggle).show();
      }
    });

    submenu.addEventListener('mouseleave', () => {
      setTimeout(() => {
        if (!submenu.matches(':hover')) {
          if (toggle) {
            bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
          }
        }
      }, 300);
    });

    const parentMenu = submenu.closest('.dropdown');
    if (parentMenu) {
      parentMenu.addEventListener('mouseleave', () => {
        if (toggle) {
          bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
        }
      });
    }
  });

  // Klik toggle submenu di mobile/desktop
  document.querySelectorAll('.dropdown-submenu > a').forEach(function (submenuToggle) {
    submenuToggle.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      const submenu = this.nextElementSibling;

      // Tutup semua submenu lain
      const openSubmenus = this.closest('.dropdown-menu')?.querySelectorAll('.dropdown-menu.show') || [];
      openSubmenus.forEach(function (el) {
        if (el !== submenu) el.classList.remove('show');
      });

      // Toggle submenu
      if (submenu) submenu.classList.toggle('show');
    });
  });

  // Klik di luar => tutup submenu
  window.addEventListener('click', function (e) {
    document.querySelectorAll('.dropdown-submenu .dropdown-menu.show').forEach(function (submenu) {
      if (!submenu.contains(e.target)) {
        submenu.classList.remove('show');
      }
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
