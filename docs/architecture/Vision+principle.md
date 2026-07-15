# Visi & Prinsip

> **Versi Dokumen:** 1.0
> **Status:** Draf
> **Berlaku Untuk:** Seluruh Platform Nexuni

---

# Visi

Nexuni dirancang untuk menjadi platform PPOB (*Payment Point Online Bank*) yang modern, terukur (*scalable*), dan berkelas *enterprise*, yang mampu melayani pengguna ritel, jaringan *reseller*, dan mitra *Host-to-Host* (H2H) dengan ketersediaan tinggi (*high availability*), integritas finansial, dan pemrosesan transaksi yang andal.

Platform ini dibangun dengan mempertimbangkan evolusi jangka panjang.

Alih-alih melakukan optimasi skalabilitas terlalu dini, Nexuni memprioritaskan:

* ketepatan (*correctness*) di atas performa
* kemudahan pemeliharaan (*maintainability*) di atas kompleksitas
* integritas finansial di atas kecepatan transaksi
* arsitektur modular di atas *microservices*

Arsitektur ini harus memungkinkan sistem untuk berevolusi secara alami dari *monolith* modular menjadi arsitektur layanan terdistribusi tanpa memerlukan penulisan ulang (*rewrite*) besar-besaran.

---

# Tujuan Jangka Panjang

Arsitektur harus mendukung:

* Jutaan transaksi per hari
* Ribuan permintaan (*request*) API secara bersamaan
* Banyak *supplier upstream*
* *Failover supplier* otomatis
* Sistem finansial berbasis dompet (*wallet*)
* Hierarki *reseller* multi-level
* Integrasi H2H (*Host-to-Host*)
* Aplikasi seluler
* *Dashboard* web
* API Publik
* Layanan internal
* Penskalaan horizontal (*horizontal scaling*)
* *Deployment* tanpa waktu jeda (*zero downtime*)

Sistem harus tetap mudah dipelihara terlepas dari pertumbuhan bisnis.

---

# Filosofi Arsitektur

Nexuni mengikuti beberapa filosofi inti.

## 1. Logika Bisnis Harus Eksplisit

Aturan bisnis tidak boleh disembunyikan di dalam *controller*, *job*, *middleware*, atau layanan eksternal.

Setiap proses bisnis yang penting harus berada di dalam *service* khusus atau *class* domain.

Contoh:

* WalletLedgerService
* TransactionService
* PricingService
* CommissionService

Hal ini memastikan aturan bisnis tetap dapat diuji (*testable*) dan dapat digunakan kembali (*reusable*).

---

## 2. Integritas Finansial adalah yang Utama

Uang adalah aset paling kritis dari platform ini.

Setiap perubahan saldo harus:

* atomik (*atomic*)
* dapat dilacak (*traceable*)
* dapat diaudit (*auditable*)
* dapat dibatalkan/dikembalikan (*reversible*)

Tidak boleh ada saldo yang berubah tanpa membuat catatan buku besar (*ledger*).

Saldo dompet (*wallet*) dianggap sebagai nilai turunan dari riwayat *ledger*.

*Ledger* adalah sumber kebenaran utama (*source of truth*).

---

## 3. Sumber Kebenaran Tunggal (*Single Source of Truth*)

Setiap entitas bisnis memiliki tepat satu pemilik.

Contoh:

| Domain | Sumber Kebenaran |
| --- | --- |
| Dompet (*Wallet*) | Laravel Core |
| Buku Besar (*Ledger*) | Laravel Core |
| Pengguna (*Users*) | Laravel Core |
| Harga (*Pricing*) | Laravel Core |
| Produk | Laravel Core |
| Eksekusi *Supplier* | Go Engine |
| Antrean Ulang (*Retry Queue*) | Go Engine |
| Pemutus Arus (*Circuit Breaker*) | Go Engine |

Tidak ada layanan lain yang boleh memodifikasi data otoritatif dari layanan lain secara langsung.

---

## 4. Pemisahan Berbasis Domain (*Domain-Driven Separation*)

Sistem ini diorganisasikan berdasarkan domain bisnis alih-alih lapisan teknis.

Contoh:

* Wallet
* Transaction
* Product
* Supplier
* Deposit
* Commission
* Authentication

Setiap domain memiliki:

* model
* *service*
* validasi
* *event*
* pengujian (*tests*)

---

## 5. Komunikasi Berbasis Event (*Event-Driven Communication*)

Layanan harus berkomunikasi menggunakan *event* setiap kali komunikasi sinkron (*synchronous*) tidak diperlukan.

Contoh:

```
TransactionCreated

↓

RabbitMQ

↓

Go Engine

```

Alih-alih:

```
Laravel

↓

Panggilan HTTP Langsung

↓

Supplier

```

