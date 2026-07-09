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

## Zależności (CDN)

- [Leaflet 1.9.4](https://leafletjs.com/) — mapa
- [OpenStreetMap](https://www.openstreetmap.org/) — kafelki

## Licencja

Prywatny plan podróży. Rób z nim co chcesz.
