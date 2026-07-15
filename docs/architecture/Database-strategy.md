# Database Strategy

> Dokumen ini menjelaskan strategi pengelolaan database Nexuni, mulai dari fase awal pengembangan hingga arsitektur enterprise.
>
> Fokus utama strategi database adalah menjaga:
>
> - Integritas finansial
> - Konsistensi data
> - Auditability
> - Skalabilitas
> - Kemudahan evolusi sistem

---

# Tujuan

Database Nexuni dirancang untuk memenuhi kebutuhan platform PPOB dengan karakteristik:

- Transaksi finansial tinggi
- Banyak reseller
- Banyak supplier
- Riwayat transaksi panjang
- Kebutuhan audit
- Kebutuhan rekonsiliasi
- Kebutuhan reporting

Database bukan hanya tempat menyimpan data, tetapi menjadi fondasi utama kepercayaan sistem.

---

# Prinsip Utama Database

## 1. Single Source of Truth

Setiap data bisnis hanya memiliki satu pemilik.

Contoh:

| Data | Owner |
|------|-------|
| User | Laravel Core |
| Reseller | Laravel Core |
| Wallet | Laravel Core |
| Wallet Ledger | Laravel Core |
| Product | Laravel Core |
| Pricing | Laravel Core |
| Transaction Record | Laravel Core |
| Supplier Execution Log | Go Engine |

Tidak boleh ada dua database yang menjadi sumber kebenaran untuk data yang sama.

---

# 2. Financial Data First

Data yang berhubungan dengan uang harus memiliki tingkat perlindungan tertinggi.

Contoh:

- Wallet
- Ledger
- Deposit
- Refund
- Commission

Semua perubahan finansial wajib:

- menggunakan database transaction
- tercatat dalam ledger
- memiliki audit trail
- dapat direkonstruksi

---

# 3. Ledger Sebagai Sumber Kebenaran

Saldo wallet bukan sumber utama.

Ledger adalah sumber utama.

Contoh:

Wallet:

```
available_balance = 95000
```

hanyalah hasil perhitungan dari:

```
Wallet Ledger

+100000 Deposit

-5000 Transaction

=95000 Balance
```

Apabila terjadi perbedaan:

Ledger selalu menjadi referensi utama.

---

# Fase Database Evolution

## Phase 1 — Single Database (Modular Monolith)

Pada tahap awal Nexuni menggunakan satu database utama.

Arsitektur:

```
Laravel Core

        |

        ▼

PostgreSQL

        |

        ├── Users
        ├── Wallets
        ├── Ledgers
        ├── Products
        ├── Pricing
        ├── Transactions
        └── Reports
```

---

## Karakteristik

- Satu PostgreSQL instance
- Foreign key aktif
- Transaction ACID
- Relasi database kuat
- Backup sederhana

---

## Keuntungan

- Konsistensi tinggi
- Development cepat
- Mudah debugging
- Cocok untuk tahap awal

---

# Phase 2 — Database Optimization

Ketika transaksi meningkat, dilakukan optimasi tanpa mengubah ownership.

Optimasi:

- Index optimization
- Query optimization
- Table partitioning
- Read replica
- Cache layer
- Background processing

---

Contoh:

Transaction table:

```
transactions

2026
 ├── January
 ├── February
 ├── March
```

dapat dipartisi berdasarkan:

- tanggal
- status
- reseller

---

# Phase 3 — Hybrid Laravel + Go

Ketika Go Transaction Engine mulai digunakan:

Database tetap dimiliki Laravel.

Arsitektur:

```
Laravel Core

        |

        ▼

PostgreSQL


Go Engine

        |

        ▼

RabbitMQ / Redis
```

---

## Aturan Penting

Go TIDAK BOLEH:

- mengubah wallet
- mengubah ledger
- menulis transaksi bisnis langsung
- mengakses tabel Laravel

---

Go hanya menyimpan data teknis seperti:

- execution log
- supplier response
- retry state
- metrics

---

# Phase 4 — Database per Service

Apabila Nexuni sudah membutuhkan distributed services, database dapat dipisahkan.

Contoh:

```
Wallet Service

    |

Wallet Database


Transaction Service

    |

Transaction Database


Reporting Service

    |

Analytics Database
```

---

# Database Ownership

Setiap service memiliki database sendiri.

Service lain tidak boleh melakukan query langsung.

Komunikasi menggunakan:

- API
- gRPC
- Event

---

# Shared Database Rule

Shared database hanya diperbolehkan pada tahap awal.

Ketika service sudah dipisahkan:

Tidak diperbolehkan:

```
Service A

    |

    ▼

Database Service B
```

Yang benar:

```
Service A

    |

 API / Event

    |

Service B
```

---

# Database Technology

## Primary Database

Rekomendasi:

```
PostgreSQL
```

Alasan:

- ACID transaction
- Strong consistency
- JSON support
- Indexing kuat
- Cocok untuk financial system

---

## Cache

Menggunakan:

```
Redis
```

Untuk:

- Session
- Rate limiting
- Temporary data
- Cache pricing
- Queue support

Redis bukan sumber data utama.

---

## Message Storage

Menggunakan:

```
RabbitMQ
```

Untuk:

- Event delivery
- Queue
- Retry
- Dead Letter Queue

RabbitMQ bukan database transaksi.

---

# Transaction Safety

Setiap operasi finansial harus menggunakan database transaction.

Contoh:

```
BEGIN TRANSACTION

1. Lock Wallet

2. Check Balance

3. Create Ledger

4. Update Balance

5. Create Transaction

COMMIT
```

Jika gagal:

```
ROLLBACK
```

---

# Locking Strategy

Untuk wallet digunakan:

## Pessimistic Lock

Contoh:

```
SELECT wallet
FOR UPDATE
```

Tujuan:

Mencegah dua transaksi menggunakan saldo yang sama.

---

# Backup Strategy

Database wajib memiliki:

## Daily Backup

- Full backup

## Continuous Backup

- WAL Archiving

## Disaster Recovery

- Restore testing
- Backup verification

Backup yang tidak pernah diuji hanyalah harapan yang diberi nama teknis.

---

# Migration Strategy

Semua perubahan schema wajib melalui migration.

Tidak diperbolehkan:

- edit database manual production
- perubahan tanpa version control

---

# Data Retention

Data transaksi tidak boleh dihapus.

Data finansial harus immutable.

Untuk data besar:

gunakan:

- Archive table
- Partition
- Cold storage

---

# Audit Requirement

Data berikut wajib memiliki audit:

- Wallet
- Ledger
- Deposit
- Transaction
- Commission
- Admin Action

---

# Database Rules

Developer wajib mengikuti aturan:

1. Jangan menyimpan saldo sebagai satu-satunya sumber kebenaran.
2. Jangan mengubah ledger yang sudah tercatat.
3. Jangan menghapus transaksi finansial.
4. Jangan mengakses database service lain langsung.
5. Semua schema change melalui migration.
6. Semua transaksi finansial harus atomic.
7. Semua data penting harus dapat diaudit.

---

# Kesimpulan

Strategi database Nexuni berfokus pada keseimbangan antara:

- Konsistensi finansial
- Kemudahan pengembangan
- Skalabilitas
- Evolusi arsitektur

Nexuni tidak memulai dengan database terdistribusi yang kompleks.

Nexuni membangun fondasi yang benar terlebih dahulu, kemudian memisahkan database hanya ketika kebutuhan bisnis dan skala benar-benar membutuhkan.
