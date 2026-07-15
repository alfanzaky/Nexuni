# Reliability

> Dokumen ini menjelaskan strategi reliability Nexuni untuk memastikan sistem tetap aman, konsisten, dan dapat dipulihkan ketika terjadi kegagalan.
>
> Dalam sistem finansial, kegagalan bukan sesuatu yang dapat dihindari sepenuhnya. Yang terpenting adalah memastikan kegagalan dapat dikendalikan, dilacak, dan dipulihkan tanpa menyebabkan kehilangan data maupun kerugian finansial.

---

# Tujuan

Reliability Nexuni bertujuan memastikan:

- Tidak ada saldo hilang
- Tidak ada transaksi ganda
- Tidak ada transaksi tanpa status jelas
- Semua kegagalan dapat dilacak
- Sistem dapat melakukan recovery otomatis
- Layanan tetap tersedia ketika komponen tertentu mengalami gangguan

---

# Prinsip Reliability

## 1. Financial Integrity First

Prioritas utama Nexuni:

```
Correctness
    >
Availability
    >
Performance
```

Sistem lebih baik menolak transaksi daripada menghasilkan transaksi yang salah.

Contoh:

Lebih baik:

```
Transaction Failed
```

daripada:

```
Saldo terpotong
+
Supplier tidak menerima transaksi
```

---

# 2. Failure Is Expected

Setiap komponen harus dianggap dapat mengalami kegagalan.

Kemungkinan kegagalan:

- Supplier tidak merespons
- Internet terputus
- Database lambat
- Queue penuh
- Service restart
- Duplicate message
- Timeout

Sistem harus memiliki mekanisme menghadapi kondisi tersebut.

---

# Idempotency

## Tujuan

Mencegah transaksi diproses lebih dari satu kali.

---

## Contoh Masalah

Client mengirim:

```
BUY PULSA 10000
```

Request pertama:

```
Timeout
```

Client mengirim ulang.

Tanpa idempotency:

```
Transaction A

Transaction B
```

Saldo terpotong dua kali.

---

Dengan idempotency:

```
Request A

↓

Transaction ID X


Request B

↓

Return Transaction ID X
```

---

## Implementasi

Setiap transaksi memiliki:

- transaction_id
- idempotency_key
- correlation_id

Database harus memiliki unique constraint.

---

# Retry Strategy

Retry digunakan untuk kegagalan sementara.

Contoh:

- Network timeout
- Supplier temporary unavailable
- Connection reset

---

## Retry Policy

Contoh:

```
Attempt 1

wait 5 seconds

Attempt 2

wait 30 seconds

Attempt 3

wait 5 minutes
```

---

## Retry Tidak Boleh

Retry tidak boleh membuat transaksi baru.

Salah:

```
Transaction A gagal

↓

buat Transaction B
```

Benar:

```
Transaction A

↓

Retry
```

---

# Circuit Breaker

## Tujuan

Melindungi sistem dari supplier yang sedang bermasalah.

---

## Contoh

Supplier A gagal:

```
Request 1 FAILED

Request 2 FAILED

Request 3 FAILED
```

Circuit breaker membuka:

```
Supplier A

STATUS:
OPEN
```

Request berikutnya dialihkan:

```
Supplier B
```

---

## State Circuit Breaker

```
CLOSED

(normal)

    |

    ▼

OPEN

(failed)

    |

    ▼

HALF OPEN

(test)
```

---

# Timeout Management

Setiap komunikasi harus memiliki timeout.

Tidak boleh ada request tanpa batas waktu.

---

## Contoh

Supplier Request:

```
Timeout:
10 seconds
```

Jika melewati batas:

```
UNKNOWN
```

kemudian masuk proses recovery.

---

# Queue Reliability

RabbitMQ digunakan untuk memastikan pesan tidak hilang.

---

## Message Guarantee

Setiap event harus menggunakan:

- Persistent Message
- Publisher Confirmation
- Consumer Acknowledgement

---

## Consumer Rule

Consumer harus:

