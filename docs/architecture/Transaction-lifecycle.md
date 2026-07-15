# Transaction Lifecycle

> Dokumen ini menjelaskan siklus hidup (Lifecycle) sebuah transaksi di dalam Nexuni, mulai dari request diterima hingga transaksi selesai diproses.
>
> Seluruh jenis produk harus mengikuti lifecycle ini agar konsisten, mudah diaudit, dan menjaga integritas finansial.

---

# Tujuan

Transaction Lifecycle dibuat untuk memastikan bahwa setiap transaksi:

- memiliki status yang jelas
- dapat dilacak (traceable)
- aman terhadap duplicate request
- dapat dipulihkan apabila terjadi kegagalan
- tidak menyebabkan saldo menjadi tidak konsisten

---

# Gambaran Umum

```text
Client
    │
    ▼
Request Transaction
    │
    ▼
Validation
    │
    ▼
Price Calculation
    │
    ▼
Wallet Hold
    │
    ▼
Create Transaction (PENDING)
    │
    ▼
Publish Event
    │
    ▼
RabbitMQ
    │
    ▼
Go Transaction Engine
    │
    ▼
Supplier
    │
    ▼
Supplier Response
    │
    ▼
Laravel Core
    │
    ├──────────────┐
    │              │
 SUCCESS        FAILED
    │              │
Capture        Refund
    │              │
    ▼              ▼
Completed     Completed
```

---

# Tahapan Transaksi

## 1. Request Diterima

Client mengirim request transaksi melalui REST API.

Contoh:

- Web Dashboard
- Mobile App
- H2H Partner

Request harus memiliki:

- Product Code
- Destination
- Idempotency Key
- API Credential
- Signature (H2H)

---

## 2. Validasi

Laravel melakukan validasi awal.

Meliputi:

- User aktif
- Produk aktif
- Harga tersedia
- Wallet aktif
- Saldo cukup
- PIN transaksi (jika diperlukan)
- Rate limit
- Duplicate request

Apabila salah satu validasi gagal, transaksi langsung dihentikan.

---

## 3. Perhitungan Harga

Laravel menentukan harga final berdasarkan:

- Harga dasar produk
- Markup reseller
- Grup reseller
- Promo (future)
- Diskon (future)

Harga yang telah dihitung menjadi harga final transaksi.

Harga tidak boleh berubah setelah transaksi dibuat.

---

## 4. Wallet Hold

Laravel melakukan Hold Balance.

Saldo tidak langsung dikurangi permanen.

Sebaliknya, saldo dipindahkan menjadi **Held Balance**.

Contoh:

```text
Available Balance : 100.000

Held Balance : 0
```

Setelah Hold:

```text
Available Balance : 90.000

Held Balance : 10.000
```

Dengan cara ini saldo tetap aman apabila supplier belum memberikan hasil.

---

## 5. Membuat Transaction Record

Laravel membuat data transaksi.

Status awal:

```text
PENDING
```

Informasi yang disimpan:

- Transaction ID
- Product
- Destination
- Harga
- Status
- Reseller
- Supplier (jika sudah dipilih)
- Timestamp

Transaction ID bersifat immutable.

---

## 6. Publish Event

Laravel menerbitkan event ke RabbitMQ.

Contoh event:

```text
TransactionCreated
```

Payload hanya berisi informasi yang diperlukan untuk diproses oleh Go Engine.

Laravel tidak menunggu supplier.

Request kepada client dapat langsung dikembalikan.

---

## 7. Queue Processing

Go Engine mengambil event dari RabbitMQ.

Kemudian:

- memilih supplier
- menerapkan routing
- memeriksa circuit breaker
- menentukan timeout
- menyiapkan retry policy

---

## 8. Eksekusi Supplier

Go mengirim request ke supplier.

Supplier dapat memberikan respon:

- SUCCESS
- FAILED
- PENDING
- UNKNOWN

Seluruh response dinormalisasi menjadi format internal Nexuni.

---

## 9. Callback ke Laravel

Go mengirim hasil transaksi ke Laravel melalui gRPC atau Internal API.

Laravel tidak menerima response mentah supplier.

Laravel hanya menerima response yang telah dinormalisasi.

---

# Penyelesaian Transaksi

## SUCCESS

Jika supplier berhasil:

Laravel melakukan:

- Capture Hold Balance
- Mengubah status menjadi SUCCESS
- Menyimpan Serial Number
- Membuat Wallet Ledger
- Menghitung komisi
- Mengirim notifikasi

Status akhir:

```text
SUCCESS
```

---

## FAILED

Jika supplier gagal:

Laravel melakukan:

- Release Hold Balance
- Refund saldo
- Mengubah status menjadi FAILED
- Membuat Wallet Ledger
- Menyimpan alasan kegagalan

Status akhir:

```text
FAILED
```

---

## PENDING

Jika supplier masih memproses:

Status tetap:

```text
PENDING
```

Go akan menunggu:

- webhook
- callback
- scheduler
- retry

Tidak ada refund selama status masih pending.

---

# Retry

Retry hanya dilakukan oleh Go Engine.

Retry dilakukan apabila:

- timeout
- network error
- supplier unavailable
- temporary failure

Retry tidak boleh membuat transaksi baru.

Retry selalu menggunakan Transaction ID yang sama.

---

# Idempotency

Setiap transaksi wajib memiliki Idempotency Key.

Apabila request yang sama dikirim dua kali:

Laravel harus mengembalikan transaksi sebelumnya.

Laravel tidak boleh membuat transaksi baru.

---

# Timeout

Go menentukan timeout supplier.

Apabila timeout tercapai:

Status menjadi:

```text
PROCESSING
```

Kemudian scheduler akan menentukan apakah transaksi:

- diteruskan
- di-retry
- dianggap gagal

---

# Refund

Refund hanya dilakukan oleh Laravel.

Refund tidak boleh dilakukan oleh Go.

Refund harus:

- atomik
- tercatat di Wallet Ledger
- dapat diaudit

---

# Commission

Komisi hanya dihitung apabila transaksi berhasil.

Komisi tidak boleh dihitung oleh Go.

---

# Audit Trail

Seluruh perubahan status transaksi wajib memiliki riwayat.

Contoh:

```text
CREATED

↓

PENDING

↓

PROCESSING

↓

SUCCESS
```

atau

```text
CREATED

↓

PENDING

↓

FAILED
```

Audit trail tidak boleh dihapus.

---

# Status Lifecycle

```text
CREATED
    │
    ▼
PENDING
    │
    ▼
PROCESSING
   ├───────────────┐
   │               │
   ▼               ▼
SUCCESS        FAILED
```

Future status:

- EXPIRED
- CANCELLED
- REVERSED

---

# Prinsip Transaction Lifecycle

Seluruh transaksi di Nexuni harus memenuhi prinsip berikut:

- Financial Integrity
- Idempotency
- Atomic Operation
- Auditability
- Traceability
- Reliability
- Event-Driven Processing
- Immutable Transaction Record

Tidak ada transaksi yang boleh mengubah saldo tanpa Wallet Ledger, dan tidak ada transaksi yang boleh selesai tanpa status akhir yang jelas.