# Evolution Roadmap

> **"Bangun sesuai kebutuhan hari ini, rancang agar siap berkembang di masa depan."**

---

# Gambaran Umum

Nexuni dirancang sebagai platform yang terus berevolusi.

Alih-alih langsung mengadopsi arsitektur microservices yang kompleks sejak awal, Nexuni akan berkembang melalui beberapa tahapan yang terencana. Setiap fase dibangun untuk memenuhi kebutuhan bisnis saat itu sekaligus mempersiapkan fondasi bagi pertumbuhan di masa depan.

Pendekatan ini memungkinkan pengembangan berjalan lebih cepat, biaya operasional tetap efisien, dan kompleksitas sistem tetap terkendali tanpa mengorbankan kemampuan untuk berkembang.

Tujuan utama roadmap ini bukan membangun arsitektur yang rumit, tetapi membangun sistem yang dapat berkembang tanpa harus melakukan penulisan ulang (rewrite) besar-besaran.

---

# Evolusi Arsitektur

```text
Phase 1
Modular Monolith (Laravel)

        │

        ▼

Phase 2
Event-Driven Modular Monolith

        │

        ▼

Phase 3
Hybrid Laravel + Go Transaction Engine

        │

        ▼

Phase 4
Distributed Services

        │

        ▼

Phase 5
Enterprise Platform
```

Setiap fase dirancang agar tetap kompatibel dengan fase sebelumnya sehingga proses migrasi dapat dilakukan secara bertahap tanpa mengganggu logika bisnis yang sudah berjalan.

---

# Phase 1 — Modular Monolith

## Tujuan

Membangun fondasi bisnis yang kuat dengan pemisahan domain yang jelas.

Pada tahap ini seluruh logika bisnis berada dalam satu aplikasi Laravel, namun sudah dipisahkan berdasarkan domain bisnis sehingga nantinya mudah diekstrak menjadi service terpisah apabila dibutuhkan.

Target utama fase ini bukan membangun microservices, melainkan membangun aplikasi yang siap berkembang menjadi microservices.

---

## Tanggung Jawab Laravel

Laravel menangani seluruh proses bisnis utama, antara lain:

- Autentikasi dan Otorisasi
- Manajemen Pengguna
- Wallet & Ledger
- Deposit Saldo
- Katalog Produk
- Pricing
- Manajemen Transaksi
- Konfigurasi Supplier
- Komisi & Bonus
- Reporting
- Dashboard Admin
- REST API

---

## Karakteristik

- Satu codebase
- Satu deployment
- Satu database
- Struktur modular berdasarkan domain
- Service Layer yang jelas
- Domain Event
- Pengujian otomatis (Automated Testing)

---

## Keuntungan

- Pengembangan lebih cepat
- Debugging lebih mudah
- Infrastruktur sederhana
- Konsistensi data tinggi
- Biaya operasional rendah

---

## Kriteria Naik ke Phase 2

Fase berikutnya dimulai apabila:

- Domain bisnis sudah stabil.
- Wallet Ledger telah siap digunakan di production.
- Siklus transaksi sudah final.
- Integrasi supplier sudah berjalan dengan baik.
- Test coverage sudah memadai.
- Batas antar domain sudah jelas.

---

# Phase 2 — Event-Driven Modular Monolith

## Tujuan

Mengurangi ketergantungan antar modul dengan komunikasi berbasis event.

Modul tidak lagi saling memanggil secara langsung apabila proses tersebut dapat dijalankan secara asynchronous.

---

## Komponen Baru

- RabbitMQ
- Domain Event Publisher
- Queue Worker
- Retry Queue
- Dead Letter Queue (DLQ)

---

## Contoh

Daripada:

```text
TransactionService

↓

CommissionService
```

Menjadi:

```text
TransactionCompleted

↓

RabbitMQ

↓

Commission Worker
```

Dengan cara ini setiap domain menjadi lebih independen dan lebih mudah dikembangkan.

---

## Keuntungan

- Skalabilitas meningkat
- Ketahanan sistem lebih baik
- Background processing lebih optimal
- Antar domain menjadi lebih longgar (Loose Coupling)
- Kegagalan pada satu modul tidak langsung memengaruhi modul lain

---

## Kriteria Naik ke Phase 3

