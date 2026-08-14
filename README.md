# Invoice App

Full-stack модуль для створення та управління рахунками (invoices).

## Стек

| Слой | Технології |
|---|---|
| Frontend | Nuxt 4, Vue 3.5, TailwindCSS 4, Vee-Validate + Zod |
| Backend | PHP 8.3, Laravel 13, PostgreSQL 16 |
| Інфраструктура | Docker Compose |

---

## 1. Як структуровані frontend і backend?

### Backend

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/InvoiceController.php   # REST CRUD (index, store, show, update)
│   │   └── Requests/
│   │       ├── StoreInvoiceRequest.php             # Валідація створення
│   │       └── UpdateInvoiceRequest.php            # Валідація оновлення + optimistic lock
│   ├── Models/Invoice.php                          # Eloquent-модель (UUID, decimal casts)
│   └── Support/MoneyNormalizer.php                 # Нормалізація грошових сум через bcmath
├── database/
│   ├── migrations/                                 # Схема таблиці invoices
│   ├── factories/InvoiceFactory.php                # Фабрика для тестових даних
│   └── seeders/DatabaseSeeder.php                  # Сидування 20 рахунків
└── routes/api.php                                  # Route::apiResource('invoices')
```

Контролер тонкий — валідація винесена у FormRequest, грошова логіка — у `MoneyNormalizer`.
Оновлення перевіряє статус `pending` через `authorize()` і повертає 403 з JSON-повідомленням.
Optimistic locking реалізований через порівняння `updated_at` (HTTP 409 Conflict).

### Frontend

```
frontend/app/
├── pages/
│   ├── invoices/index.vue      # Список рахунків (таблиця, клік → деталі)
│   └── invoices/[id].vue       # Деталі + форма редагування (vee-validate + zod)
├── composables/useApi.ts       # SSR-aware API-клієнт (useFetch / $fetch)
├── types/invoice.ts            # TypeScript-інтерфейси
└── utils/
    ├── formatters.ts           # formatDate (UTC) / formatDateTime (Europe/Kyiv)
    └── moneyNormalizer.ts      # Парсинг і форматування грошей через BigInt
```

Сторінки використовують file-based routing Nuxt 4.
API-клієнт автоматично обирає internal URL (SSR) або public URL (client-side).
Форма редагування перераховує `gross_amount` автоматично через `watch([net, vat])`.

---

## 2. Які компроміси ти зробив і чому?

**`php artisan serve` замість Nginx + PHP-FPM**
Для тестового завдання з docker-compose вбудований сервер Laravel достатній. Nginx + php-fpm додає складності конфігурації без відчутної вигоди на етапі розробки.

**Без пагінації**
ТЗ позначає пагінацію як опціональну. Для 20 сід-записів вона не потрібна. Додавання `paginate()` замість `get()` — тривіальна зміна.

**Хардкод `Europe/Kyiv` у `formatDateTime`**
Для тестового завдання фіксований часовий пояс прийнятний. У production це має бути конфігурованим значенням або визначатися з профілю користувача.

**Без сервісного шару**
Логіка CRUD інвойсів проста enough, щоб жити у контролері + FormRequest. Окремий InvoiceService був би надлишковим для цього обсягу функціональності.

**Optimistic locking замість песимістичного**
409 Conflict замість блокування рядка БД. Для UI з формою редагування це дає кращий UX: користувач не стикається з таймаутами блокування.

---

## 3. Що б ти покращив у production-версії?

- **Nginx + PHP-FPM** замість `artisan serve` — стабільність, concurrency, graceful restart
- **Rate limiting** на API-ендпоінти (Laravel throttle middleware)
- **Пагінація** з cursor-based або offset-based підходом + метадані у відповіді
- **CORS middleware** — явна конфігурація дозволених доменів
- **Індекси БД** — `status`, `due_date`, `issue_date` для фільтрації та сортування
- **Аутентифікація** (Laravel Sanctum) та авторизація на рівні ролей
- **Unit/Feature тести** — покриття CRUD, валідації, optimistic locking, edge cases
- **Логування та моніторинг** — Sentry або аналог для відстеження помилок у реальному часі
- **Конфігурація часового поясу** через змінну оточення замість хардкоду
- **CI/CD pipeline** — lint, тести, збірка образів, деплой

---

## 4. Які UX edge cases ти врахував?

**Конкурентне редагування (Optimistic Locking)**
Якщо інший користувач змінив рахунок, поки поточний редагував форму, сервер повертає 409 Conflict. Frontend показує повідомлення із запитом оновити сторінку. Жодні дані не губляться мовчки.

**Серверна корекція `gross_amount`**
Сервер завжди перераховує `gross = net + vat`. Якщо серверне значення відрізняється від клієнтського (наприклад, через розбіжності в округленні), frontend показує amber-попередження з конкретними цифрами: «сума змінена з X на Y».

**Форма заблокована для non-pending**
Рахунки зі статусом «Схвалено» або «Відхилено» показують amber-банер з поясненням, чому редагування недоступне. Кнопка «Зберегти» не рендериться.

**Loading / Error / Empty states**
Кожна сторінка має три стани: спіннер під час завантаження, повідомлення про помилку з кнопкою «Повторити», та empty state, коли рахунків немає.

**Locale-aware парсинг грошей**
Frontend приймає і `1 234,56` (український формат), і `1234.56` (API-формат). BigInt-арифметика усуває втрату копійок.

**Часові пояси**
Дати без часу (`issue_date`, `due_date`) форматуються через `UTC` — це усуває зсув на день, коли JavaScript парсить `"2026-08-14"` як опівніч UTC. Timestamps (`created_at`, `updated_at`) відображаються у `Europe/Kyiv`.

**Кнопка збереження**
Блокується, якщо форма невалідна (`!meta.valid`), не було змін (`!meta.dirty`), або запит у процесі (`isSubmitting`). Текст змінюється на «Збереження...» під час відправки.

---

## Запуск проєкту

### Вимоги

- Docker та Docker Compose

### Перший запуск (після `git clone`)

```bash
# 1. Переходимо у директорію проєкту
cd invoice-app

# 2. Створюємо .env файли
cp .env.example .env
cp backend/.env.example backend/.env

# 3. Запускаємо PostgreSQL (backend поки не чіпаємо)
docker compose up -d postgres

# 4. Встановлюємо PHP-залежності всередині контейнера
docker compose run --rm --no-deps backend composer install

# 5. Генеруємо APP_KEY
docker compose run --rm --no-deps backend php artisan key:generate

# 6. Запускаємо всі сервіси
docker compose up -d
```

На цьому етапі `entrypoint.sh` автоматично виконає міграції та засіє 20 тестових рахунків.

Frontend (`npm install` + `nuxt dev`) стартує автоматично через CMD у Dockerfile.

### Доступ

| Сервіс | URL |
|---|---|
| Frontend (Nuxt) | http://localhost:3000 |
| Backend API (Laravel) | http://localhost:8000 |
| PostgreSQL | localhost:5432 |

### Подальші запуски

```bash
docker compose up -d           
```

### Корисні команди

```bash
# Переглянути логи backend
docker compose logs -f backend

# Перезапустити backend (наприклад, після зміни коду)
docker compose restart backend

# Запустити міграції вручну
docker compose exec backend php artisan migrate

# Засіяти тестові дані (якщо таблиця порожня)
docker compose exec backend php artisan db:seed

# Зупинити все
docker compose down

# Зупинити і видалити дані БД (повне скидання)
docker compose down -v
```
