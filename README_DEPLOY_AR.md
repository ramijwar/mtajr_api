# حزمة خادم منصة تجارتي الكاملة (SQLite)

هذه هي نسخة خادم PHP الكاملة المتوافقة مع قاعدة بيانات **SQLite 3**. ارفع محتويات هذا المجلد إلى `mtajr_api/` بحيث يكون الملف التنفيذي الفعلي في `mtajr_api/index.php`.

## الملفات الأساسية

يحتوي الخادم على `index.php`، و`src/`، و`.htaccess`، و`config.local.php.example`، ومخطط SQLite الكامل داخل `database/schema.sqlite.sql`، وبيانات بذور المدن العربية داخل `database/seed_marketplace_arab_cities.sqlite.sql`، وسكربت التهيئة `database/init_sqlite.php`، إضافة إلى وثائق التشغيل داخل `docs/`.

## الإعداد

1. انسخ `config.local.php.example` إلى `config.local.php` على الاستضافة، واضبط مسار قاعدة البيانات `DB_PATH` و`JWT_SECRET` و`CORS_ORIGINS`.
2. تأكد من منح مستخدم PHP صلاحية الكتابة على المجلد `storage/` ومجلد `storage/uploads/`.
3. لا تحتاج إلى إعداد خادم MySQL منفصل؛ تعمل المنصة بالكامل مع ملف SQLite داخل `storage/database.sqlite`.

## إنشاء قاعدة البيانات

- **تلقائياً**: إذا لم تكن قاعدة البيانات موجودة، سيقوم الخادم تلقائياً بإنشاء ملف قاعدة البيانات وتطبيق المخطط الكامل وزرع البيانات المرجعية عند أول طلب.
- **يدوياً**: يمكنك تهيئة قاعدة البيانات في أي وقت عبر سطر الأوامر:
  ```bash
  php database/init_sqlite.php
  ```
  أو لإعادة ضبطها من الصفر:
  ```bash
  php database/init_sqlite.php --force
  ```

## إعلان التاجر المحلي

يعالج `index.php` مسار إعلان التاجر:

`POST /mtajr_api/merchant/stores/{storeId}/advertisements/create-with-image`

يرسل التطبيق الطلب بصيغة `multipart/form-data` مع الحقل `image` وحقول `target_type` و`target_product_id` أو `target_url`. الإعلان يحفظ في `store_advertisements` ويظهر داخل المتجر المحدد فقط.
