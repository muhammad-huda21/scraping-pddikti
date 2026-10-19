// ==========================================================================
// PDDIKTI Aceh & PNL Explorer - Enhanced UI Controller with Searchable Select
// ==========================================================================

let currentMhsPage = 1;
let currentDosenPage = 1;
let topProdiChartInstance = null;
let jenjangChartInstance = null;

// TomSelect instances
let tsMhsPt = null;
let tsDosenPt = null;
let tsProdiPt = null;
let tsPenelitianDosen = null;
let tsPendidikanDosen = null;

// Cache data kampus dan dosen PNL
let allKampusAceh = [];
let allDosenPnl = [];

document.addEventListener("DOMContentLoaded", async () => {
  setupTabs();
  loadDashboardStats();
  
  // Inisialisasi dropdown kampus & dosen PNL terlebih dahulu
  await initSearchableDropdowns();
  
  // Event listener tombol Enter pada kotak pencarian filter
  document.getElementById("mhsSearchInput")?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      loadMahasiswa(1);
    }
  });
  document.getElementById("dosenSearchInput")?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      loadDosen(1);
    }
  });
  document.getElementById("penelitianSearchInput")?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      loadPenelitian();
    }
  });

  // Muat data awal untuk masing-masing tab
  loadMahasiswa(1);
  loadDosen(1);
  loadPenelitian();
  loadProdiStats();
  loadPendidikan();
  loadKampus();
});

// ==========================================
// TABS NAVIGATION
// ==========================================
function setupTabs() {
  const tabButtons = document.querySelectorAll(".tab-btn");
  tabButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      tabButtons.forEach(b => b.classList.remove("active"));
      document.querySelectorAll(".tab-content").forEach(tc => tc.classList.remove("active"));

      btn.classList.add("active");
      const targetId = btn.getAttribute("data-tab");
      const targetContent = document.getElementById(targetId);
      if (targetContent) {
        targetContent.classList.add("active");
      }
    });
  });
}

// ==========================================
// INISIALISASI SEARCHABLE SELECT (122 KAMPUS & DOSEN PNL)
// ==========================================
async function initSearchableDropdowns() {
  try {
    // 1. Ambil 122 seluruh kampus di Aceh
    const resKampus = await fetch("api.php?action=kampus&limit=300");
    const jsonKampus = await resKampus.json();
    if (jsonKampus.status === "success" && jsonKampus.data) {
      allKampusAceh = jsonKampus.data;
    }

    // 2. Ambil seluruh dosen PNL
    const resDosenPnl = await fetch("api.php?action=dosen_pnl_options");
    const jsonDosenPnl = await resDosenPnl.json();
    if (jsonDosenPnl.status === "success" && jsonDosenPnl.data) {
      allDosenPnl = jsonDosenPnl.data;
    }

    // Siapkan opsi kampus
    const kampusOptions = [
      { value: "", text: "-- Semua Kampus di Aceh (120+ Kampus) --" },
      ...allKampusAceh.map(k => ({
        value: k.nama,
        text: `${k.nama} (${k.kab_kota || 'Aceh'})`
      }))
    ];

    // Siapkan opsi dosen PNL
    const dosenPnlOptions = [
      { value: "", text: "-- Semua Dosen PNL --" },
      ...allDosenPnl.map(d => ({
        value: d.dosen_nama,
        text: `${d.dosen_nama} ${d.nidn ? `(NIDN: ${d.nidn})` : ''}`
      }))
    ];

    // Inisialisasi TomSelect pada Tab 1 (Mahasiswa)
    const selectMhs = document.getElementById("mhsPtFilter");
    if (selectMhs) {
      tsMhsPt = new TomSelect(selectMhs, {
        options: kampusOptions,
        valueField: "value",
        labelField: "text",
        searchField: ["text"],
        placeholder: "Ketik untuk mencari 120+ kampus di Aceh...",
        maxOptions: 250,
        create: false
      });
    }

    // Inisialisasi TomSelect pada Tab 2 (Dosen)
    const selectDosen = document.getElementById("dosenPtFilter");
    if (selectDosen) {
      tsDosenPt = new TomSelect(selectDosen, {
        options: kampusOptions,
        valueField: "value",
        labelField: "text",
        searchField: ["text"],
        placeholder: "Ketik untuk mencari 120+ kampus di Aceh...",
        maxOptions: 250,
        create: false
      });
    }

    // Inisialisasi TomSelect pada Tab 4 (Prodi)
    const selectProdi = document.getElementById("prodiPtFilter");
    if (selectProdi) {
      tsProdiPt = new TomSelect(selectProdi, {
        options: kampusOptions,
        valueField: "value",
        labelField: "text",
        searchField: ["text"],
        placeholder: "Ketik untuk mencari 120+ kampus di Aceh...",
        maxOptions: 250,
        create: false
      });
    }

    // Inisialisasi TomSelect pada Tab 3 (Penelitian Dosen PNL)
    const selectPenelitianDosen = document.getElementById("penelitianDosenSelect");
    if (selectPenelitianDosen) {
      tsPenelitianDosen = new TomSelect(selectPenelitianDosen, {
        options: dosenPnlOptions,
        valueField: "value",
        labelField: "text",
        searchField: ["text"],
        placeholder: "Ketik nama dosen PNL...",
        maxOptions: 200,
        create: false
      });
    }

    // Inisialisasi TomSelect pada Tab 5 (Riwayat Pendidikan Dosen PNL)
    const selectPendidikanDosen = document.getElementById("pendidikanDosenSelect");
    if (selectPendidikanDosen) {
      tsPendidikanDosen = new TomSelect(selectPendidikanDosen, {
        options: dosenPnlOptions,
        valueField: "value",
        labelField: "text",
        searchField: ["text"],
        placeholder: "Ketik nama dosen PNL...",
        maxOptions: 200,
        create: false
      });
    }

  } catch (err) {
    console.error("Gagal menginisialisasi searchable dropdown:", err);
  }
}

