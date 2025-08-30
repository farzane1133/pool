=== Pool Sessions (Jalali) ===
Contributors: hoseinmos
Tags: calendar, jalali, persian, pool, sessions, events, rtl
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

افزونه حرفه‌ای مدیریت سانس‌های استخر با تقویم شمسی، فیلتر جنسیت و سرویس، و ایمپورت CSV/ICS

== Description ==

افزونه **Pool Sessions (Jalali)** یک راه‌حل کامل برای مدیریت سانس‌های استخر با پشتیبانی کامل از تقویم شمسی (جلالی) است.

= ویژگی‌های کلیدی =

* **تقویم شمسی کامل**: پشتیبانی از تقویم جلالی با تبدیل خودکار تاریخ‌ها
* **فیلتر پیشرفته**: فیلتر بر اساس جنسیت (آقا/خانم/هردو) و سرویس‌ها
* **رابط کاربری ریسپانسیو**: طراحی مینیمال و موبایل‌فرست
* **ایمپورت داده**: پشتیبانی از فایل‌های CSV و ICS
* **مدیریت سرویس‌ها**: تعریف و مدیریت سرویس‌ها با رنگ‌های اختصاصی
* **تنظیمات پیشرفته**: شخصی‌سازی کامل ظاهر و رفتار تقویم
* **پشتیبانی RTL**: سازگار کامل با زبان فارسی

= کاربردها =

* استخرها و مراکز ورزشی
* سالن‌های ماساژ و اسپا
* مراکز درمانی و فیزیوتراپی
* باشگاه‌های ورزشی
* هر مرکزی که نیاز به مدیریت زمان‌بندی دارد

= شورت‌کد =

```
[pool_calendar]
[pool_calendar gender="male"]
[pool_calendar service="pool" gender="female"]
[pool_calendar initial_year="1404" initial_month="6"]
```

== Installation ==

= نصب از فایل ZIP =

1. فایل `pool-sessions-jalali.zip` را دانلود کنید
2. در پنل ادمین وردپرس، به بخش **افزونه‌ها > افزودن** بروید
3. روی **بارگذاری افزونه** کلیک کنید
4. فایل ZIP را انتخاب کرده و **اکنون نصب کن** را بزنید
5. افزونه را فعال کنید

= نصب دستی =

1. محتویات پوشه را در مسیر `/wp-content/plugins/pool-sessions-jalali/` کپی کنید
2. از پنل ادمین، افزونه را فعال کنید

== Frequently Asked Questions ==

= آیا این افزونه با وردپرس 6.0 سازگار است؟ =

بله، این افزونه برای وردپرس 6.0 و بالاتر طراحی شده است.

= آیا می‌توانم رنگ‌های تقویم را تغییر دهم؟ =

بله، از طریق پنل تنظیمات می‌توانید تمام رنگ‌ها را شخصی‌سازی کنید.

= آیا پشتیبانی از موبایل دارد؟ =

بله، رابط کاربری کاملاً ریسپانسیو است و از جسچرهای لمسی پشتیبانی می‌کند.

= آیا می‌توانم فایل CSV ایمپورت کنم؟ =

بله، پشتیبانی کامل از ایمپورت CSV با نگاشت ستون‌ها وجود دارد.

= آیا API برای توسعه‌دهندگان دارد؟ =

بله، REST API کامل با مستندات ارائه شده است.

== Screenshots ==

1. تقویم اصلی با فیلترهای جنسیت و سرویس
2. پنل تنظیمات با گزینه‌های شخصی‌سازی
3. مدیریت سرویس‌ها
4. صفحه ایمپورت/اکسپورت
5. نمایش موبایل

== Changelog ==

= 1.0.0 =
* انتشار اولیه
* تقویم شمسی کامل
* فیلتر جنسیت و سرویس
* ایمپورت CSV/ICS
* رابط کاربری ریسپانسیو
* پشتیبانی RTL

== Upgrade Notice ==

= 1.0.0 =
نسخه اولیه با تمام ویژگی‌های اصلی

== Developer Notes ==

= REST API Endpoints =

* `GET /wp-json/pool/v1/sessions` - دریافت سانس‌ها
* `GET /wp-json/pool/v1/services` - دریافت سرویس‌ها
* `POST /wp-json/pool/v1/import/csv` - ایمپورت CSV
* `POST /wp-json/pool/v1/import/ics` - ایمپورت ICS

= Hooks and Filters =

افزونه از WordPress Hooks و Filters استاندارد استفاده می‌کند.

= Database Schema =

* Custom Post Type: `pool_session`
* Meta Fields: gender, service, start_datetime, end_datetime, note, capacity

== Credits ==

* **نویسنده**: hoseinmos
* **مجوز**: MIT
* **پشتیبانی**: GitHub Issues

== Support ==

برای پشتیبانی و گزارش مشکلات:

* GitHub: [@hoseinmos](https://github.com/hoseinmos)
* مستندات کامل در README.md موجود است
