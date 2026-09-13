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
//   POST action=check       + token    -> sprawdzenie tokenu (tryb edycji w index.html)
//
// Zapis wymaga tokenu z 'token' w config.php — tego samego co panel.
// Odczyt jest publiczny: znajomi mają widzieć notatki i zdjęcia, ale nie ruszać.

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

jexit(['error' => 'Nieznana akcja'], 400);
