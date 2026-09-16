-- Arabic city reference data for Marketplace locations.
-- Source: jamsshhayd/world-cities-translations, CC BY 4.0, based on Wikidata.
-- Generated locally; do not edit manually. Areas remain administrator-managed.

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'إربد', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الجويدة', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الرصيفة', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الرمثا', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'السلط', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الشجرة', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الطرة', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الطفيلة', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'العقبة', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الكرك', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المزار الشمالي (الأردن)', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المفرق', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حلاوة', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'صحابة', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عَمَّان', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كفرعوان', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'لواء الجيزة', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'لواء بصيرا', 27, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'لواء قصبة عجلون', 28, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مؤتة', 29, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مادبا', 30, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة جرش', 31, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية الحصن', 32, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مركز الزرقا', 33, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الأردن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أبو ظبي', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الإمارات العربية المتحدة' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أم القيوين', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الإمارات العربية المتحدة' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الشارقة', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الإمارات العربية المتحدة' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الفجيرة', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الإمارات العربية المتحدة' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'خورفكان', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الإمارات العربية المتحدة' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دبي', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الإمارات العربية المتحدة' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'رأس الخيمة', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الإمارات العربية المتحدة' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عجمان', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الإمارات العربية المتحدة' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'نادي الجزيرة الحمراء', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الإمارات العربية المتحدة' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المالكية', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='البحرين' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المحرق', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='البحرين' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المنامة', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='البحرين' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جد حفص', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='البحرين' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مدينة حمد', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='البحرين' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مدينة عيسى', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='البحرين' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أرزيو', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أرمري', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أفلو', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أولاد ايعيش', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أولاد جلال', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الأخضرية', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'البرواقية', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الجزائر العاصمة', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الخروب', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الدار البيضاء', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الرويبة', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الرويسات', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'السانية', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'السوقر', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الشريعة', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الشقفة', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'العطاف', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'العلمة', 27, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المحمدية', 28, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المشرية', 29, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الميلية', 30, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بئر الجير', 31, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بئر العاتر', 32, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بئر خادم', 33, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'باب الزوار', 34, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'باتنة', 35, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بجاية', 36, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'برج الكيفان', 37, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'برج بوعريريج', 38, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'برج منايل', 39, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بريكة', 40, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بلدية الرغاية', 41, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بلدية بابا حسن', 42, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بودواو', 43, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بوسعادة', 44, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بوفاريك', 45, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تقرت', 46, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تلمسان', 47, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تيزي وزو', 48, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جماعة دفاع عن المصالح', 49, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حاسي بحبح', 50, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حامة بوزيان', 51, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حجوط', 52, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'خميس الخشنة', 53, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'خميس مليانة', 54, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دائرة تغنيف', 55, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دائرة قصر البخاري', 56, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سطيف', 57, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سعيدة', 58, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سوق أهراس', 59, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سيدي الشحمي', 60, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سيدي عيسى', 61, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'شلغوم العيد', 62, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طاهر', 63, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عزابة', 64, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عنابة', 65, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عين البيضاء', 66, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عين التوتة', 67, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عين مليلة', 68, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عين وسارة', 69, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عين ولمان', 70, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قالمة', 71, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قسطنطين', 72, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مروانة', 73, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مستغانم', 74, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مسعد', 75, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مسكيانة', 76, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مغنية', 77, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ميدي', 78, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ميلا', 79, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'وادي ارهيو', 80, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية أدرار', 81, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية أم البواقي', 82, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية الأغواط', 83, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية البويرة', 84, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية البيض', 85, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية الجلفة', 86, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية الشلف', 87, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية المسيلة', 88, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية الوادي', 89, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية بسكرة', 90, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية بشار', 91, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية تبسة', 92, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية تمنراست', 93, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية تيارت', 94, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية تيسمسيلت', 95, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية جيجل', 96, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية خنشلة', 97, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية سكيكدة', 98, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية سيدي بلعباس', 99, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية عين الدفلى', 100, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية عين تموشنت', 101, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية غرداية', 102, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية غليزان', 103, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية ورقلة', 104, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'وهران', 105, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الجزائر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أبو حمد', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أم بادر', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أم درمان', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أم روابة', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الأبيض', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الأمازيغية', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الجنينة', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الخرطوم', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الخرطوم بحري', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الدامر', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الدمازين', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الروصيرص', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الضعين', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القضارف', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القطينة', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الكرمك', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'النهود', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بورتسودان', 27, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'زالنجي', 28, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سنغافورة', 29, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سواكن', 30, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'شندي', 31, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كتم', 32, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كرمة البلد', 33, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كريمة', 34, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مدينة مراوي', 35, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مطار الفاشر', 36, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'نهر عطبرة', 37, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'نيالا', 38, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', '‏وادي حلفا', 39, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='السودان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أفجوي', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أفمدو', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أفين', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بارديرا', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بلد وين', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بندر بيلا‎', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بوالي', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بورهكبا', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بوصاصو', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بوهودلي', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بيدوا', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جالكعيو', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جربهاري', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جلالقسي', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جمامة', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جوهر (اسم)', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حررطيري', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حودر', 27, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دنجوريو', 28, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ديناصور', 29, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'زيلع', 30, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طوس مريب', 31, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'علولة', 32, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عيل بور', 33, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'غاروي', 34, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قرضو', 35, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قندلة', 36, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قوريولي', 37, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'لوق', 38, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مقديشو', 39, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'هبيا', 40, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'هرجيسا', 41, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'هرديو', 42, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ونلوين', 43, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الصومال' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'آمرلي', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'آناهايم', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أبو غريب', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أم قصر', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الإسكندرية', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'البصرة', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'التجمع الوطني الديمقراطي', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الحلة', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الحي', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الدجيل', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الديوانية', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الرمادي', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الزبيدية', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الزبير', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الشامية', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الشطرة', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الصويرة', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'العزيزية', 27, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'العمارة', 28, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الفاو', 29, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القائم', 30, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القاسم', 31, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القرنة', 32, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الكوفة', 33, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المسيب', 34, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المقدادية', 35, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الناصرية', 36, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'النجف', 37, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'النعمانية', 38, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الهاشمية', 39, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الهندية', 40, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بعقوبة', 41, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بغداد', 42, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بهرز', 43, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بيجي', 44, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تكريت', 45, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جمجمال', 46, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حديثة', 47, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حلبجة', 48, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حمام العليل', 49, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'رواندز', 50, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'زاخو', 51, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'زرباطية', 52, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سنجار', 53, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سيميل', 54, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طوز خورماتو', 55, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عقرة', 56, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'غماس', 57, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'فاموتيدين', 58, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قضاء شقلاوة', 59, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قلة الصفيحات المحدثة بالهيبارين', 60, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قلعة سكر', 61, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كركوك', 62, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كفري', 63, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كلار', 64, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة السليمانية', 65, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة دهوك', 66, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة كربلاء', 67, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مخمور', 68, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مطار تلعفر العسكري', 69, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'معركة الرطبة', 70, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ملعب السماوة', 71, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ملعب الفلوجة', 72, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مندلي', 73, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ناحية حرير', 74, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'نادي جلولاء', 75, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='العراق' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الأحمدي', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الكويت' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الرقة', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الكويت' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة الجهراء', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الكويت' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مدينة الكويت', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='الكويت' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أزرو', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أزمور', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أكادير', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أورير', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أولاد تايمة', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أيت ملول', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'إقليم تارودانت', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'إقليم خنيفرة', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'إقليم سيدي بنور', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'إقليم شيشاوة', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'إقليم وزان', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'إنزكان', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'التمسية', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الجديدة', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الحسيمة', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الدار البيضاء', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الرباط', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الرشيدية', 27, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'السمارة', 28, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الصخيرات', 29, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الصويرة', 30, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'العرائش', 31, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'العروي', 32, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'العيون', 33, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القصر الكبير', 34, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القنيطرة', 35, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المحمدية', 36, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المضيق', 37, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المهدية', 38, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'اليوسفية', 39, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بركان', 40, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بن جرير', 41, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بني انصار', 42, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بني ملال', 43, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بني يخلف', 44, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بوسكورة', 45, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تازة', 46, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تاوريرت', 47, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تاونات', 48, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تزنيت', 49, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تطوان', 50, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تمارة', 51, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تمصلوحت', 52, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تنغير', 53, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تيط مليل', 54, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تيفلت', 55, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جرادة', 56, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جرسيف', 57, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سطات', 58, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سيدي بوقنادل', 59, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سيدي بيبي', 60, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سيدي سليمان', 61, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سيدي قاسم', 62, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سيدي محمد الأحمر', 63, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سيدي يحيى الغرب', 64, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سيدي يحيى زعير', 65, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'شفشاون', 66, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'صافي', 67, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'صفرو', 68, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طانطان', 69, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طنجة', 70, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عين العودة', 71, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عين حرودة', 72, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'فاس', 73, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قصر تابونت', 74, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كزناية', 75, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كلميم', 76, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مراكش', 77, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مريرت', 78, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مكناس', 79, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مولاي عبد الله', 80, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ميدلت', 81, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'وادي زم', 82, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'وجدة-أنجاد', 83, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ويسلان', 84, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='المغرب' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المسراخ', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المكلا', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المنصورة', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دماج', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ريدة', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'زبيد', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'زنجبار', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سنة', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عتق', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'عزلة الشحر', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'فيروسات غدانية', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة إب', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة البيضاء', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة الحديدة', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة الضالع', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة المحويت', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة تعز', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة حجة', 27, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة ذمار', 28, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة لحج', 29, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة مأرب', 30, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية التواهي', 31, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية الديس', 32, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية الزيدية', 33, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية الغيضة', 34, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية باجل', 35, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية بيت الفقيه', 36, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية تريم', 37, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية جبلة', 38, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية حديبو', 39, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية خمر', 40, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية سيئون', 41, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية يريم', 42, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مدينة القاعده', 43, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'نادي الحزم', 44, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='اليمن' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أجيم', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أكودة', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'اريانا', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'البقالطة', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الجديدة', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الحامة', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الحمامات', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الدهماني', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الرديف', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الزهراء', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الساحلين', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الشابة', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'العالية', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الفحص', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القصر', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القلعة الكبرى', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القيروان', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الكاف', 27, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الكرم', 28, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المتحف الأثري بالجم', 29, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المتلوي', 30, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المرسى', 31, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المرناقية', 32, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المطوية', 33, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المكنين', 34, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المهدية', 35, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'باجة', 36, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'باردو', 37, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بنزرت', 38, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بنقردان', 39, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بني خلاد', 40, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بني خيار', 41, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بوتلي', 42, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تاجروين', 43, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تاكلسة', 44, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تبرسق', 45, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'توزر', 46, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تونس', 47, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جرجيس', 48, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جندوبة', 49, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حلق الوادي', 50, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حمام الأنف', 51, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حمام سوسة', 52, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حومة السوق', 53, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دار شعبان الفهري', 54, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'زاوية سوسة', 55, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سبيطلة', 56, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سكرة، تونس', 57, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سوسة', 58, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طبربة', 59, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طبرقة', 60, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طبلبة', 61, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'غار الدماء', 62, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'فوشانة', 63, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قابس', 64, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قرمبالية', 65, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قصر هلال', 66, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قصور الساف', 67, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قفصة', 68, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قليبية', 69, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كارثاغ', 70, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ماطر', 71, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مجاز الباب', 72, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مساكن', 73, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'معتمدية بئر علي بن خليفة', 74, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مقرين', 75, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'منزل بورقيبة', 76, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'منزل تميم', 77, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'منزل عبد الرحمان', 78, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'منوبة', 79, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ميدون', 80, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'نابل', 81, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'نفطة', 82, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'وادي ليلي', 83, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية القصرين', 84, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية تطاوين', 85, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية زغوان', 86, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية سيدي بوزيد', 87, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية قبلي', 88, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية مدنين', 89, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='تونس' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أدا دويني', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='جزر القمر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أواني', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='جزر القمر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الكوراني', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='جزر القمر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بازيميني', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='جزر القمر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دوموني', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='جزر القمر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'فومبوني', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='جزر القمر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'موتسامودو', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='جزر القمر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أوبوك', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='جيبوتي' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'إقليم تاجورة', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='جيبوتي' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جيبوتي', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='جيبوتي' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دخيل', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='جيبوتي' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'علي صبيح', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='جيبوتي' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أعزاز', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أنخل', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'البوكمال', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الحارة', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الحجر الأسود', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الرحيبة', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الرقة', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الشرق الأوسط وشمال إفريقيا', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الشيخ بدر', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'العشارة', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الغزلانية', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القامشلي', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القريا', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القريتين', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القصير', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القطيفة', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الكسوة', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'اللاذقية', 27, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'اللطامنة', 28, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المالكية', 29, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المجلس الوطني لقوى الثورة السلمية', 30, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'النبك', 31, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بانياس', 32, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بصرى', 33, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بنش', 34, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تادف', 35, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تارتو', 36, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تدمر', 37, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تل رفعت', 38, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تل شهاب', 39, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تل عرن', 40, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تلبيسة', 41, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تلكلخ', 42, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جاسم', 43, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جرمانا', 44, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جسر الشغور', 45, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جيرود', 46, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حرستا', 47, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حرملك', 48, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حلب', 49, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حماة', 50, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'خان شيخون', 51, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'داريا', 52, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دمشق', 53, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دير حافر', 54, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'زملكا', 55, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سراقب', 56, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سقبا', 57, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سلحب', 58, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سلقين', 59, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'شهبا', 60, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'صافيتا', 61, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'صبيخان', 62, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'صلخد', 63, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'صيدنايا', 64, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طفس', 65, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طيبة الإمام', 66, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'غريسيوفولفين', 67, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قطنا', 68, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كفر بطنا', 69, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كفرزيتا', 70, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كفرلاها', 71, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كفرنبل', 72, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مارية', 73, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مانين', 74, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة إدلب', 75, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة الحسكة', 76, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة السويداء', 77, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة دير الزور', 78, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية الثورة', 79, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مديرية جبلة', 80, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مصياف', 81, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'معرة النعمان', 82, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'منبج', 83, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'منطقة الزبداني', 84, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'منطقة عين العرب', 85, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ناحية مركز الرستن', 86, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ناحية مركز الصنمين', 87, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ناحية مركز الميادين', 88, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ها جين', 89, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'يبرود', 90, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='سوريا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'البريمي', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الرستاق', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المضيبي', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'صلالة', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قلعة بهلاء', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مسقط', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مطرة', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية إزكي', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية المزيونة', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية بوشر', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية خصب', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية شناص', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية طاقة', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية قريات', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية مرباط', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية نخل', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ولاية نزوى', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='عُمان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الجميلية', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='قطر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الخور والدخيرة', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='قطر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الدوحة', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='قطر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الريان', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='قطر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'نادي الوكرة', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='قطر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أميون', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'إطار هواء', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'إهدن', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'البترون', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الدامور', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الصرفند', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القعدي', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'النبطية', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الهرمل', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بتغرين', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بشري', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بعبدا', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بعقلين', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بعلبك', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بنت جبيل', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بيروت', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جبيل', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جونيه', 27, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حصرون', 28, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'حقل زراعي', 29, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'رأس المتن', 30, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'زغرتا', 31, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'شرتون', 32, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'شمسطار', 33, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'صريفا', 34, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ضبية', 35, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طرابلس', 36, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قناة ري', 37, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كفركدة', 38, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='لبنان' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أجدابيا', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أم الرزم', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أوباري', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الأبرق', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الأوجلية', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الجميلية', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الخمس', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الزاوية', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الزنتان', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الزويتينة', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'العزيزية', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'العقيلة', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القبة', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المجر', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المرج', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'براك', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بنغازي', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بني وليد', 27, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تجرهي', 28, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ترهونة', 29, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تمسة', 30, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'توكرة', 31, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جنزور', 32, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'زلطن', 33, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'زوارة', 34, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'شحات', 35, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'شعبية تاجوراء والنواحي الأربع', 36, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'شعبية سرت', 37, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'صبراتة', 38, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طبرق', 39, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طرابلس', 40, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'غات', 41, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'غدامس', 42, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'غريان', 43, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قمينس', 44, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة البيضاء', 45, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مرزق', 46, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مزدة', 47, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مصراتة', 48, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'منطقة الجوف', 49, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ميناء درنة', 50, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'نالوت', 51, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ودان', 52, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'يفرن', 53, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='ليبيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'Suez', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أبنوب', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أبو تشت', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أبو تيج', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أخميم', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أسوان', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أسيوط', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'إبشواي', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'إدفو', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'إدكو', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الأقصر', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الإسكندرية', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الباجور', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الخانكة', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الزقازيق', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'العريش', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الفشن', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القاهرة', 27, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القرين', 28, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'القوصية', 29, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المحلة الكبرى', 30, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المراغة', 31, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المطرية', 32, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'المنصورة', 33, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بلبيس', 34, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بني مزار', 35, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بورسعيد', 36, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بورفؤاد', 37, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بيلا', 38, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تالا', 39, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جامعة المنيا', 40, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'جرجا', 41, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دسوق', 42, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دكرنس', 43, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دمنهور', 44, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دمياط', 45, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ديروط', 46, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'رفح', 47, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'روزيتَا', 48, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'زفتى', 49, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ساقلتة', 50, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سعيد الفيومي', 51, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سمالوط', 52, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سنورس', 53, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سوهاج', 54, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'شبرا الخيمة', 55, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'شبين القناطر', 56, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'شبين الكوم', 57, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'شرم الشيخ', 58, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طلخا', 59, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'طنطا', 60, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'فرشوط', 61, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قسم الشيخ زويد', 62, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قلين', 63, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قليوب', 64, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كرداسة', 65, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كفر الدوار', 66, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كفر الزيات', 67, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كفر الشيخ', 68, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ليتوبوليس', 69, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مثلث الشهداء', 70, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة بني سويف', 71, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'محافظة مطروح', 72, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مدينة السادات', 73, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مدينة السادس من أكتوبر', 74, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مركز أبو حمص', 75, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مركز البدرشين', 76, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مركز الوقف', 77, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مركز بركة السبع', 78, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مركز شبراخيت', 79, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مركز صدفا', 80, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مركز كفر صقر', 81, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مركز مغاغة', 82, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مركز ميت سلسيل', 83, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مستشفي العجمي التخصصي', 84, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ملوي', 85, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'منفلوط', 86, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'منيا القمح', 87, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ميت غمر', 88, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ههيا', 89, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='مصر' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أتاري إس تي', 10, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'أكجوجت', 11, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الزويرات', 12, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'الشقة', 13, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'بوتلميت', 14, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تجكجة', 15, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'تمبدغة', 16, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'دار النعيم', 17, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'ديدان أسطوانية', 18, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سمكة موزة', 19, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'سيليبابي', 20, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'قصر', 21, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كيفة', 22, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'كيهيدي', 23, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مطار عيون العتروس', 24, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'مقاطعة_كرو', 25, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'نواذيبو', 26, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);

INSERT INTO marketplace_locations (parent_id, location_type, name, display_order, status)
SELECT id, 'city', 'نواكشوط', 27, 'active' FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND name='موريتانيا' LIMIT 1
ON DUPLICATE KEY UPDATE display_order=VALUES(display_order), status=VALUES(status);
