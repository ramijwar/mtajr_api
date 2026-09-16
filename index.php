<?php
declare(strict_types=1);

use SouqLink\Audit;
use SouqLink\Auth;
use SouqLink\Database;
use SouqLink\Http;

$applicationRoot = is_dir(__DIR__ . '/src') ? __DIR__ : dirname(__DIR__);
require_once $applicationRoot . '/src/Http.php';
require_once $applicationRoot . '/src/Database.php';
require_once $applicationRoot . '/src/Auth.php';
require_once $applicationRoot . '/src/Audit.php';

set_exception_handler(static function (Throwable $exception): void {
    error_log((string)$exception);
    Http::error('server_error', 'حدث خطأ غير متوقع. حاول لاحقاً.', 500);
});

$allowedOrigins = array_filter(array_map('trim', explode(',', Database::environment('CORS_ORIGINS', ''))));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, $allowedOrigins, true)) header("Access-Control-Allow-Origin: {$origin}");
header('Vary: Origin');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$method = $_SERVER['REQUEST_METHOD'];
$path = '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) $path = substr($path, strlen($basePath)) ?: '/';
$path = preg_replace('#^/api/v1#', '', $path) ?: '/';

function user(PDO $db): array { return Auth::bearerUser($db); }
function optionalUser(PDO $db): ?array {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', (string)$header, $matches)) return null;
    try {
        $payload = Auth::decodeToken($matches[1]);
        $statement = $db->prepare("SELECT id, full_name, phone, email, role, status, created_at FROM users WHERE id=? AND status='active' LIMIT 1");
        $statement->execute([(int)($payload['sub'] ?? 0)]);
        return $statement->fetch() ?: null;
    } catch (Throwable) { return null; }
}
function role(array $user, array $roles): void { Auth::requireRole($user, $roles); }
function isDuplicateConstraint(PDOException $exception): bool {
    $code = (int)($exception->errorInfo[1] ?? 0);
    return in_array($code, [1062, 19, 2067], true) || $exception->getCode() === '23000' || str_contains($exception->getMessage(), 'UNIQUE');
}

function reconcileUserWallets(PDO $db, int $userId): void {
    foreach (['USD', 'SYP'] as $currency) {
        $db->prepare('INSERT OR IGNORE INTO wallets (user_id, currency, available_balance) VALUES (?, ?, 0)')->execute([$userId, $currency]);
    }
    $sales = $db->prepare("INSERT INTO wallet_transactions (wallet_id, transaction_type, direction, amount, balance_after, reference_type, reference_id, description, created_by)
        SELECT w.id, 'merchant_sale', 'credit', o.products_subtotal, o.products_subtotal, 'order', o.id, 'ترميم رصيد مبيعات معتمد', ?
        FROM orders o JOIN stores s ON s.id=o.store_id JOIN payment_receipts pr ON pr.order_id=o.id AND pr.status='verified'
        JOIN wallets w ON w.user_id=s.merchant_id AND w.currency=o.currency
        WHERE s.merchant_id=? AND pr.id=(SELECT MAX(pr2.id) FROM payment_receipts pr2 WHERE pr2.order_id=o.id)
          AND NOT EXISTS (SELECT 1 FROM wallet_transactions wt WHERE wt.wallet_id=w.id AND wt.reference_type='order' AND wt.reference_id=o.id AND wt.transaction_type='merchant_sale')");
    $sales->execute([$userId, $userId]);
    $preorders = $db->prepare("INSERT INTO wallet_transactions (wallet_id, transaction_type, direction, amount, balance_after, reference_type, reference_id, description, created_by)
        SELECT w.id, 'merchant_sale', 'credit', po.products_subtotal, po.products_subtotal, 'preorder_order', po.id, 'ترميم رصيد طلب مسبق معتمد', ?
        FROM preorder_orders po JOIN stores s ON s.id=po.store_id JOIN preorder_payment_receipts pr ON pr.preorder_id=po.id AND pr.status='verified'
        JOIN wallets w ON w.user_id=s.merchant_id AND w.currency=po.currency
        WHERE s.merchant_id=? AND pr.id=(SELECT MAX(pr2.id) FROM preorder_payment_receipts pr2 WHERE pr2.preorder_id=po.id)
          AND NOT EXISTS (SELECT 1 FROM wallet_transactions wt WHERE wt.wallet_id=w.id AND wt.reference_type='preorder_order' AND wt.reference_id=po.id AND wt.transaction_type='merchant_sale')");
    $preorders->execute([$userId, $userId]);
    $wallets = $db->prepare('SELECT id FROM wallets WHERE user_id=?'); $wallets->execute([$userId]);
    $balance = $db->prepare("SELECT COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE -amount END), 0) FROM wallet_transactions WHERE wallet_id=? AND transaction_type NOT IN ('withdrawal_paid')");
    $update = $db->prepare('UPDATE wallets SET available_balance=? WHERE id=?');
    foreach ($wallets->fetchAll() as $wallet) { $balance->execute([(int)$wallet['id']]); $update->execute([max(0.0, (float)$balance->fetchColumn()), (int)$wallet['id']]); }
}
function reconcilePlatformWallets(PDO $db): void {
    foreach (['USD', 'SYP'] as $currency) {
        $db->prepare('INSERT OR IGNORE INTO platform_wallets (currency, available_balance) VALUES (?, 0)')->execute([$currency]);
    }
    $sum = $db->prepare("SELECT COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE -amount END), 0) FROM platform_wallet_transactions WHERE wallet_id=?");
    $update = $db->prepare('UPDATE platform_wallets SET available_balance=? WHERE id=?');
    foreach ($db->query('SELECT id FROM platform_wallets')->fetchAll() as $wallet) { $sum->execute([(int)$wallet['id']]); $update->execute([max(0.0, (float)$sum->fetchColumn()), (int)$wallet['id']]); }
}
function ensureCustomerDeliveryAddressTable(PDO $db): void {
    static $ready = false; if ($ready) return;
    $db->exec("CREATE TABLE IF NOT EXISTS customer_delivery_addresses (user_id INTEGER PRIMARY KEY, label TEXT NOT NULL DEFAULT '', address_text TEXT NOT NULL, latitude NUMERIC NULL, longitude NUMERIC NULL, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_customer_delivery_address_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)"); $ready = true;
}
function ensureNotificationPreferencesTable(PDO $db): void {
    static $ready = false; if ($ready) return;
    $db->exec("CREATE TABLE IF NOT EXISTS notification_preferences (user_id INTEGER PRIMARY KEY, orders_enabled INTEGER NOT NULL DEFAULT 1, payments_enabled INTEGER NOT NULL DEFAULT 1, delivery_enabled INTEGER NOT NULL DEFAULT 1, verification_enabled INTEGER NOT NULL DEFAULT 1, wallet_enabled INTEGER NOT NULL DEFAULT 1, marketplace_enabled INTEGER NOT NULL DEFAULT 1, support_enabled INTEGER NOT NULL DEFAULT 1, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_notification_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)"); $ready = true;
}
function ensureCourierReviewsTable(PDO $db): void {
    static $ready = false; if ($ready) return;
    $db->exec("CREATE TABLE IF NOT EXISTS courier_reviews (id INTEGER PRIMARY KEY AUTOINCREMENT, courier_id INTEGER NOT NULL, customer_id INTEGER NOT NULL, order_id INTEGER NOT NULL UNIQUE, rating INTEGER NOT NULL, comment TEXT NULL, status TEXT NOT NULL DEFAULT 'published', created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, CHECK (rating BETWEEN 1 AND 5), FOREIGN KEY (courier_id) REFERENCES users(id), FOREIGN KEY (customer_id) REFERENCES users(id), FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE)"); $ready = true;
}
function ensureAdminNotificationReadsTable(PDO $db): void {
    static $ready = false; if ($ready) return;
    $db->exec("CREATE TABLE IF NOT EXISTS admin_notification_reads (admin_id INTEGER NOT NULL, section_key TEXT NOT NULL, read_at TEXT NOT NULL, PRIMARY KEY (admin_id, section_key), FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE)");
    $ready = true;
}
function ensureMarketplaceSocialTables(PDO $db): void {
    static $ready = false; if ($ready) return;
    $db->exec("CREATE TABLE IF NOT EXISTS marketplace_comments (id INTEGER PRIMARY KEY AUTOINCREMENT, listing_id INTEGER NOT NULL, user_id INTEGER NOT NULL, parent_id INTEGER NULL, body TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'visible', created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_marketplace_comments_listing FOREIGN KEY (listing_id) REFERENCES listings_marketplace(id) ON DELETE CASCADE, CONSTRAINT fk_marketplace_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT, CONSTRAINT fk_marketplace_comments_parent FOREIGN KEY (parent_id) REFERENCES marketplace_comments(id) ON DELETE SET NULL)");
    $db->exec("CREATE TABLE IF NOT EXISTS marketplace_follows (follower_id INTEGER NOT NULL, followed_id INTEGER NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (follower_id, followed_id), CONSTRAINT fk_marketplace_follows_follower FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE, CONSTRAINT fk_marketplace_follows_followed FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE)");
    $ready = true;
}
function schemaTableExists(PDO $db, string $table): bool {
    static $cache = [];
    if (array_key_exists($table, $cache)) return $cache[$table];
    $statement = $db->prepare("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name=?");
    $statement->execute([$table]);
    return $cache[$table] = (int)$statement->fetchColumn() > 0;
}
function ensureStoreVisitEventsTable(PDO $db): void {
    static $ready = false;
    if ($ready) return;
    $db->exec("CREATE TABLE IF NOT EXISTS store_visit_events (id INTEGER PRIMARY KEY AUTOINCREMENT, store_id INTEGER NOT NULL, viewer_id INTEGER NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_store_visit_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE, CONSTRAINT fk_store_visit_viewer FOREIGN KEY (viewer_id) REFERENCES users(id) ON DELETE SET NULL)");
    $ready = true;
}
function schemaColumnExists(PDO $db, string $table, string $column): bool {
    static $cache = [];
    $key = "$table.$column";
    if (array_key_exists($key, $cache)) return $cache[$key];
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $statement = $db->query("PRAGMA table_info({$safeTable})");
    $columns = $statement ? $statement->fetchAll() : [];
    foreach ($columns as $col) {
        if (strcasecmp((string)($col['name'] ?? ''), $column) === 0) {
            return $cache[$key] = true;
        }
    }
    return $cache[$key] = false;
}
function normalizeStoreHours(mixed $raw): ?string {
    if ($raw === null || $raw === '') return null;
    $data = is_string($raw) ? json_decode($raw, true) : $raw;
    if (!is_array($data)) Http::error('validation_error', 'بيانات أوقات الدوام غير صالحة.', 422);
    $mode = (string)($data['mode'] ?? 'full');
    if (!in_array($mode, ['full', 'partial'], true)) Http::error('validation_error', 'نمط الدوام غير صالح.', 422);
    $timePattern = '/^(?:[01]\\d|2[0-3]):[0-5]\\d$/';
    $times = ['full_start','full_end','morning_start','morning_end','evening_start','evening_end'];
    foreach ($times as $key) if (isset($data[$key]) && $data[$key] !== '' && !preg_match($timePattern, (string)$data[$key])) Http::error('validation_error', 'صيغة وقت الدوام يجب أن تكون HH:MM.', 422);
    $holidays = array_values(array_unique(array_filter(array_map('intval', (array)($data['holidays'] ?? [])), static fn(int $day): bool => $day >= 0 && $day <= 6)));
    $normalized = ['mode' => $mode, 'full_start' => (string)($data['full_start'] ?? '08:00'), 'full_end' => (string)($data['full_end'] ?? '20:00'), 'morning_start' => (string)($data['morning_start'] ?? '08:00'), 'morning_end' => (string)($data['morning_end'] ?? '13:00'), 'evening_start' => (string)($data['evening_start'] ?? '16:00'), 'evening_end' => (string)($data['evening_end'] ?? '20:00'), 'holidays' => $holidays];
    return json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}
function route(string $pattern, string $path, ?array &$parameters = null): bool {
    $regex = '#^' . preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
    if (!preg_match($regex, $path, $matches)) return false;
    $parameters = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    return true;
}
function orderStatusLabel(string $status): string {
    return match ($status) { 'payment_review' => 'بانتظار مراجعة الدفع', 'ready_for_delivery' => 'جاهز للتوصيل', 'out_for_delivery' => 'قيد التوصيل', 'delivered' => 'تم التسليم', default => $status };
}
function saveUpload(string $field, int $maxBytes, string $prefix): string {
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) Http::error('validation_error', 'ملف الصورة مطلوب.', 422, [$field => 'ملف مطلوب']);
    $file = $_FILES[$field];
    $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $message = match ($uploadError) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'حجم الصورة يتجاوز الحد المسموح.',
            UPLOAD_ERR_PARTIAL => 'لم يكتمل رفع الصورة. تحقق من الشبكة وحاول مجدداً.',
            UPLOAD_ERR_NO_TMP_DIR => 'خدمة رفع الصور غير جاهزة حالياً.',
            UPLOAD_ERR_CANT_WRITE => 'تعذر حفظ الصورة في مساحة الرفع.',
            UPLOAD_ERR_EXTENSION => 'رفض الخادم الصورة المرفوعة.',
            default => 'تعذر استلام الصورة. اخترها مجدداً وحاول.',
        };
        Http::error('upload_failed', $message, $uploadError === UPLOAD_ERR_NO_TMP_DIR || $uploadError === UPLOAD_ERR_CANT_WRITE ? 503 : 422, [$field => $message]);
    }
    $size = (int)($file['size'] ?? 0);
    if ($size < 1) Http::error('empty_file', 'ملف الصورة فارغ.', 422, [$field => 'الصورة فارغة']);
    if ($size > $maxBytes) Http::error('file_too_large', 'حجم الصورة يتجاوز الحد المسموح.', 422, [$field => 'حجم الصورة كبير']);
    $temporaryPath = (string)($file['tmp_name'] ?? '');
    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) Http::error('invalid_upload', 'لم يتحقق الخادم من ملف الصورة. اختر الصورة مجدداً.', 422, [$field => 'ملف غير صالح']);
    if (!class_exists('finfo')) Http::error('upload_inspection_unavailable', 'خدمة فحص الصور غير متاحة حالياً. حاول لاحقاً.', 503);
    try { $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath); }
    catch (Throwable) { Http::error('upload_inspection_failed', 'تعذر فحص نوع الصورة. اختر صورة أخرى.', 422, [$field => 'تعذر فحص الصورة']); }
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) Http::error('unsupported_file', 'يسمح فقط بصور JPEG وPNG وWEBP.', 422);
    $directory = rtrim(Database::environment('UPLOAD_PATH', __DIR__ . '/storage/uploads'), '/');
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) Http::error('upload_storage_unavailable', 'مساحة رفع الصور غير متاحة حالياً. حاول لاحقاً.', 503);
    if (!is_writable($directory)) Http::error('upload_storage_unavailable', 'مساحة رفع الصور غير قابلة للكتابة حالياً. حاول لاحقاً.', 503);
    $name = $prefix . '_' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($temporaryPath, $directory . '/' . $name)) Http::error('upload_storage_write_failed', 'تعذر حفظ الصورة في مساحة الرفع. حاول لاحقاً.', 503);
    return $name;
}

function notifyStoreFollowersForAdvertisement(PDO $db, int $advertisementId, int $storeId): void {
    $statement = $db->prepare("SELECT a.id, a.title, a.target_type, a.target_product_id, a.target_url, s.name AS store_name FROM store_advertisements a JOIN stores s ON s.id=a.store_id WHERE a.id=? AND a.store_id=? AND a.status='active' AND a.image_path IS NOT NULL AND a.image_path<>'' LIMIT 1");
    $statement->execute([$advertisementId, $storeId]);
    $advertisement = $statement->fetch();
    if (!$advertisement) return;
    $followers = $db->prepare("SELECT user_id FROM store_followers WHERE store_id=? AND status='active'");
    $followers->execute([$storeId]);
    foreach ($followers->fetchAll() as $follower) {
        $userId = (int)$follower['user_id'];
        marketplaceNotify($db, $userId, 'store_advertisement', 'إعلان جديد من المتجر', 'أضاف متجر ' . $advertisement['store_name'] . ' إعلاناً جديداً.', 'store_advertisement:' . $advertisementId . ':' . $userId, [], [
            'source' => 'store', 'context' => 'store_advertisement', 'store_id' => $storeId,
            'advertisement_id' => (int)$advertisement['id'], 'target_type' => $advertisement['target_type'],
            'target_product_id' => $advertisement['target_product_id'] === null ? null : (int)$advertisement['target_product_id'],
            'target_url' => $advertisement['target_url'],
        ]);
    }
}
function storeAdvertisementRows(PDO $db, int $storeId, bool $activeOnly = true): array {
    if (!schemaTableExists($db, 'store_advertisements')) return [];
    $where = $activeOnly
        ? "AND a.status='active' AND a.image_path IS NOT NULL AND a.image_path<>'' AND (a.starts_at IS NULL OR a.starts_at<=NOW()) AND (a.ends_at IS NULL OR a.ends_at>NOW())"
        : '';
    $statement = $db->prepare("SELECT a.id, a.store_id, a.title, a.body, a.image_path, a.target_type, a.target_product_id, a.target_url, a.display_order, a.starts_at, a.ends_at, a.status, a.created_at, a.updated_at, p.name AS target_product_name FROM store_advertisements a LEFT JOIN products p ON p.id=a.target_product_id AND p.store_id=a.store_id WHERE a.store_id=? {$where} ORDER BY a.display_order ASC, a.id DESC");
    $statement->execute([$storeId]);
    return $statement->fetchAll();
}
function merchantStoreAdvertisement(PDO $db, int $storeId, int $merchantId): array {
    $statement = $db->prepare('SELECT id, store_id, name FROM stores WHERE id=? AND merchant_id=? LIMIT 1');
    $statement->execute([$storeId, $merchantId]);
    $store = $statement->fetch();
    if (!$store) Http::error('forbidden', 'لا تملك هذا المتجر.', 403);
    return $store;
}
function validateStoreAdvertisementInput(PDO $db, int $storeId, array $input, ?array $existing = null): array {
    $title = trim((string)($input['title'] ?? ($existing['title'] ?? '')));
    if ($title === '') $title = 'إعلان المتجر';
    // بعض الاستضافات المشتركة لا تفعّل mbstring؛ غيابها كان يحوّل الطلب إلى 500 عام قبل INSERT.
    $titleLength = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
    if ($titleLength > 180) Http::error('validation_error', 'بيانات الإعلان غير صالحة.', 422);
    $body = trim((string)($input['body'] ?? ($existing['body'] ?? '')));
    $targetType = (string)($input['target_type'] ?? ($existing['target_type'] ?? 'none'));
    if (!in_array($targetType, ['none', 'product', 'url'], true)) Http::error('validation_error', 'نوع وجهة الإعلان غير صالح.', 422);
    $productId = $input['target_product_id'] ?? ($existing['target_product_id'] ?? null);
    $productId = $productId === null || $productId === '' ? null : (int)$productId;
    $targetUrl = trim((string)($input['target_url'] ?? ($existing['target_url'] ?? '')));
    if ($targetType === 'product') {
        if (!$productId) Http::error('validation_error', 'اختر منتجاً من هذا المتجر.', 422);
        $product = $db->prepare("SELECT id FROM products WHERE id=? AND store_id=? AND status <> 'hidden' LIMIT 1");
        $product->execute([$productId, $storeId]);
        if (!$product->fetch()) Http::error('validation_error', 'المنتج المرتبط غير موجود في هذا المتجر.', 422);
        $targetUrl = '';
    } elseif ($targetType === 'url') {
        $parsed = filter_var($targetUrl, FILTER_VALIDATE_URL);
        if (!$parsed || !in_array(strtolower((string)parse_url($targetUrl, PHP_URL_SCHEME)), ['http', 'https'], true)) Http::error('validation_error', 'أدخل رابطاً خارجياً صحيحاً يبدأ بـ http أو https.', 422);
        $productId = null;
    } else {
        $productId = null; $targetUrl = '';
    }
    $status = (string)($input['status'] ?? ($existing['status'] ?? 'draft'));
    if (!in_array($status, ['draft', 'active', 'paused'], true)) Http::error('validation_error', 'حالة الإعلان غير صالحة.', 422);
    $order = max(0, (int)($input['display_order'] ?? ($existing['display_order'] ?? 0)));
    $starts = array_key_exists('starts_at', $input) ? ($input['starts_at'] ?: null) : ($existing['starts_at'] ?? null);
    $ends = array_key_exists('ends_at', $input) ? ($input['ends_at'] ?: null) : ($existing['ends_at'] ?? null);
    if ($starts !== null && strtotime((string)$starts) === false) Http::error('validation_error', 'تاريخ بدء الإعلان غير صالح.', 422);
    if ($ends !== null && strtotime((string)$ends) === false) Http::error('validation_error', 'تاريخ انتهاء الإعلان غير صالح.', 422);
    if ($starts !== null && $ends !== null && strtotime((string)$ends) <= strtotime((string)$starts)) Http::error('validation_error', 'يجب أن يكون انتهاء الإعلان بعد بدايته.', 422);
    return [$title, $body !== '' ? $body : null, $targetType, $productId, $targetUrl !== '' ? $targetUrl : null, $order, $starts, $ends, $status];
}
function marketplaceCategory(PDO $db, int $categoryId, bool $activeOnly = false): array {
    $statement = $db->prepare('SELECT id, parent_id, name, slug, icon_key, image_path, display_order, status FROM categories_marketplace WHERE id=?' . ($activeOnly ? " AND status='active'" : '') . ' LIMIT 1');
    $statement->execute([$categoryId]);
    $category = $statement->fetch();
    if (!$category) Http::error('not_found', 'قسم الحراج غير موجود.', 404);
    return $category;
}

function marketplaceCategoryChain(PDO $db, int $categoryId, bool $activeOnly = true): array {
    $chain = [];
    $seen = [];
    $currentId = $categoryId;
    for ($depth = 0; $currentId > 0 && $depth < 12; $depth++) {
        if (isset($seen[$currentId])) Http::error('invalid_category_tree', 'تسلسل أقسام الحراج غير صالح.', 422);
        $seen[$currentId] = true;
        $category = marketplaceCategory($db, $currentId, $activeOnly);
        array_unshift($chain, $category);
        $currentId = (int)($category['parent_id'] ?? 0);
    }
    return $chain;
}

function marketplaceAttributeOptions(PDO $db, array $attributeIds, bool $activeOnly = false): array {
    if ($attributeIds === []) return [];
    $placeholders = implode(',', array_fill(0, count($attributeIds), '?'));
    $statement = $db->prepare('SELECT id, attribute_id, option_key, label, display_order, status FROM category_attribute_options_marketplace WHERE attribute_id IN (' . $placeholders . ')' . ($activeOnly ? " AND status='active'" : '') . ' ORDER BY display_order, id');
    $statement->execute($attributeIds);
    $grouped = [];
    foreach ($statement->fetchAll() as $row) $grouped[(int)$row['attribute_id']][] = $row;
    return $grouped;
}

function marketplaceAttributesForCategory(PDO $db, int $categoryId, bool $activeOnly = true): array {
    $chain = marketplaceCategoryChain($db, $categoryId, $activeOnly);
    $categoryIds = array_map(static fn(array $category): int => (int)$category['id'], $chain);
    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
    $statement = $db->prepare('SELECT id, category_id, attribute_key, label, field_type, placeholder, help_text, is_required, applies_to_descendants, display_order, status FROM category_attributes_marketplace WHERE category_id IN (' . $placeholders . ')' . ($activeOnly ? " AND status='active'" : '') . ' ORDER BY display_order, id');
    $statement->execute($categoryIds);
    $leafId = $categoryIds[count($categoryIds) - 1];
    $attributes = [];
    foreach ($statement->fetchAll() as $attribute) {
        if ((int)$attribute['category_id'] !== $leafId && !(bool)$attribute['applies_to_descendants']) continue;
        $attributes[] = $attribute;
    }
    $options = marketplaceAttributeOptions($db, array_map(static fn(array $attribute): int => (int)$attribute['id'], $attributes), $activeOnly);
    foreach ($attributes as &$attribute) $attribute['options'] = $options[(int)$attribute['id']] ?? [];
    unset($attribute);
    return $attributes;
}

function marketplaceLocation(PDO $db, int $locationId, bool $activeOnly = false): array {
    $statement = $db->prepare('SELECT id, parent_id, location_type, name, code, display_order, status FROM marketplace_locations WHERE id=?' . ($activeOnly ? " AND status='active'" : '') . ' LIMIT 1');
    $statement->execute([$locationId]);
    $location = $statement->fetch();
    if (!$location) Http::error('not_found', 'موقع الحراج غير موجود.', 404);
    return $location;
}

function marketplaceLocationParentIsValid(PDO $db, string $locationType, ?int $parentId): bool {
    $expectedParent = ['country' => null, 'city' => 'country', 'area' => 'city'];
    if (!array_key_exists($locationType, $expectedParent)) return false;
    if ($expectedParent[$locationType] === null) return $parentId === null;
    if ($parentId === null) return false;
    return (marketplaceLocation($db, $parentId)['location_type'] ?? null) === $expectedParent[$locationType];
}

function marketplaceDescendantCategoryIds(PDO $db, int $rootCategoryId): array {
    marketplaceCategory($db, $rootCategoryId, true);
    $all = [$rootCategoryId]; $frontier = [$rootCategoryId];
    for ($depth = 0; $frontier !== [] && $depth < 12; $depth++) {
        $placeholders = implode(',', array_fill(0, count($frontier), '?'));
        $statement = $db->prepare("SELECT id FROM categories_marketplace WHERE parent_id IN ({$placeholders}) AND status='active'");
        $statement->execute($frontier); $frontier = array_map('intval', array_column($statement->fetchAll(), 'id'));
        $all = [...$all, ...$frontier];
    }
    return array_values(array_unique($all));
}

function marketplaceListingSelect(): string {
    return "SELECT l.id, l.seller_user_id, l.store_id, l.category_id, l.title, l.description, l.listing_kind, l.item_condition, l.price, l.currency, l.is_negotiable, l.link_type, l.linked_product_id, l.external_url, CASE WHEN EXISTS (SELECT 1 FROM marketplace_promotions promotion_featured WHERE promotion_featured.listing_id=l.id AND promotion_featured.promotion_type='featured' AND promotion_featured.status='active' AND promotion_featured.end_at>NOW()) OR (l.is_featured=1 AND (l.featured_until IS NULL OR l.featured_until>NOW())) THEN 1 ELSE 0 END AS is_featured, CASE WHEN EXISTS (SELECT 1 FROM marketplace_promotions promotion_highlight WHERE promotion_highlight.listing_id=l.id AND promotion_highlight.promotion_type='highlight' AND promotion_highlight.status='active' AND promotion_highlight.end_at>NOW()) THEN 1 ELSE 0 END AS is_highlighted, l.featured_until, l.fulfillment_mode, l.location_text, l.latitude, l.longitude, l.service_available_from, l.service_available_to, l.contact_phone, l.status, l.view_count, l.published_at, l.created_at, c.name AS category_name, seller.full_name AS seller_name, s.name AS store_name, country.name AS country_name, city.name AS city_name, area.name AS area_name, CASE WHEN EXISTS (SELECT 1 FROM verification_requests vr WHERE vr.user_id=l.seller_user_id AND vr.status='approved' AND vr.is_active=1) THEN 1 ELSE 0 END AS seller_is_verified, (SELECT file_path FROM listing_images_marketplace li WHERE li.listing_id=l.id ORDER BY li.sort_order, li.id LIMIT 1) AS image_path, CONCAT('[', COALESCE((SELECT GROUP_CONCAT(JSON_QUOTE(li2.file_path) , ',') FROM listing_images_marketplace li2 WHERE li2.listing_id=l.id), ''), ']') AS image_paths FROM listings_marketplace l JOIN categories_marketplace c ON c.id=l.category_id JOIN users seller ON seller.id=l.seller_user_id LEFT JOIN stores s ON s.id=l.store_id LEFT JOIN marketplace_locations country ON country.id=l.country_location_id LEFT JOIN marketplace_locations city ON city.id=l.city_location_id LEFT JOIN marketplace_locations area ON area.id=l.area_location_id";
}

function marketplaceExpirePromotions(PDO $db): void {
    $db->prepare("UPDATE marketplace_promotions SET status='expired', updated_at=NOW() WHERE status='active' AND end_at IS NOT NULL AND end_at<=NOW()")->execute();
}

function marketplaceAlertFilters(array $filters): array {
    $allowed = ['q','category_id','country_id','city_id','area_id','min_price','max_price','condition','negotiable','delivery_available','verified_seller','seller_type'];
    $normalized = [];
    foreach ($allowed as $key) if (array_key_exists($key, $filters) && $filters[$key] !== null && $filters[$key] !== '') $normalized[$key] = $filters[$key];
    if (isset($normalized['q']) && mb_strlen(trim((string)$normalized['q'])) > 160) Http::error('validation_error', 'عبارة التنبيه طويلة جداً.', 422);
    foreach (['category_id','country_id','city_id','area_id'] as $key) if (isset($normalized[$key]) && (!is_numeric($normalized[$key]) || (int)$normalized[$key] < 1)) Http::error('validation_error', 'معرف فلتر التنبيه غير صالح.', 422);
    foreach (['min_price','max_price'] as $key) if (isset($normalized[$key]) && (!is_numeric($normalized[$key]) || (float)$normalized[$key] < 0)) Http::error('validation_error', 'سعر فلتر التنبيه غير صالح.', 422);
    if (isset($normalized['min_price'], $normalized['max_price']) && (float)$normalized['min_price'] > (float)$normalized['max_price']) Http::error('validation_error', 'السعر الأدنى يجب ألا يتجاوز السعر الأعلى.', 422);
    if (isset($normalized['condition']) && !in_array($normalized['condition'], ['new','used'], true)) Http::error('validation_error', 'حالة المنتج غير صالحة.', 422);
    if (isset($normalized['seller_type']) && !in_array($normalized['seller_type'], ['store','individual'], true)) Http::error('validation_error', 'نوع البائع غير صالح.', 422);
    return $normalized;
}

function marketplaceAlertWhere(PDO $db, array $filters, array &$parameters): array {
    $where = [];
    if (isset($filters['q'])) { $like = '%' . trim((string)$filters['q']) . '%'; $where[] = '(l.title LIKE ? OR l.description LIKE ? OR c.name LIKE ?)'; array_push($parameters, $like, $like, $like); }
    if (isset($filters['category_id'])) { $ids = marketplaceDescendantCategoryIds($db, (int)$filters['category_id']); $where[] = 'l.category_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')'; array_push($parameters, ...$ids); }
    foreach (['country_id' => 'country_location_id', 'city_id' => 'city_location_id', 'area_id' => 'area_location_id'] as $key => $column) if (isset($filters[$key])) { $where[] = "l.{$column}=?"; $parameters[] = (int)$filters[$key]; }
    if (isset($filters['min_price'])) { $where[] = 'l.price>=?'; $parameters[] = (float)$filters['min_price']; }
    if (isset($filters['max_price'])) { $where[] = 'l.price<=?'; $parameters[] = (float)$filters['max_price']; }
    if (isset($filters['condition'])) { $where[] = 'l.item_condition=?'; $parameters[] = $filters['condition']; }
    if (($filters['negotiable'] ?? false) === true || ($filters['negotiable'] ?? '') === '1') $where[] = 'l.is_negotiable=1';
    if (($filters['delivery_available'] ?? false) === true || ($filters['delivery_available'] ?? '') === '1') $where[] = "l.fulfillment_mode IN ('delivery','both')";
    if (($filters['verified_seller'] ?? false) === true || ($filters['verified_seller'] ?? '') === '1') $where[] = "EXISTS (SELECT 1 FROM verification_requests vr WHERE vr.user_id=l.seller_user_id AND vr.status='approved' AND vr.is_active=1)";
    if (($filters['seller_type'] ?? '') === 'store') $where[] = 'l.store_id IS NOT NULL';
    if (($filters['seller_type'] ?? '') === 'individual') $where[] = 'l.store_id IS NULL';
    return $where;
}

function marketplaceCreateAlertEvent(PDO $db, int $userId, ?int $listingId, ?int $ruleId, string $type, string $eventKey, array $payload): void {
    $statement = $db->prepare('INSERT OR IGNORE INTO marketplace_alert_events (user_id, listing_id, rule_id, event_type, event_key, payload_json) VALUES (?, ?, ?, ?, ?, ?)');
    $statement->execute([$userId, $listingId, $ruleId, $type, $eventKey, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
}

function marketplaceSyncAlertEvents(PDO $db, int $userId): void {
    $rules = $db->prepare("SELECT id, rule_type, filters_json, last_checked_at FROM marketplace_alert_rules WHERE user_id=? AND status='active'");
    $rules->execute([$userId]);
    foreach ($rules->fetchAll() as $rule) {
        if ($rule['rule_type'] !== 'new_listing') continue;
        $filters = json_decode((string)($rule['filters_json'] ?? '{}'), true) ?: [];
        $parameters = [$rule['last_checked_at']]; $where = ["l.status='published'", 'l.published_at>?'];
        $where = [...$where, ...marketplaceAlertWhere($db, $filters, $parameters)];
        $statement = $db->prepare(marketplaceListingSelect() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY l.published_at ASC, l.id ASC LIMIT 50');
        $statement->execute($parameters);
        foreach ($statement->fetchAll() as $listing) marketplaceCreateAlertEvent($db, $userId, (int)$listing['id'], (int)$rule['id'], 'new_listing', 'new_listing:' . $rule['id'] . ':' . $listing['id'], ['listing' => $listing]);
        $db->prepare('UPDATE marketplace_alert_rules SET last_checked_at=NOW() WHERE id=?')->execute([$rule['id']]);
    }
    $favorites = $db->prepare("SELECT f.id, f.listing_id, f.last_known_price, l.price, l.currency, l.title, (SELECT file_path FROM listing_images_marketplace li WHERE li.listing_id=l.id ORDER BY li.sort_order, li.id LIMIT 1) AS image_path FROM favorites_marketplace f JOIN listings_marketplace l ON l.id=f.listing_id WHERE f.user_id=? AND l.price IS NOT NULL");
    $favorites->execute([$userId]);
    foreach ($favorites->fetchAll() as $favorite) {
        $previous = $favorite['last_known_price'] === null ? null : (float)$favorite['last_known_price']; $current = (float)$favorite['price'];
        if ($previous !== null && $current < $previous) marketplaceCreateAlertEvent($db, $userId, (int)$favorite['listing_id'], null, 'price_drop', 'price_drop:' . $favorite['id'] . ':' . $favorite['price'], ['listing_id' => (int)$favorite['listing_id'], 'title' => $favorite['title'], 'old_price' => $previous, 'new_price' => $current, 'currency' => $favorite['currency'], 'image_path' => $favorite['image_path']]);
        $db->prepare('UPDATE favorites_marketplace SET last_known_price=? WHERE id=?')->execute([$current, $favorite['id']]);
    }
}

function marketplaceChatConversation(PDO $db, int $conversationId, int $actorId): array {
    $statement = $db->prepare('SELECT * FROM marketplace_conversations WHERE id=? AND (buyer_id=? OR seller_id=?) LIMIT 1');
    $statement->execute([$conversationId, $actorId, $actorId]);
    $conversation = $statement->fetch();
    if (!$conversation) Http::error('not_found', 'محادثة الحراج غير موجودة أو لا تملك صلاحية الوصول إليها.', 404);
    return $conversation;
}

function marketplaceChatOtherUserId(array $conversation, int $actorId): int {
    return (int)$conversation['buyer_id'] === $actorId ? (int)$conversation['seller_id'] : (int)$conversation['buyer_id'];
}

function marketplaceChatBlocked(PDO $db, int $firstUserId, int $secondUserId): bool {
    $statement = $db->prepare('SELECT 1 FROM marketplace_user_blocks WHERE (blocker_id=? AND blocked_id=?) OR (blocker_id=? AND blocked_id=?) LIMIT 1');
    $statement->execute([$firstUserId, $secondUserId, $secondUserId, $firstUserId]);
    return (bool)$statement->fetchColumn();
}

function marketplaceChatAssertOpen(PDO $db, array $conversation, int $actorId): int {
    $otherUserId = marketplaceChatOtherUserId($conversation, $actorId);
    if (marketplaceChatBlocked($db, $actorId, $otherUserId)) Http::error('chat_blocked', 'لا يمكن المراسلة لأن أحد الطرفين حظر الآخر.', 403);
    return $otherUserId;
}

function marketplaceChatPublicConversation(PDO $db, array $conversation, int $actorId): array {
    $otherUserId = marketplaceChatOtherUserId($conversation, $actorId);
    $user = $db->prepare('SELECT full_name, avatar_path FROM users WHERE id=? LIMIT 1');
    $user->execute([$otherUserId]);
    $other = $user->fetch() ?: ['full_name' => 'مستخدم', 'avatar_path' => null];
    $listing = $db->prepare("SELECT title, price, currency, status, (SELECT file_path FROM listing_images_marketplace li WHERE li.listing_id=l.id ORDER BY li.sort_order, li.id LIMIT 1) AS image_path FROM listings_marketplace l WHERE l.id=? LIMIT 1");
    $listing->execute([(int)$conversation['listing_id']]);
    $listing = $listing->fetch() ?: [];
    $unread = $db->prepare('SELECT COUNT(*) FROM marketplace_messages WHERE conversation_id=? AND sender_id<>? AND read_at IS NULL');
    $unread->execute([(int)$conversation['id'], $actorId]);
    $lastMessage = $db->prepare('SELECT body, message_type, created_at FROM marketplace_messages WHERE conversation_id=? ORDER BY created_at DESC, id DESC LIMIT 1');
    $lastMessage->execute([(int)$conversation['id']]);
    $last = $lastMessage->fetch() ?: [];
    return [
        'id' => (int)$conversation['id'], 'listing_id' => (int)$conversation['listing_id'],
        'buyer_id' => (int)$conversation['buyer_id'], 'seller_id' => (int)$conversation['seller_id'],
        'other_user_id' => $otherUserId, 'other_user_name' => $other['full_name'], 'other_user_avatar_path' => $other['avatar_path'],
        'listing_title' => $listing['title'] ?? $conversation['listing_title_snapshot'],
        'listing_price' => $listing['price'] ?? $conversation['listing_price_snapshot'],
        'listing_currency' => $listing['currency'] ?? $conversation['listing_currency_snapshot'],
        'listing_image_path' => $listing['image_path'] ?? $conversation['listing_image_path_snapshot'],
        'listing_status' => $listing['status'] ?? 'unavailable', 'unread_count' => (int)$unread->fetchColumn(),
        'last_message_at' => $last['created_at'] ?? $conversation['last_message_at'],
        'last_message_body' => $last['body'] ?? null,
        'last_message_type' => $last['message_type'] ?? null,
        'created_at' => $conversation['created_at'],
        'is_blocked' => marketplaceChatBlocked($db, $actorId, $otherUserId),
    ];
}

function marketplaceExpireOffers(PDO $db, ?int $conversationId = null): void {
    $expiredSql = "SELECT id, listing_id, buyer_id, seller_id FROM marketplace_offers WHERE status='pending' AND expires_at<=NOW()";
    $expiredParams = [];
    if ($conversationId !== null) { $expiredSql .= ' AND conversation_id=?'; $expiredParams[] = $conversationId; }
    $expired = $db->prepare($expiredSql); $expired->execute($expiredParams); $expiredRows = $expired->fetchAll();
    $sql = "UPDATE marketplace_offers SET status='expired', responded_at=COALESCE(responded_at, NOW()) WHERE status='pending' AND expires_at<=NOW()";
    $parameters = [];
    if ($conversationId !== null) { $sql .= ' AND conversation_id=?'; $parameters[] = $conversationId; }
    $db->prepare($sql)->execute($parameters);
    foreach ($expiredRows as $offer) foreach ([(int)$offer['buyer_id'], (int)$offer['seller_id']] as $recipientId) marketplaceNotify($db, $recipientId, 'offer_expired', 'انتهت مدة العرض', 'انتهت مدة أحد عروض الحراج دون قبول أو رفض.', 'offer_expired:' . $offer['id'] . ':' . $recipientId, ['listing_id' => (int)$offer['listing_id'], 'offer_id' => (int)$offer['id']]);
}

function marketplaceNotify(PDO $db, int $userId, string $type, string $title, string $body, string $eventKey, array $links = [], array $payload = []): void {
    if ($userId < 1) return;
    ensureNotificationPreferencesTable($db);
    $preference = match (true) {
        str_starts_with($type, 'verification') => 'verification_enabled',
        str_starts_with($type, 'delivery') => 'delivery_enabled',
        str_starts_with($type, 'wallet') || str_starts_with($type, 'withdrawal') => 'wallet_enabled',
        str_starts_with($type, 'payment') => 'payments_enabled',
        str_starts_with($type, 'support') => 'support_enabled',
        str_starts_with($type, 'listing') || str_starts_with($type, 'offer') || str_starts_with($type, 'chat') || str_starts_with($type, 'store_') => 'marketplace_enabled',
        default => 'orders_enabled',
    };
    $preferenceCheck = $db->prepare("SELECT {$preference} FROM notification_preferences WHERE user_id=? LIMIT 1"); $preferenceCheck->execute([$userId]); $preferenceValue = $preferenceCheck->fetchColumn();
    if ($preferenceValue === '0' || $preferenceValue === 0) return;
    $statement = $db->prepare('INSERT OR IGNORE INTO marketplace_notifications (user_id, notification_type, title, body, event_key, listing_id, conversation_id, offer_id, transaction_id, delivery_task_id, review_id, payload_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $statement->execute([
        $userId, $type, $title, $body, mb_substr($eventKey, 0, 255),
        $links['listing_id'] ?? null, $links['conversation_id'] ?? null, $links['offer_id'] ?? null,
        $links['transaction_id'] ?? null, $links['delivery_task_id'] ?? null, $links['review_id'] ?? null,
        $payload === [] ? null : json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
}

function marketplaceOffer(PDO $db, int $offerId, int $actorId, bool $forUpdate = false): array {
    $statement = $db->prepare('SELECT * FROM marketplace_offers WHERE id=? AND (buyer_id=? OR seller_id=?) LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : ''));
    $statement->execute([$offerId, $actorId, $actorId]); $offer = $statement->fetch();
    if (!$offer) Http::error('not_found', 'عرض الحراج غير موجود أو لا تملك صلاحية الوصول إليه.', 404);
    return $offer;
}

function marketplaceOfferPublic(array $offer): array {
    return [
        'id' => (int)$offer['id'], 'conversation_id' => (int)$offer['conversation_id'],
        'listing_id' => (int)$offer['listing_id'], 'buyer_id' => (int)$offer['buyer_id'],
        'seller_id' => (int)$offer['seller_id'], 'created_by_id' => (int)$offer['created_by_id'],
        'parent_offer_id' => $offer['parent_offer_id'] === null ? null : (int)$offer['parent_offer_id'],
        'amount' => (float)$offer['amount'], 'currency' => $offer['currency'],
        'status' => $offer['status'], 'expires_at' => $offer['expires_at'],
        'responded_at' => $offer['responded_at'], 'responded_by_id' => $offer['responded_by_id'] === null ? null : (int)$offer['responded_by_id'],
        'message_id' => $offer['message_id'] === null ? null : (int)$offer['message_id'],
        'transaction_id' => $offer['transaction_id'] === null ? null : (int)$offer['transaction_id'],
        'transaction_number' => $offer['transaction_number'] ?? null,
        'transaction_status' => $offer['transaction_status'] ?? null,
        'created_at' => $offer['created_at'],
    ];
}

function marketplaceOfferRows(PDO $db, int $conversationId): array {
    marketplaceExpireOffers($db, $conversationId);
    $statement = $db->prepare('SELECT o.*, t.transaction_number, t.status AS transaction_status FROM marketplace_offers o LEFT JOIN marketplace_transactions t ON t.id=o.transaction_id WHERE o.conversation_id=? ORDER BY o.created_at ASC, o.id ASC');
    $statement->execute([$conversationId]);
    return array_map(static fn(array $offer): array => marketplaceOfferPublic($offer), $statement->fetchAll());
}

function marketplaceOfferExpiry(array $input): string {
    $value = trim((string)($input['expires_at'] ?? ''));
    try { $expiry = $value === '' ? new DateTimeImmutable('+24 hours') : new DateTimeImmutable($value); }
    catch (Throwable $exception) { $expiry = false; }
    if (!$expiry) Http::error('validation_error', 'تاريخ انتهاء العرض غير صالح.', 422);
    $now = new DateTimeImmutable();
    if ($expiry <= $now->modify('+5 minutes') || $expiry > $now->modify('+30 days')) Http::error('validation_error', 'يجب أن ينتهي العرض بعد 5 دقائق وقبل 30 يوماً.', 422);
    return $expiry->format('Y-m-d H:i:s');
}

function marketplaceOwnedListing(PDO $db, int $listingId, int $actorId, bool $forUpdate = false): array {
    $statement = $db->prepare('SELECT * FROM listings_marketplace WHERE id=? AND seller_user_id=? LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : ''));
    $statement->execute([$listingId, $actorId]); $listing = $statement->fetch();
    if (!$listing) Http::error('not_found', 'الإعلان غير موجود أو لا تملك صلاحية إدارته.', 404);
    return $listing;
}

function marketplaceMyListingRow(PDO $db, int $listingId, int $actorId): array {
    $statement = $db->prepare("SELECT l.id, l.title, l.description, l.price, l.currency, l.status, l.view_count, CASE WHEN EXISTS (SELECT 1 FROM marketplace_promotions promotion_featured WHERE promotion_featured.listing_id=l.id AND promotion_featured.promotion_type='featured' AND promotion_featured.status='active' AND promotion_featured.end_at>NOW()) THEN 1 ELSE l.is_featured END AS is_featured, l.featured_until, l.link_type, l.linked_product_id, l.external_url, l.published_at, l.created_at, (SELECT file_path FROM listing_images_marketplace image WHERE image.listing_id=l.id ORDER BY image.sort_order, image.id LIMIT 1) AS image_path, (SELECT COUNT(*) FROM favorites_marketplace favorite WHERE favorite.listing_id=l.id) AS favorite_count, (SELECT COUNT(*) FROM marketplace_conversations conversation WHERE conversation.listing_id=l.id) AS message_count, (SELECT COUNT(*) FROM marketplace_offers offer_row WHERE offer_row.listing_id=l.id) AS offer_count, (SELECT COUNT(*) FROM marketplace_transactions transaction_row WHERE transaction_row.listing_id=l.id AND transaction_row.status IN ('paid','completed')) AS sales_count, (SELECT COALESCE(SUM(transaction_row.amount), 0) FROM marketplace_transactions transaction_row WHERE transaction_row.listing_id=l.id AND transaction_row.status IN ('paid','completed')) AS sales_amount, (SELECT CONCAT(promotion.promotion_type, ':', promotion.status) FROM marketplace_promotions promotion WHERE promotion.listing_id=l.id ORDER BY promotion.created_at DESC, promotion.id DESC LIMIT 1) AS promotion_status FROM listings_marketplace l WHERE l.id=? AND l.seller_user_id=? LIMIT 1");
    $statement->execute([$listingId, $actorId]); $row = $statement->fetch();
    if (!$row) Http::error('not_found', 'الإعلان غير موجود أو لا تملك صلاحية إدارته.', 404);
    return $row;
}

function marketplaceTransactionRow(PDO $db, int $transactionId, int $actorId, bool $forUpdate = false): array {
    $sql = "SELECT t.*, COALESCE(t.agreed_price, t.amount) AS agreed_price, l.title AS listing_title, (SELECT file_path FROM listing_images_marketplace image WHERE image.listing_id=t.listing_id ORDER BY image.sort_order, image.id LIMIT 1) AS image_path, CASE WHEN t.buyer_id=? THEN 'buyer' ELSE 'seller' END AS participant_role, other.full_name AS other_user_name FROM marketplace_transactions t JOIN listings_marketplace l ON l.id=t.listing_id JOIN users other ON other.id=CASE WHEN t.buyer_id=? THEN t.seller_id ELSE t.buyer_id END WHERE t.id=? AND (t.buyer_id=? OR t.seller_id=?) LIMIT 1" . ($forUpdate ? ' FOR UPDATE' : '');
    $statement = $db->prepare($sql); $statement->execute([$actorId, $actorId, $transactionId, $actorId, $actorId]); $row = $statement->fetch();
    if (!$row) Http::error('not_found', 'المعاملة غير موجودة أو لا تملك صلاحية الوصول إليها.', 404);
    return $row;
}

function marketplaceTransactionAction(PDO $db, array $transaction, int $actorId, string $action): array {
    $role = (int)$transaction['buyer_id'] === $actorId ? 'buyer' : 'seller'; $status = (string)$transaction['status'];
    $nextStatus = null; $paymentStatus = null; $deliveryStatus = null; $completed = false;
    if ($action === 'confirm' && $role === 'seller' && $status === 'pending') $nextStatus = 'confirmed';
    if ($action === 'request_payment' && $role === 'seller' && $status === 'confirmed') { $nextStatus = 'waiting_payment'; $paymentStatus = 'waiting_payment'; }
    if ($action === 'mark_paid' && $role === 'buyer' && $status === 'waiting_payment') { $nextStatus = 'paid'; $paymentStatus = 'paid'; }
    if ($action === 'waiting_delivery' && $role === 'seller' && $status === 'paid') { $nextStatus = 'waiting_delivery'; $deliveryStatus = 'waiting_delivery'; }
    if ($action === 'delivering' && $role === 'seller' && $status === 'waiting_delivery') { $nextStatus = 'delivering'; $deliveryStatus = 'delivering'; }
    if ($action === 'complete' && $role === 'buyer' && in_array($status, ['paid','delivering'], true)) { $nextStatus = 'completed'; $deliveryStatus = $status === 'delivering' ? 'delivered' : null; $completed = true; }
    if ($action === 'cancel' && in_array($role, ['buyer','seller'], true) && in_array($status, ['pending','confirmed','waiting_payment'], true)) { $nextStatus = 'cancelled'; $paymentStatus = 'cancelled'; $deliveryStatus = 'cancelled'; }
    if ($action === 'dispute' && in_array($role, ['buyer','seller'], true) && !in_array($status, ['completed','cancelled','disputed'], true)) $nextStatus = 'disputed';
    if ($nextStatus === null) Http::error('transaction_transition_invalid', 'هذا الإجراء غير متاح لحالة المعاملة أو دورك الحالي.', 409);
    $update = $db->prepare('UPDATE marketplace_transactions SET status=?, payment_status=COALESCE(?, payment_status), delivery_status=COALESCE(?, delivery_status), completed_at=CASE WHEN ? THEN NOW() ELSE completed_at END WHERE id=? AND status=?');
    $update->execute([$nextStatus, $paymentStatus, $deliveryStatus, $completed ? 1 : 0, $transaction['id'], $status]);
    if ($update->rowCount() !== 1) Http::error('transaction_conflict', 'تغيرت حالة المعاملة. حدّث الصفحة ثم أعد المحاولة.', 409);
    return ['action' => $action, 'status' => $nextStatus, 'payment_status' => $paymentStatus, 'delivery_status' => $deliveryStatus];
}

function marketplaceTransactionReviewState(PDO $db, int $transactionId, int $actorId): array {
    $transaction = marketplaceTransactionRow($db, $transactionId, $actorId);
    $reviewerRole = (int)$transaction['buyer_id'] === $actorId ? 'buyer' : 'seller';
    $revieweeId = $reviewerRole === 'buyer' ? (int)$transaction['seller_id'] : (int)$transaction['buyer_id'];
    $reviewed = $db->prepare('SELECT id FROM marketplace_transaction_reviews WHERE transaction_id=? AND reviewer_id=? LIMIT 1'); $reviewed->execute([$transactionId, $actorId]);
    return ['transaction' => $transaction, 'reviewer_role' => $reviewerRole, 'reviewee_id' => $revieweeId, 'can_review' => $transaction['status'] === 'completed' && !$reviewed->fetch()];
}

function marketplaceReviewSummary(PDO $db, int $userId): array {
    $statement = $db->prepare('SELECT COALESCE(ROUND(AVG(rating), 2), 0) AS average_rating, COUNT(*) AS total_reviews FROM marketplace_transaction_reviews WHERE reviewee_id=?'); $statement->execute([$userId]); return $statement->fetch() ?: ['average_rating' => 0, 'total_reviews' => 0];
}

function marketplaceDeliveryTaskRow(PDO $db, int $transactionId, int $actorId, bool $forUpdate = false): array {
    $sql = "SELECT dt.*, t.transaction_number, t.buyer_id, t.seller_id, t.status AS transaction_status, t.payment_status, t.delivery_status, l.title AS listing_title, buyer.full_name AS buyer_name, seller.full_name AS seller_name, courier.full_name AS courier_name FROM delivery_tasks dt JOIN marketplace_transactions t ON t.id=dt.marketplace_transaction_id JOIN listings_marketplace l ON l.id=t.listing_id JOIN users buyer ON buyer.id=t.buyer_id JOIN users seller ON seller.id=t.seller_id LEFT JOIN users courier ON courier.id=dt.courier_id WHERE dt.marketplace_transaction_id=? AND (t.buyer_id=? OR t.seller_id=?) LIMIT 1" . ($forUpdate ? ' FOR UPDATE' : '');
    $statement = $db->prepare($sql); $statement->execute([$transactionId, $actorId, $actorId]); $row = $statement->fetch();
    if (!$row) Http::error('not_found', 'طلب التوصيل غير موجود أو لا تملك صلاحية الوصول إليه.', 404);
    return $row;
}

function marketplaceDeliveryTaskPublic(array $task): array {
    return [
        'id' => (int)$task['id'], 'transaction_id' => (int)$task['marketplace_transaction_id'], 'tracking_code' => $task['tracking_code'], 'status' => $task['status'],
        'pickup_location' => $task['pickup_location_text'], 'delivery_location' => $task['delivery_location_text'], 'package_information' => $task['package_information'],
        'delivery_fee' => $task['delivery_fee'], 'currency' => $task['currency'], 'courier_id' => $task['courier_id'] === null ? null : (int)$task['courier_id'],
        'courier_name' => $task['courier_name'] ?? null, 'eta_value' => $task['eta_value'] === null ? null : (int)$task['eta_value'], 'eta_unit' => $task['eta_unit'],
        'listing_title' => $task['listing_title'], 'buyer_name' => $task['buyer_name'], 'seller_name' => $task['seller_name'], 'created_at' => $task['created_at'], 'accepted_at' => $task['accepted_at']
    ];
}

function marketplaceDeliveryNotificationContext(PDO $db, int $taskId): array {
    $statement = $db->prepare('SELECT dt.id, dt.marketplace_transaction_id, t.listing_id, t.buyer_id, t.seller_id FROM delivery_tasks dt JOIN marketplace_transactions t ON t.id=dt.marketplace_transaction_id WHERE dt.id=? AND dt.marketplace_transaction_id IS NOT NULL LIMIT 1');
    $statement->execute([$taskId]);
    return $statement->fetch() ?: [];
}

function marketplaceListingRows(PDO $db, bool $featured, int $limit = 16): array {
    marketplaceExpirePromotions($db);
    $featuredWhere = " AND (EXISTS (SELECT 1 FROM marketplace_promotions promotion_featured WHERE promotion_featured.listing_id=l.id AND promotion_featured.promotion_type='featured' AND promotion_featured.status='active' AND promotion_featured.end_at>NOW()) OR (l.is_featured=1 AND (l.featured_until IS NULL OR l.featured_until>NOW())))";
    $promotionOrder = "CASE WHEN EXISTS (SELECT 1 FROM marketplace_promotions promotion_boost WHERE promotion_boost.listing_id=l.id AND promotion_boost.promotion_type='boost' AND promotion_boost.status='active' AND promotion_boost.end_at>NOW()) THEN 0 WHEN EXISTS (SELECT 1 FROM marketplace_promotions promotion_top WHERE promotion_top.listing_id=l.id AND promotion_top.promotion_type='top_category' AND promotion_top.status='active' AND promotion_top.end_at>NOW()) THEN 1 ELSE 2 END";
    $statement = $db->prepare(marketplaceListingSelect() . " WHERE l.status='published'" . ($featured ? $featuredWhere : '') . ' ORDER BY ' . ($featured ? 'l.published_at DESC' : $promotionOrder . ', l.published_at DESC, l.id DESC') . ' LIMIT :limit');
    $statement->bindValue('limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetchAll();
}

if ($method === 'GET' && $path === '/health') Http::json(['data' => ['status' => 'ok', 'service' => 'souqlink-api']]);

$db = Database::connection();

function ensureDeliveryTaskStatuses(PDO $db): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        $db->exec("UPDATE delivery_tasks SET status='in_transit' WHERE status='proof_submitted'");
        $db->exec("UPDATE delivery_tasks SET status='delivered' WHERE status='approved'");
    } catch (Throwable $exception) {
        error_log('delivery task status migration: ' . $exception->getMessage());
    }
}
ensureDeliveryTaskStatuses($db);

if ($method === 'GET' && route('/media/{file}', $path, $parameters)) {
    $fileName = basename($parameters['file']);
    $statement = $db->prepare('SELECT file_path FROM product_images WHERE file_path=? UNION SELECT image_path AS file_path FROM advertisements WHERE image_path=? UNION SELECT logo_path AS file_path FROM stores WHERE logo_path=? UNION SELECT avatar_path AS file_path FROM users WHERE avatar_path=? UNION SELECT image_path AS file_path FROM categories_marketplace WHERE image_path=? UNION SELECT file_path FROM listing_images_marketplace WHERE file_path=? UNION SELECT image_path AS file_path FROM marketplace_messages WHERE image_path=? UNION SELECT proof_image_path AS file_path FROM delivery_tasks WHERE proof_image_path=? UNION SELECT pickup_proof_image_path AS file_path FROM delivery_tasks WHERE pickup_proof_image_path=? UNION SELECT delivery_proof_image_path AS file_path FROM delivery_tasks WHERE delivery_proof_image_path=? UNION SELECT courier_delivery_proof_image_path AS file_path FROM delivery_tasks WHERE courier_delivery_proof_image_path=? UNION SELECT customer_proof_image_path AS file_path FROM delivery_tasks WHERE customer_proof_image_path=? LIMIT 1');
    $statement->execute([$fileName, $fileName, $fileName, $fileName, $fileName, $fileName, $fileName, $fileName, $fileName, $fileName, $fileName, $fileName]);
    if (!$statement->fetch()) Http::error('not_found', 'الصورة غير موجودة.', 404);
    $directory = rtrim(Database::environment('UPLOAD_PATH', __DIR__ . '/storage/uploads'), '/'); $file = $directory . '/' . $fileName;
    if (!is_file($file)) Http::error('not_found', 'الصورة غير موجودة.', 404);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime); header('Content-Length: ' . filesize($file)); header('Cache-Control: public, max-age=86400'); header('X-Content-Type-Options: nosniff'); readfile($file); exit;
}

if ($method === 'POST' && $path === '/auth/register') {
    $input = Http::input();
    Http::requireFields($input, ['full_name', 'phone', 'password', 'role']);
    $fullName = trim((string)$input['full_name']);
    $phone = strtr(trim((string)$input['phone']), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    $phone = preg_replace('/[\s\-()]/', '', $phone) ?? '';
    if (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 120) Http::error('validation_error', 'الاسم الكامل يجب أن يتكون من 3 إلى 120 حرفاً.', 422, ['full_name' => 'الاسم غير صالح.']);
    if (!preg_match('/^\+?[0-9]{8,32}$/', $phone)) Http::error('validation_error', 'أدخل رقم هاتف صالحاً.', 422, ['phone' => 'رقم الهاتف غير صالح.']);
    if (!in_array($input['role'], ['customer', 'merchant', 'courier'], true)) Http::error('validation_error', 'الدور المختار غير متاح.', 422);
    if (mb_strlen((string)$input['password']) < 8) Http::error('validation_error', 'كلمة المرور يجب أن تتكون من 8 محارف على الأقل.', 422, ['password' => 'قصيرة جداً']);
    $lock = ['locked' => 1];
    $db->beginTransaction();
    try {
        $isFirstAccount = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;
        $assignedRole = $isFirstAccount ? 'admin' : $input['role'];
        $statement = $db->prepare('INSERT INTO users (full_name, phone, email, password_hash, role, status) VALUES (:name, :phone, :email, :password, :role, :status)');
        $statement->execute(['name' => $fullName, 'phone' => $phone, 'email' => $input['email'] ?? null, 'password' => password_hash($input['password'], PASSWORD_DEFAULT), 'role' => $assignedRole, 'status' => 'active']);
        $id = (int)$db->lastInsertId();
        if ($assignedRole === 'merchant') $db->prepare('INSERT INTO merchant_profiles (user_id, sham_cash_address) VALUES (?, ?)')->execute([$id, '']);
        if ($assignedRole === 'courier') $db->prepare('INSERT INTO courier_profiles (user_id, verification_status) VALUES (?, ?)')->execute([$id, 'pending']);
        Audit::log($db, $id, 'account.created', 'user', $id, null, ['role' => $assignedRole, 'is_first_admin' => $isFirstAccount]);
        $db->commit();
    } catch (PDOException $exception) {
        if ($db->inTransaction()) $db->rollBack();
        if (isDuplicateConstraint($exception)) Http::error('duplicate_account', 'رقم الهاتف أو البريد مستخدم مسبقاً.', 409);
        throw $exception;
    } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        throw $exception;
    } finally {
        // Named lock not required in SQLite transactions
    }
    $createdAt = date('Y-m-d H:i:s');
    $account = ['id' => $id, 'full_name' => $fullName, 'phone' => $phone, 'email' => $input['email'] ?? null, 'role' => $assignedRole, 'status' => 'active', 'profile_update_permission' => 'allowed', 'avatar_path' => null, 'created_at' => $createdAt];
    Http::json(['data' => ['access_token' => Auth::createToken($account), 'token_type' => 'Bearer', 'expires_in' => 3600, 'user' => $account, 'is_first_admin' => $isFirstAccount]], 201);
}

if ($method === 'POST' && $path === '/auth/login') {
    $input = Http::input();
    Http::requireFields($input, ['phone', 'password']);
    $statement = $db->prepare('SELECT id, full_name, phone, email, password_hash, role, status, profile_update_permission, avatar_path, created_at FROM users WHERE phone = :phone LIMIT 1');
    $statement->execute(['phone' => trim($input['phone'])]);
    $account = $statement->fetch();
    if (!$account || !password_verify($input['password'], $account['password_hash'])) Http::error('invalid_credentials', 'بيانات الدخول غير صحيحة.', 401);
    if ($account['status'] !== 'active') Http::error('account_unavailable', 'الحساب قيد المراجعة أو مقيّد.', 403);
    $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$account['id']]);
    Http::json(['data' => ['access_token' => Auth::createToken($account), 'token_type' => 'Bearer', 'expires_in' => 3600, 'user' => array_diff_key($account, ['password_hash' => true])]]);
}

if ($method === 'GET' && $path === '/profile') {
    $actor = user($db);
    Http::json(['data' => $actor]);
}

if ($method === 'PATCH' && $path === '/profile') {
    $actor = user($db); $input = Http::input();
    $verification = $db->prepare("SELECT 1 FROM verification_requests WHERE user_id=? AND subject_role=? AND status='approved' LIMIT 1");
    $verification->execute([$actor['id'], $actor['role']]);
    $verified = in_array($actor['role'], ['merchant', 'courier'], true) && (bool)$verification->fetchColumn();
    if ($verified && ($actor['profile_update_permission'] ?? 'locked') !== 'allowed') Http::error('profile_locked', 'تم تقييد تعديل البيانات بعد التوثيق. اطلب من الإدارة السماح بالتعديل.', 403);
    $name = array_key_exists('full_name', $input) ? trim((string)$input['full_name']) : $actor['full_name'];
    $phone = array_key_exists('phone', $input) ? trim((string)$input['phone']) : $actor['phone'];
    $email = array_key_exists('email', $input) ? trim((string)$input['email']) : $actor['email'];
    if (mb_strlen($name) < 3 || mb_strlen($name) > 120) Http::error('validation_error', 'الاسم الكامل يجب أن يتكون من 3 إلى 120 حرفاً.', 422);
    if (!preg_match('/^\+?[0-9]{8,32}$/', $phone)) Http::error('validation_error', 'أدخل رقم هاتف صالحاً.', 422);
    if ($email !== null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) Http::error('validation_error', 'البريد الإلكتروني غير صالح.', 422);
    try { $db->prepare('UPDATE users SET full_name=?, phone=?, email=? WHERE id=?')->execute([$name, $phone, $email ?: null, $actor['id']]); }
    catch (PDOException $exception) { if (isDuplicateConstraint($exception)) Http::error('duplicate_account', 'رقم الهاتف أو البريد مستخدم مسبقاً.', 409); throw $exception; }
    Audit::log($db, (int)$actor['id'], 'profile.updated', 'user', (int)$actor['id'], null, ['full_name' => $name]);
    $fresh = $db->prepare('SELECT id, full_name, phone, email, role, status, profile_update_permission, avatar_path, created_at FROM users WHERE id=?'); $fresh->execute([$actor['id']]); Http::json(['data' => $fresh->fetch()]);
}

if ($method === 'POST' && $path === '/profile/avatar') {
    $actor = user($db);
    if (($actor['profile_update_permission'] ?? 'locked') !== 'allowed') Http::error('profile_locked', 'لا يسمح بتعديل الصورة الشخصية حالياً.', 403);
    $avatar = saveUpload('image', (int)Database::environment('MAX_PROFILE_IMAGE_BYTES', '3145728'), 'avatar');
    $db->prepare('UPDATE users SET avatar_path=? WHERE id=?')->execute([$avatar, $actor['id']]); Audit::log($db, (int)$actor['id'], 'profile.avatar.updated', 'user', (int)$actor['id']); Http::json(['data' => ['avatar_path' => $avatar]]);
}

function syncVerificationSubject(PDO $db, int $userId, string $subjectRole, string $state): void {
    $verified = $state === 'approved';
    if ($subjectRole === 'courier') {
        $courierState = $verified ? 'verified' : ($state === 'pending' ? 'pending' : 'rejected');
        $db->prepare('UPDATE courier_profiles SET verification_status=? WHERE user_id=?')->execute([$courierState, $userId]);
    }
    if ($subjectRole === 'merchant') {
        $db->prepare('UPDATE stores SET is_verified=? WHERE merchant_id=?')->execute([$verified ? 1 : 0, $userId]);
    }
    $db->prepare("UPDATE users SET profile_update_permission=? WHERE id=?")->execute([$verified ? 'locked' : 'allowed', $userId]);
}

if ($method === 'GET' && $path === '/verification/me') {
    $actor = user($db); role($actor, ['merchant', 'courier']);
    $statement = $db->prepare("SELECT id, subject_role, CASE WHEN status='approved' AND is_active=1 THEN 'approved' WHEN status='approved' AND is_active=0 THEN 'revoked' ELSE status END AS status, review_note, identity_front_path, portrait_path, reviewed_at, created_at, updated_at FROM verification_requests WHERE user_id=:user AND subject_role=:role LIMIT 1");
    $statement->execute(['user' => $actor['id'], 'role' => $actor['role']]);
    Http::json(['data' => $statement->fetch() ?: ['subject_role' => $actor['role'], 'status' => 'not_submitted']]);
}

if ($method === 'POST' && $path === '/verification') {
    $actor = user($db); role($actor, ['merchant', 'courier']);
    $existingStatement = $db->prepare("SELECT status, is_active FROM verification_requests WHERE user_id=? AND subject_role=? LIMIT 1");
    $existingStatement->execute([$actor['id'], $actor['role']]);
    $existing = $existingStatement->fetch();
    if ($existing && $existing['status'] === 'approved' && (int)$existing['is_active'] === 1) {
        Http::error('already_verified', 'الحساب موثق بالفعل. لا يمكن إرسال طلب توثيق جديد قبل إلغاء التوثيق من الإدارة.', 409);
    }
    $uploaded = [];
    try {
        $uploaded[] = $identityFront = saveUpload('identity_front', (int)Database::environment('MAX_VERIFICATION_IMAGE_BYTES', '5242880'), 'verification_identity');
        $uploaded[] = $portrait = saveUpload('portrait', (int)Database::environment('MAX_VERIFICATION_IMAGE_BYTES', '5242880'), 'verification_portrait');
        $db->beginTransaction();
        $db->prepare("INSERT INTO verification_requests (user_id, subject_role, identity_front_path, portrait_path, status, is_active, review_note, reviewed_by, reviewed_at) VALUES (:user, :role, :identity, :portrait, 'pending', 0, NULL, NULL, NULL) ON CONFLICT(user_id, subject_role) DO UPDATE SET identity_front_path=excluded.identity_front_path, portrait_path=excluded.portrait_path, status='pending', is_active=0, review_note=NULL, reviewed_by=NULL, reviewed_at=NULL")
            ->execute(['user' => $actor['id'], 'role' => $actor['role'], 'identity' => $identityFront, 'portrait' => $portrait]);
        syncVerificationSubject($db, (int)$actor['id'], (string)$actor['role'], 'pending');
        Audit::log($db, (int)$actor['id'], 'verification.submitted', 'verification_request', (int)$actor['id'], null, ['role' => $actor['role'], 'status' => 'pending']);
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        $directory = rtrim(Database::environment('UPLOAD_PATH', __DIR__ . '/storage/uploads'), '/');
        foreach ($uploaded as $fileName) { $pathName = $directory . '/' . basename($fileName); if (is_file($pathName)) @unlink($pathName); }
        throw $exception;
    }
    Http::json(['data' => ['subject_role' => $actor['role'], 'status' => 'pending']], 201);
}

if ($method === 'GET' && $path === '/stores') {
    $page = Http::queryInt('page', 1, 1, 10000); $perPage = Http::queryInt('per_page', 20, 1, 50); $offset = ($page - 1) * $perPage;
    $hasVisitEvents = schemaTableExists($db, 'store_visit_events');
    $hasFollowers = schemaTableExists($db, 'store_followers');
    $visitTotalSql = $hasVisitEvents ? '(SELECT COUNT(*) FROM store_visit_events sv_total WHERE sv_total.store_id=s.id)' : '0';
    $visitWeeklySql = $hasVisitEvents ? "(SELECT COUNT(*) FROM store_visit_events sv WHERE sv.store_id=s.id AND sv.created_at >= datetime('now', '-7 days'))" : '0';
    $subscriberCountSql = $hasFollowers ? "(SELECT COUNT(*) FROM store_followers sf_count WHERE sf_count.store_id=s.id AND sf_count.status='active')" : '0';
    $hoursSql = schemaColumnExists($db, 'stores', 'opening_hours_json') ? 's.opening_hours_json' : 'NULL';
    $statement = $db->prepare("SELECT s.id, s.name, s.description, s.logo_path, s.phone, s.store_type, s.is_verified, s.rating_avg AS rating, s.rating_count, s.created_at, {$hoursSql} AS opening_hours_json, {$visitTotalSql} AS total_views, {$visitWeeklySql} AS weekly_views, {$subscriberCountSql} AS subscriber_count, (SELECT COUNT(DISTINCT o.id) FROM orders o WHERE o.store_id=s.id AND o.status='delivered' AND o.created_at >= datetime('now', '-7 days')) AS weekly_sales FROM stores s WHERE s.status='active' ORDER BY s.id DESC LIMIT :limit OFFSET :offset");
    $statement->bindValue('limit', $perPage, PDO::PARAM_INT); $statement->bindValue('offset', $offset, PDO::PARAM_INT); $statement->execute();
    Http::json(['data' => $statement->fetchAll(), 'meta' => ['page' => $page, 'per_page' => $perPage]]);
}

if ($method === 'GET' && $path === '/products') {
    $q = trim((string)($_GET['q'] ?? '')); $storeId = filter_input(INPUT_GET, 'store_id', FILTER_VALIDATE_INT); $offersOnly = (string)($_GET['offers'] ?? '') === '1';
    $hasOfferPrice = schemaColumnExists($db, 'products', 'offer_price');
    $hasOfferStarts = schemaColumnExists($db, 'products', 'offer_starts_at');
    $hasOfferEnds = schemaColumnExists($db, 'products', 'offer_ends_at');
    $offerFields = ($hasOfferPrice ? 'p.offer_price' : 'NULL') . ' AS offer_price, ' . ($hasOfferStarts ? 'p.offer_starts_at' : 'NULL') . ' AS offer_starts_at, ' . ($hasOfferEnds ? 'p.offer_ends_at' : 'NULL') . ' AS offer_ends_at';
    $sql = "SELECT p.*, {$offerFields}, s.name AS store_name, s.store_type, s.is_verified AS store_is_verified, (SELECT file_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY sort_order, id LIMIT 1) AS image_path FROM products p JOIN stores s ON s.id=p.store_id WHERE p.status='active' AND s.status='active'";
    $parameters = [];
    if ($q !== '') { $sql .= ' AND (p.name LIKE :q OR s.name LIKE :q)'; $parameters['q'] = "%{$q}%"; }
    if ($storeId) { $sql .= ' AND p.store_id = :store_id'; $parameters['store_id'] = $storeId; }
    if ($offersOnly) {
        if (!$hasOfferPrice || !$hasOfferEnds) { Http::json(['data' => []]); }
        $sql .= " AND p.offer_price IS NOT NULL AND p.offer_price > 0 AND p.offer_ends_at > UTC_TIMESTAMP()" . ($hasOfferStarts ? " AND (p.offer_starts_at IS NULL OR p.offer_starts_at <= UTC_TIMESTAMP())" : '');
    }
    $sql .= ' ORDER BY p.created_at DESC LIMIT 100';
    $statement = $db->prepare($sql); $statement->execute($parameters); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'GET' && $path === '/discovery') {
    $viewer = optionalUser($db); $viewerId = $viewer ? (int)$viewer['id'] : 0;
    $hasVisitEvents = schemaTableExists($db, 'store_visit_events');
    $hasFollowers = schemaTableExists($db, 'store_followers');
    $hasOfferPrice = schemaColumnExists($db, 'products', 'offer_price');
    $hasOfferStarts = schemaColumnExists($db, 'products', 'offer_starts_at');
    $hasOfferEnds = schemaColumnExists($db, 'products', 'offer_ends_at');
    $visitTotalSql = $hasVisitEvents ? '(SELECT COUNT(*) FROM store_visit_events sv_total WHERE sv_total.store_id=s.id)' : '0';
    $visitWeeklySql = $hasVisitEvents ? "(SELECT COUNT(*) FROM store_visit_events sv WHERE sv.store_id=s.id AND sv.created_at >= datetime('now', '-7 days'))" : '0';
    $subscriptionSql = $hasFollowers && $viewerId > 0 ? "CASE WHEN EXISTS (SELECT 1 FROM store_followers sf WHERE sf.store_id=s.id AND sf.user_id={$viewerId} AND sf.status='active') THEN 1 ELSE 0 END" : '0';
    $subscriberCountSql = $hasFollowers ? "(SELECT COUNT(*) FROM store_followers sf_count WHERE sf_count.store_id=s.id AND sf_count.status='active')" : '0';
    $hoursSql = schemaColumnExists($db, 'stores', 'opening_hours_json') ? 's.opening_hours_json' : 'NULL';
    $storesSql = "SELECT s.id, s.name, s.description, s.logo_path, s.phone, s.store_type, s.is_verified, s.rating_avg AS rating, s.rating_count, s.created_at, {$hoursSql} AS opening_hours_json, COUNT(p.id) AS product_count, {$visitTotalSql} AS total_views, {$visitWeeklySql} AS weekly_views, {$subscriberCountSql} AS subscriber_count, (SELECT COUNT(DISTINCT o.id) FROM orders o WHERE o.store_id=s.id AND o.status='delivered' AND o.created_at >= datetime('now', '-7 days')) AS weekly_sales, {$subscriptionSql} AS is_subscribed FROM stores s LEFT JOIN products p ON p.store_id=s.id AND p.status='active' WHERE s.status='active' GROUP BY s.id ORDER BY is_subscribed DESC, s.is_verified DESC, rating DESC, s.id DESC LIMIT 50";
    $storeRows = $db->query($storesSql)->fetchAll(); $stores = $storeRows;
    $offerFields = ($hasOfferPrice ? 'p.offer_price' : 'NULL') . ' AS offer_price, ' . ($hasOfferStarts ? 'p.offer_starts_at' : 'NULL') . ' AS offer_starts_at, ' . ($hasOfferEnds ? 'p.offer_ends_at' : 'NULL') . ' AS offer_ends_at';
    $base = "SELECT p.id, p.name, p.description, p.price, {$offerFields}, p.currency, p.stock_quantity, p.preorder_unit_label, p.preorder_min_quantity, p.preorder_lead_days, p.delivery_fee, p.rating_avg, p.rating_count, p.sales_count, p.store_id, s.name AS store_name, s.store_type, s.is_verified AS store_is_verified, (SELECT file_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY sort_order, id LIMIT 1) AS image_path FROM products p JOIN stores s ON s.id=p.store_id WHERE p.status='active' AND s.status='active'";
    $mostDemanded = $db->query($base . ' ORDER BY p.sales_count DESC, p.rating_avg DESC, p.created_at DESC LIMIT 12')->fetchAll();
    $topRated = $db->query($base . ' ORDER BY p.rating_avg DESC, p.rating_count DESC, p.created_at DESC LIMIT 12')->fetchAll();
    $bestSelling = $db->query($base . ' ORDER BY p.sales_count DESC, p.created_at DESC LIMIT 12')->fetchAll();
    // لا يتيح نموذج الإدارة جدولة بداية مستقبلية، لذلك لا نمنع الإعلان النشط
    // بسبب فرق المنطقة الزمنية بين جهاز المدير والخادم. يبقى تاريخ الانتهاء
    // والحالة الفعلية هما ضابطا العرض العام.
    $ads = schemaTableExists($db, 'advertisements')
        ? $db->query("SELECT id, title, body, image_path, size_preset, target_type, target_value, starts_at, ends_at FROM advertisements WHERE status='active' AND (ends_at IS NULL OR ends_at>NOW()) ORDER BY display_order ASC, id DESC LIMIT 20")->fetchAll()
        : [];
    Http::json(['data' => ['stores' => $stores, 'most_demanded' => $mostDemanded, 'top_rated' => $topRated, 'best_selling' => $bestSelling, 'advertisements' => $ads]]);
}

if ($method === 'GET' && $path === '/marketplace/home') {
    $title = $db->query("SELECT value_text FROM marketplace_settings WHERE setting_key='home_title' LIMIT 1")->fetchColumn();
    $categories = $db->query("SELECT id, parent_id, name, slug, icon_key, image_path, display_order, status FROM categories_marketplace WHERE parent_id IS NULL AND status='active' ORDER BY display_order, name, id LIMIT 20")->fetchAll();
    $countries = $db->query("SELECT id, parent_id, location_type, name, code, display_order, status FROM marketplace_locations WHERE parent_id IS NULL AND location_type='country' AND status='active' ORDER BY display_order, name, id")->fetchAll();
    Http::json(['data' => ['title' => $title ?: 'الحراج', 'categories' => $categories, 'featured_listings' => marketplaceListingRows($db, true, 16), 'latest_listings' => marketplaceListingRows($db, false, 10), 'countries' => $countries]]);
}

if ($method === 'GET' && $path === '/marketplace/notifications') {
    $actor = user($db); $page = Http::queryInt('page', 1, 1, 1000000); $perPage = Http::queryInt('per_page', 30, 1, 50); $offset = ($page - 1) * $perPage;
    $statement = $db->prepare('SELECT id, notification_type, title, body, listing_id, conversation_id, offer_id, transaction_id, delivery_task_id, review_id, payload_json, read_at, created_at FROM marketplace_notifications WHERE user_id=? ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?'); $statement->bindValue(1, $actor['id'], PDO::PARAM_INT); $statement->bindValue(2, $perPage, PDO::PARAM_INT); $statement->bindValue(3, $offset, PDO::PARAM_INT); $statement->execute(); $rows = $statement->fetchAll(); foreach ($rows as &$row) { $row['payload'] = $row['payload_json'] === null ? null : (json_decode((string)$row['payload_json'], true) ?: null); unset($row['payload_json']); } unset($row);
    $count = $db->prepare('SELECT COUNT(*) FROM marketplace_notifications WHERE user_id=? AND read_at IS NULL'); $count->execute([$actor['id']]); Http::json(['data' => $rows, 'meta' => ['page' => $page, 'per_page' => $perPage, 'has_more' => count($rows) === $perPage, 'unread_count' => (int)$count->fetchColumn()]]);
}

if ($method === 'POST' && route('/marketplace/notifications/{id}/read', $path, $parameters)) {
    $actor = user($db); $notificationId = (int)$parameters['id']; $statement = $db->prepare('UPDATE marketplace_notifications SET read_at=COALESCE(read_at, NOW()) WHERE id=? AND user_id=?'); $statement->execute([$notificationId, $actor['id']]); if ($statement->rowCount() === 0) { $check = $db->prepare('SELECT id FROM marketplace_notifications WHERE id=? AND user_id=?'); $check->execute([$notificationId, $actor['id']]); if (!$check->fetch()) Http::error('not_found', 'الإشعار غير موجود.', 404); } Http::json(['data' => ['id' => $notificationId, 'read' => true]]);
}

if ($method === 'POST' && $path === '/marketplace/notifications/read-all') {
    $actor = user($db); $db->prepare('UPDATE marketplace_notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL')->execute([$actor['id']]); Http::json(['data' => ['read_all' => true]]);
}

if ($method === 'POST' && $path === '/marketplace/listings') {
    $actor = user($db); $input = Http::input();
    Http::requireFields($input, ['title', 'description', 'category_id']);
    $title = trim((string)$input['title']); $description = trim((string)$input['description']);
    $categoryId = (int)$input['category_id']; $kind = (string)($input['listing_kind'] ?? 'item');
    $condition = $input['item_condition'] ?? null; $price = $input['price'] ?? null;
    $currency = $input['currency'] ?? null; $linkType = (string)($input['link_type'] ?? 'none');
    $fulfillment = (string)($input['fulfillment_mode'] ?? 'pickup');
    $latitude = array_key_exists('latitude', $input) && $input['latitude'] !== null && $input['latitude'] !== '' ? (float)$input['latitude'] : null;
    $longitude = array_key_exists('longitude', $input) && $input['longitude'] !== null && $input['longitude'] !== '' ? (float)$input['longitude'] : null;
    $serviceFrom = trim((string)($input['service_available_from'] ?? '')) ?: null;
    $serviceTo = trim((string)($input['service_available_to'] ?? '')) ?: null;
    $contactPhone = trim((string)($input['contact_phone'] ?? '')) ?: null;
    if ($kind === 'service' && (($serviceFrom !== null && !preg_match('/^([01]\\d|2[0-3]):[0-5]\\d(:[0-5]\\d)?$/', $serviceFrom)) || ($serviceTo !== null && !preg_match('/^([01]\\d|2[0-3]):[0-5]\\d(:[0-5]\\d)?$/', $serviceTo)))) Http::error('validation_error', 'وقت توفر الخدمة غير صالح.', 422);
    if ($contactPhone !== null && (mb_strlen($contactPhone) > 32 || !preg_match('/^[0-9+()\\-\\s]{6,32}$/', $contactPhone))) Http::error('validation_error', 'رقم الهاتف غير صالح.', 422);
    if ($latitude !== null && ($latitude < -90 || $latitude > 90) || $longitude !== null && ($longitude < -180 || $longitude > 180)) Http::error('validation_error', 'إحداثيات الموقع غير صالحة.', 422);
    if (mb_strlen($title) < 2 || mb_strlen($title) > 180 || mb_strlen($description) < 2 || mb_strlen($description) > 10000) Http::error('validation_error', 'تحقق من عنوان الإعلان ووصفه.', 422);
    if (!in_array($kind, ['item', 'service'], true) || ($kind === 'item' && !in_array($condition, ['new', 'used'], true)) || !in_array($fulfillment, ['pickup', 'delivery', 'both'], true)) Http::error('validation_error', 'نوع الإعلان أو حالته أو طريقة التسليم غير صالحة.', 422);
    if ($price !== null && $price !== '' && (!is_numeric($price) || (float)$price < 0)) Http::error('validation_error', 'السعر غير صالح.', 422);
    $price = ($price === null || $price === '') ? null : (float)$price;
    if ($price !== null && !in_array($currency, ['USD', 'SYP'], true)) Http::error('validation_error', 'اختر عملة صحيحة للسعر.', 422);
    $category = $db->prepare("SELECT id FROM categories_marketplace WHERE id=? AND status='active' LIMIT 1"); $category->execute([$categoryId]); if (!$category->fetch()) Http::error('category_unavailable', 'القسم غير متاح حالياً.', 422);
    $linkedProductId = null; $externalUrl = null; $storeId = null;
    if ($linkType === 'product') {
        $linkedProductId = (int)($input['linked_product_id'] ?? 0); if ($linkedProductId < 1) Http::error('validation_error', 'اختر المنتج المرتبط.', 422);
        $product = $db->prepare("SELECT id, store_id FROM products WHERE id=? AND status='active' LIMIT 1"); $product->execute([$linkedProductId]); $product = $product->fetch(); if (!$product) Http::error('linked_product_unavailable', 'المنتج المرتبط غير متاح.', 422); $storeId = (int)$product['store_id'];
    } elseif ($linkType === 'external') {
        $externalUrl = trim((string)($input['external_url'] ?? '')); $parsed = filter_var($externalUrl, FILTER_VALIDATE_URL); if (!$parsed || !in_array(strtolower((string)parse_url($externalUrl, PHP_URL_SCHEME)), ['http', 'https'], true) || mb_strlen($externalUrl) > 2048) Http::error('validation_error', 'أدخل رابطاً خارجياً صحيحاً يبدأ بـ http أو https.', 422);
    } elseif ($linkType !== 'none') {
        Http::error('validation_error', 'نوع الربط غير صالح.', 422);
    }
    $autoApprove = $db->query("SELECT value_text FROM marketplace_settings WHERE setting_key='auto_approve_listings' LIMIT 1")->fetchColumn() === '1';
    $listingStatus = $autoApprove ? 'published' : 'pending_review';
    $insert = $db->prepare("INSERT INTO listings_marketplace (seller_user_id, store_id, category_id, title, description, listing_kind, item_condition, price, currency, is_negotiable, fulfillment_mode, link_type, linked_product_id, external_url, location_text, latitude, longitude, service_available_from, service_available_to, contact_phone, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ?='published' THEN NOW() ELSE NULL END)");
    $insert->execute([(int)$actor['id'], $storeId, $categoryId, $title, $description, $kind, $condition, $price, $price === null ? null : $currency, !empty($input['is_negotiable']) ? 1 : 0, $fulfillment, $linkType, $linkedProductId, $externalUrl, trim((string)($input['location_text'] ?? '')) ?: null, $latitude, $longitude, $kind === 'service' ? $serviceFrom : null, $kind === 'service' ? $serviceTo : null, $kind === 'service' ? $contactPhone : null, $listingStatus, $listingStatus]);
    $listingId = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'marketplace.listing.created', 'marketplace_listing', $listingId, null, ['status' => $listingStatus, 'link_type' => $linkType]);
    if ($listingStatus === 'pending_review') {
        $admins = $db->query("SELECT id FROM users WHERE role='admin' AND status='active'")->fetchAll(); foreach ($admins as $admin) marketplaceNotify($db, (int)$admin['id'], 'listing_submitted', 'إعلان حراج جديد للمراجعة', 'تم إرسال إعلان جديد إلى لوحة الإدارة.', 'listing_submitted:' . $listingId, ['listing_id' => $listingId]);
    }
    Http::json(['data' => ['id' => $listingId, 'status' => $listingStatus, 'link_type' => $linkType, 'linked_product_id' => $linkedProductId, 'external_url' => $externalUrl]], 201);
}

if ($method === 'GET' && route('/marketplace/my/listings/{id}', $path, $parameters)) {
    $actor = user($db);
    $listing = marketplaceOwnedListing($db, (int)$parameters['id'], (int)$actor['id']);
    Http::json(['data' => marketplaceMyListingRow($db, (int)$listing['id'], (int)$actor['id'])]);
}

if ($method === 'POST' && route('/marketplace/my/listings/{id}/image', $path, $parameters)) {
    $actor = user($db); $listingId = (int)$parameters['id'];
    $listing = marketplaceOwnedListing($db, $listingId, (int)$actor['id']);
    if (in_array($listing['status'], ['reserved', 'sold', 'deleted'], true)) {
        Http::error('listing_locked', 'لا يمكن تعديل صورة إعلان محجوز أو مباع أو محذوف.', 409);
    }
    $imagePath = saveUpload('image', (int)Database::environment('MAX_MARKETPLACE_LISTING_IMAGE_BYTES', '4194304'), 'marketplace_listing');
    $existing = $db->prepare('SELECT id FROM listing_images_marketplace WHERE listing_id=? ORDER BY sort_order, id LIMIT 1');
    $existing->execute([$listingId]); $existingId = $existing->fetchColumn();
    if ($existingId) {
        $db->prepare('UPDATE listing_images_marketplace SET file_path=? WHERE id=?')->execute([$imagePath, $existingId]);
    } else {
        $db->prepare('INSERT INTO listing_images_marketplace (listing_id, file_path, sort_order) VALUES (?, ?, 0)')->execute([$listingId, $imagePath]);
    }
    Audit::log($db, (int)$actor['id'], 'marketplace.listing.image.updated', 'marketplace_listing', $listingId, null, ['file_path' => $imagePath]);
    Http::json(['data' => ['listing_id' => $listingId, 'image_path' => $imagePath]]);
}

if ($method === 'GET' && $path === '/marketplace/my/listings') {
    $actor = user($db); marketplaceExpirePromotions($db); $actorId = (int)$actor['id']; $tab = (string)($_GET['tab'] ?? 'active'); $page = Http::queryInt('page', 1, 1, 1000000); $perPage = Http::queryInt('per_page', 20, 1, 40); $offset = ($page - 1) * $perPage;
    $statusMap = ['active' => ['published','reserved'], 'review' => ['pending_review'], 'sold' => ['sold'], 'stopped' => ['hidden','rejected'], 'expired' => ['expired'], 'draft' => ['draft']];
    if (!isset($statusMap[$tab])) Http::error('validation_error', 'تبويب الإعلانات غير صالح.', 422);
    $statuses = $statusMap[$tab]; $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $sql = "SELECT l.id, l.title, l.description, l.price, l.currency, l.status, l.view_count, CASE WHEN EXISTS (SELECT 1 FROM marketplace_promotions promotion_featured WHERE promotion_featured.listing_id=l.id AND promotion_featured.promotion_type='featured' AND promotion_featured.status='active' AND promotion_featured.end_at>NOW()) THEN 1 ELSE l.is_featured END AS is_featured, l.featured_until, l.link_type, l.linked_product_id, l.external_url, l.published_at, l.created_at, (SELECT file_path FROM listing_images_marketplace image WHERE image.listing_id=l.id ORDER BY image.sort_order, image.id LIMIT 1) AS image_path, (SELECT COUNT(*) FROM favorites_marketplace favorite WHERE favorite.listing_id=l.id) AS favorite_count, (SELECT COUNT(*) FROM marketplace_conversations conversation WHERE conversation.listing_id=l.id) AS message_count, (SELECT COUNT(*) FROM marketplace_offers offer_row WHERE offer_row.listing_id=l.id) AS offer_count, (SELECT COUNT(*) FROM marketplace_transactions transaction_row WHERE transaction_row.listing_id=l.id AND transaction_row.status IN ('paid','completed')) AS sales_count, (SELECT COALESCE(SUM(transaction_row.amount), 0) FROM marketplace_transactions transaction_row WHERE transaction_row.listing_id=l.id AND transaction_row.status IN ('paid','completed')) AS sales_amount, (SELECT CONCAT(promotion.promotion_type, ':', promotion.status) FROM marketplace_promotions promotion WHERE promotion.listing_id=l.id ORDER BY promotion.created_at DESC, promotion.id DESC LIMIT 1) AS promotion_status FROM listings_marketplace l WHERE l.seller_user_id=? AND l.status IN ($placeholders) ORDER BY l.updated_at DESC, l.id DESC LIMIT ? OFFSET ?";
    $statement = $db->prepare($sql); $position = 1; $statement->bindValue($position++, $actorId, PDO::PARAM_INT); foreach ($statuses as $status) $statement->bindValue($position++, $status); $statement->bindValue($position++, $perPage, PDO::PARAM_INT); $statement->bindValue($position, $offset, PDO::PARAM_INT); $statement->execute(); $rows = $statement->fetchAll();
    Http::json(['data' => $rows, 'meta' => ['tab' => $tab, 'page' => $page, 'per_page' => $perPage, 'has_more' => count($rows) === $perPage]]);
}

if ($method === 'GET' && $path === '/marketplace/my/transactions') {
    $actor = user($db); $actorId = (int)$actor['id'];
    $statement = $db->prepare("SELECT t.id, t.transaction_number, t.listing_id, t.buyer_id, t.seller_id, COALESCE(t.agreed_price, t.amount) AS agreed_price, t.currency, t.status, t.payment_status, t.delivery_status, t.created_at, t.completed_at, l.title AS listing_title, (SELECT file_path FROM listing_images_marketplace image WHERE image.listing_id=t.listing_id ORDER BY image.sort_order, image.id LIMIT 1) AS image_path, CASE WHEN t.buyer_id=? THEN 'buyer' ELSE 'seller' END AS participant_role, other.full_name AS other_user_name FROM marketplace_transactions t JOIN listings_marketplace l ON l.id=t.listing_id JOIN users other ON other.id=CASE WHEN t.buyer_id=? THEN t.seller_id ELSE t.buyer_id END WHERE t.buyer_id=? OR t.seller_id=? ORDER BY t.created_at DESC, t.id DESC LIMIT 200");
    $statement->execute([$actorId, $actorId, $actorId, $actorId]); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'GET' && route('/marketplace/transactions/{id}', $path, $parameters)) {
    $actor = user($db); $row = marketplaceTransactionRow($db, (int)$parameters['id'], (int)$actor['id']); Http::json(['data' => $row]);
}

if ($method === 'GET' && route('/marketplace/transactions/{id}/review-state', $path, $parameters)) {
    $actor = user($db); $state = marketplaceTransactionReviewState($db, (int)$parameters['id'], (int)$actor['id']); Http::json(['data' => ['can_review' => $state['can_review'], 'reviewer_role' => $state['reviewer_role'], 'reviewee_id' => $state['reviewee_id']]]);
}

if ($method === 'POST' && route('/marketplace/transactions/{id}/reviews', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $transactionId = (int)$parameters['id']; $input = Http::input(); $rating = $input['rating'] ?? null; $comment = trim((string)($input['comment'] ?? ''));
    if (!is_numeric($rating) || (int)$rating < 1 || (int)$rating > 5 || (float)$rating !== (float)(int)$rating || mb_strlen($comment) > 1000) Http::error('validation_error', 'التقييم يجب أن يكون من 1 إلى 5 والتعليق لا يتجاوز 1000 حرف.', 422);
    $db->beginTransaction(); try { $state = marketplaceTransactionReviewState($db, $transactionId, $actorId); if (!$state['can_review']) Http::error('review_not_allowed', 'المراجعة متاحة مرة واحدة فقط بعد اكتمال المعاملة.', 409); $insert = $db->prepare('INSERT INTO marketplace_transaction_reviews (transaction_id, reviewer_id, reviewee_id, reviewer_role, rating, comment) VALUES (?, ?, ?, ?, ?, ?)'); $insert->execute([$transactionId, $actorId, $state['reviewee_id'], $state['reviewer_role'], (int)$rating, $comment ?: null]); $reviewId = (int)$db->lastInsertId(); $db->commit(); } catch (PDOException $exception) { if ($db->inTransaction()) $db->rollBack(); if (isDuplicateConstraint($exception)) Http::error('review_already_exists', 'لقد أرسلت مراجعتك لهذه المعاملة مسبقاً.', 409); throw $exception; } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    $summary = marketplaceReviewSummary($db, (int)$state['reviewee_id']); marketplaceNotify($db, (int)$state['reviewee_id'], 'review_received', 'تقييم جديد', 'تلقيت تقييماً جديداً بعد إتمام صفقة في الحراج.', 'review_received:' . $reviewId, ['transaction_id' => $transactionId, 'review_id' => $reviewId]); Audit::log($db, $actorId, 'marketplace.transaction.review_created', 'marketplace_transaction_review', $reviewId, null, ['transaction_id' => $transactionId, 'reviewee_id' => $state['reviewee_id'], 'rating' => (int)$rating]); Http::json(['data' => ['id' => $reviewId, 'transaction_id' => $transactionId, 'reviewee_id' => $state['reviewee_id'], 'rating' => (int)$rating, 'comment' => $comment ?: null, 'created_at' => gmdate('Y-m-d H:i:s'), 'average_rating' => $summary['average_rating'], 'total_reviews' => (int)$summary['total_reviews']]], 201);
}

if ($method === 'GET' && route('/marketplace/users/{id}/reviews', $path, $parameters)) {
    $userId = (int)$parameters['id']; $profile = $db->prepare("SELECT u.id, u.full_name, u.avatar_path, CASE WHEN EXISTS (SELECT 1 FROM verification_requests vr WHERE vr.user_id=u.id AND vr.status='approved' AND vr.is_active=1) THEN 1 ELSE 0 END AS is_verified FROM users u WHERE u.id=? LIMIT 1"); $profile->execute([$userId]); $profile = $profile->fetch(); if (!$profile) Http::error('not_found', 'ملف المستخدم غير موجود.', 404);
    $summary = marketplaceReviewSummary($db, $userId); $page = max(1, (int)($_GET['page'] ?? 1)); $perPage = min(40, max(1, (int)($_GET['per_page'] ?? 20))); $offset = ($page - 1) * $perPage;
    $reviews = $db->prepare('SELECT r.id, r.transaction_id, r.reviewer_role, r.rating, r.comment, r.created_at, reviewer.full_name AS reviewer_name FROM marketplace_transaction_reviews r JOIN users reviewer ON reviewer.id=r.reviewer_id WHERE r.reviewee_id=? ORDER BY r.created_at DESC, r.id DESC LIMIT ? OFFSET ?'); $reviews->bindValue(1, $userId, PDO::PARAM_INT); $reviews->bindValue(2, $perPage, PDO::PARAM_INT); $reviews->bindValue(3, $offset, PDO::PARAM_INT); $reviews->execute(); $rows = $reviews->fetchAll();
    Http::json(['data' => ['profile' => $profile, 'average_rating' => (float)$summary['average_rating'], 'total_reviews' => (int)$summary['total_reviews'], 'reviews' => $rows, 'has_more' => count($rows) === $perPage]]);
}

if ($method === 'POST' && route('/marketplace/transactions/{id}/actions', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $transactionId = (int)$parameters['id']; $input = Http::input(); $action = (string)($input['action'] ?? '');
    if (!in_array($action, ['confirm','request_payment','mark_paid','waiting_delivery','delivering','complete','cancel','dispute'], true)) Http::error('validation_error', 'إجراء المعاملة غير صالح.', 422);
    $db->beginTransaction(); try {
        $transaction = marketplaceTransactionRow($db, $transactionId, $actorId, true); $result = marketplaceTransactionAction($db, $transaction, $actorId, $action); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    $updated = marketplaceTransactionRow($db, $transactionId, $actorId); $recipientId = (int)$transaction['buyer_id'] === $actorId ? (int)$transaction['seller_id'] : (int)$transaction['buyer_id']; $completed = $result['status'] === 'completed'; marketplaceNotify($db, $recipientId, $completed ? 'transaction_completed' : 'transaction_updated', $completed ? 'اكتملت الصفقة' : 'تحديث على الصفقة', $completed ? 'تم إتمام الصفقة في الحراج بنجاح.' : 'تغيرت حالة صفقة مرتبطة بإعلان الحراج.', ($completed ? 'transaction_completed:' : 'transaction_updated:') . $transactionId . ':' . $result['status'], ['listing_id' => (int)$transaction['listing_id'], 'transaction_id' => $transactionId], ['action' => $action, 'status' => $result['status']]); Audit::log($db, $actorId, 'marketplace.transaction.' . $action, 'marketplace_transaction', $transactionId, null, $result); Http::json(['data' => $updated]);
}

if ($method === 'POST' && route('/marketplace/transactions/{id}/delivery', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $transactionId = (int)$parameters['id']; $input = Http::input();
    $fulfillment = (string)($input['fulfillment'] ?? ''); if (!in_array($fulfillment, ['pickup','delivery'], true)) Http::error('validation_error', 'اختر الاستلام المباشر أو التوصيل عبر التطبيق.', 422);
    $db->beginTransaction(); try {
        $transaction = marketplaceTransactionRow($db, $transactionId, $actorId, true);
        if ((int)$transaction['buyer_id'] !== $actorId || !in_array($transaction['status'], ['paid','waiting_delivery'], true)) Http::error('transaction_not_ready', 'يمكن اختيار الاستلام أو التوصيل بعد دفع المعاملة فقط.', 409);
        if ($fulfillment === 'pickup') {
            $db->prepare("UPDATE marketplace_transactions SET delivery_status='not_required' WHERE id=?")->execute([$transactionId]); $db->commit();
            Audit::log($db, $actorId, 'marketplace.transaction.pickup_selected', 'marketplace_transaction', $transactionId); Http::json(['data' => ['fulfillment' => 'pickup']]);
        }
        $pickup = trim((string)($input['pickup_location'] ?? '')); $delivery = trim((string)($input['delivery_location'] ?? '')); $package = trim((string)($input['package_information'] ?? $transaction['listing_title'])); $fee = $input['delivery_fee'] ?? null;
        if (mb_strlen($pickup) < 3 || mb_strlen($pickup) > 500 || mb_strlen($delivery) < 3 || mb_strlen($delivery) > 500 || mb_strlen($package) < 3 || mb_strlen($package) > 500 || !is_numeric($fee) || (float)$fee < 0) Http::error('validation_error', 'أدخل بيانات الالتقاط والتسليم والطرد ورسوم التوصيل بشكل صحيح.', 422);
        $existing = $db->prepare('SELECT id FROM delivery_tasks WHERE marketplace_transaction_id=? LIMIT 1 FOR UPDATE'); $existing->execute([$transactionId]); if ($existing->fetch()) Http::error('delivery_exists', 'يوجد طلب توصيل مرتبط بهذه المعاملة بالفعل.', 409);
        $tracking = 'MPD-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
        $create = $db->prepare("INSERT INTO delivery_tasks (marketplace_transaction_id, delivery_fee, currency, status, pickup_location_text, pickup_latitude, pickup_longitude, delivery_location_text, delivery_latitude, delivery_longitude, package_information, tracking_code) VALUES (?, ?, ?, 'requested', ?, ?, ?, ?, ?, ?, ?, ?)");
        $create->execute([$transactionId, (float)$fee, $transaction['currency'], $pickup, isset($input['pickup_latitude']) && is_numeric($input['pickup_latitude']) ? (float)$input['pickup_latitude'] : null, isset($input['pickup_longitude']) && is_numeric($input['pickup_longitude']) ? (float)$input['pickup_longitude'] : null, $delivery, isset($input['delivery_latitude']) && is_numeric($input['delivery_latitude']) ? (float)$input['delivery_latitude'] : null, isset($input['delivery_longitude']) && is_numeric($input['delivery_longitude']) ? (float)$input['delivery_longitude'] : null, $package, $tracking]);
        $taskId = (int)$db->lastInsertId(); $db->prepare("UPDATE marketplace_transactions SET status='waiting_delivery', delivery_status='waiting_delivery' WHERE id=?")->execute([$transactionId]); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    marketplaceNotify($db, (int)$transaction['seller_id'], 'delivery_created', 'تم إنشاء طلب توصيل', 'اختار المشتري التوصيل عبر التطبيق لهذه الصفقة.', 'delivery_created:' . $taskId, ['listing_id' => (int)$transaction['listing_id'], 'transaction_id' => $transactionId, 'delivery_task_id' => $taskId]); Audit::log($db, $actorId, 'marketplace.delivery.requested', 'delivery_task', $taskId, null, ['transaction_id' => $transactionId]); $task = marketplaceDeliveryTaskRow($db, $transactionId, $actorId); Http::json(['data' => marketplaceDeliveryTaskPublic($task)], 201);
}

if ($method === 'POST' && route('/marketplace/transactions/{id}/delivery/search-driver', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $transactionId = (int)$parameters['id'];
    $db->beginTransaction(); try { $task = marketplaceDeliveryTaskRow($db, $transactionId, $actorId, true); if (!in_array((int)$task['buyer_id'], [$actorId], true) && !in_array((int)$task['seller_id'], [$actorId], true)) Http::error('forbidden', 'لا تملك إدارة هذا الطلب.', 403); if ($task['status'] !== 'requested') Http::error('delivery_transition_invalid', 'لا يمكن بدء البحث عن عامل لهذه الحالة.', 409); $db->prepare("UPDATE delivery_tasks SET status='searching_driver' WHERE id=? AND status='requested'")->execute([$task['id']]); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    $task = marketplaceDeliveryTaskRow($db, $transactionId, $actorId); Audit::log($db, $actorId, 'marketplace.delivery.searching_driver', 'delivery_task', (int)$task['id']); Http::json(['data' => marketplaceDeliveryTaskPublic($task)]);
}

if ($method === 'GET' && route('/marketplace/transactions/{id}/delivery', $path, $parameters)) {
    $actor = user($db); $task = marketplaceDeliveryTaskRow($db, (int)$parameters['id'], (int)$actor['id']); Http::json(['data' => marketplaceDeliveryTaskPublic($task)]);
}

if ($method === 'GET' && $path === '/marketplace/my/offers') {
    $actor = user($db); $actorId = (int)$actor['id']; marketplaceExpireOffers($db);
    $statement = $db->prepare("SELECT o.*, l.title AS listing_title, (SELECT file_path FROM listing_images_marketplace image WHERE image.listing_id=o.listing_id ORDER BY image.sort_order, image.id LIMIT 1) AS image_path, creator.full_name AS created_by_name, other.full_name AS other_user_name, t.transaction_number, t.status AS transaction_status FROM marketplace_offers o JOIN listings_marketplace l ON l.id=o.listing_id JOIN users creator ON creator.id=o.created_by_id JOIN users other ON other.id=CASE WHEN o.created_by_id=? THEN CASE WHEN o.buyer_id=? THEN o.seller_id ELSE o.buyer_id END ELSE o.created_by_id END LEFT JOIN marketplace_transactions t ON t.id=o.transaction_id WHERE o.buyer_id=? OR o.seller_id=? ORDER BY o.created_at DESC, o.id DESC LIMIT 300");
    $statement->execute([$actorId, $actorId, $actorId, $actorId]); $rows = $statement->fetchAll(); foreach ($rows as &$row) $row = array_merge($row, marketplaceOfferPublic($row)); unset($row); Http::json(['data' => $rows]);
}

if ($method === 'PATCH' && route('/marketplace/my/listings/{id}', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $listingId = (int)$parameters['id']; $existing = marketplaceOwnedListing($db, $listingId, $actorId); $input = Http::input();
    if (in_array($existing['status'], ['reserved', 'sold', 'deleted'], true)) Http::error('listing_locked', 'لا يمكن تعديل إعلان محجوز أو مباع أو محذوف.', 409);
    $title = trim((string)($input['title'] ?? $existing['title'])); $description = trim((string)($input['description'] ?? $existing['description']));
    $categoryId = (int)($input['category_id'] ?? $existing['category_id']); $kind = (string)($input['listing_kind'] ?? $existing['listing_kind']);
    $condition = array_key_exists('item_condition', $input) ? $input['item_condition'] : $existing['item_condition']; $price = array_key_exists('price', $input) ? $input['price'] : $existing['price'];
    $currency = array_key_exists('currency', $input) ? $input['currency'] : $existing['currency']; $fulfillment = (string)($input['fulfillment_mode'] ?? $existing['fulfillment_mode']);
    $location = trim((string)($input['location_text'] ?? ($existing['location_text'] ?? ''))); $negotiable = array_key_exists('is_negotiable', $input) ? (!empty($input['is_negotiable']) ? 1 : 0) : (int)$existing['is_negotiable'];
    $latitude = array_key_exists('latitude', $input) ? (($input['latitude'] === null || $input['latitude'] === '') ? null : (float)$input['latitude']) : ($existing['latitude'] ?? null);
    $longitude = array_key_exists('longitude', $input) ? (($input['longitude'] === null || $input['longitude'] === '') ? null : (float)$input['longitude']) : ($existing['longitude'] ?? null);
    $serviceFrom = array_key_exists('service_available_from', $input) ? (trim((string)$input['service_available_from']) ?: null) : ($existing['service_available_from'] ?? null);
    $serviceTo = array_key_exists('service_available_to', $input) ? (trim((string)$input['service_available_to']) ?: null) : ($existing['service_available_to'] ?? null);
    $contactPhone = array_key_exists('contact_phone', $input) ? (trim((string)$input['contact_phone']) ?: null) : ($existing['contact_phone'] ?? null);
    if ($kind === 'service' && (($serviceFrom !== null && !preg_match('/^([01]\\d|2[0-3]):[0-5]\\d(:[0-5]\\d)?$/', $serviceFrom)) || ($serviceTo !== null && !preg_match('/^([01]\\d|2[0-3]):[0-5]\\d(:[0-5]\\d)?$/', $serviceTo)))) Http::error('validation_error', 'وقت توفر الخدمة غير صالح.', 422);
    if ($contactPhone !== null && (mb_strlen($contactPhone) > 32 || !preg_match('/^[0-9+()\\-\\s]{6,32}$/', $contactPhone))) Http::error('validation_error', 'رقم الهاتف غير صالح.', 422);
    if ($latitude !== null && ($latitude < -90 || $latitude > 90) || $longitude !== null && ($longitude < -180 || $longitude > 180)) Http::error('validation_error', 'إحداثيات الموقع غير صالحة.', 422);
    if (mb_strlen($title) < 2 || mb_strlen($title) > 180 || mb_strlen($description) < 2 || mb_strlen($description) > 10000) Http::error('validation_error', 'تحقق من عنوان الإعلان ووصفه.', 422);
    if (!in_array($kind, ['item', 'service'], true) || ($kind === 'item' && !in_array($condition, ['new', 'used'], true)) || ($kind === 'service' && $condition !== null && $condition !== '') || !in_array($fulfillment, ['pickup', 'delivery', 'both'], true)) Http::error('validation_error', 'نوع الإعلان أو حالته أو طريقة التسليم غير صالحة.', 422);
    if ($price !== null && $price !== '' && (!is_numeric($price) || (float)$price < 0)) Http::error('validation_error', 'السعر غير صالح.', 422);
    $price = ($price === null || $price === '') ? null : (float)$price; $currency = $price === null ? null : (string)$currency;
    if ($price !== null && !in_array($currency, ['USD', 'SYP'], true)) Http::error('validation_error', 'اختر عملة صحيحة للسعر.', 422);
    $category = $db->prepare("SELECT id FROM categories_marketplace WHERE id=? AND status='active' LIMIT 1"); $category->execute([$categoryId]); if (!$category->fetch()) Http::error('category_unavailable', 'القسم غير متاح حالياً.', 422);
    $linkType = (string)($input['link_type'] ?? ($existing['link_type'] ?? 'none')); $linkedProductId = null; $externalUrl = null;
    if ($linkType === 'product') { $linkedProductId = (int)($input['linked_product_id'] ?? 0); $linked = $db->prepare("SELECT id FROM products WHERE id=? AND status='active' LIMIT 1"); $linked->execute([$linkedProductId]); if ($linkedProductId < 1 || !$linked->fetch()) Http::error('linked_product_unavailable', 'المنتج المرتبط غير متاح.', 422); }
    elseif ($linkType === 'external') { $externalUrl = trim((string)($input['external_url'] ?? '')); $scheme = strtolower((string)parse_url($externalUrl, PHP_URL_SCHEME)); if (!filter_var($externalUrl, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true) || mb_strlen($externalUrl) > 2048) Http::error('validation_error', 'أدخل رابطاً خارجياً صحيحاً.', 422); }
    elseif ($linkType !== 'none') Http::error('validation_error', 'نوع الربط غير صالح.', 422);
    $db->prepare("UPDATE listings_marketplace SET category_id=?, title=?, description=?, listing_kind=?, item_condition=?, price=?, currency=?, is_negotiable=?, fulfillment_mode=?, location_text=?, latitude=?, longitude=?, service_available_from=?, service_available_to=?, contact_phone=?, link_type=?, linked_product_id=?, external_url=?, status='pending_review', published_at=NULL WHERE id=? AND seller_user_id=?")->execute([$categoryId, $title, $description, $kind, $kind === 'service' ? null : $condition, $price, $currency, $negotiable, $fulfillment, $location === '' ? null : $location, $latitude, $longitude, $kind === 'service' ? $serviceFrom : null, $kind === 'service' ? $serviceTo : null, $kind === 'service' ? $contactPhone : null, $linkType, $linkedProductId, $externalUrl, $listingId, $actorId]);
    Audit::log($db, $actorId, 'marketplace.listing.updated_by_owner', 'marketplace_listing', $listingId, null, ['category_id' => $categoryId, 'status' => 'pending_review', 'link_type' => $linkType]);
    Http::json(['data' => marketplaceMyListingRow($db, $listingId, $actorId)]);
}

if ($method === 'POST' && route('/marketplace/my/listings/{id}/stop', $path, $parameters)) {
    $actor = user($db); $listingId = (int)$parameters['id']; $listing = marketplaceOwnedListing($db, $listingId, (int)$actor['id']); if ($listing['status'] !== 'published') Http::error('listing_not_stoppable', 'يمكن إيقاف الإعلان المنشور فقط.', 409); $db->prepare("UPDATE listings_marketplace SET status='hidden' WHERE id=? AND seller_user_id=? AND status='published'")->execute([$listingId, $actor['id']]); Audit::log($db, (int)$actor['id'], 'marketplace.listing.stopped', 'marketplace_listing', $listingId); Http::json(['data' => marketplaceMyListingRow($db, $listingId, (int)$actor['id'])]);
}

if ($method === 'POST' && route('/marketplace/my/listings/{id}/reactivate', $path, $parameters)) {
    $actor = user($db); $listingId = (int)$parameters['id']; $listing = marketplaceOwnedListing($db, $listingId, (int)$actor['id']); if (!in_array($listing['status'], ['hidden','expired','rejected'], true)) Http::error('listing_not_reactivatable', 'لا يمكن إعادة تفعيل هذه الحالة.', 409); $db->prepare("UPDATE listings_marketplace SET status='pending_review', published_at=NULL WHERE id=? AND seller_user_id=?")->execute([$listingId, $actor['id']]); Audit::log($db, (int)$actor['id'], 'marketplace.listing.reactivation_requested', 'marketplace_listing', $listingId); Http::json(['data' => marketplaceMyListingRow($db, $listingId, (int)$actor['id'])]);
}

if ($method === 'POST' && route('/marketplace/my/listings/{id}/sold', $path, $parameters)) {
    $actor = user($db); $listingId = (int)$parameters['id']; $listing = marketplaceOwnedListing($db, $listingId, (int)$actor['id']); if (!in_array($listing['status'], ['published','reserved'], true)) Http::error('listing_not_sellable', 'لا يمكن تعليم هذه الحالة كمباعة.', 409); $db->prepare("UPDATE listings_marketplace SET status='sold' WHERE id=? AND seller_user_id=?")->execute([$listingId, $actor['id']]); Audit::log($db, (int)$actor['id'], 'marketplace.listing.marked_sold', 'marketplace_listing', $listingId); Http::json(['data' => marketplaceMyListingRow($db, $listingId, (int)$actor['id'])]);
}

if ($method === 'DELETE' && route('/marketplace/my/listings/{id}', $path, $parameters)) {
    $actor = user($db); $listingId = (int)$parameters['id']; $listing = marketplaceOwnedListing($db, $listingId, (int)$actor['id']); if ($listing['status'] === 'reserved') Http::error('listing_reserved', 'لا يمكن حذف إعلان محجوز قبل معالجة المعاملة.', 409); $active = $db->prepare("SELECT COUNT(*) FROM marketplace_transactions WHERE listing_id=? AND status IN ('pending','confirmed','waiting_payment','paid','waiting_delivery','delivering','disputed')"); $active->execute([$listingId]); if ((int)$active->fetchColumn() > 0) Http::error('listing_transaction_active', 'لا يمكن حذف إعلان له معاملة نشطة.', 409); $db->prepare("UPDATE listings_marketplace SET status='deleted' WHERE id=? AND seller_user_id=?")->execute([$listingId, $actor['id']]); Audit::log($db, (int)$actor['id'], 'marketplace.listing.deleted_by_owner', 'marketplace_listing', $listingId); Http::json(['data' => ['id' => $listingId, 'deleted' => true]]);
}

if ($method === 'GET' && $path === '/marketplace/screens') {
    $statement = $db->query("SELECT id, screen_key, title, display_order FROM marketplace_screens WHERE status='active' ORDER BY display_order, id"); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && $path === '/admin/marketplace/promotions') {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['listing_id', 'promotion_type', 'duration_days', 'screen_ids']);
    $listingId = (int)$input['listing_id']; $type = trim((string)$input['promotion_type']); $days = (int)$input['duration_days']; $screenIds = array_values(array_unique(array_map('intval', is_array($input['screen_ids']) ? $input['screen_ids'] : [])));
    if ($listingId < 1 || !in_array($type, ['featured','top_category','boost','highlight'], true) || $days < 1 || $days > 365 || $screenIds === []) Http::error('validation_error', 'اختر إعلاناً ونوع الترويج ومدة وشاشة واحدة على الأقل.', 422);
    $listing = $db->prepare("SELECT id FROM listings_marketplace WHERE id=? AND status IN ('published','reserved') LIMIT 1"); $listing->execute([$listingId]); if (!$listing->fetch()) Http::error('listing_unavailable', 'الإعلان غير متاح للترويج.', 422);
    $placeholders = implode(',', array_fill(0, count($screenIds), '?')); $screens = $db->prepare("SELECT id FROM marketplace_screens WHERE id IN ($placeholders) AND status='active'"); $screens->execute($screenIds); $validScreenIds = array_map('intval', array_column($screens->fetchAll(), 'id')); if (count($validScreenIds) !== count($screenIds)) Http::error('screen_unavailable', 'إحدى شاشات الحراج غير متاحة.', 422);
    $db->beginTransaction(); try {
        $insert = $db->prepare("INSERT INTO marketplace_promotions (listing_id, user_id, promotion_type, duration_days, start_at, end_at, status, note, reviewed_by_id, reviewed_at) VALUES (?, ?, ?, ?, NOW(), datetime('now', '+' || ? || ' days'), 'active', ?, ?, NOW())"); $insert->execute([$listingId, $actor['id'], $type, $days, $days, trim((string)($input['note'] ?? '')) ?: null, $actor['id']]); $promotionId = (int)$db->lastInsertId();
        $target = $db->prepare('INSERT INTO marketplace_promotion_screens (promotion_id, screen_id) VALUES (?, ?)'); foreach ($validScreenIds as $screenId) $target->execute([$promotionId, $screenId]); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Audit::log($db, (int)$actor['id'], 'marketplace.promotion.created_by_admin', 'marketplace_promotion', $promotionId, null, ['listing_id' => $listingId, 'screen_ids' => $validScreenIds, 'duration_days' => $days]); Http::json(['data' => ['id' => $promotionId, 'listing_id' => $listingId, 'status' => 'active', 'duration_days' => $days, 'screen_ids' => $validScreenIds]], 201);
}

if ($method === 'GET' && $path === '/marketplace/promotion-prices') {
    $type = trim((string)($_GET['promotion_type'] ?? '')); $sql = "SELECT id, promotion_type, duration_days, currency, price FROM marketplace_promotion_prices WHERE status='active'"; $params = [];
    if ($type !== '') { if (!in_array($type, ['featured','top_category','boost','highlight'], true)) Http::error('validation_error', 'نوع الترويج غير صالح.', 422); $sql .= ' AND promotion_type=?'; $params[] = $type; }
    $sql .= ' ORDER BY promotion_type, duration_days, currency'; $statement = $db->prepare($sql); $statement->execute($params); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'GET' && $path === '/marketplace/my/promotions') {
    $actor = user($db); marketplaceExpirePromotions($db); $statement = $db->prepare('SELECT promotion.id, promotion.listing_id, promotion.user_id, promotion.promotion_type, promotion.duration_days, promotion.start_at, promotion.end_at, promotion.payment_id, promotion.price_amount, promotion.price_currency, promotion.status, promotion.note, promotion.created_at, listing.title AS listing_title FROM marketplace_promotions promotion JOIN listings_marketplace listing ON listing.id=promotion.listing_id WHERE promotion.user_id=? ORDER BY promotion.created_at DESC, promotion.id DESC LIMIT 200'); $statement->execute([$actor['id']]); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && route('/marketplace/my/listings/{id}/promotion', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $listingId = (int)$parameters['id']; $listing = marketplaceOwnedListing($db, $listingId, $actorId); if ($listing['status'] !== 'published') Http::error('listing_not_promotable', 'يمكن طلب ترويج إعلان منشور فقط.', 409);
    $input = Http::input(); Http::requireFields($input, ['promotion_type','duration_days','currency']); $type = trim((string)$input['promotion_type']); $days = (int)$input['duration_days']; $currency = trim((string)$input['currency']); $paymentId = isset($input['payment_id']) && $input['payment_id'] !== '' ? (int)$input['payment_id'] : null; $note = trim((string)($input['note'] ?? ''));
    if (!in_array($type, ['featured','top_category','boost','highlight'], true) || $days < 1 || $days > 365 || !in_array($currency, ['USD','SYP'], true) || ($paymentId !== null && $paymentId < 1) || mb_strlen($note) > 500) Http::error('validation_error', 'بيانات الترويج غير صالحة.', 422);
    $price = $db->prepare("SELECT id, price FROM marketplace_promotion_prices WHERE promotion_type=? AND duration_days=? AND currency=? AND status='active' LIMIT 1"); $price->execute([$type, $days, $currency]); $price = $price->fetch(); if (!$price) Http::error('promotion_price_unavailable', 'لا توجد تسعيرة مفعّلة لهذا النوع والمدة والعملة.', 409);
    marketplaceExpirePromotions($db); $existing = $db->prepare("SELECT id FROM marketplace_promotions WHERE listing_id=? AND promotion_type=? AND status IN ('pending','active') LIMIT 1"); $existing->execute([$listingId, $type]); if ($existing->fetch()) Http::error('promotion_exists', 'يوجد ترويج نشط أو قيد المراجعة من هذا النوع للإعلان.', 409);
    $db->prepare('INSERT INTO marketplace_promotions (listing_id, user_id, promotion_type, duration_days, payment_id, price_id, price_amount, price_currency, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([$listingId, $actorId, $type, $days, $paymentId, $price['id'], $price['price'], $currency, $note === '' ? null : $note]); $promotionId = (int)$db->lastInsertId(); Audit::log($db, $actorId, 'marketplace.promotion.requested', 'marketplace_promotion', $promotionId, null, ['listing_id' => $listingId, 'promotion_type' => $type, 'duration_days' => $days]); Http::json(['data' => ['id' => $promotionId, 'listing_id' => $listingId, 'type' => $type, 'status' => 'pending', 'price' => $price['price'], 'currency' => $currency]], 201);
}

if ($method === 'GET' && $path === '/admin/marketplace/promotion-prices') {
    $actor = user($db); role($actor, ['admin']); $statement = $db->query('SELECT id, promotion_type, duration_days, currency, price, status, created_at, updated_at FROM marketplace_promotion_prices ORDER BY promotion_type, duration_days, currency'); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && $path === '/admin/marketplace/promotion-prices') {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['promotion_type','duration_days','currency','price']); $type = trim((string)$input['promotion_type']); $days = (int)$input['duration_days']; $currency = trim((string)$input['currency']); $price = $input['price']; $status = trim((string)($input['status'] ?? 'active'));
    if (!in_array($type, ['featured','top_category','boost','highlight'], true) || $days < 1 || $days > 365 || !in_array($currency, ['USD','SYP'], true) || !is_numeric($price) || (float)$price < 0 || !in_array($status, ['active','inactive'], true)) Http::error('validation_error', 'بيانات تسعيرة الترويج غير صالحة.', 422);
    $db->prepare('INSERT INTO marketplace_promotion_prices (promotion_type, duration_days, currency, price, status) VALUES (?, ?, ?, ?, ?)')->execute([$type, $days, $currency, $price, $status]); $priceId = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'marketplace.promotion_price.created', 'marketplace_promotion_price', $priceId); Http::json(['data' => ['id' => $priceId]], 201);
}

if ($method === 'PATCH' && route('/admin/marketplace/promotion-prices/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $priceId = (int)$parameters['id']; $input = Http::input(); $price = $input['price'] ?? null; $status = isset($input['status']) ? trim((string)$input['status']) : null;
    if ($price === null && $status === null) Http::error('validation_error', 'أدخل سعراً أو حالة جديدة.', 422); if ($price !== null && (!is_numeric($price) || (float)$price < 0)) Http::error('validation_error', 'سعر الترويج غير صالح.', 422); if ($status !== null && !in_array($status, ['active','inactive'], true)) Http::error('validation_error', 'حالة التسعيرة غير صالحة.', 422);
    $statement = $db->prepare('UPDATE marketplace_promotion_prices SET price=COALESCE(?, price), status=COALESCE(?, status) WHERE id=?'); $statement->execute([$price, $status, $priceId]); if ($statement->rowCount() === 0) { $check = $db->prepare('SELECT id FROM marketplace_promotion_prices WHERE id=?'); $check->execute([$priceId]); if (!$check->fetch()) Http::error('not_found', 'تسعيرة الترويج غير موجودة.', 404); } Audit::log($db, (int)$actor['id'], 'marketplace.promotion_price.updated', 'marketplace_promotion_price', $priceId); Http::json(['data' => ['id' => $priceId, 'updated' => true]]);
}

if ($method === 'GET' && $path === '/admin/marketplace/promotions') {
    $actor = user($db); role($actor, ['admin']); marketplaceExpirePromotions($db); $status = trim((string)($_GET['status'] ?? 'pending')); if ($status !== '' && !in_array($status, ['pending','active','expired','cancelled','rejected'], true)) Http::error('validation_error', 'حالة الترويج غير صالحة.', 422); $sql = 'SELECT promotion.id, promotion.listing_id, promotion.user_id, promotion.promotion_type, promotion.duration_days, promotion.start_at, promotion.end_at, promotion.payment_id, promotion.price_amount, promotion.price_currency, promotion.status, promotion.note, promotion.created_at, listing.title AS listing_title, user.full_name AS user_name FROM marketplace_promotions promotion JOIN listings_marketplace listing ON listing.id=promotion.listing_id JOIN users user ON user.id=promotion.user_id'; $params = []; if ($status !== '') { $sql .= ' WHERE promotion.status=?'; $params[] = $status; } $sql .= ' ORDER BY promotion.created_at ASC, promotion.id ASC LIMIT 200'; $statement = $db->prepare($sql); $statement->execute($params); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'PATCH' && route('/admin/marketplace/promotions/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $promotionId = (int)$parameters['id']; $input = Http::input(); Http::requireFields($input, ['status']); $status = trim((string)$input['status']); $paymentId = isset($input['payment_id']) && $input['payment_id'] !== '' ? (int)$input['payment_id'] : null;
    if (!in_array($status, ['active','cancelled','rejected'], true) || ($paymentId !== null && $paymentId < 1)) Http::error('validation_error', 'قرار الترويج غير صالح.', 422); $statement = $db->prepare('SELECT id, status, duration_days FROM marketplace_promotions WHERE id=? LIMIT 1'); $statement->execute([$promotionId]); $promotion = $statement->fetch(); if (!$promotion) Http::error('not_found', 'طلب الترويج غير موجود.', 404); if ($promotion['status'] !== 'pending') Http::error('promotion_not_pending', 'لا يمكن معالجة طلب ترويج غير معلق.', 409);
    if ($status === 'active') $db->prepare("UPDATE marketplace_promotions SET status=?, payment_id=COALESCE(?, payment_id), start_at=NOW(), end_at=datetime('now', '+' || ? || ' days'), reviewed_by_id=?, reviewed_at=NOW() WHERE id=?")->execute([$status, $paymentId, $promotion['duration_days'], $actor['id'], $promotionId]); else $db->prepare('UPDATE marketplace_promotions SET status=?, payment_id=COALESCE(?, payment_id), reviewed_by_id=?, reviewed_at=NOW() WHERE id=?')->execute([$status, $paymentId, $actor['id'], $promotionId]); Audit::log($db, (int)$actor['id'], 'marketplace.promotion.reviewed', 'marketplace_promotion', $promotionId, ['status' => $promotion['status']], ['status' => $status]); Http::json(['data' => ['id' => $promotionId, 'status' => $status, 'listing_action' => 'none']]);
}

if ($method === 'GET' && $path === '/marketplace/listings') {
    marketplaceExpirePromotions($db); $page = Http::queryInt('page', 1, 1, 1000000); $perPage = Http::queryInt('per_page', 20, 1, 40); $offset = ($page - 1) * $perPage;
    $q = trim((string)($_GET['q'] ?? '')); $sort = (string)($_GET['sort'] ?? 'relevant');
    $where = ["l.status='published'"]; $parameters = [];
    if ($q !== '') {
        if (mb_strlen($q) > 160) Http::error('validation_error', 'عبارة البحث طويلة جداً.', 422);
        $like = '%' . $q . '%'; $where[] = '(l.title LIKE ? OR l.description LIKE ? OR c.name LIKE ?)'; array_push($parameters, $like, $like, $like);
    }
    $categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
    if ($categoryId) {
        $categoryIds = marketplaceDescendantCategoryIds($db, $categoryId); $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $where[] = "l.category_id IN ({$placeholders})"; array_push($parameters, ...$categoryIds);
    }
    foreach (['country_id' => 'country_location_id', 'city_id' => 'city_location_id', 'area_id' => 'area_location_id'] as $queryKey => $column) {
        $id = filter_input(INPUT_GET, $queryKey, FILTER_VALIDATE_INT); if ($id) { $where[] = "l.{$column}=?"; $parameters[] = $id; }
    }
    $minPrice = filter_input(INPUT_GET, 'min_price', FILTER_VALIDATE_FLOAT); if ($minPrice !== false && $minPrice !== null) { if ($minPrice < 0) Http::error('validation_error', 'السعر الأدنى غير صالح.', 422); $where[] = 'l.price>=?'; $parameters[] = $minPrice; }
    $maxPrice = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT); if ($maxPrice !== false && $maxPrice !== null) { if ($maxPrice < 0) Http::error('validation_error', 'السعر الأعلى غير صالح.', 422); $where[] = 'l.price<=?'; $parameters[] = $maxPrice; }
    if ($minPrice !== false && $minPrice !== null && $maxPrice !== false && $maxPrice !== null && $minPrice > $maxPrice) Http::error('validation_error', 'السعر الأدنى يجب ألا يتجاوز السعر الأعلى.', 422);
    $currency = (string)($_GET['currency'] ?? ''); if ($currency !== '') { if (!in_array($currency, ['USD','SYP'], true)) Http::error('validation_error', 'العملة غير صالحة.', 422); $where[] = 'l.currency=?'; $parameters[] = $currency; }
    $condition = (string)($_GET['condition'] ?? ''); if ($condition !== '') { if (!in_array($condition, ['new','used'], true)) Http::error('validation_error', 'حالة المنتج غير صالحة.', 422); $where[] = 'l.item_condition=?'; $parameters[] = $condition; }
    $listingKind = (string)($_GET['listing_kind'] ?? ''); if ($listingKind !== '') { if (!in_array($listingKind, ['item','service'], true)) Http::error('validation_error', 'نوع الإعلان غير صالح.', 422); $where[] = 'l.listing_kind=?'; $parameters[] = $listingKind; }
    if (($_GET['negotiable'] ?? '') === '1') $where[] = 'l.is_negotiable=1';
    if (($_GET['delivery_available'] ?? '') === '1') $where[] = "l.fulfillment_mode IN ('delivery','both')";
    if (($_GET['verified_seller'] ?? '') === '1') $where[] = "EXISTS (SELECT 1 FROM verification_requests vr WHERE vr.user_id=l.seller_user_id AND vr.status='approved' AND vr.is_active=1)";
    $sellerType = (string)($_GET['seller_type'] ?? ''); if ($sellerType === 'store') $where[] = 'l.store_id IS NOT NULL'; elseif ($sellerType === 'individual') $where[] = 'l.store_id IS NULL'; elseif ($sellerType !== '') Http::error('validation_error', 'نوع البائع غير صالح.', 422);
    $promotionOrder = "CASE WHEN EXISTS (SELECT 1 FROM marketplace_promotions promotion_boost WHERE promotion_boost.listing_id=l.id AND promotion_boost.promotion_type='boost' AND promotion_boost.status='active' AND promotion_boost.end_at>NOW()) THEN 0 WHEN EXISTS (SELECT 1 FROM marketplace_promotions promotion_top WHERE promotion_top.listing_id=l.id AND promotion_top.promotion_type='top_category' AND promotion_top.status='active' AND promotion_top.end_at>NOW()) THEN 1 ELSE 2 END";
    $orders = [
        'latest' => $promotionOrder . ', l.published_at DESC, l.id DESC',
        'oldest' => 'l.published_at ASC, l.id ASC',
        'price_asc' => 'l.price IS NULL ASC, l.price ASC, l.id DESC',
        'price_desc' => 'l.price IS NULL ASC, l.price DESC, l.id DESC',
        'views' => 'l.view_count DESC, l.published_at DESC, l.id DESC',
    ];
    $order = $orders[$sort] ?? null;
    if ($sort === 'relevant') {
        if ($q !== '') { $like = '%' . $q . '%'; $order = $promotionOrder . ', CASE WHEN l.title LIKE ? THEN 0 WHEN c.name LIKE ? THEN 1 WHEN l.description LIKE ? THEN 2 ELSE 3 END, l.published_at DESC, l.id DESC'; array_push($parameters, $like, $like, $like); }
        else $order = $orders['latest'];
    } elseif ($sort === 'closest') {
        $latitude = filter_input(INPUT_GET, 'latitude', FILTER_VALIDATE_FLOAT); $longitude = filter_input(INPUT_GET, 'longitude', FILTER_VALIDATE_FLOAT);
        if ($latitude === false || $latitude === null || $longitude === false || $longitude === null || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) Http::error('location_required', 'حدّد موقعك لفرز النتائج بالأقرب.', 422);
        $where[] = 'l.latitude IS NOT NULL AND l.longitude IS NOT NULL';
        $order = '6371 * ACOS(LEAST(1, GREATEST(-1, COS(RADIANS(?)) * COS(RADIANS(l.latitude)) * COS(RADIANS(l.longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(l.latitude))))) ASC, l.published_at DESC';
        array_push($parameters, $latitude, $longitude, $latitude);
    } elseif ($order === null) Http::error('validation_error', 'طريقة الترتيب غير صالحة.', 422);
    $sql = marketplaceListingSelect() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $order . ' LIMIT ? OFFSET ?';
    $statement = $db->prepare($sql); foreach ($parameters as $index => $value) $statement->bindValue($index + 1, $value); $statement->bindValue(count($parameters) + 1, $perPage, PDO::PARAM_INT); $statement->bindValue(count($parameters) + 2, $offset, PDO::PARAM_INT); $statement->execute();
    $rows = $statement->fetchAll();
    Http::json(['data' => $rows, 'meta' => ['page' => $page, 'per_page' => $perPage, 'has_more' => count($rows) === $perPage, 'sort' => $sort]]);
}

if ($method === 'POST' && route('/marketplace/listings/{id}/view', $path, $parameters)) {
    $listingId = (int)$parameters['id'];
    $statement = $db->prepare("UPDATE listings_marketplace SET view_count=COALESCE(view_count, 0) + 1 WHERE id=? AND status IN ('published','reserved')");
    $statement->execute([$listingId]);
    if ($statement->rowCount() !== 1) Http::error('not_found', 'الإعلان غير متاح للمشاهدة.', 404);
    $current = $db->prepare('SELECT id, view_count FROM listings_marketplace WHERE id=? LIMIT 1');
    $current->execute([$listingId]);
    Http::json(['data' => $current->fetch() ?: ['id' => $listingId, 'view_count' => 0]]);
}

if ($method === 'POST' && route('/marketplace/listings/{id}/favorite', $path, $parameters)) {
    $actor = user($db); $listingId = (int)$parameters['id'];
    $listing = $db->prepare("SELECT l.id, l.title, l.price, l.currency, (SELECT file_path FROM listing_images_marketplace li WHERE li.listing_id=l.id ORDER BY li.sort_order, li.id LIMIT 1) AS image_path FROM listings_marketplace l WHERE l.id=? AND l.status='published' LIMIT 1");
    $listing->execute([$listingId]); $row = $listing->fetch();
    if (!$row) Http::error('not_found', 'الإعلان غير متاح للحفظ.', 404);
    $insert = $db->prepare('INSERT INTO favorites_marketplace (user_id, listing_id, listing_title_snapshot, image_path_snapshot, price_snapshot, currency_snapshot, last_known_price) VALUES (?, ?, ?, ?, ?, ?, ?) ON CONFLICT(user_id, listing_id) DO UPDATE SET updated_at=CURRENT_TIMESTAMP');
    $insert->execute([$actor['id'], $listingId, $row['title'], $row['image_path'], $row['price'], $row['currency'], $row['price']]);
    $count = $db->prepare('SELECT COUNT(*) FROM favorites_marketplace WHERE user_id=?'); $count->execute([$actor['id']]);
    Http::json(['data' => ['listing_id' => $listingId, 'favorite' => true, 'count' => (int)$count->fetchColumn()]], 201);
}

if ($method === 'DELETE' && route('/marketplace/listings/{id}/favorite', $path, $parameters)) {
    $actor = user($db); $statement = $db->prepare('DELETE FROM favorites_marketplace WHERE user_id=? AND listing_id=?');
    $statement->execute([$actor['id'], (int)$parameters['id']]);
    $count = $db->prepare('SELECT COUNT(*) FROM favorites_marketplace WHERE user_id=?'); $count->execute([$actor['id']]);
    Http::json(['data' => ['listing_id' => (int)$parameters['id'], 'favorite' => false, 'count' => (int)$count->fetchColumn()]]);
}

if ($method === 'DELETE' && route('/marketplace/favorites/{id}', $path, $parameters)) {
    $actor = user($db); $statement = $db->prepare('DELETE FROM favorites_marketplace WHERE id=? AND user_id=?');
    $statement->execute([(int)$parameters['id'], $actor['id']]);
    $count = $db->prepare('SELECT COUNT(*) FROM favorites_marketplace WHERE user_id=?'); $count->execute([$actor['id']]);
    Http::json(['data' => ['id' => (int)$parameters['id'], 'deleted' => $statement->rowCount() > 0, 'count' => (int)$count->fetchColumn()]]);
}

if ($method === 'GET' && $path === '/marketplace/favorites/summary') {
    $actor = user($db); $statement = $db->prepare('SELECT COUNT(*) AS count FROM favorites_marketplace WHERE user_id=?'); $statement->execute([$actor['id']]);
    $ids = $db->prepare('SELECT listing_id FROM favorites_marketplace WHERE user_id=? AND listing_id IS NOT NULL ORDER BY created_at DESC LIMIT 500'); $ids->execute([$actor['id']]);
    Http::json(['data' => ['count' => (int)$statement->fetchColumn(), 'listing_ids' => array_map('intval', array_column($ids->fetchAll(), 'listing_id'))]]);
}

if ($method === 'GET' && $path === '/marketplace/favorites') {
    $actor = user($db); $page = Http::queryInt('page', 1, 1, 1000000); $perPage = Http::queryInt('per_page', 20, 1, 40); $offset = ($page - 1) * $perPage;
    $statement = $db->prepare("SELECT f.id AS favorite_id, f.listing_id, COALESCE(l.title, f.listing_title_snapshot) AS title, COALESCE((SELECT file_path FROM listing_images_marketplace li WHERE li.listing_id=l.id ORDER BY li.sort_order, li.id LIMIT 1), f.image_path_snapshot) AS image_path, COALESCE(l.price, f.price_snapshot) AS price, COALESCE(l.currency, f.currency_snapshot) AS currency, l.listing_kind, l.item_condition, l.is_negotiable, l.is_featured, l.fulfillment_mode, l.location_text, l.status AS listing_status, l.view_count, l.published_at, c.name AS category_name, s.name AS store_name, seller.full_name AS seller_name, CASE WHEN l.id IS NULL OR l.status <> 'published' THEN 1 ELSE 0 END AS unavailable FROM favorites_marketplace f LEFT JOIN listings_marketplace l ON l.id=f.listing_id LEFT JOIN categories_marketplace c ON c.id=l.category_id LEFT JOIN users seller ON seller.id=l.seller_user_id LEFT JOIN stores s ON s.id=l.store_id WHERE f.user_id=? ORDER BY unavailable ASC, f.updated_at DESC, f.id DESC LIMIT ? OFFSET ?");
    $statement->bindValue(1, $actor['id'], PDO::PARAM_INT); $statement->bindValue(2, $perPage, PDO::PARAM_INT); $statement->bindValue(3, $offset, PDO::PARAM_INT); $statement->execute(); $rows = $statement->fetchAll();
    Http::json(['data' => $rows, 'meta' => ['page' => $page, 'per_page' => $perPage, 'has_more' => count($rows) === $perPage]]);
}

if ($method === 'GET' && route('/marketplace/listings/{id}/comments', $path, $parameters)) {
    ensureMarketplaceSocialTables($db); $listingId = (int)$parameters['id']; $page = Http::queryInt('page', 1, 1, 1000000); $perPage = Http::queryInt('per_page', 20, 1, 50); $offset = ($page - 1) * $perPage;
    $check = $db->prepare('SELECT id FROM listings_marketplace WHERE id=?'); $check->execute([$listingId]); if (!$check->fetchColumn()) Http::error('not_found', 'الإعلان غير موجود.', 404);
    $statement = $db->prepare("SELECT c.id, c.listing_id, c.user_id, c.parent_id, c.body, c.created_at, u.full_name AS user_name, u.avatar_path, (u.role='admin') AS is_admin, EXISTS(SELECT 1 FROM verification_requests vr WHERE vr.user_id=u.id AND vr.subject_role=u.role AND vr.status='approved' AND vr.is_active=1) AS is_verified FROM marketplace_comments c JOIN users u ON u.id=c.user_id WHERE c.listing_id=? AND c.status='visible' ORDER BY c.created_at ASC, c.id ASC LIMIT ? OFFSET ?");
    $statement->bindValue(1, $listingId, PDO::PARAM_INT); $statement->bindValue(2, $perPage, PDO::PARAM_INT); $statement->bindValue(3, $offset, PDO::PARAM_INT); $statement->execute(); $rows = $statement->fetchAll(); Http::json(['data' => $rows, 'meta' => ['page' => $page, 'per_page' => $perPage, 'has_more' => count($rows) === $perPage]]);
}
if ($method === 'POST' && route('/marketplace/listings/{id}/comments', $path, $parameters)) {
    ensureMarketplaceSocialTables($db); $actor = user($db); $listingId = (int)$parameters['id']; $input = Http::input(); Http::requireFields($input, ['body']); $body = trim((string)$input['body']); if ($body === '' || mb_strlen($body) > 2000) Http::error('validation_error', 'نص التعليق مطلوب وبحد أقصى 2000 محرف.', 422);
    $check = $db->prepare('SELECT seller_user_id FROM listings_marketplace WHERE id=? AND status NOT IN (\'deleted\',\'hidden\')'); $check->execute([$listingId]); if (!$check->fetch()) Http::error('not_found', 'الإعلان غير موجود أو غير متاح.', 404);
    $parentId = !empty($input['parent_id']) ? (int)$input['parent_id'] : null; if ($parentId !== null) { $parent = $db->prepare('SELECT id FROM marketplace_comments WHERE id=? AND listing_id=? AND status=\'visible\''); $parent->execute([$parentId, $listingId]); if (!$parent->fetchColumn()) Http::error('validation_error', 'التعليق الأب غير صالح.', 422); }
    $statement = $db->prepare('INSERT INTO marketplace_comments (listing_id,user_id,parent_id,body) VALUES (?,?,?,?)'); $statement->execute([$listingId, $actor['id'], $parentId, $body]); Http::json(['data' => ['id' => (int)$db->lastInsertId(), 'listing_id' => $listingId, 'user_id' => (int)$actor['id'], 'parent_id' => $parentId, 'body' => $body, 'user_name' => $actor['full_name'], 'is_admin' => $actor['role'] === 'admin' ? 1 : 0, 'created_at' => date('Y-m-d H:i:s')]], 201);
}
if ($method === 'DELETE' && route('/marketplace/comments/{id}', $path, $parameters)) {
    ensureMarketplaceSocialTables($db); $actor = user($db); role($actor, ['customer', 'merchant', 'courier', 'admin']);
    $commentId = (int)$parameters['id'];
    $statement = $actor['role'] === 'admin'
        ? $db->prepare("UPDATE marketplace_comments SET status='deleted', body='' WHERE id=? AND status<>'deleted'")
        : $db->prepare("UPDATE marketplace_comments SET status='deleted', body='' WHERE id=? AND user_id=? AND status<>'deleted'");
    $statement->execute($actor['role'] === 'admin' ? [$commentId] : [$commentId, $actor['id']]);
    if ($statement->rowCount() < 1) Http::error('not_found', 'التعليق غير موجود أو محذوف مسبقاً.', 404);
    Audit::log($db, (int)$actor['id'], 'marketplace.comment.deleted', 'marketplace_comment', $commentId);
    Http::json(['data' => ['deleted' => true, 'id' => $commentId]]);
}
if ($method === 'GET' && route('/marketplace/users/{id}/profile', $path, $parameters)) {
    ensureMarketplaceSocialTables($db); $userId = (int)$parameters['id']; $actorId = null; try { $actorId = (int)(user($db)['id']); } catch (Throwable $ignored) {}
    $statement = $db->prepare("SELECT u.id, u.full_name, u.avatar_path, u.role, u.created_at, (SELECT COUNT(*) FROM marketplace_follows f WHERE f.followed_id=u.id) AS followers_count, (SELECT COUNT(*) FROM marketplace_follows f WHERE f.follower_id=u.id) AS following_count, (SELECT COUNT(*) FROM listings_marketplace l WHERE l.seller_user_id=u.id AND l.status='published') AS listings_count, CASE WHEN ? IS NULL THEN 0 ELSE EXISTS(SELECT 1 FROM marketplace_follows mf WHERE mf.follower_id=? AND mf.followed_id=u.id) END AS is_following, EXISTS(SELECT 1 FROM verification_requests vr WHERE vr.user_id=u.id AND vr.subject_role=u.role AND vr.status='approved' AND vr.is_active=1) AS is_verified FROM users u WHERE u.id=? AND u.status='active'"); $statement->execute([$actorId ?: null, $actorId ?: null, $userId]); $profile = $statement->fetch(); if (!$profile) Http::error('not_found', 'المستخدم غير موجود.', 404);
    $listings = $db->prepare("SELECT l.id,l.title,l.price,l.currency,l.location_text,l.status,l.published_at,(SELECT li.file_path FROM listing_images_marketplace li WHERE li.listing_id=l.id ORDER BY li.sort_order,li.id LIMIT 1) AS image_path,c.name AS category_name FROM listings_marketplace l LEFT JOIN categories_marketplace c ON c.id=l.category_id WHERE l.seller_user_id=? AND l.status='published' ORDER BY l.published_at DESC,l.id DESC LIMIT 60"); $listings->execute([$userId]); $profile['listings'] = $listings->fetchAll(); Http::json(['data' => $profile]);
}
if (($method === 'POST' || $method === 'DELETE') && route('/marketplace/users/{id}/follow', $path, $parameters)) {
    ensureMarketplaceSocialTables($db); $actor = user($db); $followedId = (int)$parameters['id']; if ((int)$actor['id'] === $followedId) Http::error('validation_error', 'لا يمكنك متابعة حسابك.', 422); $check = $db->prepare('SELECT id FROM users WHERE id=? AND status=\'active\''); $check->execute([$followedId]); if (!$check->fetchColumn()) Http::error('not_found', 'المستخدم غير موجود.', 404);
    if ($method === 'POST') { $statement = $db->prepare('INSERT OR IGNORE INTO marketplace_follows (follower_id,followed_id) VALUES (?,?)'); $statement->execute([$actor['id'], $followedId]); } else { $statement = $db->prepare('DELETE FROM marketplace_follows WHERE follower_id=? AND followed_id=?'); $statement->execute([$actor['id'], $followedId]); }
    $count = $db->prepare('SELECT COUNT(*) FROM marketplace_follows WHERE followed_id=?'); $count->execute([$followedId]); Http::json(['data' => ['followed_id' => $followedId, 'is_following' => $method === 'POST', 'followers_count' => (int)$count->fetchColumn()]]);
}
if ($method === 'GET' && $path === '/marketplace/suggested-listings') {
    ensureMarketplaceSocialTables($db); $actor = user($db); $page = Http::queryInt('page', 1, 1, 1000000); $perPage = Http::queryInt('per_page', 20, 1, 40); $offset = ($page - 1) * $perPage;
    $statement = $db->prepare("SELECT l.id,l.title,l.price,l.currency,l.listing_kind,l.is_negotiable,l.fulfillment_mode,l.location_text,l.status,l.published_at,l.view_count,l.seller_user_id,u.full_name AS seller_name,u.avatar_path AS seller_avatar, c.name AS category_name,(SELECT li.file_path FROM listing_images_marketplace li WHERE li.listing_id=l.id ORDER BY li.sort_order,li.id LIMIT 1) AS image_path FROM listings_marketplace l JOIN users u ON u.id=l.seller_user_id LEFT JOIN categories_marketplace c ON c.id=l.category_id WHERE l.status='published' AND l.seller_user_id IN (SELECT followed_id FROM marketplace_follows WHERE follower_id=?) ORDER BY l.published_at DESC,l.id DESC LIMIT ? OFFSET ?"); $statement->bindValue(1, $actor['id'], PDO::PARAM_INT); $statement->bindValue(2, $perPage, PDO::PARAM_INT); $statement->bindValue(3, $offset, PDO::PARAM_INT); $statement->execute(); $rows = $statement->fetchAll(); Http::json(['data' => $rows, 'meta' => ['page' => $page, 'per_page' => $perPage, 'has_more' => count($rows) === $perPage]]);
}
if ($method === 'GET' && $path === '/marketplace/alert-rules') {
    $actor = user($db); $statement = $db->prepare('SELECT id, rule_type, filters_json, status, created_at, updated_at FROM marketplace_alert_rules WHERE user_id=? ORDER BY created_at DESC, id DESC'); $statement->execute([$actor['id']]);
    $rules = $statement->fetchAll(); foreach ($rules as &$rule) $rule['filters'] = json_decode((string)($rule['filters_json'] ?? '{}'), true) ?: []; unset($rule);
    Http::json(['data' => $rules]);
}

if ($method === 'POST' && $path === '/marketplace/alert-rules') {
    $actor = user($db); $input = Http::input(); Http::requireFields($input, ['rule_type']);
    if (!in_array($input['rule_type'], ['new_listing','price_drop'], true)) Http::error('validation_error', 'نوع التنبيه غير صالح.', 422);
    $filters = marketplaceAlertFilters(is_array($input['filters'] ?? null) ? $input['filters'] : []);
    $statement = $db->prepare('INSERT INTO marketplace_alert_rules (user_id, rule_type, filters_json) VALUES (?, ?, ?)'); $statement->execute([$actor['id'], $input['rule_type'], json_encode($filters, JSON_UNESCAPED_UNICODE)]);
    Http::json(['data' => ['id' => (int)$db->lastInsertId()]], 201);
}

if ($method === 'PATCH' && route('/marketplace/alert-rules/{id}', $path, $parameters)) {
    $actor = user($db); $input = Http::input(); $ruleId = (int)$parameters['id'];
    $existing = $db->prepare('SELECT id FROM marketplace_alert_rules WHERE id=? AND user_id=?'); $existing->execute([$ruleId, $actor['id']]); if (!$existing->fetch()) Http::error('not_found', 'قاعدة التنبيه غير موجودة.', 404);
    $fields = []; $values = [];
    if (array_key_exists('filters', $input)) { $fields[] = 'filters_json=?'; $values[] = json_encode(marketplaceAlertFilters(is_array($input['filters']) ? $input['filters'] : []), JSON_UNESCAPED_UNICODE); }
    if (array_key_exists('status', $input)) { if (!in_array($input['status'], ['active','paused'], true)) Http::error('validation_error', 'حالة التنبيه غير صالحة.', 422); $fields[] = 'status=?'; $values[] = $input['status']; }
    if (!$fields) Http::error('validation_error', 'لا توجد تعديلات للحفظ.', 422);
    $values[] = $ruleId; $values[] = $actor['id']; $statement = $db->prepare('UPDATE marketplace_alert_rules SET ' . implode(',', $fields) . ' WHERE id=? AND user_id=?'); $statement->execute($values); Http::json(['data' => ['id' => $ruleId]]);
}

if ($method === 'DELETE' && route('/marketplace/alert-rules/{id}', $path, $parameters)) {
    $actor = user($db); $statement = $db->prepare('DELETE FROM marketplace_alert_rules WHERE id=? AND user_id=?'); $statement->execute([(int)$parameters['id'], $actor['id']]); Http::json(['data' => ['deleted' => $statement->rowCount() > 0]]);
}

if ($method === 'GET' && $path === '/marketplace/alerts/sync') {
    $actor = user($db); marketplaceSyncAlertEvents($db, (int)$actor['id']);
    $statement = $db->prepare('SELECT id, listing_id, rule_id, event_type, event_key, payload_json, occurred_at FROM marketplace_alert_events WHERE user_id=? AND delivered_at IS NULL ORDER BY occurred_at ASC, id ASC LIMIT 50'); $statement->execute([$actor['id']]); $events = $statement->fetchAll();
    foreach ($events as &$event) { $event['payload'] = json_decode((string)$event['payload_json'], true) ?: []; $priceDrop = $event['event_type'] === 'price_drop'; marketplaceNotify($db, (int)$actor['id'], $priceDrop ? 'price_drop' : 'new_listing_match', $priceDrop ? 'انخفض سعر إعلان محفوظ' : 'إعلان جديد مطابق', $priceDrop ? 'انخفض سعر إعلان محفوظ في المفضلة.' : 'ظهر إعلان جديد يطابق تنبيه الحراج الخاص بك.', 'alert_center:' . $event['event_key'], ['listing_id' => $event['listing_id'] === null ? null : (int)$event['listing_id']], $event['payload']); } unset($event);
    Http::json(['data' => $events]);
}

if ($method === 'POST' && route('/marketplace/alerts/{id}/ack', $path, $parameters)) {
    $actor = user($db); $statement = $db->prepare('UPDATE marketplace_alert_events SET delivered_at=COALESCE(delivered_at, NOW()) WHERE id=? AND user_id=?'); $statement->execute([(int)$parameters['id'], $actor['id']]); Http::json(['data' => ['id' => (int)$parameters['id'], 'acknowledged' => $statement->rowCount() > 0]]);
}

if ($method === 'GET' && $path === '/marketplace/chats') {
    $actor = user($db); $actorId = (int)$actor['id'];
    $statement = $db->prepare('SELECT * FROM marketplace_conversations WHERE buyer_id=? OR seller_id=? ORDER BY COALESCE(last_message_at, created_at) DESC, id DESC LIMIT 100');
    $statement->execute([$actorId, $actorId]);
    $conversations = array_map(static fn(array $row): array => marketplaceChatPublicConversation($db, $row, $actorId), $statement->fetchAll());
    $unread = $db->prepare('SELECT COUNT(*) FROM marketplace_messages m JOIN marketplace_conversations c ON c.id=m.conversation_id WHERE (c.buyer_id=? OR c.seller_id=?) AND m.sender_id<>? AND m.read_at IS NULL');
    $unread->execute([$actorId, $actorId, $actorId]);
    Http::json(['data' => $conversations, 'meta' => ['unread_count' => (int)$unread->fetchColumn()]]);
}

if ($method === 'GET' && $path === '/marketplace/chats/summary') {
    $actor = user($db); $actorId = (int)$actor['id'];
    $unread = $db->prepare('SELECT COUNT(*) FROM marketplace_messages m JOIN marketplace_conversations c ON c.id=m.conversation_id WHERE (c.buyer_id=? OR c.seller_id=?) AND m.sender_id<>? AND m.read_at IS NULL');
    $unread->execute([$actorId, $actorId, $actorId]);
    Http::json(['data' => ['unread_count' => (int)$unread->fetchColumn()]]);
}

if ($method === 'POST' && $path === '/marketplace/chats') {
    $actor = user($db); $actorId = (int)$actor['id']; $input = Http::input(); Http::requireFields($input, ['listing_id']); $listingId = (int)$input['listing_id'];
    $listing = $db->prepare("SELECT l.id, l.seller_user_id, l.title, l.price, l.currency, (SELECT file_path FROM listing_images_marketplace li WHERE li.listing_id=l.id ORDER BY li.sort_order, li.id LIMIT 1) AS image_path FROM listings_marketplace l WHERE l.id=? AND l.status IN ('published','reserved') LIMIT 1");
    $listing->execute([$listingId]); $listing = $listing->fetch();
    if (!$listing) Http::error('not_found', 'الإعلان غير متاح لبدء محادثة.', 404);
    $sellerId = (int)$listing['seller_user_id'];
    if ($sellerId === $actorId) Http::error('self_chat', 'لا يمكنك بدء محادثة مع نفسك.', 422);
    if (marketplaceChatBlocked($db, $actorId, $sellerId)) Http::error('chat_blocked', 'لا يمكن بدء المحادثة لأن أحد الطرفين حظر الآخر.', 403);
    $convCheck = $db->prepare('SELECT id FROM marketplace_conversations WHERE listing_id=? AND buyer_id=? LIMIT 1');
    $convCheck->execute([$listingId, $actorId]);
    $conversationId = (int)$convCheck->fetchColumn();
    if (!$conversationId) {
        $db->prepare('INSERT INTO marketplace_conversations (listing_id, buyer_id, seller_id, listing_title_snapshot, listing_price_snapshot, listing_currency_snapshot, listing_image_path_snapshot) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$listingId, $actorId, $sellerId, $listing['title'], $listing['price'], $listing['currency'], $listing['image_path']]);
        $conversationId = (int)$db->lastInsertId();
    } $conversation = marketplaceChatConversation($db, $conversationId, $actorId);
    Audit::log($db, $actorId, 'marketplace.chat.opened', 'marketplace_conversation', $conversationId, null, ['listing_id' => $listingId]);
    Http::json(['data' => marketplaceChatPublicConversation($db, $conversation, $actorId)], 201);
}

if ($method === 'GET' && route('/marketplace/chats/{id}/messages', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $conversationId = (int)$parameters['id']; $conversation = marketplaceChatConversation($db, $conversationId, $actorId);
    $db->prepare('UPDATE marketplace_messages SET read_at=COALESCE(read_at, NOW()) WHERE conversation_id=? AND sender_id<>? AND read_at IS NULL')->execute([$conversationId, $actorId]);
    $messages = $db->prepare('SELECT m.id, m.conversation_id, m.sender_id, sender.full_name AS sender_name, m.message_type, m.body, m.image_path, m.payload_json, m.read_at, m.created_at FROM marketplace_messages m JOIN users sender ON sender.id=m.sender_id WHERE m.conversation_id=? ORDER BY m.created_at ASC, m.id ASC LIMIT 500');
    $messages->execute([$conversationId]); $rows = $messages->fetchAll(); marketplaceExpireOffers($db, $conversationId);
    $offers = marketplaceOfferRows($db, $conversationId); $offersByMessage = [];
    foreach ($offers as $offer) if ($offer['message_id'] !== null) $offersByMessage[(int)$offer['message_id']] = $offer;
    foreach ($rows as &$row) { $row['payload'] = $row['payload_json'] === null ? null : (json_decode((string)$row['payload_json'], true) ?: null); if (isset($offersByMessage[(int)$row['id']])) $row['payload'] = ['offer' => $offersByMessage[(int)$row['id']]]; unset($row['payload_json']); } unset($row);
    $otherId = marketplaceChatOtherUserId($conversation, $actorId); $typing = $db->prepare('SELECT 1 FROM marketplace_typing_status WHERE conversation_id=? AND user_id=? AND expires_at>NOW() LIMIT 1'); $typing->execute([$conversationId, $otherId]);
    Http::json(['data' => $rows, 'conversation' => marketplaceChatPublicConversation($db, $conversation, $actorId), 'meta' => ['other_is_typing' => (bool)$typing->fetchColumn()]]);
}

if ($method === 'GET' && route('/marketplace/chats/{id}/offers', $path, $parameters)) {
    $actor = user($db); $conversationId = (int)$parameters['id']; marketplaceChatConversation($db, $conversationId, (int)$actor['id']);
    Http::json(['data' => marketplaceOfferRows($db, $conversationId)]);
}

if ($method === 'POST' && route('/marketplace/chats/{id}/offers', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $conversationId = (int)$parameters['id']; $conversation = marketplaceChatConversation($db, $conversationId, $actorId); marketplaceChatAssertOpen($db, $conversation, $actorId);
    if ((int)$conversation['buyer_id'] !== $actorId) Http::error('forbidden', 'يمكن للمشتري فقط إرسال العرض الأول.', 403);
    $input = Http::input(); $amount = $input['amount'] ?? null; $currency = strtoupper(trim((string)($input['currency'] ?? '')));
    if (!is_numeric($amount) || (float)$amount <= 0 || !in_array($currency, ['USD','SYP'], true)) Http::error('validation_error', 'أدخل قيمة وعملة صالحتين للعرض.', 422);
    $expiresAt = marketplaceOfferExpiry($input); marketplaceExpireOffers($db, $conversationId);
    $db->beginTransaction(); try {
        $lockedConversation = marketplaceChatConversation($db, $conversationId, $actorId); $listing = $db->prepare('SELECT id, seller_user_id, currency, status FROM listings_marketplace WHERE id=? FOR UPDATE'); $listing->execute([(int)$lockedConversation['listing_id']]); $listing = $listing->fetch();
        if (!$listing || $listing['status'] !== 'published') Http::error('listing_unavailable', 'لا يمكن إرسال عرض لأن الإعلان لم يعد متاحاً.', 409);
        if (strtoupper(trim((string)$listing['currency'])) !== $currency) Http::error('validation_error', 'يجب أن تطابق عملة العرض عملة الإعلان.', 422);
        $pending = $db->prepare("SELECT id FROM marketplace_offers WHERE conversation_id=? AND status='pending' LIMIT 1 FOR UPDATE"); $pending->execute([$conversationId]); if ($pending->fetch()) Http::error('active_offer_exists', 'يوجد عرض نشط بالفعل. انتظر الرد أو ألغِ العرض الحالي.', 409);
        $db->prepare('INSERT INTO marketplace_offers (conversation_id, listing_id, buyer_id, seller_id, created_by_id, amount, currency, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')->execute([$conversationId, $lockedConversation['listing_id'], $lockedConversation['buyer_id'], $lockedConversation['seller_id'], $actorId, (float)$amount, $currency, $expiresAt]);
        $offerId = (int)$db->lastInsertId(); $db->prepare("INSERT INTO marketplace_messages (conversation_id, sender_id, message_type, payload_json) VALUES (?, ?, 'offer', ?)")->execute([$conversationId, $actorId, json_encode(['offer_id' => $offerId], JSON_UNESCAPED_UNICODE)]); $messageId = (int)$db->lastInsertId();
        $db->prepare('UPDATE marketplace_offers SET message_id=? WHERE id=?')->execute([$messageId, $offerId]); $db->prepare('UPDATE marketplace_conversations SET last_message_at=NOW() WHERE id=?')->execute([$conversationId]); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    $offer = marketplaceOffer($db, $offerId, $actorId); marketplaceNotify($db, (int)$offer['seller_id'], 'offer_received', 'وصل عرض جديد', 'وصل عرض جديد على إعلانك في الحراج.', 'offer_received:' . $offerId, ['listing_id' => (int)$offer['listing_id'], 'conversation_id' => $conversationId, 'offer_id' => $offerId]); Audit::log($db, $actorId, 'marketplace.offer.created', 'marketplace_offer', $offerId, null, ['conversation_id' => $conversationId, 'amount' => (float)$amount, 'currency' => $currency]); Http::json(['data' => marketplaceOfferPublic($offer)], 201);
}

if ($method === 'POST' && route('/marketplace/offers/{id}/counter', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $offerId = (int)$parameters['id']; $input = Http::input(); $amount = $input['amount'] ?? null; $currency = strtoupper(trim((string)($input['currency'] ?? '')));
    if (!is_numeric($amount) || (float)$amount <= 0 || !in_array($currency, ['USD','SYP'], true)) Http::error('validation_error', 'أدخل قيمة وعملة صالحتين للعرض المقابل.', 422);
    $expiresAt = marketplaceOfferExpiry($input); $db->beginTransaction(); try {
        $offer = marketplaceOffer($db, $offerId, $actorId, true); marketplaceExpireOffers($db, (int)$offer['conversation_id']); $offer = marketplaceOffer($db, $offerId, $actorId, true);
        if ($offer['status'] !== 'pending' || (int)$offer['created_by_id'] === $actorId) Http::error('offer_not_actionable', 'لا يمكن إرسال عرض مقابل لهذا العرض.', 409);
        $conversation = marketplaceChatConversation($db, (int)$offer['conversation_id'], $actorId); marketplaceChatAssertOpen($db, $conversation, $actorId);
        $listing = $db->prepare('SELECT id, status, currency FROM listings_marketplace WHERE id=? FOR UPDATE'); $listing->execute([(int)$offer['listing_id']]); $listing = $listing->fetch(); if (!$listing || $listing['status'] !== 'published' || strtoupper(trim((string)$listing['currency'])) !== $currency) Http::error('listing_unavailable', 'الإعلان غير متاح أو عملة العرض غير مطابقة.', 409);
        $db->prepare("UPDATE marketplace_offers SET status='counter_offer', responded_at=NOW(), responded_by_id=? WHERE id=? AND status='pending'")->execute([$actorId, $offerId]);
        $db->prepare('INSERT INTO marketplace_offers (conversation_id, listing_id, buyer_id, seller_id, created_by_id, parent_offer_id, amount, currency, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([$offer['conversation_id'], $offer['listing_id'], $offer['buyer_id'], $offer['seller_id'], $actorId, $offerId, (float)$amount, $currency, $expiresAt]);
        $counterId = (int)$db->lastInsertId(); $db->prepare("INSERT INTO marketplace_messages (conversation_id, sender_id, message_type, payload_json) VALUES (?, ?, 'counter_offer', ?)")->execute([$offer['conversation_id'], $actorId, json_encode(['offer_id' => $counterId, 'parent_offer_id' => $offerId], JSON_UNESCAPED_UNICODE)]); $messageId = (int)$db->lastInsertId(); $db->prepare('UPDATE marketplace_offers SET message_id=? WHERE id=?')->execute([$messageId, $counterId]); $db->prepare('UPDATE marketplace_conversations SET last_message_at=NOW() WHERE id=?')->execute([$offer['conversation_id']]); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    $counter = marketplaceOffer($db, $counterId, $actorId); $recipientId = (int)$counter['buyer_id'] === $actorId ? (int)$counter['seller_id'] : (int)$counter['buyer_id']; marketplaceNotify($db, $recipientId, 'counter_offer_received', 'وصل عرض مضاد', 'أرسل الطرف المقابل عرضاً مضاداً على إعلان الحراج.', 'counter_offer_received:' . $counterId, ['listing_id' => (int)$counter['listing_id'], 'conversation_id' => (int)$counter['conversation_id'], 'offer_id' => $counterId]); Audit::log($db, $actorId, 'marketplace.offer.countered', 'marketplace_offer', $counterId, null, ['parent_offer_id' => $offerId]); Http::json(['data' => marketplaceOfferPublic($counter)], 201);
}

if ($method === 'POST' && route('/marketplace/offers/{id}/accept', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $offerId = (int)$parameters['id']; $db->beginTransaction(); try {
        $offer = marketplaceOffer($db, $offerId, $actorId, true); marketplaceExpireOffers($db, (int)$offer['conversation_id']); $offer = marketplaceOffer($db, $offerId, $actorId, true);
        if ($offer['status'] !== 'pending' || (int)$offer['created_by_id'] === $actorId) Http::error('offer_not_actionable', 'لا يمكن قبول هذا العرض.', 409);
        $conversation = marketplaceChatConversation($db, (int)$offer['conversation_id'], $actorId); marketplaceChatAssertOpen($db, $conversation, $actorId);
        $listing = $db->prepare('SELECT id, status FROM listings_marketplace WHERE id=? FOR UPDATE'); $listing->execute([(int)$offer['listing_id']]); $listing = $listing->fetch(); if (!$listing || $listing['status'] !== 'published') Http::error('listing_unavailable', 'لا يمكن قبول العرض لأن الإعلان لم يعد متاحاً.', 409);
        $existing = $db->prepare('SELECT id FROM marketplace_transactions WHERE offer_id=? LIMIT 1 FOR UPDATE'); $existing->execute([$offerId]); if ($existing->fetch()) Http::error('transaction_exists', 'أنشئت معاملة لهذا العرض سابقاً.', 409);
        $transactionNumber = 'MTX-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
        $db->prepare("INSERT INTO marketplace_transactions (transaction_number, listing_id, buyer_id, seller_id, amount, agreed_price, currency, status, payment_status, delivery_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', 'not_required')")->execute([$transactionNumber, $offer['listing_id'], $offer['buyer_id'], $offer['seller_id'], $offer['amount'], $offer['amount'], $offer['currency']]); $transactionId = (int)$db->lastInsertId();
        $acceptedUpdate = $db->prepare("UPDATE marketplace_offers SET status='accepted', responded_at=NOW(), responded_by_id=?, transaction_id=? WHERE id=? AND status='pending'"); $acceptedUpdate->execute([$actorId, $transactionId, $offerId]); if ($acceptedUpdate->rowCount() !== 1) Http::error('offer_not_actionable', 'تعذر تحديث العرض.', 409);
        $db->prepare('UPDATE marketplace_transactions SET offer_id=? WHERE id=? AND offer_id IS NULL')->execute([$offerId, $transactionId]); $db->prepare("UPDATE listings_marketplace SET status='reserved' WHERE id=? AND status='published'")->execute([$offer['listing_id']]);
        $db->prepare("UPDATE marketplace_offers SET status='cancelled', responded_at=NOW(), responded_by_id=? WHERE listing_id=? AND id<>? AND status='pending'")->execute([$actorId, $offer['listing_id'], $offerId]);
        $db->prepare("INSERT INTO marketplace_messages (conversation_id, sender_id, message_type, body, payload_json) VALUES (?, ?, 'transaction', ?, ?)")->execute([$offer['conversation_id'], $actorId, 'تم قبول العرض وحُجز الإعلان. أُنشئت معاملة بانتظار الإجراء التالي.', json_encode(['offer_id' => $offerId, 'transaction_id' => $transactionId, 'transaction_number' => $transactionNumber], JSON_UNESCAPED_UNICODE)]); $db->prepare('UPDATE marketplace_conversations SET last_message_at=NOW() WHERE id=?')->execute([$offer['conversation_id']]); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    $accepted = marketplaceOffer($db, $offerId, $actorId); $offerCreatorId = (int)$accepted['created_by_id']; marketplaceNotify($db, $offerCreatorId, 'offer_accepted', 'تم قبول العرض', 'تم قبول عرضك وحُجز الإعلان لإنشاء الصفقة.', 'offer_accepted:' . $offerId, ['listing_id' => (int)$accepted['listing_id'], 'conversation_id' => (int)$accepted['conversation_id'], 'offer_id' => $offerId, 'transaction_id' => $transactionId]); foreach ([(int)$accepted['buyer_id'], (int)$accepted['seller_id']] as $recipientId) marketplaceNotify($db, $recipientId, 'transaction_created', 'تم إنشاء صفقة', 'أُنشئت صفقة جديدة مرتبطة بالعرض المقبول.', 'transaction_created:' . $transactionId . ':' . $recipientId, ['listing_id' => (int)$accepted['listing_id'], 'conversation_id' => (int)$accepted['conversation_id'], 'offer_id' => $offerId, 'transaction_id' => $transactionId]); Audit::log($db, $actorId, 'marketplace.offer.accepted', 'marketplace_offer', $offerId, null, ['transaction_id' => $transactionId, 'listing_status' => 'reserved']); Http::json(['data' => marketplaceOfferPublic($accepted), 'transaction' => ['id' => $transactionId, 'number' => $transactionNumber, 'status' => 'pending'], 'listing_status' => 'reserved']);
}

if ($method === 'POST' && route('/marketplace/offers/{id}/reject', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $offerId = (int)$parameters['id']; $db->beginTransaction(); try { $offer = marketplaceOffer($db, $offerId, $actorId, true); marketplaceExpireOffers($db, (int)$offer['conversation_id']); $offer = marketplaceOffer($db, $offerId, $actorId, true); if ($offer['status'] !== 'pending' || (int)$offer['created_by_id'] === $actorId) Http::error('offer_not_actionable', 'لا يمكن رفض هذا العرض.', 409); $db->prepare("UPDATE marketplace_offers SET status='rejected', responded_at=NOW(), responded_by_id=? WHERE id=? AND status='pending'")->execute([$actorId, $offerId]); $db->prepare("INSERT INTO marketplace_messages (conversation_id, sender_id, message_type, body, payload_json) VALUES (?, ?, 'system', ?, ?)")->execute([$offer['conversation_id'], $actorId, 'تم رفض العرض.', json_encode(['offer_id' => $offerId, 'status' => 'rejected'])]); $db->prepare('UPDATE marketplace_conversations SET last_message_at=NOW() WHERE id=?')->execute([$offer['conversation_id']]); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    $rejected = marketplaceOffer($db, $offerId, $actorId); marketplaceNotify($db, (int)$rejected['created_by_id'], 'offer_rejected', 'تم رفض العرض', 'تم رفض العرض المرتبط بإعلان الحراج.', 'offer_rejected:' . $offerId, ['listing_id' => (int)$rejected['listing_id'], 'conversation_id' => (int)$rejected['conversation_id'], 'offer_id' => $offerId]); Audit::log($db, $actorId, 'marketplace.offer.rejected', 'marketplace_offer', $offerId); Http::json(['data' => marketplaceOfferPublic($rejected)]);
}

if ($method === 'POST' && route('/marketplace/offers/{id}/cancel', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $offerId = (int)$parameters['id']; $db->beginTransaction(); try { $offer = marketplaceOffer($db, $offerId, $actorId, true); marketplaceExpireOffers($db, (int)$offer['conversation_id']); $offer = marketplaceOffer($db, $offerId, $actorId, true); if ($offer['status'] !== 'pending' || (int)$offer['created_by_id'] !== $actorId) Http::error('offer_not_actionable', 'لا يمكن إلغاء هذا العرض.', 409); $db->prepare("UPDATE marketplace_offers SET status='cancelled', responded_at=NOW(), responded_by_id=? WHERE id=? AND status='pending'")->execute([$actorId, $offerId]); $db->prepare("INSERT INTO marketplace_messages (conversation_id, sender_id, message_type, body, payload_json) VALUES (?, ?, 'system', ?, ?)")->execute([$offer['conversation_id'], $actorId, 'أُلغي العرض من صاحبه.', json_encode(['offer_id' => $offerId, 'status' => 'cancelled'])]); $db->prepare('UPDATE marketplace_conversations SET last_message_at=NOW() WHERE id=?')->execute([$offer['conversation_id']]); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    $cancelled = marketplaceOffer($db, $offerId, $actorId); Audit::log($db, $actorId, 'marketplace.offer.cancelled', 'marketplace_offer', $offerId); Http::json(['data' => marketplaceOfferPublic($cancelled)]);
}

if ($method === 'POST' && route('/marketplace/chats/{id}/messages', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $conversationId = (int)$parameters['id']; $conversation = marketplaceChatConversation($db, $conversationId, $actorId); marketplaceChatAssertOpen($db, $conversation, $actorId);
    $input = Http::input(); $type = (string)($input['message_type'] ?? 'text'); $body = trim((string)($input['body'] ?? ''));
    if ($type !== 'text') Http::error('validation_error', 'استخدم نظام العروض لإرسال عرض أو عرض مقابل.', 422);
    if (mb_strlen($body) < 1 || mb_strlen($body) > 4000) Http::error('validation_error', 'يجب أن تكون الرسالة النصية بين 1 و4000 حرف.', 422);
    $db->beginTransaction(); try {
        $db->prepare('INSERT INTO marketplace_messages (conversation_id, sender_id, message_type, body) VALUES (?, ?, ?, ?)')->execute([$conversationId, $actorId, 'text', $body]);
        $messageId = (int)$db->lastInsertId(); $db->prepare('UPDATE marketplace_conversations SET last_message_at=NOW() WHERE id=?')->execute([$conversationId]); $db->prepare('DELETE FROM marketplace_typing_status WHERE conversation_id=? AND user_id=?')->execute([$conversationId, $actorId]); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    $otherUserId = marketplaceChatOtherUserId($conversation, $actorId); marketplaceNotify($db, $otherUserId, 'message_received', 'رسالة جديدة في الحراج', mb_substr($body, 0, 220), 'message_received:' . $messageId, ['listing_id' => (int)$conversation['listing_id'], 'conversation_id' => $conversationId], ['message_id' => $messageId]); Audit::log($db, $actorId, 'marketplace.chat.message.created', 'marketplace_message', $messageId, null, ['conversation_id' => $conversationId, 'type' => $type]);
    Http::json(['data' => ['id' => $messageId, 'conversation_id' => $conversationId]], 201);
}

if ($method === 'POST' && route('/marketplace/chats/{id}/image', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $conversationId = (int)$parameters['id']; $conversation = marketplaceChatConversation($db, $conversationId, $actorId); marketplaceChatAssertOpen($db, $conversation, $actorId);
    $imagePath = saveUpload('image', (int)Database::environment('MAX_MARKETPLACE_CHAT_IMAGE_BYTES', '5242880'), 'marketplace_chat'); $caption = trim((string)($_POST['caption'] ?? ''));
    if (mb_strlen($caption) > 1000) Http::error('validation_error', 'تعليق الصورة طويل جداً.', 422);
    $db->beginTransaction(); try { $db->prepare("INSERT INTO marketplace_messages (conversation_id, sender_id, message_type, body, image_path) VALUES (?, ?, 'image', ?, ?)")->execute([$conversationId, $actorId, $caption === '' ? null : $caption, $imagePath]); $messageId = (int)$db->lastInsertId(); $db->prepare('UPDATE marketplace_conversations SET last_message_at=NOW() WHERE id=?')->execute([$conversationId]); $db->commit(); }
    catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); $file = rtrim(Database::environment('UPLOAD_PATH', __DIR__ . '/storage/uploads'), '/') . '/' . basename($imagePath); if (is_file($file)) @unlink($file); throw $exception; }
    Audit::log($db, $actorId, 'marketplace.chat.image.created', 'marketplace_message', $messageId, null, ['conversation_id' => $conversationId]); Http::json(['data' => ['id' => $messageId, 'image_path' => $imagePath]], 201);
}

if ($method === 'POST' && route('/marketplace/chats/{id}/read', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $conversationId = (int)$parameters['id']; marketplaceChatConversation($db, $conversationId, $actorId);
    $statement = $db->prepare('UPDATE marketplace_messages SET read_at=COALESCE(read_at, NOW()) WHERE conversation_id=? AND sender_id<>? AND read_at IS NULL'); $statement->execute([$conversationId, $actorId]); Http::json(['data' => ['conversation_id' => $conversationId, 'read_count' => $statement->rowCount()]]);
}

if ($method === 'POST' && route('/marketplace/chats/{id}/typing', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $conversationId = (int)$parameters['id']; $conversation = marketplaceChatConversation($db, $conversationId, $actorId); marketplaceChatAssertOpen($db, $conversation, $actorId); $input = Http::input(); $isTyping = ($input['is_typing'] ?? true) !== false;
    if ($isTyping) $db->prepare("INSERT INTO marketplace_typing_status (conversation_id, user_id, expires_at) VALUES (?, ?, datetime('now', '+15 seconds')) ON CONFLICT(conversation_id, user_id) DO UPDATE SET expires_at=excluded.expires_at")->execute([$conversationId, $actorId]); else $db->prepare('DELETE FROM marketplace_typing_status WHERE conversation_id=? AND user_id=?')->execute([$conversationId, $actorId]);
    Http::json(['data' => ['conversation_id' => $conversationId, 'is_typing' => $isTyping]]);
}

if ($method === 'POST' && route('/marketplace/users/{id}/block', $path, $parameters)) {
    $actor = user($db); $actorId = (int)$actor['id']; $targetId = (int)$parameters['id']; if ($targetId < 1 || $targetId === $actorId) Http::error('validation_error', 'لا يمكنك حظر حسابك.', 422);
    $target = $db->prepare('SELECT id FROM users WHERE id=? LIMIT 1'); $target->execute([$targetId]); if (!$target->fetch()) Http::error('not_found', 'المستخدم غير موجود.', 404);
    $db->prepare('INSERT OR IGNORE INTO marketplace_user_blocks (blocker_id, blocked_id) VALUES (?, ?)')->execute([$actorId, $targetId]); Audit::log($db, $actorId, 'marketplace.user.blocked', 'user', $targetId); Http::json(['data' => ['user_id' => $targetId, 'blocked' => true]]);
}

if ($method === 'DELETE' && route('/marketplace/users/{id}/block', $path, $parameters)) {
    $actor = user($db); $targetId = (int)$parameters['id']; $db->prepare('DELETE FROM marketplace_user_blocks WHERE blocker_id=? AND blocked_id=?')->execute([$actor['id'], $targetId]); Audit::log($db, (int)$actor['id'], 'marketplace.user.unblocked', 'user', $targetId); Http::json(['data' => ['user_id' => $targetId, 'blocked' => false]]);
}

if ($method === 'POST' && $path === '/marketplace/reports') {
    $actor = user($db); $actorId = (int)$actor['id']; $input = Http::input(); Http::requireFields($input, ['reason']); $reason = trim((string)$input['reason']);
    $reasons = ['fraud','fake_listing','prohibited_item','inaccurate_information','stolen_image','duplicate_listing','inappropriate_content','suspicious_seller','other'];
    if (!in_array($reason, $reasons, true)) Http::error('validation_error', 'سبب التبليغ غير صالح.', 422);
    $listingId = isset($input['listing_id']) ? (int)$input['listing_id'] : null; $targetType = trim((string)($input['target_type'] ?? ($listingId ? 'listing' : 'user')));
    if (!in_array($targetType, ['listing','user'], true)) Http::error('validation_error', 'نوع هدف التبليغ غير صالح.', 422);
    $targetId = isset($input['target_user_id']) ? (int)$input['target_user_id'] : 0;
    if ($targetType === 'listing') {
        if (!$listingId) Http::error('validation_error', 'الإعلان المطلوب غير محدد.', 422);
        $listingStatement = $db->prepare('SELECT id, seller_user_id FROM listings_marketplace WHERE id=? LIMIT 1'); $listingStatement->execute([$listingId]); $listing = $listingStatement->fetch();
        if (!$listing) Http::error('not_found', 'الإعلان المحدد غير موجود.', 404); $targetId = (int)$listing['seller_user_id'];
    } else {
        if ($targetId < 1) Http::error('validation_error', 'المستخدم المطلوب غير محدد.', 422);
        $target = $db->prepare('SELECT id FROM users WHERE id=? LIMIT 1'); $target->execute([$targetId]); if (!$target->fetch()) Http::error('not_found', 'المستخدم غير موجود.', 404);
    }
    if ($targetId === $actorId) Http::error('validation_error', 'لا يمكنك الإبلاغ عن نفسك أو إعلانك.', 422);
    $conversationId = isset($input['conversation_id']) ? (int)$input['conversation_id'] : null; $messageId = isset($input['message_id']) ? (int)$input['message_id'] : null;
    if ($conversationId) { $conversation = marketplaceChatConversation($db, $conversationId, $actorId); if (marketplaceChatOtherUserId($conversation, $actorId) !== $targetId) Http::error('validation_error', 'هدف التبليغ لا يطابق المحادثة.', 422); if ($listingId && (int)$conversation['listing_id'] !== $listingId) Http::error('validation_error', 'الإعلان المحدد لا يطابق المحادثة.', 422); }
    if ($messageId) { $message = $db->prepare('SELECT sender_id, conversation_id FROM marketplace_messages WHERE id=? LIMIT 1'); $message->execute([$messageId]); $message = $message->fetch(); if (!$message || (int)$message['sender_id'] !== $targetId || ($conversationId && (int)$message['conversation_id'] !== $conversationId)) Http::error('validation_error', 'الرسالة المحددة غير صالحة.', 422); }
    if ($listingId && $targetType === 'user') { $listing = $db->prepare('SELECT id FROM listings_marketplace WHERE id=? LIMIT 1'); $listing->execute([$listingId]); if (!$listing->fetch()) Http::error('not_found', 'الإعلان المحدد غير موجود.', 404); }
    $details = trim((string)($input['details'] ?? '')); if (mb_strlen($details) > 2000) Http::error('validation_error', 'تفاصيل التبليغ طويلة جداً.', 422);
    $db->prepare('INSERT INTO marketplace_reports (reporter_id, target_type, target_user_id, listing_id, conversation_id, message_id, reason, details) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')->execute([$actorId, $targetType, $targetId, $listingId ?: null, $conversationId ?: null, $messageId ?: null, $reason, $details === '' ? null : $details]); $reportId = (int)$db->lastInsertId(); Audit::log($db, $actorId, 'marketplace.report.created', 'marketplace_report', $reportId, null, ['target_type' => $targetType, 'target_user_id' => $targetId, 'listing_id' => $listingId, 'reason' => $reason]); Http::json(['data' => ['id' => $reportId, 'target_type' => $targetType, 'status' => 'open']], 201);
}

if ($method === 'GET' && $path === '/admin/marketplace/reports') {
    $actor = user($db); role($actor, ['admin']); $status = trim((string)($_GET['status'] ?? 'open')); $allowedStatuses = ['open','under_review','resolved','rejected'];
    if ($status !== '' && !in_array($status, $allowedStatuses, true)) Http::error('validation_error', 'حالة البلاغ غير صالحة.', 422);
    $sql = 'SELECT r.id, r.target_type, r.target_user_id, r.listing_id, r.conversation_id, r.message_id, r.reason, r.details, r.status, r.resolution_note, r.created_at, r.reviewed_at, reporter.full_name AS reporter_name, target.full_name AS target_user_name, listing.title AS listing_title, reviewer.full_name AS reviewer_name FROM marketplace_reports r JOIN users reporter ON reporter.id=r.reporter_id JOIN users target ON target.id=r.target_user_id LEFT JOIN listings_marketplace listing ON listing.id=r.listing_id LEFT JOIN users reviewer ON reviewer.id=r.reviewed_by';
    $params = []; if ($status !== '') { $sql .= ' WHERE r.status=?'; $params[] = $status; } $sql .= ' ORDER BY r.created_at ASC, r.id ASC LIMIT 200'; $statement = $db->prepare($sql); $statement->execute($params); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'PATCH' && route('/admin/marketplace/reports/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $reportId = (int)$parameters['id']; $input = Http::input(); Http::requireFields($input, ['status']); $status = trim((string)$input['status']);
    if (!in_array($status, ['open','under_review','resolved','rejected'], true)) Http::error('validation_error', 'حالة البلاغ غير صالحة.', 422); $note = trim((string)($input['resolution_note'] ?? '')); if (mb_strlen($note) > 1000) Http::error('validation_error', 'ملاحظة القرار طويلة جداً.', 422);
    $report = $db->prepare('SELECT id, status FROM marketplace_reports WHERE id=? LIMIT 1'); $report->execute([$reportId]); $previous = $report->fetch(); if (!$previous) Http::error('not_found', 'البلاغ غير موجود.', 404);
    $db->prepare('UPDATE marketplace_reports SET status=?, resolution_note=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?')->execute([$status, $note === '' ? null : $note, $actor['id'], $reportId]); Audit::log($db, (int)$actor['id'], 'marketplace.report.reviewed', 'marketplace_report', $reportId, ['status' => $previous['status']], ['status' => $status]);
    Http::json(['data' => ['id' => $reportId, 'status' => $status, 'listing_action' => 'none']]);
}

if ($method === 'DELETE' && route('/admin/products/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $productId = (int)$parameters['id'];
    $statement = $db->prepare("UPDATE products SET status='hidden' WHERE id=? AND status<>'hidden'"); $statement->execute([$productId]);
    if ($statement->rowCount() < 1) Http::error('not_found', 'المنتج غير موجود أو محذوف مسبقاً.', 404);
    Audit::log($db, (int)$actor['id'], 'admin.product.deleted', 'product', $productId);
    Http::json(['data' => ['id' => $productId, 'deleted' => true]]);
}
if ($method === 'DELETE' && route('/admin/marketplace/listings/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $listingId = (int)$parameters['id'];
    $statement = $db->prepare("UPDATE listings_marketplace SET status='deleted' WHERE id=? AND status<>'deleted'"); $statement->execute([$listingId]);
    if ($statement->rowCount() < 1) Http::error('not_found', 'إعلان الحراج غير موجود أو محذوف مسبقاً.', 404);
    Audit::log($db, (int)$actor['id'], 'admin.marketplace.listing.deleted', 'marketplace_listing', $listingId);
    Http::json(['data' => ['id' => $listingId, 'deleted' => true]]);
}
if ($method === 'PATCH' && route('/admin/marketplace/listings/{id}/moderation', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $listingId = (int)$parameters['id']; $input = Http::input(); Http::requireFields($input, ['status']); $status = trim((string)$input['status']);
    if (!in_array($status, ['published','rejected'], true)) Http::error('validation_error', 'قرار مراجعة الإعلان غير صالح.', 422);
    $listing = $db->prepare('SELECT id, seller_user_id, title, status FROM listings_marketplace WHERE id=? LIMIT 1 FOR UPDATE'); $listing->execute([$listingId]); $listing = $listing->fetch(); if (!$listing) Http::error('not_found', 'الإعلان غير موجود.', 404); if ($listing['status'] !== 'pending_review') Http::error('listing_not_pending_review', 'هذا الإعلان ليس قيد المراجعة.', 409);
    $db->prepare("UPDATE listings_marketplace SET status=?, published_at=CASE WHEN ?='published' THEN NOW() ELSE published_at END WHERE id=? AND status='pending_review'")->execute([$status, $status, $listingId]);
    if ($status === 'published') { marketplaceNotify($db, (int)$listing['seller_user_id'], 'listing_approved', 'تم قبول إعلانك', 'تم قبول إعلانك ونشره في الحراج.', 'listing_approved:' . $listingId, ['listing_id' => $listingId]); marketplaceNotify($db, (int)$listing['seller_user_id'], 'listing_published', 'تم نشر إعلانك', 'أصبح إعلانك متاحاً للبحث والتصفح في الحراج.', 'listing_published:' . $listingId, ['listing_id' => $listingId]); }
    else marketplaceNotify($db, (int)$listing['seller_user_id'], 'listing_rejected', 'تم رفض الإعلان', 'لم يُقبل إعلانك في المراجعة الحالية. يمكنك تعديله ثم إرساله للمراجعة من جديد.', 'listing_rejected:' . $listingId, ['listing_id' => $listingId]);
    Audit::log($db, (int)$actor['id'], 'marketplace.listing.moderated', 'marketplace_listing', $listingId, ['status' => $listing['status']], ['status' => $status]); Http::json(['data' => ['id' => $listingId, 'status' => $status]]);
}

if ($method === 'GET' && $path === '/admin/marketplace/management/dashboard') {
    $actor = user($db); role($actor, ['admin']); marketplaceExpirePromotions($db);
    $counts = $db->query("SELECT COUNT(*) AS total_listings, SUM(status='published') AS active_listings, SUM(status='sold') AS sold_listings, SUM(status='pending_review') AS pending_review FROM listings_marketplace")->fetch() ?: [];
    $reports = (int)$db->query("SELECT COUNT(*) FROM marketplace_reports WHERE status IN ('open','under_review')")->fetchColumn();
    $transactions = (int)$db->query('SELECT COUNT(*) FROM marketplace_transactions')->fetchColumn();
    $revenue = $db->query("SELECT COALESCE(SUM(CASE WHEN currency='USD' AND status='completed' THEN agreed_price ELSE 0 END),0) AS usd, COALESCE(SUM(CASE WHEN currency='SYP' AND status='completed' THEN agreed_price ELSE 0 END),0) AS syp FROM marketplace_transactions")->fetch() ?: [];
    $deliveryRevenue = $db->query("SELECT COALESCE(SUM(CASE WHEN dt.currency='USD' AND dt.status='delivered' THEN dt.delivery_fee ELSE 0 END),0) AS usd, COALESCE(SUM(CASE WHEN dt.currency='SYP' AND dt.status='delivered' THEN dt.delivery_fee ELSE 0 END),0) AS syp FROM delivery_tasks dt WHERE dt.marketplace_transaction_id IS NOT NULL")->fetch() ?: [];
    $promotionRevenue = $db->query("SELECT COALESCE(SUM(CASE WHEN price_currency='USD' AND status IN ('active','expired') THEN price_amount ELSE 0 END),0) AS usd, COALESCE(SUM(CASE WHEN price_currency='SYP' AND status IN ('active','expired') THEN price_amount ELSE 0 END),0) AS syp FROM marketplace_promotions")->fetch() ?: [];
    $statusRows = $db->query("SELECT status, COUNT(*) AS total FROM marketplace_transactions GROUP BY status ORDER BY total DESC")->fetchAll();
    Http::json(['data' => ['total_listings' => (int)($counts['total_listings'] ?? 0), 'active_listings' => (int)($counts['active_listings'] ?? 0), 'sold_listings' => (int)($counts['sold_listings'] ?? 0), 'pending_review' => (int)($counts['pending_review'] ?? 0), 'reports' => $reports, 'transactions' => $transactions, 'revenue' => ['usd' => (float)($revenue['usd'] ?? 0), 'syp' => (float)($revenue['syp'] ?? 0)], 'delivery_revenue' => ['usd' => (float)($deliveryRevenue['usd'] ?? 0), 'syp' => (float)($deliveryRevenue['syp'] ?? 0)], 'promotion_revenue' => ['usd' => (float)($promotionRevenue['usd'] ?? 0), 'syp' => (float)($promotionRevenue['syp'] ?? 0)], 'transaction_statuses' => $statusRows]]);
}

if ($method === 'GET' && route('/admin/marketplace/management/{section}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); marketplaceExpirePromotions($db); $section = (string)$parameters['section']; $status = trim((string)($_GET['status'] ?? '')); $q = trim((string)($_GET['q'] ?? '')); $allowed = ['listings','pending_listings','users','sellers','merchants','categories','attributes','offers','transactions','deliveries','reviews','promotions','statistics']; if (!in_array($section, $allowed, true)) Http::error('not_found', 'قسم إدارة الحراج غير موجود.', 404);
    $rows = [];
    if (in_array($section, ['listings','pending_listings'], true)) {
        $sql = "SELECT l.id, l.title, l.price, l.currency, l.status, l.view_count, l.created_at, l.published_at, seller.id AS seller_id, seller.full_name AS seller_name, c.name AS category_name, (SELECT file_path FROM listing_images_marketplace li WHERE li.listing_id=l.id ORDER BY li.sort_order, li.id LIMIT 1) AS image_path, CASE WHEN EXISTS (SELECT 1 FROM marketplace_promotions p WHERE p.listing_id=l.id AND p.status='active' AND p.end_at>NOW()) THEN 1 ELSE 0 END AS has_active_promotion FROM listings_marketplace l JOIN users seller ON seller.id=l.seller_user_id JOIN categories_marketplace c ON c.id=l.category_id WHERE 1=1"; $args = [];
        if ($section === 'pending_listings') { $sql .= " AND l.status='pending_review'"; } elseif ($status !== '' && in_array($status, ['draft','pending_review','published','reserved','sold','hidden','expired','rejected','deleted'], true)) { $sql .= ' AND l.status=?'; $args[] = $status; }
        if ($q !== '') { $sql .= ' AND (l.title LIKE ? OR seller.full_name LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; } $sql .= ' ORDER BY l.created_at DESC, l.id DESC LIMIT 200'; $statement = $db->prepare($sql); $statement->execute($args); $rows = $statement->fetchAll();
    } elseif (in_array($section, ['users','sellers','merchants'], true)) {
        $sql = "SELECT DISTINCT u.id, u.full_name, u.phone, u.email, u.role, u.status, u.created_at, u.last_login_at, COUNT(DISTINCT l.id) AS listings_count, COUNT(DISTINCT t.id) AS transactions_count, COUNT(DISTINCT s.id) AS stores_count FROM users u LEFT JOIN listings_marketplace l ON l.seller_user_id=u.id LEFT JOIN marketplace_transactions t ON t.buyer_id=u.id OR t.seller_id=u.id LEFT JOIN stores s ON s.merchant_id=u.id WHERE 1=1"; $args = [];
        if ($section === 'sellers') $sql .= ' AND l.id IS NOT NULL'; if ($section === 'merchants') $sql .= " AND u.role='merchant'"; if ($status !== '' && in_array($status, ['active','restricted','pending','deleted'], true)) { $sql .= ' AND u.status=?'; $args[] = $status; } if ($q !== '') { $sql .= ' AND (u.full_name LIKE ? OR u.phone LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; } $sql .= ' GROUP BY u.id, u.full_name, u.phone, u.email, u.role, u.status, u.created_at, u.last_login_at ORDER BY u.created_at DESC LIMIT 200'; $statement = $db->prepare($sql); $statement->execute($args); $rows = $statement->fetchAll();
    } elseif ($section === 'categories') {
        $rows = $db->query('SELECT c.id, c.name, c.slug, c.parent_id, parent.name AS parent_name, c.status, c.display_order, COUNT(l.id) AS listings_count FROM categories_marketplace c LEFT JOIN categories_marketplace parent ON parent.id=c.parent_id LEFT JOIN listings_marketplace l ON l.category_id=c.id GROUP BY c.id, c.name, c.slug, c.parent_id, parent.name, c.status, c.display_order ORDER BY c.display_order, c.name LIMIT 300')->fetchAll();
    } elseif ($section === 'attributes') {
        $rows = $db->query('SELECT a.id, a.category_id, c.name AS category_name, a.attribute_key, a.label, a.field_type, a.is_required, a.status, a.display_order, COUNT(o.id) AS options_count FROM category_attributes_marketplace a JOIN categories_marketplace c ON c.id=a.category_id LEFT JOIN category_attribute_options_marketplace o ON o.attribute_id=a.id GROUP BY a.id, a.category_id, c.name, a.attribute_key, a.label, a.field_type, a.is_required, a.status, a.display_order ORDER BY c.name, a.display_order, a.id LIMIT 400')->fetchAll();
    } elseif ($section === 'offers') {
        $rows = $db->query('SELECT o.id, o.listing_id, l.title AS listing_title, o.amount, o.currency, o.status, o.expires_at, o.created_at, buyer.full_name AS buyer_name, seller.full_name AS seller_name, creator.full_name AS created_by_name FROM marketplace_offers o JOIN listings_marketplace l ON l.id=o.listing_id JOIN users buyer ON buyer.id=o.buyer_id JOIN users seller ON seller.id=o.seller_id JOIN users creator ON creator.id=o.created_by_id ORDER BY o.created_at DESC, o.id DESC LIMIT 300')->fetchAll();
    } elseif ($section === 'transactions') {
        $rows = $db->query('SELECT t.id, t.transaction_number, t.listing_id, l.title AS listing_title, t.agreed_price, t.currency, t.status, t.payment_status, t.delivery_status, t.created_at, t.completed_at, buyer.full_name AS buyer_name, seller.full_name AS seller_name FROM marketplace_transactions t JOIN listings_marketplace l ON l.id=t.listing_id JOIN users buyer ON buyer.id=t.buyer_id JOIN users seller ON seller.id=t.seller_id ORDER BY t.created_at DESC, t.id DESC LIMIT 300')->fetchAll();
    } elseif ($section === 'deliveries') {
        $rows = $db->query('SELECT dt.id, dt.marketplace_transaction_id AS transaction_id, dt.tracking_code, dt.status, dt.delivery_fee, dt.currency, dt.created_at, dt.accepted_at, l.title AS listing_title, buyer.full_name AS buyer_name, seller.full_name AS seller_name, courier.full_name AS courier_name FROM delivery_tasks dt JOIN marketplace_transactions t ON t.id=dt.marketplace_transaction_id JOIN listings_marketplace l ON l.id=t.listing_id JOIN users buyer ON buyer.id=t.buyer_id JOIN users seller ON seller.id=t.seller_id LEFT JOIN users courier ON courier.id=dt.courier_id WHERE dt.marketplace_transaction_id IS NOT NULL ORDER BY dt.created_at DESC, dt.id DESC LIMIT 300')->fetchAll();
    } elseif ($section === 'reviews') {
        $rows = $db->query('SELECT r.id, r.transaction_id, r.rating, r.comment, r.reviewer_role, r.created_at, reviewer.full_name AS reviewer_name, reviewee.full_name AS reviewee_name FROM marketplace_transaction_reviews r JOIN users reviewer ON reviewer.id=r.reviewer_id JOIN users reviewee ON reviewee.id=r.reviewee_id ORDER BY r.created_at DESC, r.id DESC LIMIT 300')->fetchAll();
    } elseif ($section === 'promotions') {
        $rows = $db->query('SELECT p.id, p.listing_id, l.title AS listing_title, u.full_name AS user_name, p.promotion_type, p.status, p.start_at, p.end_at, p.price_amount, p.price_currency, p.created_at FROM marketplace_promotions p JOIN listings_marketplace l ON l.id=p.listing_id JOIN users u ON u.id=p.user_id ORDER BY p.created_at DESC, p.id DESC LIMIT 300')->fetchAll();
    } elseif ($section === 'statistics') {
        $rows = $db->query("SELECT CONCAT('المعاملات: ', status) AS label, status, COUNT(*) AS total FROM marketplace_transactions GROUP BY status UNION ALL SELECT CONCAT('الإعلانات: ', status) AS label, status, COUNT(*) AS total FROM listings_marketplace GROUP BY status ORDER BY total DESC LIMIT 100")->fetchAll();
    } else {
        $rows = $db->query("SELECT r.id, r.target_type, r.reason, r.status, r.created_at, reporter.full_name AS reporter_name, target.full_name AS target_name, l.title AS listing_title FROM marketplace_reports r JOIN users reporter ON reporter.id=r.reporter_id JOIN users target ON target.id=r.target_user_id LEFT JOIN listings_marketplace l ON l.id=r.listing_id ORDER BY r.created_at DESC, r.id DESC LIMIT 300")->fetchAll();
    }
    Http::json(['data' => $rows, 'meta' => ['section' => $section, 'count' => count($rows)]]);
}

if ($method === 'PATCH' && route('/admin/marketplace/management/listings/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $listingId = (int)$parameters['id']; $input = Http::input(); Http::requireFields($input, ['action']); $action = (string)$input['action']; if (!in_array($action, ['approve','reject','delete'], true)) Http::error('validation_error', 'إجراء الإعلان غير صالح.', 422);
    $listing = $db->prepare('SELECT id, seller_user_id, status FROM listings_marketplace WHERE id=? LIMIT 1'); $listing->execute([$listingId]); $listing = $listing->fetch(); if (!$listing) Http::error('not_found', 'الإعلان غير موجود.', 404);
    if ($action === 'approve' && in_array($listing['status'], ['published','reserved','sold','deleted'], true)) Http::error('listing_not_unpublished', 'لا يمكن نشر إعلان منشور أو محجوز أو مباع أو محذوف.', 409);
    if ($action === 'delete' && $listing['status'] !== 'published') Http::error('listing_not_published', 'الحذف من هذه الشاشة متاح للإعلان المنشور فقط.', 409);
    $next = $action === 'approve' ? 'published' : 'deleted';
    $db->prepare("UPDATE listings_marketplace SET status=?, published_at=CASE WHEN ?='published' THEN COALESCE(published_at, NOW()) ELSE published_at END WHERE id=? AND status=?")->execute([$next, $next, $listingId, $listing['status']]);
    $type = $action === 'approve' ? 'listing_approved' : ($action === 'reject' ? 'listing_rejected' : 'listing_deleted'); $title = $action === 'approve' ? 'تم قبول إعلانك' : ($action === 'reject' ? 'تم رفض الإعلان وحذفه' : 'تم حذف الإعلان'); $message = $action === 'approve' ? 'تم قبول إعلانك ونشره في الحراج.' : ($action === 'reject' ? 'تم رفض الإعلان وحذفه من لوحة الإدارة.' : 'تم حذف إعلانك من الحراج بواسطة الإدارة.'); marketplaceNotify($db, (int)$listing['seller_user_id'], $type, $title, $message, $type . ':admin:' . $listingId, ['listing_id' => $listingId]); Audit::log($db, (int)$actor['id'], 'marketplace.management.listing_' . $action, 'marketplace_listing', $listingId, ['status' => $listing['status']], ['status' => $next]); Http::json(['data' => ['id' => $listingId, 'status' => $next, 'action' => $action]]);
}

if ($method === 'PATCH' && route('/admin/marketplace/management/users/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $userId = (int)$parameters['id']; $input = Http::input(); Http::requireFields($input, ['status']); $status = (string)$input['status']; if (!in_array($status, ['active','restricted'], true)) Http::error('validation_error', 'حالة المستخدم غير صالحة.', 422); if ($userId === (int)$actor['id']) Http::error('forbidden', 'لا يمكنك تقييد حسابك من هذه الشاشة.', 403);
    $target = $db->prepare('SELECT id, role, status FROM users WHERE id=? LIMIT 1'); $target->execute([$userId]); $target = $target->fetch(); if (!$target) Http::error('not_found', 'المستخدم غير موجود.', 404); if ($target['role'] === 'admin') Http::error('forbidden', 'لا يمكن تقييد حساب مدير من هذه الشاشة.', 403); $db->prepare('UPDATE users SET status=? WHERE id=?')->execute([$status, $userId]); Audit::log($db, (int)$actor['id'], 'marketplace.management.user_status', 'user', $userId, ['status' => $target['status']], ['status' => $status]); Http::json(['data' => ['id' => $userId, 'status' => $status]]);
}

if ($method === 'GET' && $path === '/marketplace/locations') {
    $parentId = filter_input(INPUT_GET, 'parent_id', FILTER_VALIDATE_INT);
    $locationType = trim((string)($_GET['location_type'] ?? ''));
    $sql = 'SELECT id, parent_id, location_type, name, code, display_order, status FROM marketplace_locations WHERE status=\'active\'';
    $parameters = [];
    if ($parentId) { $sql .= ' AND parent_id=?'; $parameters[] = $parentId; }
    elseif ($locationType !== '') { $sql .= ' AND location_type=?'; $parameters[] = $locationType; }
    else $sql .= ' AND parent_id IS NULL';
    $sql .= ' ORDER BY display_order, name, id';
    $statement = $db->prepare($sql); $statement->execute($parameters); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'GET' && $path === '/admin/marketplace/settings') {
    $actor = user($db); role($actor, ['admin']);
    $title = $db->query("SELECT value_text FROM marketplace_settings WHERE setting_key='home_title' LIMIT 1")->fetchColumn();
    $autoApprove = $db->query("SELECT value_text FROM marketplace_settings WHERE setting_key='auto_approve_listings' LIMIT 1")->fetchColumn();
    Http::json(['data' => ['home_title' => $title ?: 'الحراج', 'auto_approve_listings' => $autoApprove === '1']]);
}

if ($method === 'PATCH' && $path === '/admin/marketplace/settings') {
    $actor = user($db); role($actor, ['admin']); $input = Http::input();
    $title = trim((string)($input['home_title'] ?? ''));
    if (mb_strlen($title) < 2 || mb_strlen($title) > 100) Http::error('validation_error', 'عنوان الحراج يجب أن يكون بين حرفين و100 حرف.', 422, ['home_title' => 'غير صالح']);
    $autoApprove = !empty($input['auto_approve_listings']);
    $db->prepare("INSERT INTO marketplace_settings (setting_key, value_text, updated_by) VALUES ('home_title', ?, ?) ON CONFLICT(setting_key) DO UPDATE SET value_text=excluded.value_text, updated_by=excluded.updated_by")->execute([$title, $actor['id']]);
    $db->prepare("INSERT INTO marketplace_settings (setting_key, value_text, updated_by) VALUES ('auto_approve_listings', ?, ?) ON CONFLICT(setting_key) DO UPDATE SET value_text=excluded.value_text, updated_by=excluded.updated_by")->execute([$autoApprove ? '1' : '0', $actor['id']]);
    Audit::log($db, (int)$actor['id'], 'marketplace.settings.updated', 'marketplace_setting', null, null, ['home_title' => $title, 'auto_approve_listings' => $autoApprove]);
    Http::json(['data' => ['home_title' => $title, 'auto_approve_listings' => $autoApprove]]);
}

if ($method === 'GET' && $path === '/admin/marketplace/locations') {
    $actor = user($db); role($actor, ['admin']);
    $statement = $db->query('SELECT l.id, l.parent_id, l.location_type, l.name, l.code, l.display_order, l.status, l.created_at, parent.name AS parent_name, COUNT(child.id) AS children_count FROM marketplace_locations l LEFT JOIN marketplace_locations parent ON parent.id=l.parent_id LEFT JOIN marketplace_locations child ON child.parent_id=l.id GROUP BY l.id, l.parent_id, l.location_type, l.name, l.code, l.display_order, l.status, l.created_at, parent.name ORDER BY l.location_type, l.parent_id IS NOT NULL, l.display_order, l.name, l.id');
    Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && $path === '/admin/marketplace/locations') {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['name', 'location_type']);
    $name = trim((string)$input['name']); $type = (string)$input['location_type']; $code = strtoupper(trim((string)($input['code'] ?? '')));
    $parentId = array_key_exists('parent_id', $input) && $input['parent_id'] !== null && $input['parent_id'] !== '' ? (int)$input['parent_id'] : null;
    $status = (string)($input['status'] ?? 'active');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 160 || !in_array($type, ['country','city','area'], true) || !in_array($status, ['active','inactive'], true) || !marketplaceLocationParentIsValid($db, $type, $parentId)) Http::error('validation_error', 'تحقق من اسم الموقع وتسلسله وحالته.', 422);
    if ($code !== '' && !preg_match('/^[A-Z0-9_-]{2,24}$/', $code)) Http::error('validation_error', 'رمز الموقع غير صالح.', 422, ['code' => 'غير صالح']);
    try { $db->prepare('INSERT INTO marketplace_locations (parent_id, location_type, name, code, display_order, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)')->execute([$parentId, $type, $name, $code ?: null, max(0, (int)($input['display_order'] ?? 0)), $status, $actor['id']]); }
    catch (PDOException $exception) { if ($exception->getCode() === '23000') Http::error('duplicate_location', 'الموقع أو رمزه موجود مسبقاً في المستوى نفسه.', 422); throw $exception; }
    $id = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'marketplace.location.created', 'marketplace_location', $id, null, ['name' => $name, 'type' => $type]); Http::json(['data' => marketplaceLocation($db, $id)], 201);
}

if ($method === 'PATCH' && route('/admin/marketplace/locations/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $locationId = (int)$parameters['id']; $existing = marketplaceLocation($db, $locationId); $input = Http::input();
    $name = trim((string)($input['name'] ?? $existing['name'])); $type = (string)($input['location_type'] ?? $existing['location_type']); $code = strtoupper(trim((string)($input['code'] ?? $existing['code'] ?? '')));
    $parentId = array_key_exists('parent_id', $input) ? (($input['parent_id'] === null || $input['parent_id'] === '') ? null : (int)$input['parent_id']) : ($existing['parent_id'] === null ? null : (int)$existing['parent_id']); $status = (string)($input['status'] ?? $existing['status']);
    if (mb_strlen($name) < 2 || mb_strlen($name) > 160 || !in_array($type, ['country','city','area'], true) || !in_array($status, ['active','inactive'], true) || $parentId === $locationId || !marketplaceLocationParentIsValid($db, $type, $parentId)) Http::error('validation_error', 'تحقق من اسم الموقع وتسلسله وحالته.', 422);
    $children = $db->prepare('SELECT COUNT(*) FROM marketplace_locations WHERE parent_id=?'); $children->execute([$locationId]); if ((int)$children->fetchColumn() > 0 && ($type !== $existing['location_type'] || $parentId !== ($existing['parent_id'] === null ? null : (int)$existing['parent_id']))) Http::error('location_in_use', 'لا يمكن تغيير نوع أو أب موقع يحوي مواقع فرعية.', 422);
    try { $db->prepare('UPDATE marketplace_locations SET parent_id=?, location_type=?, name=?, code=?, display_order=?, status=? WHERE id=?')->execute([$parentId, $type, $name, $code ?: null, max(0, (int)($input['display_order'] ?? $existing['display_order'])), $status, $locationId]); }
    catch (PDOException $exception) { if ($exception->getCode() === '23000') Http::error('duplicate_location', 'الموقع أو رمزه موجود مسبقاً في المستوى نفسه.', 422); throw $exception; }
    Audit::log($db, (int)$actor['id'], 'marketplace.location.updated', 'marketplace_location', $locationId, $existing, ['name' => $name, 'status' => $status]); Http::json(['data' => marketplaceLocation($db, $locationId)]);
}

if ($method === 'DELETE' && route('/admin/marketplace/locations/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $locationId = (int)$parameters['id']; marketplaceLocation($db, $locationId);
    $children = $db->prepare('SELECT COUNT(*) FROM marketplace_locations WHERE parent_id=?'); $children->execute([$locationId]); if ((int)$children->fetchColumn() > 0) Http::error('location_in_use', 'لا يمكن حذف موقع يحوي مواقع فرعية. عطّله أو انقل المواقع الفرعية أولاً.', 422);
    $db->prepare('DELETE FROM marketplace_locations WHERE id=?')->execute([$locationId]); Audit::log($db, (int)$actor['id'], 'marketplace.location.deleted', 'marketplace_location', $locationId); Http::json(['data' => ['id' => $locationId, 'deleted' => true]]);
}

if ($method === 'GET' && $path === '/marketplace/categories') {
    $includeInactive = ($_GET['include_inactive'] ?? '') === '1';
    if ($includeInactive) { $actor = user($db); role($actor, ['admin']); }
    $statement = $db->prepare('SELECT id, parent_id, name, slug, icon_key, image_path, display_order, status FROM categories_marketplace' . ($includeInactive ? '' : " WHERE status='active'") . ' ORDER BY parent_id IS NOT NULL, display_order, name, id');
    $statement->execute();
    Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'GET' && route('/marketplace/categories/{id}/attributes', $path, $parameters)) {
    $categoryId = (int)$parameters['id'];
    marketplaceCategory($db, $categoryId, true);
    Http::json(['data' => marketplaceAttributesForCategory($db, $categoryId)]);
}

if ($method === 'GET' && $path === '/admin/marketplace/categories') {
    $actor = user($db); role($actor, ['admin']);
    $statement = $db->query('SELECT c.id, c.parent_id, c.name, c.slug, c.icon_key, c.image_path, c.display_order, c.status, c.created_at, parent.name AS parent_name, COUNT(child.id) AS children_count FROM categories_marketplace c LEFT JOIN categories_marketplace parent ON parent.id=c.parent_id LEFT JOIN categories_marketplace child ON child.parent_id=c.id GROUP BY c.id, c.parent_id, c.name, c.slug, c.icon_key, c.image_path, c.display_order, c.status, c.created_at, parent.name ORDER BY c.parent_id IS NOT NULL, c.display_order, c.name, c.id');
    Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && $path === '/admin/marketplace/categories') {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['name']);
    $name = trim((string)$input['name']); $slug = trim((string)($input['slug'] ?? ''));
    $parentId = array_key_exists('parent_id', $input) && $input['parent_id'] !== null && $input['parent_id'] !== '' ? (int)$input['parent_id'] : null;
    $status = (string)($input['status'] ?? 'active');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 160) Http::error('validation_error', 'اسم القسم يجب أن يكون بين حرفين و160 حرفاً.', 422);
    if ($slug === '') {
        // الاسم العربي لا ينتج slug لاتينياً بواسطة preg_replace؛ أنشئ معرفاً داخلياً آمناً وفريداً.
        $asciiSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        $slug = $asciiSlug !== ''
            ? $asciiSlug
            : 'category-' . substr(hash('sha256', $name . '|' . ($parentId ?? 'root') . '|' . bin2hex(random_bytes(8))), 0, 16);
    }
    if (mb_strlen($slug) > 180) Http::error('validation_error', 'معرّف القسم غير صالح. يجب ألا يتجاوز 180 حرفاً.', 422);
    if (!in_array($status, ['active', 'inactive'], true)) Http::error('validation_error', 'حالة القسم غير صالحة.', 422);
    if ($parentId !== null) marketplaceCategory($db, $parentId);
    $statement = $db->prepare('INSERT INTO categories_marketplace (parent_id, name, slug, icon_key, display_order, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
    try { $statement->execute([$parentId, $name, $slug, trim((string)($input['icon_key'] ?? '')) ?: null, max(0, (int)($input['display_order'] ?? 0)), $status, $actor['id']]); }
    catch (PDOException $exception) { if ($exception->getCode() === '23000') Http::error('duplicate_category_slug', 'معرّف القسم مستخدم مسبقاً.', 422, ['slug' => 'مستخدم مسبقاً']); throw $exception; }
    $id = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'marketplace.category.created', 'marketplace_category', $id, null, ['name' => $name]);
    Http::json(['data' => marketplaceCategory($db, $id)], 201);
}

if ($method === 'PATCH' && route('/admin/marketplace/categories/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); $categoryId = (int)$parameters['id']; $existing = marketplaceCategory($db, $categoryId);
    $name = trim((string)($input['name'] ?? $existing['name'])); $slug = trim((string)($input['slug'] ?? $existing['slug']));
    $parentId = array_key_exists('parent_id', $input) ? (($input['parent_id'] === null || $input['parent_id'] === '') ? null : (int)$input['parent_id']) : ($existing['parent_id'] === null ? null : (int)$existing['parent_id']);
    $status = (string)($input['status'] ?? $existing['status']);
    if (mb_strlen($name) < 2 || mb_strlen($name) > 160 || $slug === '' || mb_strlen($slug) > 180) Http::error('validation_error', 'تحقق من اسم القسم ومعرّفه.', 422);
    if (!in_array($status, ['active', 'inactive'], true)) Http::error('validation_error', 'حالة القسم غير صالحة.', 422);
    if ($parentId === $categoryId) Http::error('validation_error', 'لا يمكن أن يكون القسم الأب هو القسم نفسه.', 422, ['parent_id' => 'غير صالح']);
    if ($parentId !== null) {
        $parentChain = marketplaceCategoryChain($db, $parentId, false);
        foreach ($parentChain as $ancestor) if ((int)$ancestor['id'] === $categoryId) Http::error('validation_error', 'لا يمكن نقل القسم داخل أحد أقسامه الفرعية.', 422, ['parent_id' => 'غير صالح']);
    }
    try { $db->prepare('UPDATE categories_marketplace SET parent_id=?, name=?, slug=?, icon_key=?, display_order=?, status=? WHERE id=?')->execute([$parentId, $name, $slug, trim((string)($input['icon_key'] ?? $existing['icon_key'])) ?: null, max(0, (int)($input['display_order'] ?? $existing['display_order'])), $status, $categoryId]); }
    catch (PDOException $exception) { if ($exception->getCode() === '23000') Http::error('duplicate_category_slug', 'معرّف القسم مستخدم مسبقاً.', 422, ['slug' => 'مستخدم مسبقاً']); throw $exception; }
    Audit::log($db, (int)$actor['id'], 'marketplace.category.updated', 'marketplace_category', $categoryId, $existing, ['name' => $name, 'status' => $status]); Http::json(['data' => marketplaceCategory($db, $categoryId)]);
}

if ($method === 'POST' && route('/admin/marketplace/categories/{id}/image', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $categoryId = (int)$parameters['id']; marketplaceCategory($db, $categoryId);
    $imagePath = saveUpload('image', (int)Database::environment('MAX_MARKETPLACE_CATEGORY_IMAGE_BYTES', '3145728'), 'marketplace_category');
    $db->prepare('UPDATE categories_marketplace SET image_path=? WHERE id=?')->execute([$imagePath, $categoryId]); Audit::log($db, (int)$actor['id'], 'marketplace.category.image.updated', 'marketplace_category', $categoryId); Http::json(['data' => ['id' => $categoryId, 'image_path' => $imagePath]]);
}

if ($method === 'DELETE' && route('/admin/marketplace/categories/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $categoryId = (int)$parameters['id']; marketplaceCategory($db, $categoryId);
    $children = $db->prepare('SELECT COUNT(*) FROM categories_marketplace WHERE parent_id=?'); $children->execute([$categoryId]);
    $listings = $db->prepare('SELECT COUNT(*) FROM listings_marketplace WHERE category_id=?'); $listings->execute([$categoryId]);
    if ((int)$children->fetchColumn() > 0 || (int)$listings->fetchColumn() > 0) Http::error('category_in_use', 'لا يمكن حذف قسم يحوي أقساماً فرعية أو إعلانات. عطّله أو انقل المحتوى أولاً.', 422);
    $db->prepare('DELETE FROM categories_marketplace WHERE id=?')->execute([$categoryId]); Audit::log($db, (int)$actor['id'], 'marketplace.category.deleted', 'marketplace_category', $categoryId); Http::json(['data' => ['id' => $categoryId, 'deleted' => true]]);
}

if ($method === 'GET' && route('/admin/marketplace/categories/{id}/attributes', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $categoryId = (int)$parameters['id']; marketplaceCategory($db, $categoryId);
    Http::json(['data' => marketplaceAttributesForCategory($db, $categoryId, false)]);
}

if ($method === 'POST' && route('/admin/marketplace/categories/{id}/attributes', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $categoryId = (int)$parameters['id']; marketplaceCategory($db, $categoryId); $input = Http::input(); Http::requireFields($input, ['attribute_key', 'label', 'field_type']);
    $attributeKey = trim((string)$input['attribute_key']); $label = trim((string)$input['label']); $fieldType = (string)$input['field_type'];
    $allowedTypes = ['text','number','select','multi_select','boolean','date','year','price','textarea'];
    if (!preg_match('/^[a-z][a-z0-9_]{1,99}$/', $attributeKey) || mb_strlen($label) < 1 || mb_strlen($label) > 160 || !in_array($fieldType, $allowedTypes, true)) Http::error('validation_error', 'تحقق من مفتاح الخاصية واسمها ونوع الحقل.', 422);
    $statement = $db->prepare('INSERT INTO category_attributes_marketplace (category_id, attribute_key, label, field_type, placeholder, help_text, is_required, applies_to_descendants, display_order, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    try { $statement->execute([$categoryId, $attributeKey, $label, $fieldType, trim((string)($input['placeholder'] ?? '')) ?: null, trim((string)($input['help_text'] ?? '')) ?: null, !empty($input['is_required']) ? 1 : 0, !empty($input['applies_to_descendants']) ? 1 : 0, max(0, (int)($input['display_order'] ?? 0)), in_array(($input['status'] ?? 'active'), ['active','inactive'], true) ? $input['status'] : 'active', $actor['id']]); }
    catch (PDOException $exception) { if ($exception->getCode() === '23000') Http::error('duplicate_attribute_key', 'مفتاح الخاصية مستخدم مسبقاً في هذا القسم.', 422, ['attribute_key' => 'مستخدم مسبقاً']); throw $exception; }
    $id = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'marketplace.attribute.created', 'marketplace_category_attribute', $id, null, ['category_id' => $categoryId, 'attribute_key' => $attributeKey]); Http::json(['data' => ['id' => $id]], 201);
}

if ($method === 'PATCH' && route('/admin/marketplace/attributes/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $attributeId = (int)$parameters['id']; $input = Http::input();
    $statement = $db->prepare('SELECT * FROM category_attributes_marketplace WHERE id=?'); $statement->execute([$attributeId]); $existing = $statement->fetch(); if (!$existing) Http::error('not_found', 'خاصية الحراج غير موجودة.', 404);
    $attributeKey = trim((string)($input['attribute_key'] ?? $existing['attribute_key'])); $label = trim((string)($input['label'] ?? $existing['label'])); $fieldType = (string)($input['field_type'] ?? $existing['field_type']);
    $allowedTypes = ['text','number','select','multi_select','boolean','date','year','price','textarea'];
    if (!preg_match('/^[a-z][a-z0-9_]{1,99}$/', $attributeKey) || mb_strlen($label) < 1 || mb_strlen($label) > 160 || !in_array($fieldType, $allowedTypes, true)) Http::error('validation_error', 'تحقق من مفتاح الخاصية واسمها ونوع الحقل.', 422);
    try { $db->prepare('UPDATE category_attributes_marketplace SET attribute_key=?, label=?, field_type=?, placeholder=?, help_text=?, is_required=?, applies_to_descendants=?, display_order=?, status=? WHERE id=?')->execute([$attributeKey, $label, $fieldType, trim((string)($input['placeholder'] ?? $existing['placeholder'])) ?: null, trim((string)($input['help_text'] ?? $existing['help_text'])) ?: null, array_key_exists('is_required', $input) ? (!empty($input['is_required']) ? 1 : 0) : $existing['is_required'], array_key_exists('applies_to_descendants', $input) ? (!empty($input['applies_to_descendants']) ? 1 : 0) : $existing['applies_to_descendants'], max(0, (int)($input['display_order'] ?? $existing['display_order'])), in_array(($input['status'] ?? $existing['status']), ['active','inactive'], true) ? ($input['status'] ?? $existing['status']) : $existing['status'], $attributeId]); }
    catch (PDOException $exception) { if ($exception->getCode() === '23000') Http::error('duplicate_attribute_key', 'مفتاح الخاصية مستخدم مسبقاً في هذا القسم.', 422, ['attribute_key' => 'مستخدم مسبقاً']); throw $exception; }
    Audit::log($db, (int)$actor['id'], 'marketplace.attribute.updated', 'marketplace_category_attribute', $attributeId); Http::json(['data' => ['id' => $attributeId]]);
}

if ($method === 'DELETE' && route('/admin/marketplace/attributes/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $attributeId = (int)$parameters['id'];
    $used = $db->prepare('SELECT COUNT(*) FROM listing_attribute_values_marketplace WHERE category_attribute_id=?'); $used->execute([$attributeId]); if ((int)$used->fetchColumn() > 0) Http::error('attribute_in_use', 'لا يمكن حذف خاصية مستخدمة في إعلانات. عطّلها للحفاظ على بيانات الإعلانات.', 422);
    $statement = $db->prepare('DELETE FROM category_attributes_marketplace WHERE id=?'); $statement->execute([$attributeId]); if ($statement->rowCount() !== 1) Http::error('not_found', 'خاصية الحراج غير موجودة.', 404); Audit::log($db, (int)$actor['id'], 'marketplace.attribute.deleted', 'marketplace_category_attribute', $attributeId); Http::json(['data' => ['id' => $attributeId, 'deleted' => true]]);
}

if ($method === 'POST' && route('/admin/marketplace/attributes/{id}/options', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $attributeId = (int)$parameters['id']; $input = Http::input(); Http::requireFields($input, ['option_key', 'label']);
    $attribute = $db->prepare('SELECT field_type FROM category_attributes_marketplace WHERE id=?'); $attribute->execute([$attributeId]); $attribute = $attribute->fetch(); if (!$attribute) Http::error('not_found', 'خاصية الحراج غير موجودة.', 404);
    if (!in_array($attribute['field_type'], ['select', 'multi_select'], true)) Http::error('validation_error', 'الخيارات متاحة فقط لحقول الاختيار.', 422);
    $optionKey = trim((string)$input['option_key']); $label = trim((string)$input['label']); if (!preg_match('/^[a-z][a-z0-9_]{1,99}$/', $optionKey) || $label === '' || mb_strlen($label) > 160) Http::error('validation_error', 'تحقق من مفتاح الخيار واسمه.', 422);
    try { $db->prepare('INSERT INTO category_attribute_options_marketplace (attribute_id, option_key, label, display_order, status) VALUES (?, ?, ?, ?, ?)')->execute([$attributeId, $optionKey, $label, max(0, (int)($input['display_order'] ?? 0)), in_array(($input['status'] ?? 'active'), ['active','inactive'], true) ? $input['status'] : 'active']); }
    catch (PDOException $exception) { if ($exception->getCode() === '23000') Http::error('duplicate_option_key', 'مفتاح الخيار مستخدم مسبقاً في هذه الخاصية.', 422, ['option_key' => 'مستخدم مسبقاً']); throw $exception; }
    $id = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'marketplace.attribute.option.created', 'marketplace_attribute_option', $id, null, ['attribute_id' => $attributeId]); Http::json(['data' => ['id' => $id]], 201);
}

if ($method === 'PATCH' && route('/admin/marketplace/attribute-options/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $optionId = (int)$parameters['id']; $input = Http::input(); $statement = $db->prepare('SELECT * FROM category_attribute_options_marketplace WHERE id=?'); $statement->execute([$optionId]); $existing = $statement->fetch(); if (!$existing) Http::error('not_found', 'خيار الخاصية غير موجود.', 404);
    $optionKey = trim((string)($input['option_key'] ?? $existing['option_key'])); $label = trim((string)($input['label'] ?? $existing['label'])); if (!preg_match('/^[a-z][a-z0-9_]{1,99}$/', $optionKey) || $label === '' || mb_strlen($label) > 160) Http::error('validation_error', 'تحقق من مفتاح الخيار واسمه.', 422);
    try { $db->prepare('UPDATE category_attribute_options_marketplace SET option_key=?, label=?, display_order=?, status=? WHERE id=?')->execute([$optionKey, $label, max(0, (int)($input['display_order'] ?? $existing['display_order'])), in_array(($input['status'] ?? $existing['status']), ['active','inactive'], true) ? ($input['status'] ?? $existing['status']) : $existing['status'], $optionId]); }
    catch (PDOException $exception) { if ($exception->getCode() === '23000') Http::error('duplicate_option_key', 'مفتاح الخيار مستخدم مسبقاً في هذه الخاصية.', 422, ['option_key' => 'مستخدم مسبقاً']); throw $exception; }
    Audit::log($db, (int)$actor['id'], 'marketplace.attribute.option.updated', 'marketplace_attribute_option', $optionId); Http::json(['data' => ['id' => $optionId]]);
}

if ($method === 'DELETE' && route('/admin/marketplace/attribute-options/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $optionId = (int)$parameters['id']; $statement = $db->prepare('DELETE FROM category_attribute_options_marketplace WHERE id=?'); $statement->execute([$optionId]); if ($statement->rowCount() !== 1) Http::error('not_found', 'خيار الخاصية غير موجود.', 404); Audit::log($db, (int)$actor['id'], 'marketplace.attribute.option.deleted', 'marketplace_attribute_option', $optionId); Http::json(['data' => ['id' => $optionId, 'deleted' => true]]);
}

if ($method === 'GET' && $path === '/merchant/context') {
    $actor = user($db); role($actor, ['merchant']);
    $hasVisitEvents = schemaTableExists($db, 'store_visit_events');
    $hasProductViews = schemaColumnExists($db, 'products', 'view_count');
    $hasCostPrice = schemaColumnExists($db, 'products', 'cost_price');
    $hasPreorderUnit = schemaColumnExists($db, 'products', 'preorder_unit_label');
    $hasPreorderMinimum = schemaColumnExists($db, 'products', 'preorder_min_quantity');
    $hasPreorderLead = schemaColumnExists($db, 'products', 'preorder_lead_days');
    $storeViewsSql = $hasVisitEvents ? '(SELECT COUNT(*) FROM store_visit_events sv WHERE sv.store_id=s.id)' : '0';
    $storeHoursSql = schemaColumnExists($db, 'stores', 'opening_hours_json') ? 's.opening_hours_json' : 'NULL';
    $productViewsSql = $hasProductViews ? 'p.view_count' : '0';
    $costPriceSql = $hasCostPrice ? 'p.cost_price' : '0';
    $preorderUnitSql = $hasPreorderUnit ? 'p.preorder_unit_label' : 'NULL';
    $preorderMinimumSql = $hasPreorderMinimum ? 'p.preorder_min_quantity' : 'NULL';
    $preorderLeadSql = $hasPreorderLead ? 'p.preorder_lead_days' : 'NULL';
    $stores = $db->prepare("SELECT s.id, s.name, s.description, s.logo_path, s.phone, s.store_type, s.status, s.is_verified, s.created_at, {$storeHoursSql} AS opening_hours_json, (SELECT COUNT(*) FROM products p_count WHERE p_count.store_id=s.id AND p_count.status='active') AS product_count, {$storeViewsSql} AS total_views, COALESCE(mp.sham_cash_address, '') AS sham_cash_address FROM stores s LEFT JOIN merchant_profiles mp ON mp.user_id=s.merchant_id WHERE s.merchant_id=:merchant ORDER BY s.created_at DESC");
    $stores->execute(['merchant' => $actor['id']]); $storeRows = $stores->fetchAll();
    $products = $db->prepare("SELECT p.id, p.store_id, p.name, p.description, p.price, {$costPriceSql} AS cost_price, p.currency, p.stock_quantity, {$preorderUnitSql} AS preorder_unit_label, {$preorderMinimumSql} AS preorder_min_quantity, {$preorderLeadSql} AS preorder_lead_days, p.delivery_fee, p.rating_avg, p.rating_count, p.sales_count, {$productViewsSql} AS view_count, p.status, s.name AS store_name, s.store_type, s.is_verified AS store_is_verified, (SELECT file_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY sort_order, id LIMIT 1) AS image_path FROM products p JOIN stores s ON s.id=p.store_id WHERE s.merchant_id=:merchant ORDER BY p.created_at DESC LIMIT 100");
    $products->execute(['merchant' => $actor['id']]);
    $orders = $db->prepare("SELECT o.id, o.order_number, o.status, o.currency, o.fulfillment_type, o.products_subtotal, o.delivery_total, o.platform_fee_total, o.grand_total, o.delivery_address_text, o.created_at, o.updated_at, MAX(customer.full_name) AS customer_name, MAX(customer.phone) AS customer_phone, MAX(pr.id) AS payment_receipt_id, MAX(pr.transaction_number) AS payment_transaction_number, MAX(pr.status) AS payment_receipt_status, MAX(pr.merchant_review_status) AS merchant_review_status, MAX(pr.merchant_review_note) AS merchant_review_note, MAX(pr.created_at) AS payment_submitted_at, MAX(dt.id) AS delivery_task_id, MAX(dt.status) AS delivery_task_status, MAX(dt.eta_value) AS courier_eta_value, MAX(dt.eta_unit) AS courier_eta_unit, MAX(dt.accepted_at) AS delivery_accepted_at, MAX(dt.proof_submitted_at) AS delivery_proof_submitted_at, MAX(dt.reviewed_at) AS delivery_reviewed_at, COALESCE(MAX(dt.pickup_proof_image_path), MAX(dt.proof_image_path)) AS pickup_proof_image_path, MAX(dt.delivery_proof_image_path) AS delivery_proof_image_path, MAX(dt.courier_delivery_proof_image_path) AS courier_delivery_proof_image_path, MAX(dt.customer_proof_image_path) AS customer_proof_image_path, MAX(courier.full_name) AS courier_name, MAX(courier.phone) AS courier_phone, GROUP_CONCAT(CONCAT(oi.product_name_snapshot, ' × ', oi.quantity) , '، ') AS products FROM orders o JOIN stores s ON s.id=o.store_id JOIN users customer ON customer.id=o.customer_id LEFT JOIN payment_receipts pr ON pr.order_id=o.id LEFT JOIN delivery_tasks dt ON dt.order_id=o.id LEFT JOIN users courier ON courier.id=dt.courier_id LEFT JOIN order_items oi ON oi.order_id=o.id WHERE s.merchant_id=:merchant GROUP BY o.id, o.order_number, o.status, o.currency, o.fulfillment_type, o.products_subtotal, o.delivery_total, o.platform_fee_total, o.grand_total, o.delivery_address_text, o.created_at, o.updated_at ORDER BY o.created_at DESC LIMIT 100");
    $orders->execute(['merchant' => $actor['id']]);
    $preorders = $db->prepare("SELECT po.id, po.preorder_number, po.status, po.currency, po.product_name_snapshot, po.unit_label_snapshot, po.requested_quantity, po.grand_total, po.requested_fulfillment_at, po.merchant_reminder_hours, po.merchant_payment_review_status, po.merchant_payment_review_note, po.created_at, customer.full_name AS customer_name, customer.phone AS customer_phone, (SELECT ppr.id FROM preorder_payment_receipts ppr WHERE ppr.preorder_id=po.id ORDER BY ppr.created_at DESC, ppr.id DESC LIMIT 1) AS payment_receipt_id, (SELECT ppr.transaction_number FROM preorder_payment_receipts ppr WHERE ppr.preorder_id=po.id ORDER BY ppr.created_at DESC, ppr.id DESC LIMIT 1) AS payment_transaction_number, (SELECT ppr.status FROM preorder_payment_receipts ppr WHERE ppr.preorder_id=po.id ORDER BY ppr.created_at DESC, ppr.id DESC LIMIT 1) AS payment_receipt_status, (SELECT ppr.created_at FROM preorder_payment_receipts ppr WHERE ppr.preorder_id=po.id ORDER BY ppr.created_at DESC, ppr.id DESC LIMIT 1) AS payment_submitted_at FROM preorder_orders po JOIN stores s ON s.id=po.store_id JOIN users customer ON customer.id=po.customer_id WHERE s.merchant_id=:merchant ORDER BY po.requested_fulfillment_at ASC, po.id DESC LIMIT 100");
    $preorders->execute(['merchant' => $actor['id']]);
    $subscriptions = $db->prepare("SELECT CONCAT(css.courier_id, ':', css.store_id) AS subscription_key, css.store_id, css.courier_id, css.status, css.created_at, s.name AS store_name, u.full_name AS courier_name, u.phone AS courier_phone, cp.verification_status AS courier_verification_status FROM courier_store_subscriptions css JOIN stores s ON s.id=css.store_id AND s.merchant_id=:merchant JOIN users u ON u.id=css.courier_id LEFT JOIN courier_profiles cp ON cp.user_id=css.courier_id ORDER BY CASE css.status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 ELSE 2 END, css.created_at DESC");
    $subscriptions->execute(['merchant' => $actor['id']]);
    $productRows = $products->fetchAll();
    $subscriptionRows = $subscriptions->fetchAll();
    Http::json(['data' => [
        'stores' => $storeRows,
        'store' => $storeRows[0] ?? null,
        'products' => $productRows,
        'merchant_products' => $productRows,
        'orders' => $orders->fetchAll(),
        'preorders' => $preorders->fetchAll(),
        'courier_subscriptions' => $subscriptionRows,
        'courierSubscriptions' => $subscriptionRows,
    ]]);
}

if ($method === 'GET' && $path === '/merchant/courier-subscriptions') {
    $actor = user($db); role($actor, ['merchant']);
    Http::json(['data' => [], 'subscriptions_disabled' => true]);
}

if ($method === 'POST' && route('/merchant/courier-subscriptions/{courier_id}/{store_id}/decision', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); Http::error('subscriptions_disabled', 'تم إلغاء طلبات اشتراك عمال التوصيل. تظهر المهام الجاهزة لجميع العمال الموثقين.', 410); $input = Http::input(); $decision = (string)($input['decision'] ?? '');
    if (!in_array($decision, ['approved', 'rejected'], true)) Http::error('validation_error', 'قرار الاشتراك غير صالح.', 422);
    $statement = $db->prepare("UPDATE courier_store_subscriptions SET status=:status WHERE courier_id=:courier_id AND store_id=:store_id AND status='pending' AND store_id IN (SELECT id FROM stores WHERE merchant_id=:merchant)");
    $statement->execute(['merchant' => $actor['id'], 'status' => $decision === 'approved' ? 'active' : 'rejected', 'courier_id' => (int)$parameters['courier_id'], 'store_id' => (int)$parameters['store_id']]);
    if ($statement->rowCount() !== 1) Http::error('subscription_unavailable', 'طلب الاشتراك غير موجود أو تمت معالجته مسبقاً.', 409);
    Audit::log($db, (int)$actor['id'], 'merchant.courier_subscription.' . $decision, 'courier_store_subscription', null, null, ['courier_id' => (int)$parameters['courier_id'], 'store_id' => (int)$parameters['store_id']]);
    Http::json(['data' => ['courier_id' => (int)$parameters['courier_id'], 'store_id' => (int)$parameters['store_id'], 'status' => $decision === 'approved' ? 'active' : 'rejected']]);
}

if ($method === 'POST' && $path === '/merchant/stores') {
    $actor = user($db); role($actor, ['merchant']); $input = Http::input(); Http::requireFields($input, ['name']);
    $name = trim((string)$input['name']); if (mb_strlen($name) < 3 || mb_strlen($name) > 160) Http::error('validation_error', 'اسم المتجر يجب أن يكون بين 3 و160 حرفاً.', 422);
    $storeType = (string)($input['store_type'] ?? 'retail'); if (!in_array($storeType, ['retail','preorder'], true)) Http::error('validation_error', 'نوع المتجر غير صالح.', 422);
    $hoursJson = schemaColumnExists($db, 'stores', 'opening_hours_json') ? normalizeStoreHours($input['opening_hours'] ?? null) : null;
    $columns = 'merchant_id, name, description, phone, store_type, status, is_verified'; $values = ':merchant, :name, :description, :phone, :store_type, \'active\', FALSE';
    $params = ['merchant' => $actor['id'], 'name' => $name, 'description' => trim((string)($input['description'] ?? '')) ?: null, 'phone' => trim((string)($input['phone'] ?? '')) ?: null, 'store_type' => $storeType];
    if ($hoursJson !== null) { $columns .= ', opening_hours_json'; $values .= ', :opening_hours_json'; $params['opening_hours_json'] = $hoursJson; }
    $statement = $db->prepare("INSERT INTO stores ($columns) VALUES ($values)");
    $statement->execute($params);
    $id = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'merchant.store.created', 'store', $id, null, ['name' => $name]);
    Http::json(['data' => ['id' => $id, 'name' => $name, 'description' => trim((string)($input['description'] ?? '')) ?: null, 'store_type' => $storeType, 'opening_hours_json' => $hoursJson, 'logo_path' => null, 'is_verified' => false, 'status' => 'active']], 201);
}

if ($method === 'PATCH' && route('/merchant/stores/{id}/details', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); $input = Http::input(); $storeId = (int)$parameters['id'];
    $store = $db->prepare('SELECT id, name, description, phone, store_type FROM stores WHERE id=? AND merchant_id=?'); $store->execute([$storeId, $actor['id']]); $store = $store->fetch(); if (!$store) Http::error('forbidden', 'لا تملك هذا المتجر.', 403);
    $name = trim((string)($input['name'] ?? $store['name'])); $description = trim((string)($input['description'] ?? $store['description'])); $phone = trim((string)($input['phone'] ?? $store['phone']));
    $storeType = (string)($input['store_type'] ?? $store['store_type']);
    if (mb_strlen($name) < 3 || mb_strlen($name) > 160) Http::error('validation_error', 'اسم المتجر يجب أن يكون بين 3 و160 حرفاً.', 422);
    if (mb_strlen($description) > 5000 || !in_array($storeType, ['retail','preorder'], true)) Http::error('validation_error', 'تحقق من وصف المتجر ونمط البيع.', 422);
    $hoursJson = schemaColumnExists($db, 'stores', 'opening_hours_json') ? normalizeStoreHours($input['opening_hours'] ?? null) : null;
    if ($hoursJson !== null) $db->prepare('UPDATE stores SET name=?, description=?, phone=?, store_type=?, opening_hours_json=? WHERE id=?')->execute([$name, $description ?: null, $phone ?: null, $storeType, $hoursJson, $storeId]);
    else $db->prepare('UPDATE stores SET name=?, description=?, phone=?, store_type=? WHERE id=?')->execute([$name, $description ?: null, $phone ?: null, $storeType, $storeId]);
    Audit::log($db, (int)$actor['id'], 'merchant.store.details.updated', 'store', $storeId); Http::json(['data' => ['id' => $storeId, 'name' => $name, 'description' => $description ?: null, 'phone' => $phone ?: null, 'store_type' => $storeType, 'opening_hours_json' => $hoursJson]]);
}

if ($method === 'POST' && route('/merchant/stores/{id}/logo', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); $storeId = (int)$parameters['id']; $exists = $db->prepare('SELECT id FROM stores WHERE id=? AND merchant_id=?'); $exists->execute([$storeId, $actor['id']]); if (!$exists->fetch()) Http::error('forbidden', 'لا تملك هذا المتجر.', 403);
    $logo = saveUpload('image', (int)Database::environment('MAX_STORE_LOGO_BYTES', '3145728'), 'store_logo'); $db->prepare('UPDATE stores SET logo_path=? WHERE id=?')->execute([$logo, $storeId]); Audit::log($db, (int)$actor['id'], 'merchant.store.logo.updated', 'store', $storeId); Http::json(['data' => ['id' => $storeId, 'logo_path' => $logo]]);
}

$parameters = [];
if ($method === 'POST' && route('/merchant/products', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); $input = Http::input();
    Http::requireFields($input, ['store_id', 'name', 'price', 'cost_price', 'currency', 'stock_quantity', 'delivery_fee']);
    $store = $db->prepare('SELECT id, store_type FROM stores WHERE id=:id AND merchant_id=:merchant LIMIT 1'); $store->execute(['id' => $input['store_id'], 'merchant' => $actor['id']]); $store = $store->fetch(); if (!$store) Http::error('forbidden', 'المتجر غير تابع لهذا الحساب.', 403);
    if (!in_array($input['currency'], ['USD', 'SYP'], true)) Http::error('validation_error', 'العملة يجب أن تكون USD أو SYP.', 422);
    if ((float)$input['price'] <= 0 || (float)$input['cost_price'] < 0 || (float)$input['delivery_fee'] < 0) Http::error('validation_error', 'تحقق من سعر البيع ورأس المال ورسوم التوصيل.', 422);
    $offerPrice = array_key_exists('offer_price', $input) && $input['offer_price'] !== null && $input['offer_price'] !== '' ? (float)$input['offer_price'] : null;
    $offerMinutes = (int)($input['offer_duration_minutes'] ?? 0);
    if ($offerPrice !== null && ($offerPrice <= 0 || $offerPrice >= (float)$input['price'] || $offerMinutes < 1)) Http::error('validation_error', 'سعر العرض يجب أن يكون أقل من السعر الأصلي مع مدة صحيحة.', 422);
    $offerEndsAt = $offerPrice !== null ? gmdate('Y-m-d H:i:s', time() + ($offerMinutes * 60)) : null;
    $unitLabel = trim((string)($input['preorder_unit_label'] ?? '')) ?: null; $minimum = array_key_exists('preorder_min_quantity', $input) ? (float)$input['preorder_min_quantity'] : null; $leadDays = array_key_exists('preorder_lead_days', $input) ? (int)$input['preorder_lead_days'] : null;
    if ($store['store_type'] === 'preorder' && ($unitLabel === null || $minimum === null || $minimum <= 0 || $leadDays === null || $leadDays < 0)) Http::error('validation_error', 'حدد وحدة المادة والحد الأدنى للكمية ومدة تنفيذ الطلب المسبق.', 422);
    $hasOfferColumns = schemaColumnExists($db, 'products', 'offer_price') && schemaColumnExists($db, 'products', 'offer_starts_at') && schemaColumnExists($db, 'products', 'offer_ends_at');
    $hasCostPrice = schemaColumnExists($db, 'products', 'cost_price');
    $hasPreorderColumns = schemaColumnExists($db, 'products', 'preorder_unit_label') && schemaColumnExists($db, 'products', 'preorder_min_quantity') && schemaColumnExists($db, 'products', 'preorder_lead_days');
    if ($offerPrice !== null && !$hasOfferColumns) Http::error('schema_not_ready', 'قاعدة البيانات تحتاج ترحيل أعمدة العروض قبل نشر منتج بعرض مؤقت.', 503);
    if (!$hasCostPrice) Http::error('schema_not_ready', 'قاعدة البيانات تحتاج ترحيل عمود رأس المال قبل نشر المنتجات.', 503);
    if ($store['store_type'] === 'preorder' && !$hasPreorderColumns) Http::error('schema_not_ready', 'قاعدة البيانات تحتاج ترحيل أعمدة الطلب المسبق لهذا المتجر.', 503);
    $columns = ['store_id', 'category_id', 'name', 'description', 'price', 'cost_price', 'currency', 'stock_quantity', 'delivery_fee', 'status'];
    $values = [':store', ':category', ':name', ':description', ':price', ':cost_price', ':currency', ':stock', ':delivery', "'active'"];
    $params = ['store' => $input['store_id'], 'category' => $input['category_id'] ?? null, 'name' => trim($input['name']), 'description' => $input['description'] ?? null, 'price' => $input['price'], 'cost_price' => $input['cost_price'], 'currency' => $input['currency'], 'stock' => $input['stock_quantity'], 'delivery' => $input['delivery_fee']];
    if ($hasOfferColumns) {
        $columns = array_merge(array_slice($columns, 5, 0), ['offer_price', 'offer_starts_at', 'offer_ends_at'], $columns);
        $values = array_merge(array_slice($values, 5, 0), [':offer_price', 'UTC_TIMESTAMP()', ':offer_ends_at'], $values);
        $params['offer_price'] = $offerPrice; $params['offer_ends_at'] = $offerEndsAt;
    }
    if ($hasPreorderColumns) {
        $deliveryIndex = array_search('delivery_fee', $columns, true);
        array_splice($columns, $deliveryIndex, 0, ['preorder_unit_label', 'preorder_min_quantity', 'preorder_lead_days']);
        array_splice($values, $deliveryIndex, 0, [':unit_label', ':minimum', ':lead_days']);
        $params['unit_label'] = $unitLabel; $params['minimum'] = $minimum; $params['lead_days'] = $leadDays;
    }
    $statement = $db->prepare('INSERT INTO products (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')');
    $statement->execute($params);
    $id = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'product.created', 'product', $id, null, ['name' => $input['name']]); Http::json(['data' => ['id' => $id]], 201);
}

if ($method === 'PATCH' && route('/merchant/products/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); $input = Http::input(); $productId = (int)$parameters['id'];
    $statement = $db->prepare('SELECT p.*, s.store_type FROM products p JOIN stores s ON s.id=p.store_id WHERE p.id=:id AND s.merchant_id=:merchant LIMIT 1'); $statement->execute(['id' => $productId, 'merchant' => $actor['id']]); $existing = $statement->fetch(); if (!$existing) Http::error('not_found', 'المنتج غير موجود ضمن متجرك.', 404);
    $currency = $input['currency'] ?? $existing['currency']; $status = $input['status'] ?? $existing['status'];
    if (!in_array($currency, ['USD','SYP'], true) || !in_array($status, ['draft','active','hidden','out_of_stock'], true)) Http::error('validation_error', 'إعدادات المنتج غير صحيحة.', 422);
    $price = array_key_exists('price', $input) ? (float)$input['price'] : (float)$existing['price']; $cost = array_key_exists('cost_price', $input) ? (float)$input['cost_price'] : (float)$existing['cost_price']; $delivery = array_key_exists('delivery_fee', $input) ? (float)$input['delivery_fee'] : (float)$existing['delivery_fee']; $stock = array_key_exists('stock_quantity', $input) ? (int)$input['stock_quantity'] : (int)$existing['stock_quantity'];
    if ($price <= 0 || $cost < 0 || $delivery < 0 || $stock < 0) Http::error('validation_error', 'تحقق من الأسعار والمخزون ورسوم التوصيل.', 422);
    $offerPrice = array_key_exists('offer_price', $input) && $input['offer_price'] !== null && $input['offer_price'] !== '' ? (float)$input['offer_price'] : null;
    $offerMinutes = (int)($input['offer_duration_minutes'] ?? 0);
    if ($offerPrice !== null && ($offerPrice <= 0 || $offerPrice >= $price || $offerMinutes < 1)) Http::error('validation_error', 'سعر العرض يجب أن يكون أقل من السعر الأصلي مع مدة صحيحة.', 422);
    $offerStartsAt = $offerPrice !== null ? gmdate('Y-m-d H:i:s') : null;
    $offerEndsAt = $offerPrice !== null ? gmdate('Y-m-d H:i:s', time() + ($offerMinutes * 60)) : null;
    $name = trim((string)($input['name'] ?? $existing['name'])); if (mb_strlen($name) < 2 || mb_strlen($name) > 180) Http::error('validation_error', 'اسم المنتج غير صالح.', 422);
    $unitLabel = trim((string)($input['preorder_unit_label'] ?? $existing['preorder_unit_label'])) ?: null; $minimum = array_key_exists('preorder_min_quantity', $input) ? (float)$input['preorder_min_quantity'] : ($existing['preorder_min_quantity'] === null ? null : (float)$existing['preorder_min_quantity']); $leadDays = array_key_exists('preorder_lead_days', $input) ? (int)$input['preorder_lead_days'] : ($existing['preorder_lead_days'] === null ? null : (int)$existing['preorder_lead_days']);
    if ($existing['store_type'] === 'preorder' && ($unitLabel === null || $minimum === null || $minimum <= 0 || $leadDays === null || $leadDays < 0)) Http::error('validation_error', 'حدد وحدة المادة والحد الأدنى للكمية ومدة تنفيذ الطلب المسبق.', 422);
    $db->prepare('UPDATE products SET name=:name, description=:description, price=:price, offer_price=:offer_price, offer_starts_at=:offer_starts_at, offer_ends_at=:offer_ends_at, cost_price=:cost, currency=:currency, stock_quantity=:stock, preorder_unit_label=:unit_label, preorder_min_quantity=:minimum, preorder_lead_days=:lead_days, delivery_fee=:delivery, status=:status WHERE id=:id')->execute(['name' => $name, 'description' => trim((string)($input['description'] ?? $existing['description'])) ?: null, 'price' => $price, 'offer_price' => $offerPrice, 'offer_starts_at' => $offerStartsAt, 'offer_ends_at' => $offerEndsAt, 'cost' => $cost, 'currency' => $currency, 'stock' => $stock, 
'unit_label' => $unitLabel, 'minimum' => $minimum, 'lead_days' => $leadDays, 'delivery' => $delivery, 'status' => $status, 'id' => $productId]);
    Audit::log($db, (int)$actor['id'], 'product.updated', 'product', $productId, ['status' => $existing['status']], ['status' => $status]); Http::json(['data' => ['id' => $productId, 'status' => $status]]);
}

if ($method === 'DELETE' && route('/merchant/products/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); $productId = (int)$parameters['id'];
    $statement = $db->prepare("UPDATE products SET status='hidden' WHERE id=:id AND status<>'hidden' AND store_id IN (SELECT id FROM stores WHERE merchant_id=:merchant)"); $statement->execute(['id' => $productId, 'merchant' => $actor['id']]); if ($statement->rowCount() !== 1) Http::error('not_found', 'المنتج غير موجود أو محذوف مسبقاً.', 404); Audit::log($db, (int)$actor['id'], 'product.hidden', 'product', $productId); Http::json(['data' => ['id' => $productId, 'deleted' => true]]);
}

if ($method === 'PATCH' && route('/merchant/orders/{id}/status', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); $input = Http::input(); Http::requireFields($input, ['status']);
    $orderId = (int)$parameters['id']; $target = (string)$input['status'];
    $db->beginTransaction();
    try {
        $statement = $db->prepare('SELECT o.id, o.status, o.fulfillment_type, o.currency, o.delivery_total FROM orders o JOIN stores s ON s.id=o.store_id WHERE o.id=:id AND s.merchant_id=:merchant FOR UPDATE');
        $statement->execute(['id' => $orderId, 'merchant' => $actor['id']]); $order = $statement->fetch();
        if (!$order) Http::error('not_found', 'الطلب غير موجود ضمن متجرك.', 404);
        $next = $order['status'] === 'paid' ? 'preparing' : ($order['status'] === 'preparing' ? ($order['fulfillment_type'] === 'delivery' ? 'ready_for_delivery' : 'delivered') : null);
        if ($next === null || $target !== $next) Http::error('invalid_transition', 'لا يمكن تحديث حالة الطلب بهذه الطريقة.', 422);
        $db->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$target, $orderId]);
        if ($target === 'ready_for_delivery') {
            $task = $db->prepare('SELECT id, status FROM delivery_tasks WHERE order_id=? FOR UPDATE');
            $task->execute([$orderId]);
            $existingTask = $task->fetch();
            if (!$existingTask) {
                $db->prepare("INSERT INTO delivery_tasks (order_id, delivery_fee, currency, status) VALUES (?, ?, ?, 'available')")
                    ->execute([$orderId, $order['delivery_total'], $order['currency']]);
            } else {
                $db->prepare("UPDATE delivery_tasks SET delivery_fee=?, currency=?, status=CASE WHEN status='rejected' THEN 'available' ELSE status END, courier_id=CASE WHEN status='rejected' THEN NULL ELSE courier_id END, eta_value=CASE WHEN status='rejected' THEN NULL ELSE eta_value END, eta_unit=CASE WHEN status='rejected' THEN NULL ELSE eta_unit END, accepted_at=CASE WHEN status='rejected' THEN NULL ELSE accepted_at END WHERE id=?")
                    ->execute([$order['delivery_total'], $order['currency'], $existingTask['id']]);
                if ($existingTask['status'] === 'rejected') {
                    $db->prepare('DELETE FROM delivery_task_rejections WHERE task_id=?')->execute([$existingTask['id']]);
                }
            }
        }
        Audit::log($db, (int)$actor['id'], 'merchant.order.status.updated', 'order', $orderId, ['status' => $order['status']], ['status' => $target]);
        $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $orderId, 'status' => $target]]);
}

if ($method === 'POST' && $path === '/uploads/product-image') {
    $actor = user($db); role($actor, ['merchant']); $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT); if (!$productId) Http::error('validation_error', 'معرف المنتج مطلوب.', 422);
    $owner = $db->prepare('SELECT p.id FROM products p JOIN stores s ON s.id=p.store_id WHERE p.id=:id AND s.merchant_id=:merchant'); $owner->execute(['id' => $productId, 'merchant' => $actor['id']]); if (!$owner->fetch()) Http::error('forbidden', 'لا تملك هذا المنتج.', 403);
    $pathName = saveUpload('image', (int)Database::environment('MAX_PRODUCT_IMAGE_BYTES', '4194304'), 'product'); $db->prepare('INSERT INTO product_images (product_id, file_path) VALUES (?, ?)')->execute([$productId, $pathName]); Http::json(['data' => ['file_path' => $pathName]], 201);
}

if ($method === 'GET' && $path === '/cart') {
    $actor = user($db); $statement = $db->prepare('SELECT ci.id, ci.quantity, p.id AS product_id, p.name, p.price, p.offer_price, p.offer_starts_at, p.offer_ends_at, p.currency, p.stock_quantity, p.delivery_fee, p.store_id FROM carts c JOIN cart_items ci ON ci.cart_id=c.id JOIN products p ON p.id=ci.product_id WHERE c.customer_id=:customer'); $statement->execute(['customer' => $actor['id']]); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'PUT' && route('/cart/items/{productId}', $path, $parameters)) {
    $actor = user($db); role($actor, ['customer']); $input = Http::input(); Http::requireFields($input, ['quantity']); $productId = (int)$parameters['productId'];
    $product = $db->prepare("SELECT id, store_id, currency, stock_quantity FROM products WHERE id=:id AND status='active'"); $product->execute(['id' => $productId]); $product = $product->fetch(); if (!$product) Http::error('not_found', 'المنتج غير متاح.', 404);
    $quantity = (int)$input['quantity']; if ($quantity < 1 || $quantity > (int)$product['stock_quantity']) Http::error('validation_error', 'الكمية غير متاحة.', 422);
    $db->beginTransaction();
    try {
        $cart = $db->prepare('SELECT id, currency FROM carts WHERE customer_id=:customer AND store_id=:store FOR UPDATE'); $cart->execute(['customer' => $actor['id'], 'store' => $product['store_id']]); $cart = $cart->fetch();
        if (!$cart) { $db->prepare('INSERT INTO carts (customer_id, store_id, currency) VALUES (?, ?, ?)')->execute([$actor['id'], $product['store_id'], $product['currency']]); $cart = ['id' => (int)$db->lastInsertId(), 'currency' => $product['currency']]; }
        if ($cart['currency'] !== $product['currency']) Http::error('currency_mismatch', 'لا يمكن جمع عملتين في سلة واحدة.', 422);
        $db->prepare('INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart, :product, :quantity) ON CONFLICT(cart_id, product_id) DO UPDATE SET quantity=excluded.quantity')->execute(['cart' => $cart['id'], 'product' => $productId, 'quantity' => $quantity]); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['product_id' => $productId, 'quantity' => $quantity]]);
}

if ($method === 'POST' && $path === '/preorders') {
    $actor = user($db); role($actor, ['customer']); $input = Http::input();
    Http::requireFields($input, ['product_id', 'quantity', 'requested_fulfillment_at']);
    $productId = (int)$input['product_id']; $quantity = (float)$input['quantity'];
    $product = $db->prepare("SELECT p.*, s.id AS store_id, s.store_type, s.merchant_id, COALESCE(mp.sham_cash_address, '') AS sham_cash_address FROM products p JOIN stores s ON s.id=p.store_id LEFT JOIN merchant_profiles mp ON mp.user_id=s.merchant_id WHERE p.id=? AND p.status='active' AND s.status='active' FOR UPDATE");
    $db->beginTransaction();
    try {
        $product->execute([$productId]); $product = $product->fetch();
        if (!$product || $product['store_type'] !== 'preorder') Http::error('not_found', 'هذه المادة ليست متاحة للطلب المسبق.', 404);
        $minimum = (float)$product['preorder_min_quantity'];
        if ($quantity <= 0 || $quantity + 0.000001 < $minimum) Http::error('validation_error', "الحد الأدنى لهذه المادة هو {$minimum} {$product['preorder_unit_label']}.", 422);
        try { $dueAt = new DateTimeImmutable((string)$input['requested_fulfillment_at']); }
        catch (Throwable $exception) { Http::error('validation_error', 'اختر موعد تنفيذ صحيحاً.', 422); }
        $minimumDue = (new DateTimeImmutable('now'))->modify('+' . max(0, (int)$product['preorder_lead_days']) . ' days');
        if ($dueAt <= $minimumDue) Http::error('validation_error', 'موعد التنفيذ يجب أن يراعي مدة التجهيز المحددة لهذه المادة.', 422);
        if (trim((string)$product['sham_cash_address']) === '') Http::error('merchant_payment_not_ready', 'لم يحدد المتجر عنوان شام كاش بعد.', 422);
        $fulfillment = (string)($input['fulfillment_type'] ?? 'pickup');
        if ($fulfillment !== 'pickup') Http::error('validation_error', 'الطلب المسبق يدعم الاستلام من المتجر حالياً لضمان متابعة التنفيذ والسداد بدقة.', 422);
        $deliveryAddress = null;
        $subtotal = round(((float)$product['price']) * $quantity, 2); $delivery = 0.0;
        $feePercent = (float)($db->query("SELECT decimal_value FROM platform_settings WHERE setting_key='sale_fee_percent' LIMIT 1")->fetchColumn() ?: 0); $platformFee = round($subtotal * $feePercent / 100, 2); $total = round($subtotal + $delivery + $platformFee, 2);
        $number = 'PRE-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $statement = $db->prepare("INSERT INTO preorder_orders (preorder_number, customer_id, store_id, product_id, product_name_snapshot, unit_label_snapshot, requested_quantity, unit_price_snapshot, currency, products_subtotal, delivery_total, platform_fee_percent, platform_fee_total, grand_total, fulfillment_type, delivery_address_text, delivery_latitude, delivery_longitude, requested_fulfillment_at, sham_cash_address_snapshot, customer_note) VALUES (:number,:customer,:store,:product,:product_name,:unit_label,:quantity,:price,:currency,:subtotal,:delivery,:fee_percent,:fee_total,:total,:fulfillment,:address,:latitude,:longitude,:due_at,:sham_cash,:note)");
        $statement->execute(['number' => $number, 'customer' => $actor['id'], 'store' => $product['store_id'], 'product' => $product['id'], 'product_name' => $product['name'], 'unit_label' => $product['preorder_unit_label'], 'quantity' => $quantity, 'price' => $product['price'], 'currency' => $product['currency'], 'subtotal' => $subtotal, 'delivery' => $delivery, 'fee_percent' => $feePercent, 'fee_total' => $platformFee, 'total' => $total, 'fulfillment' => $fulfillment, 'address' => $deliveryAddress, 'latitude' => $input['delivery_latitude'] ?? null, 'longitude' => $input['delivery_longitude'] ?? null, 'due_at' => $dueAt->format('Y-m-d H:i:s'), 'sham_cash' => $product['sham_cash_address'], 'note' => trim((string)($input['customer_note'] ?? '')) ?: null]);
        $preorderId = (int)$db->lastInsertId();
        $db->prepare('INSERT INTO preorder_conversations (preorder_id, buyer_id, merchant_id) VALUES (?, ?, ?)')->execute([$preorderId, $actor['id'], $product['merchant_id']]);
        Audit::log($db, (int)$actor['id'], 'preorder.created', 'preorder_order', $preorderId, null, ['number' => $number, 'quantity' => $quantity]); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $preorderId, 'preorder_number' => $number, 'status' => 'pending_payment', 'grand_total' => $total, 'currency' => $product['currency'], 'sham_cash_address' => $product['sham_cash_address']]], 201);
}

if ($method === 'POST' && route('/preorders/{id}/payment-receipts', $path, $parameters)) {
    $actor = user($db); role($actor, ['customer']); $input = Http::input(); Http::requireFields($input, ['transaction_number']); $preorderId = (int)$parameters['id'];
    $order = $db->prepare("SELECT id, grand_total FROM preorder_orders WHERE id=:id AND customer_id=:customer AND status IN ('pending_payment','payment_review') FOR UPDATE");
    $db->beginTransaction();
    try { $order->execute(['id' => $preorderId, 'customer' => $actor['id']]); $order = $order->fetch(); if (!$order) Http::error('not_found', 'لا يمكن إرسال سند لهذا الطلب المسبق.', 404); $db->prepare("INSERT INTO preorder_payment_receipts (preorder_id, transaction_number, paid_amount, status) VALUES (?, ?, ?, 'submitted')")->execute([$preorderId, trim((string)$input['transaction_number']), $order['grand_total']]); $db->prepare("UPDATE preorder_orders SET status='payment_review', merchant_payment_review_status='pending', merchant_payment_review_note=NULL, merchant_payment_reviewed_at=NULL WHERE id=?")->execute([$preorderId]); Audit::log($db, (int)$actor['id'], 'preorder.payment.submitted', 'preorder_order', $preorderId, null, ['transaction_number' => $input['transaction_number']]); $db->commit(); }
    catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['preorder_id' => $preorderId, 'status' => 'payment_review']], 201);
}

if ($method === 'GET' && $path === '/customer/preorders') {
    $actor = user($db); role($actor, ['customer']);
    $statement = $db->prepare("SELECT po.*, s.name AS store_name, (SELECT ppr.id FROM preorder_payment_receipts ppr WHERE ppr.preorder_id=po.id ORDER BY ppr.created_at DESC, ppr.id DESC LIMIT 1) AS payment_receipt_id, (SELECT ppr.status FROM preorder_payment_receipts ppr WHERE ppr.preorder_id=po.id ORDER BY ppr.created_at DESC, ppr.id DESC LIMIT 1) AS payment_receipt_status, (SELECT ppr.transaction_number FROM preorder_payment_receipts ppr WHERE ppr.preorder_id=po.id ORDER BY ppr.created_at DESC, ppr.id DESC LIMIT 1) AS payment_transaction_number, (SELECT ppr.created_at FROM preorder_payment_receipts ppr WHERE ppr.preorder_id=po.id ORDER BY ppr.created_at DESC, ppr.id DESC LIMIT 1) AS payment_submitted_at FROM preorder_orders po JOIN stores s ON s.id=po.store_id WHERE po.customer_id=? ORDER BY po.created_at DESC LIMIT 100");
    $statement->execute([$actor['id']]); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'PATCH' && route('/merchant/preorders/{id}/payment-review', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); $input = Http::input(); Http::requireFields($input, ['review_status']); $preorderId = (int)$parameters['id']; $review = (string)$input['review_status']; $note = trim((string)($input['note'] ?? '')) ?: null;
    if (!in_array($review, ['acknowledged','issue_reported'], true) || ($review === 'issue_reported' && $note === null)) Http::error('validation_error', 'حدد نتيجة المراجعة وأضف سبباً عند وجود مشكلة.', 422);
    $statement = $db->prepare("UPDATE preorder_orders SET merchant_payment_review_status=?, merchant_payment_review_note=?, merchant_payment_reviewed_at=NOW() WHERE id=? AND status='payment_review' AND store_id IN (SELECT id FROM stores WHERE merchant_id=?)");
    $statement->execute([$review, $note, $preorderId, $actor['id']]); if ($statement->rowCount() !== 1) Http::error('not_found', 'لا يوجد سند دفع معلق لهذا الطلب ضمن متجرك.', 404); Audit::log($db, (int)$actor['id'], 'preorder.payment.merchant_reviewed', 'preorder_order', $preorderId, null, ['status' => $review]); Http::json(['data' => ['id' => $preorderId, 'merchant_payment_review_status' => $review]]);
}

if ($method === 'PATCH' && route('/merchant/preorders/{id}/status', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); $input = Http::input(); Http::requireFields($input, ['status']); $preorderId = (int)$parameters['id']; $target = (string)$input['status'];
    $statement = $db->prepare('SELECT po.id, po.status FROM preorder_orders po JOIN stores s ON s.id=po.store_id WHERE po.id=? AND s.merchant_id=? FOR UPDATE');
    $db->beginTransaction();
    try { $statement->execute([$preorderId, $actor['id']]); $order = $statement->fetch(); if (!$order) Http::error('not_found', 'الطلب المسبق غير موجود ضمن متجرك.', 404); $next = match ($order['status']) { 'paid' => 'merchant_confirmed', 'merchant_confirmed' => 'in_production', 'in_production' => 'ready', 'ready' => 'fulfilled', default => null }; if ($next !== $target) Http::error('invalid_transition', 'لا يمكن تحديث حالة الطلب المسبق بهذه الطريقة.', 422); $db->prepare('UPDATE preorder_orders SET status=?, merchant_confirmed_at=CASE WHEN ?=\'merchant_confirmed\' THEN NOW() ELSE merchant_confirmed_at END, fulfilled_at=CASE WHEN ?=\'fulfilled\' THEN NOW() ELSE fulfilled_at END WHERE id=?')->execute([$target, $target, $target, $preorderId]); Audit::log($db, (int)$actor['id'], 'preorder.status.updated', 'preorder_order', $preorderId, ['status' => $order['status']], ['status' => $target]); $db->commit(); }
    catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $preorderId, 'status' => $target]]);
}

if ($method === 'PATCH' && route('/merchant/preorders/{id}/reminder', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); $input = Http::input(); Http::requireFields($input, ['hours_before']); $preorderId = (int)$parameters['id']; $hours = (int)$input['hours_before'];
    if ($hours < 1 || $hours > 168) Http::error('validation_error', 'اختر تذكيراً بين ساعة و7 أيام قبل الموعد.', 422);
    $statement = $db->prepare("UPDATE preorder_orders SET merchant_reminder_hours=?, reminder_sent_at=NULL WHERE id=? AND status IN ('paid','merchant_confirmed','in_production','ready') AND store_id IN (SELECT id FROM stores WHERE merchant_id=?)");
    $statement->execute([$hours, $preorderId, $actor['id']]); if ($statement->rowCount() !== 1) Http::error('not_found', 'لا يمكن ضبط تنبيه لهذا الطلب في حالته الحالية.', 404); Http::json(['data' => ['id' => $preorderId, 'merchant_reminder_hours' => $hours]]);
}

if ($method === 'GET' && route('/preorders/{id}/conversation', $path, $parameters)) {
    $actor = user($db); $preorderId = (int)$parameters['id'];
    $conversation = $db->prepare("SELECT pc.id, po.preorder_number, po.product_name_snapshot, po.requested_quantity, po.unit_label_snapshot, po.status, po.requested_fulfillment_at, buyer.full_name AS buyer_name, merchant.full_name AS merchant_name FROM preorder_conversations pc JOIN preorder_orders po ON po.id=pc.preorder_id JOIN users buyer ON buyer.id=pc.buyer_id JOIN users merchant ON merchant.id=pc.merchant_id WHERE pc.preorder_id=? AND (pc.buyer_id=? OR pc.merchant_id=?) LIMIT 1");
    $conversation->execute([$preorderId, $actor['id'], $actor['id']]); $conversation = $conversation->fetch(); if (!$conversation) Http::error('forbidden', 'لا تملك صلاحية هذه المحادثة.', 403);
    $messages = $db->prepare('SELECT pm.id, pm.sender_id, pm.body, pm.read_at, pm.created_at, u.full_name AS sender_name FROM preorder_messages pm JOIN users u ON u.id=pm.sender_id WHERE pm.conversation_id=? ORDER BY pm.created_at ASC, pm.id ASC'); $messages->execute([$conversation['id']]); $db->prepare('UPDATE preorder_messages SET read_at=NOW() WHERE conversation_id=? AND sender_id<>? AND read_at IS NULL')->execute([$conversation['id'], $actor['id']]); Http::json(['data' => $messages->fetchAll(), 'conversation' => $conversation]);
}

if ($method === 'POST' && route('/preorders/{id}/messages', $path, $parameters)) {
    $actor = user($db); $input = Http::input(); Http::requireFields($input, ['body']); $preorderId = (int)$parameters['id']; $body = trim((string)$input['body']); if ($body === '' || mb_strlen($body) > 2000) Http::error('validation_error', 'اكتب رسالة بين حرف و2000 حرف.', 422);
    $conversation = $db->prepare('SELECT id FROM preorder_conversations WHERE preorder_id=? AND (buyer_id=? OR merchant_id=?) LIMIT 1'); $conversation->execute([$preorderId, $actor['id'], $actor['id']]); $conversation = $conversation->fetch(); if (!$conversation) Http::error('forbidden', 'لا تملك صلاحية المراسلة بشأن هذا الطلب.', 403); $db->prepare('INSERT INTO preorder_messages (conversation_id, sender_id, body) VALUES (?, ?, ?)')->execute([$conversation['id'], $actor['id'], $body]); $messageId = (int)$db->lastInsertId(); $db->prepare('UPDATE preorder_conversations SET last_message_at=NOW() WHERE id=?')->execute([$conversation['id']]); Http::json(['data' => ['id' => $messageId, 'conversation_id' => (int)$conversation['id']]], 201);
}

if ($method === 'POST' && $path === '/orders') {
    $actor = user($db); role($actor, ['customer']); $input = Http::input(); Http::requireFields($input, ['fulfillment_type']);
    $fulfillment = $input['fulfillment_type']; if (!in_array($fulfillment, ['pickup', 'delivery'], true)) Http::error('validation_error', 'نوع الاستلام غير صحيح.', 422);
    if ($fulfillment === 'delivery') Http::requireFields($input, ['delivery_address_text', 'delivery_latitude', 'delivery_longitude']);
    $db->beginTransaction();
    try {
        $cartQuery = $db->prepare('SELECT c.id, c.store_id, c.currency, s.merchant_id, mp.sham_cash_address FROM carts c JOIN stores s ON s.id=c.store_id JOIN merchant_profiles mp ON mp.user_id=s.merchant_id WHERE c.customer_id=:customer FOR UPDATE'); $cartQuery->execute(['customer' => $actor['id']]); $cart = $cartQuery->fetch(); if (!$cart) Http::error('empty_cart', 'السلة فارغة.', 422);
        $itemsQuery = $db->prepare("SELECT ci.product_id, ci.quantity, p.name, p.price, p.offer_price, p.offer_starts_at, p.offer_ends_at, p.cost_price, p.delivery_fee, p.stock_quantity FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.cart_id=:cart FOR UPDATE"); $itemsQuery->execute(['cart' => $cart['id']]); $items = $itemsQuery->fetchAll(); if (!$items) Http::error('empty_cart', 'السلة فارغة.', 422);
        $subtotal = 0.0; $delivery = 0.0; foreach ($items as &$item) { if ((int)$item['quantity'] > (int)$item['stock_quantity']) Http::error('stock_unavailable', 'أحد المنتجات لم يعد متوفراً بالكمية المطلوبة.', 422); $offerActive = $item['offer_price'] !== null && (float)$item['offer_price'] > 0 && strtotime((string)$item['offer_ends_at']) > time() && ($item['offer_starts_at'] === null || strtotime((string)$item['offer_starts_at']) <= time()); $item['checkout_price'] = $offerActive ? (float)$item['offer_price'] : (float)$item['price']; $subtotal += $item['checkout_price'] * (int)$item['quantity']; if ($fulfillment === 'delivery') $delivery += (float)$item['delivery_fee'] * (int)$item['quantity']; } unset($item);
        $feePercent = max(0.0, min(100.0, (float)($db->query("SELECT decimal_value FROM platform_settings WHERE setting_key='sale_fee_percent' LIMIT 1")->fetchColumn() ?: 0))); $platformFee = round($subtotal * ($feePercent / 100), 2); $grandTotal = $subtotal + $delivery + $platformFee;
        $number = 'SL-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $create = $db->prepare("INSERT INTO orders (order_number, customer_id, store_id, currency, fulfillment_type, status, products_subtotal, delivery_total, platform_fee_percent, platform_fee_total, grand_total, delivery_address_text, delivery_latitude, delivery_longitude, sham_cash_address_snapshot, notes) VALUES (:number, :customer, :store, :currency, :fulfillment, 'pending_payment', :subtotal, :delivery, :fee_percent, :platform_fee, :grand, :address, :latitude, :longitude, :sham, :notes)");
        $create->execute(['number' => $number, 'customer' => $actor['id'], 'store' => $cart['store_id'], 'currency' => $cart['currency'], 'fulfillment' => $fulfillment, 'subtotal' => $subtotal, 'delivery' => $delivery, 'fee_percent' => $feePercent, 'platform_fee' => $platformFee, 'grand' => $grandTotal, 'address' => $input['delivery_address_text'] ?? null, 'latitude' => $input['delivery_latitude'] ?? null, 'longitude' => $input['delivery_longitude'] ?? null, 'sham' => $cart['sham_cash_address'], 'notes' => $input['notes'] ?? null]);
        $orderId = (int)$db->lastInsertId(); $line = $db->prepare('INSERT INTO order_items (order_id, product_id, product_name_snapshot, unit_price, unit_cost, unit_delivery_fee, quantity, line_total) VALUES (:order, :product, :name, :price, :cost, :fee, :quantity, :line_total)'); $decrement = $db->prepare('UPDATE products SET stock_quantity=stock_quantity - :quantity WHERE id=:id');
        foreach ($items as $item) { $line->execute(['order' => $orderId, 'product' => $item['product_id'], 'name' => $item['name'], 'price' => $item['checkout_price'], 'cost' => $item['cost_price'], 'fee' => $item['delivery_fee'], 'quantity' => $item['quantity'], 'line_total' => $item['checkout_price'] * (int)$item['quantity']]); $decrement->execute(['quantity' => $item['quantity'], 'id' => $item['product_id']]); }
        $db->prepare('DELETE FROM carts WHERE id=?')->execute([$cart['id']]); Audit::log($db, (int)$actor['id'], 'order.created', 'order', $orderId, null, ['order_number' => $number, 'grand_total' => $grandTotal, 'platform_fee_total' => $platformFee]); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $orderId, 'order_number' => $number, 'status' => 'pending_payment', 'sham_cash_address' => $cart['sham_cash_address'], 'products_subtotal' => $subtotal, 'delivery_total' => $delivery, 'platform_fee_percent' => $feePercent, 'platform_fee_total' => $platformFee, 'grand_total' => $grandTotal, 'currency' => $cart['currency']]], 201);
}

if ($method === 'POST' && route('/orders/{id}/payment-receipts', $path, $parameters)) {
    $actor = user($db); role($actor, ['customer']); $input = Http::input(); Http::requireFields($input, ['transaction_number']); $orderId = (int)$parameters['id'];
    $order = $db->prepare('SELECT id, grand_total FROM orders WHERE id=:id AND customer_id=:customer AND status IN (\'pending_payment\', \'payment_review\')'); $order->execute(['id' => $orderId, 'customer' => $actor['id']]); $order = $order->fetch(); if (!$order) Http::error('not_found', 'لا يمكن إرسال سند لهذا الطلب.', 404);
    $db->beginTransaction(); try { $db->prepare("INSERT INTO payment_receipts (order_id, transaction_number, paid_amount, status) VALUES (?, ?, ?, 'submitted')")->execute([$orderId, trim($input['transaction_number']), $order['grand_total']]); $db->prepare("UPDATE orders SET status='payment_review' WHERE id=?")->execute([$orderId]); Audit::log($db, (int)$actor['id'], 'payment.submitted', 'order', $orderId, null, ['transaction_number' => $input['transaction_number']]); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['order_id' => $orderId, 'status' => 'payment_review']], 201);
}

if ($method === 'GET' && $path === '/customer/orders') {
    $actor = user($db); role($actor, ['customer']);
    $storeReviewsAvailable = schemaTableExists($db, 'store_reviews');
    $storeReviewedExpression = $storeReviewsAvailable
        ? "EXISTS(SELECT 1 FROM store_reviews sr WHERE sr.order_id=o.id AND sr.store_id=o.store_id AND sr.customer_id=:review_customer)"
        : '0';
    ensureCourierReviewsTable($db);
    $courierReviewedExpression = "EXISTS(SELECT 1 FROM courier_reviews cr WHERE cr.order_id=o.id AND cr.customer_id=:review_customer_courier)";
    $ordersSql = "SELECT o.id, o.order_number, o.status, o.currency, o.fulfillment_type, o.products_subtotal, o.delivery_total, o.platform_fee_percent, o.platform_fee_total, o.grand_total, o.delivery_address_text, o.delivery_latitude, o.delivery_longitude, o.sham_cash_address_snapshot, o.created_at, s.id AS store_id, s.name AS store_name, (SELECT dt.status FROM delivery_tasks dt WHERE dt.order_id=o.id LIMIT 1) AS delivery_task_status, (SELECT COALESCE(dt.pickup_proof_image_path, dt.proof_image_path) FROM delivery_tasks dt WHERE dt.order_id=o.id LIMIT 1) AS pickup_proof_image_path, (SELECT dt.courier_delivery_proof_image_path FROM delivery_tasks dt WHERE dt.order_id=o.id LIMIT 1) AS courier_delivery_proof_image_path, (SELECT dt.customer_proof_image_path FROM delivery_tasks dt WHERE dt.order_id=o.id LIMIT 1) AS customer_proof_image_path, (SELECT pr.status FROM payment_receipts pr WHERE pr.order_id=o.id ORDER BY pr.created_at DESC, pr.id DESC LIMIT 1) AS payment_receipt_status, (SELECT pr.transaction_number FROM payment_receipts pr WHERE pr.order_id=o.id ORDER BY pr.created_at DESC, pr.id DESC LIMIT 1) AS payment_transaction_number, (SELECT pr.created_at FROM payment_receipts pr WHERE pr.order_id=o.id ORDER BY pr.created_at DESC, pr.id DESC LIMIT 1) AS payment_submitted_at, {$storeReviewedExpression} AS store_reviewed, {$courierReviewedExpression} AS courier_reviewed FROM orders o JOIN stores s ON s.id=o.store_id WHERE o.customer_id=:customer ORDER BY o.created_at DESC LIMIT 100";
    $ordersStatement = $db->prepare($ordersSql);
    $ordersStatement->execute(['customer' => $actor['id'], 'review_customer' => $actor['id'], 'review_customer_courier' => $actor['id']]); $orders = $ordersStatement->fetchAll();
    $itemsStatement = $db->prepare("SELECT oi.product_id, oi.product_name_snapshot, oi.unit_price, oi.unit_delivery_fee, oi.quantity, oi.line_total, EXISTS(SELECT 1 FROM reviews r WHERE r.order_id=oi.order_id AND r.product_id=oi.product_id AND r.customer_id=:customer) AS reviewed FROM order_items oi WHERE oi.order_id=:order ORDER BY oi.id");
    foreach ($orders as &$order) {
        $itemsStatement->execute(['customer' => $actor['id'], 'order' => $order['id']]);
        $order['items'] = $itemsStatement->fetchAll();
    }
    unset($order);
    Http::json(['data' => $orders]);
}

if ($method === 'PATCH' && route('/merchant/orders/{id}/payment-review', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); $input = Http::input(); Http::requireFields($input, ['review_status']); $orderId = (int)$parameters['id']; $review = (string)$input['review_status']; $note = trim((string)($input['note'] ?? '')) ?: null;
    if (!in_array($review, ['acknowledged','issue_reported'], true) || ($review === 'issue_reported' && $note === null)) Http::error('validation_error', 'حدد نتيجة مراجعة السداد وأضف سبب المشكلة.', 422);
    $db->beginTransaction();
    try {
        $statement = $db->prepare("SELECT pr.*, o.order_number, o.customer_id, o.products_subtotal, o.platform_fee_total, o.currency, s.merchant_id FROM payment_receipts pr JOIN orders o ON o.id=pr.order_id JOIN stores s ON s.id=o.store_id WHERE pr.order_id=? AND s.merchant_id=? AND o.status='payment_review' AND pr.status='submitted' AND pr.merchant_review_status='pending' ORDER BY pr.created_at DESC, pr.id DESC LIMIT 1 FOR UPDATE");
        $statement->execute([$orderId, $actor['id']]); $receipt = $statement->fetch();
        if (!$receipt) Http::error('not_found', 'لا يوجد سند دفع معلق ضمن متجرك لمراجعته.', 404);
        if ($review === 'issue_reported') {
            $db->prepare("UPDATE payment_receipts SET status='rejected', merchant_review_status='issue_reported', merchant_review_note=?, merchant_reviewed_at=NOW(), rejection_reason=? WHERE id=?")->execute([$note, $note, (int)$receipt['id']]);
            $db->prepare("UPDATE orders SET status='pending_payment' WHERE id=? AND status='payment_review'")->execute([$orderId]);
            Audit::log($db, (int)$actor['id'], 'payment.merchant_rejected', 'order', $orderId, ['status' => 'payment_review'], ['status' => 'pending_payment', 'reason' => $note]);
            $db->commit();
            marketplaceNotify($db, (int)$receipt['customer_id'], 'payment_rejected', 'يحتاج سند الدفع إلى مراجعة', 'أبلغ المتجر عن مشكلة في سند الدفع: ' . $note, 'payment_rejected:' . $orderId . ':customer', ['order_id' => $orderId], ['order_id' => $orderId, 'order_number' => $receipt['order_number']]);
            Http::json(['data' => ['order_id' => $orderId, 'merchant_review_status' => $review, 'payment_status' => 'rejected']]);
        }
        $db->prepare("UPDATE payment_receipts SET status='verified', merchant_review_status='acknowledged', merchant_review_note=NULL, merchant_reviewed_at=NOW(), rejection_reason=NULL WHERE id=?")->execute([(int)$receipt['id']]);
        $db->prepare("UPDATE orders SET status='paid' WHERE id=? AND status='payment_review'")->execute([$orderId]);
        $db->prepare('UPDATE products SET sales_count = sales_count + (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_items.product_id = products.id AND order_items.order_id = ?) WHERE id IN (SELECT product_id FROM order_items WHERE order_id = ?)')->execute([$orderId, $orderId]);
        $db->prepare('INSERT OR IGNORE INTO wallets (user_id, currency, available_balance) VALUES (?, ?, 0)')->execute([$receipt['merchant_id'], $receipt['currency']]);
        $wallet = $db->prepare('SELECT id, available_balance FROM wallets WHERE user_id=? AND currency=? FOR UPDATE'); $wallet->execute([$receipt['merchant_id'], $receipt['currency']]); $wallet = $wallet->fetch();
        $merchantBalance = (float)$wallet['available_balance'] + (float)$receipt['products_subtotal'];
        $db->prepare('UPDATE wallets SET available_balance=? WHERE id=?')->execute([$merchantBalance, $wallet['id']]);
        $db->prepare("INSERT INTO wallet_transactions (wallet_id, transaction_type, direction, amount, balance_after, reference_type, reference_id, description, created_by) VALUES (?, 'merchant_sale', 'credit', ?, ?, 'order', ?, 'رصيد مبيعات معتمد من التاجر', ?)")->execute([$wallet['id'], $receipt['products_subtotal'], $merchantBalance, $orderId, $actor['id']]);
        $db->prepare('INSERT OR IGNORE INTO platform_wallets (currency, available_balance) VALUES (?, 0)')->execute([$receipt['currency']]);
        $platformWallet = $db->prepare('SELECT id, available_balance FROM platform_wallets WHERE currency=? FOR UPDATE'); $platformWallet->execute([$receipt['currency']]); $platformWallet = $platformWallet->fetch();
        $platformBalance = (float)$platformWallet['available_balance'] + (float)$receipt['platform_fee_total'];
        $db->prepare('UPDATE platform_wallets SET available_balance=? WHERE id=?')->execute([$platformBalance, $platformWallet['id']]);
        if ((float)$receipt['platform_fee_total'] > 0) $db->prepare("INSERT INTO platform_wallet_transactions (wallet_id, transaction_type, direction, amount, balance_after, order_id, description, created_by) VALUES (?, 'sale_fee', 'credit', ?, ?, ?, 'رسم منصة من بيع معتمد من التاجر', ?)")->execute([$platformWallet['id'], $receipt['platform_fee_total'], $platformBalance, $orderId, $actor['id']]);
        Audit::log($db, (int)$actor['id'], 'payment.merchant_verified', 'order', $orderId, ['status' => 'payment_review'], ['status' => 'paid', 'payment_receipt_id' => (int)$receipt['id']]);
        $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    try {
        $links = ['order_id' => $orderId]; $payload = ['source' => 'store', 'order_id' => $orderId, 'order_number' => $receipt['order_number'], 'status' => 'paid'];
        marketplaceNotify($db, (int)$receipt['customer_id'], 'payment_verified', 'تم اعتماد الدفع', 'وافق المتجر على سند الدفع، وسيبدأ تجهيز طلبك.', 'payment_verified:' . $orderId . ':customer', $links, $payload);
        marketplaceNotify($db, (int)$actor['id'], 'payment_verified', 'تم اعتماد سند الدفع', 'تم اعتماد السند وإضافة قيمة المنتجات إلى رصيد متجرك.', 'payment_verified:' . $orderId . ':merchant', $links, $payload);
    } catch (Throwable $notificationError) { error_log('payment notification skipped: ' . $notificationError->getMessage()); }
    Http::json(['data' => ['order_id' => $orderId, 'merchant_review_status' => 'acknowledged', 'payment_status' => 'verified', 'order_status' => 'paid']]);
}

if ($method === 'GET' && $path === '/courier/tasks') {
    $actor = user($db); role($actor, ['courier']);
    $db->prepare("UPDATE delivery_tasks SET status='available', courier_id=NULL, eta_value=NULL, eta_unit=NULL, accepted_at=NULL WHERE status='rejected' AND courier_id IS NULL AND order_id IN (SELECT id FROM orders WHERE status='ready_for_delivery' AND fulfillment_type='delivery')")->execute();
    $db->prepare("INSERT INTO delivery_tasks (order_id, delivery_fee, currency, status) SELECT o.id, o.delivery_total, o.currency, 'available' FROM orders o LEFT JOIN delivery_tasks existing ON existing.order_id=o.id WHERE o.status='ready_for_delivery' AND o.fulfillment_type='delivery' AND existing.id IS NULL")->execute();
    $profile = $db->prepare("SELECT verification_status FROM courier_profiles WHERE user_id=?"); $profile->execute([$actor['id']]); if (($profile->fetchColumn() ?: '') !== 'verified') Http::error('courier_not_verified', 'يجب توثيق حساب عامل التوصيل قبل عرض مهام التوصيل أو استلامها.', 403);
    $query = trim((string)($_GET['q'] ?? '')); $sort = (string)($_GET['sort'] ?? 'newest');
    $orderBy = match ($sort) { 'price_asc' => 'dt.delivery_fee ASC, dt.created_at DESC', 'price_desc' => 'dt.delivery_fee DESC, dt.created_at DESC', default => "CASE WHEN dt.status='available' THEN 0 ELSE 1 END, dt.created_at DESC" };
    $where = "((dt.status='available' AND dtr.task_id IS NULL) OR (dt.courier_id=:courier AND dt.status IN ('accepted','picked_up','in_transit','proof_submitted','approved','disputed')))"; $args = ['courier' => (int)$actor['id'], 'rejected_courier' => (int)$actor['id']];
    if ($query !== '') { $where .= " AND (s.name LIKE :q_store OR o.order_number LIKE :q_order OR o.delivery_address_text LIKE :q_address OR oi.product_name_snapshot LIKE :q_product)"; $needle = '%' . mb_substr($query, 0, 120) . '%'; $args['q_store'] = $needle; $args['q_order'] = $needle; $args['q_address'] = $needle; $args['q_product'] = $needle; }
    $statement = $db->prepare("SELECT dt.*, o.order_number, o.delivery_address_text, o.delivery_latitude, o.delivery_longitude, s.name AS store_name, GROUP_CONCAT(CONCAT(oi.product_name_snapshot, ' × ', oi.quantity) , '، ') AS products FROM delivery_tasks dt JOIN orders o ON o.id=dt.order_id JOIN stores s ON s.id=o.store_id LEFT JOIN delivery_task_rejections dtr ON dtr.task_id=dt.id AND dtr.courier_id=:rejected_courier LEFT JOIN order_items oi ON oi.order_id=o.id WHERE $where GROUP BY dt.id ORDER BY $orderBy LIMIT 200"); $statement->execute($args); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'GET' && $path === '/courier/marketplace-tasks') {
    $actor = user($db); role($actor, ['courier']); $profile = $db->prepare('SELECT verification_status FROM courier_profiles WHERE user_id=?'); $profile->execute([$actor['id']]); if (($profile->fetchColumn() ?: '') !== 'verified') Http::error('courier_not_verified', 'يجب توثيق الحساب قبل عرض مهام Marketplace.', 403);
    $statement = $db->prepare("SELECT dt.id, dt.marketplace_transaction_id, dt.tracking_code, dt.status, dt.delivery_fee, dt.currency, dt.eta_value, dt.eta_unit, dt.pickup_location_text, dt.delivery_location_text, dt.package_information, dt.courier_id, dt.created_at, t.transaction_number, l.title AS listing_title, seller.full_name AS seller_name, buyer.full_name AS buyer_name FROM delivery_tasks dt JOIN marketplace_transactions t ON t.id=dt.marketplace_transaction_id JOIN listings_marketplace l ON l.id=t.listing_id JOIN users seller ON seller.id=t.seller_id JOIN users buyer ON buyer.id=t.buyer_id LEFT JOIN delivery_task_rejections rejected ON rejected.task_id=dt.id AND rejected.courier_id=? WHERE (dt.status='searching_driver' AND rejected.task_id IS NULL) OR (dt.courier_id=? AND dt.status IN ('driver_assigned','going_to_pickup','picked_up','in_transit')) ORDER BY CASE WHEN dt.status='searching_driver' THEN 0 ELSE 1 END, dt.created_at DESC");
    $statement->execute([$actor['id'], $actor['id']]); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && route('/courier/marketplace-tasks/{id}/accept', $path, $parameters)) {
    $actor = user($db); role($actor, ['courier']); $profile = $db->prepare('SELECT verification_status FROM courier_profiles WHERE user_id=?'); $profile->execute([$actor['id']]); if (($profile->fetchColumn() ?: '') !== 'verified') Http::error('courier_not_verified', 'يجب توثيق الحساب قبل قبول مهام Marketplace.', 403); $input = Http::input(); Http::requireFields($input, ['eta_value','eta_unit']); if (!is_numeric($input['eta_value']) || (int)$input['eta_value'] < 1 || !in_array($input['eta_unit'], ['hours','days'], true)) Http::error('validation_error', 'وقت الوصول غير صالح.', 422);
    $db->beginTransaction(); try { $lock = $db->prepare('SELECT user_id FROM courier_profiles WHERE user_id=? FOR UPDATE'); $lock->execute([(int)$actor['id']]); $active = $db->prepare("SELECT id FROM delivery_tasks WHERE courier_id=? AND status IN ('accepted','picked_up','proof_submitted','approved','disputed','driver_assigned','going_to_pickup','in_transit') LIMIT 1 FOR UPDATE"); $active->execute([(int)$actor['id']]); if ($active->fetch()) Http::error('courier_busy', 'لديك مهمة توصيل قيد التنفيذ. أكمل تسليمها قبل قبول مهمة جديدة.', 409); $take = $db->prepare("UPDATE delivery_tasks SET courier_id=?, status='driver_assigned', eta_value=?, eta_unit=?, accepted_at=NOW() WHERE id=? AND marketplace_transaction_id IS NOT NULL AND status='searching_driver'"); $take->execute([$actor['id'], (int)$input['eta_value'], $input['eta_unit'], (int)$parameters['id']]); if ($take->rowCount() !== 1) Http::error('task_unavailable', 'هذه المهمة لم تعد متاحة.', 409); $task = $db->prepare('SELECT marketplace_transaction_id FROM delivery_tasks WHERE id=? FOR UPDATE'); $task->execute([(int)$parameters['id']]); $task = $task->fetch(); $db->prepare("UPDATE marketplace_transactions SET delivery_status='waiting_delivery' WHERE id=?")->execute([(int)$task['marketplace_transaction_id']]); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    $taskId = (int)$parameters['id']; $context = marketplaceDeliveryNotificationContext($db, $taskId); foreach ([(int)($context['buyer_id'] ?? 0), (int)($context['seller_id'] ?? 0)] as $recipientId) marketplaceNotify($db, $recipientId, 'driver_assigned', 'تم تعيين عامل التوصيل', 'تم تعيين عامل توصيل موثّق لطلب التوصيل المرتبط بالصفقة.', 'driver_assigned:' . $taskId . ':' . $recipientId, ['listing_id' => (int)($context['listing_id'] ?? 0), 'transaction_id' => (int)($context['marketplace_transaction_id'] ?? 0), 'delivery_task_id' => $taskId]); Audit::log($db, (int)$actor['id'], 'marketplace.delivery.driver_assigned', 'delivery_task', $taskId); Http::json(['data' => ['id' => $taskId, 'status' => 'driver_assigned']]);
}

if ($method === 'POST' && route('/courier/marketplace-tasks/{id}/reject', $path, $parameters)) {
    $actor = user($db); role($actor, ['courier']); $taskId = (int)$parameters['id']; $task = $db->prepare("SELECT id FROM delivery_tasks WHERE id=? AND marketplace_transaction_id IS NOT NULL AND status='searching_driver'"); $task->execute([$taskId]); if (!$task->fetch()) Http::error('task_unavailable', 'لا يمكن رفض هذه المهمة حالياً.', 409); $db->prepare('INSERT OR IGNORE INTO delivery_task_rejections (task_id, courier_id) VALUES (?, ?)')->execute([$taskId, $actor['id']]); Audit::log($db, (int)$actor['id'], 'marketplace.delivery.rejected', 'delivery_task', $taskId); Http::json(['data' => ['id' => $taskId, 'status' => 'rejected']]);
}

if ($method === 'POST' && route('/courier/marketplace-tasks/{id}/going-to-pickup', $path, $parameters)) {
    $actor = user($db); role($actor, ['courier']); $statement = $db->prepare("UPDATE delivery_tasks SET status='going_to_pickup' WHERE id=? AND marketplace_transaction_id IS NOT NULL AND courier_id=? AND status='driver_assigned'"); $statement->execute([(int)$parameters['id'], $actor['id']]); if ($statement->rowCount() !== 1) Http::error('task_unavailable', 'لا يمكن بدء التوجه للاستلام الآن.', 409); Audit::log($db, (int)$actor['id'], 'marketplace.delivery.going_to_pickup', 'delivery_task', (int)$parameters['id']); Http::json(['data' => ['id' => (int)$parameters['id'], 'status' => 'going_to_pickup']]);
}

if ($method === 'POST' && route('/courier/marketplace-tasks/{id}/pickup', $path, $parameters)) {
    $actor = user($db); role($actor, ['courier']); $taskId = (int)$parameters['id']; $db->beginTransaction(); try { $task = $db->prepare("SELECT marketplace_transaction_id FROM delivery_tasks WHERE id=? AND marketplace_transaction_id IS NOT NULL AND courier_id=? AND status='going_to_pickup' FOR UPDATE"); $task->execute([$taskId, $actor['id']]); $task = $task->fetch(); if (!$task) Http::error('task_unavailable', 'لا يمكن تأكيد الاستلام الآن.', 409); $db->prepare("UPDATE delivery_tasks SET status='picked_up' WHERE id=?")->execute([$taskId]); $db->prepare("UPDATE marketplace_transactions SET status='delivering', delivery_status='delivering' WHERE id=?")->execute([(int)$task['marketplace_transaction_id']]); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; } $context = marketplaceDeliveryNotificationContext($db, $taskId); foreach ([(int)($context['buyer_id'] ?? 0), (int)($context['seller_id'] ?? 0)] as $recipientId) marketplaceNotify($db, $recipientId, 'item_picked_up', 'تم استلام السلعة', 'استلم عامل التوصيل السلعة وبدأت مرحلة التوصيل.', 'item_picked_up:' . $taskId . ':' . $recipientId, ['listing_id' => (int)($context['listing_id'] ?? 0), 'transaction_id' => (int)($context['marketplace_transaction_id'] ?? 0), 'delivery_task_id' => $taskId]); Audit::log($db, (int)$actor['id'], 'marketplace.delivery.picked_up', 'delivery_task', $taskId); Http::json(['data' => ['id' => $taskId, 'status' => 'picked_up']]);
}

if ($method === 'POST' && route('/courier/marketplace-tasks/{id}/in-transit', $path, $parameters)) {
    $actor = user($db); role($actor, ['courier']); $statement = $db->prepare("UPDATE delivery_tasks SET status='in_transit' WHERE id=? AND marketplace_transaction_id IS NOT NULL AND courier_id=? AND status='picked_up'"); $statement->execute([(int)$parameters['id'], $actor['id']]); if ($statement->rowCount() !== 1) Http::error('task_unavailable', 'لا يمكن بدء النقل لهذه المهمة الآن.', 409); Audit::log($db, (int)$actor['id'], 'marketplace.delivery.in_transit', 'delivery_task', (int)$parameters['id']); Http::json(['data' => ['id' => (int)$parameters['id'], 'status' => 'in_transit']]);
}

if ($method === 'POST' && route('/courier/marketplace-tasks/{id}/delivered', $path, $parameters)) {
    $actor = user($db); role($actor, ['courier']); $taskId = (int)$parameters['id']; $db->beginTransaction(); try { $task = $db->prepare("SELECT marketplace_transaction_id FROM delivery_tasks WHERE id=? AND marketplace_transaction_id IS NOT NULL AND courier_id=? AND status='in_transit' FOR UPDATE"); $task->execute([$taskId, $actor['id']]); $task = $task->fetch(); if (!$task) Http::error('task_unavailable', 'لا يمكن تأكيد التسليم الآن.', 409); $db->prepare("UPDATE delivery_tasks SET status='delivered' WHERE id=?")->execute([$taskId]); $db->prepare("UPDATE marketplace_transactions SET delivery_status='delivered' WHERE id=?")->execute([(int)$task['marketplace_transaction_id']]); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; } $context = marketplaceDeliveryNotificationContext($db, $taskId); foreach ([(int)($context['buyer_id'] ?? 0), (int)($context['seller_id'] ?? 0)] as $recipientId) marketplaceNotify($db, $recipientId, 'delivery_delivered', 'تم التسليم', 'تم تأكيد تسليم طلب التوصيل المرتبط بالصفقة.', 'delivery_delivered:' . $taskId . ':' . $recipientId, ['listing_id' => (int)($context['listing_id'] ?? 0), 'transaction_id' => (int)($context['marketplace_transaction_id'] ?? 0), 'delivery_task_id' => $taskId]); Audit::log($db, (int)$actor['id'], 'marketplace.delivery.delivered', 'delivery_task', $taskId); Http::json(['data' => ['id' => $taskId, 'status' => 'delivered']]);
}

if ($method === 'POST' && route('/courier/tasks/{id}/accept', $path, $parameters)) {
    $actor = user($db); role($actor, ['courier']); $profile = $db->prepare("SELECT verification_status FROM courier_profiles WHERE user_id=?"); $profile->execute([$actor['id']]); if (($profile->fetchColumn() ?: '') !== 'verified') Http::error('courier_not_verified', 'يجب توثيق الحساب قبل قبول مهام التوصيل.', 403); $input = Http::input(); Http::requireFields($input, ['eta_value', 'eta_unit']); if (!in_array($input['eta_unit'], ['hours', 'days'], true)) Http::error('validation_error', 'وحدة الوقت غير صحيحة.', 422);
    $db->beginTransaction(); try { $lock = $db->prepare('SELECT user_id FROM courier_profiles WHERE user_id=? FOR UPDATE'); $lock->execute([(int)$actor['id']]); $active = $db->prepare("SELECT id FROM delivery_tasks WHERE courier_id=? AND status IN ('accepted','picked_up','proof_submitted','approved','disputed','driver_assigned','going_to_pickup','in_transit') LIMIT 1 FOR UPDATE"); $active->execute([(int)$actor['id']]); if ($active->fetch()) Http::error('courier_busy', 'لديك مهمة توصيل قيد التنفيذ. أكمل تسليمها قبل قبول مهمة جديدة.', 409); $take = $db->prepare("UPDATE delivery_tasks SET courier_id=:courier, status='accepted', eta_value=:eta, eta_unit=:unit, accepted_at=NOW() WHERE id=:id AND status='available' AND NOT EXISTS (SELECT 1 FROM delivery_task_rejections r WHERE r.task_id=delivery_tasks.id AND r.courier_id=:rejected_courier)"); $take->execute(['courier' => (int)$actor['id'], 'rejected_courier' => (int)$actor['id'], 'eta' => (int)$input['eta_value'], 'unit' => $input['eta_unit'], 'id' => (int)$parameters['id']]); if ($take->rowCount() !== 1) Http::error('task_unavailable', 'هذه المهمة لم تعد متاحة.', 409); Audit::log($db, (int)$actor['id'], 'delivery_task.accepted', 'delivery_task', (int)$parameters['id'], null, ['eta_value' => $input['eta_value'], 'eta_unit' => $input['eta_unit']]); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => (int)$parameters['id'], 'status' => 'accepted']]);
}

if ($method === 'POST' && route('/courier/tasks/{id}/reject', $path, $parameters)) {
    $actor = user($db); role($actor, ['courier']); $profile = $db->prepare("SELECT verification_status FROM courier_profiles WHERE user_id=?"); $profile->execute([$actor['id']]); if (($profile->fetchColumn() ?: '') !== 'verified') Http::error('courier_not_verified', 'يجب توثيق الحساب قبل التعامل مع مهام التوصيل.', 403); $taskId = (int)$parameters['id'];
    $task = $db->prepare("SELECT dt.id FROM delivery_tasks dt JOIN orders o ON o.id=dt.order_id WHERE dt.id=:task AND dt.status='available'"); $task->execute(['task' => $taskId]); if (!$task->fetch()) Http::error('task_unavailable', 'هذه المهمة غير متاحة للرفض حالياً.', 409);
    $db->prepare('INSERT OR IGNORE INTO delivery_task_rejections (task_id, courier_id) VALUES (?, ?)')->execute([$taskId, $actor['id']]); Audit::log($db, (int)$actor['id'], 'delivery_task.rejected', 'delivery_task', $taskId, null, ['hidden_for_courier' => true]); Http::json(['data' => ['id' => $taskId, 'status' => 'rejected']]);
}

if ($method === 'POST' && route('/courier/tasks/{id}/proof', $path, $parameters)) {
    $actor = user($db); role($actor, ['courier']); $taskId = (int)$parameters['id'];
    $file = null;
    $db->beginTransaction();
    try {
        $statement = $db->prepare("SELECT dt.*, o.customer_id, o.order_number, o.store_id, s.merchant_id, s.name AS store_name FROM delivery_tasks dt JOIN orders o ON o.id=dt.order_id JOIN stores s ON s.id=o.store_id WHERE dt.id=:id AND dt.courier_id=:courier AND dt.status IN ('accepted','picked_up') FOR UPDATE");
        $statement->execute(['id' => $taskId, 'courier' => $actor['id']]); $task = $statement->fetch();
        if (!$task) Http::error('task_unavailable', 'لا يمكن رفع إثبات لهذه المهمة.', 409);
        $file = saveUpload('image', (int)Database::environment('MAX_PROOF_IMAGE_BYTES', '5242880'), 'delivery');

        $db->prepare("UPDATE delivery_tasks SET proof_image_path=:file, pickup_proof_image_path=:file, proof_submitted_at=NOW(), status='in_transit', reviewed_by=NULL, reviewed_at=NULL WHERE id=:id")->execute(['file' => $file, 'id' => $taskId]);
        $db->prepare("UPDATE orders SET status='out_for_delivery' WHERE id=:id AND status IN ('ready_for_delivery','out_for_delivery')")->execute(['id' => $task['order_id']]);

        $links = ['delivery_task_id' => $taskId]; $payload = ['order_id' => (int)$task['order_id'], 'order_number' => $task['order_number'], 'proof_image_path' => $file, 'pickup_proof_image_path' => $file, 'status' => 'out_for_delivery'];
        try {
            marketplaceNotify($db, (int)$task['customer_id'], 'delivery_proof_uploaded', 'تم استلام المنتج من المتجر', 'أرفق عامل التوصيل صورة الاستلام وبدأ التوصيل. أكّد التسليم بعد وصول المنتج إليك.', 'delivery_proof_uploaded:' . $taskId . ':customer', $links, $payload);
            marketplaceNotify($db, (int)$task['merchant_id'], 'delivery_proof_uploaded', 'الطلب قيد التوصيل', 'استلم عامل التوصيل المنتج وأرفق صورة الاستلام. بانتظار تأكيد الزبون النهائي.', 'delivery_proof_uploaded:' . $taskId . ':merchant', $links, $payload);
            $admins = $db->query("SELECT id FROM users WHERE role='admin'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($admins as $adminId) marketplaceNotify($db, (int)$adminId, 'delivery_proof_uploaded', 'تم استلام المنتج للتوصيل', 'استلم عامل التوصيل المنتج وحُفظت صورة الاستلام للمراجعة الإدارية.', 'delivery_proof_uploaded:' . $taskId . ':admin:' . (int)$adminId, $links, $payload);
        } catch (Throwable $notificationException) { error_log('delivery proof notification skipped: ' . $notificationException->getMessage()); }
        try { Audit::log($db, (int)$actor['id'], 'delivery_proof.completed', 'delivery_task', $taskId, ['status' => $task['status']], ['status' => 'in_transit', 'order_status' => 'out_for_delivery', 'file' => $file]); }
        catch (Throwable $auditException) { error_log('delivery proof audit skipped: ' . $auditException->getMessage()); }
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        if ($file !== null) { $directory = rtrim(Database::environment('UPLOAD_PATH', __DIR__ . '/storage/uploads'), '/'); $savedPath = $directory . '/' . basename($file); if (is_file($savedPath)) @unlink($savedPath); }
        throw $exception;
    }
    Http::json(['data' => ['file_path' => $file, 'status' => 'in_transit', 'order_status' => 'out_for_delivery', 'requires_customer_confirmation' => true]]);
}

if ($method === 'POST' && route('/courier/tasks/{id}/delivery-proof', $path, $parameters)) {
    $actor = user($db); role($actor, ['courier']); $taskId = (int)$parameters['id']; $file = null;
    $db->beginTransaction();
    try {
        $statement = $db->prepare("SELECT dt.*, o.customer_id, o.order_number, o.store_id, s.merchant_id FROM delivery_tasks dt JOIN orders o ON o.id=dt.order_id JOIN stores s ON s.id=o.store_id WHERE dt.id=:id AND dt.courier_id=:courier AND dt.status='in_transit' FOR UPDATE");
        $statement->execute(['id' => $taskId, 'courier' => $actor['id']]); $task = $statement->fetch();
        if (!$task) Http::error('task_unavailable', 'يجب رفع صورة استلام المتجر أولاً، أو أن المهمة ليست في الطريق حالياً.', 409);
        $file = saveUpload('image', (int)Database::environment('MAX_PROOF_IMAGE_BYTES', '5242880'), 'courier_delivery');
        $db->prepare('UPDATE delivery_tasks SET courier_delivery_proof_image_path=? WHERE id=?')->execute([$file, $taskId]);
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        if ($file !== null) { $directory = rtrim(Database::environment('UPLOAD_PATH', __DIR__ . '/storage/uploads'), '/'); $savedPath = $directory . '/' . basename($file); if (is_file($savedPath)) @unlink($savedPath); }
        throw $exception;
    }
    Http::json(['data' => ['file_path' => $file, 'courier_delivery_proof_image_path' => $file, 'status' => 'in_transit', 'requires_customer_confirmation' => true]]);
}
if ($method === 'POST' && route('/courier/tasks/{id}/pickup', $path, $parameters)) {
    $actor = user($db); role($actor, ['courier']); $taskId = (int)$parameters['id'];
    $db->beginTransaction();
    try {
        $statement = $db->prepare("SELECT id, order_id FROM delivery_tasks WHERE id=:id AND courier_id=:courier AND status='accepted' FOR UPDATE"); $statement->execute(['id' => $taskId, 'courier' => $actor['id']]); $task = $statement->fetch();
        if (!$task) Http::error('task_unavailable', 'لا يمكن بدء هذه المهمة حالياً.', 409);
        $db->prepare("UPDATE delivery_tasks SET status='picked_up' WHERE id=?")->execute([$taskId]);
        $db->prepare("UPDATE orders SET status='out_for_delivery' WHERE id=? AND status='ready_for_delivery'")->execute([$task['order_id']]);
        Audit::log($db, (int)$actor['id'], 'delivery_task.picked_up', 'delivery_task', $taskId, ['status' => 'accepted'], ['status' => 'picked_up']);
        $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $taskId, 'status' => 'picked_up']]);
}

if ($method === 'GET' && $path === '/courier/delivery-history') {
    $actor = user($db); role($actor, ['courier']);
    $regular = $db->prepare("SELECT dt.id, 'store_order' AS source_type, dt.order_id, NULL AS marketplace_transaction_id, o.order_number AS reference_number, s.name AS store_name, customer.full_name AS customer_name, GROUP_CONCAT(CONCAT(oi.product_name_snapshot, ' × ', oi.quantity) , '، ') AS products, o.grand_total AS invoice_amount, dt.delivery_fee, dt.currency, dt.status, o.delivery_address_text AS delivery_address, COALESCE(dt.pickup_proof_image_path, dt.proof_image_path) AS pickup_proof_image_path, dt.courier_delivery_proof_image_path, dt.customer_proof_image_path, COALESCE(dt.reviewed_at, dt.proof_submitted_at, dt.accepted_at, dt.created_at) AS completed_at FROM delivery_tasks dt JOIN orders o ON o.id=dt.order_id JOIN stores s ON s.id=o.store_id JOIN users customer ON customer.id=o.customer_id LEFT JOIN order_items oi ON oi.order_id=o.id WHERE dt.courier_id=:courier AND dt.status IN ('delivered','approved') GROUP BY dt.id");
    $regular->execute(['courier' => (int)$actor['id']]);
    $marketplace = $db->prepare("SELECT dt.id, 'marketplace' AS source_type, NULL AS order_id, dt.marketplace_transaction_id, mt.transaction_number AS reference_number, CONCAT('الحراج — ', seller.full_name) AS store_name, buyer.full_name AS customer_name, COALESCE(dt.package_information, listing.title) AS products, COALESCE(mt.agreed_price, mt.amount) AS invoice_amount, dt.delivery_fee, dt.currency, dt.status, dt.delivery_location_text AS delivery_address, COALESCE(dt.pickup_proof_image_path, dt.proof_image_path) AS pickup_proof_image_path, dt.courier_delivery_proof_image_path, dt.customer_proof_image_path, COALESCE(dt.reviewed_at, dt.accepted_at, dt.created_at) AS completed_at FROM delivery_tasks dt JOIN marketplace_transactions mt ON mt.id=dt.marketplace_transaction_id JOIN listings_marketplace listing ON listing.id=mt.listing_id JOIN users buyer ON buyer.id=mt.buyer_id JOIN users seller ON seller.id=mt.seller_id WHERE dt.courier_id=:courier AND dt.status IN ('delivered','approved')");
    $marketplace->execute(['courier' => (int)$actor['id']]);
    $rows = array_merge($regular->fetchAll(), $marketplace->fetchAll());
    usort($rows, static fn($left, $right) => strcmp((string)($right['completed_at'] ?? ''), (string)($left['completed_at'] ?? '')));
    Http::json(['data' => $rows]);
}
if ($method === 'GET' && $path === '/courier/stats') {
    $actor = user($db); role($actor, ['courier']); ensureCourierReviewsTable($db);
    $summary = $db->prepare("SELECT COUNT(*) AS completed_count, COALESCE(SUM(delivery_fee), 0) AS total_delivery_fees, COALESCE(SUM(CASE WHEN currency='USD' THEN delivery_fee ELSE 0 END), 0) AS total_delivery_fees_usd, COALESCE(SUM(CASE WHEN currency='SYP' THEN delivery_fee ELSE 0 END), 0) AS total_delivery_fees_syp FROM delivery_tasks WHERE courier_id=? AND status IN ('delivered','approved')"); $summary->execute([$actor['id']]); $row = $summary->fetch() ?: [];
    $active = $db->prepare("SELECT COUNT(*) FROM delivery_tasks WHERE courier_id=? AND status IN ('accepted','picked_up','in_transit','proof_submitted')"); $active->execute([$actor['id']]);
    $rating = $db->prepare("SELECT COALESCE(ROUND(AVG(rating), 2), 0) AS average_rating, COUNT(*) AS rating_count FROM courier_reviews WHERE courier_id=? AND status='published'"); $rating->execute([$actor['id']]); $ratingRow = $rating->fetch() ?: [];
    Http::json(['data' => ['completed_count' => (int)($row['completed_count'] ?? 0), 'active_count' => (int)$active->fetchColumn(), 'total_delivery_fees' => (float)($row['total_delivery_fees'] ?? 0), 'total_delivery_fees_usd' => (float)($row['total_delivery_fees_usd'] ?? 0), 'total_delivery_fees_syp' => (float)($row['total_delivery_fees_syp'] ?? 0), 'average_rating' => (float)($ratingRow['average_rating'] ?? 0), 'rating_count' => (int)($ratingRow['rating_count'] ?? 0)]]);
}
if ($method === 'GET' && $path === '/admin/delivery-tasks') {
    $actor = user($db); role($actor, ['admin']); $status = (string)($_GET['status'] ?? 'all');
    $allowed = ['all','available','accepted','picked_up','in_transit','delivered','proof_submitted','approved','disputed']; if (!in_array($status, $allowed, true)) $status = 'all';
    $where = $status === 'all' ? '1=1' : 'dt.status=:status'; $statement = $db->prepare("SELECT dt.*, o.order_number, o.status AS order_status, o.delivery_address_text, u.full_name AS courier_name, s.name AS store_name FROM delivery_tasks dt JOIN orders o ON o.id=dt.order_id JOIN stores s ON s.id=o.store_id LEFT JOIN users u ON u.id=dt.courier_id WHERE $where ORDER BY dt.created_at DESC LIMIT 200"); $statement->execute($status === 'all' ? [] : ['status' => $status]); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && route('/admin/delivery-tasks/{id}/approve-proof', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $taskId = (int)$parameters['id'];
    $db->beginTransaction();
    try {
        $statement = $db->prepare("SELECT dt.*, o.id AS order_id FROM delivery_tasks dt JOIN orders o ON o.id=dt.order_id WHERE dt.id=:id AND dt.status IN ('in_transit','proof_submitted') FOR UPDATE"); $statement->execute(['id' => $taskId]); $task = $statement->fetch();
        if (!$task) Http::error('not_found', 'إثبات التسليم غير متاح للمراجعة.', 404);
        $db->prepare("UPDATE delivery_tasks SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$actor['id'], $taskId]);
        $db->prepare("UPDATE orders SET status='delivered' WHERE id=?")->execute([$task['order_id']]);
        $db->prepare('INSERT OR IGNORE INTO wallets (user_id, currency, available_balance) VALUES (?, ?, 0)')->execute([$task['courier_id'], $task['currency']]);
        $wallet = $db->prepare('SELECT id, available_balance FROM wallets WHERE user_id=? AND currency=? FOR UPDATE'); $wallet->execute([$task['courier_id'], $task['currency']]); $wallet = $wallet->fetch(); $balance = (float)$wallet['available_balance'] + (float)$task['delivery_fee'];
        $db->prepare('UPDATE wallets SET available_balance=? WHERE id=?')->execute([$balance, $wallet['id']]);
        $db->prepare("INSERT INTO wallet_transactions (wallet_id, transaction_type, direction, amount, balance_after, reference_type, reference_id, description, created_by) VALUES (?, 'courier_commission', 'credit', ?, ?, 'delivery_task', ?, 'عمولة توصيل معتمدة', ?)")->execute([$wallet['id'], $task['delivery_fee'], $balance, $taskId, $actor['id']]);
        Audit::log($db, (int)$actor['id'], 'delivery_proof.approved', 'delivery_task', $taskId, ['status' => 'proof_submitted'], ['status' => 'approved']);
        $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $taskId, 'status' => 'approved', 'order_status' => 'delivered']]);
}

if ($method === 'POST' && route('/admin/delivery-tasks/{id}/reject-proof', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['reason']); $taskId = (int)$parameters['id'];
    $statement = $db->prepare("UPDATE delivery_tasks SET status='picked_up', reviewed_by=?, reviewed_at=NOW() WHERE id=? AND status IN ('in_transit','proof_submitted')"); $statement->execute([$actor['id'], $taskId]); if ($statement->rowCount() !== 1) Http::error('not_found', 'إثبات التسليم غير متاح للمراجعة.', 404); Audit::log($db, (int)$actor['id'], 'delivery_proof.rejected', 'delivery_task', $taskId, ['status' => 'proof_submitted'], ['status' => 'picked_up', 'reason' => trim((string)$input['reason'])]); Http::json(['data' => ['id' => $taskId, 'status' => 'picked_up']]);
}

if ($method === 'GET' && $path === '/customer/delivery-address') {
    $actor = user($db); role($actor, ['customer']); ensureCustomerDeliveryAddressTable($db); $statement = $db->prepare('SELECT label, address_text AS text, latitude, longitude, updated_at FROM customer_delivery_addresses WHERE user_id=? LIMIT 1'); $statement->execute([$actor['id']]); Http::json(['data' => $statement->fetch() ?: null]);
}
if ($method === 'PUT' && $path === '/customer/delivery-address') {
    $actor = user($db); role($actor, ['customer']); $input = Http::input(); Http::requireFields($input, ['text']); $text = trim((string)$input['text']); if ($text === '' || mb_strlen($text) > 500) Http::error('validation_error', 'عنوان التوصيل غير صالح.', 422); ensureCustomerDeliveryAddressTable($db); $latitude = isset($input['latitude']) && is_numeric($input['latitude']) ? (float)$input['latitude'] : null; $longitude = isset($input['longitude']) && is_numeric($input['longitude']) ? (float)$input['longitude'] : null; $db->prepare("INSERT INTO customer_delivery_addresses (user_id, label, address_text, latitude, longitude) VALUES (?, ?, ?, ?, ?) ON CONFLICT(user_id) DO UPDATE SET label=excluded.label, address_text=excluded.address_text, latitude=excluded.latitude, longitude=excluded.longitude")->execute([$actor['id'], trim((string)($input['label'] ?? '')), $text, $latitude, $longitude]); Audit::log($db, (int)$actor['id'], 'customer.delivery_address.saved', 'customer_delivery_address', (int)$actor['id'], null, ['text' => $text, 'latitude' => $latitude, 'longitude' => $longitude]); Http::json(['data' => ['label' => trim((string)($input['label'] ?? '')), 'text' => $text, 'latitude' => $latitude, 'longitude' => $longitude]]);
}

if ($method === 'GET' && $path === '/wallets') {
    $actor = user($db); if ($actor['role'] === 'merchant') { try { reconcileUserWallets($db, (int)$actor['id']); } catch (Throwable $exception) { error_log('wallet reconciliation skipped: ' . $exception->getMessage()); } }
    $statement = $db->prepare('SELECT id, currency, available_balance, pending_balance, updated_at FROM wallets WHERE user_id=:user ORDER BY currency'); $statement->execute(['user' => $actor['id']]); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'GET' && $path === '/wallet-transactions') {
    $actor = user($db);
    $sql = "SELECT wt.id, wt.transaction_type, wt.direction, wt.amount, wt.balance_after, wt.reference_type, wt.reference_id, wt.description, wt.created_at, w.currency,
        COALESCE(o.order_number, delivery_order.order_number) AS order_number,
        COALESCE(s.name, '') AS store_name, customer.full_name AS customer_name, courier.full_name AS courier_name,
        (SELECT GROUP_CONCAT(CONCAT(oi.product_name_snapshot, ' × ', oi.quantity) , '، ') FROM order_items oi WHERE oi.order_id=o.id) AS products,
        o.grand_total AS invoice_amount, COALESCE(dt.delivery_fee, o.delivery_total, 0) AS delivery_fee
        FROM wallet_transactions wt JOIN wallets w ON w.id=wt.wallet_id
        LEFT JOIN orders o ON wt.reference_type='order' AND o.id=wt.reference_id
        LEFT JOIN delivery_tasks dt ON wt.reference_type='delivery_task' AND dt.id=wt.reference_id
        LEFT JOIN orders delivery_order ON delivery_order.id=dt.order_id
        LEFT JOIN stores s ON s.id=COALESCE(o.store_id, delivery_order.store_id)
        LEFT JOIN users customer ON customer.id=COALESCE(o.customer_id, delivery_order.customer_id)
        LEFT JOIN users courier ON courier.id=COALESCE(dt.courier_id, NULL)
        WHERE w.user_id=? ORDER BY wt.created_at DESC, wt.id DESC LIMIT 300";
    $statement = $db->prepare($sql); $statement->execute([(int)$actor['id']]);
    Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && $path === '/withdrawals') {
    $actor = user($db); role($actor, ['courier']); $input = Http::input(); Http::requireFields($input, ['wallet_id', 'amount', 'payout_method', 'payout_details']);
    $walletId = filter_var($input['wallet_id'], FILTER_VALIDATE_INT); $amount = round((float)$input['amount'], 2); $payoutMethod = trim((string)$input['payout_method']); $payoutDetails = trim((string)$input['payout_details']);
    if (!$walletId || $amount <= 0 || $payoutDetails === '' || !in_array($payoutMethod, ['sham_cash', 'bank_transfer', 'cash'], true)) Http::error('validation_error', 'تحقق من المحفظة والمبلغ وطريقة وبيانات الاستلام.', 422);
    $db->beginTransaction();
    try {
        $walletStatement = $db->prepare('SELECT * FROM wallets WHERE id=:id AND user_id=:user FOR UPDATE'); $walletStatement->execute(['id' => $walletId, 'user' => $actor['id']]); $wallet = $walletStatement->fetch();
        if (!$wallet) Http::error('wallet_not_found', 'المحفظة المحددة غير مرتبطة بحسابك.', 422);
        if ((float)$wallet['available_balance'] + 0.000001 < $amount) Http::error('insufficient_balance', 'الرصيد المتاح لا يغطي طلب السحب.', 422);
        $duplicate = $db->prepare("SELECT id FROM withdrawal_requests WHERE wallet_id=? AND status IN ('requested','under_review') LIMIT 1 FOR UPDATE"); $duplicate->execute([$walletId]); if ($duplicate->fetch()) Http::error('withdrawal_pending', 'لديك طلب سحب قيد المراجعة لهذه العملة.', 409);
        $newAvailable = (float)$wallet['available_balance'] - $amount; $db->prepare('UPDATE wallets SET available_balance=?, pending_balance=pending_balance+? WHERE id=?')->execute([$newAvailable, $amount, $walletId]);
        $db->prepare("INSERT INTO withdrawal_requests (user_id, wallet_id, amount, payout_method, payout_details, status) VALUES (?, ?, ?, ?, ?, 'requested')")->execute([$actor['id'], $walletId, $amount, $payoutMethod, $payoutDetails]); $id = (int)$db->lastInsertId();
        $db->prepare("INSERT INTO wallet_transactions (wallet_id, transaction_type, direction, amount, balance_after, reference_type, reference_id, description, created_by) VALUES (?, 'withdrawal_hold', 'debit', ?, ?, 'withdrawal_request', ?, 'تجميد مبلغ طلب سحب', ?)")->execute([$walletId, $amount, $newAvailable, $id, $actor['id']]);
        Audit::log($db, (int)$actor['id'], 'withdrawal.requested', 'withdrawal_request', $id, null, ['wallet_id' => $walletId, 'currency' => $wallet['currency'], 'amount' => $amount, 'payout_method' => $payoutMethod]);
        try {
            foreach ($db->query("SELECT id FROM users WHERE role='admin' AND status='active'")->fetchAll(PDO::FETCH_COLUMN) as $adminId) {
                marketplaceNotify($db, (int)$adminId, 'withdrawal_requested', 'طلب سحب جديد', 'يوجد طلب سحب جديد بانتظار مراجعة الإدارة.', 'withdrawal_requested:' . $id . ':' . (int)$adminId, [], ['withdrawal_request_id' => $id, 'amount' => $amount, 'currency' => $wallet['currency']]);
            }
        } catch (Throwable $notificationException) {
            error_log('withdrawal notification skipped: ' . $notificationException->getMessage());
        }
        $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $id, 'status' => 'requested', 'currency' => $wallet['currency'], 'amount' => $amount]], 201);
}

if ($method === 'GET' && $path === '/admin/order-payments') {
    $actor = user($db); role($actor, ['admin']); $status = trim((string)($_GET['status'] ?? 'pending')); $where = "pr.status='submitted'"; $args = [];
    if ($status === 'pending') $where .= " AND pr.merchant_review_status='acknowledged'";
    elseif ($status === 'merchant_pending') $where .= " AND pr.merchant_review_status='pending'";
    elseif ($status === 'issue_reported') $where .= " AND pr.merchant_review_status='issue_reported'";
    elseif ($status === 'all') $where = '1=1';
    else Http::error('validation_error', 'حالة دفعة الطلب غير صالحة.', 422);
    $sql = "SELECT pr.id AS payment_receipt_id, pr.order_id, pr.transaction_number, pr.paid_amount, pr.status AS payment_status, pr.merchant_review_status, pr.merchant_review_note, pr.merchant_reviewed_at, pr.created_at AS payment_submitted_at, o.order_number, o.status AS order_status, o.grand_total, o.currency, o.fulfillment_type, s.id AS store_id, s.name AS store_name, customer.id AS customer_id, customer.full_name AS customer_name, customer.phone AS customer_phone, GROUP_CONCAT(CONCAT(oi.product_name_snapshot, ' × ', oi.quantity) , '، ') AS products FROM payment_receipts pr JOIN orders o ON o.id=pr.order_id JOIN stores s ON s.id=o.store_id JOIN users customer ON customer.id=o.customer_id LEFT JOIN order_items oi ON oi.order_id=o.id WHERE {$where} GROUP BY pr.id, pr.order_id, pr.transaction_number, pr.paid_amount, pr.status, pr.merchant_review_status, pr.merchant_review_note, pr.merchant_reviewed_at, pr.created_at, o.order_number, o.status, o.grand_total, o.currency, o.fulfillment_type, s.id, s.name, customer.id, customer.full_name, customer.phone ORDER BY pr.created_at DESC, pr.id DESC LIMIT 200";
    $statement = $db->prepare($sql); $statement->execute($args); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && route('/admin/order-payments/{id}/verify', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $paymentId = (int)$parameters['id']; $db->beginTransaction();
    try { $statement = $db->prepare("SELECT pr.*, o.order_number, o.status AS order_status FROM payment_receipts pr JOIN orders o ON o.id=pr.order_id WHERE pr.id=? FOR UPDATE"); $statement->execute([$paymentId]); $receipt = $statement->fetch(); if (!$receipt) Http::error('not_found', 'سند الدفع غير موجود.', 404); if ($receipt['status'] !== 'submitted' || $receipt['merchant_review_status'] !== 'acknowledged') Http::error('payment_not_ready', 'يجب أن يراجع التاجر السند أولاً قبل اعتماد الإدارة.', 409); $db->prepare("UPDATE payment_receipts SET status='verified', reviewed_by=?, reviewed_at=NOW(), rejection_reason=NULL WHERE id=?")->execute([$actor['id'], $paymentId]); $db->prepare("UPDATE orders SET status='paid' WHERE id=? AND status='payment_review'")->execute([$receipt['order_id']]); Audit::log($db, (int)$actor['id'], 'payment.admin_verified', 'order', (int)$receipt['order_id'], ['status' => 'payment_review'], ['status' => 'paid', 'payment_receipt_id' => $paymentId]); $db->commit(); }
    catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['payment_receipt_id' => $paymentId, 'order_id' => (int)$receipt['order_id'], 'status' => 'verified']]);
}

if ($method === 'POST' && route('/admin/order-payments/{id}/reject', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['reason']); $paymentId = (int)$parameters['id']; $reason = trim((string)$input['reason']);
    $db->beginTransaction();
    try { $statement = $db->prepare("SELECT pr.*, o.order_number FROM payment_receipts pr JOIN orders o ON o.id=pr.order_id WHERE pr.id=? AND pr.status='submitted' FOR UPDATE"); $statement->execute([$paymentId]); $receipt = $statement->fetch(); if (!$receipt) Http::error('not_found', 'سند الدفع غير متاح للرفض.', 404); $db->prepare("UPDATE payment_receipts SET status='rejected', reviewed_by=?, reviewed_at=NOW(), rejection_reason=? WHERE id=?")->execute([$actor['id'], $reason, $paymentId]); $db->prepare("UPDATE orders SET status='pending_payment' WHERE id=? AND status='payment_review'")->execute([$receipt['order_id']]); Audit::log($db, (int)$actor['id'], 'payment.admin_rejected', 'order', (int)$receipt['order_id'], ['status' => 'payment_review'], ['status' => 'pending_payment', 'payment_receipt_id' => $paymentId, 'reason' => $reason]); $db->commit(); }
    catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['payment_receipt_id' => $paymentId, 'order_id' => (int)$receipt['order_id'], 'status' => 'rejected']]);
}

if ($method === 'GET' && $path === '/admin/verifications') {
    $actor = user($db); role($actor, ['admin']);
    $status = $_GET['status'] ?? 'pending';
    $subjectRole = $_GET['subject_role'] ?? null;
    $sql = "SELECT vr.id, vr.user_id, vr.subject_role, CASE WHEN vr.status='approved' AND vr.is_active=1 THEN 'approved' WHEN vr.status='approved' AND vr.is_active=0 THEN 'revoked' ELSE vr.status END AS status, vr.is_active, vr.review_note, vr.identity_front_path, vr.portrait_path, vr.created_at, vr.reviewed_at, u.full_name, u.phone FROM verification_requests vr JOIN users u ON u.id=vr.user_id";
    $args = []; $where = [];
    if (in_array($status, ['pending', 'approved', 'rejected'], true)) { $where[] = 'vr.status=:status'; $args['status'] = $status; }
    if ($status === 'approved') $where[] = 'vr.is_active=1';
    if ($status === 'revoked') { $where[] = "vr.status='approved'"; $where[] = 'vr.is_active=0'; }
    if (in_array($subjectRole, ['merchant', 'courier'], true)) { $where[] = 'vr.subject_role=:subject_role'; $args['subject_role'] = $subjectRole; }
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY vr.updated_at DESC LIMIT 200';
    $statement = $db->prepare($sql); $statement->execute($args); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'GET' && $path === '/admin/approval-notifications') {
    $actor = user($db); role($actor, ['admin']); ensureAdminNotificationReadsTable($db);
    $adminId = (int)$actor['id'];
    $count = static function (PDO $db, int $adminId, string $section, string $condition, string $timeColumn): int {
        $sql = "SELECT COUNT(*) FROM {$condition} AND {$timeColumn} > COALESCE((SELECT read_at FROM admin_notification_reads WHERE admin_id=? AND section_key=?), '1000-01-01 00:00:00')";
        $statement = $db->prepare($sql); $statement->execute([$adminId, $section]); return (int)$statement->fetchColumn();
    };
    $counts = [
        'merchant_verification' => $count($db, $adminId, 'merchant_verification', "verification_requests WHERE subject_role='merchant' AND status='pending'", 'created_at'),
        'courier_verification' => $count($db, $adminId, 'courier_verification', "verification_requests WHERE subject_role='courier' AND status='pending'", 'created_at'),
        'order_payments' => $count($db, $adminId, 'order_payments', "orders WHERE status='payment_review'", 'updated_at'),
        'delivery_proofs' => $count($db, $adminId, 'delivery_proofs', "delivery_tasks WHERE status='proof_submitted'", 'proof_submitted_at'),
        'withdrawals' => $count($db, $adminId, 'withdrawals', "withdrawal_requests wr JOIN wallets w ON w.id=wr.wallet_id JOIN users wu ON wu.id=wr.user_id WHERE wu.role='courier' AND wr.status IN ('requested','under_review')", 'wr.created_at'),
    ];
    $counts['total'] = array_sum($counts); Http::json(['data' => $counts]);
}

if ($method === 'POST' && route('/admin/approval-notifications/{section}/read', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); ensureAdminNotificationReadsTable($db);
    $section = (string)$parameters['section'];
    $allowed = ['merchant_verification', 'courier_verification', 'order_payments', 'delivery_proofs', 'withdrawals'];
    if (!in_array($section, $allowed, true)) Http::error('invalid_section', 'قسم الإشعار غير صالح.', 422);
    $statement = $db->prepare('INSERT INTO admin_notification_reads (admin_id, section_key, read_at) VALUES (?, ?, NOW()) ON CONFLICT(admin_id, section_key) DO UPDATE SET read_at=excluded.read_at');
    $statement->execute([(int)$actor['id'], $section]); Http::json(['data' => ['section' => $section, 'read' => true]]);
}

if ($method === 'GET' && route('/admin/verifications/{id}/files/{kind}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']);
    $column = match ($parameters['kind']) { 'identity_front' => 'identity_front_path', 'portrait' => 'portrait_path', default => null };
    if ($column === null) Http::error('not_found', 'نوع ملف التوثيق غير موجود.', 404);
    $statement = $db->prepare("SELECT {$column} AS file_path FROM verification_requests WHERE id=? LIMIT 1"); $statement->execute([(int)$parameters['id']]); $request = $statement->fetch();
    $directory = rtrim(Database::environment('UPLOAD_PATH', __DIR__ . '/storage/uploads'), '/');
    $file = $request ? $directory . '/' . basename((string)$request['file_path']) : '';
    if ($file === '' || !is_file($file)) Http::error('not_found', 'ملف التوثيق غير موجود.', 404);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime); header('Content-Length: ' . filesize($file)); header('X-Content-Type-Options: nosniff'); readfile($file); exit;
}

if ($method === 'POST' && route('/admin/verifications/{id}/approve', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $requestId = (int)$parameters['id'];
    $db->beginTransaction();
    try {
        $statement = $db->prepare("SELECT * FROM verification_requests WHERE id=? AND status='pending' FOR UPDATE"); $statement->execute([$requestId]); $request = $statement->fetch();
        if (!$request) Http::error('not_found', 'طلب التوثيق غير متاح للمراجعة.', 404);
        $db->prepare("UPDATE verification_requests SET status='approved', is_active=1, review_note=NULL, reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$actor['id'], $requestId]);
        syncVerificationSubject($db, (int)$request['user_id'], (string)$request['subject_role'], 'approved');
        Audit::log($db, (int)$actor['id'], 'verification.approved', 'verification_request', $requestId, ['status' => 'pending'], ['status' => 'approved', 'role' => $request['subject_role']]);
        $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $requestId, 'status' => 'approved']]);
}

if ($method === 'POST' && route('/admin/verifications/{id}/reject', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['reason']); $requestId = (int)$parameters['id'];
    $db->beginTransaction();
    try {
        $statement = $db->prepare("SELECT * FROM verification_requests WHERE id=? AND status='pending' FOR UPDATE"); $statement->execute([$requestId]); $request = $statement->fetch();
        if (!$request) Http::error('not_found', 'طلب التوثيق غير متاح للرفض.', 404);
        $reason = trim((string)$input['reason']);
        $db->prepare("UPDATE verification_requests SET status='rejected', is_active=0, review_note=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$reason, $actor['id'], $requestId]);
        syncVerificationSubject($db, (int)$request['user_id'], (string)$request['subject_role'], 'rejected');
        Audit::log($db, (int)$actor['id'], 'verification.rejected', 'verification_request', $requestId, ['status' => 'pending'], ['status' => 'rejected', 'reason' => $reason]);
        $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $requestId, 'status' => 'rejected']]);
}

if ($method === 'POST' && route('/admin/verifications/{id}/revoke', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['reason']); $requestId = (int)$parameters['id'];
    $db->beginTransaction();
    try {
        $statement = $db->prepare("SELECT * FROM verification_requests WHERE id=? AND status='approved' FOR UPDATE"); $statement->execute([$requestId]); $request = $statement->fetch();
        if (!$request) Http::error('not_found', 'التوثيق المعتمد غير موجود أو موقوف مسبقاً.', 404);
        $db->prepare("UPDATE verification_requests SET status='approved', is_active=0, review_note=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([trim((string)$input['reason']), $actor['id'], $requestId]);
        syncVerificationSubject($db, (int)$request['user_id'], (string)$request['subject_role'], 'revoked');
        Audit::log($db, (int)$actor['id'], 'verification.revoked', 'verification_request', $requestId, ['status' => 'approved'], ['status' => 'revoked']); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $requestId, 'status' => 'revoked']]);
}

if ($method === 'DELETE' && route('/admin/verifications/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $requestId = (int)$parameters['id'];
    $db->beginTransaction();
    try {
        $statement = $db->prepare('SELECT * FROM verification_requests WHERE id=? FOR UPDATE'); $statement->execute([$requestId]); $request = $statement->fetch();
        if (!$request) Http::error('not_found', 'سجل التوثيق غير موجود.', 404);
        $db->prepare('DELETE FROM verification_requests WHERE id=?')->execute([$requestId]);
        syncVerificationSubject($db, (int)$request['user_id'], (string)$request['subject_role'], 'pending');
        Audit::log($db, (int)$actor['id'], 'verification.deleted', 'verification_request', $requestId, ['status' => $request['status']], null); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $requestId, 'deleted' => true]]);
}

if ($method === 'GET' && $path === '/admin/advertisements') {
    $actor = user($db); role($actor, ['admin']);
    $statement = $db->query('SELECT id, title, body, image_path, size_preset, target_type, target_value, starts_at, ends_at, display_order, status, created_at, updated_at FROM advertisements ORDER BY updated_at DESC LIMIT 200');
    Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && $path === '/admin/advertisements') {
    $actor = user($db); role($actor, ['admin']); $input = $_POST; Http::requireFields($input, ['title', 'size_preset', 'target_type', 'starts_at', 'status']);
    if (!in_array($input['size_preset'], ['wide', 'medium', 'compact'], true) || !in_array($input['target_type'], ['none', 'store', 'product', 'url'], true) || !in_array($input['status'], ['draft', 'active', 'paused'], true)) Http::error('validation_error', 'إعدادات الإعلان غير صحيحة.', 422);
    $targetType = (string)$input['target_type'];
    $targetValue = trim((string)($input['target_value'] ?? ''));
    if ($targetType === 'store') {
        $check = $db->prepare("SELECT id FROM stores WHERE id=? AND status<>'suspended' LIMIT 1"); $check->execute([(int)$targetValue]);
        if ($targetValue === '' || !$check->fetch()) Http::error('validation_error', 'اختر متجراً موجوداً من القائمة.', 422);
    } elseif ($targetType === 'product') {
        $check = $db->prepare("SELECT id FROM products WHERE id=? AND status<>'hidden' LIMIT 1"); $check->execute([(int)$targetValue]);
        if ($targetValue === '' || !$check->fetch()) Http::error('validation_error', 'اختر منتجاً موجوداً من القائمة.', 422);
    } elseif ($targetType === 'url') {
        if (!filter_var($targetValue, FILTER_VALIDATE_URL)) Http::error('validation_error', 'أدخل رابطاً خارجياً صحيحاً.', 422);
    } else { $targetValue = ''; }
    $image = saveUpload('image', (int)Database::environment('MAX_AD_IMAGE_BYTES', '5242880'), 'advertisement');
    $statement = $db->prepare('INSERT INTO advertisements (title, body, image_path, size_preset, target_type, target_value, starts_at, ends_at, display_order, status, created_by) VALUES (:title, :body, :image, :size, :target_type, :target_value, :starts, :ends, :display_order, :status, :creator)');
    $statement->execute(['title' => trim($input['title']), 'body' => trim((string)($input['body'] ?? '')) ?: null, 'image' => $image, 'size' => $input['size_preset'], 'target_type' => $targetType, 'target_value' => $targetValue !== '' ? $targetValue : null, 'starts' => $input['starts_at'], 'ends' => trim((string)($input['ends_at'] ?? '')) ?: null, 'display_order' => max(0, (int)($input['display_order'] ?? 0)), 'status' => $input['status'], 'creator' => $actor['id']]);
    $id = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'advertisement.created', 'advertisement', $id, null, ['status' => $input['status'], 'size' => $input['size_preset']]); Http::json(['data' => ['id' => $id, 'image_path' => $image]], 201);
}

if ($method === 'PATCH' && route('/admin/advertisements/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); $id = (int)$parameters['id'];
    $existing = $db->prepare('SELECT * FROM advertisements WHERE id=?'); $existing->execute([$id]); $existing = $existing->fetch(); if (!$existing) Http::error('not_found', 'الإعلان غير موجود.', 404);
    $size = $input['size_preset'] ?? $existing['size_preset']; $targetType = $input['target_type'] ?? $existing['target_type']; $status = $input['status'] ?? $existing['status'];
    if (!in_array($size, ['wide', 'medium', 'compact'], true) || !in_array($targetType, ['none', 'store', 'product', 'url'], true) || !in_array($status, ['draft', 'active', 'paused'], true)) Http::error('validation_error', 'إعدادات الإعلان غير صحيحة.', 422);
    $targetValue = array_key_exists('target_value', $input) ? trim((string)$input['target_value']) : trim((string)($existing['target_value'] ?? ''));
    if ($targetType === 'store') { $check = $db->prepare("SELECT id FROM stores WHERE id=? AND status<>'suspended' LIMIT 1"); $check->execute([(int)$targetValue]); if ($targetValue === '' || !$check->fetch()) Http::error('validation_error', 'اختر متجراً موجوداً من القائمة.', 422); }
    elseif ($targetType === 'product') { $check = $db->prepare("SELECT id FROM products WHERE id=? AND status<>'hidden' LIMIT 1"); $check->execute([(int)$targetValue]); if ($targetValue === '' || !$check->fetch()) Http::error('validation_error', 'اختر منتجاً موجوداً من القائمة.', 422); }
    elseif ($targetType === 'url' && !filter_var($targetValue, FILTER_VALIDATE_URL)) Http::error('validation_error', 'أدخل رابطاً خارجياً صحيحاً.', 422);
    elseif ($targetType === 'none') $targetValue = '';
    $db->prepare('UPDATE advertisements SET title=:title, body=:body, size_preset=:size, target_type=:target_type, target_value=:target_value, starts_at=:starts, ends_at=:ends, display_order=:display_order, status=:status WHERE id=:id')->execute(['title' => trim((string)($input['title'] ?? $existing['title'])), 'body' => trim((string)($input['body'] ?? $existing['body'])) ?: null, 'size' => $size, 'target_type' => $targetType, 'target_value' => $targetValue !== '' ? $targetValue : null, 'starts' => $input['starts_at'] ?? $existing['starts_at'], 'ends' => $input['ends_at'] ?? $existing['ends_at'], 'display_order' => max(0, (int)($input['display_order'] ?? $existing['display_order'])), 'status' => $status, 'id' => $id]);
    Audit::log($db, (int)$actor['id'], 'advertisement.updated', 'advertisement', $id, ['status' => $existing['status']], ['status' => $status]); Http::json(['data' => ['id' => $id, 'status' => $status]]);
}

if ($method === 'POST' && route('/admin/advertisements/{id}/image', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $id = (int)$parameters['id']; $exists = $db->prepare('SELECT id FROM advertisements WHERE id=?'); $exists->execute([$id]); if (!$exists->fetch()) Http::error('not_found', 'الإعلان غير موجود.', 404);
    $image = saveUpload('image', (int)Database::environment('MAX_AD_IMAGE_BYTES', '5242880'), 'advertisement'); $db->prepare('UPDATE advertisements SET image_path=? WHERE id=?')->execute([$image, $id]); Audit::log($db, (int)$actor['id'], 'advertisement.image.updated', 'advertisement', $id); Http::json(['data' => ['id' => $id, 'image_path' => $image]]);
}

if ($method === 'DELETE' && route('/admin/advertisements/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $id = (int)$parameters['id']; $statement = $db->prepare('DELETE FROM advertisements WHERE id=?'); $statement->execute([$id]); if ($statement->rowCount() !== 1) Http::error('not_found', 'الإعلان غير موجود.', 404); Audit::log($db, (int)$actor['id'], 'advertisement.deleted', 'advertisement', $id); Http::json(['data' => ['id' => $id, 'deleted' => true]]);
}

if ($method === 'POST' && route('/admin/verifications/{id}/reject', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['reason']); $requestId = (int)$parameters['id'];
    $db->beginTransaction();
    try {
        $statement = $db->prepare("SELECT * FROM verification_requests WHERE id=? AND status='pending' FOR UPDATE"); $statement->execute([$requestId]); $request = $statement->fetch();
        if (!$request) Http::error('not_found', 'طلب التوثيق غير متاح للمراجعة.', 404);
        $db->prepare("UPDATE verification_requests SET status='rejected', is_active=0, review_note=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([trim($input['reason']), $actor['id'], $requestId]);
        if ($request['subject_role'] === 'courier') $db->prepare("UPDATE courier_profiles SET verification_status='rejected' WHERE user_id=?")->execute([$request['user_id']]);
        if ($request['subject_role'] === 'merchant') $db->prepare('UPDATE stores SET is_verified=FALSE WHERE merchant_id=?')->execute([$request['user_id']]);
        Audit::log($db, (int)$actor['id'], 'verification.rejected', 'verification_request', $requestId, ['status' => 'pending'], ['status' => 'rejected']);
        $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $requestId, 'status' => 'rejected']]);
}

if ($method === 'GET' && $path === '/admin/finance/transactions') {
    $actor = user($db); role($actor, ['admin']); $q = trim((string)($_GET['q'] ?? '')); $status = $_GET['status'] ?? null;
    $sql = "SELECT wt.*, w.currency, u.full_name AS account_name,
        COALESCE(o.order_number, delivery_order.order_number) AS order_number,
        COALESCE(store.name, '') AS store_name,
        customer.full_name AS customer_name, courier.full_name AS courier_name,
        (SELECT GROUP_CONCAT(CONCAT(oi.product_name_snapshot, ' × ', oi.quantity) , '، ') FROM order_items oi WHERE oi.order_id=COALESCE(o.id, delivery_order.id)) AS products,
        COALESCE(o.grand_total, delivery_order.grand_total) AS invoice_amount,
        COALESCE(dt.delivery_fee, o.delivery_total, delivery_order.delivery_total, 0) AS delivery_fee
        FROM wallet_transactions wt JOIN wallets w ON w.id=wt.wallet_id JOIN users u ON u.id=w.user_id
        LEFT JOIN orders o ON wt.reference_type='order' AND o.id=wt.reference_id
        LEFT JOIN delivery_tasks dt ON wt.reference_type='delivery_task' AND dt.id=wt.reference_id
        LEFT JOIN orders delivery_order ON delivery_order.id=dt.order_id
        LEFT JOIN stores store ON store.id=COALESCE(o.store_id, delivery_order.store_id)
        LEFT JOIN users customer ON customer.id=COALESCE(o.customer_id, delivery_order.customer_id)
        LEFT JOIN users courier ON courier.id=dt.courier_id WHERE 1=1"; $args = [];
    if ($q !== '') { $sql .= ' AND (u.full_name LIKE :q OR wt.reference_type LIKE :q OR wt.description LIKE :q OR store.name LIKE :q OR customer.full_name LIKE :q OR courier.full_name LIKE :q)'; $args['q'] = "%{$q}%"; }
    $sql .= ' ORDER BY wt.created_at DESC LIMIT 300'; $statement = $db->prepare($sql); $statement->execute($args); Http::json(['data' => $statement->fetchAll(), 'filters' => ['q' => $q, 'status' => $status]]);
}

if ($method === 'GET' && $path === '/image-compression-settings') {
    $quality = (int)($db->query("SELECT decimal_value FROM platform_settings WHERE setting_key='image_compression_quality' LIMIT 1")->fetchColumn() ?: 72);
    $quality = max(45, min(92, $quality));
    Http::json(['data' => ['quality' => $quality, 'max_dimension' => 1600, 'format' => 'jpeg']]);
}

if ($method === 'GET' && $path === '/admin/platform/settings') {
    $actor = user($db); role($actor, ['admin']);
    $value = (float)($db->query("SELECT decimal_value FROM platform_settings WHERE setting_key='sale_fee_percent' LIMIT 1")->fetchColumn() ?: 0);
    $quality = (int)($db->query("SELECT decimal_value FROM platform_settings WHERE setting_key='image_compression_quality' LIMIT 1")->fetchColumn() ?: 72);
    Http::json(['data' => ['sale_fee_percent' => $value, 'image_compression_quality' => max(45, min(92, $quality))]]);
}

if ($method === 'PATCH' && $path === '/admin/platform/settings') {
    $actor = user($db); role($actor, ['admin']); $input = Http::input();
    $events = [];
    if (array_key_exists('sale_fee_percent', $input)) {
        $percent = (float)$input['sale_fee_percent']; if ($percent < 0 || $percent > 100) Http::error('validation_error', 'يجب أن تكون نسبة رسم المنصة بين 0 و100.', 422);
        $db->prepare("INSERT INTO platform_settings (setting_key, decimal_value, updated_by) VALUES ('sale_fee_percent', ?, ?) ON CONFLICT(setting_key) DO UPDATE SET decimal_value=excluded.decimal_value, updated_by=excluded.updated_by")->execute([$percent, $actor['id']]); $events['sale_fee_percent'] = $percent;
    }
    if (array_key_exists('image_compression_quality', $input)) {
        $quality = (int)$input['image_compression_quality']; if ($quality < 45 || $quality > 92) Http::error('validation_error', 'جودة ضغط الصور يجب أن تكون بين 45 و92.', 422);
        $db->prepare("INSERT INTO platform_settings (setting_key, decimal_value, updated_by) VALUES ('image_compression_quality', ?, ?) ON CONFLICT(setting_key) DO UPDATE SET decimal_value=excluded.decimal_value, updated_by=excluded.updated_by")->execute([$quality, $actor['id']]); $events['image_compression_quality'] = $quality;
    }
    if ($events === []) Http::error('validation_error', 'لم يتم إرسال إعداد قابل للتعديل.', 422);
    Audit::log($db, (int)$actor['id'], 'platform.settings.updated', 'platform_setting', null, null, $events);
    $fee = (float)($db->query("SELECT decimal_value FROM platform_settings WHERE setting_key='sale_fee_percent' LIMIT 1")->fetchColumn() ?: 0);
    $savedQuality = (int)($db->query("SELECT decimal_value FROM platform_settings WHERE setting_key='image_compression_quality' LIMIT 1")->fetchColumn() ?: 72);
    Http::json(['data' => ['sale_fee_percent' => $fee, 'image_compression_quality' => max(45, min(92, $savedQuality))]]);
}

if ($method === 'GET' && $path === '/merchant/analytics') {
    $actor = user($db); role($actor, ['merchant']); $states = "'paid','preparing','ready_for_delivery','out_for_delivery','delivered'";
    $orders = $db->prepare("SELECT o.currency, COUNT(*) AS order_count, COALESCE(SUM(o.products_subtotal), 0) AS sales_total, COALESCE(SUM(o.delivery_total), 0) AS delivery_total, COALESCE(SUM(o.platform_fee_total), 0) AS platform_fees FROM orders o JOIN stores s ON s.id=o.store_id WHERE s.merchant_id=? AND o.status IN ({$states}) GROUP BY o.currency"); $orders->execute([$actor['id']]); $summary = [];
    foreach ($orders->fetchAll() as $row) $summary[$row['currency']] = ['currency' => $row['currency'], 'orders' => (int)$row['order_count'], 'sales_total' => (float)$row['sales_total'], 'delivery_total' => (float)$row['delivery_total'], 'platform_fees' => (float)$row['platform_fees'], 'capital_total' => 0.0, 'gross_profit' => 0.0];
    $costs = $db->prepare("SELECT o.currency, COALESCE(SUM(oi.unit_cost * oi.quantity), 0) AS capital_total, COALESCE(SUM((oi.unit_price - oi.unit_cost) * oi.quantity), 0) AS gross_profit FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN stores s ON s.id=o.store_id WHERE s.merchant_id=? AND o.status IN ({$states}) GROUP BY o.currency"); $costs->execute([$actor['id']]); foreach ($costs->fetchAll() as $row) { $summary[$row['currency']] ??= ['currency' => $row['currency'], 'orders' => 0, 'sales_total' => 0.0, 'delivery_total' => 0.0, 'platform_fees' => 0.0, 'capital_total' => 0.0, 'gross_profit' => 0.0]; $summary[$row['currency']]['capital_total'] = (float)$row['capital_total']; $summary[$row['currency']]['gross_profit'] = (float)$row['gross_profit']; }
    $due = $db->prepare("SELECT o.currency, COALESCE(SUM(dt.delivery_fee), 0) AS delivery_fees_due FROM delivery_tasks dt JOIN orders o ON o.id=dt.order_id JOIN stores s ON s.id=o.store_id WHERE s.merchant_id=? AND dt.courier_id IS NOT NULL AND dt.status IN ('delivered','approved') AND dt.delivery_fee_settled_at IS NULL GROUP BY o.currency");
    try { $due->execute([$actor['id']]); foreach ($due->fetchAll() as $row) { $summary[$row['currency']]['delivery_fees_due'] = (float)$row['delivery_fees_due']; } } catch (Throwable $exception) { foreach ($summary as &$item) $item['delivery_fees_due'] = 0.0; }
    foreach ($summary as &$item) $item['delivery_fees_due'] ??= 0.0;
    $wallets = $db->prepare('SELECT currency, available_balance, pending_balance FROM wallets WHERE user_id=? ORDER BY currency'); $wallets->execute([$actor['id']]); Http::json(['data' => ['summary' => array_values($summary), 'wallets' => $wallets->fetchAll()]]);
}

if ($method === 'GET' && $path === '/admin/merchant-delivery-fees') {
    $actor = user($db); role($actor, ['admin']);
    if (!schemaTableExists($db, 'merchant_delivery_fee_settlements')) Http::error('schema_not_ready', 'ترحيل تسويات أجور التوصيل غير موجود.', 503);
    $sql = "SELECT u.id AS merchant_id, u.full_name AS merchant_name, o.currency, COALESCE(SUM(dt.delivery_fee),0) AS amount_due FROM delivery_tasks dt JOIN orders o ON o.id=dt.order_id JOIN stores s ON s.id=o.store_id JOIN users u ON u.id=s.merchant_id WHERE dt.courier_id IS NOT NULL AND dt.status IN ('delivered','approved') AND dt.delivery_fee_settled_at IS NULL GROUP BY u.id, u.full_name, o.currency ORDER BY u.full_name, o.currency";
    $statement = $db->query($sql); Http::json(['data' => $statement->fetchAll()]);
}
if ($method === 'POST' && route('/admin/merchant-delivery-fees/{merchantId}/settle', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']);
    if (!schemaTableExists($db, 'merchant_delivery_fee_settlements')) Http::error('schema_not_ready', 'ترحيل تسويات أجور التوصيل غير موجود.', 503);
    $merchantId = (int)$parameters['merchantId']; $input = Http::input(); $currency = strtoupper(trim((string)($input['currency'] ?? ''))); $note = trim((string)($input['note'] ?? ''));
    if (!in_array($currency, ['USD','SYP'], true)) Http::error('validation_error', 'العملة غير صالحة.', 422);
    $merchant = $db->prepare('SELECT id FROM users WHERE id=? AND role=\'merchant\' LIMIT 1'); $merchant->execute([$merchantId]); if (!$merchant->fetch()) Http::error('not_found', 'التاجر غير موجود.', 404);
    $db->beginTransaction();
    try {
        $amountStatement = $db->prepare("SELECT COALESCE(SUM(dt.delivery_fee),0) FROM delivery_tasks dt JOIN orders o ON o.id=dt.order_id JOIN stores s ON s.id=o.store_id WHERE s.merchant_id=? AND o.currency=? AND dt.courier_id IS NOT NULL AND dt.status IN ('delivered','approved') AND dt.delivery_fee_settled_at IS NULL");
        $amountStatement->execute([$merchantId, $currency]); $amount = round((float)$amountStatement->fetchColumn(), 2);
        if ($amount <= 0) Http::error('nothing_to_settle', 'لا يوجد مبلغ توصيل مستحق لهذه العملة.', 422);
        $db->prepare('INSERT INTO merchant_delivery_fee_settlements (merchant_id, currency, amount, settled_by, note) VALUES (?, ?, ?, ?, ?)')->execute([$merchantId, $currency, $amount, $actor['id'], $note !== '' ? $note : null]);
        $mark = $db->prepare("UPDATE delivery_tasks SET delivery_fee_settled_at=NOW(), delivery_fee_settled_by=? WHERE courier_id IS NOT NULL AND status IN ('delivered','approved') AND delivery_fee_settled_at IS NULL AND order_id IN (SELECT o.id FROM orders o JOIN stores s ON s.id=o.store_id WHERE s.merchant_id=? AND o.currency=?)");
        $mark->execute([$actor['id'], $merchantId, $currency]);
        Audit::log($db, (int)$actor['id'], 'merchant.delivery_fees.settled', 'merchant', $merchantId, null, ['currency' => $currency, 'amount' => $amount, 'tasks_settled' => $mark->rowCount()]); $db->commit();
        Http::json(['data' => ['merchant_id' => $merchantId, 'currency' => $currency, 'amount' => $amount, 'settled' => true]]);
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
}

if ($method === 'GET' && $path === '/admin/platform/analytics') {
    $actor = user($db); role($actor, ['admin']); try { reconcilePlatformWallets($db); } catch (Throwable $exception) { error_log('platform wallet reconciliation skipped: ' . $exception->getMessage()); } $states = "'paid','preparing','ready_for_delivery','out_for_delivery','delivered'";
    $orders = $db->query("SELECT currency, COUNT(*) AS order_count, COALESCE(SUM(products_subtotal), 0) AS sales_total, COALESCE(SUM(platform_fee_total), 0) AS platform_fees, COALESCE(SUM(grand_total), 0) AS customer_paid_total FROM orders WHERE status IN ({$states}) GROUP BY currency")->fetchAll();
    $capital = $db->query("SELECT o.currency, COALESCE(SUM(oi.unit_cost * oi.quantity), 0) AS capital_total FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.status IN ({$states}) GROUP BY o.currency")->fetchAll(); $capitalByCurrency = []; foreach ($capital as $row) $capitalByCurrency[$row['currency']] = (float)$row['capital_total']; foreach ($orders as &$row) $row['capital_total'] = $capitalByCurrency[$row['currency']] ?? 0.0;
    $wallets = $db->query('SELECT currency, available_balance, updated_at FROM platform_wallets ORDER BY currency')->fetchAll();
    $withdrawals = $db->query("SELECT status, currency, COUNT(*) AS request_count, COALESCE(SUM(amount), 0) AS amount_total FROM withdrawal_requests wr JOIN wallets w ON w.id=wr.wallet_id GROUP BY status, currency ORDER BY status, currency")->fetchAll();
    $auditCount = (int)$db->query("SELECT COUNT(*) FROM audit_logs WHERE created_at >= datetime('now', '-30 days')")->fetchColumn();
    Http::json(['data' => ['summary' => $orders, 'wallets' => $wallets, 'withdrawals' => $withdrawals, 'audit_logs_last_30_days' => $auditCount]]);
}

if ($method === 'GET' && $path === '/support/tickets') {
    $actor = user($db); $isAdmin = $actor['role'] === 'admin'; $sql = "SELECT t.*, u.full_name AS creator_name, last_msg.body AS last_message_body, last_msg.created_at AS last_message_at FROM support_tickets t JOIN users u ON u.id=t.user_id LEFT JOIN support_messages last_msg ON last_msg.id=(SELECT MAX(candidate.id) FROM support_messages candidate WHERE candidate.ticket_id=t.id)"; $args = []; if (!$isAdmin) { $sql .= ' WHERE t.user_id=:user'; $args['user'] = $actor['id']; } $sql .= ' ORDER BY COALESCE(last_msg.created_at, t.updated_at) DESC, t.id DESC LIMIT 100'; $statement = $db->prepare($sql); $statement->execute($args); Http::json(['data' => $statement->fetchAll()]);
}

$parameters = [];
if ($method === 'GET' && route('/support/tickets/{id}/messages', $path, $parameters)) {
    $actor = user($db); $ticketId = (int)$parameters['id'];
    $ticket = $db->prepare('SELECT id, user_id FROM support_tickets WHERE id=:id LIMIT 1'); $ticket->execute(['id' => $ticketId]); $ticket = $ticket->fetch();
    if (!$ticket) Http::error('not_found', 'التذكرة غير موجودة.', 404);
    if ($actor['role'] !== 'admin' && (int)$ticket['user_id'] !== (int)$actor['id']) Http::error('forbidden', 'لا يمكنك الاطلاع على هذه التذكرة.', 403);
    $messages = $db->prepare('SELECT m.id, m.ticket_id, m.body, m.created_at, m.sender_id, u.full_name AS sender_name, u.role AS sender_role FROM support_messages m JOIN users u ON u.id=m.sender_id WHERE m.ticket_id=:ticket ORDER BY m.created_at ASC, m.id ASC');
    $messages->execute(['ticket' => $ticketId]); Http::json(['data' => $messages->fetchAll()]);
}

if ($method === 'POST' && route('/support/tickets/{id}/messages', $path, $parameters)) {
    $actor = user($db); $ticketId = (int)$parameters['id']; $input = Http::input(); Http::requireFields($input, ['message']); $body = trim((string)$input['message']); if (mb_strlen($body) < 1 || mb_strlen($body) > 4000) Http::error('validation_error', 'يجب أن تكون الرسالة بين 1 و4000 حرف.', 422);
    $ticket = $db->prepare('SELECT id, user_id FROM support_tickets WHERE id=:id LIMIT 1'); $ticket->execute(['id' => $ticketId]); $ticket = $ticket->fetch();
    if (!$ticket) Http::error('not_found', 'التذكرة غير موجودة.', 404);
    if ($actor['role'] !== 'admin' && (int)$ticket['user_id'] !== (int)$actor['id']) Http::error('forbidden', 'لا يمكنك الرد على هذه التذكرة.', 403);
    $db->prepare('INSERT INTO support_messages (ticket_id, sender_id, body) VALUES (?, ?, ?)')->execute([$ticketId, $actor['id'], $body]);
    $db->prepare("UPDATE support_tickets SET status=CASE WHEN ?='admin' THEN 'waiting_user' ELSE 'open' END, assigned_admin_id=CASE WHEN ?='admin' THEN ? ELSE assigned_admin_id END, updated_at=NOW() WHERE id=?")->execute([$actor['role'], $actor['role'], $actor['id'], $ticketId]);
    $id = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'ticket.message.created', 'support_ticket', $ticketId, null, ['message_id' => $id]); Http::json(['data' => ['id' => $id, 'ticket_id' => $ticketId]], 201);
}

if ($method === 'PATCH' && route('/admin/support/tickets/{id}/status', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['status']); $ticketId = (int)$parameters['id']; $status = (string)$input['status'];
    if (!in_array($status, ['in_progress','waiting_user','resolved','closed'], true)) Http::error('validation_error', 'حالة التذكرة غير صحيحة.', 422);
    $statement = $db->prepare('UPDATE support_tickets SET status=?, assigned_admin_id=?, updated_at=NOW() WHERE id=?'); $statement->execute([$status, $actor['id'], $ticketId]); if ($statement->rowCount() !== 1) Http::error('not_found', 'التذكرة غير موجودة.', 404); Audit::log($db, (int)$actor['id'], 'ticket.status.updated', 'support_ticket', $ticketId, null, ['status' => $status]); Http::json(['data' => ['id' => $ticketId, 'status' => $status]]);
}

if ($method === 'DELETE' && route('/admin/support/tickets/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $ticketId = (int)$parameters['id']; $statement = $db->prepare('DELETE FROM support_tickets WHERE id=?'); $statement->execute([$ticketId]); if ($statement->rowCount() !== 1) Http::error('not_found', 'التذكرة غير موجودة.', 404); Audit::log($db, (int)$actor['id'], 'ticket.deleted', 'support_ticket', $ticketId); Http::json(['data' => ['id' => $ticketId, 'deleted' => true]]);
}

if ($method === 'POST' && $path === '/support/tickets') {
    $actor = user($db); role($actor, ['customer','merchant','courier']); $input = Http::input(); Http::requireFields($input, ['subject', 'category', 'message']); if (!in_array($input['category'], ['account','order','payment','delivery','wallet','other'], true)) Http::error('validation_error', 'فئة التذكرة غير صحيحة.', 422);
    $number = 'TK-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)); $db->beginTransaction(); try { $db->prepare('INSERT INTO support_tickets (ticket_number, user_id, subject, category, priority, status) VALUES (?, ?, ?, ?, ?, \'open\')')->execute([$number, $actor['id'], trim($input['subject']), $input['category'], $input['priority'] ?? 'normal']); $id = (int)$db->lastInsertId(); $db->prepare('INSERT INTO support_messages (ticket_id, sender_id, body) VALUES (?, ?, ?)')->execute([$id, $actor['id'], trim($input['message'])]); Audit::log($db, (int)$actor['id'], 'ticket.created', 'support_ticket', $id, null, ['ticket_number' => $number]); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $id, 'ticket_number' => $number, 'status' => 'open']], 201);
}

if ($method === 'GET' && $path === '/addresses') {
    $actor = user($db); $statement = $db->prepare('SELECT id, label, address_text, latitude, longitude, is_default FROM addresses WHERE user_id=:user ORDER BY is_default DESC, id DESC'); $statement->execute(['user' => $actor['id']]); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && $path === '/addresses') {
    $actor = user($db); $input = Http::input(); Http::requireFields($input, ['label', 'address_text']);
    $statement = $db->prepare('INSERT INTO addresses (user_id, label, address_text, latitude, longitude, is_default) VALUES (:user, :label, :address, :latitude, :longitude, :default)');
    $statement->execute(['user' => $actor['id'], 'label' => trim($input['label']), 'address' => trim($input['address_text']), 'latitude' => $input['latitude'] ?? null, 'longitude' => $input['longitude'] ?? null, 'default' => !empty($input['is_default']) ? 1 : 0]);
    $id = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'address.created', 'address', $id, null, ['label' => $input['label']]); Http::json(['data' => ['id' => $id]], 201);
}

if ($method === 'GET' && route('/products/{id}/reviews', $path, $parameters)) {
    $statement = $db->prepare("SELECT r.id, r.rating, r.comment, r.created_at, u.full_name FROM reviews r JOIN users u ON u.id=r.customer_id WHERE r.product_id=:product AND r.status='published' ORDER BY r.created_at DESC LIMIT 100"); $statement->execute(['product' => (int)$parameters['id']]); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && route('/products/{id}/view', $path, $parameters)) {
    $productId = (int)$parameters['id'];
    $statement = $db->prepare("UPDATE products SET view_count=view_count+1 WHERE id=:id AND status='active' AND store_id IN (SELECT id FROM stores WHERE status='active')");
    $statement->execute(['id' => $productId]);
    Http::json(['data' => ['product_id' => $productId, 'counted' => $statement->rowCount() === 1]]);
}
if ($method === 'POST' && route('/products/{id}/reviews', $path, $parameters)) {
    $actor = user($db); role($actor, ['customer']); $input = Http::input(); Http::requireFields($input, ['order_id', 'rating']); $rating = (int)$input['rating']; $comment = trim((string)($input['comment'] ?? '')); if ($rating < 1 || $rating > 5) Http::error('validation_error', 'التقييم يجب أن يكون بين 1 و5.', 422); if (mb_strlen($comment) > 1500) Http::error('validation_error', 'المراجعة يجب ألا تتجاوز 1500 حرف.', 422);
    $productId = (int)$parameters['id']; $orderId = (int)$input['order_id'];
    $eligible = $db->prepare("SELECT oi.id FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.product_id=:product AND o.id=:order AND o.customer_id=:customer AND o.status='delivered'"); $eligible->execute(['product' => $productId, 'order' => $orderId, 'customer' => $actor['id']]); if (!$eligible->fetch()) Http::error('review_not_allowed', 'يمكن تقييم المنتج بعد استلامه فقط.', 403);
    $db->beginTransaction(); try { $db->prepare('INSERT INTO reviews (product_id, customer_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)')->execute([$productId, $actor['id'], $orderId, $rating, $comment ?: null]); $reviewId = (int)$db->lastInsertId(); $db->prepare('UPDATE products SET rating_avg=(SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE product_id=:id AND status=\'published\'), rating_count=(SELECT COUNT(*) FROM reviews WHERE product_id=:id2 AND status=\'published\') WHERE id=:id3')->execute(['id' => $productId, 'id2' => $productId, 'id3' => $productId]); Audit::log($db, (int)$actor['id'], 'review.created', 'review', $reviewId, null, ['product_id' => $productId, 'rating' => $rating]); $db->commit(); } catch (PDOException $exception) { if ($db->inTransaction()) $db->rollBack(); if (isDuplicateConstraint($exception)) Http::error('review_exists', 'سبق أن قيّمت هذا المنتج في هذا الطلب.', 409); throw $exception; } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $reviewId]], 201);
}

if ($method === 'POST' && route('/stores/{id}/view', $path, $parameters)) {
    $storeId = (int)$parameters['id'];
    $store = $db->prepare("SELECT id FROM stores WHERE id=? AND status='active'");
    $store->execute([$storeId]);
    if (!$store->fetchColumn()) Http::error('not_found', 'المتجر غير موجود.', 404);
    ensureStoreVisitEventsTable($db);
    $viewer = optionalUser($db); $viewerId = $viewer ? (int)$viewer['id'] : null;
    $db->prepare('INSERT INTO store_visit_events (store_id, viewer_id) VALUES (?, ?)')->execute([$storeId, $viewerId]);
    $count = $db->prepare('SELECT COUNT(*) FROM store_visit_events WHERE store_id=?');
    $count->execute([$storeId]);
    $subscriberCount = 0;
    if (schemaTableExists($db, 'store_followers')) {
        $subscriber = $db->prepare("SELECT COUNT(*) FROM store_followers WHERE store_id=? AND status='active'");
        $subscriber->execute([$storeId]);
        $subscriberCount = (int)$subscriber->fetchColumn();
    }
    Http::json(['data' => ['store_id' => $storeId, 'recorded' => true, 'total_views' => (int)$count->fetchColumn(), 'subscriber_count' => $subscriberCount]], 201);
}
if ($method === 'GET' && route('/stores/{id}/reviews', $path, $parameters)) {
    $statement = $db->prepare("SELECT sr.id, sr.rating, sr.comment, sr.created_at, u.full_name FROM store_reviews sr JOIN users u ON u.id=sr.customer_id WHERE sr.store_id=:store AND sr.status='published' ORDER BY sr.created_at DESC LIMIT 100"); $statement->execute(['store' => (int)$parameters['id']]); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && route('/stores/{id}/reviews', $path, $parameters)) {
    $actor = user($db); role($actor, ['customer']); $input = Http::input(); Http::requireFields($input, ['order_id', 'rating']); $rating = (int)$input['rating']; $comment = trim((string)($input['comment'] ?? '')); if ($rating < 1 || $rating > 5) Http::error('validation_error', 'التقييم يجب أن يكون بين 1 و5.', 422); if (mb_strlen($comment) > 1500) Http::error('validation_error', 'المراجعة يجب ألا تتجاوز 1500 حرف.', 422);
    $storeId = (int)$parameters['id']; $orderId = (int)$input['order_id'];
    $eligible = $db->prepare("SELECT id FROM orders WHERE id=:order AND store_id=:store AND customer_id=:customer AND status='delivered'"); $eligible->execute(['order' => $orderId, 'store' => $storeId, 'customer' => $actor['id']]); if (!$eligible->fetch()) Http::error('review_not_allowed', 'يمكن تقييم المتجر بعد استلام طلبه فقط.', 403);
    $db->beginTransaction(); try { $db->prepare('INSERT INTO store_reviews (store_id, customer_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)')->execute([$storeId, $actor['id'], $orderId, $rating, $comment ?: null]); $reviewId = (int)$db->lastInsertId(); $db->prepare('UPDATE stores SET rating_avg=(SELECT COALESCE(AVG(rating), 0) FROM store_reviews WHERE store_id=:id AND status=\'published\'), rating_count=(SELECT COUNT(*) FROM store_reviews WHERE store_id=:id2 AND status=\'published\') WHERE id=:id3')->execute(['id' => $storeId, 'id2' => $storeId, 'id3' => $storeId]); Audit::log($db, (int)$actor['id'], 'store_review.created', 'store_review', $reviewId, null, ['store_id' => $storeId, 'rating' => $rating]); $db->commit(); } catch (PDOException $exception) { if ($db->inTransaction()) $db->rollBack(); if (isDuplicateConstraint($exception)) Http::error('review_exists', 'سبق أن قيّمت هذا المتجر في هذا الطلب.', 409); throw $exception; } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $reviewId]], 201);
}

if ($method === 'POST' && route('/orders/{id}/courier-review', $path, $parameters)) {
    $actor = user($db); role($actor, ['customer']); ensureCourierReviewsTable($db); $input = Http::input(); Http::requireFields($input, ['rating']); $orderId = (int)$parameters['id']; $rating = (int)$input['rating']; $comment = trim((string)($input['comment'] ?? ''));
    if ($rating < 1 || $rating > 5) Http::error('validation_error', 'التقييم يجب أن يكون بين نجمة و5 نجوم.', 422);
    $statement = $db->prepare("SELECT o.id, o.order_number, o.status, dt.id AS delivery_task_id, dt.courier_id FROM orders o JOIN delivery_tasks dt ON dt.order_id=o.id WHERE o.id=? AND o.customer_id=? AND o.status='delivered' AND dt.courier_id IS NOT NULL AND dt.status IN ('delivered','approved') LIMIT 1"); $statement->execute([$orderId, $actor['id']]); $order = $statement->fetch();
    if (!$order) Http::error('review_unavailable', 'لا يمكن تقييم عامل التوصيل قبل إتمام التسليم.', 409);
    try {
        $db->prepare('INSERT INTO courier_reviews (courier_id, customer_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)')->execute([(int)$order['courier_id'], $actor['id'], $orderId, $rating, $comment !== '' ? $comment : null]);
    } catch (PDOException $exception) {
        if (isDuplicateConstraint($exception)) Http::error('review_exists', 'سبق أن قيّمت عامل التوصيل لهذا الطلب.', 409);
        throw $exception;
    }
    $reviewId = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'courier_review.created', 'courier_review', $reviewId, null, ['order_id' => $orderId, 'courier_id' => (int)$order['courier_id'], 'rating' => $rating]);
    try { marketplaceNotify($db, (int)$order['courier_id'], 'courier_review_received', 'وصل تقييم جديد', 'قيّمك الزبون بـ ' . $rating . ' من 5 بعد إتمام الطلب ' . $order['order_number'] . '.', 'courier_review:' . $reviewId . ':courier', ['order_id' => $orderId], ['order_id' => $orderId, 'rating' => $rating]); } catch (Throwable $notificationError) { error_log('courier review notification skipped: ' . $notificationError->getMessage()); }
    Http::json(['data' => ['id' => $reviewId, 'order_id' => $orderId, 'courier_id' => (int)$order['courier_id'], 'rating' => $rating]], 201);
}
if ($method === 'PATCH' && route('/merchant/stores/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); $input = Http::input(); Http::requireFields($input, ['sham_cash_address']); $address = trim((string)$input['sham_cash_address']);
    $store = $db->prepare('SELECT id, merchant_id FROM stores WHERE id=:id AND merchant_id=:merchant'); $store->execute(['id' => (int)$parameters['id'], 'merchant' => $actor['id']]); if (!$store->fetch()) Http::error('forbidden', 'لا تملك هذا المتجر.', 403);
    if ($address === '') Http::error('validation_error', 'أدخل عنواناً أو معرفاً صالحاً لشام كاش.', 422);
    $db->prepare('INSERT INTO merchant_profiles (user_id, sham_cash_address) VALUES (:merchant, :address) ON CONFLICT(user_id) DO UPDATE SET sham_cash_address=excluded.sham_cash_address')->execute(['address' => $address, 'merchant' => $actor['id']]); Audit::log($db, (int)$actor['id'], 'merchant.sham_cash_address.updated', 'store', (int)$parameters['id'], null, ['updated' => true]); Http::json(['data' => ['store_id' => (int)$parameters['id'], 'sham_cash_address' => $address]]);
}

if ($method === 'GET' && route('/stores/{id}/subscription', $path, $parameters)) {
    $actor = user($db); $storeId = (int)$parameters['id'];
    $store = $db->prepare("SELECT id FROM stores WHERE id=? AND status='active' LIMIT 1"); $store->execute([$storeId]);
    if (!$store->fetch()) Http::error('not_found', 'المتجر غير موجود.', 404);
    if (!schemaTableExists($db, 'store_followers')) Http::json(['data' => ['subscribed' => false, 'status' => null, 'created_at' => null, 'subscriber_count' => 0]]);
    $check = $db->prepare("SELECT status, created_at FROM store_followers WHERE user_id=? AND store_id=? LIMIT 1"); $check->execute([$actor['id'], $storeId]);
    $row = $check->fetch(); $count = $db->prepare("SELECT COUNT(*) FROM store_followers WHERE store_id=? AND status='active'"); $count->execute([$storeId]);
    Http::json(['data' => ['subscribed' => (bool)$row && $row['status'] === 'active', 'status' => $row['status'] ?? null, 'created_at' => $row['created_at'] ?? null, 'subscriber_count' => (int)$count->fetchColumn()]]);
}
if ($method === 'POST' && route('/stores/{id}/subscription', $path, $parameters)) {
    $actor = user($db); $storeId = (int)$parameters['id'];
    $store = $db->prepare("SELECT id FROM stores WHERE id=? AND status='active' LIMIT 1"); $store->execute([$storeId]);
    if (!$store->fetch()) Http::error('not_found', 'المتجر غير موجود.', 404);
    if (!schemaTableExists($db, 'store_followers')) Http::error('service_unavailable', 'خدمة الاشتراك غير مهيأة على الخادم.', 503);
    $db->prepare("INSERT INTO store_followers (user_id, store_id, status) VALUES (?, ?, 'active') ON CONFLICT(user_id, store_id) DO UPDATE SET status='active', updated_at=CURRENT_TIMESTAMP")->execute([$actor['id'], $storeId]);
    $count = $db->prepare("SELECT COUNT(*) FROM store_followers WHERE store_id=? AND status='active'"); $count->execute([$storeId]);
    Http::json(['data' => ['store_id' => $storeId, 'subscribed' => true, 'subscriber_count' => (int)$count->fetchColumn()]]);
}
if ($method === 'DELETE' && route('/stores/{id}/subscription', $path, $parameters)) {
    $actor = user($db); $storeId = (int)$parameters['id'];
    if (schemaTableExists($db, 'store_followers')) $db->prepare('UPDATE store_followers SET status=\'paused\', updated_at=NOW() WHERE user_id=? AND store_id=?')->execute([$actor['id'], $storeId]);
    $count = schemaTableExists($db, 'store_followers') ? $db->prepare("SELECT COUNT(*) FROM store_followers WHERE store_id=? AND status='active'") : null;
    if ($count) { $count->execute([$storeId]); $subscriberCount = (int)$count->fetchColumn(); } else { $subscriberCount = 0; }
    Http::json(['data' => ['store_id' => $storeId, 'subscribed' => false, 'subscriber_count' => $subscriberCount]]);
}
if ($method === 'GET' && route('/stores/{id}/advertisements', $path, $parameters)) {
    $storeId = (int)$parameters['id'];
    $store = $db->prepare("SELECT id FROM stores WHERE id=? AND status='active' LIMIT 1"); $store->execute([$storeId]);
    if (!$store->fetch()) Http::error('not_found', 'المتجر غير موجود.', 404);
    Http::json(['data' => storeAdvertisementRows($db, $storeId, true)]);
}
if ($method === 'GET' && route('/merchant/stores/{id}/advertisements', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); $storeId = (int)$parameters['id']; merchantStoreAdvertisement($db, $storeId, (int)$actor['id']);
    Http::json(['data' => storeAdvertisementRows($db, $storeId, false)]);
}
if ($method === 'POST' && route('/merchant/stores/{id}/advertisements/create-with-image', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']);
    if (!schemaTableExists($db, 'store_advertisements')) Http::error('schema_not_ready', 'ترحيل إعلانات المتجر غير موجود. ارفع ملف 20260827_store_advertisements.sql ثم أعد المحاولة.', 503);
    $storeId = (int)$parameters['id']; merchantStoreAdvertisement($db, $storeId, (int)$actor['id']);
    $input = $_POST;
    $requestedStatus = (string)($input['status'] ?? 'active');
    $input['status'] = $requestedStatus === 'paused' ? 'paused' : 'active';
    [$title, $body, $targetType, $productId, $targetUrl, $order, $starts, $ends, $status] = validateStoreAdvertisementInput($db, $storeId, $input);
    $image = saveUpload('image', (int)Database::environment('MAX_STORE_AD_IMAGE_BYTES', '5242880'), 'store_advertisement');
    $id = 0;
    try {
        $db->beginTransaction();
        $statement = $db->prepare('INSERT INTO store_advertisements (store_id, title, body, image_path, target_type, target_product_id, target_url, display_order, starts_at, ends_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$storeId, $title, $body, $image, $targetType, $productId, $targetUrl, $order, $starts, $ends, $status]);
        $id = (int)$db->lastInsertId();
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        $directory = rtrim(Database::environment('UPLOAD_PATH', __DIR__ . '/storage/uploads'), '/');
        $savedPath = $directory . '/' . basename($image);
        if (is_file($savedPath)) @unlink($savedPath);
        error_log('store advertisement atomic create failed: ' . $exception->getMessage());
        Http::error('store_advertisement_create_failed', 'تعذر حفظ إعلان المتجر. تأكد من رفع تحديث الخادم ثم حاول مجدداً.', 500);
    }
    try { Audit::log($db, (int)$actor['id'], 'store_advertisement.created', 'store_advertisement', $id, null, ['store_id' => $storeId, 'target_type' => $targetType, 'status' => $status]); }
    catch (Throwable $auditException) { error_log('store advertisement audit skipped: ' . $auditException->getMessage()); }
    if ($status === 'active') {
        try { notifyStoreFollowersForAdvertisement($db, $id, $storeId); }
        catch (Throwable $notificationException) { error_log('store advertisement notification skipped: ' . $notificationException->getMessage()); }
    }
    Http::json(['data' => ['id' => $id, 'store_id' => $storeId, 'image_path' => $image, 'status' => $status]], 201);
}
if ($method === 'POST' && route('/merchant/stores/{id}/advertisements', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']);
    if (!schemaTableExists($db, 'store_advertisements')) Http::error('schema_not_ready', 'ترحيل إعلانات المتجر غير موجود. ارفع ملف 20260827_store_advertisements.sql ثم أعد المحاولة.', 503);
    $storeId = (int)$parameters['id']; merchantStoreAdvertisement($db, $storeId, (int)$actor['id']);
    $input = Http::input(); [$title, $body, $targetType, $productId, $targetUrl, $order, $starts, $ends, $status] = validateStoreAdvertisementInput($db, $storeId, $input);
    $statement = $db->prepare('INSERT INTO store_advertisements (store_id, title, body, target_type, target_product_id, target_url, display_order, starts_at, ends_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if ($status === 'active') Http::error('validation_error', 'يجب رفع صورة الإعلان أولاً قبل نشره.', 422);
    $statement->execute([$storeId, $title, $body, $targetType, $productId, $targetUrl, $order, $starts, $ends, $status]);
    $id = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'store_advertisement.created', 'store_advertisement', $id, null, ['store_id' => $storeId, 'target_type' => $targetType]);
    Http::json(['data' => ['id' => $id, 'store_id' => $storeId, 'status' => $status === 'draft' ? 'active' : $status]], 201);
}
if ($method === 'PATCH' && route('/merchant/store-advertisements/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']);
    if (!schemaTableExists($db, 'store_advertisements')) Http::error('schema_not_ready', 'ترحيل إعلانات المتجر غير موجود. ارفع ملف 20260827_store_advertisements.sql ثم أعد المحاولة.', 503);
    $id = (int)$parameters['id'];
    $existing = $db->prepare('SELECT a.* FROM store_advertisements a JOIN stores s ON s.id=a.store_id AND s.merchant_id=? WHERE a.id=? LIMIT 1'); $existing->execute([$actor['id'], $id]); $existing = $existing->fetch();
    if (!$existing) Http::error('not_found', 'إعلان المتجر غير موجود.', 404);
    $input = Http::input(); [$title, $body, $targetType, $productId, $targetUrl, $order, $starts, $ends, $status] = validateStoreAdvertisementInput($db, (int)$existing['store_id'], $input, $existing);
    if ($status === 'active' && empty($existing['image_path'])) Http::error('validation_error', 'يجب رفع صورة الإعلان أولاً قبل نشره.', 422);
    $statement = $db->prepare('UPDATE store_advertisements SET title=?, body=?, target_type=?, target_product_id=?, target_url=?, display_order=?, starts_at=?, ends_at=?, status=? WHERE id=?');
    $statement->execute([$title, $body, $targetType, $productId, $targetUrl, $order, $starts, $ends, $status, $id]);
    Audit::log($db, (int)$actor['id'], 'store_advertisement.updated', 'store_advertisement', $id, ['status' => $existing['status']], ['status' => $status]); Http::json(['data' => ['id' => $id, 'status' => $status]]);
}
if ($method === 'POST' && route('/merchant/store-advertisements/{id}/image', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']);
    if (!schemaTableExists($db, 'store_advertisements')) Http::error('schema_not_ready', 'ترحيل إعلانات المتجر غير موجود. ارفع ملف 20260827_store_advertisements.sql ثم أعد المحاولة.', 503);
    $id = (int)$parameters['id'];
    $existing = $db->prepare('SELECT a.id, a.store_id FROM store_advertisements a JOIN stores s ON s.id=a.store_id AND s.merchant_id=? WHERE a.id=? LIMIT 1'); $existing->execute([$actor['id'], $id]); if (!$existing->fetch()) Http::error('not_found', 'إعلان المتجر غير موجود.', 404);
    $image = saveUpload('image', (int)Database::environment('MAX_STORE_AD_IMAGE_BYTES', '5242880'), 'store_advertisement'); $db->prepare('UPDATE store_advertisements SET image_path=? WHERE id=?')->execute([$image, $id]);
    $details = $db->prepare('SELECT store_id, status FROM store_advertisements WHERE id=?'); $details->execute([$id]); $details = $details->fetch();
    if ($details && $details['status'] === 'active') notifyStoreFollowersForAdvertisement($db, $id, (int)$details['store_id']);
    Audit::log($db, (int)$actor['id'], 'store_advertisement.image.updated', 'store_advertisement', $id); Http::json(['data' => ['id' => $id, 'image_path' => $image]]);
}
if ($method === 'DELETE' && route('/merchant/store-advertisements/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['merchant']); $id = (int)$parameters['id'];
    $statement = $db->prepare('DELETE FROM store_advertisements WHERE id=? AND store_id IN (SELECT id FROM stores WHERE merchant_id=?)'); $statement->execute([$actor['id'], $id]); if ($statement->rowCount() !== 1) Http::error('not_found', 'إعلان المتجر غير موجود.', 404);
    Audit::log($db, (int)$actor['id'], 'store_advertisement.deleted', 'store_advertisement', $id); Http::json(['data' => ['id' => $id, 'deleted' => true]]);
}
if ($method === 'GET' && $path === '/courier/stores/subscriptions') {
    $actor = user($db); role($actor, ['courier']);
    Http::json(['data' => [], 'subscriptions_disabled' => true]);
}
if ($method === 'POST' && route('/courier/stores/{id}/subscriptions', $path, $parameters)) {
    $actor = user($db); role($actor, ['courier']);
    Http::error('subscriptions_disabled', 'تم إلغاء اشتراك العامل بالمتجر. تظهر لك الآن مهام التوصيل المتاحة من جميع المتاجر.', 410);
}

if ($method === 'GET' && $path === '/admin/preorders') {
    $actor = user($db); role($actor, ['admin']); $status = trim((string)($_GET['status'] ?? ''));
    $sql = "SELECT po.*, s.name AS store_name, merchant.full_name AS merchant_name, customer.full_name AS customer_name, (SELECT ppr.id FROM preorder_payment_receipts ppr WHERE ppr.preorder_id=po.id ORDER BY ppr.created_at DESC, ppr.id DESC LIMIT 1) AS payment_receipt_id, (SELECT ppr.status FROM preorder_payment_receipts ppr WHERE ppr.preorder_id=po.id ORDER BY ppr.created_at DESC, ppr.id DESC LIMIT 1) AS payment_receipt_status, (SELECT ppr.transaction_number FROM preorder_payment_receipts ppr WHERE ppr.preorder_id=po.id ORDER BY ppr.created_at DESC, ppr.id DESC LIMIT 1) AS payment_transaction_number, (SELECT ppr.created_at FROM preorder_payment_receipts ppr WHERE ppr.preorder_id=po.id ORDER BY ppr.created_at DESC, ppr.id DESC LIMIT 1) AS payment_submitted_at FROM preorder_orders po JOIN stores s ON s.id=po.store_id JOIN users merchant ON merchant.id=s.merchant_id JOIN users customer ON customer.id=po.customer_id";
    $parameters = []; if ($status !== '') { $sql .= ' WHERE po.status=:status'; $parameters['status'] = $status; }
    $sql .= ' ORDER BY CASE WHEN po.status=\'payment_review\' THEN 0 ELSE 1 END, po.requested_fulfillment_at ASC, po.id DESC LIMIT 200'; $statement = $db->prepare($sql); $statement->execute($parameters); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && route('/admin/preorder-payment-receipts/{id}/verify', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $receiptId = (int)$parameters['id'];
    $db->beginTransaction();
    try {
        $receipt = $db->prepare("SELECT ppr.*, po.products_subtotal, po.platform_fee_total, po.currency, po.id AS preorder_id, s.merchant_id FROM preorder_payment_receipts ppr JOIN preorder_orders po ON po.id=ppr.preorder_id JOIN stores s ON s.id=po.store_id WHERE ppr.id=? AND ppr.status='submitted' FOR UPDATE"); $receipt->execute([$receiptId]); $receipt = $receipt->fetch(); if (!$receipt) Http::error('not_found', 'سند دفع الطلب المسبق غير متاح للمراجعة.', 404);
        $db->prepare("UPDATE preorder_payment_receipts SET status='verified', reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$actor['id'], $receiptId]); $db->prepare("UPDATE preorder_orders SET status='paid' WHERE id=?")->execute([$receipt['preorder_id']]);
        $db->prepare('INSERT OR IGNORE INTO wallets (user_id, currency, available_balance) VALUES (?, ?, 0)')->execute([$receipt['merchant_id'], $receipt['currency']]); $merchantWallet = $db->prepare('SELECT id, available_balance FROM wallets WHERE user_id=? AND currency=? FOR UPDATE'); $merchantWallet->execute([$receipt['merchant_id'], $receipt['currency']]); $merchantWallet = $merchantWallet->fetch(); $merchantBalance = (float)$merchantWallet['available_balance'] + (float)$receipt['products_subtotal']; $db->prepare('UPDATE wallets SET available_balance=? WHERE id=?')->execute([$merchantBalance, $merchantWallet['id']]); $db->prepare("INSERT INTO wallet_transactions (wallet_id, transaction_type, direction, amount, balance_after, reference_type, reference_id, description, created_by) VALUES (?, 'merchant_sale', 'credit', ?, ?, 'preorder_order', ?, 'رصيد طلب مسبق معتمد', ?)")->execute([$merchantWallet['id'], $receipt['products_subtotal'], $merchantBalance, $receipt['preorder_id'], $actor['id']]);
        $db->prepare('INSERT OR IGNORE INTO platform_wallets (currency, available_balance) VALUES (?, 0)')->execute([$receipt['currency']]); $platformWallet = $db->prepare('SELECT id, available_balance FROM platform_wallets WHERE currency=? FOR UPDATE'); $platformWallet->execute([$receipt['currency']]); $platformWallet = $platformWallet->fetch(); $platformBalance = (float)$platformWallet['available_balance'] + (float)$receipt['platform_fee_total']; $db->prepare('UPDATE platform_wallets SET available_balance=? WHERE id=?')->execute([$platformBalance, $platformWallet['id']]); if ((float)$receipt['platform_fee_total'] > 0) $db->prepare("INSERT INTO platform_wallet_transactions (wallet_id, transaction_type, direction, amount, balance_after, preorder_id, description, created_by) VALUES (?, 'sale_fee', 'credit', ?, ?, ?, 'رسم منصة من طلب مسبق معتمد', ?)")->execute([$platformWallet['id'], $receipt['platform_fee_total'], $platformBalance, $receipt['preorder_id'], $actor['id']]);
        Audit::log($db, (int)$actor['id'], 'preorder.payment.verified', 'preorder_payment_receipt', $receiptId, ['status' => 'submitted'], ['status' => 'verified']); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $receiptId, 'status' => 'verified']]);
}

if ($method === 'POST' && route('/admin/preorder-payment-receipts/{id}/reject', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['reason']); $receiptId = (int)$parameters['id'];
    $db->beginTransaction();
    try { $receipt = $db->prepare("SELECT preorder_id FROM preorder_payment_receipts WHERE id=? AND status='submitted' FOR UPDATE"); $receipt->execute([$receiptId]); $receipt = $receipt->fetch(); if (!$receipt) Http::error('not_found', 'سند الدفع غير متاح للمراجعة.', 404); $db->prepare("UPDATE preorder_payment_receipts SET status='rejected', reviewed_by=?, reviewed_at=NOW(), rejection_reason=? WHERE id=?")->execute([$actor['id'], trim((string)$input['reason']), $receiptId]); $db->prepare("UPDATE preorder_orders SET status='pending_payment', merchant_payment_review_status='pending' WHERE id=?")->execute([$receipt['preorder_id']]); Audit::log($db, (int)$actor['id'], 'preorder.payment.rejected', 'preorder_payment_receipt', $receiptId, ['status' => 'submitted'], ['status' => 'rejected']); $db->commit(); }
    catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $receiptId, 'status' => 'rejected']]);
}

if ($method === 'POST' && route('/admin/payment-receipts/{id}/verify', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $receiptId = (int)$parameters['id'];
    $db->beginTransaction(); try { $receipt = $db->prepare("SELECT pr.*, o.fulfillment_type, o.delivery_total, o.products_subtotal, o.platform_fee_total, o.currency, o.id AS order_id, s.merchant_id FROM payment_receipts pr JOIN orders o ON o.id=pr.order_id JOIN stores s ON s.id=o.store_id WHERE pr.id=:id AND pr.status='submitted' FOR UPDATE"); $receipt->execute(['id' => $receiptId]); $receipt = $receipt->fetch(); if (!$receipt) Http::error('not_found', 'سند الدفع غير متاح للمراجعة.', 404); $db->prepare("UPDATE payment_receipts SET status='verified', reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$actor['id'], $receiptId]); $nextStatus = 'paid'; $db->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$nextStatus, $receipt['order_id']]); $db->prepare('UPDATE products SET sales_count = sales_count + (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_items.product_id = products.id AND order_items.order_id = ?) WHERE id IN (SELECT product_id FROM order_items WHERE order_id = ?)')->execute([$receipt['order_id'], $receipt['order_id']]); $db->prepare('INSERT OR IGNORE INTO wallets (user_id, currency, available_balance) VALUES (?, ?, 0)')->execute([$receipt['merchant_id'], $receipt['currency']]); $merchantWallet = $db->prepare('SELECT id, available_balance FROM wallets WHERE user_id=? AND currency=? FOR UPDATE'); $merchantWallet->execute([$receipt['merchant_id'], $receipt['currency']]); $merchantWallet = $merchantWallet->fetch(); $merchantBalance = (float)$merchantWallet['available_balance'] + (float)$receipt['products_subtotal']; $db->prepare('UPDATE wallets SET available_balance=? WHERE id=?')->execute([$merchantBalance, $merchantWallet['id']]); $db->prepare("INSERT INTO wallet_transactions (wallet_id, transaction_type, direction, amount, balance_after, reference_type, reference_id, description, created_by) VALUES (?, 'merchant_sale', 'credit', ?, ?, 'order', ?, 'رصيد مبيعات معتمد', ?)")->execute([$merchantWallet['id'], $receipt['products_subtotal'], $merchantBalance, $receipt['order_id'], $actor['id']]); $db->prepare('INSERT OR IGNORE INTO platform_wallets (currency, available_balance) VALUES (?, 0)')->execute([$receipt['currency']]); $platformWallet = $db->prepare('SELECT id, available_balance FROM platform_wallets WHERE currency=? FOR UPDATE'); $platformWallet->execute([$receipt['currency']]); $platformWallet = $platformWallet->fetch(); $platformBalance = (float)$platformWallet['available_balance'] + (float)$receipt['platform_fee_total']; $db->prepare('UPDATE platform_wallets SET available_balance=? WHERE id=?')->execute([$platformBalance, $platformWallet['id']]); if ((float)$receipt['platform_fee_total'] > 0) $db->prepare("INSERT INTO platform_wallet_transactions (wallet_id, transaction_type, direction, amount, balance_after, order_id, description, created_by) VALUES (?, 'sale_fee', 'credit', ?, ?, ?, 'رسم منصة من بيع معتمد', ?)")->execute([$platformWallet['id'], $receipt['platform_fee_total'], $platformBalance, $receipt['order_id'], $actor['id']]); Audit::log($db, (int)$actor['id'], 'payment.verified', 'payment_receipt', $receiptId, ['status' => 'submitted'], ['status' => 'verified', 'platform_fee_total' => $receipt['platform_fee_total']]); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $receiptId, 'status' => 'verified']]);
}

if ($method === 'POST' && route('/admin/payment-receipts/{id}/reject', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['reason']); $receiptId = (int)$parameters['id'];
    $db->beginTransaction(); try { $receipt = $db->prepare("SELECT order_id FROM payment_receipts WHERE id=:id AND status='submitted' FOR UPDATE"); $receipt->execute(['id' => $receiptId]); $receipt = $receipt->fetch(); if (!$receipt) Http::error('not_found', 'سند الدفع غير متاح للمراجعة.', 404); $db->prepare("UPDATE payment_receipts SET status='rejected', reviewed_by=?, reviewed_at=NOW(), rejection_reason=? WHERE id=?")->execute([$actor['id'], trim($input['reason']), $receiptId]); $db->prepare("UPDATE orders SET status='pending_payment' WHERE id=?")->execute([$receipt['order_id']]); Audit::log($db, (int)$actor['id'], 'payment.rejected', 'payment_receipt', $receiptId, ['status' => 'submitted'], ['status' => 'rejected']); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $receiptId, 'status' => 'rejected']]);
}

if ($method === 'GET' && $path === '/admin/withdrawals') {
    $actor = user($db); role($actor, ['admin']); $status = trim((string)($_GET['status'] ?? 'all')); $allowed = ['requested', 'under_review', 'approved', 'rejected', 'paid', 'all']; if (!in_array($status, $allowed, true)) Http::error('validation_error', 'حالة السحب غير صالحة.', 422);
    $where = $status === 'all' ? '1=1' : 'wr.status=:status'; $sql = "SELECT wr.id, wr.user_id, wr.wallet_id, wr.amount, w.currency, wr.payout_method, wr.payout_details, wr.status, wr.reviewed_by, wr.review_note, wr.created_at, wr.reviewed_at, u.full_name AS user_name, u.phone AS user_phone, reviewer.full_name AS reviewer_name FROM withdrawal_requests wr JOIN wallets w ON w.id=wr.wallet_id JOIN users u ON u.id=wr.user_id LEFT JOIN users reviewer ON reviewer.id=wr.reviewed_by WHERE u.role='courier' AND {$where} ORDER BY wr.created_at DESC LIMIT 300"; $statement = $db->prepare($sql); $statement->execute($status === 'all' ? [] : ['status' => $status]); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && route('/admin/withdrawals/{id}/approve', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $withdrawalId = (int)$parameters['id'];
    $db->beginTransaction(); try { $request = $db->prepare("SELECT wr.*, w.pending_balance, w.available_balance, w.currency FROM withdrawal_requests wr JOIN wallets w ON w.id=wr.wallet_id JOIN users wu ON wu.id=wr.user_id WHERE wr.id=:id AND wu.role='courier' AND wr.status IN ('requested','under_review') FOR UPDATE"); $request->execute(['id' => $withdrawalId]); $request = $request->fetch(); if (!$request) Http::error('not_found', 'طلب السحب غير متاح.', 404); $db->prepare("UPDATE withdrawal_requests SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$actor['id'], $withdrawalId]); $db->prepare('UPDATE wallets SET pending_balance=GREATEST(0, pending_balance-:amount) WHERE id=:id')->execute(['amount' => $request['amount'], 'id' => $request['wallet_id']]); $db->prepare("INSERT INTO wallet_transactions (wallet_id, transaction_type, direction, amount, balance_after, reference_type, reference_id, description, created_by) VALUES (?, 'withdrawal_paid', 'debit', ?, ?, 'withdrawal_request', ?, 'سحب معتمد من الإدارة', ?)")->execute([$request['wallet_id'], $request['amount'], (float)$request['available_balance'], $withdrawalId, $actor['id']]); Audit::log($db, (int)$actor['id'], 'withdrawal.approved', 'withdrawal_request', $withdrawalId, ['status' => $request['status']], ['status' => 'approved', 'amount' => (float)$request['amount'], 'currency' => $request['currency']]); marketplaceNotify($db, (int)$request['user_id'], 'withdrawal_approved', 'تمت الموافقة على السحب', 'وافقت الإدارة على طلب السحب الخاص بك.', 'withdrawal_approved:' . $withdrawalId . ':' . (int)$request['user_id'], [], ['withdrawal_request_id' => $withdrawalId, 'amount' => (float)$request['amount'], 'currency' => $request['currency']]); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $withdrawalId, 'status' => 'approved']]);
}

if ($method === 'POST' && route('/admin/withdrawals/{id}/reject', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['reason']); $withdrawalId = (int)$parameters['id'];
    $db->beginTransaction(); try { $request = $db->prepare("SELECT wr.*, w.available_balance, w.pending_balance, w.currency FROM withdrawal_requests wr JOIN wallets w ON w.id=wr.wallet_id JOIN users wu ON wu.id=wr.user_id WHERE wr.id=:id AND wu.role='courier' AND wr.status IN ('requested','under_review') FOR UPDATE"); $request->execute(['id' => $withdrawalId]); $request = $request->fetch(); if (!$request) Http::error('not_found', 'طلب السحب غير متاح.', 404); $db->prepare("UPDATE withdrawal_requests SET status='rejected', reviewed_by=?, reviewed_at=NOW(), review_note=? WHERE id=?")->execute([$actor['id'], trim($input['reason']), $withdrawalId]); $db->prepare('UPDATE wallets SET pending_balance=GREATEST(0, pending_balance-:amount), available_balance=available_balance+:amount WHERE id=:id')->execute(['amount' => $request['amount'], 'id' => $request['wallet_id']]); $db->prepare("INSERT INTO wallet_transactions (wallet_id, transaction_type, direction, amount, balance_after, reference_type, reference_id, description, created_by) VALUES (?, 'withdrawal_release', 'credit', ?, ?, 'withdrawal_request', ?, 'إعادة مبلغ سحب مرفوض', ?)")->execute([$request['wallet_id'], $request['amount'], (float)$request['available_balance'] + (float)$request['amount'], $withdrawalId, $actor['id']]); Audit::log($db, (int)$actor['id'], 'withdrawal.rejected', 'withdrawal_request', $withdrawalId, ['status' => $request['status']], ['status' => 'rejected', 'reason' => trim($input['reason']), 'amount' => (float)$request['amount'], 'currency' => $request['currency']]); marketplaceNotify($db, (int)$request['user_id'], 'withdrawal_rejected', 'تم رفض طلب السحب', 'رفضت الإدارة طلب السحب: ' . trim($input['reason']), 'withdrawal_rejected:' . $withdrawalId . ':' . (int)$request['user_id'], [], ['withdrawal_request_id' => $withdrawalId, 'amount' => (float)$request['amount'], 'currency' => $request['currency'], 'reason' => trim($input['reason'])]); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $withdrawalId, 'status' => 'rejected']]);
}

if ($method === 'GET' && $path === '/admin/users') {
    $actor = user($db); role($actor, ['admin']); $q = trim((string)($_GET['q'] ?? '')); $roleFilter = $_GET['role'] ?? null; $statusFilter = $_GET['status'] ?? null; $createdFrom = trim((string)($_GET['created_from'] ?? '')); $createdTo = trim((string)($_GET['created_to'] ?? '')); $createdOrder = (string)($_GET['created_order'] ?? 'newest');
    $parseDate = static function (string $value, string $field): ?string { if ($value === '') return null; $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value); if (!$date || $date->format('Y-m-d') !== $value) Http::error('validation_error', "صيغة {$field} يجب أن تكون YYYY-MM-DD.", 422); return $date->format('Y-m-d'); }; $createdFrom = $parseDate($createdFrom, 'تاريخ البداية'); $createdTo = $parseDate($createdTo, 'تاريخ النهاية'); if ($createdFrom !== null && $createdTo !== null && $createdFrom > $createdTo) Http::error('validation_error', 'تاريخ البداية يجب ألا يتجاوز تاريخ النهاية.', 422); if (!in_array($createdOrder, ['newest','oldest'], true)) $createdOrder = 'newest';
    $sql = "SELECT u.id, u.full_name, u.phone, u.email, u.role, u.status, u.profile_update_permission, u.avatar_path, u.created_at, u.last_login_at, CASE WHEN vr.status='approved' AND vr.is_active=1 THEN 1 ELSE 0 END AS is_verified FROM users u LEFT JOIN verification_requests vr ON vr.user_id=u.id AND vr.subject_role=u.role AND vr.status='approved' WHERE 1=1"; $args = []; if ($q !== '') { $sql .= ' AND (u.full_name LIKE :q OR u.phone LIKE :q OR u.email LIKE :q)'; $args['q'] = "%{$q}%"; } if (in_array($roleFilter, ['customer','merchant','courier','admin'], true)) { $sql .= ' AND u.role=:role'; $args['role'] = $roleFilter; } if (in_array($statusFilter, ['active','restricted','pending','deleted'], true)) { $sql .= ' AND u.status=:status'; $args['status'] = $statusFilter; } if ($createdFrom !== null) { $sql .= ' AND u.created_at >= :created_from'; $args['created_from'] = $createdFrom . ' 00:00:00'; } if ($createdTo !== null) { $sql .= " AND u.created_at < datetime(:created_to, '+1 day')"; $args['created_to'] = $createdTo; } $sql .= ' ORDER BY u.created_at ' . ($createdOrder === 'oldest' ? 'ASC' : 'DESC') . ', u.id DESC LIMIT 200'; $statement = $db->prepare($sql); $statement->execute($args); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && $path === '/admin/users') {
    $actor = user($db); role($actor, ['admin']); $input = Http::input();
    Http::requireFields($input, ['full_name', 'phone', 'password', 'role']);
    $fullName = trim((string)$input['full_name']);
    $phone = strtr(trim((string)$input['phone']), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    $phone = preg_replace('/[\s\-()]/', '', $phone) ?? '';
    $email = trim((string)($input['email'] ?? '')) ?: null;
    $accountRole = (string)$input['role'];
    $status = (string)($input['status'] ?? 'active');
    $permission = (string)($input['profile_update_permission'] ?? 'allowed');
    if (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 120 || !preg_match('/^\+?[0-9]{8,32}$/', $phone) || mb_strlen((string)$input['password']) < 8) Http::error('validation_error', 'تحقق من الاسم ورقم الهاتف وكلمة المرور.', 422);
    if ($email !== null && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 191)) Http::error('validation_error', 'البريد الإلكتروني غير صالح.', 422);
    if (!in_array($accountRole, ['customer', 'merchant', 'courier'], true) || !in_array($status, ['active','restricted','pending'], true) || !in_array($permission, ['allowed','locked'], true)) Http::error('validation_error', 'إعدادات الحساب غير صحيحة.', 422);
    $db->beginTransaction();
    try {
        $duplicateWhere = 'phone=:phone'; $duplicateParams = ['phone' => $phone];
        if ($email !== null) { $duplicateWhere .= ' OR email=:email'; $duplicateParams['email'] = $email; }
        $duplicate = $db->prepare("SELECT id FROM users WHERE ({$duplicateWhere}) LIMIT 1 FOR UPDATE"); $duplicate->execute($duplicateParams);
        if ($duplicate->fetch()) Http::error('duplicate_account', 'رقم الهاتف أو البريد الإلكتروني مستخدم مسبقاً.', 409);
        $db->prepare('INSERT INTO users (full_name, phone, email, password_hash, role, status, profile_update_permission) VALUES (:name, :phone, :email, :password, :role, :status, :permission)')->execute(['name' => $fullName, 'phone' => $phone, 'email' => $email, 'password' => password_hash((string)$input['password'], PASSWORD_DEFAULT), 'role' => $accountRole, 'status' => $status, 'permission' => $permission]);
        $id = (int)$db->lastInsertId();
        if ($accountRole === 'merchant') $db->prepare('INSERT INTO merchant_profiles (user_id, sham_cash_address) VALUES (?, ?)')->execute([$id, trim((string)($input['sham_cash_address'] ?? ''))]);
        if ($accountRole === 'courier') $db->prepare('INSERT INTO courier_profiles (user_id, vehicle_type, is_available, verification_status) VALUES (?, ?, ?, \'pending\')')->execute([$id, trim((string)($input['vehicle_type'] ?? '')) ?: null, array_key_exists('is_available', $input) ? (!empty($input['is_available']) ? 1 : 0) : 1]);
        Audit::log($db, (int)$actor['id'], 'user.admin.created', 'user', $id, null, ['role' => $accountRole, 'status' => $status]); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $id, 'role' => $accountRole, 'status' => $status]], 201);
}

if ($method === 'PATCH' && route('/admin/users/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); $userId = (int)$parameters['id']; if (!$input) Http::error('validation_error', 'أدخل حقلاً واحداً على الأقل للتعديل.', 422);
    $requestedVerification = array_key_exists('is_verified', $input) ? (!empty($input['is_verified']) ? 1 : 0) : null;
    $statement = $db->prepare('SELECT id, full_name, phone, email, role, status, profile_update_permission FROM users WHERE id=? FOR UPDATE'); $db->beginTransaction(); try {
        $statement->execute([$userId]); $target = $statement->fetch(); if (!$target) Http::error('not_found', 'الحساب غير موجود.', 404);
        if ($target['role'] === 'admin' && (int)$target['id'] !== (int)$actor['id']) Http::error('forbidden', 'لا يمكن تعديل حساب مدير آخر من هذا المسار.', 403);
        $fullName = trim((string)($input['full_name'] ?? $target['full_name']));
        $phone = strtr(trim((string)($input['phone'] ?? $target['phone'])), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']); $phone = preg_replace('/[\s\-()]/', '', $phone) ?? '';
        $email = array_key_exists('email', $input) ? (trim((string)$input['email']) ?: null) : $target['email'];
        $accountRole = (string)($input['role'] ?? $target['role']); $status = (string)($input['status'] ?? $target['status']); $permission = (string)($input['profile_update_permission'] ?? $target['profile_update_permission']);
        if (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 120 || !preg_match('/^\+?[0-9]{8,32}$/', $phone) || ($email !== null && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 191))) Http::error('validation_error', 'تحقق من الاسم ورقم الهاتف والبريد الإلكتروني.', 422);
        if (!in_array($accountRole, ['customer','merchant','courier','admin'], true) || !in_array($status, ['active','restricted','pending','deleted'], true) || !in_array($permission, ['allowed','locked'], true)) Http::error('validation_error', 'إعدادات الحساب غير صحيحة.', 422);
        if ($target['role'] === 'admin' && $accountRole !== 'admin') Http::error('forbidden', 'لا يمكن تغيير دور المدير من هذا المسار.', 403);
        if ($accountRole === 'admin' && $target['role'] !== 'admin') Http::error('forbidden', 'لا يمكن ترقية حساب إلى مدير من هذا المسار.', 403);
        $duplicateWhere = 'phone=:phone'; $duplicateParams = ['phone' => $phone, 'id' => $userId];
        if ($email !== null) { $duplicateWhere .= ' OR email=:email'; $duplicateParams['email'] = $email; }
        $duplicate = $db->prepare("SELECT id FROM users WHERE ({$duplicateWhere}) AND id<>:id LIMIT 1"); $duplicate->execute($duplicateParams); if ($duplicate->fetch()) Http::error('duplicate_account', 'رقم الهاتف أو البريد الإلكتروني مستخدم مسبقاً.', 409);
        $passwordSql = ''; $params = ['name' => $fullName, 'phone' => $phone, 'email' => $email, 'role' => $accountRole, 'status' => $status, 'permission' => $permission, 'id' => $userId];
        if (array_key_exists('password', $input) && trim((string)$input['password']) !== '') { if (mb_strlen((string)$input['password']) < 8) Http::error('validation_error', 'كلمة المرور يجب أن تتكون من 8 محارف على الأقل.', 422); $passwordSql = ', password_hash=:password'; $params['password'] = password_hash((string)$input['password'], PASSWORD_DEFAULT); }
        $db->prepare("UPDATE users SET full_name=:name, phone=:phone, email=:email, role=:role, status=:status, profile_update_permission=:permission{$passwordSql} WHERE id=:id")->execute($params);
        if ($accountRole === 'merchant') $db->prepare('INSERT OR IGNORE INTO merchant_profiles (user_id, sham_cash_address) VALUES (?, ?)')->execute([$userId, trim((string)($input['sham_cash_address'] ?? ''))]);
        if ($accountRole === 'courier') {
            $profile = $db->prepare('SELECT vehicle_type, is_available FROM courier_profiles WHERE user_id=?'); $profile->execute([$userId]); $profile = $profile->fetch();
            $vehicle = array_key_exists('vehicle_type', $input) ? (trim((string)$input['vehicle_type']) ?: null) : ($profile['vehicle_type'] ?? null);
            $available = array_key_exists('is_available', $input) ? (!empty($input['is_available']) ? 1 : 0) : (int)($profile['is_available'] ?? 1);
            $db->prepare('INSERT INTO courier_profiles (user_id, vehicle_type, is_available, verification_status) VALUES (?, ?, ?, \'pending\') ON DUPLICATE KEY UPDATE vehicle_type=VALUES(vehicle_type), is_available=VALUES(is_available)')->execute([$userId, $vehicle, $available]);
            if ($requestedVerification !== null) {
                $verification = $db->prepare("SELECT id FROM verification_requests WHERE user_id=? AND subject_role='courier' LIMIT 1 FOR UPDATE"); $verification->execute([$userId]); $verificationId = $verification->fetchColumn();
                if ($verificationId) {
                    $nextVerificationState = $requestedVerification === 1 ? 'approved' : 'revoked';
                    $db->prepare("UPDATE verification_requests SET status='approved', is_active=?, review_note=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$requestedVerification, $requestedVerification === 1 ? 'تم اعتماد التوثيق من الإدارة.' : 'تم إيقاف التوثيق من الإدارة.', $actor['id'], $verificationId]);
                    syncVerificationSubject($db, $userId, 'courier', $nextVerificationState);
                } elseif ($requestedVerification === 1) {
                    Http::error('verification_documents_required', 'لا يمكن اعتماد العامل يدوياً قبل إرسال طلب التوثيق ومرفقاته.', 422);
                } else {
                    syncVerificationSubject($db, $userId, 'courier', 'rejected');
                }
            }
        }
        Audit::log($db, (int)$actor['id'], 'user.admin.updated', 'user', $userId, ['role' => $target['role'], 'status' => $target['status']], ['role' => $accountRole, 'status' => $status, 'is_verified' => $requestedVerification]); $db->commit();
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['id' => $userId, 'role' => $accountRole, 'status' => $status, 'profile_update_permission' => $permission]]);
}

if ($method === 'GET' && $path === '/admin/stores') {
    $actor = user($db); role($actor, ['admin']); $q = trim((string)($_GET['q'] ?? '')); $status = $_GET['status'] ?? null; $sql = 'SELECT s.id, s.name, s.description, s.logo_path, s.phone, s.status, s.is_verified, s.created_at, u.id AS merchant_id, u.full_name AS merchant_name, u.phone AS merchant_phone FROM stores s JOIN users u ON u.id=s.merchant_id WHERE 1=1'; $args = []; if ($q !== '') { $sql .= ' AND (s.name LIKE :q OR u.full_name LIKE :q OR s.phone LIKE :q)'; $args['q'] = "%{$q}%"; } if (in_array($status, ['draft','active','suspended'], true)) { $sql .= ' AND s.status=:status'; $args['status'] = $status; } $sql .= ' ORDER BY s.created_at DESC LIMIT 200'; $statement = $db->prepare($sql); $statement->execute($args); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'GET' && $path === '/admin/products') {
    $actor = user($db); role($actor, ['admin']);
    $q = trim((string)($_GET['q'] ?? ''));
    $sql = "SELECT p.id, p.name, p.store_id, p.status, s.name AS store_name,
                   (SELECT file_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY sort_order, id LIMIT 1) AS image_path
            FROM products p JOIN stores s ON s.id=p.store_id WHERE p.status <> 'hidden'";
    $args = [];
    if ($q !== '') {
        $sql .= ' AND (p.name LIKE :q OR s.name LIKE :q OR CAST(p.id AS CHAR) LIKE :q)';
        $args['q'] = "%{$q}%";
    }
    $sql .= ' ORDER BY p.created_at DESC LIMIT 200';
    $statement = $db->prepare($sql); $statement->execute($args);
    Http::json(['data' => $statement->fetchAll()]);
}
if ($method === 'POST' && $path === '/admin/stores') {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); Http::requireFields($input, ['merchant_id', 'name']);
    $merchantId = (int)$input['merchant_id']; $name = trim((string)$input['name']); $status = (string)($input['status'] ?? 'draft');
    if ($merchantId < 1 || mb_strlen($name) < 2 || mb_strlen($name) > 160 || !in_array($status, ['draft','active','suspended'], true)) Http::error('validation_error', 'بيانات المتجر غير صحيحة.', 422);
    $merchant = $db->prepare("SELECT id FROM users WHERE id=? AND role='merchant' AND status<>'deleted' LIMIT 1"); $merchant->execute([$merchantId]); if (!$merchant->fetch()) Http::error('validation_error', 'اختر حساب تاجر صالحاً لمالك المتجر.', 422);
    $db->prepare('INSERT INTO stores (merchant_id, name, description, phone, status, is_verified) VALUES (?, ?, ?, ?, ?, ?)')->execute([$merchantId, $name, trim((string)($input['description'] ?? '')) ?: null, trim((string)($input['phone'] ?? '')) ?: null, $status, !empty($input['is_verified']) ? 1 : 0]);
    $id = (int)$db->lastInsertId(); Audit::log($db, (int)$actor['id'], 'store.admin.created', 'store', $id, null, ['merchant_id' => $merchantId, 'status' => $status]); Http::json(['data' => ['id' => $id, 'status' => $status]], 201);
}

if ($method === 'PATCH' && route('/admin/stores/{id}', $path, $parameters)) {
    $actor = user($db); role($actor, ['admin']); $input = Http::input(); $id = (int)$parameters['id']; if (!$input) Http::error('validation_error', 'أدخل حقلاً واحداً على الأقل للتعديل.', 422);
    $statement = $db->prepare('SELECT * FROM stores WHERE id=?'); $statement->execute([$id]); $store = $statement->fetch(); if (!$store) Http::error('not_found', 'المتجر غير موجود.', 404);
    $merchantId = (int)($input['merchant_id'] ?? $store['merchant_id']); $name = trim((string)($input['name'] ?? $store['name'])); $description = trim((string)($input['description'] ?? $store['description'])) ?: null; $phone = trim((string)($input['phone'] ?? $store['phone'])) ?: null; $status = (string)($input['status'] ?? $store['status']); $verified = array_key_exists('is_verified', $input) ? (!empty($input['is_verified']) ? 1 : 0) : (int)$store['is_verified'];
    if ($merchantId < 1 || mb_strlen($name) < 2 || mb_strlen($name) > 160 || !in_array($status, ['draft','active','suspended'], true)) Http::error('validation_error', 'بيانات المتجر غير صحيحة.', 422);
    $merchant = $db->prepare("SELECT id FROM users WHERE id=? AND role='merchant' AND status<>'deleted' LIMIT 1"); $merchant->execute([$merchantId]); if (!$merchant->fetch()) Http::error('validation_error', 'اختر حساب تاجر صالحاً لمالك المتجر.', 422);
    $db->prepare('UPDATE stores SET merchant_id=?, name=?, description=?, phone=?, status=?, is_verified=? WHERE id=?')->execute([$merchantId, $name, $description, $phone, $status, $verified, $id]); Audit::log($db, (int)$actor['id'], 'store.admin.updated', 'store', $id, ['status' => $store['status']], ['status' => $status, 'merchant_id' => $merchantId]); Http::json(['data' => ['id' => $id, 'status' => $status, 'is_verified' => (bool)$verified]]);
}

if ($method === 'GET' && $path === '/admin/couriers') {
    $actor = user($db); role($actor, ['admin']); $q = trim((string)($_GET['q'] ?? '')); $sql = "SELECT u.id, u.full_name, u.phone, u.email, u.status, u.profile_update_permission, u.created_at, cp.vehicle_type, cp.is_available, CASE WHEN EXISTS (SELECT 1 FROM verification_requests vr_active WHERE vr_active.user_id=u.id AND vr_active.subject_role='courier' AND vr_active.status='approved' AND vr_active.is_active=1) THEN 'verified' WHEN EXISTS (SELECT 1 FROM verification_requests vr_revoked WHERE vr_revoked.user_id=u.id AND vr_revoked.subject_role='courier' AND vr_revoked.status='approved' AND vr_revoked.is_active=0) THEN 'revoked' ELSE cp.verification_status END AS verification_status, CASE WHEN EXISTS (SELECT 1 FROM verification_requests vr_badge WHERE vr_badge.user_id=u.id AND vr_badge.subject_role='courier' AND vr_badge.status='approved' AND vr_badge.is_active=1) THEN 1 ELSE 0 END AS is_verified, COUNT(css.store_id) AS subscribed_stores FROM users u JOIN courier_profiles cp ON cp.user_id=u.id LEFT JOIN courier_store_subscriptions css ON css.courier_id=u.id AND css.status='active' WHERE u.role='courier'"; $args = []; if ($q !== '') { $sql .= ' AND (u.full_name LIKE :q OR u.phone LIKE :q)'; $args['q'] = "%{$q}%"; } $sql .= ' GROUP BY u.id ORDER BY u.created_at DESC LIMIT 200'; $statement = $db->prepare($sql); $statement->execute($args); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'GET' && $path === '/admin/audit-logs') {
    $actor = user($db); role($actor, ['admin']); $q = trim((string)($_GET['q'] ?? '')); $from = $_GET['from'] ?? null; $to = $_GET['to'] ?? null;
    $sql = 'SELECT a.*, u.full_name AS actor_name FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_id WHERE 1=1'; $args = []; if ($q !== '') { $sql .= ' AND (a.action LIKE :q OR a.entity_type LIKE :q OR u.full_name LIKE :q)'; $args['q'] = "%{$q}%"; } if ($from) { $sql .= ' AND a.created_at >= :from'; $args['from'] = $from; } if ($to) { $sql .= " AND a.created_at < datetime(:to, '+1 day')"; $args['to'] = $to; } $sql .= ' ORDER BY a.created_at DESC LIMIT 500'; $statement = $db->prepare($sql); $statement->execute($args); Http::json(['data' => $statement->fetchAll()]);
}

if ($method === 'POST' && route('/support/tickets/{id}/messages', $path, $parameters)) {
    $actor = user($db); $input = Http::input(); Http::requireFields($input, ['message']); $ticketId = (int)$parameters['id'];
    $ticket = $db->prepare('SELECT user_id, status FROM support_tickets WHERE id=?'); $ticket->execute([$ticketId]); $ticket = $ticket->fetch(); if (!$ticket) Http::error('not_found', 'التذكرة غير موجودة.', 404); if ($actor['role'] !== 'admin' && (int)$ticket['user_id'] !== (int)$actor['id']) Http::error('forbidden', 'لا يمكنك المراسلة في هذه التذكرة.', 403);
    $db->beginTransaction(); try { $db->prepare('INSERT INTO support_messages (ticket_id, sender_id, body, attachment_path) VALUES (?, ?, ?, ?)')->execute([$ticketId, $actor['id'], trim($input['message']), $input['attachment_path'] ?? null]); $nextStatus = $actor['role'] === 'admin' ? 'waiting_user' : 'in_progress'; $db->prepare('UPDATE support_tickets SET status=?, updated_at=NOW() WHERE id=?')->execute([$nextStatus, $ticketId]); Audit::log($db, (int)$actor['id'], 'ticket.message.created', 'support_ticket', $ticketId); $db->commit(); } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['ticket_id' => $ticketId, 'status' => $nextStatus]], 201);
}

if ($method === 'POST' && route('/customer/orders/{id}/confirm-delivery', $path, $parameters)) {
    $actor = user($db); role($actor, ['customer']); $orderId = (int)$parameters['id']; $customerProofFile = null;
    $db->beginTransaction();
    try {
        $statement = $db->prepare("SELECT dt.*, o.customer_id, o.order_number, o.status AS order_status, o.store_id, s.merchant_id, s.name AS store_name FROM delivery_tasks dt JOIN orders o ON o.id=dt.order_id JOIN stores s ON s.id=o.store_id WHERE o.id=:order AND o.customer_id=:customer AND dt.courier_id IS NOT NULL AND dt.status IN ('in_transit','proof_submitted') AND (dt.courier_delivery_proof_image_path IS NOT NULL OR dt.delivery_proof_image_path IS NOT NULL OR dt.proof_image_path IS NOT NULL) FOR UPDATE");
        $statement->execute(['order' => $orderId, 'customer' => $actor['id']]); $task = $statement->fetch();
        if (!$task) Http::error('delivery_confirmation_unavailable', 'لا يمكن تأكيد التسليم لهذا الطلب حالياً. يجب أن يكون عامل التوصيل قد أرفق صورة الاستلام.', 409);
        if (isset($_FILES['image'])) $customerProofFile = saveUpload('image', (int)Database::environment('MAX_PROOF_IMAGE_BYTES', '5242880'), 'customer_delivery');
        if ($customerProofFile !== null) {
            $db->prepare("UPDATE delivery_tasks SET customer_proof_image_path=? WHERE id=?")->execute([$customerProofFile, (int)$task['id']]);
            $task['customer_proof_image_path'] = $customerProofFile;
        }
        $db->prepare("UPDATE delivery_tasks SET status='delivered' WHERE id=? AND status IN ('in_transit','proof_submitted')")->execute([(int)$task['id']]);
        $db->prepare("UPDATE orders SET status='delivered' WHERE id=? AND status NOT IN ('cancelled','rejected','delivered')")->execute([$orderId, $orderId]);
        $db->prepare('INSERT OR IGNORE INTO wallets (user_id, currency, available_balance) VALUES (?, ?, 0)')->execute([(int)$task['courier_id'], $task['currency']]);
        $wallet = $db->prepare('SELECT id, available_balance FROM wallets WHERE user_id=? AND currency=? FOR UPDATE'); $wallet->execute([(int)$task['courier_id'], $task['currency']]); $wallet = $wallet->fetch();
        $existingCredit = $db->prepare("SELECT id FROM wallet_transactions WHERE wallet_id=? AND transaction_type='courier_commission' AND reference_type='delivery_task' AND reference_id=? LIMIT 1"); $existingCredit->execute([(int)$wallet['id'], (int)$task['id']]);
        if (!$existingCredit->fetch()) {
            $balance = (float)$wallet['available_balance'] + (float)$task['delivery_fee'];
            $db->prepare('UPDATE wallets SET available_balance=? WHERE id=?')->execute([$balance, $wallet['id']]);
            $db->prepare("INSERT INTO wallet_transactions (wallet_id, transaction_type, direction, amount, balance_after, reference_type, reference_id, description, created_by) VALUES (?, 'courier_commission', 'credit', ?, ?, 'delivery_task', ?, 'أجرة التوصيل بعد تأكيد الزبون للاستلام', ?)")->execute([$wallet['id'], $task['delivery_fee'], $balance, (int)$task['id'], $actor['id']]);
        }
        $links = ['delivery_task_id' => (int)$task['id'], 'order_id' => $orderId]; $payload = ['source' => 'store', 'order_id' => $orderId, 'store_order_id' => $orderId, 'order_number' => $task['order_number'], 'proof_image_path' => $task['proof_image_path'], 'courier_delivery_proof_image_path' => $task['courier_delivery_proof_image_path'] ?? $task['delivery_proof_image_path'] ?? null, 'customer_proof_image_path' => $task['customer_proof_image_path'] ?? $customerProofFile, 'status' => 'delivered'];
        // اعتماد حالة الطلب والرصيد أولاً؛ الإشعارات والتدقيق آثار ثانوية لا يجوز أن تلغي النجاح.
        $db->commit();
        try {
            marketplaceNotify($db, (int)$task['merchant_id'], 'delivery_completed', 'اكتمل تسليم الطلب', 'أكد الزبون استلام المنتج بنجاح.', 'delivery_completed:' . (int)$task['id'] . ':merchant', $links, $payload);
            $deliveryFeeLabel = number_format((float)$task['delivery_fee'], 2, '.', '') . ' ' . (string)$task['currency'];
            marketplaceNotify($db, (int)$task['courier_id'], 'delivery_completed', 'اكتملت مهمة التوصيل', 'أكد الزبون استلام المنتج وتم إيداع مبلغ ' . $deliveryFeeLabel . ' في محفظتك.', 'delivery_completed:' . (int)$task['id'] . ':courier', $links, $payload);
            $admins = $db->query("SELECT id FROM users WHERE role='admin'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($admins as $adminId) marketplaceNotify($db, (int)$adminId, 'delivery_completed', 'أكد الزبون استلام الطلب', 'أغلق الطلب بعد تأكيد الزبون وحُفظت صورة الاستلام.', 'delivery_completed:' . (int)$task['id'] . ':admin:' . (int)$adminId, $links, $payload);
        } catch (Throwable $notificationError) {
            // لا نعيد العملية إلى الخلف إذا تعذر تسجيل إشعار غير أساسي.
        }
        try {
            Audit::log($db, (int)$actor['id'], 'delivery.confirmed_by_customer', 'delivery_task', (int)$task['id'], ['status' => $task['status']], ['status' => 'delivered', 'order_status' => 'delivered']);
        } catch (Throwable $auditError) {
            // يبقى اعتماد التسليم ناجحاً حتى عند تعذر السجل الثانوي.
        }
    } catch (Throwable $exception) { if ($db->inTransaction()) $db->rollBack(); throw $exception; }
    Http::json(['data' => ['order_id' => $orderId, 'delivery_task_id' => (int)$task['id'], 'status' => 'delivered', 'order_status' => 'delivered', 'wallet_credited' => true, 'customer_proof_image_path' => $customerProofFile]]);
}
if ($method === 'GET' && $path === '/notifications/preferences') {
    $actor = user($db); ensureNotificationPreferencesTable($db); $statement = $db->prepare('SELECT orders_enabled, payments_enabled, delivery_enabled, verification_enabled, wallet_enabled, marketplace_enabled, support_enabled FROM notification_preferences WHERE user_id=? LIMIT 1'); $statement->execute([$actor['id']]); $row = $statement->fetch(); if (!$row) { $db->prepare('INSERT OR IGNORE INTO notification_preferences (user_id) VALUES (?)')->execute([$actor['id']]); $row = ['orders_enabled'=>1,'payments_enabled'=>1,'delivery_enabled'=>1,'verification_enabled'=>1,'wallet_enabled'=>1,'marketplace_enabled'=>1,'support_enabled'=>1]; } Http::json(['data' => array_map('intval', $row)]);
}
if ($method === 'PUT' && $path === '/notifications/preferences') {
    $actor = user($db); $input = Http::input(); ensureNotificationPreferencesTable($db); $keys = ['orders_enabled','payments_enabled','delivery_enabled','verification_enabled','wallet_enabled','marketplace_enabled','support_enabled']; $values = []; foreach ($keys as $key) $values[$key] = array_key_exists($key, $input) ? (!empty($input[$key]) ? 1 : 0) : 1; $db->prepare('INSERT INTO notification_preferences (user_id, orders_enabled, payments_enabled, delivery_enabled, verification_enabled, wallet_enabled, marketplace_enabled, support_enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT(user_id) DO UPDATE SET orders_enabled=excluded.orders_enabled, payments_enabled=excluded.payments_enabled, delivery_enabled=excluded.delivery_enabled, verification_enabled=excluded.verification_enabled, wallet_enabled=excluded.wallet_enabled, marketplace_enabled=excluded.marketplace_enabled, support_enabled=excluded.support_enabled')->execute([$actor['id'], ...array_values($values)]); Audit::log($db, (int)$actor['id'], 'notification.preferences.updated', 'user', (int)$actor['id'], null, $values); Http::json(['data' => $values]);
}
Http::error('not_found', 'المسار المطلوب غير موجود.', 404);
