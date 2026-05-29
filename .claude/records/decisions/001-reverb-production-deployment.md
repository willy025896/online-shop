# ADR-001: Reverb 生產部署 Checklist

**Date**: 2026-04-30
**Status**: Reference（部署時再執行，目前僅作為參考文件）

## Context

即時通訊功能基於 Laravel Reverb（WebSocket 伺服器）。Reverb 是獨立於 Laravel 應用之外的常駐 PHP process，生產部署有別於一般 PHP-FPM web 應用，需要額外處理服務常駐、reverse proxy、TLS、規模擴展等議題。

本文件作為部署時的 checklist，假設環境為 Linux + Nginx，並標註替代方案（Caddy / docker / 雲端託管）。

---

## 部署架構圖

```
[ 瀏覽器 ] ─ wss://yourdomain.com/app/... ─→ [ Nginx 443 + TLS ]
                                                    │
                                                    ↓
                                              [ Reverb :8080 ]
                                                    ↑
                                              [ Laravel app ] (broadcast() 推事件)
                                                    ↓
                                              [ Redis ] (queue + scaling pub/sub)
```

---

## Checklist

### 1. Reverb 服務常駐（Process Manager）

❌ **不可用** `php artisan reverb:start` 直接在 SSH 終端機跑（連線中斷就死）

✅ **使用 Supervisor**（Ubuntu/Debian 主流）

**安裝**：
```bash
sudo apt-get install supervisor
```

**設定檔**：`/etc/supervisor/conf.d/reverb.conf`
```ini
[program:reverb]
process_name=%(program_name)s
command=php /var/www/online-shop/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/reverb.log
stopwaitsecs=3600
```

**啟用**：
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
sudo supervisorctl status
```

**替代方案**：
- **systemd**：寫 `.service` 檔放 `/etc/systemd/system/`
- **Docker**：把 reverb 包成獨立 container，用 docker-compose / Kubernetes 管理
- **PM2**：可以但不推薦（PM2 是 Node 生態的工具）

---

### 2. Nginx Reverse Proxy

瀏覽器只走 443，Nginx 把 WebSocket 流量轉發給 8080。

**設定檔片段**：`/etc/nginx/sites-available/online-shop`
```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate     /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # Laravel app（PHP-FPM）
    root /var/www/online-shop/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # ── Reverb WebSocket ──
    location /app/ {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";

        proxy_read_timeout 60m;
        proxy_send_timeout 60m;
    }

    # Reverb 認證 endpoint（apps API）
    location /apps/ {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
    }
}
```

**生效**：
```bash
sudo nginx -t
sudo systemctl reload nginx
```

**替代方案**：
- **Caddy**：自動處理 TLS，設定更簡潔。WebSocket 預設支援不需手動加 upgrade header
- **Cloudflare**：可作為前端 reverse proxy，需確認 WebSocket 支援已開啟（Cloudflare 預設支援）

---

### 3. TLS / SSL（wss）

正式站必須走 https，WebSocket 也必須 wss。

**Let's Encrypt + Certbot**：
```bash
sudo apt-get install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
sudo certbot renew --dry-run   # 測試自動續訂
```

**`.env` 對應修改**：
```
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

**注意**：
- TLS 在 Nginx 終止，Reverb process 內部仍跑 http（不要在 Reverb config 裡開啟 TLS）
- 修改 `.env` 後必跑 `php artisan config:clear` 與 `npm run build`

---

### 4. CORS / 來源限制

`config/reverb.php` 的 `allowed_origins` 預設是 `['*']`，**生產必須鎖定**。

```php
'apps' => [
    'apps' => [
        [
            'app_id'         => env('REVERB_APP_ID'),
            'key'            => env('REVERB_APP_KEY'),
            'secret'         => env('REVERB_APP_SECRET'),
            'allowed_origins' => ['yourdomain.com'],   // ← 鎖死
            'ping_interval'  => env('REVERB_APP_PING_INTERVAL', 60),
            'max_message_size' => env('REVERB_APP_MAX_MESSAGE_SIZE', 10_000),
        ],
    ],
],
```

---

### 5. 防火牆設定

8080 不對外開放，只允許 Nginx 同機連線。

**ufw（Ubuntu）**：
```bash
sudo ufw allow 22/tcp           # SSH
sudo ufw allow 80/tcp           # HTTP（會被 redirect 到 443）
sudo ufw allow 443/tcp          # HTTPS / WSS
sudo ufw deny 8080/tcp          # Reverb 不對外
sudo ufw enable
```

雲端供應商也要在 security group / firewall rules 同步設定。

---

### 6. 規模擴展（Redis Scaling）

**何時需要**：單 Reverb process 大約撐 1000 個並發 WebSocket 連線。超過時開橫向擴展。

**.env**：
```
REVERB_SCALING_ENABLED=true
REVERB_SCALING_CHANNEL=reverb

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=...
```

**多開 Reverb process** 在 Supervisor 設定加：
```ini
numprocs=4
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/online-shop/artisan reverb:start --host=0.0.0.0 --port=80%(process_num)02d
```

