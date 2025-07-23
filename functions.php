<?php
date_default_timezone_set('Asia/Jakarta');

function formatTanggal($str) {
  $str = trim($str ?? '');
  if ($str === '' || $str === '-') return '';
  $formats = ['Y-m-d H:i:s', 'Y-m-d', 'd-m-Y', 'd/m/Y', 'd M Y', 'd-m-Y H:i:s', 'd/m/Y H:i:s'];
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

// 🧩 Field mapping
$mapping = [
  'JICT' => [
    'vessel' => 'vessel',
    'voyage' => 'voyage',
    'open_stack' => 'open_stack',
    'etd' => 'departure',
    'closing_time' => 'closing',
    'closing_doc' => 'closing'
  ],
  'MALT300' => [
    'vessel' => 'vessel',
    'voyage' => 'voy_out',
    'open_stack' => 'open_stack',
    'etd' => 'departure',
    'closing_time' => 'closing',
    'closing_doc' => function($etd) {
      return date('d/m/Y', strtotime($etd . ' -2 days'));
    }
  ],
  'NPCT1' => [
    'vessel' => 'vessel',
    'voyage' => 'voy_out',
    'open_stack' => 'open_stack',
    'etd' => 'etd',
    'closing_time' => 'closing_phy',
    'closing_doc' => 'closing_doc'
  ],
  'TPK Koja' => [
    'vessel' => 'vessel_name',
    'voyage' => function($code) {
      $parts = explode('-', $code);
      return $parts[1] ?? $code;
    },
    'open_stack' => 'openstack',
    'etd' => 'etd',
    'closing_time' => 'ct',
    'closing_doc' => function($etd) {
      return date('d/m/Y', strtotime($etd . ' -2 days'));
    }
  ]
];

function applyMapping($row, $port) {
  global $mapping;
  $map = $mapping[$port];
  $result = [];
  foreach ($map as $key => $field) {
    if (is_callable($field)) {
      $input = $row['etd'] ?? $row['code'] ?? '';
      $result[$key] = $field($input);
    } else {
      $result[$key] = $row[$field] ?? '';
    }
  }
  $result['source'] = $port;
  return $result;
}

function scrapeJICT() {
  $url = 'https://jict.co.id/index.php/vessel-schedule';
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

function scrapeTPKKoja() {
  $url = 'https://www.tpkkoja.co.id/vessel-schedule/';
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
    if ($cols->length >= 10) {
      $data[] = [
        'no'         => clean($cols->item(0)->nodeValue),
        'vessel'     => clean($cols->item(1)->nodeValue),
        'vessel_name'=> clean($cols->item(2)->nodeValue),
        'eta'        => formatTanggal($cols->item(3)->nodeValue),
                'ct'         => formatTanggal($cols->item(4)->nodeValue),
        'etd'        => formatTanggal($cols->item(5)->nodeValue),
        'no_bc'      => clean($cols->item(6)->nodeValue),
        'berthing'   => formatTanggal($cols->item(7)->nodeValue),
        'service'    => clean($cols->item(8)->nodeValue),
        'openstack'  => formatTanggal($cols->item(9)->nodeValue),
      ];
    }
  }
  return $data;
}
?>
