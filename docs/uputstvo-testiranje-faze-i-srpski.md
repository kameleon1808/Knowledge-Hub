# Uputstvo za testiranje Faze I (Phase I) — na srpskom

Korak-po-korak vodič za pokretanje okruženja i ručno testiranje AI Knowledge Base (RAG), izvoza i aktivnosti.

---

## 1. Priprema okruženja

### 1.1 Pokretanje kontejnera

U root-u projekta (gde je `docker-compose.yml`) pokreni:

```bash
docker compose up -d --build
```

Sačekaj da se svi servisi podignu: `app`, `queue`, `reverb`, `web`, `db`, `node`.

**Šta se sada pokreće automatski:**
- **app** — Laravel (PHP-FPM)
- **queue** — radnik za redove (`php artisan queue:work`), obrađuje dokumente (PDF/DOCX/TXT) i e-mail stavke
- **reverb** — WebSocket server (opciono za real-time)
- **web** — Nginx (aplikacija na http://localhost:8080)
- **db** — PostgreSQL sa pgvector
- **node** — Vite (frontend na http://localhost:5173)

### 1.2 Prva instalacija (ako još nisi radio)

Ako projekat prvi put kloniraš ili nisi radio migracije:

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan storage:link
```

- **composer install** — PHP zavisnosti (uključujući phpoffice/phpword, smalot/pdfparser)
- **key:generate** — aplikacioni ključ
- **migrate** — tabele u bazi (uključujući projekte, knowledge_items, knowledge_chunks, rag_queries, activity_logs)
- **db:seed** — demo korisnici, kategorije, pitanja/odgovori i **demo projekat Faze I** („Demo Knowledge Base” + 2 e-mail stavke + RAG upit + aktivnosti)
- **storage:link** — link za preuzimanje fajlova (izvoz, dokumenti)

---

## 2. Prijava i pristup projektima

### 2.1 Otvori aplikaciju

1. U browseru otvori: **http://localhost:8080**
2. Ako vidiš stranicu za prijavu, klikni **Login**.

### 2.2 Prijavi se demo nalozima

Možeš koristiti:

| Uloga   | E-mail                    | Lozinka  |
|--------|----------------------------|----------|
| Admin  | admin@knowledge-hub.test  | password |
| Član   | member@knowledge-hub.test  | password |
| Moderator | moderator@knowledge-hub.test | password |

Za test Faze I dovoljno su **admin** i **member**.

### 2.3 Lista projekata

1. U meniju otvori **Projects** (ili idi na `/projects`).
2. Trebalo bi da vidiš projekat **„Demo Knowledge Base”** (kreiran seed-om).
3. Klikni na njega da uđeš u projekat.

---

## 3. Testiranje Knowledge Base (dokumenti i e-mail)

### 3.1 Tab „Knowledge Base”

1. U projektu otvori tab **„Knowledge Base”**.
2. Trebalo bi da vidiš dve e-mail stavke (seed): **„Welcome email”** i **„Release notes”**. Status može biti **Pending** dok queue ne obradi stavke.

### 3.2 Obrada stavki (queue)

- Kontejner **queue** već radi (`docker compose up`), tako da će **Pending** stavke vremenom dobiti status **Processed** (ili **Failed** ako nešto pukne).
- Ako želiš da odmah vidiš promenu: osveži stranicu nakon 10–20 sekundi.
- Ako ne vidiš promenu: proveri da li kontejner `queue` radi:  
  `docker compose ps` — servis `queue` treba da bude „Up”.
- **Važno:** Ako menjaš `.env` (npr. `AI_PROVIDER`, `GEMINI_API_KEY`), moraš ponovo pokrenuti queue radnika da učita nove vrednosti:  
  `docker compose restart queue` ili `docker compose up -d` (restartuje sve).
- Za RAG i obradu dokumenata koristi se embedding model **gemini-embedding-001** kada je `AI_PROVIDER=gemini` (podrazumevano u configu).

### 3.3 Upload dokumenta (PDF / DOCX / TXT)

1. U tabu **Knowledge Base** nađi formu za upload.
2. Izaberi fajl (npr. neki .txt ili .pdf).
3. Pošalji formu.
4. U listi bi trebalo da se pojavi nova stavka sa statusom **Pending**, zatim **Processed** (kad queue obradi).
5. Ako ostane **Failed**, ispod stavke će se prikazati **error_message** (razlog greške). Proveri da li je `AI_PROVIDER` (mock, openai ili gemini) i odgovarajući API ključ ispravno postavljen u `.env` i da li je queue radnik restartovan posle izmene.

### 3.4 Dodavanje e-mail stavke (ručno)
1. U istom tabu nađi formu **„Dodaj e-mail”** (ili „Add email”).
2. Popuni:
   - **Naslov / Subject** (obavezno)
   - **From** (opciono)
   - **Datum** (opciono)
   - **Tekst e-maila (body)** — obavezno
3. Pošalji. Nova stavka tipa e-mail pojaviće se u listi i queue će je obraditi.

---

## 4. Testiranje RAG (Ask AI)

### 4.1 Tab „Ask AI”

1. U projektu otvori tab **„Ask AI”**.
2. U polje za pitanje upiši npr.: **„Šta je ovaj projekat?”** ili **„What is this project about?”**

### 4.2 Šta očekivati

- Aplikacija šalje pitanje na backend; backend uradi pretragu po vektorima (chunk-ovi iz projekta), pa pozove LLM (ili mock).
- Trebalo bi da dobiješ **odgovor** i eventualno **citiranja** (brojevi [1], [2]… koji vode do izvornih delova teksta).
- Jedan RAG upit je već u bazi (seed): „What is this project about?” — možeš ga videti u istorici na stranici.

### 4.3 Ako AI ne radi (npr. bez API ključa)

- Ako je u `.env` postavljen `AI_PROVIDER=mock`, odgovori će biti iz mock klijenta (bez pravog API-ja).
- **Ako promeniš `AI_PROVIDER` u `.env` a i dalje dobijaš grešku za stari provider (npr. gemini):** očisti keš konfiguracije i restartuj kontejnere:  
  `docker compose exec app php artisan config:clear`  
  zatim `docker compose restart queue app`  
  (ili `docker compose down` pa `docker compose up -d`). Bez toga aplikacija i queue radnik mogu koristiti staru vrednost.
- Za prave odgovore iz dokumenata potrebni su `AI_PROVIDER` (mock, openai ili gemini) i odgovarajući API ključ. RAG i obrada dokumenata podržavaju mock, openai i gemini. Vidi `docs/phase-i-rag-knowledge-base.md` i `backend/.env.example`.

---

## 5. Testiranje izvoza (Export)

### 5.1 Tab „Exports”

1. U projektu otvori tab **„Exports”**.
2. Trebalo bi da imaš dugmad tipa **„Export as Markdown”** i **„Export as PDF”**.

### 5.2 Izvoz u Markdown

1. Klikni **„Export as Markdown”**.
2. Trebalo bi da se preuzme `.md` fajl (naziv može sadržati UUID).
3. Otvori fajl: unutra treba da bude naslov projekta, opis i sadržaj stavki iz Knowledge Base.

### 5.3 Izvoz u PDF

1. Klikni **„Export as PDF”**.
2. Trebalo bi da se preuzme `.pdf` fajl.
3. Otvori PDF: isti sadržaj kao u Markdown izvozu, u čitljivom formatu.

---

## 6. Testiranje Activity log-a

### 6.1 Tab „Activity”

1. U projektu otvori tab **„Activity”**.
2. Trebalo bi da vidiš listu **nedavnih događaja**, npr.:
   - `project.created`
   - `knowledge_item.uploaded`
   - `rag.asked`
   - `export.generated` (ako si radio izvoz)

Lista je paginirana; stariji događaji su ispod.

---

## 7. Testiranje prava pristupa

### 7.1 Član (member) ne može da menja projekat

1. Izloguj se. Prijavi se kao **member@knowledge-hub.test** (lozinka: password).
2. Otvori **Projects** i uđi u **„Demo Knowledge Base”**.
3. Trebalo bi da vidiš projekat i tabove (Knowledge Base, Ask AI, Exports, Activity), ali **ne** i dugme/formu za izmenu projekta ili upravljanje članovima (to je za vlasnika/admina).

### 7.2 Nepoznati korisnik ne sme da vidi projekat

1. Kreiraj novi projekat kao **admin** (npr. „Test projekat 2”) i **ne dodavaj** member@ kao člana.
2. Izloguj se i prijavi se kao **member@knowledge-hub.test**.
3. Na listi projekata trebalo bi da vidiš samo projekte gde si član (npr. „Demo Knowledge Base”). „Test projekat 2” ne bi trebalo da vidiš, ili ako ga otvoriš direktno po URL-u (`/projects/{id}`) trebalo bi da dobiješ **403 Forbidden**.

### 7.4 Add member / Remove member (vlasnik ili admin)

1. Prijavi se kao **vlasnik projekta** ili **admin**. Otvori projekat i skroluj do sekcije **Members**.
2. **Dodavanje člana:** U polje „Search by email or name...” ukucaj deo e-maila ili imena korisnika (npr. „member”). Pojaviće se lista korisnika koji još nisu u projektu. Klikni **Add** pored željenog korisnika. Član se dodaje sa ulogom „member”.
3. **Uklanjanje člana:** Pored svakog člana (osim vlasnika) postoji dugme **Remove**. Klikni ga i potvrdi. Vlasnika (owner) nije moguće ukloniti.

### 7.3 Admin vidi sve

1. Prijavi se kao **admin@knowledge-hub.test**.
2. U **Projects** trebalo bi da vidiš sve projekte i da možeš da ih otvoriš, menjaš i upravljaš članovima (ako je UI za to implementiran).

---

## 8. Brzi pregled komandi (Docker)

| Zadatak | Komanda |
|--------|--------|
| Podići sve (uključujući queue) | `docker compose up -d --build` |
| Status servisa | `docker compose ps` |
| Logovi queue radnika | `docker compose logs -f queue` |
| Logovi aplikacije | `docker compose logs -f app` |
| Migracije | `docker compose exec app php artisan migrate --force` |
| Seed (demo podaci) | `docker compose exec app php artisan db:seed --force` |
| Testovi Faze I | `docker compose exec app php artisan test --filter=PhaseITest` |

---

## 9. Rezime redosleda za prvi test

1. **Pokreni kontejnere:** `docker compose up -d --build`
2. **Instaliraj i bazu (ako treba):** composer install, key:generate, migrate, db:seed, storage:link
3. **Otvori:** http://localhost:8080 → Login → admin@knowledge-hub.test / password
4. **Projects** → otvori „Demo Knowledge Base”
5. **Knowledge Base** — proveri stavke, eventualno uploaduj fajl ili dodaj e-mail
6. **Ask AI** — postavi pitanje i proveri odgovor/citiranja
7. **Exports** — preuzmi Markdown i PDF
8. **Activity** — proveri da li se vidi aktivnost
9. **Prava** — prijavi se kao member i proveri da ne vidiš tudje projekte i da ne možeš da menjaš projekat

Na ovaj način imaš kompletan ručni test Faze I uz queue radnika koji radi u posebnom kontejneru bez ručnog pokretanja `queue:work` u terminalu.
