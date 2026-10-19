<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Perguruan Tinggi PDDikti (PHP)</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background-color: #f8f9fa;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        h2 { margin-top: 0; color: #1a252f; }
        .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        button {
            padding: 10px 20px;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        button:hover { background-color: #0b5ed7; }
        button:disabled { background-color: #6c757d; cursor: not-allowed; }
        .status-info { font-size: 14px; color: #495057; font-weight: bold; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }
        th { background-color: #f1f3f5; font-weight: 600; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .action-area { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Daftar Perguruan Tinggi PDDikti</h2>
    
    <div class="controls">
        <div class="status-info" id="statusText">Tersimpan: 0 Data</div>
        <div id="loadingText" style="color: #0d6efd;"></div>
    </div>

    <table id="ptTable">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Perguruan Tinggi</th>
                <th>Singkatan</th>
                <th>Jenis</th>
                <th>Status</th>
                <th>Akreditasi</th>
                <th>Kab/Kota</th>
                <th>Provinsi</th>
                <th>Prodi</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <tr>
                <td colspan="9" style="text-align:center;">Memuat data awal...</td>
            </tr>
        </tbody>
    </table>

    <div class="action-area">
        <button id="btnLoadMore" onclick="loadNextChunk()" disabled>Muat 100 Data Berikutnya</button>
    </div>
</div>

<script>
    // Memanggil API PHP lokal
    const PHP_API = "api.php";
    
    let currentApiPage = 1;
    let totalApiPages = 462;
    let totalLoadedItems = 0;
    let grandTotalItems = 0;
    const itemsPerChunk = 100;

    async function fetchApiPage(page) {
        const response = await fetch(`${PHP_API}?page=${page}`);
        if (!response.ok) throw new Error("Gagal mengambil data dari server PHP");
        const json = await response.json();
        return json.data;
    }

    async function loadNextChunk() {
        const btn = document.getElementById("btnLoadMore");
        const loadingText = document.getElementById("loadingText");
        btn.disabled = true;
        loadingText.innerText = "Sedang mengambil 100 data berikutnya...";

        let collectedChunk = [];

        try {
            while (collectedChunk.length < itemsPerChunk && currentApiPage <= totalApiPages) {
                const res = await fetchApiPage(currentApiPage);
                grandTotalItems = res.totalItems || 6927;
                totalApiPages = res.totalPages || 462;
                
                collectedChunk.push(...res.data);
                currentApiPage++;
                
                // Jeda 100ms
                await new Promise(r => setTimeout(r, 100));
            }

            if (collectedChunk.length > 0) {
                if (totalLoadedItems === 0) {
                    document.getElementById("tableBody").innerHTML = "";
                }

                renderTableRows(collectedChunk);
                totalLoadedItems += collectedChunk.length;
                document.getElementById("statusText").innerText = `Tampil: ${totalLoadedItems} dari${grandTotalItems} Data`;
            }

            loadingText.innerText = "";
            
            if (totalLoadedItems < grandTotalItems) {
                btn.disabled = false;
            } else {
                btn.innerText = "Semua Data Telah Dimuat";
            }

        } catch (err) {
            loadingText.innerText = "Terjadi error: " + err.message;
            btn.disabled = false;
        }
    }

    function renderTableRows(dataChunk) {
        const tbody = document.getElementById("tableBody");

        dataChunk.forEach((item) => {
            const row = document.createElement("tr");
            const currentIndex = tbody.children.length + 1;
            
            row.innerHTML = `
                <td>${currentIndex}</td>
                <td><strong>${item.nama_pt || '-'}</strong></td>
                <td>${item.nama_singkat || '-'}</td>
                <td>${item.jenis_pt || '-'}</td>
                <td>${item.status_pt || '-'}</td>
                <td>${item.akreditasi || '-'}</td>
                <td>${item.kab_kota_pt || '-'}</td>
                <td>${item.provinsi_pt || '-'}</td>
                <td>${item.jumlah_prodi || 0}</td>
            `;
            tbody.appendChild(row);
        });
    }

    // Jalankan pertama kali saat dibuka
    loadNextChunk();
</script>

</body>
</html>