1. Membaca message
2. Memproses
3. Menyimpan hasil
4. ACK message

Jika gagal:

```
NACK

↓

Retry Queue
```

---

# Dead Letter Queue (DLQ)

Event yang gagal setelah retry maksimum dipindahkan ke DLQ.

Contoh:

```
transaction.process

        |

        ▼

Retry 3x

        |

        ▼

Dead Letter Queue
```

---

DLQ digunakan untuk:

- Investigasi
- Manual recovery
- Monitoring

Event tidak boleh hilang diam-diam.

---

# Transaction Recovery

Setiap transaksi harus dapat dipulihkan.

---

## Kondisi:

### Laravel Crash Setelah Hold Balance

Recovery:

```
Transaction Status Check

↓

Resume Processing
```

---

### Supplier Berhasil Tetapi Callback Hilang

Recovery:

```
Webhook

atau

Reconciliation Worker
```

---

### Supplier Tidak Memberikan Response

Recovery:

```
Retry

↓

Check Status

↓

Resolve Transaction
```

---

# Reconciliation System

Nexuni harus memiliki sistem rekonsiliasi.

Tujuan:

Mencari transaksi yang tidak sinkron.

---

Contoh:

Database Nexuni:

```
PENDING
```

Supplier:

```
SUCCESS
```

Reconciliation Worker:

```
Detect Difference

↓

Update Transaction
```

---

# Graceful Degradation

Ketika sebagian sistem gagal, Nexuni harus tetap memberikan layanan terbatas.

Contoh:

Supplier A down:

```
Supplier A

OFF

Supplier B

ACTIVE
```

---

# Health Check

Setiap service harus menyediakan health check.

Contoh:

```
/health

/readiness

/liveness
```

---

## Monitoring Health

Monitor:

- Database connection
- Queue status
- Memory
- CPU
- Supplier availability
- Response time

---

# Observability

Reliability membutuhkan visibility.

Setiap transaksi harus memiliki:

- Transaction ID
- Correlation ID
- Service logs
- Event history
- Timing information

---

# Logging Strategy

Log harus memiliki level:

## INFO

Aktivitas normal.

Contoh:

```
Transaction Created
```

---

## WARNING

Potensi masalah.

Contoh:

```
Supplier response slow
```

---

## ERROR

Kegagalan.

Contoh:

```
Supplier timeout
```

---

# Disaster Recovery

Nexuni harus memiliki kemampuan recovery ketika terjadi kegagalan besar.

---

## Backup

Meliputi:

- Database backup
- Configuration backup
- Secret backup

---

## Recovery Testing

Backup harus diuji secara berkala.

Backup yang tidak pernah diuji bukan backup, hanya file yang merasa penting.

---

# Reliability Checklist

Setiap fitur baru harus mempertimbangkan:

- Apakah operasi ini idempotent?
- Bagaimana jika service mati?
- Bagaimana jika network gagal?
- Bagaimana jika message dikirim dua kali?
- Bagaimana proses recovery?
- Apakah transaksi dapat diaudit?
- Apakah ada retry?
- Apakah ada monitoring?

---

# Golden Rules

1. Jangan pernah menganggap network selalu tersedia.
2. Jangan pernah memproses transaksi tanpa idempotency.
3. Jangan pernah menghapus event gagal.
4. Jangan pernah melakukan retry dengan membuat transaksi baru.
5. Jangan pernah mengubah saldo tanpa ledger.
6. Semua kegagalan harus dapat direcovery.
7. Semua transaksi harus memiliki jejak audit.
8. Reliability lebih penting daripada kecepatan.

---

# Kesimpulan

Reliability Nexuni dibangun bukan dengan menganggap sistem tidak akan gagal.

Sebaliknya, Nexuni dirancang dengan asumsi bahwa kegagalan pasti terjadi.

Arsitektur yang baik bukan arsitektur yang tidak pernah gagal, tetapi arsitektur yang mampu gagal dengan aman, mendeteksi masalah dengan cepat, dan pulih tanpa kehilangan integritas data.