Bagian **Observability** adalah mata dan telinga Nexuni.

Kalau Security menjaga agar orang yang salah tidak masuk, Reliability memastikan sistem bisa pulih, maka Observability memastikan kita **tahu apa yang sedang terjadi di dalam sistem**.

Untuk platform PPOB, observability sangat penting karena masalah sering tidak terlihat dari luar:

* transaksi stuck di `PENDING`
* supplier lambat tapi belum gagal
* queue menumpuk
* saldo mismatch
* worker mati diam-diam
* API partner melakukan abuse

Tanpa observability, tim hanya menunggu komplain reseller. Itu bukan monitoring, itu sistem alarm berbasis manusia yang panik. 😄

Berikut draft dokumentasinya:

```markdown
# Observability Architecture

> Dokumen ini menjelaskan strategi observability Nexuni untuk memantau kesehatan sistem, mendeteksi masalah, melakukan debugging, dan memastikan seluruh transaksi dapat ditelusuri.
>
> Observability memungkinkan tim memahami kondisi internal sistem berdasarkan data yang dihasilkan oleh aplikasi.

---

# Tujuan

Observability Nexuni bertujuan memastikan:

- Semua transaksi dapat dilacak
- Masalah dapat ditemukan sebelum berdampak luas
- Performa sistem dapat diukur
- Kegagalan dapat dianalisis
- Root cause analysis dapat dilakukan dengan cepat

---

# Prinsip Observability

## 1. Everything Must Be Observable

Setiap komponen harus menghasilkan informasi yang cukup untuk menjawab:

- Apa yang terjadi?
- Kapan terjadi?
- Mengapa terjadi?
- Service mana yang bermasalah?
- Dampaknya terhadap transaksi apa?

---

# 2. Three Pillars of Observability

Nexuni menggunakan tiga pilar utama:

```

Logs

Metrics

Traces

```

---

# Logs

## Tujuan

Mencatat kejadian yang terjadi dalam sistem.

Logs digunakan untuk:

- debugging
- audit
- investigasi error

---

# Log Categories

## Application Log

Contoh:

```

Transaction created

Wallet hold successful

Supplier request sent

```

---

## Security Log

Contoh:

```

Failed login

Invalid API signature

Permission denied

```

---

## Transaction Log

Contoh:

```

Transaction ID:
TRX123456

Status:
PROCESSING -> SUCCESS

````

---

## Audit Log

Mencatat perubahan penting:

- Harga produk berubah
- Saldo manual adjustment
- User permission berubah
- Admin action

---

# Structured Logging

Log harus menggunakan format terstruktur.

Contoh:

```json
{
  "timestamp": "2026-07-15T12:00:00Z",
  "level": "INFO",
  "service": "transaction-engine",
  "transaction_id": "TRX123456",
  "correlation_id": "abc-123",
  "message": "supplier response received"
}
````

---

Keuntungan:

* mudah dicari
* mudah dianalisis
* mudah dikirim ke log platform

---

# Metrics

## Tujuan

Mengukur kondisi sistem secara kuantitatif.

---

# Business Metrics

Metric paling penting untuk Nexuni.

Contoh:

## Transaction Volume

```
Total transaction / minute
```

---

## Success Rate

```
SUCCESS / TOTAL TRANSACTION
```

---

## Failed Transaction Rate

```
FAILED / TOTAL TRANSACTION
```

---

## Pending Transaction

```
Jumlah transaksi pending
```

---

## Revenue

* Total margin
* Commission
* Deposit

---

# Technical Metrics

## Application

* Request per second
* Response time
* Error rate

---

## Database

* Connection pool
* Query latency
* Slow query

---

## Queue

* Queue length
* Consumer status
* Processing time

---

## Go Engine

* Goroutine count
* Memory usage
* CPU usage
* Supplier latency

---

# Distributed Tracing

## Tujuan

Melacak satu transaksi melewati seluruh sistem.

---

Contoh:

```
Client

 |

API Request

 |

Laravel

 |

RabbitMQ

 |

Go Engine

 |

Supplier

 |

Callback

 |

Laravel
```

---

Setiap transaksi membawa:

```
Correlation ID
```

---

Contoh:

```
Transaction:

TRX123456


Trace:

API
 |
Laravel
 |
