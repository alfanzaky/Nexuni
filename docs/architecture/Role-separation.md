# Role Separation

> Dokumen ini menjelaskan batas tanggung jawab (Bounded Context) antara setiap komponen utama dalam arsitektur Nexuni.
>
> Setiap service memiliki domain yang jelas dan tidak diperbolehkan mengambil alih tanggung jawab service lain.
>
> Prinsip utama:
>
> **One Domain, One Owner.**

---

# Gambaran Umum

Nexuni dibangun menggunakan pendekatan **Hybrid Architecture**, yaitu kombinasi antara:

- Laravel Core
- Go Transaction Engine

Kedua komponen tersebut memiliki tujuan yang berbeda.

Laravel bertanggung jawab terhadap **aturan bisnis (Business Rules)**.

Go bertanggung jawab terhadap **eksekusi transaksi berperforma tinggi (Transaction Execution)**.

---

# Laravel Core

## Peran

Laravel merupakan pusat seluruh aturan bisnis (Business Authority).

Semua keputusan bisnis harus dibuat oleh Laravel.

Laravel menjadi **Single Source of Truth** untuk seluruh data bisnis.

---

## Bertanggung Jawab Atas

### Authentication

- Login
- Logout
- Registrasi
- Session
- API Token
- Permission
- Role
- Reseller Level

---

### User Management

- Data User
- Data Reseller
- Grup Reseller
- Profil
- Status User

---

### Wallet

- Saldo
- Hold Balance
- Refund
- Capture
- Release
- Balance Validation

---

### Wallet Ledger

- Ledger Entry
- Audit Trail
- Mutasi
- Riwayat Saldo

Seluruh perubahan saldo wajib melalui Wallet Ledger.

Tidak ada pengecualian.

---

### Product

- Master Produk
- Provider
- Kategori
- Harga Dasar
- Pricing Group
- Margin
- Markup

---

### Transaction

Laravel bertanggung jawab terhadap:

- Membuat transaksi
- Validasi transaksi
- Menentukan harga
- Hold saldo
- Mengubah status transaksi
- Refund
- Capture
- Distribusi komisi

Laravel **tidak menjalankan request ke supplier.**

---

### Deposit

- Topup
- Payment Gateway
- Approval Deposit
- Manual Adjustment

---

### Commission

- Bonus
- Cashback
- Komisi
- Upline
- Downline

---

### Reporting

- Dashboard
- Statistik
- Export
- Rekap

---

### Audit

- Activity Log
- Wallet Audit
- Transaction Audit

---

# Laravel Tidak Boleh

Laravel **tidak diperbolehkan**:

- Request langsung ke supplier
- Menjalankan retry supplier
- Menentukan failover supplier
- Mengelola circuit breaker
- Menjalankan webhook provider
- Mengelola queue transaksi supplier

Laravel hanya mengelola aturan bisnis.

---

# Go Transaction Engine

## Peran

Go bertindak sebagai mesin pemrosesan transaksi.

Go dioptimalkan untuk:

- Throughput tinggi
- Latensi rendah
- Concurrency tinggi

Go tidak memiliki business rules.

---

## Bertanggung Jawab Atas

### Queue Consumer

Mengambil transaksi dari RabbitMQ.

---

### Supplier Connector

Melakukan komunikasi ke:

- Digiflazz
- VIP
- PPOB Bank
- Supplier Internal
- Supplier Lainnya

---

### Transaction Execution

Go bertugas:

- Request supplier
- Parsing response
- Normalisasi response
- Mapping status

Go **tidak menentukan aturan bisnis.**

---

### Retry Worker

Menjalankan retry otomatis.

---

### Circuit Breaker

Menonaktifkan supplier yang gagal.

---

### Supplier Failover

Memilih supplier cadangan.

---

### Scheduler

Menjalankan:

- Retry
- Timeout
- Cleanup
- Reconciliation

---

### Webhook Receiver

Menerima callback supplier.

---

### H2H Gateway

Melayani request:

- API Mitra
- Signature Validation
- HMAC
- Rate Limit

Go tidak melakukan mutasi saldo.

---

# Go Tidak Boleh

Go **tidak diperbolehkan**:

- Mengubah wallet
- Mengubah ledger
- Menghitung komisi
- Mengubah pricing
- Mengubah data user
- Mengubah data produk
- Mengubah reseller

Go hanya melaporkan hasil transaksi.

---

# RabbitMQ

RabbitMQ bukan tempat menyimpan data.

RabbitMQ hanya menjadi media komunikasi asynchronous.

RabbitMQ bertugas:

- Queue
- Retry Queue
- Dead Letter Queue
- Event Distribution

RabbitMQ tidak memiliki business logic.

---

# PostgreSQL

Database dimiliki oleh Laravel.

Go tidak boleh membaca ataupun menulis tabel Laravel secara langsung.

Semua komunikasi data dilakukan melalui:

- RabbitMQ
- gRPC

---

# API Gateway

API Gateway bertugas:

- SSL Termination
- Reverse Proxy
- Routing
- Load Balancing
- Rate Limiting
- Logging

API Gateway tidak memiliki business logic.

---

# Ownership Matrix

| Domain | Laravel | Go |
|----------|:-------:|:--:|
| Authentication | ✅ | ❌ |
| User | ✅ | ❌ |
| Wallet | ✅ | ❌ |
| Wallet Ledger | ✅ | ❌ |
| Product | ✅ | ❌ |
| Pricing | ✅ | ❌ |
| Deposit | ✅ | ❌ |
| Commission | ✅ | ❌ |
| Reporting | ✅ | ❌ |
| Transaction Record | ✅ | ❌ |
| Queue Consumer | ❌ | ✅ |
| Supplier Connector | ❌ | ✅ |
| Retry | ❌ | ✅ |
| Circuit Breaker | ❌ | ✅ |
| Failover | ❌ | ✅ |
| Scheduler | ❌ | ✅ |
| Webhook Receiver | ❌ | ✅ |
| H2H Gateway | ❌ | ✅ |

---

# Prinsip Dasar

Setiap domain hanya memiliki **satu pemilik**.

Tidak ada dua service yang boleh memiliki kewenangan terhadap domain yang sama.

Sebagai contoh:

- Wallet hanya boleh dimiliki Laravel.
- Ledger hanya boleh dimiliki Laravel.
- Retry hanya boleh dimiliki Go.
- Supplier Connector hanya boleh dimiliki Go.

Dengan prinsip ini, Nexuni dapat berkembang menjadi sistem berskala enterprise tanpa menciptakan konflik antar service.

---

# Golden Rules

Seluruh developer maupun AI Agent yang berkontribusi pada Nexuni wajib mengikuti aturan berikut:

1. Jangan pernah mengubah saldo wallet di luar Wallet Ledger.
2. Jangan pernah mengakses supplier langsung dari Laravel.
3. Jangan pernah mengubah business rules dari Go.
4. Jangan pernah mengakses database Laravel langsung dari Go.
5. Semua komunikasi asynchronous harus melalui RabbitMQ.
6. Semua komunikasi synchronous antar service menggunakan gRPC.
7. Setiap perubahan business logic harus berada di Laravel.
8. Setiap proses eksekusi transaksi harus berada di Go.
9. Tidak ada domain yang memiliki lebih dari satu pemilik.
10. Financial Integrity selalu menjadi prioritas utama dibanding performa.