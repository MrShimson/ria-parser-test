# Новостной агрегатор РИА

Тестовое задание. Native PHP 8.3 + MySQL 8 + Memcached, без фреймворка.

## Быстрый старт

```bash
make init          # сборка образов + запуск + composer install
```

Открыть: http://localhost:8080

## Повседневно

```bash
make up      # запустить контейнеры
make down    # остановить
```

## Ручной запуск парсера

```bash
make parse
```

## Cron (раз в час)

```
0 * * * * cd /path/to/project && docker compose exec -T php php bin/console.php >> /var/log/ria-parser.log 2>&1
```

## Тесты

```bash
make test
```

---

## Архитектура

```
public/index.php          ← front controller (только это видит веб-сервер)
bin/console.php           ← CLI-точка входа для парсера (cron)

src/
  Config/AppConfig        ← readonly-конфиг из env
  Bootstrap               ← создаёт PDO + Memcached + Logger
  Controller/             ← разбор $_GET → DTO, ETag, вызов UseCase, рендер
  Application/
    ListNewsUseCase       ← нормализация фильтров (даты, окно), вызов репозитория
    ParseFeedHandler      ← оркестратор парсера: fetch → parse → upsert → invalidate
  Repository/
    NewsRepository        ← чистый SQL (PDO)
    CategoryRepository    ← upsert + loadMap + findActiveWithNews
    CachedNewsRepository  ← декоратор: ключи, get/set, версионирование
  Service/Rss/
    CurlRssClient         ← HTTP через cURL, за RssClientInterface
    RssParser             ← SimpleXML → iterable<NewsItem>
    SlugExtractor         ← slug из URL-пути
  Cache/CacheVersion      ← версионирование Memcached-ключей
  Logger/StderrLogger     ← PSR-3 адаптер → php://stderr
  Dto/                    ← NewsItem, NewsRow, NewsFilters, NewsPage,
                            ListNewsQuery, ListNewsResult

templates/                ← PHP-шаблоны (Bootstrap 5, flatpickr)
sql/
  01_schema.sql           ← основная схема (авто-накатывается при первом старте)
  02_test_db.sql          ← тестовая БД news_test
```

**Слои веба:** Controller → ListNewsUseCase → CachedNewsRepository → NewsRepository.
Контроллер не знает о кеше; репозиторий не знает о Memcached; UseCase не знает о HTTP.

---

## Принятые компромиссы

- **Категории = slug из URL.** РИА не отдаёт рубрики в RSS; в проде — рубричный фид или парсинг страницы новости.
- **Пагинация — `LIMIT/OFFSET`.** Для больших датасетов нужна keyset-пагинация (`WHERE published_at < :cursor`).
- **`php -S` вместо nginx + php-fpm.** Только для демо.
- **Свой PSR-3 адаптер вместо Monolog.** Хватает `StderrLogger` (~30 строк).
- **Кеш-инвалидация — версионирование, не `flush_all`.** Не трогает посторонние ключи в Memcached.
- **ETag на основе версии кеша.** `If-None-Match` → 304, без отдельного `Last-Modified`.
