# Kalamata Trip — plan workation Gdynia → Kalamata (wariant bałkański)

Interaktywny, samodzielny plan wyjazdu kamperem (Opel Vivaro L2H2) z Gdyni do Kalamaty
przez Bałkany, w trybie **workation** (praca zdalna w trasie). Całość to jeden plik
`index.html` — mapa Leaflet + rozpiska dzień po dniu + kalkulator kosztów.

## Uruchomienie

Otwórz `index.html` w przeglądarce (Safari/Chrome, też na iPadzie).

Opcjonalnie lokalny serwer (żeby localStorage działał pewniej niż z `file://`):

```bash
python3 -m http.server 8000
# potem: http://localhost:8000
```

## Co jest w środku

- **Mapa** (Leaflet + OpenStreetMap): trasa, noclegi, granice (klik = kamera live +
  najlepsze godziny), strefa roamingu (Serbia + Macedonia), odcinki górskie (Pindos).
- **Rozpiska** dojazd / pobyt / powrót — każdy dzień z godzinami, dystansem, temperaturą,
  a dni z granicą rozbite na osobne punkty (przejazd do granicy / od granicy).
  Notatki i checkboxy są **wspólne, trzymane na serwerze**: widzą je wszyscy, edytuje
  tylko właściciel po włączeniu trybu edycji (przycisk 🔒 w pasku narzędzi + token).
- **Dziennik z trasy** — wpisy i zdjęcia przypięte do miejsc, widoczne na mapie jako
  fioletowe znaczniki 📷 / 📝. Dodajesz je w `panel.php`.
- **Kalkulator kosztów** (paliwo + opłaty + noclegi w drodze + Chalkidiki + Kalamata) — pola do edycji na żywo.

## Założenia planu

- Start: piątek 2.10.2026 po pracy (15:00), powrót we wtorek 27.10. Łącznie ~6 060 km.
- **Dwie bazy:** Chalkidiki / Sithonia 4–10.10 (6 nocy) i Kalamata 10–23.10 (13 nocy).
- Dni robocze: jazda dopiero po pracy (po 15:00). Dojazd 3 dni (pt–nd), powrót 5 dni (pt–wt).
- Bez urlopu, kosztem długich weekendów: sobota 3.10 to ~970 km i ~13 h w drodze
  (start 05:00), niedziela 4.10 ~790 km. Przeskok Chalkidiki → Kalamata (~895 km)
  wypada w sobotę 10.10, więc też nie kosztuje dnia wolnego.
- Trasa na mapie liczona routerem OSRM na danych OpenStreetMap i wklejona na stałe.
  Przeskok do Kalamaty **wymuszony przez A1 i obwodnicę Aten (Attiki Odos)** — router
  sam prowadzi go przez Pindos i most Rio–Antirrio, czego plan unika.
- Cel logistyczny: jak najszybciej do Grecji (UE) — działający internet do pracy.
  Strefę roamingu (Serbia + Macedonia) przejeżdżamy w weekend.
- Pojazd: Opel Vivaro L2H2 (kamper ≤3,5 t) — wysoki bus = kat. 2 opłat w RS/MK/GR.
- Spalanie założone ~10 l/100 km (ON).
- Nocleg 1 (pt 2.10): **MOP Woźniki Zachód**, A1 km 461+300 — Circle K i McDonald's 24/7,
  toalety i prysznice całodobowo, spanie w busie za darmo.
- Nocleg w Belgradzie: Camp Dunav (Zemun), przy E-75.
- Chalkidiki: **Christos House**, Imeri Elia / Neos Marmaras — 2 noce (nd+pn), potem
  4 noce na kempingu, którego szukacie w poniedziałek po 13:00.
- Nocleg w Kalamacie: Camping Fare (koniec ul. Navarinou, przy plazy) — otwarty caly rok,
  ~22 EUR/dobe z pradem, psy OK, tel. +30 27210 29520, camping-fare.com.

## Uwagi

- Mapa wymaga internetu (kafelki + biblioteka z CDN). Rozpiska i kalkulator działają offline.
- Dane (odległości, ceny winiet 2026, godziny granic, pogoda) są szacunkowe — przed
  wyjazdem warto potwierdzić: winiety SK/HU, stawki opłat kat. 2, status tunelu Llogara
  (wariant adriatycki), rozkłady kamer granicznych.

## Notatki i dziennik (PHP)

`api.php` obsługuje treści, które mają być **publiczne do czytania, ale tylko Twoje do
zmiany**. Autoryzacja tokenem z `token` w `config.php` — tym samym co panel.

```
GET  api.php?action=notes        -> { notes:{id:tekst}, done:{id:true} }
GET  api.php?action=posts        -> [ {id,lat,lng,text,photo,taken,created} ]
POST action=note|done   + token  -> notatka / zaznaczenie przy dniu rozpiski
POST action=post        + token  -> wpis dziennika (opcjonalnie ze zdjęciem)
POST action=post_delete + token  -> kasowanie wpisu razem z plikiem
POST action=check       + token  -> weryfikacja tokenu (tryb edycji w index.html)
```

