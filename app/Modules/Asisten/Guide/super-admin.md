## Mengatur koneksi AI untuk Tanya Asisten

Jawaban AI dipakai Tanya Asisten hanya jika pertanyaan pengunjung tidak cocok dengan Tanya Jawab sekolah. Penyedia AI apa pun yang cocok dengan OpenAI (endpoint `/chat/completions`) bisa dipakai, misalnya OpenAI, OpenRouter, atau Groq.

1. Di menu kiri, pada bagian **Pengaturan**, pilih **Koneksi AI**.
2. Tekan **Tambah koneksi**.
3. Isi **Nama koneksi**, **Alamat dasar (base URL)** yang diawali `https://` (tanpa `/chat/completions`), dan **Model**.
4. Tempel **API key**. Key disimpan terenkripsi dan tidak ditampilkan lagi; halaman hanya menunjukkan 4 huruf terakhirnya.
5. Atur **Batas waktu**, **Token jawaban paling banyak**, dan **Temperature** bila perlu. Nilai bawaan sudah cukup untuk jawaban pendek.
6. Tekan **Simpan koneksi**, lalu tekan **Tes koneksi** di daftar untuk memastikan koneksinya berjalan.

Beberapa koneksi dipakai bergantian. Jika satu koneksi gagal (kuota habis, error server, waktu habis), koneksi berikutnya langsung dicoba dan yang gagal **istirahat** beberapa menit. Atur urutannya dengan tombol panah, dan matikan koneksi yang tidak dipakai dengan **Matikan**.

> **Tips:** Untuk mengganti API key, buka **Ubah** lalu tempel key baru. Biarkan kolom API key kosong untuk tetap memakai key yang tersimpan.

- Jika muncul peringatan kunci enkripsi belum diisi, maka isi `encryption.key` di `.env` server (`php spark key:generate`) sebelum menyimpan API key. Jangan mengganti kunci ini setelah API key tersimpan, karena key lama tidak bisa dibaca lagi.
- Jika **Tes koneksi** gagal dengan "API key ditolak", maka periksa key dan saldo di penyedia. Jika gagal dengan "Alamat atau model tidak ditemukan", maka periksa alamat dasar dan nama model.

## Menyalakan jawaban AI dan mengatur batas

1. Buka **Pengaturan → Koneksi AI**.
2. Centang **Nyalakan jawaban AI**. Jika dimatikan, asisten tetap menjawab dengan pilihan cepat dan pencarian Tanya Jawab.
3. Atur **Jawaban AI per pengunjung per hari** (bawaan 10) dan **Jawaban AI seluruh website per hari** (bawaan 300). Hanya jawaban AI yang dihitung.
4. Atur **Pertanyaan per pengunjung per menit** untuk mencegah penyalahgunaan, dan **Panjang pertanyaan paling banyak**.
5. Jika perlu, tulis **Instruksi tambahan untuk AI**, misalnya gaya bahasa. Aturan dasar tetap berlaku: AI hanya menjawab soal SPMB sekolah ini, tidak meminta data pribadi, dan tidak bisa melihat data pendaftar.
6. Tekan **Simpan**.

Setelah batas tercapai, pengunjung melihat pesan ramah dengan tombol **Lanjut lewat WhatsApp**. Kalimat di atas kotak menunjukkan berapa jawaban AI yang sudah dipakai hari ini.

> **Tips:** Widget di website dinyalakan oleh Admin Sekolah di **Website → Tanya Asisten**. Jawaban AI baru terpakai setelah widget itu menyala.
