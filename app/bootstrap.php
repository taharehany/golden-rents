<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';

$localConfig = __DIR__ . '/config.local.php';
$config = is_file($localConfig) ? require $localConfig : [];
function setting(string $key, ?string $default = null): ?string
{
    global $config;
    $env = getenv('GOLDEN_RENTS_' . strtoupper($key));
    return $env !== false ? $env : ($config[$key] ?? $default);
}
function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO)
        return $pdo;
    $name = setting('db_name');
    $user = setting('db_user');
    if (!$name || !$user)
        throw new RuntimeException('Database is not configured.');
    $pdo = new PDO('mysql:host=' . setting('db_host', 'localhost') . ';dbname=' . $name . ';charset=utf8mb4', $user, setting('db_pass', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function fail(string $message, int $status = 400, array $errors = []): never
{
    json_response(['ok' => false, 'message' => $message, 'errors' => $errors], $status);
}
function input(): array
{
    $type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($type, 'application/json') !== false) {
        $data = json_decode((string) file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE)
        return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('golden_rents_admin');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
    ini_set('session.use_strict_mode', '1');
    session_start();
}
function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf']))
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function require_admin(bool $csrf = false): void
{
    start_session();
    if (empty($_SESSION['admin_id']))
        fail('Authentication required.', 401);
    if ($csrf && !hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))
        fail('Invalid CSRF token.', 403);
}
function car_payload(array $data): array
{
    $name = trim((string) ($data['name'] ?? ''));
    $brand = trim((string) ($data['brand'] ?? ''));
    $model = trim((string) ($data['model'] ?? ''));
    $price = trim((string) ($data['price'] ?? ''));
    $currency = strtoupper(trim((string) ($data['currency'] ?? 'AED')));
    $status = (string) ($data['status'] ?? 'draft');
    $errors = [];
    if ($name === '' || mb_strlen($name) > 150)
        $errors['name'] = 'Name is required (maximum 150 characters).';
    if ($brand === '' || mb_strlen($brand) > 80)
        $errors['brand'] = 'Brand is required (maximum 80 characters).';
    if ($model === '' || mb_strlen($model) > 80)
        $errors['model'] = 'Model is required (maximum 80 characters).';
    if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $price))
        $errors['price'] = 'Enter a valid non-negative price.';
    if (!preg_match('/^[A-Z]{3}$/', $currency))
        $errors['currency'] = 'Use a three-letter currency code.';
    if (!in_array($status, ['published', 'draft'], true))
        $errors['status'] = 'Invalid status.';
    if ($errors)
        fail('Please correct the highlighted fields.', 422, $errors);
    return [
        'name' => $name,
        'brand' => $brand,
        'model' => $model,
        'price' => $price,
        'currency' => $currency,
        'description' => trim((string) ($data['description'] ?? '')) ?: null,
        'featured' => !empty($data['featured']) ? 1 : 0,
        'status' => $status
    ];
}
function upload_image(?array $file): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)
        return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024 || !is_uploaded_file($file['tmp_name']))
        fail('Image upload failed or exceeds 5 MB.', 422);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false)
        fail('Use a valid JPEG, PNG, or WebP image.', 422);
    $directory = APP_ROOT . '/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0755, true))
        fail('Upload directory is unavailable.', 500);
    $relative = 'uploads/' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], APP_ROOT . '/' . $relative))
        fail('Could not save image.', 500);
    return $relative;
}
function remove_managed_image(?string $image, ?int $exceptId = null): void
{
    if (!$image || !str_starts_with($image, 'uploads/'))
        return;
    $sql = 'SELECT COUNT(*) FROM cars WHERE image = :image' . ($exceptId ? ' AND id != :id' : '');
    $s = db()->prepare($sql);
    $s->bindValue(':image', $image);
    if ($exceptId)
        $s->bindValue(':id', $exceptId, PDO::PARAM_INT);
    $s->execute();
    if ((int) $s->fetchColumn() === 0) {
        $path = APP_ROOT . '/' . $image;
        if (is_file($path))
            unlink($path);
    }
}
