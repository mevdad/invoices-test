# Invoices — Full-Stack модуль

Мінімальний, але реалістичний модуль обліку рахунків (invoices) для
бухгалтерської системи.

- **Backend:** Laravel 13 — JSON REST API (без аутентифікації, згідно з ТЗ).
- **Frontend:** Nuxt 4 (Vue 3.5, TailwindCSS 4) з SSR, споживає REST API.
- **База даних:** MySQL.
- **Інфраструктура:** Docker (nginx + php-fpm + Nuxt SSR + MySQL).

Laravel віддає **лише** API, а Nuxt — це самостійний фронтенд. Застосунок Nuxt лежить у каталозі `resources/`
і використовує спільний кореневий `package.json`.

---

## Архітектура

```
app/
  Enums/InvoiceStatus.php              pending | approved | rejected
  Models/Invoice.php                   UUID PK, casts, isPending()
  Http/
    Controllers/Api/InvoiceController  тонкий контролер, інжектить сервіс
    Requests/StoreInvoiceRequest       валідація створення
    Requests/UpdateInvoiceRequest      валідація правки + guard на non-pending
    Resources/InvoiceResource          форма JSON-відповіді
  Services/InvoiceService.php          бізнес-логіка (рахує gross_amount, approve)
routes/api.php                         REST + POST /invoices/{id}/approve
database/                             міграція, фабрика (+states), сидер
tests/Feature/InvoiceApiTest.php       14 feature-тестів

resources/                             застосунок Nuxt 4 (srcDir)
  app.vue, error.vue
  pages/invoices/index.vue             список
  pages/invoices/[id].vue              деталі + редагування + approve
  pages/invoices/create.vue            створення
  components/StatusBadge.vue
  components/InvoiceEditForm.vue        vee-validate + zod
  components/Spinner.vue
  composables/useInvoiceApi.ts          API-клієнт (base URL з урахуванням SSR)
  utils/format.ts                       форматери без hydration-mismatch
  types/invoice.ts
nuxt.config.ts                         srcDir: resources, SSR увімкнено

_docker/                               Dockerfile, nginx, supervisor, entrypoint
docker-compose.yml                     nginx + app + db, локально на :5000
```

### API

| Метод | Endpoint                       | Примітки                                          |
| ----- | ------------------------------ | ------------------------------------------------- |
| GET   | `/api/invoices`                | Список, нові першими, пагінація (15/стор.)        |
| GET   | `/api/invoices/{id}`           | Один рахунок (404, якщо не знайдено)              |
| POST  | `/api/invoices`                | Створення — 201; повна валідація                  |
| PUT   | `/api/invoices/{id}`           | Оновлення — лише `pending` (інакше 409)           |
| POST  | `/api/invoices/{id}/approve`   | Підтвердження — лише `pending` (інакше 409)       |

Бізнес-правила перевіряються на сервері: унікальність `number`, `net_amount > 0`,
`vat_amount >= 0`, `due_date >= issue_date`, і `gross_amount = net + vat`.

---

## Відповіді на запитання

### 1. Як структуровано frontend і backend?

