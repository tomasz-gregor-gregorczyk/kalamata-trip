<?php
// Endpoint pozycji — trzy tryby, wszystkie na jednym pliku location.json (bez bazy):
//
//   GET                          -> zwraca aktualną pozycję (JSON) dla mapy w index.html
//   POST application/x-www-form  -> zapis ręczny z panel.php (token w polu formularza)
//   POST application/json        -> zapis automatyczny z OwnTracks (token w query: ?token=...)
//
// OwnTracks (iOS/Android, tryb HTTP) ustawiamy na adres:
//   https://TWOJA-DOMENA/where.php?token=TAJNY_TOKEN_SLEDZENIA
// Ten adres wpisujemy W APLIKACJI, nie w przeglądarce — otwarcie go w przeglądarce
// to zwykły GET, który tylko odczytuje pozycję i niczego nie zapisuje.
// Aplikacja wysyła JSON {"_type":"location","lat":..,"lon":..,"tst":..,"batt":..}
// i oczekuje w odpowiedzi tablicy JSON — dlatego zwracamy [].

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$FILE = __DIR__ . '/location.json';
$cfg  = file_exists(__DIR__ . '/config.php')
        ? require __DIR__ . '/config.php'
        : require __DIR__ . '/config.example.php';

$TOKEN = (string)($cfg['token'] ?? '');
// Osobny sekret dla aplikacji śledzącej. Siedzi na stałe w telefonie i leci w adresie URL,
// więc trzymamy go osobno od hasła do panelu — da się unieważnić jeden bez drugiego.
$TRACK_TOKEN = (string)($cfg['track_token'] ?? $TOKEN);
// Strefa domowa: automat NIE zapisuje pozycji w jej promieniu, żeby publiczna strona
// nie ogłaszała światu, kiedy dom stoi pusty. Panel działa normalnie (zapis świadomy).
$HOME    = isset($cfg['home']) && is_array($cfg['home']) ? $cfg['home'] : null;
// Minimalny odstęp między automatycznymi zapisami (sekundy) — OwnTracks w trybie "move"
// potrafi nadawać co kilkanaście sekund, a mapa i tak odpytuje raz na minutę.
$MIN_GAP = (int)($cfg['min_interval_s'] ?? 60);

function jexit($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
function tokenOk($given, $expected) {
    return is_string($given) && $expected !== '' && hash_equals($expected, $given);
}
function distKm($lat1, $lng1, $lat2, $lng2) {
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $h = sin($dLat / 2) * sin($dLat / 2)
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
    return 2 * 6371 * asin(min(1, sqrt($h)));
}
function readCurrent($file) {
    if (!is_file($file)) return null;
    $d = json_decode((string)file_get_contents($file), true);
    return is_array($d) ? $d : null;
}
function saveLocation($file, $data) {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (file_put_contents($file, $json, LOCK_EX) === false) {
        jexit(['error' => 'Nie mogę zapisać location.json (uprawnienia zapisu w folderze?)'], 500);
    }
}
function validCoords($lat, $lng) {
    return $lat !== null && $lng !== null
        && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // GET — aktualna pozycja albo null, jeśli jeszcze nic nie zapisano
    if (is_file($FILE)) { readfile($FILE); } else { echo 'null'; }
    exit;
}

$ctype  = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
$isJson = strpos($ctype, 'application/json') !== false;

// ---------------------------------------------------------------- OwnTracks
if ($isJson) {
    // Parametr to ?token=..., ale przyjmujemy też ?track_token=... — nazwa klucza
    // w config.php myli się z nazwą parametru, więc niech działa jedno i drugie.
    $given = $_GET['token'] ?? ($_GET['track_token'] ?? '');
    if (!tokenOk($given, $TRACK_TOKEN)) {
        jexit(['error' => 'Zły token śledzenia'], 403);
    }
    $in = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($in)) jexit([], 200);

    // OwnTracks wysyła też _type: transition / waypoint / lwt / card — ignorujemy,
    // ale odpowiadamy 200, żeby aplikacja nie próbowała w kółko.
    if (($in['_type'] ?? '') !== 'location') jexit([], 200);

    $lat = isset($in['lat']) ? (float)$in['lat'] : null;
    $lng = isset($in['lon']) ? (float)$in['lon'] : null;   // OwnTracks używa "lon"
    if (!validCoords($lat, $lng)) jexit([], 200);

    $cur = readCurrent($FILE);

    // Strefa domowa — nie publikujemy
    if ($HOME && isset($HOME['lat'], $HOME['lng'])) {
        $r = (float)($HOME['radius_km'] ?? 5);
        if (distKm($lat, $lng, (float)$HOME['lat'], (float)$HOME['lng']) <= $r) {
            jexit([], 200);
        }
    }

    // Za wcześnie po poprzednim zapisie i bez istotnego ruchu — pomijamy
    if ($cur && !empty($cur['updated'])) {
        $age = time() - strtotime($cur['updated']);
        $moved = isset($cur['lat'], $cur['lng'])
               ? distKm($lat, $lng, (float)$cur['lat'], (float)$cur['lng']) : 999;
        if ($age >= 0 && $age < $MIN_GAP && $moved < 0.3) jexit([], 200);
    }

    // Etykieta: jeśli OwnTracks raportuje wejście w zdefiniowany region (inregions),
    // bierzemy jego nazwę. Inaczej czyścimy — nieaktualna nazwa miejsca myli bardziej
    // niż jej brak, a mapa pokaże wtedy po prostu "w drodze".
    $label = '';
    if (!empty($in['inregions']) && is_array($in['inregions'])) {
        $label = trim(strip_tags((string)$in['inregions'][0]));
    }
    // Notatka to wiadomość od człowieka — zostawiamy ją, dopóki sam jej nie zmienisz w panelu.
    $note = $cur['note'] ?? '';

    $data = [
        'lat'     => round($lat, 5),
        'lng'     => round($lng, 5),
        'label'   => $label,
        'note'    => $note,
        'updated' => date('c'),
        'src'     => 'auto',
    ];
    if (isset($in['batt'])) $data['batt'] = (int)$in['batt'];
    if (isset($in['acc']))  $data['acc']  = (int)$in['acc'];
    if (isset($in['vel']))  $data['vel']  = (int)$in['vel'];

    saveLocation($FILE, $data);
    jexit([], 200);   // OwnTracks oczekuje tablicy
}

// ------------------------------------------------------------ panel ręczny
if (!tokenOk($_POST['token'] ?? '', $TOKEN)) {
    jexit(['error' => 'Zły token'], 403);
}
$lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
$lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
if (!validCoords($lat, $lng)) {
    jexit(['error' => 'Brak/niepoprawne współrzędne'], 400);
}
$data = [
    'lat'     => round($lat, 5),
    'lng'     => round($lng, 5),
    'label'   => trim(strip_tags((string)($_POST['label'] ?? ''))),
    'note'    => trim(strip_tags((string)($_POST['note'] ?? ''))),
    'updated' => date('c'),
    'src'     => 'manual',
];
saveLocation($FILE, $data);
jexit(['ok' => true, 'data' => $data]);
