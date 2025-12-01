# راهنمای سریع رفع خطای 404 در OpenLiteSpeed

## فایل‌های کمکی ایجاد شده

### 1. فایل‌های تست
- `test-simple.html` - تست فایل HTML ساده
- `test-php.php` - تست PHP و نمایش تنظیمات سرور
- `test-telegram-auth.php` - تست احراز هویت تلگرام

### 2. راهنماها
- `OPENLITESPEED-GUIDE.md` - راهنمای جامع OpenLiteSpeed
- `TROUBLESHOOTING-ACCESS-DENIED.md` - راهنمای رفع خطای Access Denied

### 3. اسکریپت نصب
- `setup-openlitespeed.sh` - اسکریپت تنظیم خودکار مجوزها

---

## راه‌حل سریع (5 دقیقه)

### قدم 1: آپلود فایل‌ها
همه فایل‌های پروژه را در سرور آپلود کنید.

### قدم 2: تنظیم مجوزها
در سرور این دستورات را اجرا کنید:

```bash
cd /path/to/your/project/src

# اجرای اسکریپت تنظیم خودکار
chmod +x setup-openlitespeed.sh
./setup-openlitespeed.sh
```

یا به صورت دستی:

```bash
# تنظیم مجوزها
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 data/

# تنظیم owner
chown -R nobody:nogroup .

# راه‌اندازی مجدد
systemctl restart lsws
```

### قدم 3: تست
به ترتیب این لینک‌ها را باز کنید:

1. `https://your-domain.com/web/user/test-simple.html`
2. `https://your-domain.com/web/user/test-php.php`
3. `https://your-domain.com/web/user/index.php`

---

## چک‌لیست

- [ ] فایل‌ها آپلود شدند
- [ ] مجوزها تنظیم شد (644 برای files، 755 برای folders)
- [ ] Owner درست است (nobody:nogroup)
- [ ] OpenLiteSpeed راه‌اندازی مجدد شد
- [ ] فایل config.php وجود دارد و BOT_TOKEN و BASE_URL تنظیم شده
- [ ] SSL/HTTPS فعال است
- [ ] دامنه در BotFather تنظیم شده

---

## اگر هنوز 404 می‌ده

1. Log ها را بررسی کنید:
```bash
tail -f /usr/local/lsws/logs/error.log
```

2. بررسی کنید که Virtual Host درست تنظیم شده:
   - پنل OLS: `https://server-ip:7080`
   - Virtual Hosts → Document Root را بررسی کنید

3. Rewrite را فعال کنید:
   - Virtual Hosts → Rewrite
   - Enable Rewrite: Yes
   - Auto Load from .htaccess: Yes

---

## پشتیبانی

برای کمک بیشتر، خروجی این دستورات را بفرستید:

```bash
ls -la /path/to/web/user/
cat /usr/local/lsws/logs/error.log | tail -n 50
php -v
cat /path/to/includes/config.php | grep -E "BOT_TOKEN|BASE_URL"
```
