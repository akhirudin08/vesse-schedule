<?php
include 'functions.php';

$data = array_merge(
  array_map(fn($d) => applyMapping($d, 'JICT'), scrapeJICT()),
  array_map(fn($d) => applyMapping($d, 'MALT300'), scrapeMALT300()),
  array_map(fn($d) => applyMapping($d, 'NPCT1'), scrapeNPCT1()),
  array_map(fn($d) => applyMapping($d, 'TPK Koja'), scrapeTPKKoja())
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>📊 Rangkuman Jadwal Kapal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
  <style>
    body { background: #f8fbfd; font-family: 'Segoe UI', sans-serif; }
    .judul { font-weight: bold; color: #003366; }
    th, td { font-size: 14px; vertical-align: middle !important; }
    .table-mobile thead { display: none; }
    .table-mobile td::before { content: attr(data-label); font-weight: bold; display: inline-block; width: 40%; color: #003366; }
    .table-mobile td, .table-mobile tr { display: block; width: 100%; border-bottom: 1px solid #dee2e6; padding: 0.5rem; }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="judul">🚢 Rangkuman Jadwal Kapal</h4>
      <input type="text" id="searchInput" class="form-control w-50" placeholder="🔍 Cari...">
    </div>

    <div class="text-end mb-2">
      <button id="toggleView" class="btn btn-outline-primary">🔄 Ganti Tampilan: Mobile</button>
      <button class="btn btn-success" onclick="exportToExcel()">📤 Ekspor ke Excel</button>
    </div>

    <div class="text-end text-muted small fst-italic mb-3">
      Terakhir diperbarui: <?= date('d/m/Y H:i:s') ?>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-striped text-center align-middle" id="mainTable">
        <thead class="table-info">
          <tr>
            <th>Vessel</th><th>Voyage</th><th>Open Stack</th><th>ETD</th><th>Closing Time</th><th>Closing Doc</th><th>Source</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($data as $row): ?>
            <tr>
              <td data-label="Vessel"><?= htmlspecialchars($row['vessel']) ?></td>
              <td data-label="Voyage"><?= htmlspecialchars($row['voyage']) ?></td>
              <td data-label="Open Stack"><?= $row['open_stack'] ?></td>
              <td data-label="ETD"><?= $row['etd'] ?></td>
              <td data-label="Closing Time"><?= $row['closing_time'] ?></td>
              <td data-label="Closing Doc"><?= $row['closing_doc'] ?></td>
              <td data-label="Source"><?= $row['source'] ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
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
      const table = document.getElementById("mainTable");
      const wb = XLSX.utils.table_to_book(table, { sheet: "Rangkuman" });
      XLSX.writeFile(wb, "jadwal-rangkuman.xlsx");
    }
  </script>
</body>
</html>
