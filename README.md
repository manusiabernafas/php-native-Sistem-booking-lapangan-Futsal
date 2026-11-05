# Sistem Booking Lapangan Futsal (PHP Native + MySQL)

Sistem Booking Lapangan Futsal berbasis PHP Native dan MySQL untuk pengelola GOR atau arena futsal yang masih mencatat jadwal di buku atau grup WhatsApp. Semua jadwal lapangan tampil dalam satu grid per jam, jadi admin langsung tahu slot mana yang kosong, terisi, sudah DP, atau belum dibayar. Klik slot kosong, isi nama tim, dan booking langsung tersimpan.

Sistem otomatis menolak booking yang bentrok jam, menghitung tarif siang dan malam secara terpisah (misalnya main 16.00 sampai 19.00 dihitung 1 jam siang + 2 jam malam), mencatat DP dan pelunasan, mencetak struk, serta menyiapkan pesan konfirmasi WhatsApp ke pelanggan. Ada juga halaman jadwal publik tanpa login yang bisa dibagikan ke pelanggan agar mereka bisa cek slot kosong sendiri. Cocok untuk usaha futsal, mini soccer, badminton, atau tugas kuliah dan skripsi.

## Fitur

- Grid jadwal per jam x lapangan dengan warna status (lunas, DP, belum bayar, selesai)
- Klik slot kosong di grid untuk langsung membuat booking
- Validasi anti bentrok jadwal dengan penguncian database (aman dipakai beberapa kasir sekaligus)
- Tarif siang dan tarif malam per lapangan, dihitung otomatis per jam
- Pembayaran DP dan pelunasan, dengan minimal DP yang bisa diatur
- Daftarkan pelanggan baru langsung dari form booking
- Struk booking siap cetak
- Tombol kirim konfirmasi booking ke WhatsApp pelanggan (pesan otomatis terisi)
- Halaman jadwal publik tanpa login untuk pelanggan, dengan tombol booking via WhatsApp
- Dashboard: booking hari ini, okupansi, omzet bulan ini, tagihan belum dibayar
- Grafik omzet 14 hari dan jam paling ramai
- Manajemen lapangan: jenis (Vinyl, Sintetis, Interlock, Parket), ukuran, status perbaikan
- Data pelanggan dan tim, status member, riwayat dan total booking
- Laporan omzet per lapangan, per hari, dan tim paling sering main
- Ekspor laporan ke CSV (Excel) dan cetak
- Pengaturan jam buka, jam tutup, jam mulai tarif malam, dan profil usaha
- 2 role pengguna: Admin dan Kasir
- Keamanan: PDO prepared statement, password_hash, proteksi CSRF
- Data contoh 60 hari (800+ booking) supaya dashboard dan laporan langsung terisi
- Tanpa framework dan tanpa Composer, langsung jalan di XAMPP

## Instalasi

**Kebutuhan:** PHP 8.0 atau lebih baru dan MySQL/MariaDB (XAMPP atau Laragon).

1. Ekstrak ZIP, lalu pindahkan folder `booking-futsal` ke:
   - XAMPP: `C:\xampp\htdocs\booking-futsal`
   - Laragon: `C:\laragon\www\booking-futsal`

2. Jalankan **Apache** dan **MySQL**.

3. Buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database/booking_futsal.sql` → **Import**. Database `booking_futsal` dan data contohnya dibuat otomatis.

   Lewat terminal:
   ```bash
   mysql -u root -p < database/booking_futsal.sql
   ```

4. Jika user/password MySQL kamu berbeda, ubah `config/database.php`:
   ```php
   return [
       'host'     => 'localhost',
       'port'     => 3306,
       'database' => 'booking_futsal',
       'username' => 'root',
       'password' => '',
   ];
   ```

5. Buka `http://localhost/booking-futsal` dan login:
   - Admin: `admin` / `admin123`
   - Kasir: `kasir` / `admin123`

6. Halaman jadwal publik untuk pelanggan (tanpa login):
   ```
   http://localhost/booking-futsal/index.php?page=publik
   ```

**Tanpa XAMPP:** dari dalam folder project jalankan `php -S localhost:8000` lalu buka `http://localhost:8000`.

Setelah login, ganti nama usaha, alamat, nomor WhatsApp, dan jam operasional di menu **Pengaturan**.

## Struktur Folder

```
booking-futsal/
├── index.php              # Router utama (index.php?page=...)
├── config/database.php    # Koneksi database
├── includes/              # Helper (harga, cek bentrok), layout
├── pages/                 # jadwal, booking, lapangan, pelanggan, laporan, publik
├── assets/                # CSS & JavaScript
└── database/booking_futsal.sql
```

---
Source code ini dibagikan gratis oleh **Papicode** — https://papicode.id
