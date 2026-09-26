<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Catatan Keuangan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header>
  <div><h1>💰 Catatan Keuangan</h1><p>Kelola pemasukan, pengeluaran, dan transaksi berulang kamu</p></div>
  <div class="user-box">
    <span class="user-name">👤 <?= htmlspecialchars(currentUserName()) ?></span>
    <a href="logout.php" class="logout-link">Keluar</a>
  </div>
</header>
<div class="wrap">
  <div class="grid">
    <div>
      <div class="card">
        <h2>Tambah Transaksi</h2>
        <div class="seg" id="typeSeg">
          <button type="button" data-type="income" class="active">Pendapatan</button>
          <button type="button" data-type="expense">Pengeluaran</button>
        </div>
        <label>Jumlah (Rp)</label>
        <input type="number" id="fAmount" placeholder="0" min="0">
        <label>Kategori</label>
        <select id="fCategory"></select>
        <div class="row2">
          <div><label>Tanggal</label><input type="date" id="fDate"></div>
          <div><label>Catatan (opsional)</label><input type="text" id="fNote" placeholder="Contoh: makan siang"></div>
        </div>
        <div class="chk"><input type="checkbox" id="fRecurring"><span>Jadikan transaksi berulang</span></div>
        <div id="recurringFields">
          <label style="margin-top:0">Frekuensi</label>
          <select id="fFreq">
            <option value="daily">Harian</option>
            <option value="weekly">Mingguan</option>
            <option value="monthly" selected>Bulanan</option>
            <option value="yearly">Tahunan</option>
          </select>
          <label>Berhenti pada (opsional)</label>
          <input type="date" id="fEndDate">
        </div>
        <button class="btn" id="addBtn">Simpan Transaksi</button>
        <div id="formMsg" class="msg"></div>
      </div>

      <div class="card">
        <h2>Transaksi Berulang Aktif</h2>
        <div id="recurringList"><div class="empty">Memuat...</div></div>
      </div>
    </div>

    <div>
      <div class="summary">
        <div class="stat income"><div class="lbl">Total Pendapatan</div><div class="val mono" id="sumIncome">Rp 0</div></div>
        <div class="stat expense"><div class="lbl">Total Pengeluaran</div><div class="val mono" id="sumExpense">Rp 0</div></div>
        <div class="stat balance"><div class="lbl">Saldo</div><div class="val mono" id="sumBalance">Rp 0</div></div>
      </div>

      <div class="card charts">
        <div>
          <h2>Tren 6 Bulan Terakhir</h2>
          <div class="chart-box"><canvas id="trendChart"></canvas></div>
        </div>
        <div>
          <h2>Pengeluaran per Kategori</h2>
          <div class="chart-box"><canvas id="pieChart"></canvas></div>
        </div>
      </div>

      <div class="card">
        <h2>Riwayat Transaksi</h2>
        <div class="scrollx">
        <table>
          <thead><tr><th>Tanggal</th><th>Kategori</th><th>Catatan</th><th>Tipe</th><th style="text-align:right">Jumlah</th><th></th></tr></thead>
          <tbody id="txBody"></tbody>
        </table>
        </div>
        <div id="txEmpty" class="empty" style="display:none">Belum ada transaksi.</div>
      </div>
    </div>
  </div>
</div>
<script src="assets/app.js"></script>
</body>
</html>
