Bagian **Security** untuk Nexuni harus diperlakukan sebagai **financial security architecture**, bukan sekadar "pasang login dan hash password". Sistem PPOB membawa nilai uang, kredensial reseller, API partner, dan akses supplier. Jadi ancaman bukan cuma hacker klasik, tetapi juga:

* API key bocor
* transaksi palsu
* replay attack
* manipulasi harga
* penyalahgunaan saldo
* akses admin ilegal
* perubahan ledger tanpa izin

Berikut draft dokumentasinya.

```markdown
# Security Architecture

> Dokumen ini menjelaskan strategi keamanan Nexuni untuk melindungi data, transaksi, saldo, dan komunikasi antar komponen sistem.
>
> Security pada Nexuni dirancang berdasarkan prinsip:
>
> **"Never trust, always verify."**
>
> Setiap request, user, service, dan transaksi harus melalui proses validasi dan otorisasi yang sesuai.

---

# Tujuan

Security Architecture Nexuni bertujuan untuk memastikan:

- Data user terlindungi
- Saldo tidak dapat dimanipulasi
- Transaksi hanya dapat dilakukan oleh pihak yang sah
- Komunikasi antar service aman
- API partner terlindungi
- Aktivitas penting dapat diaudit
- Sistem memenuhi standar keamanan aplikasi finansial

---

# Security Principles

## 1. Defense in Depth

Nexuni tidak bergantung pada satu lapisan keamanan.

Keamanan diterapkan pada beberapa tingkat:

```

User

↓

Application Security

↓

API Security

↓

Service Security

↓

Database Security

↓

Infrastructure Security

```

Jika satu lapisan gagal, lapisan lain tetap memberikan perlindungan.

---

# 2. Least Privilege

Setiap user dan service hanya mendapatkan akses yang diperlukan.

Contoh:

Admin:

```

Manage User
Manage Product

```

tidak otomatis memiliki:

```

Direct Wallet Modification

```

---

Go Engine:

Boleh:

```

Execute Transaction

```

Tidak boleh:

```

Modify Wallet

```

---

# 3. Zero Trust Communication

Tidak ada service yang otomatis dipercaya.

Setiap komunikasi harus melakukan:

- Authentication
- Authorization
- Validation

---

# Authentication

## User Authentication

Laravel bertanggung jawab terhadap:

- Login
- Logout
- Session
- Token Management
- Password Management

---

## Password Security

Password wajib:

- menggunakan hashing kuat
- tidak pernah disimpan dalam bentuk plaintext
- memiliki policy minimum

Rekomendasi:

```

Argon2id

```

---

# Transaction Authentication

Transaksi finansial membutuhkan lapisan tambahan.

Contoh:

- Transaction PIN
- OTP
- Device Verification

---

# Transaction PIN

PIN transaksi:

- disimpan dalam bentuk hash
- tidak pernah dikembalikan melalui API
- memiliki batas percobaan

---

Contoh:

```

User Login

↓

Request Transaction

↓

Validate PIN

↓

Process Transaction

```

---

# Authorization

Authentication menjawab:

"Siapa kamu?"

Authorization menjawab:

"Apa yang boleh kamu lakukan?"

---

# Role Based Access Control (RBAC)

Nexuni menggunakan role:

Contoh:

```

Super Admin

Admin

Operator

Reseller

Master Reseller

H2H Partner

```

---

Setiap role memiliki permission berbeda.

Contoh:

| Permission | Admin | Reseller |
|---|---|---|
| Manage Product | ✅ | ❌ |
| Deposit | ✅ | ❌ |
| Buy Product | ❌ | ✅ |
| View Report | ✅ | Terbatas |

---

# API Security

## Public API

Semua API publik wajib menggunakan:

- Authentication
- Authorization
- Rate Limit
- Validation

---

# H2H API Security

Partner H2H menggunakan:

- API Key
- Secret Key
- HMAC Signature

---

Contoh:

Request:

```

POST /api/transaction

```

Header:

```

X-API-Key
X-Signature
X-Timestamp

```

---

Server melakukan:

```

Generate Signature

↓

Compare Signature

↓

Accept / Reject

```

---

# Replay Attack Protection

Setiap request H2H memiliki:

- timestamp
- nonce
- signature

Request lama tidak boleh digunakan kembali.

---

Contoh:

Request:

```

timestamp:
2026-07-15 12:00

```

Jika diterima:

```

2026-07-15 12:30

