<?php
// Endpoint pozycji — trzy tryby, wszystkie na jednym pliku location.json (bez bazy):
//
//   GET                          -> zwraca aktualną pozycję (JSON) dla mapy w index.html
//
// Przy każdym zapisie doklejamy nazwę okolicy ('place') z Nominatim — mapa pokazuje
// wtedy "w okolicy: Nikiti, Grecja" zamiast samych współrzędnych.
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

$FILE  = __DIR__ . '/location.json';
// Log zameldowań: dopisywany przy każdym zapisie pozycji. Gdyby coś się stało,
// z tego da się odtworzyć przebytą trasę. Format JSONL (jedna linia = jeden wpis),
// bo dopisanie linii jest atomowe i nie wymaga wczytywania całego pliku.
$TRACK = __DIR__ . '/data/track.jsonl';
$TRACK_MAX = 20000;
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
function appendTrack($file, $data, $max) {
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return;   // cicho — log nie może blokować zapisu pozycji
    $line = json_encode([
        't'     => $data['updated'],
        'lat'   => $data['lat'],
        'lng'   => $data['lng'],
        'place' => isset($data['place']) ? $data['place'] : '',
        'label' => isset($data['label']) ? $data['label'] : '',
        'src'   => isset($data['src']) ? $data['src'] : '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @file_put_contents($file, $line . "\n", FILE_APPEND | LOCK_EX);

    // Przycinamy dopiero po sporym zapasie — przy ręcznych zameldowaniach
    // 20 000 wpisów to lata jeżdżenia, ale plik nie ma rosnąć bez końca.
    if (@filesize($file) > 6 * 1024 * 1024) {
        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines && count($lines) > $max) {
            @file_put_contents($file, implode("\n", array_slice($lines, -$max)) . "\n", LOCK_EX);
        }
    }
}
function saveLocation($file, $data) {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (file_put_contents($file, $json, LOCK_EX) === false) {
        jexit(['error' => 'Nie mogę zapisać location.json (uprawnienia zapisu w folderze?)'], 500);
    }
}
// Odwrotne geokodowanie przez Nominatim (OpenStreetMap). Robimy to TU, przy zapisie,
// a nie w przeglądarce: zapytanie leci raz na zmianę pozycji zamiast raz na minutę
// razy liczba odwiedzających — Nominatim dopuszcza 1 zapytanie na sekundę.
// Każdy błąd jest cichy: brak nazwy to nie powód, żeby nie zapisać pozycji.

// Nazwa kraju z kodu ISO — po polsku i bez dodatkowego zapytania do geokodera
// (odpowiedź w języku lokalnym zwracałaby "Ελλάδα" albo "Србија").
function countryPl($code, $fallback) {
    $map = [
        'pl' => 'Polska', 'sk' => 'Słowacja', 'cz' => 'Czechy', 'hu' => 'Węgry',
        'at' => 'Austria', 'de' => 'Niemcy', 'si' => 'Słowenia', 'hr' => 'Chorwacja',
        'ba' => 'Bośnia i Hercegowina', 'rs' => 'Serbia', 'me' => 'Czarnogóra',
        'xk' => 'Kosowo', 'mk' => 'Macedonia Północna', 'al' => 'Albania',
        'gr' => 'Grecja', 'bg' => 'Bułgaria', 'ro' => 'Rumunia', 'it' => 'Włochy',
        'ua' => 'Ukraina', 'tr' => 'Turcja',
    ];
    $code = strtolower((string)$code);
    return isset($map[$code]) ? $map[$code] : (string)$fallback;
}

function nominatim($lat, $lng, $lang) {
    $url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2'
         . '&lat=' . rawurlencode((string)$lat) . '&lon=' . rawurlencode((string)$lng)
         . '&zoom=14';
    if ($lang !== '') $url .= '&accept-language=' . rawurlencode($lang);
    $ua  = 'kalamata-trip/1.0 (https://grecja.gofamily.pl)';
    $raw = false;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 4,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_USERAGENT      => $ua,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => [
            'timeout' => 4, 'header' => "User-Agent: $ua\r\n",
        ]]);
        $raw = @file_get_contents($url, false, $ctx);
    }
    if (!$raw) return null;
    $d = json_decode($raw, true);
    return (is_array($d) && !empty($d['address'])) ? $d['address'] : null;
}

// Z odpowiedzi wybieramy najbardziej szczegółową nazwę miejsca.
function placeName($addr) {
    foreach (['village', 'town', 'city', 'hamlet', 'suburb', 'municipality', 'county', 'state'] as $k) {
        if (!empty($addr[$k])) return (string)$addr[$k];
    }
    return '';
}

function reverseGeocode($lat, $lng) {
    // Bez accept-language dostajemy nazwę oryginalną, tak jak zapisana lokalnie.
    $local = nominatim($lat, $lng, '');
    if (!$local) return '';
    $name = placeName($local);
    if ($name === '') return '';

    // Angielski dokładamy tylko wtedy, gdy oryginał nie jest po łacinie — czyli
    // dla Grecji i cyrylicznej Serbii. Dla Polski czy Węgier drugie zapytanie
    // byłoby bezcelowe, a Nominatim ma limit jednego na sekundę.
    if (preg_match('/[^\x{0000}-\x{024F}]/u', $name)) {
        $en = nominatim($lat, $lng, 'en');
        if ($en) {
            $nameEn = placeName($en);
            if ($nameEn !== '' && $nameEn !== $name) $name .= ' (' . $nameEn . ')';
        }
    }

    $country = countryPl(
        isset($local['country_code']) ? $local['country_code'] : '',
        isset($local['country']) ? $local['country'] : ''
    );
    return $country !== '' ? $name . ', ' . $country : $name;
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

    // Nazwa okolicy. Jeśli ledwo się ruszyliśmy, a nazwę już mamy — nie pytamy ponownie.
    $place = '';
    $reuse = isset($cur['lat'], $cur['lng'], $cur['place']) && $cur['place'] !== ''
           && distKm($lat, $lng, (float)$cur['lat'], (float)$cur['lng']) < 0.5;
    $place = $reuse ? (string)$cur['place'] : reverseGeocode($lat, $lng);

    $data = [
        'lat'     => round($lat, 5),
        'lng'     => round($lng, 5),
        'label'   => $label,
        'place'   => $place,
        'note'    => $note,
        'updated' => date('c'),
        'src'     => 'auto',
    ];
    // OwnTracks przysyła też batt / acc / vel (bateria, dokładność, prędkość).
    // Nie zapisujemy ich: location.json jest publicznie czytelny, a te dane
    // nie są nikomu potrzebne do zobaczenia, gdzie jesteśmy.

    saveLocation($FILE, $data);
    appendTrack($TRACK, $data, $TRACK_MAX);
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
    'place'   => reverseGeocode($lat, $lng),
    'note'    => trim(strip_tags((string)($_POST['note'] ?? ''))),
    'updated' => date('c'),
    'src'     => 'manual',
];
saveLocation($FILE, $data);
appendTrack($TRACK, $data, $TRACK_MAX);
jexit(['ok' => true, 'data' => $data]);
