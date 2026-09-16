# Arabic Encoding Production Diagnosis

في 2026-08-23، أظهر فحص phpMyAdmin على استضافة iFastNet أن إعداد خادم MariaDB الافتراضي يستخدم `cp1252`، بينما مخطط SouqLink ينشئ الجداول من دون تحديد صريح لـ `DEFAULT CHARACTER SET utf8mb4` و`COLLATE utf8mb4_unicode_ci`.

تتصل طبقة PDO محلياً بالفعل باستخدام `charset=utf8mb4`، لكن الجداول التي ورثت ترميز الخادم لا تستطيع حفظ الأحرف العربية، وهو ما يفسر تخزين الاسم كعلامات استفهام وعرضه كذلك في تطبيق Flutter. يلزم تحويل قاعدة الإنتاج والجداول إلى `utf8mb4` وإضافة ترميز صريح إلى المخطط. القيم التي تحولت سابقاً إلى `?` لا يمكن استعادتها من الترميز، ويجب أن يعيد صاحبها إدخالها بعد الإصلاح.

## نشر ملفات API عبر cPanel

تؤكد وثائق cPanel الرسمية أن مسار UAPI `Fileman/upload_files` يقبل طلب `multipart/form-data` موثقاً بحساب cPanel، مع `dir` للمجلد المستهدف وحقل ملف مثل `file-1`. يمكن استخدامه لنشر ملف PHP بديل من دون الاعتماد على FTP عندما يحظر الخادم اتصال FTP الصادر. يجب إرسال المحتوى بترميز UTF-8 وعدم تضمين أي إعدادات سرية في الحزمة أو السجل.

المصدر: https://api.docs.cpanel.net/guides/quickstart-development-guide/tutorial-use-uapis-fileman-upload-files-function-in-custom-code
