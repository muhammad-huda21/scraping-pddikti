# PDDIKTI Aceh & Politeknik Negeri Lhokseumawe (PNL) - PHP Native (Laragon)

Aplikasi web portal analisis data pendidikan tinggi di Provinsi Aceh dan Politeknik Negeri Lhokseumawe (PNL) yang dibangun menggunakan **PHP Native (PHP 8.1 - Laragon)** dengan arsitektur **cURL** langsung ke API resmi PDDIKTI sesuai dengan modul pada folder `panduan/`.

---

## 🎯 5 Fitur Utama Sesuai Kebutuhan

1. **Nama Mahasiswa Seluruh Kampus di Aceh**:
   - Filter berdasarkan kampus (PNL, USK, Unimal, UTU, Unsam, dll.), prodi, NIM, dan nama mahasiswa.
   - Paginasi instan dan ekspor data ke CSV.
2. **Nama Dosen Seluruh Kampus di Aceh**:
   - Database profil tenaga pengajar terdaftar di PDDIKTI se-Aceh lengkap dengan NIDN/NUPTK, prodi homebase, dan kampus.
3. **Pencarian Penelitian Dosen Politeknik Negeri Lhokseumawe (PNL)**:
   - Rekam jejak riset, judul kegiatan, tahun kegiatan, nama dosen peneliti, dan jenis kegiatan penelitian.
4. **Data Jumlah Mahasiswa per Prodi di Aceh**:
   - Visualisasi grafik interaktif (Top 10 Prodi Terpadat & Distribusi per Jenjang D3/D4/S1/S2).
   - Tabel komparasi jumlah mahasiswa, jumlah dosen aktif, akreditasi, dan rasio dosen:mahasiswa.
5. **Data Riwayat Pendidikan Dosen Politeknik Negeri Lhokseumawe**:
   - Kualifikasi akademik dosen PNL (jenjang S1, S2, S3), almamater universitas kelulusan, gelar akademik, dan tahun kelulusan.

---

## 🚀 Cara Menjalankan

Aplikasi dapat dijalankan langsung menggunakan PHP dari instalasi Laragon Anda:

```powershell
& "D:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe" -d extension=sqlite3 -d extension=pdo_sqlite -S localhost:8000
```

Buka di browser:
👉 **http://localhost:8000**

*(Atau letakkan folder ini di dalam direktori `D:\laragon\www\da` untuk diakses melalui Apache/Nginx bawaan Laragon di `http://da.test`)*.

---

## 📂 Struktur Proyek (PHP Native)

```
d:/Project/da/
├── index.php           # Antarmuka Dashboard utama (Clean White Theme / Anti-Slop)
├── api.php             # Router REST API backend berbasis PHP cURL (sesuai panduan)
├── db.php              # Dual-engine data storage (SQLite PDO + fallback JSON)
├── pddikti_aceh.db     # Database SQLite berisi data awal kampus, prodi, dosen, riset, mhs
├── data/               # Cache JSON otomatis untuk portabilitas penuh tanpa ekstensi khusus
│   ├── kampus_aceh.json
│   ├── prodi_aceh.json
│   ├── dosen_aceh.json
│   ├── dosen_penelitian.json
│   ├── dosen_pendidikan.json
│   └── mahasiswa_aceh.json
├── css/
│   └── style.css       # Desain tema putih bersih & modern (Anti-Slop Design)
├── js/
│   └── app.js          # Controller frontend, live search, Chart.js, pagination
├── panduan/            # Referensi awal API cURL dari pengguna
│   ├── api.php
│   └── index.php
└── README.md
```

---

## 💡 Keunggulan Arsitektur
- **100% PHP Native**: Tidak memerlukan Python, Node.js, atau dependency manager tambahan.
- **cURL Terintegrasi Sesuai Panduan**: Mengadopsi mekanisme SSL bypass dan HTTP headers browser resmi dari `panduan/api.php`.
- **Live Search PDDIKTI**: Mencari mahasiswa atau dosen langsung ke server Kemdikbud secara *real-time* via PHP cURL dan otomatis meng-cache hasilnya.
- **Ekspor CSV**: Tombol unduh data CSV di setiap tab untuk laporan dan analisis.
