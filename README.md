# IndieStats

Analytics web **privacy-oriented** e minimale, costruita con [Laravel 13](https://laravel.com/docs/13.x) e [Vue 3 + Inertia](https://laravel.com/docs/13.x/starter-kits). Ogni utente gestisce **più siti**, ognuno con uno snippet di tracciamento dedicato.

## Requisiti

| Software | Versione indicativa |
|----------|---------------------|
| PHP | ^8.3 |
| Composer | 2.x |
| Node.js | 20+ (consigliato) |
| npm | 9+ |

Estensioni PHP usuali per Laravel: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`.

## Installazione rapida

Dal clone del repository:

```bash
composer run setup
```

Lo script `setup` esegue in sequenza: `composer install`, crea `.env` da `.env.example` se manca, `php artisan key:generate`, migrazioni, `npm install` e `npm run build`.

Poi avvia l’ambiente di sviluppo:

```bash
composer run dev
```

Apri il browser su **http://127.0.0.1:8000** (o la porta mostrata da `php artisan serve`). Il comando `dev` avvia in parallelo server PHP, worker code, log Pail e Vite.

## Installazione manuale

1. **Clona** il repository e entra nella cartella del progetto.

2. **Dipendenze PHP e asset:**

   ```bash
   composer install
   npm install
   ```

3. **Ambiente:**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database** (di default è SQLite):

   ```bash
   touch database/database.sqlite   # solo se il file non esiste già
   php artisan migrate
   ```

   Per **MySQL** o **PostgreSQL**, imposta `DB_*` in `.env` e crea il database vuoto prima di `migrate`.

5. **Build frontend:**

   ```bash
   npm run build
   ```

   In sviluppo puoi usare `npm run dev` insieme a `php artisan serve`.

## Configurazione

### Variabili essenziali (`.env`)

| Variabile | Ruolo |
|-----------|--------|
| `APP_NAME` | Nome mostrato nell’interfaccia |
| `APP_URL` | **Obbligatorio in produzione**: URL pubblico dell’installazione (es. `https://stats.tuodominio.it`). Lo snippet del tracker usa questo dominio per caricare `/i/{uuid}.js` e chiamare `/collect/...`. |
| `APP_ENV` / `APP_DEBUG` | In produzione: `production` e `false` |
| `DB_*` | Connessione database |

### Sessione e code

Lo starter kit usa `SESSION_DRIVER=database` e `QUEUE_CONNECTION=database`. Dopo le migrazioni le tabelle sono già presenti. In produzione configura un worker per le code se usi job in background (il comando `composer run dev` avvia già `queue:listen` in locale).

### GeoIP (paese visitatori, opzionale)

Per le statistiche per **paese** senza servizi esterni:

1. Registrati su [MaxMind](https://www.maxmind.com/) e scarica **GeoLite2 Country** (formato `.mmdb`).
2. Imposta in `.env` il percorso assoluto al file:

   ```env
   GEOIP_DATABASE=/percorso/completo/GeoLite2-Country.mmdb
   ```

Se non imposti `GEOIP_DATABASE`, il paese resterà vuoto nelle statistiche; il resto funziona comunque.

### Email (verifica account, reset password)

Configura `MAIL_*` in `.env` (es. SMTP, Resend, Postmark). In locale `MAIL_MAILER=log` scrive le mail nel log invece di inviarle.

## Utilizzo

### 1. Account

- Registrati dalla home (se la registrazione è abilitata) o crea un utente con `php artisan tinker` / seeder.
- Accedi e, se richiesto, **verifica l’email** prima di usare le pagine protette.

### 2. Creare un sito tracciato

Vai su **Siti** (`/sites` o `/dashboard`):

1. Inserisci un **nome** (solo per te).
2. Opzionale: **domini consentiti**, separati da virgola (es. `miosito.com, www.miosito.com`).

   - Se **vuoto**, il tracker accetta richieste da qualsiasi `Origin` (comodo in sviluppo, **sconsigliato in produzione** senza altre protezioni).
   - Se **compilato**, solo le pagine servite da quei host possono inviare eventi (controllo su header `Origin` / `Referer`).

3. Clicca **Aggiungi sito**.

### 3. Incollare lo snippet

Per ogni sito viene mostrato un **embed code** del tipo:

```html
<script async src="https://TUO-APP-URL/i/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.js"></script>
<noscript><img src="https://TUO-APP-URL/collect/pixel.gif?k=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx&p=/" width="1" height="1" alt="" /></noscript>
```

- Sostituisci `TUO-APP-URL` con l’origine reale della tua installazione (deve coincidere con `APP_URL`).
- Incolla lo snippet **prima della chiusura di `</body>`** sulle pagine da analizzare.

Il file `.js` è generato dinamicamente e contiene la chiave pubblica del sito.

### 4. Dashboard per sito

Apri il sito dalla lista: puoi filtrare il **periodo** con i pulsanti (oggi, 7 / 30 giorni, **3 / 6 mesi**, **1 anno**). Nella dashboard trovi **visitatori unici**, **visualizzazioni**, **tempo medio in pagina**, **click in uscita**, andamento giornaliero, top pagine, sorgenti, browser, dispositivo, paese, **UTM source**, **query di ricerca** (motori di ricerca o parametri `q` / `query` / `s` sulla URL), **eventi custom** e **goal** (vedi sotto).

### Eventi e goal (minimo)

Dopo lo snippet dello script, sul sito tracciato puoi inviare eventi da JavaScript:

```js
window.indiestats.track('nome_evento');
window.indiestats.track('nome_evento', { chiave: 'valore' });
```

- Gli **eventi** sono elencati nel periodo selezionato (nome, conteggi, visitatori unici).
- I **goal** sono configurati in dashboard: **nome** (solo etichetta) e **nome evento** (`nome_evento` deve coincidere con la stringa passata a `track('…')`). Per ogni goal vedi quante volte è stato registrato l’evento e quanti visitatori distinti.

Proprietà opzionali: oggetto **piatto**, fino a 20 chiavi, valori stringa/numero/booleano (normalizzati lato server).

### Conservazione dati (1 anno)

Le tabelle di analytics conservano al massimo **365 giorni** di dati di **pageview**, **click in uscita** ed **eventi custom** (i record dei goal sono solo le definizioni; non scadono). Il comando `php artisan analytics:prune` elimina i record più vecchi; è **pianificato ogni giorno alle 03:15** quando usi lo scheduler Laravel.

- Variabile opzionale: `ANALYTICS_RETENTION_DAYS` (default `365`) in `.env`.
- In produzione aggiungi al **cron** del server:

  ```cron
  * * * * * cd /percorso/al/progetto && php artisan schedule:run >> /dev/null 2>&1
  ```

In locale puoi eseguire manualmente: `php artisan analytics:prune`.

### Cosa viene tracciato (in sintesi)

- Pageview con percorso, referrer classificato (direct, Google, Bing, social, ecc.), UTM dalla query string, tentativo di query SEO da referrer o dalla URL.
- Identificativo visitatore in **localStorage** (prima parte, senza cookie di terze parti lato analytics).
- Durata approssimativa sulla pagina all’uscita (tab nascosta / chiusura).
- Click su link che portano **fuori** dal dominio corrente.
- **Eventi custom** con nome e proprietà opzionali (`indiestats.track`).

## Endpoint pubblici (riferimento)

| Metodo | Percorso | Descrizione |
|--------|----------|-------------|
| `GET` | `/i/{uuid}.js` | Script tracker |
| `POST` | `/collect/pageview` | Registrazione visualizzazione |
| `POST` | `/collect/duration` | Aggiornamento durata |
| `POST` | `/collect/outbound` | Click in uscita |
| `POST` | `/collect/event` | Evento custom (`name`, opz. `properties`) |
| `GET` | `/collect/pixel.gif` | Fallback noscript (tracking limitato) |

Le richieste `POST` su `/collect/*` sono escluse dal token CSRF e hanno CORS aperto per consentire il caricamento da siti esterni. Sono applicati **rate limit** sulle route di raccolta.

## Produzione

1. **`APP_URL`** deve essere l’URL HTTPS finale (senza slash finale non necessario, ma coerente con come generi gli asset).
2. **`php artisan config:cache`** e **`php artisan route:cache`** dopo il deploy.
3. Imposta **`allowed_domains`** per ogni sito così solo i tuoi domini possono usare la chiave pubblica.
4. Se sei dietro proxy/load balancer, configura i **trusted proxies** Laravel così `request()->ip()` e GeoIP vedono l’IP reale del visitatore.
5. Esegui **`npm run build`** ad ogni deploy che modifica il frontend.
6. Configura il **cron** per `schedule:run` (vedi sopra “Conservazione dati”) così la pulizia automatica degli eventi vecchi resta attiva.

## Deploy con [Cipi](https://cipi.sh/)

[Cipi](https://cipi.sh/) è un CLI open source per VPS Ubuntu: stack LEMP, più app isolate, deploy senza downtime, SSL con Let’s Encrypt, worker in Supervisor e cron. Va bene per questo progetto Laravel + Vite.

### Prerequisiti

- VPS **Ubuntu 24.04 LTS o superiore**, accesso root, porte 22 / 80 / 443 aperte (come da [documentazione Cipi](https://cipi.sh/)).
- PHP **8.3+** per l’app (selezionabile alla creazione dell’app).

### Installare Cipi sul server

Sul VPS (una tantum):

```bash
wget -O - https://cipi.sh/setup.sh | bash
```

Salva le credenziali mostrate a fine installazione (root / MariaDB), come indicato sul sito.

### Creare l’applicazione

```bash
cipi app create
```

Indica dominio, repository Git, branch e versione PHP (≥ 8.3). Cipi crea utente di sistema dedicato, database MariaDB e virtual host Nginx.

### Deploy

```bash
cipi deploy nomeapp
```

Il deployer clona il repo in una nuova cartella `releases/N/`, esegue `composer install --no-dev`, collega `shared/.env` e `shared/storage/`, lancia le migrazioni, `artisan optimize`, `storage:link`, aggiorna il symlink **`current` → `releases/N/`** senza downtime, riavvia i worker e tiene le ultime 5 release. Dettaglio della pipeline nella [documentazione Cipi — Customising the deploy script](https://cipi.sh/docs).

### Struttura cartelle (utente app)

Ogni app Laravel vive sotto `/home/nomeapp/` (come utente di sistema `nomeapp`). Schema tipico:

```
/home/nomeapp/
├── .deployer/deploy.php    ← recipe Deployer (personalizzabile)
├── current -> releases/3/  ← symlink alla release attiva
├── releases/1/, 2/, 3/      ← codice per versione; Deployer ne mantiene 5
├── shared/
│   ├── .env                ← un solo .env condiviso tra le release
│   └── storage/            ← storage persistente (symlink nelle release)
└── logs/deploy.log
```

Da SSH come utente app: `cd ~/current` equivale ad entrare nella release puntata da `current` **dopo** un deploy completato.

### Frontend (Vite): npm sul server **oppure** build in locale

IndieStats compila JS/CSS con Vite (`npm run build` → `public/build/`). La pipeline predefinita di Cipi **non** include Node: installa solo dipendenze PHP con Composer. Node va aggiunto tu sul VPS (es. [NodeSource](https://github.com/nodesource/distributions), [nvm](https://github.com/nvm-sh/nvm), ecc.) e verificato con `node -v` / `npm -v` come utente dell’app.

| Situazione | Cosa fare |
|------------|-----------|
| **Hai Node sul server** | Automatizza la build **nel recipe Deployer** (consigliato) oppure esegui a mano dopo ogni deploy — vedi sotto. |
| **Non hai Node sul server** | Build **in locale o in CI**: `npm ci && npm run build`, poi copia **`public/build/`** sulla release attiva (`~/current` o path equivalente). Il repo ignora `public/build` (`.gitignore`), quindi in genere si usa `rsync`/`scp` o artifact CI, non il commit della cartella. |

Senza `public/build/` aggiornato, l’interfaccia Inertia non caricherà CSS/JS.

#### Con Node: task personalizzato in `deploy.php`

Il file è **`/home/nomeapp/.deployer/deploy.php`**. La pipeline predefinita esegue tra l’altro: `deploy:prepare` → `deploy:vendors` (Composer) → `deploy:shared` → `artisan:migrate` → … → `deploy:symlink` → …

Puoi aggiungere un task che, **nella directory della release in costruzione**, lancia npm subito dopo Composer (così `package-lock.json` è già sul disco), **prima** dello scambio del symlink:

```php
task('npm:build', function () {
    run('cd {{release_path}} && npm ci && npm run build');
});
after('deploy:vendors', 'npm:build');
```

Usa i path e le variabili come nella [documentazione Cipi](https://cipi.sh/docs) (es. `{{release_path}}`, `{{bin/php}}` negli esempi ufficiali). Dopo modifiche a `deploy.php`, testa con `cipi deploy nomeapp` e controlla `~/logs/deploy.log` o `cipi app logs nomeapp --type=deploy` in caso di errori.

**Attenzione:** Cipi può **rigenerare** `deploy.php` quando esegui `cipi app edit nomeapp --php=…` o `--branch=…`. Tieni i task custom in fondo al file e fai backup, come raccomandato nella docs.

#### Con Node: build manuale dopo il deploy

Se non vuoi toccare `deploy.php`: dopo `cipi deploy nomeapp`, collegati come utente app (`ssh nomeapp@ip-del-vps`), `cd ~/current` e lancia `npm ci && npm run build` una tantum per quel rilascio. Ripeti quando cambi il frontend (meno comodo ma valido per prove).

### SSL e cron

- HTTPS: `cipi ssl install nomeapp` (come da flusso Cipi).
- **Scheduler Laravel**: aggiungi il cron che esegue `php artisan schedule:run` (utente e path dell’app come da configurazione Cipi), così restano attivi anche `analytics:prune` e altri task pianificati.

### Code (opzionale)

Se usi code Laravel in background, configura i worker Supervisor per l’app (Cipi gestisce code per applicazione). Per solo HTTP + database + sessione, spesso basta il web server.

### Risorse

- [Sito Cipi](https://cipi.sh/) — panoramica, quick start, stack
- Documentazione completa e comandi CLI: dalla sezione *Docs* sul sito

## Sviluppo

```bash
# Qualità codice PHP
composer run lint

# Test
php artisan test

# Frontend (tipi e lint)
npm run types:check
npm run lint:check
```

## Licenza

MIT (come da skeleton Laravel / starter kit; verifica eventuali licenze di dipendenze per il tuo uso).

## Risorse

- [Laravel 13 — Documentazione](https://laravel.com/docs/13.x)
- [Starter kit Vue](https://laravel.com/docs/13.x/starter-kits)
- [Cipi — Easy Laravel Deployments](https://cipi.sh/)
