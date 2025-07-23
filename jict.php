<?php
date_default_timezone_set('Asia/Jakarta');


function formatTanggal($str) {
  $str = trim($str ?? '');
  if ($str === '' || $str === '-') return '';
  $formats = ['d-m-Y', 'Y-m-d', 'd.m.Y', 'd/m/Y', 'd M Y', 'd M y'];
  foreach ($formats as $fmt) {
    $dt = DateTime::createFromFormat($fmt, $str);
    if ($dt !== false) return $dt->format('d/m/Y');
  }
  return str_replace('-', '/', $str);
}

function clean($val) {
  $val = trim($val ?? '');
  return ($val === '-' ? '' : $val);
}

function scrapeJICT() {
  $url = 'https://jict.co.id/index.php/vessel-schedule';
  $context = stream_context_create([
    'http' => ['header' => "User-Agent: Mozilla/5.0\r\n"]
  ]);
  $html = @file_get_contents($url, false, $context);
  if (!$html) return [];

  libxml_use_internal_errors(true);
  $dom = new DOMDocument();
  $dom->loadHTML($html);
  libxml_clear_errors();
  $xpath = new DOMXPath($dom);
  $rows = $xpath->query('//table//tr');

  $data = [];
  foreach ($rows as $row) {
    $cols = $row->getElementsByTagName('td');
    if ($cols->length >= 9) {
      $data[] = [
        'vessel'     => clean($cols->item(0)->nodeValue),
        'voyage'     => clean($cols->item(1)->nodeValue),
        'arrival'    => formatTanggal($cols->item(2)->nodeValue),
        'berthing'   => formatTanggal($cols->item(3)->nodeValue),
        'departure'  => formatTanggal($cols->item(4)->nodeValue),
        'closing'    => formatTanggal($cols->item(5)->nodeValue),
        'terminal'   => clean($cols->item(6)->nodeValue),
        'status'     => clean($cols->item(7)->nodeValue),
        'open_stack' => formatTanggal($cols->item(8)->nodeValue),
      ];
    }
  }
  return $data;
}

$data = scrapeJICT();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>JICT – Jadwal Kapal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
  <style>
    body { background: #f0f6fc; font-family: 'Segoe UI', sans-serif; }
    .judul { font-weight: 700; color: #003366; text-align: left;}
    th, td { font-size: 14px; vertical-align: middle !important; }
    #loadingSpinner { display: none; }

    .table-mobile thead { display: none; }
    .table-mobile td {
      display: block;
      width: 100%;
      text-align: left;
      border: none;
      border-bottom: 1px solid #dee2e6;
    }
    .table-mobile td::before {
      content: attr(data-label);
      font-weight: bold;
      display: inline-block;
      width: 40%;
      color: #003366;
    }
    .table-mobile tr {
      margin-bottom: 1rem;
      border: 1px solid #dee2e6;
      border-radius: 0.25rem;
      display: block;
      padding: 0.5rem;
    }

    .fade-in { animation: fadeIn 0.5s ease-in-out forwards; opacity: 0; }
    @keyframes fadeIn { to { opacity: 1; } }

    .highlight {
      animation: flash 1s ease-in-out;
    }

    @keyframes flash {
      0%   { background-color: #ffe69c; }
      50%  { background-color: #fff3cd; }
      100% { background-color: transparent; }
    }
  </style>
</head>
<body>
  <!-- 🔝 Sticky Header -->
  <div class="sticky-top bg-white shadow-sm py-3 px-4 mb-2">
    <div class="row align-items-center">
      <div class="col-md-6 col-sm-12 mb-sm-2">
        <a href="index.html" class="btn btn-outline-secondary">⬅️ Kembali</a>
      </div>
      <div class="col-md-6 col-sm-12 text-md-end text-center">
        <h5 class="judul mb-0">🚢 Jadwal Kapal – JICT</h5>
      </div>
    </div>

    <!-- 🔍 Filter & Ekspor -->
    <div class="row align-items-center mt-3">
      <div class="col-md-4">
        <input type="text" id="searchInput" class="form-control" placeholder="🔍 Cari apa saja..." />
      </div>
      <div class="col-md-4 text-end">
        <button id="toggleView" class="btn btn-outline-primary">🔄 Ganti Tampilan: Mobile</button>
        <button class="btn btn-success" onclick="exportToExcel()">📤 Ekspor ke Excel</button>
      </div>
    </div>

    <div class="text-end mt-2 mb-1">
      <span id="lastUpdated"
            class="text-muted small fst-italic fade-in"
            data-bs-toggle="tooltip"
            title="Waktu pembaruan data terakhir dari JICT">
        Terakhir diperbarui: <?= date('d/m/Y H:i:s') ?>
      </span>
    </div>
  </div>

  <!-- 📋 Tabel Data -->
  <div class="container py-3">
    <div class="table-responsive" data-aos="fade-up">
      <table class="table table-bordered table-striped text-center align-middle">
        <thead class="table-primary">
          <tr>
            <th>Vessel</th><th>Voyage</th><th>Arrival</th><th>Berthing</th><th>Departure</th>
            <th>Closing</th><th>Terminal</th><th>Status</th><th>Open Stack</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($data as $row): ?>
            <tr>
              <td data-label="Vessel"><?= htmlspecialchars($row['vessel']) ?></td>
              <td data-label="Voyage"><?= htmlspecialchars($row['voyage']) ?></td>
              <td data-label="Arrival"><?= $row['arrival'] ?></td>
              <td data-label="Berthing"><?= $row['berthing'] ?></td>
              <td data-label="Departure"><?= $row['departure'] ?></td>
              <td data-label="Closing"><?= $row['closing'] ?></td>
              <td data-label="Terminal"><?= htmlspecialchars($row['terminal']) ?></td>
              <td data-label="Status"><?= htmlspecialchars($row['status']) ?></td>
              <td data-label="Open Stack"><?= $row['open_stack'] ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 🚀 Script Interaktif -->
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
      const workbook = XLSX.utils.table_to_book(table, { sheet: "JICT" });
      XLSX.writeFile(workbook, "jadwal-jict.xlsx");
    }
  </script>
</body>
</html>
