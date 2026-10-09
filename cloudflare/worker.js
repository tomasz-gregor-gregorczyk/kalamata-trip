// Przekaźnik https -> http dla grecja.gofamily.pl.
// Certyfikat strony wygasł 7.10.2026, a OwnTracks (i GPS w panel.php) wymagają https.
// Worker na *.workers.dev ma ważny certyfikat i przekazuje każde zapytanie 1:1
// do serwera po http — ścieżka, query (z tokenem), metoda i treść bez zmian.
// Wklejany ręcznie w panelu Cloudflare (Workers & Pages) (na FTP trafia tylko jako nieszkodliwa kopia).
const ORIGIN = 'http://grecja.gofamily.pl';

export default {
  async fetch(req) {
    const url = new URL(req.url);
    const headers = new Headers(req.headers);
    headers.delete('host');
    const init = { method: req.method, headers, redirect: 'manual' };
    if (req.method !== 'GET' && req.method !== 'HEAD') init.body = req.body;

    const res = await fetch(ORIGIN + url.pathname + url.search, init);

    // Przekierowania z serwera wskazują na http://grecja… — kierujemy je z powrotem na workera
    const out = new Headers(res.headers);
    const loc = out.get('Location');
    if (loc && loc.startsWith(ORIGIN)) out.set('Location', url.origin + loc.slice(ORIGIN.length));
    return new Response(res.body, { status: res.status, statusText: res.statusText, headers: out });
  },
};
