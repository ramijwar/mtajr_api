# عقد REST API — SouqLink v1

## الاتفاقيات العامة

جميع المسارات تبدأ بـ `/api/v1` وتستخدم JSON بترميز UTF-8. يتضمن كل طلب مصادق عليه ترويسة `Authorization: Bearer <access-token>`. تعاد أخطاء التحقق بالصيغة `{ "error": { "code": "validation_error", "message": "...", "fields": { } } }`، ولا يُعاد أي استثناء أو مسار ملفات داخلي للعميل.

| المجال | المسارات الجوهرية |
|---|---|
| المصادقة | `POST /auth/register`، `POST /auth/login`، `POST /auth/refresh`، `POST /auth/logout`، `GET /auth/me` |
| المتاجر والمنتجات | `GET /stores`، `GET /stores/{id}`، `POST /merchant/stores`، `PATCH /merchant/stores/{id}`، `GET /products`، `GET /products/{id}`، `POST /merchant/products`، `PATCH /merchant/products/{id}`، `DELETE /merchant/products/{id}` |
| الوسائط | `POST /uploads/product-image`، `POST /uploads/delivery-proof`، `POST /uploads/ticket-attachment` |
| السلة والطلبات | `GET /cart`، `PUT /cart/items/{productId}`، `DELETE /cart/items/{productId}`، `POST /orders/quote`، `POST /orders`، `GET /orders`، `GET /orders/{id}` |
| الدفع | `POST /orders/{id}/payment-receipts`، `POST /admin/payment-receipts/{id}/verify`، `POST /admin/payment-receipts/{id}/reject` |
| العناوين | `GET /addresses`، `POST /addresses`، `PATCH /addresses/{id}`، `DELETE /addresses/{id}` |
| التوصيل | `GET /courier/tasks`، `POST /courier/tasks/{id}/accept`، `POST /courier/tasks/{id}/reject`، `POST /courier/tasks/{id}/eta`، `POST /courier/tasks/{id}/proof` |
| المحافظ | `GET /wallets`، `GET /wallets/{id}/transactions`، `POST /withdrawals`، `GET /withdrawals`، `POST /admin/withdrawals/{id}/approve`، `POST /admin/withdrawals/{id}/reject` |
| التقييم والدعم | `POST /products/{id}/reviews`، `GET /products/{id}/reviews`، `GET /support/tickets`، `POST /support/tickets`، `POST /support/tickets/{id}/messages` |
| الإدارة | `GET /admin/dashboard`، `GET /admin/users`، `PATCH /admin/users/{id}`، `GET /admin/audit-logs`، `GET /admin/finance/transactions` |

## إنشاء الطلب

ينفّذ `POST /orders` داخل معاملة MySQL. يقفل المنتجات المطلوبة بـ `SELECT ... FOR UPDATE`، يتحقق من كمية المخزون والعملة وتبعية العناصر لمتجر واحد، يلتقط السعر ورسوم التوصيل في `order_items`، ويعيد حساب الإجمالي. إذا اختار العميل التوصيل، يجمع رسوم `unit_delivery_fee × quantity` ويوثق العنوان والإحداثيات. يعيد الطلب مع `sham_cash_address_snapshot` لكي يعرضه التطبيق قبل أن يرسل العميل رقم معاملة الدفع.

## التحقق من الدفع وإنشاء المهمة

يرسل العميل رقم المعاملة والمبلغ إلى `POST /orders/{id}/payment-receipts`. لا ينشئ الخادم مهمة توصيل بعد الإرسال وحده. عند التحقق الإداري من السند، ينقل الطلب إلى `paid` ثم ينشئ مهمة بحالة `available` بعد وضع الطلب في `ready_for_delivery`. يستدعي منطق الإشعارات جميع عمال التوصيل ذوي الاشتراك `active` في متجر الطلب.

## قواعد رفع الصور

تستقبل مسارات الرفع `multipart/form-data` فقط، مع مفاتيح معلنة مثل `image` أو `attachment`. يقبل الخادم JPEG وPNG وWEBP فقط، ويتحقق من النوع الحقيقي لا امتداد الملف، ويحد الحجم بعد الضغط إلى 4 ميغابايت للمنتجات و5 ميغابايت لإثبات التسليم. يحفظ اسم عشوائي خارج جذر الويب ويرجع رابطاً موقّعاً أو مسار CDN آمن.

## البحث والفلترة الإدارية

تدعم المسارات الإدارية معاملات ثابتة مثل `q` و`status` و`role` و`currency` و`from` و`to` و`page` و`per_page`، ويكون الترتيب الافتراضي بالأحدث. تستعمل واجهة الإدارة طلباً مؤجلاً قصيراً للبحث الفوري، بينما يستعمل الخادم استعلامات محددة الأعمدة وفهارس توافق أعمدة الفلترة الأكثر تكراراً.

