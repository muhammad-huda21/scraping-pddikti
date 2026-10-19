<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PDDIKTI Explorer | Data Pendidikan Tinggi Aceh & PNL</title>
  
  <!-- Google Fonts & Font Awesome Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Chart.js & TomSelect (Searchable Dropdown) CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
  
  <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
  <style>
  /* Modal Detail Mahasiswa per Prodi - Dedicated Scoped Styling */
  #prodiDetailModal.pddikti-custom-modal {
    display: none;
    position: fixed !important;
    inset: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(15, 23, 42, 0.75) !important;
    backdrop-filter: blur(6px) !important;
    -webkit-backdrop-filter: blur(6px) !important;
    z-index: 99999 !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 1.5rem !important;
    margin: 0 !important;
    box-sizing: border-box !important;
  }

  #prodiDetailModal.pddikti-custom-modal.active {
    display: flex !important;
  }

  #prodiDetailModal .pddikti-modal-card {
    background: #FFFFFF !important;
    border: 1px solid #CBD5E1 !important;
    border-radius: 12px !important;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35) !important;
    width: 100% !important;
    max-width: 980px !important;
    max-height: 90vh !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    animation: pddiktiModalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
  }

  @keyframes pddiktiModalFadeIn {
    from { opacity: 0; transform: translateY(12px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }

  #prodiDetailModal .modal-header {
    padding: 1.25rem 1.5rem !important;
    border-bottom: 1px solid #E2E8F0 !important;
    background: #FFFFFF !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: flex-start !important;
    gap: 1rem !important;
  }

  #prodiDetailModal .modal-header h3 {
    margin: 0 !important;
    font-size: 1.15rem !important;
    font-weight: 700 !important;
    color: #0F172A !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
  }

  #prodiDetailModal .modal-header p {
    margin: 0.25rem 0 0 0 !important;
    font-size: 0.8125rem !important;
    color: #475569 !important;
  }

  #prodiDetailModal .modal-close-btn {
    background: transparent !important;
    border: none !important;
    font-size: 1.5rem !important;
    line-height: 1 !important;
    color: #94A3B8 !important;
    cursor: pointer !important;
    padding: 0.25rem 0.5rem !important;
    border-radius: 6px !important;
    transition: all 0.15s ease !important;
  }

  #prodiDetailModal .modal-close-btn:hover {
    background: #F1F5F9 !important;
    color: #0F172A !important;
  }

  #prodiDetailModal .modal-body {
    padding: 1.25rem 1.5rem !important;
    overflow-y: auto !important;
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 1.25rem !important;
    background: #FFFFFF !important;
  }

  #prodiDetailModal .angkatan-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)) !important;
    gap: 0.75rem !important;
  }

  #prodiDetailModal .angkatan-card {
    background: #F8FAFC !important;
    border: 1px solid #E2E8F0 !important;
    border-radius: 8px !important;
    padding: 0.75rem 0.85rem !important;
    cursor: pointer !important;
    transition: all 0.18s ease !important;
    text-align: left !important;
  }

  #prodiDetailModal .angkatan-card:hover {
    border-color: #BFDBFE !important;
    background: #EFF6FF !important;
    transform: translateY(-2px) !important;
  }

  #prodiDetailModal .angkatan-card.active {
    border-color: #1E40AF !important;
    background: #EFF6FF !important;
    box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2) !important;
  }

  #prodiDetailModal .angkatan-card-year {
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    color: #475569 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
  }

  #prodiDetailModal .angkatan-card-total {
    font-size: 1.35rem !important;
    font-weight: 700 !important;
    font-family: 'Space Grotesk', monospace !important;
    color: #1E40AF !important;
    margin: 0.2rem 0 0.35rem 0 !important;
  }

  #prodiDetailModal .angkatan-card-badges {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 0.25rem !important;
    font-size: 0.65rem !important;
  }

  #prodiDetailModal .modal-toolbar {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    gap: 0.75rem !important;
    flex-wrap: wrap !important;
    background: #F1F5F9 !important;
    padding: 0.75rem 1rem !important;
    border-radius: 8px !important;
  }

  #prodiDetailModal .modal-toolbar-filters {
    display: flex !important;
    gap: 0.5rem !important;
    align-items: center !important;
    flex-wrap: wrap !important;
    flex: 1 !important;
  }

  #prodiDetailModal .modal-footer {
    padding: 0.85rem 1.5rem !important;
    border-top: 1px solid #E2E8F0 !important;
    background: #F8FAFC !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
  }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="app-header">
    <div class="header-container">
      <div class="brand">
        <div class="brand-icon">
          <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div class="brand-text">
          <h1>PDDIKTI Explorer</h1>
          <p>Portal Data Pendidikan Tinggi Aceh & Politeknik Negeri Lhokseumawe</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn btn-primary" onclick="openLiveSearchModal()">
          <i class="fa-solid fa-magnifying-glass"></i> Live Search PDDIKTI
        </button>
      </div>
    </div>
  </header>

  <!-- Metric Highlights Bar -->
  <section class="metrics-section">
    <div class="metrics-grid">
      <div class="metric-card">
        <div class="metric-icon icon-blue"><i class="fa-solid fa-building-columns"></i></div>
        <div class="metric-content">
          <div class="metric-value" id="statKampus">--</div>
          <div class="metric-label">Perguruan Tinggi di Aceh</div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon icon-emerald"><i class="fa-solid fa-book-open"></i></div>
        <div class="metric-content">
          <div class="metric-value" id="statProdi">--</div>
          <div class="metric-label">Program Studi Terdata</div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon icon-purple"><i class="fa-solid fa-users"></i></div>
        <div class="metric-content">
          <div class="metric-value" id="statMhs">--</div>
          <div class="metric-label">Total Mahasiswa Terdata</div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon icon-amber"><i class="fa-solid fa-chalkboard-user"></i></div>
        <div class="metric-content">
          <div class="metric-value" id="statDosen">--</div>
          <div class="metric-label">Dosen Aceh Terindeks</div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon icon-cyan"><i class="fa-solid fa-flask"></i></div>
        <div class="metric-content">
          <div class="metric-value" id="statPenelitian">--</div>
          <div class="metric-label">Penelitian Dosen PNL</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Main Navigation Tabs -->
  <nav class="tabs-nav">
    <div class="nav-container">
      <button class="tab-btn active" data-tab="tab-mahasiswa">
        <i class="fa-solid fa-user-graduate"></i>
        <span>Mahasiswa Aceh</span>
      </button>
      <button class="tab-btn" data-tab="tab-dosen">
        <i class="fa-solid fa-user-tie"></i>
        <span>Dosen Aceh</span>
      </button>
      <button class="tab-btn" data-tab="tab-penelitian">
        <i class="fa-solid fa-microscope"></i>
        <span>Penelitian PNL</span>
      </button>
      <button class="tab-btn" data-tab="tab-prodi">
        <i class="fa-solid fa-chart-column"></i>
        <span>Mahasiswa per Prodi</span>
      </button>
      <button class="tab-btn" data-tab="tab-pendidikan">
        <i class="fa-solid fa-award"></i>
        <span>Pendidikan Dosen</span>
      </button>
      <button class="tab-btn" data-tab="tab-kampus">
        <i class="fa-solid fa-building-columns"></i>
        <span>Daftar Kampus</span>
      </button>
    </div>
  </nav>

  <!-- Main Tab Contents -->
  <main class="content-container">

    <!-- ============================================== -->
    <!-- TAB 1: MAHASISWA KAMPUS ACEH -->
    <!-- ============================================== -->
    <section id="tab-mahasiswa" class="tab-content active">
      <div class="panel-header">
        <div>
          <h2><i class="fa-solid fa-user-graduate text-accent"></i> Mahasiswa Kampus di Aceh</h2>
          <p>Pencarian data mahasiswa perguruan tinggi di seluruh Aceh</p>
        </div>
        <div class="panel-actions" style="display: flex; gap: 0.5rem; align-items: center;">
          <button type="button" onclick="syncCurrentStatus()" id="btnSyncStatus" class="btn btn-outline" title="Sinkronkan status mahasiswa terbaru langsung dari PDDIKTI untuk kampus/prodi yang sedang dicari/difilter">
            <i class="fa-solid fa-arrows-rotate"></i> Sinkron Status
          </button>
          <button type="button" onclick="exportMahasiswa('excel')" class="btn btn-secondary" title="Download data mahasiswa sesuai filter ke format Microsoft Excel (.xls)">
            <i class="fa-solid fa-file-excel text-success"></i> Download Excel
          </button>
          <button type="button" onclick="exportMahasiswa('csv')" class="btn btn-outline" title="Download format CSV">
            <i class="fa-solid fa-file-csv"></i> CSV
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="filter-box">
        <div class="filter-group">
          <label>Cari Mahasiswa (Nama / NIM)</label>
          <div class="search-input-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="mhsSearchInput" placeholder="Ketik nama, NIM, atau program studi mahasiswa...">
          </div>
        </div>
        <div class="filter-group" style="min-width: 260px;">
          <label>Filter Kampus di Aceh (120+ Kampus)</label>
          <select id="mhsPtFilter" placeholder="Ketik untuk cari kampus di Aceh..."></select>
        </div>
        <div class="filter-group" style="min-width: 160px;">
          <label>Tahun Angkatan</label>
          <select id="mhsAngkatanFilter" onchange="loadMahasiswa(1)">
            <option value="">Semua Angkatan</option>
            <option value="2026">2026</option>
            <option value="2025">2025</option>
            <option value="2024">2024</option>
            <option value="2023">2023</option>
            <option value="2022">2022</option>
            <option value="2021">2021</option>
            <option value="2020">2020</option>
            <option value="2019">2019</option>
            <option value="2018">2018</option>
            <option value="2017">2017</option>
            <option value="2016">2016</option>
            <option value="2015">2015</option>
          </select>
        </div>
        <div class="filter-group btn-align">
          <button class="btn btn-primary" onclick="loadMahasiswa(1)">
            <i class="fa-solid fa-filter"></i> Terapkan Filter
          </button>
          <button class="btn btn-outline" onclick="resetMhsFilter()">
            Reset
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th width="60">No</th>
                <th>Nama Mahasiswa</th>
                <th>NIM</th>
                <th width="100">Angkatan</th>
                <th>Perguruan Tinggi</th>
                <th>Program Studi</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="mhsTableBody">
              <tr><td colspan="7" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-muted"></i> Memuat data mahasiswa...</td></tr>
            </tbody>
          </table>
        </div>
        <div class="pagination-footer">
          <span id="mhsPaginationInfo">Menampilkan data...</span>
          <div class="pagination-controls" id="mhsPaginationButtons"></div>
        </div>
      </div>
    </section>

    <!-- ============================================== -->
    <!-- TAB 2: DOSEN KAMPUS ACEH -->
    <!-- ============================================== -->
    <section id="tab-dosen" class="tab-content">
      <div class="panel-header">
        <div>
          <h2><i class="fa-solid fa-user-tie text-accent"></i> Dosen Kampus di Aceh</h2>
          <p>Direktori dosen perguruan tinggi di Aceh terdaftar PDDIKTI</p>
        </div>
        <div class="panel-actions">
          <button type="button" onclick="exportDosen('excel')" class="btn btn-secondary" title="Download data dosen sesuai filter ke format Microsoft Excel (.xls)">
            <i class="fa-solid fa-file-excel text-success"></i> Download Excel
          </button>
          <button type="button" onclick="exportDosen('csv')" class="btn btn-outline" title="Download format CSV">
            <i class="fa-solid fa-file-csv text-muted"></i> CSV
          </button>
        </div>
      </div>

      <div class="filter-box">
        <div class="filter-group">
          <label>Cari Dosen (Nama / NIDN)</label>
          <div class="search-input-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="dosenSearchInput" placeholder="Ketik nama atau NIDN dosen...">
          </div>
        </div>
        <div class="filter-group" style="min-width: 280px;">
          <label>Filter Kampus di Aceh (120+ Kampus)</label>
          <select id="dosenPtFilter" placeholder="Ketik untuk cari kampus di Aceh..."></select>
        </div>
        <div class="filter-group btn-align">
          <button class="btn btn-primary" onclick="loadDosen(1)">
            <i class="fa-solid fa-filter"></i> Terapkan Filter
          </button>
          <button class="btn btn-outline" onclick="resetDosenFilter()">
            Reset
          </button>
        </div>
      </div>

      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th width="60">No</th>
                <th>Nama Dosen</th>
                <th>NIDN / NUPTK</th>
                <th>Perguruan Tinggi</th>
                <th>Program Studi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="dosenTableBody">
              <tr><td colspan="6" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-muted"></i> Memuat data dosen...</td></tr>
            </tbody>
          </table>
        </div>
        <div class="pagination-footer">
          <span id="dosenPaginationInfo">Menampilkan data...</span>
          <div class="pagination-controls" id="dosenPaginationButtons"></div>
        </div>
      </div>
    </section>

    <!-- ============================================== -->
    <!-- TAB 3: PENELITIAN DOSEN PNL -->
    <!-- ============================================== -->
    <section id="tab-penelitian" class="tab-content">
      <div class="panel-header">
        <div>
          <h2><i class="fa-solid fa-microscope text-accent"></i> Penelitian Dosen PNL</h2>
          <p>Rekam jejak riset, hibah, dan karya ilmiah dosen Politeknik Negeri Lhokseumawe</p>
        </div>
        <div class="panel-actions">
          <a href="api.php?action=export&type=penelitian&format=excel" class="btn btn-secondary" title="Download Excel">
            <i class="fa-solid fa-file-excel text-success"></i> Download Excel
          </a>
        </div>
      </div>

      <div class="filter-box">
        <div class="filter-group">
          <label>Cari Judul Penelitian</label>
          <div class="search-input-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="penelitianSearchInput" placeholder="Ketik kata kunci judul riset...">
          </div>
        </div>
        <div class="filter-group" style="min-width: 250px;">
          <label>Pilih Dosen Peneliti PNL</label>
          <select id="penelitianDosenSelect" placeholder="Ketik nama dosen PNL..."></select>
        </div>
        <div class="filter-group">
          <label>Tahun Riset</label>
          <select id="penelitianTahunFilter">
            <option value="">-- Semua Tahun --</option>
          </select>
        </div>
        <div class="filter-group btn-align">
          <button class="btn btn-primary" onclick="loadPenelitian()">
            <i class="fa-solid fa-filter"></i> Cari Riset
          </button>
        </div>
      </div>

      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th width="60">No</th>
                <th width="90">Tahun</th>
                <th>Judul Kegiatan & Penelitian</th>
                <th>Dosen Peneliti</th>
                <th>NIDN</th>
                <th>Jenis Kegiatan</th>
              </tr>
            </thead>
            <tbody id="penelitianTableBody">
              <tr><td colspan="6" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-muted"></i> Memuat data penelitian...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ============================================== -->
    <!-- TAB 4: STATISTIK MAHASISWA PRODI DI ACEH -->
    <!-- ============================================== -->
    <section id="tab-prodi" class="tab-content">
      <div class="panel-header">
        <div>
          <h2><i class="fa-solid fa-chart-column text-accent"></i> Mahasiswa per Program Studi</h2>
          <p>Sebaran daya tampung, rasio dosen:mahasiswa, dan jenjang prodi di Aceh</p>
        </div>
        <div class="panel-actions">
          <a href="api.php?action=export&type=prodi&format=excel" class="btn btn-secondary" title="Download Excel">
            <i class="fa-solid fa-file-excel text-success"></i> Download Excel
          </a>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="charts-row">
        <div class="chart-card">
          <div class="chart-header">
            <h3><i class="fa-solid fa-trophy"></i> Top 10 Program Studi Terpadat di Aceh</h3>
          </div>
          <div class="chart-body">
            <canvas id="topProdiChart"></canvas>
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-header">
            <h3><i class="fa-solid fa-pie-chart"></i> Distribusi Mahasiswa per Jenjang</h3>
          </div>
          <div class="chart-body">
            <canvas id="jenjangChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Filter Prodi -->
      <div class="filter-box mt-4">
        <div class="filter-group" style="min-width: 280px;">
          <label>Filter Kampus di Aceh (120+ Kampus)</label>
          <select id="prodiPtFilter" placeholder="Ketik untuk cari kampus di Aceh..."></select>
        </div>
        <div class="filter-group">
          <label>Jenjang</label>
          <select id="prodiJenjangFilter">
            <option value="">-- Semua Jenjang --</option>
            <option value="D3">D3 (Diploma 3)</option>
            <option value="D4">D4 (Sarjana Terapan)</option>
            <option value="S1">S1 (Sarjana)</option>
            <option value="S2">S2 (Magister)</option>
            <option value="S3">S3 (Doktor)</option>
            <option value="Profesi">Profesi</option>
            <option value="Sp-1">Spesialis (Sp-1)</option>
          </select>
        </div>
        <div class="filter-group btn-align">
          <button class="btn btn-primary" onclick="loadProdiStats()">
            <i class="fa-solid fa-filter"></i> Filter Prodi
          </button>
        </div>
      </div>

      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th width="60">No</th>
                <th>Program Studi</th>
                <th>Jenjang</th>
                <th>Perguruan Tinggi</th>
                <th>Akreditasi</th>
                <th>Jumlah Mahasiswa</th>
                <th>Jumlah Dosen</th>
                <th>Rasio Dosen:Mhs</th>
                <th width="140">Aksi</th>
              </tr>
            </thead>
            <tbody id="prodiTableBody">
              <tr><td colspan="9" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-muted"></i> Memuat data statistik prodi...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ============================================== -->
    <!-- TAB 5: RIWAYAT PENDIDIKAN DOSEN PNL -->
    <!-- ============================================== -->
    <section id="tab-pendidikan" class="tab-content">
      <div class="panel-header">
        <div>
          <h2><i class="fa-solid fa-award text-accent"></i> Riwayat Pendidikan Dosen PNL</h2>
          <p>Kualifikasi akademik dan almamater perguruan tinggi kelulusan dosen PNL</p>
        </div>
        <div class="panel-actions">
          <a href="api.php?action=export&type=pendidikan&format=excel" class="btn btn-secondary" title="Download Excel">
            <i class="fa-solid fa-file-excel text-success"></i> Download Excel
          </a>
        </div>
      </div>

      <!-- Quick Summary Cards -->
      <div class="summary-cards-row" id="pendidikanSummaryCards">
        <!-- populated via js -->
      </div>

      <div class="filter-box">
        <div class="filter-group" style="min-width: 250px;">
          <label>Pilih Dosen PNL</label>
          <select id="pendidikanDosenSelect" placeholder="Ketik nama dosen PNL..."></select>
        </div>
        <div class="filter-group">
          <label>Cari Almamater / Gelar</label>
          <div class="search-input-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="pendidikanSearchInput" placeholder="Ketik almamater / gelar...">
          </div>
        </div>
        <div class="filter-group">
          <label>Jenjang Kelulusan</label>
          <select id="pendidikanJenjangFilter">
            <option value="">-- Semua Jenjang --</option>
            <option value="S3">S3 (Doktor)</option>
            <option value="S2">S2 (Magister)</option>
            <option value="S1">S1 (Sarjana)</option>
          </select>
        </div>
        <div class="filter-group btn-align">
          <button class="btn btn-primary" onclick="loadPendidikan()">
            <i class="fa-solid fa-filter"></i> Filter
          </button>
        </div>
      </div>

      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th width="60">No</th>
                <th>Nama Dosen PNL</th>
                <th>NIDN</th>
                <th>Jenjang</th>
                <th>Gelar Akademik</th>
                <th>Almamater Perguruan Tinggi</th>
                <th>Tahun Lulus</th>
              </tr>
            </thead>
            <tbody id="pendidikanTableBody">
              <tr><td colspan="7" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-muted"></i> Memuat riwayat pendidikan...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ============================================== -->
    <!-- TAB 6: DAFTAR KAMPUS ACEH -->
    <!-- ============================================== -->
    <section id="tab-kampus" class="tab-content">
      <div class="panel-header">
        <div>
          <h2><i class="fa-solid fa-building-columns text-accent"></i> Perguruan Tinggi di Provinsi Aceh</h2>
          <p>Direktori perguruan tinggi negeri, swasta, dan keagamaan di Provinsi Aceh</p>
        </div>
        <div class="panel-actions">
          <a href="api.php?action=export&type=kampus&format=excel" class="btn btn-secondary" title="Download Excel">
            <i class="fa-solid fa-file-excel text-success"></i> Download Excel
          </a>
        </div>
      </div>

      <div class="filter-box">
        <div class="filter-group">
          <label>Cari Nama / Kode Kampus</label>
          <input type="text" id="kampusSearchInput" placeholder="Ketik nama kampus...">
        </div>
        <div class="filter-group">
          <label>Kabupaten / Kota</label>
          <select id="kampusKabFilter">
            <option value="">-- Semua Wilayah --</option>
          </select>
        </div>
        <div class="filter-group btn-align">
          <button class="btn btn-primary" onclick="loadKampus()">
            <i class="fa-solid fa-filter"></i> Filter Kampus
          </button>
        </div>
      </div>

      <div class="table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th width="60">No</th>
                <th>Kode</th>
                <th>Nama Perguruan Tinggi</th>
                <th>Wilayah (Kab/Kota)</th>
                <th>Kelompok</th>
                <th>Pembina</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="kampusTableBody">
              <tr><td colspan="7" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-muted"></i> Memuat kampus Aceh...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

  </main>

  <!-- Modal Detail Mahasiswa per Program Studi -->
  <div id="prodiDetailModal" class="pddikti-custom-modal" style="display: none;" onclick="handleProdiModalBackdropClick(event)">
    <div class="pddikti-modal-card" onclick="event.stopPropagation()">
      <div class="modal-header">
        <div>
          <h3 id="modalProdiTitle"><i class="fa-solid fa-graduation-cap text-accent"></i> Detail Mahasiswa Prodi</h3>
          <p id="modalProdiSubtitle">Memuat data perguruan tinggi dan program studi...</p>
        </div>
        <button class="modal-close-btn" onclick="closeProdiDetailModal()" title="Tutup Modal">&times;</button>
      </div>

      <div class="modal-body">
        <!-- Section 1: Ringkasan Jumlah per Angkatan -->
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
            <label style="font-size: 0.8125rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">
              <i class="fa-solid fa-chart-simple text-accent"></i> Jumlah Mahasiswa per Angkatan
            </label>
            <span id="modalTotalBadge" class="badge badge-blue">Total: 0 Mahasiswa</span>
          </div>
          <div id="modalAngkatanCards" class="angkatan-grid">
            <!-- populated via JS -->
          </div>
        </div>

        <!-- Section 2: Toolbar Filter & Search -->
        <div class="modal-toolbar">
          <div class="modal-toolbar-filters">
            <div class="search-input-wrapper" style="max-width: 260px; min-width: 200px;">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" id="modalStudentSearch" placeholder="Cari nama atau NIM..." oninput="filterModalStudents()">
            </div>
            <select id="modalFilterAngkatan" onchange="filterModalStudents()" style="padding: 0.4rem 0.6rem; border-radius: 6px; border: 1px solid var(--border-subtle); font-size: 0.8125rem;">
              <option value="">-- Semua Angkatan --</option>
            </select>
            <select id="modalFilterStatus" onchange="filterModalStudents()" style="padding: 0.4rem 0.6rem; border-radius: 6px; border: 1px solid var(--border-subtle); font-size: 0.8125rem;">
              <option value="">-- Semua Status --</option>
              <option value="Aktif">Aktif</option>
              <option value="Lulus">Lulus</option>
              <option value="Non-Aktif">Non-Aktif</option>
              <option value="Cuti">Cuti</option>
              <option value="Mengundurkan Diri">Mengundurkan Diri</option>
            </select>
          </div>
          <div class="modal-toolbar-actions">
            <button type="button" class="btn btn-outline" id="btnModalSync" onclick="syncModalProdiStatus()" title="Tarik / Sinkronkan status mahasiswa prodi ini langsung ke PDDIKTI">
              <i class="fa-solid fa-arrows-rotate"></i> Sinkron PDDIKTI
            </button>
            <button type="button" class="btn btn-secondary" onclick="exportModalProdiExcel()" title="Download data mahasiswa prodi ini ke format Excel (.xls)">
              <i class="fa-solid fa-file-excel text-success"></i> Excel
            </button>
          </div>
        </div>

        <!-- Section 3: Daftar Mahasiswa (Siapa Aja) -->
        <div class="table-card" style="border: 1px solid var(--border-subtle); margin-top: 0;">
          <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
            <table class="data-table">
              <thead>
                <tr>
                  <th width="45">No</th>
                  <th>NIM</th>
                  <th>Nama Mahasiswa</th>
                  <th>Angkatan</th>
                  <th>Status PDDIKTI</th>
                  <th>Tanggal Masuk</th>
                </tr>
              </thead>
              <tbody id="modalStudentsTableBody">
                <tr><td colspan="6" class="text-center py-4 text-muted">Memuat data mahasiswa...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <span id="modalFooterCount" style="font-size: 0.8125rem; color: var(--text-secondary);">
          Menampilkan 0 mahasiswa
        </span>
        <button class="btn btn-outline" onclick="closeProdiDetailModal()">Tutup</button>
      </div>
    </div>
  </div>

  <!-- Live Search Modal -->
  <div class="modal" id="liveSearchModal">
    <div class="modal-backdrop" onclick="closeLiveSearchModal()"></div>
    <div class="modal-dialog">
      <div class="modal-header">
        <h3><i class="fa-solid fa-bolt text-accent"></i> Live Search Langsung ke PDDIKTI Kemdikbud</h3>
        <button class="btn-close" onclick="closeLiveSearchModal()">&times;</button>
      </div>
      <div class="modal-body">
        <p class="text-muted">Cari mahasiswa atau dosen langsung ke server PDDIKTI dan otomatis tersimpan ke database lokal.</p>
        <div class="search-category-toggle">
          <label><input type="radio" name="liveType" value="mahasiswa" checked> Mahasiswa</label>
          <label><input type="radio" name="liveType" value="dosen"> Dosen</label>
        </div>
        <div class="input-with-button">
          <input type="text" id="liveSearchQuery" placeholder="Masukkan nama mahasiswa/dosen atau kata kunci...">
          <button class="btn btn-primary" onclick="performLiveSearch()">
            <i class="fa-solid fa-magnifying-glass"></i> Cari Sekarang
          </button>
        </div>
        <div id="liveSearchResults" class="mt-4"></div>
      </div>
    </div>
  </div>

  <footer class="app-footer">
    <div class="footer-container">
      <p>&copy; <?= date('Y') ?> PDDIKTI Explorer &bull; Pangkalan Data Pendidikan Tinggi</p>
    </div>
  </footer>

  <script src="js/app.js?v=<?= time() ?>"></script>
</body>
</html>
