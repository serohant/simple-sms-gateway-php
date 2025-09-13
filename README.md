# SMS API (PHP + MySQL)

A simple, API-key–protected SMS service (single, bulk, and status lookup) — framework-free, pure PHP.


## Features
- `POST /api/sms/send` — Enqueue a single SMS
- `POST /api/sms/bulk` — Enqueue bulk SMS
- `GET  /api/sms/status?id=MSG-YYYY-NNNNNN` — Query message status
- API key authentication (`X-API-Key` header)
- Per-minute rate limiting
- Privacy-focused minimal logging\
- Queue worker (from tools; __simulation__ instead of a real provider)
- Standard response schema: `{ "id" | "ids": [], "status": "...", "timestamp": "..." }`

## Quick Start
1) **Create the database:** import `database.sql` into MySQL.  
2) **Edit `config.php`,** fill in DB credentials and rate-limit settings.
3) **Server:** use `public/` as your web root or enable `.htaccess` for Apache.
4) **Create an API key:** add a user to the `users` table and set `api_key`.  
5) **Run the queue worker***: `php tools/queue_worker.php` (can be scheduled via cron).

## Example Requests

### Single Send
```bash
curl -X POST http://localhost/api/sms/send \\
  -H 'Content-Type: application/json' \\
  -H "X-API-Key: YOUR_API_KEY" \\
  -d '{"to":"+905551112233","message":"Merhaba!"}'
```

### Bulk Send
```bash
curl -X POST http://localhost/api/sms/bulk \\
  -H 'Content-Type: application/json' \\
  -H "X-API-Key: YOUR_API_KEY" \\
  -d '{"to":["+905551112233","+905554445566"],"message":"Duyuru metni"}'
```

### Status Lookup
```bash
curl "http://localhost/api/sms/status?id=MSG-2025-000001" -H 'X-API-Key: YOUR_API_KEY'
```

## Structure
- **public/index.php**: minimal router
- **src/DB.php**: PDO connection & helpers
- **src/Auth.php**: API key validation
- **src/RateLimiter.php**: per-minute limiter
- **src/Logger.php**: minimal JSON logger
- **src/Queue.php`: enqueue & provider simulation
- **src/Controllers/**: endpoint controllers
- **tools/queue_worker.php**: CLI queue processor

> Note: To integrate a real SMS provider, replace the simulation in `Queue::sendToProvider()`with a real HTTP request.
