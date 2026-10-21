<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

try {
    $path = trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $base = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
    if ($base && str_starts_with($path, $base))
        $path = ltrim(substr($path, strlen($base)), '/');
    $route = preg_replace('#^api/?#', '', $path);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($route === 'cars' && $method === 'GET') {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int) ($_GET['limit'] ?? 100)));
        $offset = ($page - 1) * $limit;
        $total = (int) db()->query("SELECT COUNT(*) FROM cars WHERE status = 'published'")->fetchColumn();
        $q = db()->prepare("SELECT id,name,brand,model,price,currency,image,description,featured FROM cars WHERE status='published' ORDER BY sort_order, id LIMIT :limit OFFSET :offset");
        $q->bindValue(':limit', $limit, PDO::PARAM_INT);
        $q->bindValue(':offset', $offset, PDO::PARAM_INT);
        $q->execute();
        json_response(['ok' => true, 'data' => $q->fetchAll(), 'meta' => ['page' => $page, 'limit' => $limit, 'total' => $total]]);
    }
    if (preg_match('#^cars/(\d+)$#', $route, $m) && $method === 'GET') {
        $q = db()->prepare("SELECT id,name,brand,model,price,currency,image,description,featured FROM cars WHERE id=? AND status='published'");
        $q->execute([(int) $m[1]]);
        $car = $q->fetch();
        if (!$car)
            fail('Car not found.', 404);
        json_response(['ok' => true, 'data' => $car]);
    }
    if ($route === 'auth/login' && $method === 'POST') {
        start_session();
        $now = time();
        $attempts = $_SESSION['login_attempts'] ?? [];
        $attempts = array_values(array_filter($attempts, fn($t) => $t > $now - 900));
        if (count($attempts) >= 5)
            fail('Too many attempts. Please try again later.', 429);
        $data = input();
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $q = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username=? LIMIT 1');
        $q->execute([$username]);
        $admin = $q->fetch();
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            $attempts[] = $now;
            $_SESSION['login_attempts'] = $attempts;
            fail('Invalid username or password.', 401);
        }
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_name'] = $admin['username'];
        $_SESSION['login_attempts'] = [];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        json_response(['ok' => true, 'data' => ['username' => $admin['username'], 'csrf_token' => $_SESSION['csrf']]]);
    }
    if ($route === 'auth/logout' && $method === 'POST') {
        require_admin(true);
        $_SESSION = [];
        if (ini_get('session.use_cookies'))
            setcookie(session_name(), '', time() - 42000, '/');
        session_destroy();
        json_response(['ok' => true]);
    }
    if ($route === 'admin/session' && $method === 'GET') {
        require_admin();
        json_response(['ok' => true, 'data' => ['username' => $_SESSION['admin_name'], 'csrf_token' => csrf_token()]]);
    }
    if (str_starts_with($route, 'admin/')) {
        require_admin(in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true));
        if ($route === 'admin/stats' && $method === 'GET') {
            $r = db()->query("SELECT COUNT(*) total, SUM(status='published') published, SUM(status='draft') draft FROM cars")->fetch();
            json_response(['ok' => true, 'data' => array_map('intval', $r)]);
        }
        if ($route === 'admin/cars' && $method === 'GET') {
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int) ($_GET['limit'] ?? 15)));
            $search = trim((string) ($_GET['search'] ?? ''));
            $where = '';
            $params = [];
            if ($search !== '') {
                $where = ' WHERE name LIKE :q OR brand LIKE :q OR model LIKE :q';
                $params[':q'] = '%' . $search . '%';
            }
            $count = db()->prepare('SELECT COUNT(*) FROM cars' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();
            $q = db()->prepare('SELECT * FROM cars' . $where . ' ORDER BY sort_order,id LIMIT :l OFFSET :o');
            foreach ($params as $k => $v)
                $q->bindValue($k, $v);
            $q->bindValue(':l', $limit, PDO::PARAM_INT);
            $q->bindValue(':o', ($page - 1) * $limit, PDO::PARAM_INT);
            $q->execute();
            json_response(['ok' => true, 'data' => $q->fetchAll(), 'meta' => compact('page', 'limit', 'total')]);
        }
        if ($route === 'admin/cars' && $method === 'POST') {
            $data = car_payload($_POST);
            $image = upload_image($_FILES['image'] ?? null);
            if ($image)
                $data['image'] = $image;
            elseif (empty($_POST['image']))
                fail('An image is required.', 422);
            else
                $data['image'] = (string) $_POST['image'];
            $q = db()->prepare('INSERT INTO cars (name,brand,model,price,currency,image,description,featured,status,sort_order) VALUES (:name,:brand,:model,:price,:currency,:image,:description,:featured,:status,(SELECT COALESCE(MAX(sort_order),0)+1 FROM (SELECT sort_order FROM cars) AS x))');
            $q->execute($data);
            json_response(['ok' => true, 'data' => ['id' => (int) db()->lastInsertId()]], 201);
        }
        if (preg_match('#^admin/cars/(\d+)$#', $route, $m)) {
            $id = (int) $m[1];
            $q = db()->prepare('SELECT * FROM cars WHERE id=?');
            $q->execute([$id]);
            $old = $q->fetch();
            if (!$old)
                fail('Car not found.', 404);
            if ($method === 'GET')
                json_response(['ok' => true, 'data' => $old]);
            if ($method === 'PUT' || $method === 'POST') {
                $data = car_payload($_POST);
                $new = upload_image($_FILES['image'] ?? null);
                $data['image'] = $new ?: $old['image'];
                $data['id'] = $id;
                db()->beginTransaction();
                try {
                    $u = db()->prepare('UPDATE cars SET name=:name,brand=:brand,model=:model,price=:price,currency=:currency,image=:image,description=:description,featured=:featured,status=:status WHERE id=:id');
                    $u->execute($data);
                    db()->commit();
                    if ($new)
                        remove_managed_image($old['image'], $id);
                } catch (Throwable $e) {
                    db()->rollBack();
                    if ($new)
                        remove_managed_image($new);
                    throw $e;
                }
                json_response(['ok' => true]);
            }
            if ($method === 'DELETE') {
                db()->prepare('DELETE FROM cars WHERE id=?')->execute([$id]);
                remove_managed_image($old['image']);
                json_response(['ok' => true]);
            }
        }
        if (preg_match('#^admin/cars/(\d+)/status$#', $route, $m) && $method === 'PATCH') {
            $data = input();
            $status = $data['status'] ?? '';
            if (!in_array($status, ['draft', 'published'], true))
                fail('Invalid status.', 422);
            $q = db()->prepare('UPDATE cars SET status=? WHERE id=?');
            $q->execute([$status, (int) $m[1]]);
            if (!$q->rowCount())
                fail('Car not found.', 404);
            json_response(['ok' => true]);
        }
    }
    fail('Endpoint not found.', 404);
} catch (PDOException $e) {
    error_log($e->getMessage());
    fail('A database error occurred. Please try again later.', 500);
} catch (Throwable $e) {
    error_log($e->getMessage());
    fail('A server error occurred. Please try again later.', 500);
}
