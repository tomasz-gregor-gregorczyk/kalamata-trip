<?php
// API treści: notatki przy dniach rozpiski + dziennik z trasy (wpisy i zdjęcia).
//
//   GET  ?action=notes                 -> { notes:{id:tekst}, done:{id:true} }  (publiczne)
//   GET  ?action=posts                 -> [ {id,lat,lng,text,photo,...} ]  (publiczne)
//   GET  ?action=track[&limit=N]       -> historia zameldowań (publiczne)
//   GET  ?action=track&format=gpx      -> ta sama historia jako plik GPX
//   POST action=note        + token    -> zapis notatki (pusty tekst = kasowanie)
//   POST action=done        + token    -> zaznaczenie "zaplanowane / zarezerwowane"
//   POST action=post        + token    -> nowy wpis dziennika (opcjonalnie ze zdjęciem)
//   POST action=post_delete + token    -> kasowanie wpisu razem ze zdjęciem
//   POST action=track_clear  + token   -> wyczyszczenie historii zameldowań
//   POST action=track_delete + token   -> usunięcie jednego zameldowania (po czasie 't')
//   POST action=check       + token    -> sprawdzenie tokenu (tryb edycji w index.html)
//   POST action=costs       + token    -> wydatki i kursy walut (sheet.php)
//   POST action=cost        + token    -> dodanie / zmiana wydatku (z 'id' = zmiana)
//   POST action=cost_delete + token    -> kasowanie wydatku
//   POST action=cost_rates  + token    -> zapis kursów walut (JSON w 'rates')
//   POST action=cost_import + token    -> wiele wydatków naraz (JSON w 'items'), np. z CSV Revoluta
//
// Zapis wymaga tokenu z 'token' w config.php — tego samego co panel.
// Odczyt jest publiczny: znajomi mają widzieć notatki i zdjęcia, ale nie ruszać.
// Wyjątek: wydatki. To nie jest treść dla znajomych, więc nawet odczyt idzie
// POST-em z tokenem.

// Bez tego hosting z serialize_precision=17 zapisuje 40.085 jako
// 40.08500000000000085265128291212022304534912109375.
@ini_set('serialize_precision', '-1');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$cfg = file_exists(__DIR__ . '/config.php')
     ? require __DIR__ . '/config.php'
     : require __DIR__ . '/config.example.php';
$TOKEN = (string)($cfg['token'] ?? '');

$DIR       = __DIR__ . '/data';
$UPLOADS   = $DIR . '/uploads';
$NOTES     = $DIR . '/notes.json';
$TRACK     = $DIR . '/track.jsonl';
$POSTS     = $DIR . '/posts.json';
$COSTS     = $DIR . '/costs.json';
$MAX_BYTES = 4 * 1024 * 1024;   // zdjęcia i tak są zmniejszane w przeglądarce przed wysyłką

function jexit($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function ensureDirs($dir, $uploads) {
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        jexit(['error' => 'Nie mogę utworzyć katalogu data/ — sprawdź prawa zapisu.'], 500);
    }
    if (!is_dir($uploads) && !@mkdir($uploads, 0755, true)) {
        jexit(['error' => 'Nie mogę utworzyć katalogu data/uploads/.'], 500);
    }
    // Katalog ze zdjęciami od użytkownika nie ma prawa wykonywać PHP.
    $ht = $uploads . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "php_flag engine off\nRemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phps\nAddType text/plain .php\nOptions -ExecCGI\n");
    }
}
function readJson($file, $fallback) {
    if (!is_file($file)) return $fallback;
    $d = json_decode((string)file_get_contents($file), true);
    return $d === null ? $fallback : $d;
}
function writeJson($file, $data) {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (file_put_contents($file, $json, LOCK_EX) === false) {
        jexit(['error' => 'Nie mogę zapisać ' . basename($file) . ' (prawa zapisu?).'], 500);
    }
}
function requireToken($expected) {
    $given = $_POST['token'] ?? '';
    if (!is_string($given) || $expected === '' || !hash_equals($expected, $given)) {
        jexit(['error' => 'Zły token'], 403);
    }
}
function clean($s, $max) {
    $s = trim(strip_tags((string)$s));
    return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
}

