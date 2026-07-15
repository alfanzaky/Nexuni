# Target Architecture

> **Target Architecture** menggambarkan arsitektur akhir yang ingin dicapai oleh Nexuni. Dokumen ini bukan berarti seluruh komponen harus dibangun sejak awal, tetapi menjadi acuan jangka panjang agar setiap pengembangan tetap berada pada jalur yang benar.

---

# Gambaran Arsitektur

```text
                                ┌────────────────────────────┐
                                │        Internet            │
                                └─────────────┬──────────────┘
                                              │
                                ┌─────────────▼──────────────┐
                                │        API Gateway         │
                                │ (Nginx / Traefik / Envoy)  │
                                └─────────────┬──────────────┘
                                              │
                    ┌─────────────────────────┴────────────────────────┐
                    │                                                  │
                    ▼                                                  ▼
        ┌──────────────────────────┐                    ┌──────────────────────────┐
        │      Laravel Core        │                    │ Go Transaction Engine    │
        └─────────────┬────────────┘                    └─────────────┬────────────┘
                      │                                               │
                      │                                               │
          ┌───────────▼───────────┐                      ┌────────────▼────────────┐
          │     PostgreSQL        │                      │       RabbitMQ          │
          └───────────┬───────────┘                      └────────────┬────────────┘
                      │                                               │
                      │                                               │
                      ▼                                               ▼
              Wallet Ledger                               Supplier Connectors
              Users                                       Retry Worker
              Products                                    Circuit Breaker
              Pricing                                     Webhook Receiver
              Deposits                                    H2H Gateway
              Transactions                                Scheduler

```

---

# Arsitektur Secara Umum

Nexuni dibangun menggunakan pendekatan **Hybrid Architecture**, yaitu kombinasi antara aplikasi bisnis berbasis Laravel dan mesin pemrosesan transaksi berbasis Go.

Masing-masing memiliki tanggung jawab yang berbeda dan tidak saling mengambil alih peran.

Laravel bertindak sebagai pusat seluruh aturan bisnis (*Business Authority*), sedangkan Go bertugas sebagai mesin eksekusi transaksi berperforma tinggi (*Transaction Execution Engine*).

---

# Komponen Utama

## 1. API Gateway

API Gateway merupakan pintu masuk seluruh request dari luar sistem.

Semua request dari:

- Web Dashboard
- Mobile App
- Mitra H2H
- Internal Service

akan melewati API Gateway.

### Tanggung Jawab

- SSL Termination
- Load Balancing
- Rate Limiting
- Routing Request
- Reverse Proxy
- Logging
- Security Layer

API Gateway tidak boleh memiliki business logic.

---

## 2. Laravel Core

Laravel merupakan pusat seluruh logika bisnis.

Laravel adalah **Single Source of Truth** bagi seluruh data bisnis.

Semua perubahan data bisnis wajib melalui Laravel.

### Domain yang Dimiliki

- Authentication
- User Management
- Wallet
- Wallet Ledger
- Deposit
- Product Catalog
- Product Pricing
- Transactions
- Commission
- Reporting
- Dashboard Admin

Laravel bertanggung jawab terhadap:

- Validasi bisnis
- Integritas data
- Mutasi saldo
- Perhitungan komisi
- Audit transaksi

Laravel **tidak** bertanggung jawab menjalankan request ke supplier.

---

## 3. Go Transaction Engine

Go bertugas sebagai mesin pemrosesan transaksi.

Go dioptimalkan untuk:

- konkurensi tinggi
- penggunaan memori rendah
- latensi rendah

Go tidak menyimpan aturan bisnis.

Go hanya menjalankan proses transaksi.

### Komponen Go

- Queue Consumer
- Supplier Connector
- H2H API
- Retry Worker
- Circuit Breaker
- Scheduler
- Webhook Receiver
- Timeout Handler
- Rate Limiter

Go menerima event dari Laravel kemudian menjalankan transaksi ke supplier.

---

## 4. PostgreSQL

PostgreSQL menjadi database utama untuk seluruh data bisnis.

Database ini hanya dimiliki oleh Laravel.

Isi database antara lain:

- users
- wallets
- wallet_ledgers
- transactions
- deposits
- products
- pricing
- commissions

Go tidak diperbolehkan mengakses database ini secara langsung.

Semua akses dilakukan melalui API internal atau gRPC.

---

## 5. RabbitMQ

RabbitMQ menjadi tulang punggung komunikasi asynchronous.

RabbitMQ digunakan untuk:

- Transaction Queue
- Retry Queue
- Dead Letter Queue
- Event Distribution

RabbitMQ memungkinkan Laravel dan Go saling berkomunikasi tanpa ketergantungan langsung.

---

## 6. Internal API (gRPC)

Komunikasi synchronous antar service menggunakan gRPC.

Digunakan untuk proses yang membutuhkan respons langsung.

Contohnya:

- Validasi Wallet
- Pricing
- Status Transaksi
- Validasi User

REST API tidak digunakan untuk komunikasi internal.

---

# Ownership

| Domain | Pemilik |
|----------|----------|
| Authentication | Laravel |
| Users | Laravel |
| Wallet | Laravel |
| Wallet Ledger | Laravel |
| Deposit | Laravel |
| Products | Laravel |
| Pricing | Laravel |
| Commission | Laravel |
| Reporting | Laravel |
| Transaction Record | Laravel |
| Supplier Connector | Go |
| Retry Logic | Go |
| Queue Consumer | Go |
| Webhook | Go |
| Circuit Breaker | Go |
| Scheduler | Go |
| H2H Gateway | Go |

---

# Prinsip Ownership

Setiap domain hanya memiliki satu pemilik.

Service lain tidak boleh mengubah data milik domain tersebut secara langsung.

Sebagai contoh:

- Go tidak boleh mengubah saldo wallet.
- Go tidak boleh menghitung komisi.
- Go tidak boleh mengubah pricing.
- Laravel tidak boleh langsung menghubungi supplier.

Dengan prinsip ini, setiap domain memiliki batas tanggung jawab yang jelas sehingga sistem lebih mudah dipelihara dan dikembangkan.

---

# Alur Komunikasi

## Request Masuk

```text
Client

↓

API Gateway

↓

Laravel
```

---

## Eksekusi Transaksi

```text
Laravel

↓

RabbitMQ

↓

Go Engine

↓

Supplier
```

---

## Callback Supplier

```text
Supplier

↓

Go Engine

↓

Laravel
```

---

## Mutasi Saldo

```text
Laravel

↓

Wallet Ledger

↓

Database
```

---

# Prinsip Desain

Arsitektur Nexuni dibangun berdasarkan prinsip berikut:

- Single Source of Truth
- Domain Ownership
- Event-Driven Communication
- High Availability
- Horizontal Scalability
- Financial Integrity
- Loose Coupling
- Observability
- Security by Design

Setiap keputusan teknis pada masa mendatang harus tetap mengikuti prinsip-prinsip tersebut agar arsitektur tetap konsisten seiring pertumbuhan platform.