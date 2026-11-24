## Mewajibkan verifikasi dua langkah

1. Di menu kiri, pilih **Keamanan**.
2. Centang peran yang wajib memakai verifikasi dua langkah. Angka di kanan menunjukkan berapa pengguna yang sudah mengaktifkannya.
3. Tekan **Simpan**.

Pengguna dengan peran tersebut akan diarahkan ke halaman pengaktifan sebelum bisa memakai dasbor.

> **Tips:** Wajibkan dulu untuk Super Admin dan Admin Sekolah, karena akun ini dapat mengubah data paling banyak.

## Menyalakan atau mematikan fitur

Matikan fitur yang tidak dipakai sekolah agar menu tetap ringkas.

1. Di menu kiri, pilih **Fitur**.
2. Geser tombol di samping fitur ke **Menyala** atau **Mati**.
3. Tekan **Simpan Perubahan**, lalu pilih **OK** pada pesan konfirmasi.

Fitur yang dimatikan langsung hilang dari menu, halaman, dan panduan semua petugas. Datanya tetap tersimpan dan muncul lagi saat fitur dinyalakan kembali.

> **Tips:** Fitur dasar (Pengaturan, Pengguna, Profil Saya, Panduan, Riwayat Perubahan) selalu menyala.

- Jika fitur yang baru dinyalakan belum muncul di menu, maka muat ulang halaman.

## Melihat kondisi server

Halaman ini membantu memeriksa apakah server siap dipakai, misalnya apakah foto dari ponsel bisa diunggah.

1. Di menu kiri, pilih **Kondisi server**.
2. Lihat kotak di atas: hijau berarti aman, kuning perlu diperhatikan, merah ada masalah.
3. Buka bagian yang berwarna kuning atau merah untuk melihat barisnya. Keterangan kecil di bawah nama baris menjelaskan apa yang perlu dilakukan.

Angka **Berkas terbesar yang bisa diunggah sekarang** harus sama atau lebih besar dari batas berkas terbesar di **Berkas Wajib**. Batas PHP diatur di `docker/php/spmb.ini`, batas Nginx di `docker/nginx/spmb.conf`; setelah mengubahnya jalankan `spmb deploy staging`, periksa, lalu `spmb deploy production`.

> **Tips:** Halaman ini hanya membaca. Tidak ada pengaturan yang berubah saat halaman dibuka.