$action = $_SERVER['REQUEST_METHOD'] === 'POST'
        ? (string)($_POST['action'] ?? '')
        : (string)($_GET['action'] ?? '');

// ------------------------------------------------------------------ odczyt
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($action === 'notes') {
        $n = readJson($NOTES, []);
        jexit([
            'notes' => (object)(isset($n['notes']) && is_array($n['notes']) ? $n['notes'] : []),
            'done'  => (object)(isset($n['done'])  && is_array($n['done'])  ? $n['done']  : []),
        ]);
    }
    if ($action === 'posts') jexit(array_values(readJson($POSTS, [])));

    // Historia zameldowań — do odtworzenia trasy, gdyby coś się stało.
    // Celowo bez tokenu: w sytuacji awaryjnej nikt nie powinien go szukać.
    if ($action === 'track') {
        $rows = [];
        if (is_file($TRACK)) {
            $lines = file($TRACK, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines) {
                foreach ($lines as $l) {
                    $r = json_decode($l, true);
                    if (is_array($r) && isset($r['lat'], $r['lng'])) $rows[] = $r;
                }
            }
        }
        $limit = isset($_GET['limit']) ? max(1, min(20000, (int)$_GET['limit'])) : 0;
        if ($limit && count($rows) > $limit) $rows = array_slice($rows, -$limit);

        if (($_GET['format'] ?? '') === 'gpx') {
            header('Content-Type: application/gpx+xml; charset=utf-8');
            header('Content-Disposition: attachment; filename="kalamata-trip.gpx"');
            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            echo '<gpx version="1.1" creator="kalamata-trip" xmlns="http://www.topografix.com/GPX/1/1">' . "\n";
            echo "  <trk><name>Gdynia - Chalkidiki - Kalamata</name><trkseg>\n";
            foreach ($rows as $r) {
                echo '    <trkpt lat="' . htmlspecialchars((string)$r['lat'], ENT_QUOTES) . '"'
                   . ' lon="' . htmlspecialchars((string)$r['lng'], ENT_QUOTES) . '">';
                if (!empty($r['t'])) echo '<time>' . htmlspecialchars((string)$r['t'], ENT_QUOTES) . '</time>';
                $nm = !empty($r['label']) ? $r['label'] : (!empty($r['place']) ? $r['place'] : '');
                if ($nm !== '') echo '<name>' . htmlspecialchars((string)$nm, ENT_QUOTES) . '</name>';
                echo "</trkpt>\n";
            }
            echo "  </trkseg></trk>\n</gpx>\n";
            exit;
        }
        jexit($rows);
    }
jexit(['error' => 'Nieznana akcja'], 400);
}

// ------------------------------------------------------------------- zapis
requireToken($TOKEN);
ensureDirs($DIR, $UPLOADS);

if ($action === 'check') {
    jexit(['ok' => true]);
}

// --- stan rozpiski: notatki i zaznaczenia ----------------------------------
if ($action === 'note' || $action === 'done') {
    $id = (string)($_POST['id'] ?? '');
    if (!preg_match('/^[a-z]+[0-9]{1,3}$/', $id)) {
        jexit(['error' => 'Niepoprawny identyfikator dnia'], 400);
    }
    $all = (array)readJson($NOTES, []);
    if (!isset($all['notes']) || !is_array($all['notes'])) $all['notes'] = [];
    if (!isset($all['done'])  || !is_array($all['done']))  $all['done']  = [];

    if ($action === 'note') {
        $text = clean($_POST['text'] ?? '', 2000);
        if ($text === '') { unset($all['notes'][$id]); } else { $all['notes'][$id] = $text; }
    } else {
        $on = ($_POST['value'] ?? '') === '1';
        if ($on) { $all['done'][$id] = true; } else { unset($all['done'][$id]); }
    }
    writeJson($NOTES, $all);
    jexit(['ok' => true, 'id' => $id]);
}