Dane lądują w `data/` (`notes.json`, `posts.json`, `uploads/`). Katalog jest w
`.gitignore` **i** w `exclude` workflow deploya, więc ani git, ani publikacja nigdy go
nie nadpiszą. `data/uploads/.htaccess` wyłącza wykonywanie PHP w katalogu ze zdjęciami.

### Zdjęcia

Upload jest w `panel.php`. Przed wysłaniem przeglądarka:

1. czyta z pliku **EXIF** — współrzędne GPS i datę wykonania (parser jest w `panel.php`,
   więc nie potrzeba rozszerzenia `exif` po stronie PHP);
2. **zmniejsza zdjęcie** do 1600 px / JPEG 82% — 4 MB z aparatu schodzi do ~300 KB,
   co ma znaczenie przy wysyłce z telefonu w trasie.

Gdy zdjęcie nie ma GPS (np. wyłączona geolokalizacja w aparacie), wpis dostaje **ostatnią
znaną pozycję** z `location.json`. Możesz też wskazać współrzędne ręcznie.

PHP przyjmuje plik tylko wtedy, gdy `getimagesize()` rozpozna w nim JPEG/PNG/WEBP,
i sam nadaje nazwę oraz rozszerzenie — nazwa z uploadu nie jest używana.

### Historia zameldowań

Każdy zapis pozycji (z OwnTracks i z panelu) jest dopisywany do `data/track.jsonl` —
jedna linia na wpis, z czasem, współrzędnymi i nazwą okolicy. Dopisanie linii jest
atomowe, więc nie trzeba wczytywać całego pliku; przy 6 MB plik jest przycinany do
ostatnich 20 000 wpisów.

```
GET api.php?action=track[&limit=N]   -> historia jako JSON
GET api.php?action=track&format=gpx  -> ta sama historia jako plik GPX
```

W `index.html` historia jest w zwijanej sekcji **📋 Historia zameldowań** nad rozpiską,
a na mapie rysuje się jako biała, kropkowana linia z punktem przy każdym zameldowaniu.
Kliknięcie wiersza przybliża mapę do tego miejsca.

**Endpoint celowo nie wymaga tokenu.** Sensem tej funkcji jest odtworzenie trasy, gdyby
coś się stało — w takiej sytuacji nikt nie powinien szukać hasła. GPX otwiera się
w Google Earth, OsmAnd czy Garminie. Jeśli wolisz to zamknąć, wystarczy przenieść
gałąź `action === 'track'` poniżej `requireToken()` w `api.php`.

### Tryb edycji w index.html

Przycisk 🔒 w pasku narzędzi pyta o token, sprawdza go przez `action=check` i zapamiętuje
w `localStorage`. Dopóki jest włączony, pola notatek i checkboxy są aktywne i zapisują się
na serwer (notatki z opóźnieniem 700 ms, żeby nie wysyłać po znaku). Dla pozostałych
odwiedzających notatki są zwykłym tekstem, bez możliwości edycji.

## Śledzenie na żywo (PHP) — „gdzie teraz jesteśmy"

Znajomi otwierają `index.html` na serwerze i widzą znacznik 🚐 z Waszą aktualną
pozycją + czasem ostatniej aktualizacji (mapa odświeża się co 60 s). Wy aktualizujecie
pozycję przez `panel.php`.

Pliki:

- `where.php` — API pozycji: `GET` zwraca pozycję (JSON), `POST` (z tokenem) ją zapisuje.
- `api.php` — API treści: notatki rozpiski i dziennik ze zdjęciami.
- `panel.php` — panel do aktualizacji: token + wybór przystanku / **GPS telefonu** / ręczne
  współrzędne + notatka.
- `config.php` — Twój tajny **token** (skopiowany z `config.example.php`). W `.gitignore`.
- `location.json` — bieżąca pozycja (zapisywana przez `where.php`). W `.gitignore`.
- `data/` — notatki, wpisy i zdjęcia. W `.gitignore` i w `exclude` deploya.

### Automatyczne śledzenie: OwnTracks

Zamiast klikać w panelu, pozycję może wysyłać aplikacja **OwnTracks** (open source,
iOS i Android, bez konta). Raportuje, kiedy faktycznie jedziecie, a na postoju milknie,
i **kolejkuje wpisy offline** — istotne w Serbii i Macedonii, gdzie macie dane wyłączone:
cały ten odcinek dośle się po wjeździe do Grecji.

Konfiguracja w aplikacji:

1. **Preferences → Connection → Mode: HTTP**
2. **URL:** `https://twojadomena/…/where.php?token=WARTOSC_Z_track_token`
   Parametr nazywa się `token`, a jego **wartość** bierzesz z pola `track_token`
   w `config.php` (przyjmowane jest też `?track_token=...`). To ma być **inny sekret**
   niż hasło do panelu, żeby dało się go unieważnić niezależnie.

   > **Ten adres wpisujesz w aplikacji, nie w przeglądarce.** Otwarcie go w przeglądarce
   > to zwykły `GET` — pokaże aktualnie zapisaną pozycję i niczego nie nadpisze.
   > Zapis robi wyłącznie `POST` z JSON-em, który wysyła OwnTracks.
