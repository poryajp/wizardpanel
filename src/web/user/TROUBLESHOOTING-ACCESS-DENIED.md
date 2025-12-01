# راهنمای رفع مشکل "Access Denied. Please open from Telegram"

## مشکل
پس از انتقال پروژه به سرور اصلی با دامنه جدید، پیام خطای زیر نمایش داده می‌شود:
```
Access Denied. Please open from Telegram.
```

## علت‌های احتمالی

### 1. عدم تنظیم دامنه در BotFather (احتمال بالا ⭐⭐⭐⭐⭐)
تلگرام برای امنیت، فقط اجازه می‌دهد Web App از دامنه‌های مشخص شده باز شود.

**راه حل:**
1. در تلگرام به [@BotFather](https://t.me/BotFather) پیام دهید
2. دستور `/mybots` را ارسال کنید
3. ربات خود را انتخاب کنید
4. گزینه **Bot Settings** را انتخاب کنید
5. گزینه **Menu Button** را انتخاب کنید
6. گزینه **Edit Menu Button URL** را انتخاب کنید
7. URL جدید خود را وارد کنید (مثال: `https://your-domain.com/web/user/`)

**نکته مهم:** URL باید با `https://` شروع شود و دامنه باید دارای گواهی SSL معتبر باشد.

---

### 2. مشکل با تنظیمات BASE_URL در config (احتمال متوسط ⭐⭐⭐)
اگر BASE_URL در فایل کانفیگ به درستی تنظیم نشده باشد،ممکن است مشکلاتی پیش بیاید.

**بررسی:**
- فایل: `src/includes/config.php`
- خط 14: بررسی کنید که `BASE_URL` دقیقاً با دامنه واقعی شما مطابقت داشته باشد
- **بدون اسلش در انتها**: ❌ `https://domain.com/` | ✅ `https://domain.com`

**مثال صحیح:**
```php
define('BASE_URL', 'https://your-domain.com');
```

---

### 3. مشکل Session در PHP (احتمال متوسط ⭐⭐⭐)
ممکن است تنظیمات session در سرور جدید درست کار نکند.

**راه حل:**
1. بررسی کنید که پوشه `session` قابل نوشتن باشد
2. اضافه کردن تنظیمات زیر به `.htaccess`:

```apache
# Session settings
php_value session.cookie_secure 1
php_value session.cookie_httponly 1
php_value session.cookie_samesite Lax
```

---

### 4. مشکل HTTPS/SSL (احتمال بالا ⭐⭐⭐⭐)
تلگرام فقط از دامنه‌های با HTTPS معتبر پشتیبانی می‌کند.

**بررسی:**
- آیا دامنه شما گواهی SSL معتبر دارد؟
- آیا وقتی به `https://your-domain.com` می‌روید، قفل سبز نمایش می‌دهد؟
- آیا certificate از یک CA معتبر صادر شده (مثل Let's Encrypt)؟

**راه حل:**
اگر SSL ندارید، از Let's Encrypt استفاده کنید (رایگان):
```bash
# برای cPanel
# از SSL/TLS Manager استفاده کنید و Let's Encrypt را فعال کنید
```

---

### 5. عدم همخوانی BOT_TOKEN (احتمال پایین ⭐⭐)
اگر BOT_TOKEN در سرور جدید اشتباه باشد، احراز هویت شکست می‌خورد.

**بررسی:**
- فایل: `src/includes/config.php`
- خط 7: بررسی کنید که `BOT_TOKEN` دقیقاً با توکن ربات شما مطابقت دارد

---

## گام‌های عیب‌یابی (به ترتیب اولویت)

### مرحله 1: تست صفحه دیباگ
1. فایل `test-telegram-auth.php` را در سرور آپلود کنید (در پوشه `web/user/`)
2. از طریق ربات تلگرام، به آدرس زیر بروید:
   ```
   https://your-domain.com/web/user/test-telegram-auth.php
   ```
3. اطلاعات نمایش داده شده را بررسی کنید:
   - آیا `initData` خالی است؟ → مشکل با تنظیمات BotFather
   - آیا `BOT_TOKEN` تنظیم شده؟
   - آیا HTTPS فعال است؟

### مرحله 2: بررسی تنظیمات BotFather
1. به BotFather بروید و Menu Button URL را بررسی کنید
2. مطمئن شوید که URL دقیقاً با دامنه سرور شما مطابقت دارد
3. URL باید به صفحه `index.php` در پوشه `web/user/` اشاره کند

### مرحله 3: بررسی فایل config.php
```php
// باید این‌طور باشد:
define('BOT_TOKEN', 'اینجا_توکن_واقعی_ربات_شما');
define('BASE_URL', 'https://your-actual-domain.com'); // بدون / در انتها
```

### مرحله 4: بررسی SSL
از سایت [SSL Labs](https://www.ssllabs.com/ssltest/) وضعیت SSL دامنه خود را بررسی کنید.

---

## راه‌حل سریع (Quick Fix)

اگر می‌خواهید سریع تست کنید که آیا مشکل از BotFather است یا خیر:

1. در فایل `session.php` (خط 25)، موقتاً کد زیر را کامنت کنید:
```php
// die('Access Denied. Please open from Telegram.');
```

2. به جای آن، این کد را اضافه کنید:
```php
// Temporary: Show detailed error
echo "<pre>";
echo "Session Data: ";
print_r($_SESSION);
echo "\n\nServer Data: ";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? 'Yes' : 'No') . "\n";
echo "Host: " . $_SERVER['HTTP_HOST'] . "\n";
echo "</pre>";
die();
```

3. صفحه را از تلگرام باز کنید و ببینید چه خروجی می‌دهد

---

## نکات امنیتی

⚠️ **هشدار**: بعد از حل مشکل:
1. فایل‌های تست (`test-telegram-auth.php`) را حذف کنید
2. کدهای دیباگ موقت را پاک کنید
3. مطمئن شوید که `BOT_TOKEN` و `SECRET_TOKEN` در `config.php` قوی هستند

---

## تماس برای کمک

اگر هنوز مشکل حل نشد:
1. نتیجه صفحه `test-telegram-auth.php` را بفرستید
2. بگویید که آیا دامنه SSL دارد یا خیر
3. بگویید که آیا Menu Button URL در BotFather را آپدیت کرده‌اید یا خیر