Keterkaitan yang longgar (*loose coupling*) meningkatkan skalabilitas dan ketahanan.

---

## 6. Sinkron Hanya Jika Diperlukan

Komunikasi sinkron internal harus dikhususkan untuk:

* validasi saldo
* autentikasi
* pengecekan harga
* konfirmasi transaksi

Protokol yang disukai:

* gRPC

REST hanya boleh diekspos untuk API publik.

---

## 7. Idempotensi Secara Bawaan (*Idempotency By Default*)

Setiap transaksi harus aman untuk diulang.

Permintaan ganda (*duplicate requests*) tidak boleh menghasilkan transaksi finansial ganda.

Setiap transaksi harus memiliki:

* ID transaksi unik
* Kunci idempotensi (*idempotency key*)
* Tanda tangan permintaan (*request signature*)

---

## 8. Keandalan di Atas Kecepatan

Sistem yang cepat namun sesekali menghilangkan uang tidak dapat diterima.

Platform ini selalu memprioritaskan:

1. ketepatan (*correctness*)
2. konsistensi
3. daya tahan (*durability*)
4. observabilitas
5. performa

---

## 9. Evolusi di Atas Penulisan Ulang (*Rewrite*)

Platform ini sengaja dirancang untuk berevolusi secara bertahap.

Fase 1

```
Laravel Modular Monolith

```

↓

Fase 2

```
Arsitektur Event-Driven

```

↓

Fase 3

```
Hybrid Laravel + Go

```

↓

Fase 4

```
Layanan Terdistribusi (Distributed Services)

```

Tidak ada satupun fase yang mengharuskan penulisan ulang logika bisnis sebelumnya.

---

## 10. Infrastruktur Harus Dapat Diganti

Logika bisnis tidak boleh bergantung secara langsung pada infrastruktur.

Contoh:

Alih-alih:

```
WalletService

↓

RabbitMQ

```

Gunakan:

```
WalletService

↓

Event Dispatcher

↓

RabbitMQ

```

Hal ini memungkinkan penggantian:

* RabbitMQ
* Redis
* Kafka
* NATS

tanpa mengubah logika bisnis.

---

# Prinsip Rekayasa (*Engineering Principles*)

Setiap fitur yang ditambahkan ke Nexuni harus memenuhi prinsip-prinsip berikut.

## Kesederhanaan (*Simplicity*)

Pilih solusi paling sederhana yang memenuhi persyaratan saat ini.

Hindari abstraksi yang tidak perlu.

---

## Skalabilitas

Setiap komponen penting harus dapat diskalakan secara horizontal.

Contoh:

* API
* *Queue Worker*
* *Supplier Connector*
* *Gateway* H2H

---

## Observabilitas

Setiap proses bisnis yang penting harus dapat diobservasi.

Termasuk:

* *log*
* metrik
* pelacakan (*traces*)
* catatan audit

Jika terjadi kegagalan, *developer* harus tahu persis alasannya.

---

## Keterujian (*Testability*)

Logika bisnis harus dapat diuji secara independen.

Hindari menempatkan logika di dalam:

* *controller*
* *route*
* *console command*

---

## Keamanan (*Security*)

Keamanan adalah kewajiban, bukan pilihan.

Setiap permintaan harus diautentikasi dan diotorisasi.

Operasi sensitif memerlukan:

* tanda tangan (*signature*)
* PIN transaksi
* pencatatan audit (*audit logging*)

---

## Kemudahan Pemeliharaan (*Maintainability*)

Kode harus dioptimalkan agar mudah dibaca.

Kontributor di masa depan harus dapat memahami arsitektur tanpa memerlukan pengetahuan khusus yang tidak tertulis (*tribal knowledge*).

---

# Bukan Tujuan (*Non-Goals*)

Hal-hal berikut sengaja tidak diprioritaskan.

* *Microservices* yang terlalu dini
* Rekayasa berlebihan (*over-engineering*)
* Keterikatan pada satu vendor (*vendor lock-in*)
* Orkestrasi kompleks sebelum validasi bisnis
* Mengoptimalkan *bottleneck* yang baru sebatas teori

---

# Definisi Kesuksesan

Nexuni dianggap sukses ketika mampu:

* memproses transaksi secara andal
* mempertahankan rekam jejak audit finansial yang lengkap
* pulih dari kegagalan dengan aman
* melakukan skala horizontal tanpa penulisan ulang arsitektur
* berintegrasi dengan berbagai *supplier*
* mendukung klien web, seluler, dan H2H secara bersamaan
* tetap dapat dipahami oleh kontributor baru

Arsitektur ini harus memungkinkan evolusi berkelanjutan tanpa mengorbankan ketepatan finansial atau kemudahan pemeliharaan jangka panjang.