- Infrastruktur RabbitMQ stabil.
- Mekanisme retry telah matang.
- Kontrak event telah terdokumentasi.
- Pengiriman event terbukti andal.
- Sebagian besar proses transaksi sudah berjalan asynchronous.

---

# Phase 3 — Hybrid Laravel + Go Transaction Engine

## Tujuan

Memisahkan proses bisnis dengan proses eksekusi transaksi berperforma tinggi.

Laravel tetap menjadi pusat seluruh aturan bisnis.

Go bertugas sebagai mesin pemrosesan transaksi (Transaction Engine).

---

## Laravel Core Bertanggung Jawab Atas

- Wallet
- Ledger
- User
- Pricing
- Produk
- Deposit
- Komisi
- Reporting
- Dashboard Admin
- Seluruh Business Rules

Laravel merupakan **Single Source of Truth** untuk seluruh data bisnis.

---

## Go Transaction Engine Bertanggung Jawab Atas

- Queue Consumer
- Supplier Connector
- H2H Gateway
- Retry Worker
- Circuit Breaker
- Rate Limiter
- Webhook Receiver
- Eksekusi Transaksi
- Scheduler

Go tidak diperbolehkan mengubah saldo ataupun aturan bisnis.

Go hanya menjalankan transaksi dan mengirim hasilnya kembali ke Laravel.

---

## Komunikasi Antar Service

### Asynchronous

Menggunakan:

- RabbitMQ

Untuk:

- Event transaksi
- Retry
- Background processing

### Synchronous

Menggunakan:

- gRPC

Untuk:

- Validasi internal
- Sinkronisasi status
- Pengambilan data yang membutuhkan respons cepat

### Public API

Menggunakan:

- REST API

---

## Keuntungan

- Throughput transaksi meningkat
- Latensi lebih rendah
- Skalabilitas horizontal lebih mudah
- Beban supplier terpisah dari aplikasi utama

---

## Kriteria Naik ke Phase 4

- Go Engine telah stabil.
- Multiple instance Go telah berjalan.
- Kontrak API internal telah matang.
- Monitoring dan observability sudah lengkap.

---

# Phase 4 — Distributed Services

## Tujuan

Memisahkan domain bisnis tertentu menjadi service yang benar-benar independen.

Tidak semua domain harus menjadi microservice.

Hanya domain yang memang membutuhkan skalabilitas dan deployment terpisah yang akan dipisahkan.

---

## Kandidat Service

- Wallet Service
- Deposit Service
- Notification Service
- Reporting Service
- Commission Service
- Analytics Service

Setiap service memiliki:

- Business Logic sendiri
- API sendiri
- Database sendiri
- Siklus deployment sendiri

---

## Karakteristik

- Deployment independen
- Database per service
- Versioned API
- Distributed Tracing
- Centralized Observability

---

# Phase 5 — Enterprise Platform

## Tujuan

Menjadikan Nexuni sebagai platform PPOB berskala enterprise yang mampu menangani jutaan transaksi dengan tingkat ketersediaan (High Availability) yang tinggi.

Pada fase ini fokus utama bukan lagi menambah fitur, tetapi meningkatkan keandalan operasional sistem.

---

## Fitur Enterprise

- Multi Region Deployment
- Read Replica Database
- Horizontal Auto Scaling
- Zero Downtime Deployment
- Blue-Green Deployment
- Distributed Tracing
- OpenTelemetry
- Prometheus
- Grafana
- Loki
- Centralized Logging
- Disaster Recovery
- Backup Automation
- Multi Supplier Routing
- Smart Failover
- Dynamic Load Balancing

---

# Prinsip Evolusi

Perpindahan antar fase dilakukan berdasarkan kebutuhan bisnis, bukan karena mengikuti tren teknologi.

Sebuah fase baru hanya dimulai ketika arsitektur yang ada benar-benar menjadi bottleneck.

Tujuan Nexuni bukan membangun sistem yang paling rumit.

Tujuan Nexuni adalah membangun platform PPOB yang sederhana, mudah dipelihara, memiliki integritas finansial yang tinggi, dan mampu berkembang tanpa harus melakukan rewrite besar di masa depan.

Setiap fase harus tetap mempertahankan:

- Integritas Finansial
- Backward Compatibility
- Kepemilikan Domain yang Jelas
- Kesederhanaan Operasional
- Kemudahan Maintenance