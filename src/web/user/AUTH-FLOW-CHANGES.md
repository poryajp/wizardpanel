# تغییرات ایجاد شده در فلوی احراز هویت

## مشکل قبلی
کد قبلی در `index.php` به این صورت بود:

```php
require_once __DIR__ . '/session.php';
// ... handle POST request ...
requireUserLogin(); // ← این خط فوراً صفحه را می‌بست!
```

**نتیجه:** اگر کاربر لاگین نبود، PHP فوراً با پیام "Access Denied" صفحه را می‌بست و کد JavaScript هرگز اجرا نمی‌شد!

---

## راه‌حل جدید

### تغییرات در index.php

#### 1. حذف `requireUserLogin()` 
به جای die کردن، حالا چک می‌کنیم که آیا کاربر لاگین است یا خیر:

```php
$isLoggedIn = isUserLoggedIn();

if ($isLoggedIn) {
    $user = getCurrentUser();
    $services = getUserServices($user['chat_id']);
    // ... محاسبه آمار
} else {
    // مقادیر پیش‌فرض
    $user = ['first_name' => 'کاربر', 'chat_id' => 0, 'balance' => 0];
    $services = [];
    $total_services = 0;
    $active_services = 0;
    $expired_services = 0;
    $recent_services = [];
}
```

#### 2. نمایش Loading Screen
اگر کاربر لاگین نیست، صفحه loading نمایش می‌دهد:

```html
<div id="loading" class="loading-overlay" style="display: <?php echo $isLoggedIn ? 'none' : 'flex'; ?>;">
    <div class="spinner"></div>
    <p>در حال احراز هویت...</p>
</div>
```

#### 3. مخفی کردن محتوا
محتوای اصلی تا زمان لاگین مخفی است:

```html
<div class="container" style="<?php echo $isLoggedIn ? '' : 'display: none;'; ?>" id="main-content">
    <!-- محتوا -->
</div>
```

#### 4. احراز هویت خودکار با JavaScript
JavaScript بعد از لود صفحه، تلاش می‌کند کاربر را احراز هویت کند:

```javascript
const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

if (!isLoggedIn) {
    if (tg.initData) {
        // ارسال درخواست برای احراز هویت
        fetch('index.php', {
            method: 'POST',
            body: 'initData=' + encodeURIComponent(tg.initData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload(); // رفرش صفحه بعد از موفقیت
            } else {
                // نمایش خطا
                showError('خطا در احراز هویت');
            }
        });
    } else {
        // نمایش پیام: باید از تلگرام باز شود
        showError('لطفاً از داخل ربات تلگرام وارد شوید');
    }
}
```

---

## مزایای روش جدید

✅ **صفحه لود می‌شود**: دیگر "Access Denied" نمایش داده نمی‌شود  
✅ **Loading زیبا**: کاربر یک صفحه loading حرفه‌ای می‌بیند  
✅ **خطاهای دقیق**: اگر مشکلی باشد، دقیقاً می‌گوید چیست  
✅ **تست آسان**: می‌توانید مشکل احراز هویت را راحت‌تر debug کنید  

---

## فلوی کامل احراز هویت

```
1. کاربر از تلگرام لینک را باز می‌کند
   ↓
2. PHP چک می‌کند: آیا session دارد؟
   ├─ بله → محتوا نمایش داده می‌شود
   └─ خیر → loading نمایش داده می‌شود
          ↓
3. JavaScript اجرا می‌شود
   ↓
4. initData از تلگرام دریافت می‌شود
   ↓
5. POST request به index.php ارسال می‌شود
   ↓
6. PHP در auth.php، initData را validate می‌کند
   ├─ معتبر → session ذخیره + return success
   └─ نامعتبر → return error
          ↓
7. JavaScript نتیجه را دریافت می‌کند
   ├─ موفق → صفحه reload می‌شود
   └─ ناموفق → پیام خطا نمایش داده می‌شود
```

---

## نکات مهم برای عیب‌یابی

### اگر هنوز "خطا در احراز هویت" می‌دهد:

#### 1. بررسی Console مرورگر
- F12 را بزنید
- در tab **Console** ببینید چه خطایی هست
- باید log های زیر را ببینید:
  ```
  Attempting authentication...
  Authentication successful, reloading...
  ```

#### 2. بررسی Network Tab
- F12 → Network
- فیلتر "XHR" را انتخاب کنید
- ببینید POST request به index.php چه response می‌دهد
  - 200 + `{"success": true}` → موفق
  - 401 + `{"success": false, "error": "..."}` → validation شکست خورد

#### 3. بررسی BOT_TOKEN
```bash
# در سرور
cat /path/to/includes/config.php | grep BOT_TOKEN
```

باید توکن واقعی ربات را نشان دهد، نه 'TOKEN'

#### 4. بررسی تنظیمات BotFather
- به [@BotFather](https://t.me/BotFather) بروید
- `/mybots` → انتخاب ربات → Bot Settings → Menu Button
- مطمئن شوید URL دقیقاً با دامنه شما مطابقت دارد

---

## صفحات دیگر

**توجه:** صفحات زیر هنوز از روش قدیمی استفاده می‌کنند:
- `services.php`
- `shop.php`
- `wallet.php`
- `support.php`
- `guides.php`
- `account.php`
- `renew.php`

**گزینه 1:** می‌توانید از `requireUserLogin()` استفاده کنید چون فقط index.php باید از طریق تلگرام باز شود

**گزینه 2:** اگر می‌خواهید همه صفحات به صورت مستقل کار کنند، باید همین منطق را در همه صفحات پیاده کنید

---

## تست کنید

بعد از آپلود فایل جدید:

1. Session را پاک کنید:
   ```javascript
   // در Console مرورگر
   sessionStorage.clear();
   localStorage.clear();
   ```

2. صفحه را از تلگرام باز کنید

3. باید یکی از این‌ها را ببینید:
   - ✅ Loading → بعد از 1-2 ثانیه رفرش و نمایش محتوا
   - ❌ Loading → پیام خطا (که حداقل نشان می‌دهد مشکل دقیقاً چیست)

4. Console مرورگر را بررسی کنید برای جزئیات

---

## نتیجه‌گیری

با این تغییرات:
- **مشکل "Access Denied" حل شد** چون صفحه دیگر فوراً نمی‌میرد
- **تجربه کاربری بهتر** با loading screen
- **Debug آسان‌تر** با پیام‌های دقیق

اگر هنوز مشکل دارید، لطفاً:
1. Screenshot از Console را بفرستید
2. Response از Network tab را بفرستید
3. بگویید دقیقاً چه پیامی نمایش می‌دهد