RabbitMQ
 |
Go
 |
Supplier
```

---

# Transaction Traceability

Setiap transaksi harus dapat menjawab:

* siapa yang melakukan?
* kapan dibuat?
* supplier mana yang digunakan?
* berapa lama prosesnya?
* response supplier apa?
* kapan selesai?

---

# Monitoring Stack

Rekomendasi:

```
Application

    |

OpenTelemetry

    |

+---------------+

Prometheus

Grafana

Loki

Tempo

+---------------+
```

---

# OpenTelemetry

Digunakan sebagai standar instrumentasi.

Mengumpulkan:

* Trace
* Metric
* Log

---

# Prometheus

Digunakan untuk:

* Metric collection
* Alert evaluation

---

# Grafana

Digunakan untuk:

* Dashboard
* Visualization
* Monitoring

---

# Loki

Digunakan untuk:

* Log aggregation
* Log search

---

# Tempo

Digunakan untuk:

* Distributed tracing

---

# Dashboard Strategy

Nexuni memiliki beberapa dashboard.

---

# Business Dashboard

Menampilkan:

* Total transaksi
* Success rate
* Failed rate
* Pending transaction
* Revenue
* Active reseller

---

# Transaction Dashboard

Menampilkan:

* Transaction latency
* Supplier performance
* Retry count
* Pending aging

---

# Infrastructure Dashboard

Menampilkan:

* CPU
* Memory
* Disk
* Network
* Database health

---

# Supplier Dashboard

Menampilkan:

* Supplier uptime
* Response time
* Success rate
* Error frequency

---

# Alerting Strategy

Alert harus berdasarkan dampak bisnis.

---

# Critical Alert

Contoh:

```
Wallet mismatch detected

Transaction failure > 10%

Database unavailable

Queue stopped
```

---

# Warning Alert

Contoh:

```
Supplier response slow

Queue increasing

Memory usage high
```

---

# Alert Channel

Future:

* Email
* Telegram
* Slack
* PagerDuty

---

# Health Monitoring

Setiap service menyediakan:

```
/health
```

Untuk pengecekan dasar.

---

## Liveness Check

Menjawab:

"Apakah service hidup?"

---

## Readiness Check

Menjawab:

"Apakah service siap menerima request?"

---

# Error Tracking

Semua exception harus tercatat.

Contoh:

* Laravel Exception Handler
* Go Error Handler

---

Informasi minimum:

* Error message
* Stack trace
* Service
* Transaction ID
* User ID

---

# Performance Monitoring

Monitor:

## API

* Response time
* Throughput
* Error rate

---

## Database

* Slow query
* Lock contention
* Connection usage

---

## Queue

* Processing delay
* Failed jobs

---

# Observability Security

Logs tidak boleh membocorkan:

* Password
* API Secret
* PIN
* Token
* Sensitive data

---

Contoh salah:

```
password=123456
```

Contoh benar:

```
password=[REDACTED]
```

---

# Incident Investigation Flow

Ketika terjadi masalah:

```
Alert

↓

Check Dashboard

↓

Find Trace ID

↓

Check Logs

↓

Identify Root Cause

↓

Fix

↓

Post Incident Review
```

---

# Observability Checklist

Setiap fitur baru wajib memiliki:

* [ ] Logging
* [ ] Metrics
* [ ] Error handling
* [ ] Trace identifier
* [ ] Monitoring impact
* [ ] Alert consideration

---

# Golden Rules

1. Jika tidak dapat diukur, tidak dapat diperbaiki.
2. Setiap transaksi harus dapat ditelusuri.
3. Semua service harus menghasilkan telemetry.
4. Jangan mencatat data sensitif.
5. Alert harus berdasarkan dampak bisnis.
6. Monitoring bukan hanya CPU dan RAM.
7. Transaction visibility adalah prioritas utama.

---

# Kesimpulan

Observability membuat Nexuni menjadi sistem yang dapat dipahami.

Sistem finansial yang besar tidak hanya membutuhkan kemampuan untuk memproses transaksi, tetapi juga kemampuan untuk menjelaskan setiap kejadian yang terjadi di dalam sistem.

Nexuni harus selalu dapat menjawab:

"Transaksi ini berada di mana, kenapa lambat, dan apa yang terjadi padanya?"

```

---