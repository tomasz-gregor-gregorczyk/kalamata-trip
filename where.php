<?php
// Prosty endpoint: GET zwraca aktualną pozycję (JSON), POST ją zapisuje (wymaga tokenu).
// Dane trzymane w pliku location.json (bez bazy danych).

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$FILE = __DIR__ . '/location.json';
$cfg  = file_exists(__DIR__ . '/config.php')
        ? require __DIR__ . '/config.php'
        : require __DIR__ . '/config.example.php';
$TOKEN = $cfg['token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    if (!is_string($token) || !hash_equals((string)$TOKEN, $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Zły token']);
        exit;
    }
    $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
    if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak/niepoprawne współrzędne']);
        exit;
    }
    $data = [
        'lat'     => round($lat, 5),
        'lng'     => round($lng, 5),
        'label'   => trim(strip_tags((string)($_POST['label'] ?? ''))),
        'note'    => trim(strip_tags((string)($_POST['note'] ?? ''))),
        'updated' => date('c'),
    ];
    if (file_put_contents($FILE, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Nie mogę zapisać location.json (uprawnienia zapisu w folderze?)']);
        exit;
    }
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

// GET — zwróć aktualną pozycję (albo null, jeśli jeszcze nie ustawiono)
if (is_file($FILE)) {
    readfile($FILE);
} else {
    echo 'null';
}
