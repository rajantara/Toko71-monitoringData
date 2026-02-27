<?php
include "../config/database.php";

$bulanNama = [
  1=>"Januari", 2=>"Februari", 3=>"Maret", 4=>"April",
  5=>"Mei", 6=>"Juni", 7=>"Juli", 8=>"Agustus",
  9=>"September", 10=>"Oktober", 11=>"November", 12=>"Desember"
];

$year  = $_GET['year'] ?? date("Y");
$month = $_GET['month'] ?? "";

/* =========================
   HAPUS DATA PER KATEGORI
========================= */
if(isset($_POST['delete_category_data'])){
  $catId = (int)$_POST['delete_category_id'];

  if($month!=""){
    $conn->query("DELETE FROM sales 
                  WHERE category_id=$catId 
                  AND year=$year 
                  AND month=$month");
  }else{
    $conn->query("DELETE FROM sales 
                  WHERE category_id=$catId 
                  AND year=$year");
  }

  header("Location: ".$_SERVER['REQUEST_URI']);
  exit;
}

/* TOTAL */
$totalAll = $conn->query("
  SELECT IFNULL(SUM(amount),0) total
  FROM sales
  WHERE year=$year ".($month!=""?"AND month=$month":"")."
")->fetch_assoc()['total'];

/* TOTAL PER BULAN */
$monthlyTotals = [];
$maxValue = 0;
$minValue = PHP_INT_MAX;
$maxMonth = 0;
$minMonth = 0;

for($m=1;$m<=12;$m++){
  $total = $conn->query("
    SELECT IFNULL(SUM(amount),0) total
    FROM sales
    WHERE year=$year AND month=$m
  ")->fetch_assoc()['total'];

  $monthlyTotals[$m] = $total;

  if($total > $maxValue){
    $maxValue = $total;
    $maxMonth = $m;
  }

  if($total < $minValue){
    $minValue = $total;
    $minMonth = $m;
  }
}

/* TOP 3 */
$rankData = [];
$qRank = $conn->query("
  SELECT c.id, c.name kategori, IFNULL(SUM(s.amount),0) total
  FROM categories c
  LEFT JOIN sales s
    ON s.category_id=c.id
    AND s.year=$year ".($month!=""?"AND s.month=$month":"")."
  GROUP BY c.id
  ORDER BY total DESC
  LIMIT 3
");
while($r=$qRank->fetch_assoc()){
  $rankData[] = $r;
}

function rankBadge($rank){
  if($rank==1) return "<span class='badge bg-warning text-dark'>TOP 1</span>";
  if($rank==2) return "<span class='badge bg-secondary'>TOP 2</span>";
  if($rank==3) return "<span class='badge bg-info text-dark'>TOP 3</span>";
  return "";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Monitoring Penjualan</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<style>
/* CSS KAMU TIDAK DIUBAH */
body{background:#f1f5f9;font-family:system-ui}
.container{max-width:1200px}
.card{border:none;border-radius:18px;box-shadow:0 15px 35px rgba(0,0,0,0.05)}
.card-body{padding:28px}
.total-amount{font-size:36px;font-weight:800;color:#0f172a}
.table thead th{background:#0f172a;color:#fff;padding:14px;border:none;font-size:13px}
.table tbody td{padding:14px;border-bottom:1px solid #e2e8f0;font-weight:600}
.table tbody tr:hover{background:#f8fafc}
.row-highest{background:#ecfdf5 !important;border-left:6px solid #16a34a}
.row-lowest{background:#fef2f2 !important;border-left:6px solid #dc2626}
.badge-high{background:#16a34a;color:#fff;padding:5px 10px;font-size:11px;border-radius:20px}
.badge-low{background:#dc2626;color:#fff;padding:5px 10px;font-size:11px;border-radius:20px}
canvas{max-height:320px}
.btn{border-radius:10px;font-weight:600}
.form-select{border-radius:10px;height:44px}
</style>
</head>

<body>

<div class="container py-4" id="export-area">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-0">
      <i class="bi bi-clipboard-data text-primary"></i>
      Monitoring Penjualan Toko Cahya 71
    </h4>
    <small class="text-muted">Rekap per tahun dan per bulan</small>
  </div>

  <div class="d-flex gap-2">
    <a href="index.php" class="btn btn-outline-primary">
      <i class="bi bi-plus-circle"></i> Input Data
    </a>

    <button onclick="exportImage()" class="btn btn-success">
      <i class="bi bi-image"></i> Export Gambar
    </button>

    <button onclick="exportToWhatsapp()" class="btn btn-success">
      <i class="bi bi-whatsapp"></i> Kirim WhatsApp
    </button>
  </div>
</div>

<form method="get" class="row g-2 mb-4">
<div class="col-md-3">
<select name="year" class="form-select">
<?php for($y=2020;$y<=date("Y");$y++): ?>
<option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
<?php endfor; ?>
</select>
</div>

<div class="col-md-3">
<select name="month" class="form-select">
<option value="">Semua Bulan</option>
<?php for($m=1;$m<=12;$m++): ?>
<option value="<?= $m ?>" <?= $month==$m?'selected':'' ?>>
<?= $bulanNama[$m] ?>
</option>
<?php endfor; ?>
</select>
</div>

<div class="col-md-3">
<button class="btn btn-primary w-100">
<i class="bi bi-filter"></i> Tampilkan
</button>
</div>
</form>

<div class="card mb-4">
<div class="card-body">
<div class="text-muted">Total Penjualan Semua Kategori</div>
<div class="total-amount" id="animatedTotal">Rp 0</div>
</div>
</div>

<div class="card mb-4">
<div class="card-body">
<h5>Grafik Penjualan Per Bulan Semua Kategori</h5>
<canvas id="salesChart"></canvas>
</div>
</div>

<div class="card mb-4">
<div class="card-body">
<h5>Top 3 Per Kategori</h5>
<table class="table">
<thead>
<tr>
<th>Rank</th>
<th>Kategori</th>
<th>Total</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
<?php $i=1; foreach($rankData as $r): ?>
<tr>
<td><?= rankBadge($i) ?></td>
<td><?= $r['kategori'] ?></td>
<td>Rp <?= number_format($r['total'],0,',','.') ?></td>
<td>
<form method="post" onsubmit="return confirm('Hapus data kategori ini?')">
<input type="hidden" name="delete_category_id" value="<?= $r['id'] ?>">
<button name="delete_category_data" class="btn btn-sm btn-danger">
<i class="bi bi-trash"></i>
</button>
</form>
</td>
</tr>
<?php $i++; endforeach; ?>
</tbody>
</table>
</div>
</div>

<div class="card">
<div class="card-body">
<h5>Rekap Per Bulan Semua Kategori</h5>
<table class="table">
<thead>
<tr>
<th>Bulan</th>
<th>Total</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach($monthlyTotals as $m=>$v): 
$class="";
$status="";
if($m==$maxMonth){ $class="row-highest"; $status="<span class='badge-high'>Tertinggi</span>"; }
if($m==$minMonth){ $class="row-lowest"; $status="<span class='badge-low'>Terendah</span>"; }
?>
<tr class="<?= $class ?>">
<td><?= $bulanNama[$m] ?></td>
<td>Rp <?= number_format($v,0,',','.') ?></td>
<td><?= $status ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

</div>

<script>
let target = <?= $totalAll ?>;
let startTime = null;
let duration = 1500;
function animateTotal(timestamp){
  if(!startTime) startTime = timestamp;
  let progress = timestamp - startTime;
  let value = Math.min(progress / duration * target, target);
  document.getElementById("animatedTotal").innerText =
    "Rp " + Math.floor(value).toLocaleString("id-ID");
  if(progress < duration){
    requestAnimationFrame(animateTotal);
  }
}
requestAnimationFrame(animateTotal);

new Chart(document.getElementById('salesChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_values($bulanNama)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($monthlyTotals)) ?>,
            backgroundColor: <?= json_encode(
                array_map(function($m) use($maxMonth,$minMonth){
                    if($m==$maxMonth) return "#16a34a";
                    if($m==$minMonth) return "#dc2626";
                    return "#2563eb";
                }, array_keys($monthlyTotals))
            ) ?>,
            borderRadius:8
        }]
    },
    options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}
});
</script>


<script>
function exportImage(){
  html2canvas(document.getElementById("export-area"), {
    scale:2,
    useCORS:true,
    backgroundColor:"#ffffff"
  }).then(function(canvas){
      const link = document.createElement("a");
      link.href = canvas.toDataURL("image/png");
      link.download = "monitoring_penjualan_<?= $year ?><?= $month ? '_'.$bulanNama[$month] : '' ?>.png";
      link.click();
  });
}

function exportToWhatsapp(){
  html2canvas(document.getElementById("export-area"), {
    scale:2,
    useCORS:true,
    backgroundColor:"#ffffff"
  }).then(function(canvas){

      const imgData = canvas.toDataURL("image/png");

      const link = document.createElement("a");
      link.href = imgData;
      link.download = "monitoring_penjualan.png";
      link.click();

      const pesan = "Laporan Monitoring Penjualan Toko Cahya 71 Tahun <?= $year ?> <?= $month ? $bulanNama[$month] : '' ?>";

      setTimeout(function(){
          window.open("https://wa.me/6281234567890?text=" + encodeURIComponent(pesan), "_blank");
      },800);

  });
}
</script>

</body>
</html>