// ==========================================
// 1. DASHBOARD OVERVIEW STATS
// ==========================================
async function loadDashboardStats() {
  try {
    const res = await fetch("api.php?action=stats");
    const json = await res.json();
    if (json.status === "success") {
      const d = json.data;
      document.getElementById("statKampus").innerText = d.total_kampus.toLocaleString();
      document.getElementById("statProdi").innerText = d.total_prodi.toLocaleString();
      document.getElementById("statMhs").innerText = d.total_mahasiswa_terdata.toLocaleString();
      document.getElementById("statDosen").innerText = d.total_dosen.toLocaleString();
      document.getElementById("statPenelitian").innerText = d.total_penelitian_pnl.toLocaleString();
    }
  } catch (err) {
    console.error("Error loading stats:", err);
  }
}

// ==========================================
// 2. KEBUTUHAN 1: MAHASISWA KAMPUS DI ACEH
// ==========================================
async function loadMahasiswa(page = 1) {
  currentMhsPage = page;
  const q = document.getElementById("mhsSearchInput").value.trim();
  const pt = tsMhsPt ? tsMhsPt.getValue() : (document.getElementById("mhsPtFilter")?.value || "");
  const angSelect = document.getElementById("mhsAngkatanFilter");
  const angkatan = angSelect ? angSelect.value : "";
  const tbody = document.getElementById("mhsTableBody");
  tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-muted"></i> Memuat data mahasiswa...</td></tr>`;

  try {
    const params = new URLSearchParams({ action: "mahasiswa", page, limit: 30 });
    if (q) params.append("q", q);
    if (pt) params.append("pt", pt);
    if (angkatan) params.append("angkatan", angkatan);

    const res = await fetch(`api.php?${params.toString()}`);
    const json = await res.json();

    // Update dynamic options di dropdown angkatan jika tersedia
    if (json.angkatan_list && json.angkatan_list.length > 0 && angSelect && angSelect.children.length <= 1) {
      const curVal = angSelect.value;
      let optHtml = '<option value="">Semua Angkatan</option>';
      json.angkatan_list.forEach(y => {
        optHtml += `<option value="${y}" ${curVal === String(y) ? 'selected' : ''}>${y}</option>`;
      });
      angSelect.innerHTML = optHtml;
    }

    if (!json.data || json.data.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <div class="empty-state-icon"><i class="fa-solid fa-user-slash"></i></div>
              <div class="empty-state-title">Tidak ada mahasiswa yang cocok</div>
              <div class="empty-state-desc">Belum ada data mahasiswa untuk filter ini atau gunakan tombol <b>Live Search PDDIKTI</b> di kanan atas.</div>
            </div>
          </td>
        </tr>
      `;
      document.getElementById("mhsPaginationInfo").innerText = "0 mahasiswa ditemukan";
      document.getElementById("mhsPaginationButtons").innerHTML = "";
      return;
    }

    let rowsHtml = "";
    json.data.forEach((m, idx) => {
      const no = (page - 1) * 30 + (idx + 1);
      const angkatanBadge = (m.angkatan && m.angkatan !== '-') 
        ? `<span class="badge badge-blue"><b>${escapeHtml(m.angkatan)}</b></span>`
        : `<span class="text-muted">-</span>`;

      let statusBadgeClass = 'badge-green';
      const stLower = (m.status_saat_ini || '').toLowerCase();
      if (stLower.includes('lulus')) {
        statusBadgeClass = 'badge-blue';
      } else if (stLower.includes('cuti') || stLower.includes('non')) {
        statusBadgeClass = 'badge-amber';
      } else if (stLower.includes('undur') || stLower.includes('keluar') || stLower.includes('meninggal')) {
        statusBadgeClass = 'badge-red';
      }

      rowsHtml += `
        <tr>
          <td>${no}</td>
          <td><b>${escapeHtml(m.nama)}</b></td>
          <td><code>${escapeHtml(m.nim || '-')}</code></td>
          <td>${angkatanBadge}</td>
          <td>${escapeHtml(m.nama_pt || '-')}</td>
          <td>${escapeHtml(m.nama_prodi || '-')}</td>
          <td><span class="badge ${statusBadgeClass}">${escapeHtml(m.status_saat_ini || 'Aktif')}</span></td>
        </tr>
      `;
    });
    tbody.innerHTML = rowsHtml;

    const totalPages = Math.ceil(json.total / 30);
    document.getElementById("mhsPaginationInfo").innerText = `Menampilkan halaman ${page} dari ${totalPages || 1} (Total ${json.total.toLocaleString('id-ID')} mahasiswa unik)`;
    renderPaginationButtons("mhsPaginationButtons", page, totalPages, loadMahasiswa);

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Gagal memuat data: ${escapeHtml(err.message)}</td></tr>`;
  }
}

function resetMhsFilter() {
  document.getElementById("mhsSearchInput").value = "";
  const angSelect = document.getElementById("mhsAngkatanFilter");
  if (angSelect) angSelect.value = "";
  if (tsMhsPt) tsMhsPt.clear();
  loadMahasiswa(1);
}

// Sinkronkan status mahasiswa terbaru langsung dari PDDIKTI untuk kampus/prodi yang sedang dipilih
async function syncCurrentStatus() {
  const btn = document.getElementById("btnSyncStatus");
  const q = document.getElementById("mhsSearchInput")?.value.trim() || "";
  const pt = tsMhsPt ? tsMhsPt.getValue() : (document.getElementById("mhsPtFilter")?.value || "");

  if (!pt && !q) {
    alert("Silakan pilih Kampus atau ketik Nama Prodi / Mahasiswa terlebih dahulu untuk sinkronisasi status.");
    return;
  }

  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Mensinkronkan...`;

  try {
    const params = new URLSearchParams({
      action: "sync_status_prodi"
    });
    if (pt) params.append("pt", pt);
    if (q) params.append("prodi", q);

    const res = await fetch(`api.php?${params.toString()}`);
    const json = await res.json();

    if (json.status === "success") {
      let rincianText = "";
      if (json.rincian_status) {
        rincianText = Object.entries(json.rincian_status)
          .map(([k, v]) => `${k}: ${v}`)
          .join(", ");
      }
      alert(`Sinkronisasi status PDDIKTI selesai!\n\nTotal diperiksa: ${json.total_diperiksa}\nBerhasil diperbarui: ${json.total_berhasil}\n${rincianText ? "Rincian: " + rincianText : ""}`);
      loadMahasiswa(currentMhsPage);
    } else {
      alert(json.message || "Gagal melakukan sinkronisasi status.");
    }
  } catch (err) {
    console.error("Sync error:", err);
    alert("Terjadi kesalahan saat menghubungi server.");
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
}

// Download / Export Excel atau CSV khusus Mahasiswa dengan Filter Aktif
function exportMahasiswa(format = "excel") {
  const q = document.getElementById("mhsSearchInput")?.value.trim() || "";
  const pt = tsMhsPt ? tsMhsPt.getValue() : (document.getElementById("mhsPtFilter")?.value || "");
  const angkatan = document.getElementById("mhsAngkatanFilter")?.value || "";

  const params = new URLSearchParams({
    action: "export",
    type: "mahasiswa",
    format: format
  });
  if (q) params.append("q", q);
  if (pt) params.append("pt", pt);
  if (angkatan) params.append("angkatan", angkatan);

  window.location.href = `api.php?${params.toString()}`;
}

// Download / Export Excel atau CSV khusus Dosen dengan Filter Aktif
function exportDosen(format = "excel") {
  const q = document.getElementById("dosenSearchInput")?.value.trim() || "";
  const pt = tsDosenPt ? tsDosenPt.getValue() : (document.getElementById("dosenPtFilter")?.value || "");

  const params = new URLSearchParams({
    action: "export",
    type: "dosen",
    format: format
  });
  if (q) params.append("q", q);
  if (pt) params.append("pt", pt);

  window.location.href = `api.php?${params.toString()}`;
}

// ==========================================
// 3. KEBUTUHAN 2: DOSEN KAMPUS DI ACEH
// ==========================================
async function loadDosen(page = 1) {
  currentDosenPage = page;
  const q = document.getElementById("dosenSearchInput").value.trim();
  const pt = tsDosenPt ? tsDosenPt.getValue() : (document.getElementById("dosenPtFilter")?.value || "");
  const tbody = document.getElementById("dosenTableBody");
  tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-muted"></i> Memuat data dosen...</td></tr>`;

  try {
    const params = new URLSearchParams({ action: "dosen", page, limit: 30 });
    if (q) params.append("q", q);
    if (pt) params.append("pt", pt);

    const res = await fetch(`api.php?${params.toString()}`);
    const json = await res.json();

    if (!json.data || json.data.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6">
            <div class="empty-state">
              <div class="empty-state-icon"><i class="fa-solid fa-user-slash"></i></div>
              <div class="empty-state-title">Tidak ada data dosen yang sesuai</div>
              <div class="empty-state-desc">Pilih kampus lain atau gunakan tombol Live Search PDDIKTI.</div>
            </div>
          </td>
        </tr>
      `;
      document.getElementById("dosenPaginationInfo").innerText = "0 dosen ditemukan";
      document.getElementById("dosenPaginationButtons").innerHTML = "";
      return;
    }

    let rowsHtml = "";
    json.data.forEach((d, idx) => {
      const no = (page - 1) * 30 + (idx + 1);
      const isPnl = (d.nama_pt || '').toLowerCase().includes('lhokseumawe');
      rowsHtml += `
        <tr>
          <td>${no}</td>
          <td><b>${escapeHtml(d.nama)}</b></td>
          <td><code>${escapeHtml(d.nidn || d.nuptk || '-')}</code></td>
          <td>
            ${escapeHtml(d.nama_pt || '-')}
            ${isPnl ? '<span class="badge badge-cyan" style="margin-left:4px;">PNL</span>' : ''}
          </td>
          <td>${escapeHtml(d.nama_prodi || '-')}</td>
          <td>
            <button class="btn btn-secondary" style="padding: 0.25rem 0.55rem; font-size: 0.75rem;" onclick="viewDosenRiwayat('${escapeHtml(d.nama)}')">
              <i class="fa-solid fa-file-lines"></i> Riset & Profil
            </button>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = rowsHtml;

    const totalPages = Math.ceil(json.total / 30);
    document.getElementById("dosenPaginationInfo").innerText = `Menampilkan halaman ${page} dari ${totalPages || 1} (Total ${json.total} dosen)`;
    renderPaginationButtons("dosenPaginationButtons", page, totalPages, loadDosen);

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Gagal memuat dosen: ${escapeHtml(err.message)}</td></tr>`;
  }
}

function resetDosenFilter() {
  document.getElementById("dosenSearchInput").value = "";
  if (tsDosenPt) tsDosenPt.clear();
  loadDosen(1);
}

function viewDosenRiwayat(namaDosen) {
  document.querySelector('[data-tab="tab-penelitian"]').click();
  if (tsPenelitianDosen) {
    tsPenelitianDosen.setValue(namaDosen);
  }
  loadPenelitian();
}

// ==========================================
// 4. KEBUTUHAN 3: PENELITIAN DOSEN PNL
// ==========================================
async function loadPenelitian() {
  const q = document.getElementById("penelitianSearchInput").value.trim();
  const dosen = tsPenelitianDosen ? tsPenelitianDosen.getValue() : "";
  const tahun = document.getElementById("penelitianTahunFilter").value;
  const tbody = document.getElementById("penelitianTableBody");
  tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-muted"></i> Memuat penelitian dosen PNL...</td></tr>`;

  try {
    const params = new URLSearchParams({ action: "penelitian", limit: 100 });
    if (q) params.append("q", q);
    if (dosen) params.append("dosen", dosen);
    if (tahun) params.append("tahun", tahun);

    const res = await fetch(`api.php?${params.toString()}`);
    const json = await res.json();

    const tahunSelect = document.getElementById("penelitianTahunFilter");
    if (tahunSelect.children.length <= 1 && json.tahun_list) {
      json.tahun_list.forEach(t => {
        const opt = document.createElement("option");
        opt.value = t;
        opt.innerText = `Tahun ${t}`;
        tahunSelect.appendChild(opt);
      });
    }

    if (!json.data || json.data.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6">
            <div class="empty-state">
              <div class="empty-state-icon"><i class="fa-solid fa-folder-open"></i></div>
              <div class="empty-state-title">Tidak ada penelitian ditemukan</div>
              <div class="empty-state-desc">Belum ada judul penelitian yang cocok dengan filter yang dipilih.</div>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    let rowsHtml = "";
    json.data.forEach((p, idx) => {
      rowsHtml += `
        <tr>
          <td>${idx + 1}</td>
          <td><span class="badge badge-blue">${p.tahun_kegiatan || '-'}</span></td>
          <td><b>${escapeHtml(p.judul_kegiatan)}</b></td>
          <td>${escapeHtml(p.dosen_nama)}</td>
          <td><code>${escapeHtml(p.nidn || '-')}</code></td>
          <td><span class="badge badge-purple">${escapeHtml(p.jenis_kegiatan || 'Penelitian')}</span></td>
        </tr>
      `;
    });
    tbody.innerHTML = rowsHtml;

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Gagal memuat penelitian: ${escapeHtml(err.message)}</td></tr>`;
  }
}

// ==========================================
// 5. KEBUTUHAN 4: STATISTIK MAHASISWA PRODI DI ACEH
// ==========================================
async function loadProdiStats() {
  const pt = tsProdiPt ? tsProdiPt.getValue() : (document.getElementById("prodiPtFilter")?.value || "");
  const jenjang = document.getElementById("prodiJenjangFilter").value;
  const tbody = document.getElementById("prodiTableBody");
  tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-muted"></i> Memuat data statistik prodi...</td></tr>`;

  try {
    const params = new URLSearchParams({ action: "prodi", limit: 100 });
    if (pt) params.append("pt", pt);
    if (jenjang) params.append("jenjang", jenjang);

    const res = await fetch(`api.php?${params.toString()}`);
    const json = await res.json();

    if (!json.data || json.data.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8">
            <div class="empty-state">
              <div class="empty-state-icon"><i class="fa-solid fa-chart-simple"></i></div>
              <div class="empty-state-title">Tidak ada data program studi</div>
              <div class="empty-state-desc">Silakan pilih kampus atau jenjang lain.</div>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    let rowsHtml = "";
    json.data.forEach((p, idx) => {
      const jmlMhs = parseInt(p.jumlah_mahasiswa) || 0;
      rowsHtml += `
        <tr>
          <td>${idx + 1}</td>
          <td>
            <a href="javascript:void(0)" onclick="openProdiMahasiswaDetail('${escapeHtml(p.nama_pt)}', '${escapeHtml(p.nama_prodi)}', '${escapeHtml(p.jenjang_prodi || '')}')" class="prodi-title-link" title="Klik untuk lihat detail mahasiswa prodi ini">
              <b>${escapeHtml(p.nama_prodi)}</b>
            </a>
          </td>
          <td><span class="badge badge-blue">${escapeHtml(p.jenjang_prodi)}</span></td>
          <td>${escapeHtml(p.nama_pt)}</td>
          <td><span class="badge badge-green">${escapeHtml(p.akreditasi || '-')}</span></td>
          <td><b style="font-family:var(--font-mono); color:var(--accent-primary);">${jmlMhs.toLocaleString()}</b> mhs</td>
          <td>${p.jumlah_dosen || 0} dosen</td>
          <td><code>${escapeHtml(p.rasio || '-')}</code></td>
          <td>
            <button type="button" class="btn btn-sm btn-primary" onclick="openProdiMahasiswaDetail('${escapeHtml(p.nama_pt)}', '${escapeHtml(p.nama_prodi)}', '${escapeHtml(p.jenjang_prodi || '')}')" title="Buka statistik per angkatan dan daftar mahasiswa">
              <i class="fa-solid fa-users"></i> Detail
            </button>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = rowsHtml;

    renderCharts(json);

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Gagal memuat data prodi: ${escapeHtml(err.message)}</td></tr>`;
  }
}

function renderCharts(json) {
  const top10 = json.data.slice(0, 10);
  const labelsTop10 = top10.map(p => `${p.nama_prodi} (${p.jenjang_prodi})`);
  const dataTop10 = top10.map(p => parseInt(p.jumlah_mahasiswa) || 0);

  const ctxTop = document.getElementById("topProdiChart").getContext("2d");
  if (topProdiChartInstance) topProdiChartInstance.destroy();

  topProdiChartInstance = new Chart(ctxTop, {
    type: "bar",
    data: {
      labels: labelsTop10,
      datasets: [{
        label: "Jumlah Mahasiswa",
        data: dataTop10,
        backgroundColor: "#2563EB",
        borderColor: "#1E40AF",
        borderWidth: 1,
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        x: {
          ticks: { color: "#64748B", font: { size: 10 } },
          grid: { display: false }
        },
        y: {
          ticks: { color: "#64748B" },
          grid: { color: "#F1F5F9" }
        }
      }
    }
  });

  if (json.jenjang_summary) {
    const jenjangLabels = json.jenjang_summary.map(j => j.jenjang_prodi);
    const jenjangValues = json.jenjang_summary.map(j => parseInt(j.total_mhs) || 0);

    const ctxJenjang = document.getElementById("jenjangChart").getContext("2d");
    if (jenjangChartInstance) jenjangChartInstance.destroy();

    jenjangChartInstance = new Chart(ctxJenjang, {
      type: "doughnut",
      data: {
        labels: jenjangLabels,
        datasets: [{
          data: jenjangValues,
          backgroundColor: [
            "#1E40AF", "#0284C7", "#0D9488", "#D97706", "#7C3AED", "#64748B"
          ],
          borderWidth: 2,
          borderColor: "#FFFFFF"
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: { color: "#475569", boxWidth: 10, font: { size: 11 } }
          }
        }
      }
    });
  }
}

// ==========================================
// 6. KEBUTUHAN 5: RIWAYAT PENDIDIKAN DOSEN PNL
// ==========================================
async function loadPendidikan() {
  const dosen = tsPendidikanDosen ? tsPendidikanDosen.getValue() : "";
  const q = document.getElementById("pendidikanSearchInput").value.trim();
  const jenjang = document.getElementById("pendidikanJenjangFilter").value;
  const tbody = document.getElementById("pendidikanTableBody");
  tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-muted"></i> Memuat data kualifikasi dosen PNL...</td></tr>`;

  try {
    const params = new URLSearchParams({ action: "pendidikan", limit: 100 });
    if (dosen) params.append("dosen", dosen);
    if (q) params.append("q", q);
    if (jenjang) params.append("jenjang", jenjang);

    const res = await fetch(`api.php?${params.toString()}`);
    const json = await res.json();

    const summaryContainer = document.getElementById("pendidikanSummaryCards");
    let summaryHtml = "";
    if (json.jenjang_dist) {
      json.jenjang_dist.forEach(j => {
        summaryHtml += `
          <div class="summary-card">
            <div class="title">Jenjang Lulusan ${j.jenjang}</div>
            <div class="val">${j.count} Riwayat</div>
          </div>
        `;
      });
    }
    if (json.top_almamater && json.top_almamater.length > 0) {
      const topPt = json.top_almamater[0];
      summaryHtml += `
        <div class="summary-card">
          <div class="title">Almamater Terbanyak</div>
          <div class="val" style="font-size:1.05rem;">${escapeHtml(topPt.nama_pt_studi)} (${topPt.count})</div>
        </div>
      `;
    }
    summaryContainer.innerHTML = summaryHtml;

    if (!json.data || json.data.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <div class="empty-state-icon"><i class="fa-solid fa-graduation-cap"></i></div>
              <div class="empty-state-title">Tidak ada riwayat pendidikan</div>
              <div class="empty-state-desc">Belum ada riwayat pendidikan yang cocok dengan filter.</div>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    let rowsHtml = "";
    json.data.forEach((d, idx) => {
      let badgeClass = "badge-blue";
      if (d.jenjang === "S3") badgeClass = "badge-purple";
      if (d.jenjang === "S2") badgeClass = "badge-cyan";

      rowsHtml += `
        <tr>
          <td>${idx + 1}</td>
          <td><b>${escapeHtml(d.dosen_nama)}</b></td>
          <td><code>${escapeHtml(d.nidn || '-')}</code></td>
          <td><span class="badge ${badgeClass}">${escapeHtml(d.jenjang || '-')}</span></td>
          <td>${escapeHtml(d.gelar_akademik || d.singkatan_gelar || '-')}</td>
          <td>${escapeHtml(d.nama_pt_studi || '-')}</td>
          <td><span class="badge badge-amber">${d.tahun_lulus > 0 ? d.tahun_lulus : '-'}</span></td>
        </tr>
      `;
    });
    tbody.innerHTML = rowsHtml;

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Gagal memuat pendidikan: ${escapeHtml(err.message)}</td></tr>`;
  }
}

// ==========================================
// 7. TAB DAFTAR KAMPUS ACEH
// ==========================================
async function loadKampus() {
  const q = document.getElementById("kampusSearchInput").value.trim();
  const kab = document.getElementById("kampusKabFilter").value;
  const tbody = document.getElementById("kampusTableBody");
  tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-muted"></i> Memuat kampus di Aceh...</td></tr>`;

  try {
    const params = new URLSearchParams({ action: "kampus", limit: 200 });
    if (q) params.append("q", q);
    if (kab) params.append("kab_kota", kab);

    const res = await fetch(`api.php?${params.toString()}`);
    const json = await res.json();

    const kabSelect = document.getElementById("kampusKabFilter");
    if (kabSelect.children.length <= 1 && json.kab_kota_list) {
      json.kab_kota_list.forEach(k => {
        const opt = document.createElement("option");
        opt.value = k;
        opt.innerText = k;
        kabSelect.appendChild(opt);
      });
    }

    if (!json.data || json.data.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <div class="empty-state-icon"><i class="fa-solid fa-building-columns"></i></div>
              <div class="empty-state-title">Tidak ada kampus yang cocok</div>
              <div class="empty-state-desc">Sesuaikan kata kunci pencarian kampus atau pilih wilayah lain.</div>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    let rowsHtml = "";
    json.data.forEach((k, idx) => {
      const isPnl = (k.nama || '').toLowerCase().includes('lhokseumawe');
      rowsHtml += `
        <tr>
          <td>${idx + 1}</td>
          <td><code>${escapeHtml(k.kode || '-')}</code></td>
          <td>
            <b>${escapeHtml(k.nama)}</b>
            ${isPnl ? '<span class="badge badge-cyan" style="margin-left:6px;">Target Utama</span>' : ''}
          </td>
          <td>${escapeHtml(k.kab_kota || '-')}</td>
          <td><span class="badge badge-blue">${escapeHtml(k.kelompok || '-')}</span></td>
          <td>${escapeHtml(k.pembina || '-')}</td>
          <td>
            <button class="btn btn-secondary" style="padding: 0.25rem 0.55rem; font-size: 0.75rem;" onclick="filterProdiByKampus('${escapeHtml(k.nama)}')">
              Lihat Prodi
            </button>
          </td>
        </tr>
      `;
    });
    tbody.innerHTML = rowsHtml;

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Gagal memuat kampus: ${escapeHtml(err.message)}</td></tr>`;
  }
}

function filterProdiByKampus(namaKampus) {
  document.querySelector('[data-tab="tab-prodi"]').click();
  if (tsProdiPt) {
    tsProdiPt.setValue(namaKampus);
  }
  loadProdiStats();
}

// ==========================================
// 8. LIVE SEARCH MODAL (PDDIKTI DIRECT via PHP cURL)
// ==========================================
function openLiveSearchModal() {
  document.getElementById("liveSearchModal").classList.add("active");
  document.getElementById("liveSearchQuery").focus();
}

function closeLiveSearchModal() {
  document.getElementById("liveSearchModal").classList.remove("active");
}

async function performLiveSearch() {
  const queryInput = document.getElementById("liveSearchQuery");
  const query = queryInput.value.trim();
  const type = document.querySelector('input[name="liveType"]:checked').value;
  const resultsDiv = document.getElementById("liveSearchResults");
  const searchBtn = document.querySelector("#liveSearchModal .btn-primary");

  if (query.length < 2) {
    alert("Masukkan minimal 2 karakter kata kunci pencarian.");
    return;
  }

  searchBtn.disabled = true;
  searchBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Mencari...`;
  resultsDiv.innerHTML = `<div class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Menghubungi server PDDIKTI Kemdikbud...</div>`;

  try {
    const res = await fetch(`api.php?action=live_search&type=${type}&keyword=${encodeURIComponent(query)}`);
    const json = await res.json();

    if (!json.data || json.data.length === 0) {
      resultsDiv.innerHTML = `
        <div class="empty-state">
          <div class="empty-state-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
          <div class="empty-state-title">Tidak ada hasil ditemukan</div>
          <div class="empty-state-desc">Server PDDIKTI tidak menemukan data dengan kata kunci "<b>${escapeHtml(query)}</b>".</div>
        </div>
      `;
      return;
    }

    let outHtml = `<div style="max-height: 320px; overflow-y: auto;">
      <p style="margin-bottom:0.75rem; font-weight:600; color:var(--success-text); font-size:0.84rem;">
        Ditemukan ${json.data.length} hasil (Tersimpan otomatis ke database):
      </p>
      <table class="data-table">
        <thead>
          <tr>
            <th>Nama</th>
            <th>${type === 'mahasiswa' ? 'NIM' : 'NIDN'}</th>
            <th>Perguruan Tinggi</th>
            <th>Program Studi</th>
          </tr>
        </thead>
        <tbody>`;

    json.data.forEach(item => {
      outHtml += `
        <tr>
          <td><b>${escapeHtml(item.nama)}</b></td>
          <td><code>${escapeHtml(item.nim || item.nidn || '-')}</code></td>
          <td>${escapeHtml(item.nama_pt || '-')}</td>
          <td>${escapeHtml(item.nama_prodi || '-')}</td>
        </tr>
      `;
    });

    outHtml += `</tbody></table></div>`;
    resultsDiv.innerHTML = outHtml;

    loadDashboardStats();
    if (type === "mahasiswa") {
      const searchBox = document.getElementById("mhsSearchInput");
      if (searchBox) searchBox.value = query;
      if (tsMhsPt && tsMhsPt.getValue() && json.data.length > 0) {
        const firstPt = (json.data[0].nama_pt || '').toLowerCase();
        if (!firstPt.includes(tsMhsPt.getValue().toLowerCase())) {
          tsMhsPt.clear();
        }
      }
      loadMahasiswa(1);
    } else {
      const searchBox = document.getElementById("dosenSearchInput");
      if (searchBox) searchBox.value = query;
      if (tsDosenPt && tsDosenPt.getValue() && json.data.length > 0) {
        const firstPt = (json.data[0].nama_pt || '').toLowerCase();
        if (!firstPt.includes(tsDosenPt.getValue().toLowerCase())) {
          tsDosenPt.clear();
        }
      }
      loadDosen(1);
    }

  } catch (err) {
    resultsDiv.innerHTML = `<div class="text-danger py-4 text-center"><i class="fa-solid fa-triangle-exclamation"></i> Gagal melakukan live search: ${escapeHtml(err.message)}</div>`;
  } finally {
    searchBtn.disabled = false;
    searchBtn.innerHTML = `<i class="fa-solid fa-magnifying-glass"></i> Cari Sekarang`;
  }
}

// Helpers
function escapeHtml(str) {
  if (!str) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function renderPaginationButtons(containerId, currentPage, totalPages, onClickFn) {
  const container = document.getElementById(containerId);
  if (!container || totalPages <= 1) {
    if (container) container.innerHTML = "";
    return;
  }

  let html = "";
  if (currentPage > 1) {
    html += `<button class="btn btn-secondary" style="padding:0.25rem 0.55rem;" onclick="(${onClickFn.name})(${currentPage - 1})"><i class="fa-solid fa-chevron-left"></i></button>`;
  }

  const start = Math.max(1, currentPage - 2);
  const end = Math.min(totalPages, currentPage + 2);

  for (let i = start; i <= end; i++) {
    const activeClass = i === currentPage ? "btn-primary" : "btn-secondary";
    html += `<button class="btn ${activeClass}" style="padding:0.25rem 0.55rem;" onclick="(${onClickFn.name})(${i})">${i}</button>`;
  }

  if (currentPage < totalPages) {
    html += `<button class="btn btn-secondary" style="padding:0.25rem 0.55rem;" onclick="(${onClickFn.name})(${currentPage + 1})"><i class="fa-solid fa-chevron-right"></i></button>`;
  }

  container.innerHTML = html;
}

// ==========================================================================
// 8. DETAIL MAHASISWA PER PRODI (MODAL, ANGKATAN STATS & DAFTAR MAHASISWA)
// ==========================================================================
let currentModalPt = "";
let currentModalProdi = "";
let currentModalJenjang = "";
let currentModalAllStudents = [];
let currentModalActiveAngkatan = "";

async function openProdiMahasiswaDetail(pt, prodi, jenjang = "") {
  currentModalPt = pt;
  currentModalProdi = prodi;
  currentModalJenjang = jenjang;
  currentModalActiveAngkatan = "";

  const modal = document.getElementById("prodiDetailModal");
  if (!modal) return;

  // Set titles & reset state
  document.getElementById("modalProdiTitle").innerHTML = `<i class="fa-solid fa-graduation-cap text-accent"></i> ${escapeHtml(prodi)} <span class="badge badge-blue" style="font-size:0.75rem; vertical-align:middle; margin-left:0.5rem;">${escapeHtml(jenjang || 'Prodi')}</span>`;
  document.getElementById("modalProdiSubtitle").innerText = `${pt} • Pangkalan Data Pendidikan Tinggi`;
  document.getElementById("modalTotalBadge").innerText = "Memuat...";
  document.getElementById("modalAngkatanCards").innerHTML = `<div class="text-muted py-2" style="grid-column: 1/-1;"><i class="fa-solid fa-spinner fa-spin"></i> Menghitung jumlah mahasiswa per angkatan...</div>`;
  document.getElementById("modalStudentsTableBody").innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Mengambil data mahasiswa...</td></tr>`;
  document.getElementById("modalStudentSearch").value = "";
  document.getElementById("modalFilterStatus").value = "";
  document.getElementById("modalFooterCount").innerText = "Memuat mahasiswa...";

  modal.style.display = "flex";
  modal.classList.add("active");
  document.body.style.overflow = "hidden";

  await fetchModalProdiData();
}

async function fetchModalProdiData() {
  try {
    const params = new URLSearchParams({
      action: "prodi_detail_mahasiswa",
      pt: currentModalPt,
      prodi: currentModalProdi
    });

    const res = await fetch(`api.php?${params.toString()}`);
    const json = await res.json();

    if (json.status !== "success") {
      document.getElementById("modalStudentsTableBody").innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">${escapeHtml(json.message || "Gagal memuat data")}</td></tr>`;
      return;
    }

    currentModalAllStudents = json.students || [];
    document.getElementById("modalTotalBadge").innerText = `Total: ${(json.total_prodi_mhs || 0).toLocaleString()} Mahasiswa`;

    // Render Angkatan Cards & Options
    renderModalAngkatan(json.angkatan_stats || []);

    // Render Table
    filterModalStudents();

  } catch (err) {
    console.error("Error fetching prodi students:", err);
    document.getElementById("modalStudentsTableBody").innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Terjadi kesalahan: ${escapeHtml(err.message)}</td></tr>`;
  }
}

function renderModalAngkatan(stats) {
  const container = document.getElementById("modalAngkatanCards");
  const select = document.getElementById("modalFilterAngkatan");

  let selectHtml = `<option value="">-- Semua Angkatan --</option>`;
  let cardsHtml = "";

  // Card "Semua Angkatan"
  const totalAll = stats.reduce((acc, cur) => acc + cur.total, 0);
  const isAllActive = !currentModalActiveAngkatan;
  cardsHtml += `
    <div class="angkatan-card ${isAllActive ? 'active' : ''}" onclick="selectModalAngkatan('')">
      <div class="angkatan-card-year">Semua</div>
      <div class="angkatan-card-total">${totalAll}</div>
      <div class="angkatan-card-badges">
        <span class="badge badge-blue">Seluruhnya</span>
      </div>
    </div>
  `;

  stats.forEach(st => {
    selectHtml += `<option value="${escapeHtml(st.angkatan)}" ${currentModalActiveAngkatan === st.angkatan ? 'selected' : ''}>Angkatan ${escapeHtml(st.angkatan)} (${st.total})</option>`;
    
    const isActive = (currentModalActiveAngkatan === st.angkatan);
    cardsHtml += `
      <div class="angkatan-card ${isActive ? 'active' : ''}" onclick="selectModalAngkatan('${escapeHtml(st.angkatan)}')">
        <div class="angkatan-card-year">Angkatan ${escapeHtml(st.angkatan)}</div>
        <div class="angkatan-card-total">${st.total} <span style="font-size:0.75rem; font-weight:normal; color:var(--text-secondary);">mhs</span></div>
        <div class="angkatan-card-badges">
          ${st.aktif ? `<span class="badge badge-green">${st.aktif} Aktif</span>` : ''}
          ${st.lulus ? `<span class="badge badge-blue">${st.lulus} Lulus</span>` : ''}
          ${st.non_aktif ? `<span class="badge badge-amber">${st.non_aktif} Non-Aktif</span>` : ''}
          ${st.cuti ? `<span class="badge badge-amber">${st.cuti} Cuti</span>` : ''}
          ${st.lainnya ? `<span class="badge badge-red">${st.lainnya} Lain</span>` : ''}
        </div>
      </div>
    `;
  });

  container.innerHTML = cardsHtml;
  select.innerHTML = selectHtml;
}

function selectModalAngkatan(year) {
  currentModalActiveAngkatan = year;
  document.getElementById("modalFilterAngkatan").value = year;
  
  // Highlight active card
  document.querySelectorAll(".angkatan-card").forEach(el => {
    el.classList.remove("active");
  });
  
  filterModalStudents();
}

function filterModalStudents() {
  const q = document.getElementById("modalStudentSearch")?.value.trim().toLowerCase() || "";
  const angSelectVal = document.getElementById("modalFilterAngkatan")?.value || "";
  const statusFilter = document.getElementById("modalFilterStatus")?.value || "";

  // Update currentModalActiveAngkatan from dropdown if changed
  currentModalActiveAngkatan = angSelectVal;

  const filtered = currentModalAllStudents.filter(s => {
    if (angSelectVal && s.angkatan !== angSelectVal) return false;
    if (statusFilter && (s.status_saat_ini || 'Aktif').toLowerCase() !== statusFilter.toLowerCase()) return false;
    if (q) {
      const matchName = (s.nama || '').toLowerCase().includes(q);
      const matchNim = (s.nim || '').toLowerCase().includes(q);
      if (!matchName && !matchNim) return false;
    }
    return true;
  });

  const tbody = document.getElementById("modalStudentsTableBody");
  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa-solid fa-user-slash"></i> Tidak ada mahasiswa yang cocok dengan filter yang dipilih</td></tr>`;
    document.getElementById("modalFooterCount").innerText = `Menampilkan 0 dari ${currentModalAllStudents.length} mahasiswa`;
    return;
  }

  let html = "";
  filtered.forEach((m, idx) => {
    let badgeClass = 'badge-green';
    const stLower = (m.status_saat_ini || '').toLowerCase();
    if (stLower.includes('lulus')) badgeClass = 'badge-blue';
    else if (stLower.includes('cuti') || stLower.includes('non')) badgeClass = 'badge-amber';
    else if (stLower.includes('undur') || stLower.includes('keluar') || stLower.includes('meninggal')) badgeClass = 'badge-red';

    html += `
      <tr>
        <td>${idx + 1}</td>
        <td><code>${escapeHtml(m.nim || '-')}</code></td>
        <td><b>${escapeHtml(m.nama)}</b></td>
        <td><span class="badge badge-blue"><b>${escapeHtml(m.angkatan || '-')}</b></span></td>
        <td><span class="badge ${badgeClass}">${escapeHtml(m.status_saat_ini || 'Aktif')}</span></td>
        <td><span class="text-muted" style="font-size:0.8rem;">${escapeHtml(m.tanggal_masuk || '-')}</span></td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
  document.getElementById("modalFooterCount").innerText = `Menampilkan ${filtered.length} dari ${currentModalAllStudents.length} mahasiswa terdaftar`;
}

async function syncModalProdiStatus() {
  const btn = document.getElementById("btnModalSync");
  const origHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Mensinkronkan...`;

  try {
    const params = new URLSearchParams({
      action: "sync_status_prodi",
      pt: currentModalPt,
      prodi: currentModalProdi
    });
    const res = await fetch(`api.php?${params.toString()}`);
    const json = await res.json();

    if (json.status === "success") {
      await fetchModalProdiData();
      alert(`Sinkronisasi status PDDIKTI berhasil diperbarui untuk ${currentModalProdi}!\nTotal diperiksa: ${json.total_diperiksa}, Diperbarui: ${json.total_berhasil}`);
    } else {
      alert(json.message || "Gagal melakukan sinkronisasi");
    }
  } catch (err) {
    alert("Terjadi kesalahan saat sinkronisasi: " + err.message);
  } finally {
    btn.disabled = false;
    btn.innerHTML = origHtml;
  }
}

function exportModalProdiExcel() {
  if (!currentModalPt && !currentModalProdi) return;
  const angkatan = document.getElementById("modalFilterAngkatan")?.value || "";
  const params = new URLSearchParams({
    action: "export",
    type: "mahasiswa",
    format: "excel",
    pt: currentModalPt,
    prodi: currentModalProdi
  });
  if (angkatan) params.append("angkatan", angkatan);
  window.location.href = `api.php?${params.toString()}`;
}

function closeProdiDetailModal() {
  const modal = document.getElementById("prodiDetailModal");
  if (modal) {
    modal.classList.remove("active");
    modal.style.display = "none";
    document.body.style.overflow = "";
  }
}

function handleProdiModalBackdropClick(event) {
  if (event.target.id === "prodiDetailModal") {
    closeProdiDetailModal();
  }
}

// ESC key listener to close modal
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closeProdiDetailModal();
  }
});
