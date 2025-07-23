<?php
date_default_timezone_set('Asia/Jakarta');


function formatTanggal($str) {
  $str = trim($str ?? '');
  if ($str === '' || $str === '-') return '';
  $formats = ['d-m-Y H:i:s', 'Y-m-d H:i:s', 'd/m/Y H:i:s', 'd-m-Y', 'd/m/Y'];
  foreach ($formats as $fmt) {
    $dt = DateTime::createFromFormat($fmt, $str);
    if ($dt !== false) return $dt->format('d/m/Y H:i');
  }
  return str_replace('-', '/', $str);
}

function clean($val) {
  $val = trim($val ?? '');
  return ($val === '-' ? '' : $val);
}

function scrapeMALT300() {
  $url = 'https://malt300.com/Layanan/jadwalKapal';
  $context = stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0\r\n"]]);
  $html = @file_get_contents($url, false, $context);
  libxml_use_internal_errors(true);
  $dom = new DOMDocument();
  $dom->loadHTML($html);
  libxml_clear_errors();
  $xpath = new DOMXPath($dom);
  $rows = $xpath->query('//table//tr');

  $data = [];
  $no = 1;
  foreach ($rows as $row) {
    $cols = $row->getElementsByTagName('td');
    if ($cols->length >= 9) {
      $data[] = [
        'no'         => $no++,
        'vessel'     => clean($cols->item(1)->nodeValue),
        'company'    => clean($cols->item(2)->nodeValue),
        'voy_in'     => clean($cols->item(3)->nodeValue),
        'voy_out'    => clean($cols->item(4)->nodeValue),
        'open_stack' => formatTanggal($cols->item(5)->nodeValue),
        'berthing'   => formatTanggal($cols->item(6)->nodeValue),
        'departure'  => formatTanggal($cols->item(7)->nodeValue),
        'closing'    => formatTanggal($cols->item(8)->nodeValue),
      ];
    }
  }
  return $data;
}

$data = scrapeMALT300();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>MALT300 – Jadwal Kapal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
  <style>
    body { background: #f9fcff; font-family: 'Segoe UI', sans-serif; }
    .judul { font-weight: bold; color: #0a58ca; text-align: left; }
    th, td { font-size: 14px; vertical-align: middle !important; }

    /* Mobile Table */
    .table-mobile thead { display: none; }
    .table-mobile td {
      display: block; width: 100%; text-align: left;
      border: none; border-bottom: 1px solid #dee2e6;
    }
    .table-mobile td::before {
      content: attr(data-label);
      font-weight: bold; display: inline-block;
      width: 40%; color: #0a58ca;
    }
    .table-mobile tr {
      margin-bottom: 1rem; border: 1px solid #dee2e6;
      border-radius: 0.25rem; display: block; padding: 0.5rem;
    }

    .fade-in { animation: fadeIn 0.5s ease-in-out forwards; opacity: 0; }
    @keyframes fadeIn { to { opacity: 1; } }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <a href="index.html" class="btn btn-outline-secondary">⬅️ Kembali</a>
      <h4 class="judul">⚓ Jadwal Kapal – MALT300</h4>
      <input type="text" id="searchInput" class="form-control form-control-sm w-50" placeholder="🔍 Cari...">
    </div>

    <div class="text-end mb-2">
      <button id="toggleView" class="btn btn-outline-primary btn-sm me-2">🔄 Ganti Tampilan: Mobile</button>
      <button class="btn btn-success btn-sm" onclick="exportToExcel()">📤 Ekspor ke Excel</button>
    </div>

    <div class="text-end text-muted small fst-italic mb-3 fade-in">
      Terakhir diperbarui: <?= date('d/m/Y H:i:s') ?>
    </div>

    <div class="table-responsive" data-aos="fade-up">
      <table class="table table-bordered table-striped text-center align-middle">
        <thead class="table-info">
          <tr>
            <th>NO.</th>
            <th>NAMA KAPAL</th>
            <th>PERUSAHAAN PELAYARAN</th>
            <th>VOY MASUK</th>
            <th>VOY KELUAR</th>
            <th>OPEN STACK</th>
            <th>TANGGAL SANDAR</th>
            <th>TANGGAL BERANGKAT</th>
            <th>TANGGAL PENUTUPAN</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($data as $row): ?>
            <tr>
              <td data-label="NO."><?= $row['no'] ?></td>
              <td data-label="NAMA KAPAL"><?= htmlspecialchars($row['vessel']) ?></td>
              <td data-label="PERUSAHAAN PELAYARAN"><?= htmlspecialchars($row['company']) ?></td>
              <td data-label="VOY MASUK"><?= htmlspecialchars($row['voy_in']) ?></td>
              <td data-label="VOY KELUAR"><?= htmlspecialchars($row['voy_out']) ?></td>
              <td data-label="OPEN STACK"><?= $row['open_stack'] ?></td>
              <td data-label="TANGGAL SANDAR"><?= $row['berthing'] ?></td>
              <td data-label="TANGGAL BERANGKAT"><?= $row['departure'] ?></td>
              <td data-label="TANGGAL PENUTUPAN"><?= $row['closing'] ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init();

    document.getElementById("searchInput").addEventListener("input", function () {
      const keyword = this.value.toLowerCase();
      document.querySelectorAll("tbody tr").forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(keyword) ? "" : "none";
      });
    });

    document.getElementById("toggleView").addEventListener("click", function () {
      const table = document.querySelector("table");
      table.classList.toggle("table-mobile");
      this.textContent = table.classList.contains("table-mobile")
        ? "🔄 Ganti Tampilan: Tabel"
        : "🔄 Ganti Tampilan: Mobile";
    });

    function exportToExcel() {
      const table = document.querySelector("table");
      const wb = XLSX.utils.table_to_book(table, { sheet: "MALT300" });
      XLSX.writeFile(wb, "jadwal-malt300.xlsx");
    }
  </script>
</body>
</html>
