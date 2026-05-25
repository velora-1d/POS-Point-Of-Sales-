# Flow: Setting Notifikasi

**Role**: Owner, Supervisor

## Konfigurasi Channel Notifikasi

```
Owner buka Pengaturan → Notifikasi
  → Per jenis notifikasi:
      → Toggle aktif/tidak
      → Pilih channel: app / WA / email
      → Set penerima: role tertentu
  → Simpan
```

## Setup WhatsApp Notifikasi

```
Owner buka Pengaturan → Notifikasi → WhatsApp
  → Masukkan nomor WA bisnis
  → Pilih provider: WA Business API / Twilio / dll
  → Input credentials
  → Test kirim pesan
  → Aktifkan
```

## Template Pesan

```
Owner edit template pesan notifikasi:
  → Kasbon jatuh tempo: "Halo {nama}, kasbon Anda sebesar {nominal} jatuh tempo {tanggal}"
  → Struk digital: format struk via WA
  → Konfirmasi reservasi: "Reservasi Anda untuk {tgl} pukul {jam} telah dikonfirmasi"
```
