Catatan Keuangan (PHP + MySQL)
Aplikasi pencatat keuangan dark mode dengan pemasukan, pengeluaran, transaksi berulang, dan grafik (Chart.js).
Struktur folder
finance-tracker-php/
├── database.sql          # skema + seed kategori, import ini dulu
├── config.php            # kredensial koneksi database
├── functions.php         # semua logic (query, proses recurring)
├── index.php             # halaman dashboard
├── api/
│   ├── data.php              # GET: summary, transaksi, recurring, chart
│   ├── categories.php        # GET: daftar kategori per tipe
│   ├── add_transaction.php   # POST: tambah transaksi biasa/berulang
│   ├── delete_transaction.php# POST: hapus transaksi
│   └── delete_recurring.php  # POST: hentikan aturan berulang
└── assets/
    ├── style.css
    └── app.js

Instalasi (XAMPP / Laragon / server PHP + MySQL apa saja)
1.	Import database
o	Buka phpMyAdmin (atau mysql -u root -p)
o	Import file database.sql — ini akan membuat database finance_tracker beserta tabel dan kategori default.
2.	Atur koneksi
o	Buka config.php, sesuaikan $DB_HOST, $DB_USER, $DB_PASS dengan pengaturan server kamu.
o	Default XAMPP biasanya: host localhost, user root, password kosong.
3.	Jalankan
o	Letakkan folder finance-tracker-php di dalam htdocs (XAMPP) atau folder web root kamu.
o	Buka http://localhost/finance-tracker-php/ di browser.
4.	Atau untuk testing cepat pakai PHP built-in server (butuh PHP + ekstensi pdo_mysql aktif):
5.	cd finance-tracker-php
php -S localhost:8000

6.	lalu buka http://localhost:8000
Cara kerja transaksi berulang
·	Saat menambah transaksi, centang "Jadikan transaksi berulang", pilih frekuensi (harian/mingguan/bulanan/tahunan), dan opsional tanggal berhenti.
·	Setiap kali halaman/API diakses, processRecurring() di functions.php otomatis mengecek semua aturan berulang yang aktif: jika next_date sudah jatuh tempo (≤ hari ini), sistem membuat transaksi baru dan memajukan next_date sesuai frekuensinya — tanpa perlu cron job.
·	Jika ingin dijalankan otomatis di background (misal tiap tengah malam) tanpa menunggu ada yang membuka web, kamu bisa menjadwalkan cron job untuk memanggil api/data.php sekali sehari.
Catatan keamanan sebelum dipakai serius
Versi ini fokus pada fungsionalitas inti (belum ada login/multi-user). Sebelum dipakai di server publik, sebaiknya tambahkan:
·	Autentikasi (login) dan kolom user_id di tabel transactions & recurring_transactions.
·	CSRF token pada form.
·	Validasi & sanitasi input lebih ketat di sisi server.
·	HTTPS di server produksi.
