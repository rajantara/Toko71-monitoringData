<?php

include "../config/database.php";

/* =====================
   NAMA BULAN
===================== */
$bulan = [
  1=>"Januari",2=>"Februari",3=>"Maret",4=>"April",
  5=>"Mei",6=>"Juni",7=>"Juli",8=>"Agustus",
  9=>"September",10=>"Oktober",11=>"November",12=>"Desember"
];

$message = "";

/* =====================
   PARAMETER GET
===================== */
$selectedCat  = $_GET['category_id'] ?? "";
$selectedYear = $_GET['year'] ?? "";

/* =====================
   AUTO LOAD DATA + LOCK
===================== */
$oldData = [];
$locked  = false;

if($selectedCat && $selectedYear){
  $q = $conn->query("
    SELECT month, amount 
    FROM sales 
    WHERE category_id=$selectedCat AND year=$selectedYear
  ");
  while($r=$q->fetch_assoc()){
    $oldData[$r['month']] = $r['amount'];
  }
  if(count($oldData) >= 12){
    $locked = true;
  }
}

/* =====================
   TAMBAH KATEGORI
===================== */
if(isset($_POST['add_category'])){
  $name = trim($_POST['category_name']);
  if($name!=""){
    $cek = $conn->query("
      SELECT COUNT(*) t FROM categories WHERE name='$name'
    ")->fetch_assoc()['t'];

    if($cek>0){
      $message = "<div class='alert alert-warning'>Kategori sudah ada</div>";
    }else{
      $conn->query("INSERT INTO categories(name) VALUES('$name')");
      $message = "<div class='alert alert-success'>Kategori berhasil ditambahkan</div>";
    }
  }
}

/* =====================
   EDIT KATEGORI
===================== */
if(isset($_POST['edit_category'])){
  $id   = (int)$_POST['category_id'];
  $name = trim($_POST['category_name']);

  $cek = $conn->query("
    SELECT COUNT(*) t FROM categories 
    WHERE name='$name' AND id!=$id
  ")->fetch_assoc()['t'];

  if($cek>0){
    $message = "<div class='alert alert-warning'>Nama kategori sudah digunakan</div>";
  }else{
    $conn->query("UPDATE categories SET name='$name' WHERE id=$id");
    $message = "<div class='alert alert-success'>Kategori berhasil diubah</div>";
  }
}

/* =====================
   HAPUS KATEGORI
===================== */
if(isset($_GET['delete_category'])){
  $id = (int)$_GET['delete_category'];

  $used = $conn->query("
    SELECT COUNT(*) t FROM sales WHERE category_id=$id
  ")->fetch_assoc()['t'];

  if($used>0){
    $message = "<div class='alert alert-danger'>Kategori tidak bisa dihapus karena sudah digunakan</div>";
  }else{
    $conn->query("DELETE FROM categories WHERE id=$id");
    $message = "<div class='alert alert-success'>Kategori berhasil dihapus</div>";
  }
}

/* =====================
   SIMPAN PENJUALAN
===================== */
if(isset($_POST['save_sales']) && !$locked){

  $year = (int)$_POST['year'];
  $cat  = (int)$_POST['category_id'];

  $duplicateFound = false;
  $duplicateMonth = "";

  foreach($_POST['sales'] as $m=>$val){

    $amount = (int)preg_replace("/[^0-9]/","",$val);

    if($amount > 0){

      /* CEK DUPLIKASI KE SEMUA TAHUN SEBELUMNYA */
      $cekDuplicate = $conn->query("
        SELECT COUNT(*) t 
        FROM sales
        WHERE category_id=$cat
        AND month=$m
        AND amount=$amount
        AND year < $year
      ")->fetch_assoc()['t'];

      if($cekDuplicate > 0){
        $duplicateFound = true;
        $duplicateMonth = $bulan[$m];
        break;
      }

      /* CEK DATA SUDAH ADA DI TAHUN YANG SAMA */
      $cekExist = $conn->query("
        SELECT COUNT(*) t 
        FROM sales
        WHERE year=$year
        AND category_id=$cat
        AND month=$m
      ")->fetch_assoc()['t'];

      if($cekExist == 0){
        $conn->query("
          INSERT INTO sales VALUES(NULL,$cat,$year,$m,$amount)
        ");
      }
    }
  }

  if($duplicateFound){

  echo "
  <script>
  document.addEventListener('DOMContentLoaded', function(){

    Swal.fire({
      background:'#111827',
      color:'#ffffff',
      title:'Duplikasi Nominal',
      html:'Nominal bulan <b>$duplicateMonth</b><br><br>\
            sudah pernah digunakan di tahun sebelumnya.',
      icon:'error',
      confirmButtonText:'Mengerti',
      confirmButtonColor:'#ef4444',
      showClass:{
        popup:'animate__animated animate__fadeInDown'
      },
      hideClass:{
        popup:'animate__animated animate__fadeOutUp'
      }
    });

  });
  </script>";
}
else{

  echo "
  <script>
  document.addEventListener('DOMContentLoaded', function(){

    Swal.fire({
      background:'#111827',
      color:'#ffffff',
      title:'Berhasil',
      text:'Data penjualan berhasil disimpan.',
      icon:'success',
      confirmButtonColor:'#16a34a',
      showClass:{
        popup:'animate__animated animate__fadeInDown'
      },
      hideClass:{
        popup:'animate__animated animate__fadeOutUp'
      }
    });

  });
  </script>";
}


}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Input Penjualan</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<script>
function formatRupiah(el){
  let v = el.value.replace(/[^0-9]/g,'');
  if(v===''){ el.value=''; return; }
  el.value = 'Rp ' + v.replace(/\B(?=(\d{3})+(?!\d))/g,'.');
}
function beforeSubmit(){
  document.querySelectorAll('.rupiah').forEach(i=>{
    i.value = i.value.replace(/[^0-9]/g,'');
  });
}
</script>

<style>
body{
  background:#f4f6fa;
  font-family:system-ui;
}

.container{
  max-width:1100px;
}

/* HEADER */
.header-box{
  background:#ffffff;
  border-radius:18px;
  box-shadow:0 12px 30px rgba(0,0,0,0.06);
  padding:20px 24px;
}

/* CARD */
.card{
  border:none;
  border-radius:18px;
  box-shadow:0 12px 30px rgba(0,0,0,0.06);
}

.card-body{
  padding:24px;
}

/* TABLE */
.table{
  border:none;
  border-radius:14px;
  overflow:hidden;
  background:#fff;
}

.table th{
  background:#0f172a;
  color:#fff;
  font-weight:700;
  font-size:13px;
  border:none;
  padding:14px;
}

.table td{
  border:none;
  border-bottom:1px solid #e5e7eb;
  padding:14px;
  font-weight:600;
}

.table tr:last-child td{
  border-bottom:none;
}

.table tr:nth-child(even){
  background:#f8fafc;
}

.table tr:hover{
  background:#eef2ff;
  transition:0.2s;
}

/* FORM */
.form-control,
.form-select{
  border-radius:10px;
  height:42px;
  font-weight:600;
}

.form-control:focus,
.form-select:focus{
  box-shadow:0 0 0 0.15rem rgba(37,99,235,.25);
}

/* BUTTON */
.btn{
  border-radius:10px;
  font-weight:600;
}

.btn-primary{
  background:#2563eb;
  border:none;
}

.btn-primary:hover{
  background:#1d4ed8;
}

.btn-success{
  background:#16a34a;
  border:none;
}

.btn-success:hover{
  background:#15803d;
}

/* LOCK */
.locked{
  pointer-events:none;
  opacity:.6;
}

.alert{
  border:none;
  border-radius:12px;
  font-weight:600;
}
</style>
</head>

<body class="bg-light">
<div class="container py-4">

<!-- HEADER -->
<div class="header-box d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-0 fw-bold">
      <i class="bi bi-cash-stack text-primary"></i>
      Input Penjualan
    </h4>
    <small class="text-muted">Toko Cahya71</small>
  </div>
  <a href="dashboard.php" class="btn btn-outline-primary">
    <i class="bi bi-graph-up-arrow"></i> Dashboard
  </a>
</div>

<?= $locked ? '<div class="alert alert-danger">Data tahun ini sudah lengkap dan dikunci</div>' : '' ?>
<?= $message ?>

<!-- KELOLA KATEGORI -->
<div class="card mb-4">
<div class="card-body">
<h6 class="fw-bold mb-3"><i class="bi bi-tags"></i> Kelola Kategori</h6>

<form method="post" class="row g-2 mb-3">
<div class="col-md-4">
<input name="category_name" class="form-control" placeholder="Nama kategori baru" required>
</div>
<div class="col-md-2">
<button name="add_category" class="btn btn-success w-100">
<i class="bi bi-plus-circle"></i> Tambah
</button>
</div>
</form>

<table class="table table-sm">
<tr><th>Kategori</th><th width="180">Aksi</th></tr>
<?php
$q=$conn->query("SELECT * FROM categories ORDER BY name");
while($c=$q->fetch_assoc()):
$used=$conn->query("
  SELECT COUNT(*) t FROM sales WHERE category_id=".$c['id']
)->fetch_assoc()['t'];
?>
<tr>
<td>
<form method="post" class="d-flex gap-2">
<input type="hidden" name="category_id" value="<?= $c['id'] ?>">
<input name="category_name" value="<?= htmlspecialchars($c['name']) ?>" class="form-control form-control-sm">
<button name="edit_category" class="btn btn-warning btn-sm">
<i class="bi bi-pencil"></i>
</button>
</form>
</td>
<td class="text-center">
<?php if($used==0): ?>
<a href="?delete_category=<?= $c['id'] ?>" class="btn btn-danger btn-sm"
 onclick="return confirm('Hapus kategori <?= $c['name'] ?> ?')">
<i class="bi bi-trash"></i>
</a>
<?php else: ?>
<span class="badge bg-secondary">Digunakan</span>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>

<!-- INPUT PENJUALAN -->
<div class="card">
<div class="card-body">

<form method="get" class="row g-2 mb-3">
<div class="col-md-3">
<select name="category_id" class="form-select" required>
<option value="">Pilih kategori</option>
<?php
$q=$conn->query("SELECT * FROM categories ORDER BY name");
while($c=$q->fetch_assoc()){
  $s=$selectedCat==$c['id']?'selected':'';
  echo "<option value='{$c['id']}' $s>{$c['name']}</option>";
}
?>
</select>
</div>

<div class="col-md-2">
<select name="year" class="form-select" required>
<option value="">Tahun</option>
<?php
for($y=2020;$y<=date("Y");$y++){
  $s=$selectedYear==$y?'selected':'';
  echo "<option $s>$y</option>";
}
?>
</select>
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100">
<i class="bi bi-search"></i> Tampilkan
</button>
</div>
</form>

<form method="post" onsubmit="beforeSubmit()">
<input type="hidden" name="category_id" value="<?= $selectedCat ?>">
<input type="hidden" name="year" value="<?= $selectedYear ?>">

<div class="<?= $locked?'locked':'' ?>">
<table class="table text-center">
<tr><th>Bulan</th><th>Nominal</th></tr>

<?php for($m=1;$m<=12;$m++): ?>
<tr>
<td><?= $bulan[$m] ?></td>
<td>
<input
 name="sales[<?= $m ?>]"
 class="form-control rupiah text-end"
 value="<?= isset($oldData[$m])?'Rp '.number_format($oldData[$m],0,',','.') : '' ?>"
 oninput="formatRupiah(this)"
 <?= $locked?'disabled':'' ?>
>
</td>
</tr>
<?php endfor; ?>

</table>
</div>

<div class="text-end">
<button
 name="save_sales"
 class="btn btn-primary px-4"
 <?= ($locked || !$selectedCat || !$selectedYear)?'disabled':'' ?>
>
<i class="bi bi-save"></i> Simpan
</button>
</div>

</form>

</div>
</div>

</div>
</body>
</html>