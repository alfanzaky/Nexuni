# Communication Architecture

> Dokumen ini menjelaskan standar komunikasi antar komponen di dalam Nexuni.
>
> Setiap komunikasi antar service harus mengikuti aturan yang telah ditetapkan agar sistem tetap konsisten, mudah dikembangkan, dan tidak menciptakan ketergantungan yang tidak perlu.

---

# Tujuan

Communication Architecture bertujuan untuk:

- Memisahkan tanggung jawab antar service
- Mengurangi ketergantungan langsung (Loose Coupling)
- Mempermudah proses scaling
- Menjamin keandalan komunikasi
- Mendukung pemrosesan asynchronous maupun synchronous

---

# Prinsip Dasar

Nexuni menggunakan dua pola komunikasi utama:

- **Asynchronous Communication**
- **Synchronous Communication**

Pemilihan metode komunikasi harus disesuaikan dengan kebutuhan bisnis, bukan berdasarkan preferensi teknologi.

---

# Gambaran Arsitektur

```text
                     +----------------------+
                     |    Laravel Core      |
                     +----------+-----------+
                                |
                +---------------+----------------+
                |                                |
                |                                |
         Asynchronous                     Synchronous
          RabbitMQ                           gRPC
                |                                |
                ▼                                ▼
      +------------------+           +------------------+
      | Go Transaction   |           | Internal Service |
      | Engine           |           |                  |
      +------------------+           +------------------+
```

---

# Asynchronous Communication

## Tujuan

Digunakan untuk proses yang:

- tidak membutuhkan respons langsung
- dapat diproses di background
- membutuhkan retry
- memiliki kemungkinan gagal sementara

Komunikasi asynchronous dilakukan menggunakan **RabbitMQ**.

---

## Contoh Penggunaan

- Transaction Created
- Transaction Processing
- Supplier Queue
- Retry Queue
- Notification
- Commission Distribution
- Audit Event
- Activity Log

---

## Contoh Flow

```text
Laravel

↓

Publish Event

↓

RabbitMQ

↓

Go Transaction Engine
```

Laravel tidak menunggu hasil dari Go.

Setelah event dipublikasikan, request dapat langsung dikembalikan ke client.

---

## Keuntungan

- Loose Coupling
- Scalability
- Retry Support
- Fault Isolation
- Background Processing

---

# RabbitMQ

RabbitMQ berfungsi sebagai **Message Broker**.

RabbitMQ bukan tempat menyimpan data bisnis.

RabbitMQ hanya bertanggung jawab mengirimkan pesan antar service.

---

## Queue yang Digunakan

Contoh queue:

- transaction.process
- transaction.retry
- supplier.callback
- notification.send
- commission.calculate
- audit.log

Penamaan queue harus konsisten dan mudah dipahami.

---

## Retry

Retry dilakukan oleh consumer.

RabbitMQ tidak menentukan logika retry.

Retry policy merupakan tanggung jawab Go Transaction Engine.

---

## Dead Letter Queue (DLQ)

Apabila sebuah event gagal diproses setelah batas retry tertentu, event dipindahkan ke Dead Letter Queue.

Tujuan DLQ:

- Investigasi
- Reprocessing
- Monitoring

Event tidak boleh hilang.

---

# Synchronous Communication

## Tujuan

Digunakan apabila service membutuhkan respons secara langsung.

Komunikasi synchronous menggunakan **gRPC**.

---

## Contoh Penggunaan

- Validasi Wallet
- Validasi User
- Pricing Lookup
- Transaction Status
- Health Check

---

## Contoh Flow

```text
Go Transaction Engine

↓

gRPC

↓

Laravel Core
```

Go menunggu respons sebelum melanjutkan proses.

---

## Keuntungan

- Latensi rendah
- Strongly Typed
- Efficient
- Contract-based
- Cocok untuk komunikasi internal

---

# REST API

REST hanya digunakan untuk komunikasi dengan pihak luar.

Contoh:

- Mobile App
- Web Dashboard
- Mitra H2H
- Public API

REST tidak digunakan sebagai komunikasi utama antar service internal.

---

# Event Design

Setiap event harus bersifat immutable.

Event tidak boleh diubah setelah dipublikasikan.

Event hanya menggambarkan sesuatu yang telah terjadi.

Contoh:

✅

- TransactionCreated
- TransactionSucceeded
- TransactionFailed
- DepositApproved
- WalletRefunded

Hindari nama event berupa perintah seperti:

- DoTransaction
- UpdateWallet
- SendCommission

---

# Event Contract

Setiap event harus memiliki kontrak yang terdokumentasi.

Minimal berisi:

- Event ID
- Event Name
- Timestamp
- Version
- Correlation ID
- Transaction ID
- Payload

Perubahan struktur event harus dilakukan melalui versioning.

---

# Correlation ID

Seluruh komunikasi antar service wajib membawa Correlation ID.

Correlation ID digunakan untuk:

- Distributed Tracing
- Debugging
- Audit
- Monitoring

Satu transaksi hanya memiliki satu Correlation ID.

---

# Idempotency

Consumer harus mampu menerima event yang sama lebih dari satu kali.

Event tidak boleh menyebabkan:

- duplicate transaction
- duplicate refund
- duplicate commission

Seluruh consumer harus bersifat idempotent.

---

# Error Handling

Apabila terjadi kegagalan komunikasi:

- lakukan retry sesuai kebijakan
- jangan menghapus event
- jangan kehilangan pesan
- catat seluruh kegagalan

Seluruh error harus dapat ditelusuri.

---

# Communication Matrix

| Komunikasi | Teknologi |
|------------|-----------|
| Client → Laravel | REST API |
| Laravel → RabbitMQ | Event |
| RabbitMQ → Go | Queue |
| Go → Laravel | gRPC |
| Supplier → Go | HTTP / HTTPS |
| Go → Supplier | HTTP / HTTPS |

---

# Prinsip Communication Architecture

Seluruh komunikasi di dalam Nexuni harus mengikuti prinsip berikut:

- Loose Coupling
- Event-Driven
- Reliable Delivery
- Idempotent Consumer
- Immutable Event
- Versioned Contract
- Observable Communication
- Secure by Default

Tidak ada service yang boleh berkomunikasi langsung dengan database service lain.

Seluruh komunikasi harus melalui API atau Message Broker yang telah ditetapkan.