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
  Controller/             ← разбор $_GET, ETag, оркестрация
  Repository/
    NewsRepository        ← чистый SQL (PDO)
    CategoryRepository    ← upsert + loadMap + findActiveWithNews
    CachedNewsRepository  ← декоратор: ключи, get/set, версионирование
  Service/Rss/
    CurlRssClient         ← HTTP через cURL, за RssClientInterface
    RssParser             ← SimpleXML → iterable<NewsItem>
    SlugExtractor         ← slug из URL-пути
  Application/
    ParseFeedHandler      ← оркестратор: fetch → parse → upsert → invalidate
  Cache/CacheVersion      ← версионирование Memcached-ключей
  Logger/StderrLogger     ← PSR-3 адаптер → php://stderr
  Dto/                    ← NewsItem, NewsFilters, NewsPage, NewsRow

templates/                ← PHP-шаблоны (Bootstrap 5, flatpickr)
sql/
  01_schema.sql           ← основная схема (авто-накатывается при первом старте)
  02_test_db.sql          ← тестовая БД news_test
```

**Слои:** Controller → CachedNewsRepository → NewsRepository.
Контроллер не знает о кеше, NewsRepository не знает о Memcached.

---

## Принятые компромиссы

### Категории = slug из URL
РИА не отдаёт настоящие рубрики в RSS: тег `<category>` всегда «Лента новостей», namespace-теги `rian:type`/`rian:priority` к рубрикам не относятся, рубричные фиды недоступны (404).

Категория выводится из slug URL:
```
https://ria.ru/20260522/medvedev-2094017408.html → medvedev
```

В продакшне источником был бы рубричный фид или парсинг страницы новости.

### Пагинация — LIMIT/OFFSET
Для недельной выдачи (~500–2000 новостей) и первых десятков страниц `LIMIT/OFFSET` достаточен.

Для больших датасетов оптимальна **keyset-пагинация**:
```sql
WHERE published_at < :cursor ORDER BY published_at DESC LIMIT 20
```
Цена O(1) вместо O(offset).

### php -S вместо nginx + php-fpm
Встроенный сервер — для демо. В продакшне: `nginx` + `php-fpm`, один рабочий процесс на запрос, корректная обработка статики.

### Свой PSR-3 адаптер вместо Monolog
`StderrLogger` (~30 строк) достаточен для CLI и одного потока вывода. Monolog добавил бы зависимость без практической пользы в рамках задания.

### Кеш-инвалидация — версионирование, не flush_all
`flush_all` сносит весь Memcached-инстанс (включая посторонние данные, в проде может быть отключён).
Инкремент версии атомарен и меняет только префикс ключей этого приложения.

### ETag на основе версии кеша
Повторный запрос с `If-None-Match` → 304 Not Modified, PHP не считает данные.
Предпочтительнее `Last-Modified`: версия уже готовый источник, не нужно отдельно хранить дату.
