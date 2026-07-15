# Folder Structure

> Dokumen ini menjelaskan standar struktur folder Nexuni.
>
> Struktur folder dirancang berdasarkan domain bisnis, bukan hanya berdasarkan tipe file.
>
> Tujuan utama:
>
> - Memisahkan tanggung jawab setiap domain
> - Mengurangi coupling antar fitur
> - Memudahkan scaling menjadi service terpisah
> - Memudahkan developer dan AI Agent memahami lokasi kode

---

# Architectural Principle

Nexuni menggunakan pendekatan:

```
Domain First Organization
```

Artinya struktur mengikuti:

```
Business Domain

bukan

Technical Layer
```

---

# Root Repository Structure

```
Nexuni/

├── core/
├── engine/
├── ui/
│   ├──mobile/
│   └── web/
├── docs/
├── infrastructure/
├── scripts/
├── tests/
└── README.md
```

---

# Core Application

Lokasi:

```
core/
```

Berisi Laravel Core Application.

Tanggung jawab:

- Business Logic
- API
- Database
- Authentication
- Wallet
- Transaction Management

---

# Laravel Core Structure

```
core/

├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
├── composer.json
└── artisan
```

---

# Domain Structure

Lokasi:

```
app/Domains/
```

Setiap business domain memiliki folder sendiri.

Contoh:

```
app/

└── Domains/

    ├── Identity/

    ├── Reseller/

    ├── Wallet/

    ├── Product/

    ├── Pricing/

    ├── Transaction/

    ├── Supplier/

    ├── Commission/

    └── Reporting/
```

---

# Domain Folder Pattern

Setiap domain menggunakan struktur:

```
Domain/

├── Models/

├── Services/

├── Actions/

├── DTOs/

├── Events/

├── Listeners/

├── Exceptions/

├── Policies/

├── Rules/

├── Jobs/

└── Tests/
```

---

# Contoh Wallet Domain

```
Wallet/

├── Models/

│   ├── Wallet.php
│   └── WalletLedger.php


├── Services/

│   └── WalletLedgerService.php


├── Actions/

│   ├── HoldBalance.php
│   ├── CaptureBalance.php
│   └── RefundBalance.php


├── Events/

│   ├── WalletHeld.php
│   └── WalletRefunded.php


├── Exceptions/

└── Tests/
```

---

# Identity Domain

Bertanggung jawab:

- Authentication
- User
- Role
- Permission

Structure:

```
Identity/

├── Models/

│   └── User.php


├── Services/

│   └── AuthenticationService.php


├── Policies/

├── Actions/

└── Tests/
```

---

# Reseller Domain

Bertanggung jawab:

- Reseller
- Group reseller
- Status reseller

```
Reseller/

├── Models/

│   ├── Reseller.php
│   └── ResellerGroup.php


├── Services/

├── Actions/

└── Tests/
```

---

# Product Domain

Bertanggung jawab:

- Provider
- Product
- Category

```
Product/

├── Models/

│   ├── Product.php
│   ├── Provider.php
│   └── Category.php


├── Services/

├── Actions/

└── Tests/
```

---

# Transaction Domain

Domain paling penting.

```
Transaction/

├── Models/

│   └── Transaction.php


├── Services/

│   └── TransactionService.php


├── Actions/

│   ├── CreateTransaction.php
│   ├── CompleteTransaction.php
│   └── FailTransaction.php


├── Events/

│   ├── TransactionCreated.php
│   ├── TransactionSuccess.php
│   └── TransactionFailed.php


├── Jobs/

└── Tests/
```

---

# Supplier Domain

Bertanggung jawab terhadap konfigurasi supplier.

Tidak menjalankan request supplier.

```
Supplier/

├── Models/

├── Services/

├── Contracts/

│   └── SupplierConnector.php


├── DTOs/

└── Tests/
```

---

# Infrastructure Layer

Lokasi:

```
app/Infrastructure/
```

Berisi implementasi teknis.

Contoh:

```
Infrastructure/

├── Database/

├── Queue/

├── Cache/

├── Http/

├── Logging/

└── Security/
```