// --- wpis dziennika (opcjonalnie ze zdjęciem) ------------------------------
if ($action === 'post') {
    $lat = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
    $lng = isset($_POST['lng']) && $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;

    // Brak współrzędnych — bierzemy ostatnią znaną pozycję z location.json.
    if ($lat === null || $lng === null) {
        $loc = readJson(__DIR__ . '/location.json', null);
        if (is_array($loc) && isset($loc['lat'], $loc['lng'])) {
            $lat = (float)$loc['lat'];
            $lng = (float)$loc['lng'];
        }
    }
    if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        jexit(['error' => 'Brak pozycji: zdjęcie nie ma danych GPS, a nie znam bieżącej lokalizacji.'], 400);
    }

    $photo = null;
    if (!empty($_FILES['photo']['tmp_name']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            jexit(['error' => 'Błąd uploadu (kod ' . $_FILES['photo']['error'] . ')'], 400);
        }
        if ($_FILES['photo']['size'] > $MAX_BYTES) {
            jexit(['error' => 'Plik za duży (limit ' . round($MAX_BYTES / 1048576) . ' MB).'], 400);
        }
        // Jedyny wiarygodny test: czy to faktycznie obraz. Rozszerzenie nadajemy sami.
        $info = @getimagesize($_FILES['photo']['tmp_name']);
        $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!$info || !isset($ext[$info['mime']])) {
            jexit(['error' => 'To nie jest obraz JPEG/PNG/WEBP.'], 400);
        }
        $name = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext[$info['mime']];
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $UPLOADS . '/' . $name)) {
            jexit(['error' => 'Nie mogę zapisać pliku w data/uploads/.'], 500);
        }
        @chmod($UPLOADS . '/' . $name, 0644);
        $photo = 'data/uploads/' . $name;
    }

    $text = clean($_POST['text'] ?? '', 600);
    if ($text === '' && $photo === null) {
        jexit(['error' => 'Pusty wpis — dodaj tekst albo zdjęcie.'], 400);
    }

    $posts = (array)readJson($POSTS, []);
    $entry = [
        'id'      => date('Ymd-His') . '-' . bin2hex(random_bytes(3)),
        'lat'     => round($lat, 5),
        'lng'     => round($lng, 5),
        'text'    => $text,
        'photo'   => $photo,
        'taken'   => clean($_POST['taken'] ?? '', 30),
        'created' => date('c'),
    ];
    $posts[] = $entry;
    writeJson($POSTS, $posts);
    jexit(['ok' => true, 'post' => $entry]);
}

// --- kasowanie wpisu -------------------------------------------------------
if ($action === 'post_delete') {
    $id    = (string)($_POST['id'] ?? '');
    $posts = (array)readJson($POSTS, []);
    $kept  = [];
    $found = false;
    foreach ($posts as $p) {
        if (isset($p['id']) && $p['id'] === $id) {
            $found = true;
            // Kasujemy też plik, ale tylko z naszego katalogu — nazwa z JSON-a
            // nie może wyprowadzić poza data/uploads/.
            if (!empty($p['photo'])) {
                $f = $UPLOADS . '/' . basename((string)$p['photo']);
                if (is_file($f)) @unlink($f);
            }
            continue;
        }
        $kept[] = $p;
    }
    if (!$found) jexit(['error' => 'Nie ma takiego wpisu'], 404);
    writeJson($POSTS, $kept);
    jexit(['ok' => true, 'id' => $id]);
}

