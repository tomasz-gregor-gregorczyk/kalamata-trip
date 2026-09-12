<?php
// Skopiuj ten plik jako config.php i ustaw własne sekrety.
// config.php jest w .gitignore, żeby nie trafił do repo — a repo jest publiczne.
// Uwaga: workflow deploy.yml NIE wgrywa config.php na serwer, więc zmieniony token
// trzeba podmienić bezpośrednio na hostingu (FTP/menedżer plików).
return [
    // Hasło do panel.php — ręczna aktualizacja pozycji.
    'token' => 'zmien-mnie-na-tajne-haslo',

    // Osobny sekret dla OwnTracks. Siedzi na stałe w telefonie i leci w adresie URL,
    // więc trzymamy go osobno — da się go unieważnić bez zmiany hasła do panelu.
    // W aplikacji ustaw tryb HTTP i adres:
    //   https://TWOJA-DOMENA/where.php?token=TEN_TOKEN
    // Jeśli zostawisz null, automat będzie używał tokenu z 'token' powyżej.
    'track_token' => 'zmien-mnie-na-inny-tajny-token',

    // Strefa domowa: automatyczne pozycje w tym promieniu NIE są zapisywane,
    // żeby publiczna strona nie ogłaszała, kiedy dom stoi pusty.
    // Panel działa normalnie — ręczny zapis jest świadomą decyzją.
    // Ustaw na null, żeby wyłączyć.
    'home' => [
        'lat'       => 54.5189,   // Gdynia
        'lng'       => 18.5305,
        'radius_km' => 5,
    ],

    // Minimalny odstęp między automatycznymi zapisami (sekundy).
    // OwnTracks w trybie "move" nadaje co kilkanaście sekund, a mapa i tak
    // odpytuje raz na minutę. Pozycje oddalone o >300 m zapisują się zawsze.
    'min_interval_s' => 60,
];
