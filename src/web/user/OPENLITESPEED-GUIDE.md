# راهنمای حل مشکل 404 در OpenLiteSpeed

## مشکل فعلی
خطای 404 هنگام دسترسی به فایل‌های پروژه در OpenLiteSpeed

---

## راه‌حل‌های گام‌به‌گام

### مرحله 1️⃣: تست دسترسی به فایل‌ها

ابتدا بررسی کنید که آیا فایل‌ها اصلاً در دسترس هستند:

**الف) تست فایل HTML ساده:**
```
https://your-domain.com/web/user/test-simple.html
```

- ✅ اگر باز شد → فایل‌ها آپلود شده‌اند و مسیر درست است
- ❌ اگر 404 داد → مشکل در مسیر یا آپلود فایل‌ها

**ب) تست فایل PHP:**
```
https://your-domain.com/web/user/test-php.php
```

- ✅ اگر باز شد → PHP کار می‌کند
- ❌ اگر 404 داد → مشکل در تنظیمات OpenLiteSpeed یا مسیر

---

### مرحله 2️⃣: بررسی ساختار پوشه‌ها

ساختار صحیح باید این‌طور باشد:

```
/home/username/public_html/          (یا هر document root دیگر)
│
├── src/
│   ├── includes/
│   │   ├── config.php
│   │   ├── db.php
│   │   └── functions.php
│   │
│   ├── web/
│   │   ├── .htaccess
│   │   ├── index.php
│   │   ├── dashboard.php
│   │   │
│   │   ├── user/
│   │   │   ├── .htaccess
│   │   │   ├── index.php
│   │   │   ├── test-simple.html
│   │   │   ├── test-php.php
│   │   │   └── ... (سایر فایل‌ها)
│   │   │
│   │   └── pages/
│   │       └── ...
│   │
│   └── data/
│       └── ...
```

**در سرور این دستور را اجرا کنید:**
```bash
ls -la /path/to/your/public_html/src/web/user/
```

باید فایل‌های `test-simple.html` و `test-php.php` را ببینید.

---

### مرحله 3️⃣: بررسی و تنظیم مجوزها (Permissions)

**مشکل رایج در OpenLiteSpeed**: مجوزهای نادرست فایل‌ها

#### تنظیم مجوزهای صحیح:

```bash
# رفتن به پوشه اصلی پروژه
cd /path/to/your/public_html/src

# تنظیم مجوزها برای فایل‌ها (644)
find . -type f -exec chmod 644 {} \;

# تنظیم مجوزها برای پوشه‌ها (755)
find . -type d -exec chmod 755 {} \;

# تنظیم مجوز پوشه data برای نوشتن
chmod -R 775 data/
```

#### بررسی Owner (مالک فایل‌ها):

```bash
# بررسی کنید که مالک فایل‌ها کیست
ls -la /path/to/your/public_html/src/web/user/

# باید مالک nobody:nogroup یا username:username باشد
# اگر نبود، تغییر دهید:
chown -R nobody:nogroup /path/to/your/public_html/src/
# یا
chown -R username:username /path/to/your/public_html/src/
```

---

### مرحله 4️⃣: تنظیمات OpenLiteSpeed

#### الف) بررسی Virtual Host Configuration

1. وارد پنل OpenLiteSpeed شوید:
   ```
   https://your-server-ip:7080
   ```

2. به **Virtual Hosts** → **[your-domain]** بروید

3. بررسی کنید که:
   - **Document Root** درست تنظیم شده (مثلاً `/home/username/public_html/src/web`)
   - **Enable Scripts/ExtApps** روی `Yes` باشد

#### ب) فعال‌سازی Rewrite در OpenLiteSpeed

OpenLiteSpeed به طور پیش‌فرض از `.htaccess` پشتیبانی می‌کند، اما باید فعال باشد:

1. در پنل OpenLiteSpeed:
   - **Virtual Hosts** → **[your-domain]** → **Rewrite**
   
2. مطمئن شوید که:
   - **Enable Rewrite**: `Yes`
   - **Auto Load from .htaccess**: `Yes`

#### ج) راه‌اندازی مجدد OpenLiteSpeed

بعد از هر تغییر، OpenLiteSpeed را restart کنید:

```bash
# روش 1
systemctl restart lsws

# روش 2
/usr/local/lsws/bin/lswsctrl restart

# روش 3 (Graceful Restart - بدون قطع سرویس)
/usr/local/lsws/bin/lswsctrl graceful
```

---

### مرحله 5️⃣: بررسی فایل .htaccess