// --- historia zameldowań: czyszczenie i usuwanie pojedynczych wpisów ---------
// Przydaje się dwa razy: po testach i wtedy, gdy GPS strzeli gdzieś w bok
// i pojedynczy bledny punkt wykrzywi cala linie na mapie.
if ($action === 'track_clear' || $action === 'track_delete') {
    if (!is_file($TRACK)) jexit(['ok' => true, 'removed' => 0]);

    if ($action === 'track_clear') {
        $n = count(file($TRACK, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);
        if (@unlink($TRACK) === false) {
            jexit(['error' => 'Nie mogę usunąć track.jsonl (prawa zapisu?).'], 500);
        }
        jexit(['ok' => true, 'removed' => $n]);
    }

    // W treści formularza '+' oznacza spację, więc "...:44+00:00" dociera jako
    // "...:44 00:00". Cofamy to, zamiast wymagać %2B od każdego klienta.
    $t = str_replace(' ', '+', (string)($_POST['t'] ?? ''));
    $want  = strtotime($t);
    $lines = file($TRACK, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $kept  = [];
    $n     = 0;
    foreach ($lines as $l) {
        $r = json_decode($l, true);
        if (is_array($r) && isset($r['t'])) {
            $rt = (string)$r['t'];
            // Porównujemy też po czasie, żeby nie wywrócić się na innym zapisie strefy.
            if ($rt === $t || ($want !== false && strtotime($rt) === $want)) { $n++; continue; }
        }
        $kept[] = $l;
    }
    if ($n === 0) jexit(['error' => 'Nie ma zameldowania o tym czasie'], 404);
    if (file_put_contents($TRACK, $kept ? implode("\n", $kept) . "\n" : '', LOCK_EX) === false) {
        jexit(['error' => 'Nie mogę zapisać track.jsonl.'], 500);
    }
    jexit(['ok' => true, 'removed' => $n]);
}

// --- wydatki (sheet.php) ---------------------------------------------------
// Kwota zostaje w walucie, w której zapłaciliście; na złotówki przelicza
// arkusz po kursach z 'rates'. Zmiana kursu przelicza więc wszystkie wpisy.
$CURRENCIES = ['PLN', 'EUR', 'CZK', 'HUF', 'RSD', 'MKD'];
$DEF_RATES  = ['PLN' => 1, 'EUR' => 4.40, 'CZK' => 0.176, 'HUF' => 0.0113, 'RSD' => 0.0376, 'MKD' => 0.0715];

function costsData($file, $defRates) {
    $d = (array)readJson($file, []);
    $items = isset($d['items']) && is_array($d['items']) ? array_values($d['items']) : [];
    $rates = isset($d['rates']) && is_array($d['rates']) ? $d['rates'] + $defRates : $defRates;
    return ['items' => $items, 'rates' => $rates];
}

if ($action === 'costs') {
    jexit(costsData($COSTS, $DEF_RATES));
}

if ($action === 'cost') {
    $amount = (float)str_replace([',', ' '], ['.', ''], (string)($_POST['amount'] ?? ''));
    $cur    = strtoupper((string)($_POST['cur'] ?? 'PLN'));
    $date   = (string)($_POST['date'] ?? '');
    $name   = clean($_POST['name'] ?? '', 120);
    if ($name === '')                         jexit(['error' => 'Podaj nazwę wydatku.'], 400);
    if ($amount <= 0 || $amount > 10000000)   jexit(['error' => 'Niepoprawna kwota.'], 400);
    if (!in_array($cur, $CURRENCIES, true))   jexit(['error' => 'Nieznana waluta.'], 400);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

    $data = costsData($COSTS, $DEF_RATES);
    $id   = (string)($_POST['id'] ?? '');
    $row  = [
        'id'     => $id !== '' ? $id : date('Ymd-His') . '-' . bin2hex(random_bytes(3)),
        'date'   => $date,
        'name'   => $name,
        'cat'    => clean($_POST['cat'] ?? '', 30),
        'amount' => round($amount, 2),
        'cur'    => $cur,
    ];
    $found = false;
    foreach ($data['items'] as $i => $it) {
        if (isset($it['id']) && $it['id'] === $id) {
            // 'src' (klucz transakcji z importu) musi przetrwać edycję, inaczej ponowny import zrobi duplikat.
            if (isset($it['src'])) $row['src'] = $it['src'];
            $data['items'][$i] = $row; $found = true; break;
        }
    }
    if ($id !== '' && !$found) jexit(['error' => 'Nie ma takiego wydatku (skasowany?).'], 404);
    if (!$found) $data['items'][] = $row;
    writeJson($COSTS, $data);
    jexit(['ok' => true, 'item' => $row]);
}

if ($action === 'cost_delete') {
    $id   = (string)($_POST['id'] ?? '');
    $data = costsData($COSTS, $DEF_RATES);
    $kept = array_values(array_filter($data['items'], function ($it) use ($id) {
        return !isset($it['id']) || $it['id'] !== $id;
    }));
    if (count($kept) === count($data['items'])) jexit(['error' => 'Nie ma takiego wydatku'], 404);
    $data['items'] = $kept;
    writeJson($COSTS, $data);
    jexit(['ok' => true, 'id' => $id]);
}

// Import: każdy wpis ma 'src' — klucz transakcji z wyciągu. Wpis o kluczu, który już
// jest w arkuszu, jest pomijany, więc ten sam (albo nakładający się) CSV można wgrać drugi raz.
if ($action === 'cost_import') {
    $in = json_decode((string)($_POST['items'] ?? ''), true);
    if (!is_array($in) || !$in)  jexit(['error' => 'Brak wpisów do importu.'], 400);
    if (count($in) > 1000)       jexit(['error' => 'Za dużo wpisów naraz (max 1000).'], 400);
    $data  = costsData($COSTS, $DEF_RATES);
    $known = [];
    foreach ($data['items'] as $it) if (isset($it['src'])) $known[$it['src']] = true;
    $added = []; $skipped = 0;
    foreach ($in as $n => $r) {
        if (!is_array($r)) continue;
        $src    = clean($r['src'] ?? '', 300);
        $amount = (float)($r['amount'] ?? 0);
        $cur    = strtoupper((string)($r['cur'] ?? ''));
        $date   = (string)($r['date'] ?? '');
        $name   = clean($r['name'] ?? '', 120);
        if ($src === '' || isset($known[$src])) { $skipped++; continue; }
        if ($name === '' || $amount <= 0 || $amount > 10000000 || !in_array($cur, $CURRENCIES, true)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { $skipped++; continue; }
        $row = [
            'id'     => date('Ymd-His') . '-' . bin2hex(random_bytes(3)),
            'date'   => $date,
            'name'   => $name,
            'cat'    => clean($r['cat'] ?? '', 30),
            'amount' => round($amount, 2),
            'cur'    => $cur,
            'src'    => $src,
        ];
        $known[$src] = true;
        $data['items'][] = $row;
        $added[] = $row;
    }
    if ($added) writeJson($COSTS, $data);
    jexit(['ok' => true, 'added' => $added, 'skipped' => $skipped]);
}

if ($action === 'cost_rates') {
    $in   = json_decode((string)($_POST['rates'] ?? ''), true);
    $data = costsData($COSTS, $DEF_RATES);
    if (!is_array($in)) jexit(['error' => 'Niepoprawne kursy'], 400);
    foreach ($CURRENCIES as $c) {
        if ($c === 'PLN' || !isset($in[$c])) continue;
        $r = (float)str_replace(',', '.', (string)$in[$c]);
        if ($r > 0 && $r < 100) $data['rates'][$c] = $r;
    }
    writeJson($COSTS, $data);
    jexit(['ok' => true, 'rates' => $data['rates']]);
}

jexit(['error' => 'Nieznana akcja'], 400);
