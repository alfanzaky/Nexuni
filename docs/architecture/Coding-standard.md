# Coding Standards

> Dokumen ini mendefinisikan standar penulisan kode Nexuni.
>
> Coding standard digunakan untuk menjaga konsistensi, kualitas, keamanan, dan maintainability seluruh codebase.
>
> Semua developer dan AI Agent yang berkontribusi pada Nexuni wajib mengikuti aturan ini.

---

# Core Principles

Nexuni menggunakan prinsip:

```
Readable Code

+

Predictable Structure

+

Explicit Behavior

+

Minimal Complexity
```

Kode yang baik bukan kode yang paling pendek.

Kode yang baik adalah kode yang mudah dipahami oleh orang lain.

---

# General Rules

## 1. Write Code for Humans

Kode harus mudah dibaca.

Hindari:

```php
$x = $a * $b;
```

Gunakan:

```php
$totalTransactionAmount = $price * $quantity;
```

Nama yang jelas lebih penting daripada menghemat beberapa karakter.

---

# 2. Single Responsibility Principle

Satu class harus memiliki satu tanggung jawab.

Buruk:

```php
TransactionService

- create transaction
- send email
- calculate commission
- update wallet
- generate report
```

---

Benar:

```text
TransactionService

CommissionService

NotificationService

WalletService
```

---

# 3. Avoid God Classes

Jangan membuat class yang menangani terlalu banyak hal.

Contoh yang harus dihindari:

```
SystemManager.php
```

berisi:

- user
- wallet
- transaction
- product
- report

---

# Naming Convention

# PHP / Laravel

## Class

Gunakan PascalCase.

Benar:

```php
WalletLedgerService
TransactionProcessor
SupplierConnector
```

Salah:

```php
walletledger
transaction_service
```

---

## Method

Gunakan camelCase.

Benar:

```php
createTransaction()

calculatePrice()

processRefund()
```

---

## Variable

Gunakan camelCase.

Benar:

```php
$transactionAmount

$resellerBalance
```

---

# Database Naming

## Table

Gunakan snake_case plural.

Benar:

```
wallets

wallet_ledgers

transactions
```

---

## Column

Gunakan snake_case.

Benar:

```
created_at

available_balance

transaction_status
```

---

# Domain Naming

Folder domain menggunakan PascalCase.

Contoh:

```
Domains/

├── Wallet/

├── Transaction/

├── Supplier/
```

---

# Controller Rules

Controller harus tipis.

Controller hanya:

- menerima request
- validasi input
- memanggil action/service
- mengembalikan response

---

Contoh:

```php
public function store(Request $request)
{
    $transaction = CreateTransaction::execute(
        $request->validated()
    );

    return response()->json($transaction);
}
```

---

Tidak boleh:

```php
Controller

- calculate price
- deduct wallet
- call supplier
- create ledger
```

---

# Service Rules

Service digunakan untuk business operation.

Contoh:

```
WalletLedgerService

TransactionService

PricingService
```

---

Service bertanggung jawab:

- business flow
- coordination antar object

---

# Action Pattern

Gunakan Action untuk operasi spesifik.

Contoh:

```
Actions/

CreateTransaction.php

RefundTransaction.php

HoldBalance.php
```

---

Satu Action:

```
One Action

One Purpose
```

---

# Model Rules

Model tidak boleh menjadi tempat seluruh business logic.

Model bertanggung jawab:

- relationship
- attribute casting
- basic behavior

---

Contoh:

Benar:

```php
$wallet->ledger();
```

Tidak:

```php
$wallet->processCompleteTransaction();
```

---

# DTO Standards

Data antar layer menggunakan DTO.

Contoh:

```php
TransactionData

SupplierResponseData

PaymentRequestData
```

---

Tujuan:

- explicit data structure
- mengurangi array tidak jelas

---

# Type Safety

Gunakan type declaration.

Benar:

```php
public function calculate(
    Money $amount
): Money
```

