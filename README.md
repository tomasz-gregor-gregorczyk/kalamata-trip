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
  Notatki i checkboxy zapisują się w `localStorage`.
- **Kalkulator kosztów** (paliwo + opłaty + noclegi) — pola do edycji na żywo.

## Założenia planu

- Start: piątek 2.10.2026 po pracy (15:00). Powrót do ~26.10. Pobyt w Kalamacie ~18 nocy.
- Dni robocze: jazda dopiero po pracy (po 15:00). Weekendy: długie dni (~800 km).
- Cel logistyczny: jak najszybciej do Grecji (UE) — działający internet do pracy.
  Strefę roamingu (Serbia + Macedonia) przejeżdżamy w weekend.
- Pojazd: Opel Vivaro L2H2 (kamper ≤3,5 t) — wysoki bus = kat. 2 opłat w RS/MK/GR.
- Spalanie założone ~10 l/100 km (ON).
- Nocleg w Belgradzie: Camp Dunav (Zemun), przy E-75.

## Uwagi

- Mapa wymaga internetu (kafelki + biblioteka z CDN). Rozpiska i kalkulator działają offline.
- Dane (odległości, ceny winiet 2026, godziny granic, pogoda) są szacunkowe — przed
  wyjazdem warto potwierdzić: winiety SK/HU, stawki opłat kat. 2, status tunelu Llogara
  (wariant adriatycki), rozkłady kamer granicznych.

## Śledzenie na żywo (PHP) — „gdzie teraz jesteśmy"

Znajomi otwierają `index.html` na serwerze i widzą znacznik 🚐 z Waszą aktualną
pozycją + czasem ostatniej aktualizacji (mapa odświeża się co 60 s). Wy aktualizujecie
pozycję przez `panel.php`.

Pliki:

- `where.php` — API: `GET` zwraca pozycję (JSON), `POST` (z tokenem) ją zapisuje.
- `panel.php` — panel do aktualizacji: token + wybór przystanku / **GPS telefonu** / ręczne
  współrzędne + notatka.
- `config.php` — Twój tajny **token** (skopiowany z `config.example.php`). W `.gitignore`.
- `location.json` — bieżąca pozycja (zapisywana przez `where.php`). W `.gitignore`.

### Wdrożenie na serwer PHP

1. Wgraj cały folder na hosting (FTP/panel).
2. Skopiuj `config.example.php` → `config.php` i ustaw własny token
   (albo wgraj gotowy `config.php` i zmień w nim token).
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

1. Repo na GitHubie (prywatne). Wypchnij kod: `git push -u origin main`.
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