```

Request ditolak.

---

# Internal Service Security

Komunikasi:

Laravel ↔ Go

menggunakan:

- gRPC
- Authentication
- Service Identity

---

Future:

- mTLS
- Certificate Authentication

---

# Wallet Security

Wallet adalah aset paling kritis.

Aturan:

1. Tidak ada direct update saldo.
2. Semua perubahan melalui Ledger.
3. Semua perubahan memiliki actor.
4. Semua perubahan memiliki timestamp.

---

Contoh yang salah:

```

UPDATE wallets
SET balance = balance + 10000

```

---

Contoh benar:

```

Create Ledger Entry

↓

Update Wallet Balance

↓

Audit Log

```

---

# Ledger Protection

Ledger bersifat:

## Append Only

Artinya:

Boleh:

```

Tambah record baru

```

Tidak boleh:

```

Edit transaksi lama
Delete transaksi lama

```

---

Jika terjadi kesalahan:

Gunakan:

```

Adjustment Entry

```

bukan edit data lama.

---

# Input Security

Semua input harus divalidasi.

Perlindungan terhadap:

- SQL Injection
- XSS
- CSRF
- Command Injection
- Mass Assignment

---

# Database Security

Database harus menggunakan:

- User database berbeda
- Password kuat
- Permission minimum
- Encrypted connection
- Backup terenkripsi

---

Contoh:

Laravel:

```

READ/WRITE Database User

```

Monitoring:

```

READ ONLY User

```

---

# Secret Management

Secret tidak boleh berada di:

- Source code
- Git repository
- Dokumentasi publik

---

Contoh secret:

- Database password
- API Key Supplier
- HMAC Secret
- JWT Secret

---

Gunakan:

- Environment Variable
- Secret Manager

Future:

- Hashicorp Vault
- AWS Secrets Manager

---

# Admin Security

Admin memiliki akses paling tinggi.

Proteksi tambahan:

- Strong Password Policy
- Two Factor Authentication
- Login Monitoring
- IP Restriction (optional)
- Activity Audit

---

# Audit Logging

Aktivitas penting wajib dicatat.

Contoh:

- Login
- Logout
- Perubahan harga
- Perubahan user
- Deposit approval
- Manual adjustment
- Refund

---

Audit harus menyimpan:

- Actor
- Action
- Timestamp
- IP Address
- Metadata

---

# Rate Limiting

Rate limit diterapkan pada:

- Public API
- Login
- H2H API
- Supplier Request

---

Tujuan:

- Mencegah abuse
- Mencegah brute force
- Melindungi resource

---

# Encryption

## Data in Transit

Semua komunikasi menggunakan:

```

HTTPS / TLS

```

---

## Data at Rest

Data sensitif dapat dienkripsi:

- API Secret
- Personal Data
- Credential

---

# Security Monitoring

Monitor:

- Login gagal
- API abuse
- Suspicious transaction
- Unusual balance activity
- Service authentication failure

---

# Security Checklist

Setiap fitur baru harus menjawab:

- Apakah authentication diperlukan?
- Apakah authorization sudah benar?
- Apakah data sensitif terlindungi?
- Apakah aktivitas tercatat?
- Apakah request dapat dipalsukan?
- Apakah ada rate limit?
- Apakah transaksi aman dari duplicate?

---

# Golden Rules

1. Jangan pernah menyimpan password plaintext.
2. Jangan pernah menyimpan secret di repository.
3. Jangan pernah mengubah saldo secara langsung.
4. Jangan pernah mempercayai request eksternal tanpa validasi.
5. Jangan pernah memberikan permission berlebihan.
6. Semua aktivitas penting harus memiliki audit trail.
7. Semua komunikasi service harus terautentikasi.
8. Financial operation harus selalu dapat ditelusuri.

---

# Kesimpulan

Security Nexuni bukan fitur tambahan, tetapi bagian dari desain sistem.

Platform finansial tidak cukup hanya berjalan cepat.

Platform harus memastikan setiap transaksi:

- dilakukan oleh pihak yang benar,
- melalui proses yang benar,
- tercatat dengan benar,
- dan dapat dipertanggungjawabkan.
```

Catatan arsitektur: setelah ini urutan yang paling pas adalah:

**10. Observability Architecture**

Karena security menjawab:

> "Bagaimana kita mencegah dan membatasi?"

Sedangkan observability menjawab:

> "Bagaimana kita tahu kalau ada sesuatu yang salah?"

Untuk sistem PPOB, dua hal ini harus jalan beriringan. Sistem aman tapi tidak terlihat tetap menyulitkan operasional.