Hindari:

```php
public function calculate($amount)
```

---

# Enum Usage

Gunakan enum untuk nilai tetap.

Jangan:

```php
$status = "success";
```

---

Gunakan:

```php
TransactionStatus::SUCCESS;
```

---

Contoh:

```
TransactionStatus

SUCCESS

FAILED

PENDING
```

---

# Exception Handling

Gunakan exception khusus domain.

Contoh:

```
WalletInsufficientBalanceException

SupplierTimeoutException

InvalidTransactionException
```

---

Jangan:

```php
throw new Exception();
```

---

# Database Rules

## Never Modify Financial Data Directly

Tidak boleh:

```sql
UPDATE wallets
SET balance = balance + 10000
```

---

Gunakan:

```
WalletLedgerService

↓

Ledger Entry

↓

Balance Update
```

---

# Query Rules

Hindari:

- N+1 query
- query dalam loop
- raw SQL tanpa alasan

---

Gunakan:

- Eager Loading
- Query Builder
- Repository jika diperlukan

---

# Transaction Rules

Database transaction wajib digunakan untuk operasi finansial.

Contoh:

```php
DB::transaction(function(){

    createLedger();

    updateBalance();

});
```

---

# API Standards

## Endpoint Naming

Gunakan REST style.

Benar:

```
GET /api/products

POST /api/transactions

GET /api/transactions/{id}
```

---

Hindari:

```
/api/doTransaction
/api/getProducts
```

---

# API Response Format

Semua response menggunakan format konsisten.

Contoh:

```json
{
    "success": true,
    "data": {},
    "message": "Transaction created"
}
```

---

# Validation Rules

Semua input eksternal harus divalidasi.

Sumber input:

- User
- H2H Partner
- Supplier Callback

---

# Security Rules

Dilarang:

- hardcode password
- hardcode API key
- log sensitive data

---

Contoh:

Salah:

```php
$apiKey="secret123";
```

Benar:

```php
env('SUPPLIER_API_KEY');
```

---

# Testing Standards

Setiap fitur harus memiliki test.

Minimal:

```
Feature Test

Unit Test
```

---

Prioritas test:

1. Wallet
2. Ledger
3. Transaction
4. Pricing
5. Supplier Parser

---

# Git Commit Standards

Gunakan conventional commit.

Format:

```
type(scope): message
```

---

Contoh:

```
feat(wallet): add wallet hold system

fix(transaction): handle supplier timeout

docs(architecture): update deployment strategy
```

---

# Pull Request Rules

Setiap PR harus:

- memiliki deskripsi jelas
- menjelaskan perubahan
- memiliki test
- tidak merusak architecture rule

---

# Code Review Checklist

Reviewer mengecek:

- Apakah domain sudah benar?
- Apakah business logic berada di tempat yang tepat?
- Apakah security aman?
- Apakah test tersedia?
- Apakah database migration aman?

---

# AI Agent Coding Rules

AI Agent wajib:

1. Membaca architecture documentation.
2. Membaca issue sebelum implementasi.
3. Membuat implementation plan.
4. Tidak mengubah domain boundary.
5. Tidak membuat shortcut.
6. Menambahkan test.
7. Menjelaskan perubahan.

---

# Forbidden Practices

Dilarang:

❌ Business logic di Controller

❌ Direct wallet update

❌ Hardcoded secret

❌ Duplicate business logic

❌ Giant class

❌ Untracked database change

❌ Skip testing untuk financial logic

---

# Definition of Done

Sebuah perubahan dianggap selesai apabila:

- Code mengikuti standard
- Test berhasil
- Documentation diperbarui
- Security diperiksa
- Review selesai

---

# Conclusion

Coding Standards Nexuni dibuat untuk menjaga kualitas sistem dalam jangka panjang.

Dengan mengikuti standar ini, Nexuni dapat berkembang dari modular monolith menjadi platform enterprise tanpa kehilangan keteraturan dan maintainability.