فایل `.htaccess` در OpenLiteSpeed ممکن است نیاز به تنظیمات خاصی داشته باشد.

#### بررسی/ایجاد `.htaccess` در پوشه `web/`:

```apache
# Protect web panel files
<Files "*.php">
    Order Allow,Deny
    Allow from all
</Files>

# Prevent directory listing
Options -Indexes

# Default document
DirectoryIndex index.php index.html

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# PHP settings (OpenLiteSpeed compatible)
<IfModule mod_php.c>
    php_value max_execution_time 300
    php_value memory_limit 256M
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
</IfModule>
```

#### بررسی/ایجاد `.htaccess` در پوشه `web/user/`:

```apache
# Disable caching for user panel
<IfModule mod_headers.c>
    Header set Cache-Control "no-cache, no-store, must-revalidate"
    Header set Pragma "no-cache"
    Header set Expires 0
</IfModule>

# Set default charset
AddDefaultCharset UTF-8

# Default document
DirectoryIndex index.php

# PHP settings
<IfModule mod_php.c>
    php_value max_execution_time 300
    php_value memory_limit 256M
</IfModule>
```

---

### مرحله 6️⃣: بررسی Log ها

اگر هنوز مشکل دارید، log ها را بررسی کنید:

```bash
# Error log سرور
tail -f /usr/local/lsws/logs/error.log

# Access log
tail -f /usr/local/lsws/logs/access.log

# Virtual Host error log (اگر جداگانه تنظیم کرده‌اید)
tail -f /path/to/your/vhost/logs/error.log
```

---

### مرحله 7️⃣: تنظیمات PHP در OpenLiteSpeed

اگر PHP کار نمی‌کند:

#### الف) بررسی PHP Handler

1. در پنل OpenLiteSpeed:
   - **Server Configuration** → **External App**
   
2. مطمئن شوید که `lsphp` به درستی تنظیم شده

#### ب) بررسی Script Handler

1. **Server Configuration** → **Script Handler**
2. مطمئن شوید که suffixes شامل `.php` است

---

## چک‌لیست سریع برای عیب‌یابی 404

- [ ] فایل‌ها در مسیر صحیح آپلود شده‌اند
- [ ] مجوزها درست است (644 برای فایل‌ها، 755 برای پوشه‌ها)
- [ ] Owner فایل‌ها درست است (nobody:nogroup یا user:user)
- [ ] Document Root در Virtual Host درست تنظیم شده
- [ ] Rewrite در Virtual Host فعال است
- [ ] Auto Load from .htaccess روشن است
- [ ] OpenLiteSpeed راه‌اندازی مجدد شده
- [ ] فایل `.htaccess` موجود و صحیح است
- [ ] PHP Handler درست کار می‌کند
- [ ] SSL/HTTPS فعال است (برای WebApp تلگرام ضروری)

---

## تست نهایی

بعد از انجام تنظیمات بالا، به ترتیب این URL ها را تست کنید:

1. `https://your-domain.com/web/user/test-simple.html`
   - باید صفحه سبز رنگ نمایش دهد
   
2. `https://your-domain.com/web/user/test-php.php`
   - باید اطلاعات سرور و PHP را نمایش دهد
   
3. `https://your-domain.com/web/user/index.php`
   - باید پنل کاربری یا صفحه لاگین را نمایش دهد

---

## نکات مهم برای OpenLiteSpeed

### 1. تفاوت با Apache
- OpenLiteSpeed از `.htaccess` پشتیبانی می‌کند، اما باید فعال شود
- برخی دستورات Apache در OLS کار نمی‌کنند (مثلاً `php_value` در برخی موارد)

### 2. Cache
OpenLiteSpeed دارای cache قوی است که ممکن است مشکل ایجاد کند:

```bash
# Flush cache
/usr/local/lsws/bin/lswsctrl flush
```

### 3. Session در OpenLiteSpeed
مطمئن شوید که پوشه session قابل نوشتن است:

```bash
# بررسی مسیر session
php -i | grep session.save_path

# تنظیم مجوز
chmod 1733 /path/to/session/dir
```

---

## کمک بیشتر

اگر هنوز مشکل حل نشد:

1. خروجی این دستورات را بفرستید:
```bash
ls -la /path/to/web/user/
cat /usr/local/lsws/logs/error.log | tail -n 50
php -v
```

2. اسکرین‌شات از:
   - تنظیمات Virtual Host در پنل OLS
   - خطای دقیق 404

3. بگویید که از کدام روش این URL را باز می‌کنید:
   - مستقیماً از مرورگر
   - از WebApp تلگرام
   - از curl یا ابزار دیگر