---

# Shared Components

Lokasi:

```
app/Shared/
```

Berisi komponen yang digunakan banyak domain.

Contoh:

```
Shared/

├── Exceptions/

├── ValueObjects/

├── Helpers/

├── Traits/

└── Enums/
```

---

# API Structure

Lokasi:

```
app/Http/
```

Struktur:

```
Http/

├── Controllers/

│   ├── Api/

│   └── Admin/


├── Requests/

├── Resources/

├── Middleware/

└── Responses/
```

Controller hanya menangani:

- Request
- Validation
- Response

Business logic tetap berada di Domain.

---

# Database Structure

```
database/

├── migrations/

├── factories/

├── seeders/

└── schemas/
```

---

# Migration Naming

Format:

```
YYYY_MM_DD_create_domain_table.php
```

Contoh:

```
2026_01_01_create_wallets_table.php

2026_01_02_create_wallet_ledgers_table.php
```

---

# Go Engine Structure

Lokasi:

```
engine/
```

Tanggung jawab:

- Transaction Execution
- Supplier Communication
- Queue Processing

---

Structure:

```
engine/

├── cmd/

│   └── server/


├── internal/

│
├── domain/

│
├── supplier/

│
├── queue/

│
├── grpc/

│
├── webhook/

│
├── config/

│
├── pkg/

└── tests/
```

---

# Go Engine Domain

```
internal/

├── transaction/

├── supplier/

├── routing/

├── retry/

└── circuitbreaker/
```

---

# UI - Structure

```
ui/

├── web/

│   ├── admin/
│   ├── reseller/
│   └── h2h-dashboard/


└── mobile/

    ├── android/

    └── ios/
```

---

# Go Engine Domain

```
internal/

├── transaction/

├── supplier/

├── routing/

├── retry/

└── circuitbreaker/
```

---

# Documentation Structure

Lokasi:

```
docs/
```

Structure:

```
docs/

├── architecture/

│
│── vision.md
│── evolution-roadmap.md
│── target-architecture.md
│── role-separation.md
│── transaction-lifecycle.md
│── communication-architecture.md
│── database-strategy.md
│── reliability.md
│── security.md
│── deployment-strategy.md
│── observability.md
│── development-roadmap.md
│── folder-structure.md
│
├── api/

├── database/

├── guides/

└── decisions/
```

---

# Architecture Decision Records

Lokasi:

```
docs/decisions/
```

Berisi keputusan penting.

Contoh:

```
ADR-001-use-postgresql.md

ADR-002-use-rabbitmq.md

ADR-003-wallet-ledger-design.md
```

---

# Test Structure

Testing mengikuti domain.

Contoh:

```
tests/

├── Feature/

│   ├── Wallet/

│   ├── Transaction/

│   └── Authentication/


└── Unit/

    ├── Pricing/

    └── Services/
```

---

# Rules

## Domain Rules

1. Domain tidak boleh mengakses database domain lain secara langsung.
2. Komunikasi antar domain menggunakan service atau event.
3. Business logic tidak boleh berada di controller.
4. Model tidak boleh menjadi tempat seluruh logic bisnis.

---

## AI Agent Rules

AI Agent wajib:

1. Membaca folder structure sebelum membuat file.
2. Menempatkan kode sesuai domain.
3. Tidak membuat folder baru tanpa alasan.
4. Tidak mencampurkan domain.
5. Mengikuti naming convention.

---

# Evolution Compatibility

Struktur ini dirancang agar domain dapat dipisahkan menjadi service.

Contoh:

Saat ini:

```
app/Domains/Wallet
```

Future:

```
wallet-service/
```

Tanpa perlu menulis ulang seluruh business logic.

---

# Kesimpulan

Folder Structure Nexuni mengikuti prinsip:

```
Organize by Business Capability
```

Bukan:

```
Organize by File Type
```

Dengan struktur ini Nexuni dapat berkembang dari modular monolith menjadi platform enterprise tanpa harus melakukan rewrite besar.