**Nginx upstream 做負載均衡**：
```nginx
upstream reverb {
    least_conn;
    server 127.0.0.1:8000;
    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
    server 127.0.0.1:8003;
}

location /app/ {
    proxy_pass http://reverb;
    # ... 其他 proxy_set_header 同上
}
```

---

### 7. Queue / Cache 改用 Redis

即時通訊量大時 broadcast 走 queue 比較順，避免阻塞 web request。

**`.env`**：
```
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
BROADCAST_CONNECTION=reverb
```

**Event 加 ShouldQueue（可選）**：
```php
// app/Events/MessageSent.php
class MessageSent implements ShouldBroadcast, ShouldQueue
```

但即時通訊延遲敏感，通常維持 `ShouldBroadcast`（同步推送）即可，看流量決定。

**Queue worker（Supervisor 另外開一支）**：
```ini
[program:queue]
command=php /var/www/online-shop/artisan queue:work redis --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
```

---

### 8. 監控與 Debug

**Reverb 內建 debug**：
```bash
php artisan reverb:debug   # 查看當前連線
```

**Laravel Pulse**（推薦）：
```bash
composer require laravel/pulse
php artisan pulse:install
```
能看 broadcast 量、連線數、queue 待處理數。

**Health check**（給雲端 LB / k8s 用）：
- Reverb 沒有官方 health endpoint，可寫一支簡單的：
  ```php
  Route::get('/up/reverb', fn () => response()->json([
      'reverb' => @fsockopen('127.0.0.1', 8080, $e, $s, 1) ? 'up' : 'down',
  ]));
  ```

---

### 9. 部署流程（Zero-downtime）

部署時要重啟 Reverb，但不希望使用者連線中斷：

```bash
# 1. 拉新 code
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 2. Migration
php artisan migrate --force

# 3. Cache 清空
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. 平滑重啟 Reverb（用 SIGUSR2 訊號）
sudo supervisorctl signal SIGUSR2 reverb
# 或 reload（會斷線重連）
sudo supervisorctl restart reverb

# 5. Queue worker 重啟（讓它跑新 code）
sudo supervisorctl restart queue
```

前端 Echo 會自動重連，使用者大約 1-2 秒會重新連上。

---

### 10. 替代方案：託管服務

如果不想自己維運 Reverb，可考慮：

| 服務 | 優勢 | 劣勢 |
|------|------|------|
| **Pusher**（pusher.com） | 完全託管，零運維 | 商用付費（按連線數計費），國外延遲 |
| **Soketi** | Pusher 協議相容開源版 | 仍需自己跑 process |
| **Ably** | 全球 CDN、完整 SDK | 較貴 |

切換很簡單，把 `.env` 的 `BROADCAST_CONNECTION` 從 `reverb` 改成 `pusher`，前端 Echo 設定改 `broadcaster: 'pusher'` 即可（程式碼不用動）。

---

## 部署前最終 Checklist

- [ ] Supervisor 設定好 reverb + queue worker 並 autostart
- [ ] Nginx 設好 reverse proxy + WebSocket upgrade header
- [ ] Let's Encrypt SSL 憑證取得，自動續訂測試通過
- [ ] `.env` 的 REVERB_HOST/PORT/SCHEME 改為正式網域
- [ ] `config/reverb.php` 的 `allowed_origins` 鎖定網域
- [ ] 防火牆 8080 不對外，只開 22/80/443
- [ ] Redis 安裝完成，QUEUE / CACHE / SESSION 切換
- [ ] `npm run build` 重新編譯前端（VITE_REVERB_* 才會生效）
- [ ] `php artisan config:cache` 套用設定
- [ ] 兩台不同網路的裝置實測 WebSocket 連線
- [ ] 監控（Pulse / Telescope）配好
- [ ] 部署 runbook 寫好（重啟、查 log、回滾）

---

## 常見問題

**Q1: 連線一直 disconnect / reconnect 怎麼辦？**
- Nginx 的 `proxy_read_timeout` 太短（預設 60 秒）
- Reverb 的 `ping_interval` 跟 Nginx timeout 不匹配
- 改 Nginx `proxy_read_timeout 60m;`

**Q2: 訊息送出但對方收不到？**
- 檢查 `BROADCAST_CONNECTION=reverb`（不是 log 也不是 null）
- Browser console 看 Echo 連線狀態（`window.Echo.connector.pusher.connection.state`）
- `php artisan reverb:debug` 看 channel 訂閱狀況
- Reverb log（`/var/log/supervisor/reverb.log`）

**Q3: 流量大、CPU 飆高？**
- 開 scaling + 多開 Reverb process
- broadcast 加 `ShouldQueue` 走 queue
- 升級到 Redis 7+（pub/sub 效能較佳）

**Q4: 部署到 Vercel / Heroku / 其他 PaaS？**
- 大多數 PaaS **不支援** WebSocket 長連線（或限制嚴格）
- 建議改用 Pusher 託管，或部署到支援長連線的 VPS（DigitalOcean / Linode / Hetzner）

---

## 參考資料

- 官方文件：https://reverb.laravel.com/docs
- Laravel Broadcasting：https://laravel.com/docs/broadcasting
- Echo 文件：https://laravel.com/docs/broadcasting#client-side-installation