Laravel — це **API-only бекенд**: валідація у Form Request, бізнес-логіка в
єдиному сервісі `InvoiceService`, формування відповіді в API Resource, а
контролер лишається тонким (лише зв'язує компоненти). Статус — це backed enum,
первинний ключ — UUID. Переходи стану (`update`, `approve`) дозволені тільки для
`pending` і захищені на рівні запиту/контролера (409).

Фронтенд — **самостійний Nuxt 4 SSR-застосунок** у `resources/`. Сторінки
завантажують дані через `useAsyncData` і невеликий composable `useInvoiceApi`;
форми (`create`, `edit`) використовують `vee-validate` + `zod`. Обидві частини
спілкуються лише по HTTP/JSON, що чітко розділяє відповідальність.

### 2. Які компроміси і чому?

- **`gross_amount` обчислюється на сервері** і ніколи не береться від клієнта —
  єдине джерело істини. Фронтенд перераховує його лише для відображення.
- **Guard на non-pending повертає `409 Conflict`** і спрацьовує *до* валідації
  полів (у `UpdateInvoiceRequest::prepareForValidation`), тож заблокований
  рахунок відхиляється незалежно від тіла запиту.
- **Гроші зберігаються як decimal.** У проді варто перейти на цілі (копійки) або
  Money-обʼєкт, щоб уникнути похибок округлення.
- **Адреса API не захардкоджена:** браузер ходить на відносний `/api`, SSR — на
  loopback (`http://nginx/api` у Docker). Перенесення на інший домен = зміна лише
  `server_name` у nginx, без перебілду.
- **Без аутентифікації** — явно поза рамками завдання.

### 3. Що б покращив у production-версії?

- Повноцінний workflow зміни статусу (додати `reject`, переходи, історію змін /
  audit log), аутентифікація + policies.
- Гроші як цілі (копійки) / value object; правила округлення для валют.
- Rate limiting, OpenAPI-документація, структуроване логування, CI.
- Фронтенд: елементи пагінації, optimistic updates, E2E-тести (Playwright) та
  компонентні тести.
- Docker: healthcheck для `app`/nginx, `.dockerignore`, окремий multi-stage build
  замість збірки в `entrypoint`.

### 4. Які UX edge cases враховано?

- Список: skeleton під час завантаження, стан помилки з повтором, порожній список.
- Деталі: рендер на сервері (SSR); невідомий id → сторінка 404 Nuxt (із сервера).
- Форми (`create`/`edit`): валідація на клієнті (zod) **і** на сервері (422),
  серверні помилки мапляться на потрібні поля (напр. неунікальний `number`);
  `gross` рахується автоматично; форма редагування **заблокована** для не-pending.
- Спінери та плавність: спінер у кнопках під час збереження/створення/approve,
  плавні переходи повідомлень і сторінок (`<Transition>` / `pageTransition`),
  повідомлення про успіх створення/редагування/approve без миготіння (дані
  оновлюються на місці, без зайвого refetch).
- Дії зі станом: `409` (рахунок уже не pending) показується повідомленням форми;
  кнопки заблоковані під час запиту.
- SSR: детерміноване форматування сум/дат, щоб уникнути hydration mismatch;
  початкові дані вантажаться на сервері й переюзаються на клієнті (без повторного
  запиту).

---

## Запуск через Docker (рекомендовано)

```bash
cp .env.example .env            # створити .env (gitignored)
docker compose up -d --build
# відкрити http://localhost:5000
```

Контейнери: `nginx` (порт хоста **5000** → 80), `app` (php-fpm + Nuxt SSR під
supervisor + cron/scheduler + queue worker), `db` (MySQL 8). `entrypoint`
встановлює залежності, білдить Nuxt (`.output`), піднімає міграції.

Перед `docker compose up` перевір, що у `.env` для Docker задано:

```
DB_HOST=db
DB_DATABASE=invoices
DB_USERNAME=...
DB_PASSWORD=...
APP_URL=http://localhost:5000
```

> `docker compose` читає той самий `.env` для підстановки `${DB_*}` у
> `docker-compose.yml`, тож значення БД треба заповнити до старту.

Маршрутизація: браузер → `:5000` → nginx → `/` на Nuxt (`app:3000`), `/api` на
php-fpm. SSR усередині Docker ходить на `http://nginx/api`, браузер — на
відносний `/api`.

---

## Локальний запуск (без Docker)

Вимоги: PHP 8.3+, Composer, Node 20+, MySQL.

```bash
composer install
php artisan migrate --seed      # таблиці + ~15 демо-рахунків
npm install
npm run dev                     # http://localhost:3000
```

У dev браузер ходить на відносний `/api`, а Nuxt проксує його в Laravel
(`nitro.devProxy` у `nuxt.config.ts`) — same-origin, CORS не потрібен.

### Тести

```bash
php artisan test --filter=InvoiceApiTest
```
