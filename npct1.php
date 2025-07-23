<?php
date_default_timezone_set('Asia/Jakarta');


function formatTanggal($str) {
  $str = trim($str ?? '');
  if ($str === '' || $str === '-') return '';
  $formats = ['Y-m-d H:i:s', 'Y-m-d', 'd-m-Y', 'd/m/Y', 'd M Y'];
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

function scrapeNPCT1() {
  $url = 'https://www.npct1.co.id/vessel-schedule';
  $context = stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0\r\n"]]);
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
    if ($cols->length >= 12) {
      $data[] = [
        'vessel'      => clean($cols->item(0)->nodeValue),
        'voy_in'      => clean($cols->item(1)->nodeValue),
        'voy_out'     => clean($cols->item(2)->nodeValue),
        'service'     => clean($cols->item(3)->nodeValue),
        'status'      => clean($cols->item(4)->nodeValue),
        'eta'         => formatTanggal($cols->item(5)->nodeValue),
        'ata'         => formatTanggal($cols->item(6)->nodeValue),
        'etd'         => formatTanggal($cols->item(7)->nodeValue),
        'atd'         => formatTanggal($cols->item(8)->nodeValue),
        'open_stack'  => formatTanggal($cols->item(9)->nodeValue),
        'closing_doc' => formatTanggal($cols->item(10)->nodeValue),
        'closing_phy' => formatTanggal($cols->item(11)->nodeValue),
      ];
    }
  }
  return $data;
}

$data = scrapeNPCT1();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>NPCT1 – Jadwal Kapal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
  <style>
    body { background: #f3faff; font-family: 'Segoe UI', sans-serif; }
    .judul { font-weight: bold; color: #00639b; text-align: left; }
    th, td { font-size: 14px; vertical-align: middle !important; }

    .table-mobile thead { display: none; }
    .table-mobile td {
      display: block; width: 100%; text-align: left;
      border: none; border-bottom: 1px solid #dee2e6;
    }
    .table-mobile td::before {
      content: attr(data-label); font-weight: bold;
      display: inline-block; width: 40%; color: #00639b;
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
      <h4 class="judul">📋 Jadwal Kapal – NPCT1</h4>
      <input type="text" id="searchInput" class="form-control form-control-sm w-50" placeholder="🔍 Cari...">
    </div>

    <div class="text-end mb-2">
      <button id="toggleView" class="btn btn-outline-primary btn-sm me-2">🔄 Ganti Tampilan: Mobile</button>
      <button class="btn btn-success btn-sm" onclick="exportToExcel()">📤 Ekspor ke Excel</button>
    </div>

    <div class="text-end text-muted small fst-italic fade-in mb-3">
      Terakhir diperbarui: <?= date('d/m/Y H:i:s') ?>
    </div>

    <div class="table-responsive" data-aos="fade-up">
      <table class="table table-bordered table-striped text-center align-middle">
        <thead class="table-info">
          <tr>
            <th>Vessel</th><th>Voy IN</th><th>Voy OUT</th><th>Service</th><th>Status</th>
            <th>ETA</th><th>ATA</th><th>ETD</th><th>ATD</th>
            <th>Open Stack</th><th>Closing Doc</th><th>Closing Physic</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($data as $row): ?>
            <tr>
              <td data-label="Vessel"><?= htmlspecialchars($row['vessel']) ?></td>
              <td data-label="Voy IN"><?= htmlspecialchars($row['voy_in']) ?></td>
              <td data-label="Voy OUT"><?= htmlspecialchars($row['voy_out']) ?></td>
              <td data-label="Service"><?= htmlspecialchars($row['service']) ?></td>
              <td data-label="Status"><?= htmlspecialchars($row['status']) ?></td>
              <td data-label="ETA"><?= $row['eta'] ?></td>
              <td data-label="ATA"><?= $row['ata'] ?></td>
              <td data-label="ETD"><?= $row['etd'] ?></td>
              <td data-label="ATD"><?= $row['atd'] ?></td>
              <td data-label="Open Stack"><?= $row['open_stack'] ?></td>
              <td data-label="Closing Doc"><?= $row['closing_doc'] ?></td>
              <td data-label="Closing Physic"><?= $row['closing_phy'] ?></td>
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
      const wb = XLSX.utils.table_to_book(table, { sheet: "NPCT1" });
      XLSX.writeFile(wb, "jadwal-npct1.xlsx");
    }
  </script>
</body>
</html>