3. **Mode:** `Significant changes` na co dzień, `Move` na dni przejazdowe.

Co robi endpoint z takim wpisem:

- przyjmuje JSON OwnTracks (`_type: location`, `lat`, `lon`, `batt`, `acc`), pozostałe
  typy (`transition`, `waypoint`, `lwt`) kwituje `200` i ignoruje;
- **nie zapisuje pozycji w strefie domowej** (`home` w `config.php`, domyślnie 5 km wokół
  Gdyni) — publiczna strona nie ma ogłaszać, kiedy dom stoi pusty;
- pomija zapisy częstsze niż `min_interval_s` (domyślnie 60 s), o ile nie przemieściliście
  się o >300 m;
- **czyści etykietę**, bo nieaktualna nazwa miejsca myli bardziej niż jej brak — mapa
  pokaże wtedy „w drodze". Jeśli zdefiniujesz w OwnTracks regiony (waypoints), ich nazwa
  trafi na mapę automatycznie;
- **zostawia notatkę** — to wiadomość od człowieka, znika dopiero gdy zmienisz ją w panelu;
- zapisuje poziom baterii, widoczny na mapie i w panelu.

Panel `panel.php` działa dalej równolegle: przydaje się do dopisania notatki albo
ręcznego nadpisania pozycji.

**Sprawdzenie, czy działa** (z komputera, podstaw swoją domenę i token):

```bash
curl -X POST -H 'Content-Type: application/json' \
  -d '{"_type":"location","lat":44.858,"lon":20.33,"batt":80}' \
  'https://twojadomena/…/where.php?token=TWOJ_TOKEN'
```

Poprawna odpowiedź to `[]` i HTTP 200. Potem odśwież `panel.php` — przy pozycji
powinno pojawić się „📡 automat (OwnTracks)". Zły token zwróci `403`.

### Wdrożenie na serwer PHP

1. Wgraj cały folder na hosting (FTP/panel).
2. Skopiuj `config.example.php` → `config.php` i ustaw własne sekrety: `token` (panel),
   `track_token` (OwnTracks) oraz `home` (strefa domowa).
   Zmiana tokenu = podmiana `config.php` **bezpośrednio na serwerze** — git tego pliku
   nie zna, a deploy go pomija.
3. Upewnij się, że folder ma **prawo zapisu** dla PHP (żeby `location.json` dało się
   nadpisać) — zwykle `chmod 755` folder i `644` pliki wystarcza; jeśli zapis się nie
   udaje, ustaw `location.json` na `666` lub folder na `775`.
4. Wejdź na `https://twojadomena/…/panel.php`, wpisz token, ustaw pozycję (np. „Użyj GPS”).
5. Podaj znajomym link do `index.html` — reszta dzieje się sama.

Uwaga bezpieczeństwa: pozycję może zmienić tylko ktoś z tokenem. Panel jest publiczny,
ale bez tokenu nic nie zapisze. Nie commituj `config.php` (jest ignorowany).

Lokalnie (przez `file://`) live-tracking nie działa (brak PHP) — plan i mapa działają
normalnie, znika tylko znacznik 🚐. Do testów lokalnych: `php -S localhost:8000`.

## Auto-deploy przez GitHub Actions (FTP)

Workflow `.github/workflows/deploy.yml` przy każdym `git push` na gałąź `main`
wgrywa pliki na FTP.

Konfiguracja (raz):

1. Repo na GitHubie. Wypchnij kod: `git push -u origin main`.
   **Uwaga: to repo jest publiczne** — nigdy nie commituj `config.php` ani niczego
   z tokenami. Workflow i tak nie wgrywa `config.php` na serwer.
2. W repo → **Settings → Secrets and variables → Actions → New repository secret** dodaj:
   - `FTP_SERVER` — host, np. `ftp.twojadomena.pl`
   - `FTP_USERNAME` — login FTP
   - `FTP_PASSWORD` — hasło FTP
   - `FTP_SERVER_DIR` — katalog docelowy ZE slashem, np. `public_html/kalamata/`
   - (opcjonalnie) zmienna `FTP_PROTOCOL` = `ftp`, jeśli hosting nie wspiera FTPS.
3. Na serwerze **raz, ręcznie** wgraj `config.php` (z tokenem) i pierwszy `location.json`
   — workflow ich celowo NIE dotyka (są w `exclude`), żeby nie nadpisać sekretu ani
   bieżącej pozycji.

Od teraz: edytujesz plan lokalnie → `git commit` + `git push` → Action sam publikuje.
Pozycję 🚐 w trasie zmieniasz przez `panel.php` (z telefonu), niezależnie od deploya.

## Zależności (CDN)

- [Leaflet 1.9.4](https://leafletjs.com/) — mapa
- [OpenStreetMap](https://www.openstreetmap.org/) — kafelki

## Licencja

Prywatny plan podróży. Rób z nim co chcesz.
