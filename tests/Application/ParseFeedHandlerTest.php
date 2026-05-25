<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Application\ParseFeedHandler;
use App\Cache\CacheVersion;
use App\Logger\StderrLogger;
use App\Repository\CategoryRepository;
use App\Repository\NewsRepository;
use App\Service\Rss\RssClientInterface;
use App\Service\Rss\RssFetchException;
use App\Service\Rss\RssParser;
use Memcached;
use PDO;
use PHPUnit\Framework\TestCase;

final class ParseFeedHandlerTest extends TestCase
{
    private PDO $pdo;
    private Memcached $mc;

    protected function setUp(): void
    {
        $host = getenv('DB_HOST') ?: 'db';
        $name = getenv('DB_NAME_TEST') ?: 'news_test';
        $user = getenv('DB_USER') ?: 'news';
        $pass = getenv('DB_PASS') ?: 'newspass';

        $this->pdo = new PDO(
            "mysql:host={$host};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        $this->pdo->exec('DELETE FROM news');
        $this->pdo->exec('DELETE FROM categories');

        $this->mc = new Memcached();
        $this->mc->addServer(getenv('MC_HOST') ?: 'memcached', (int) (getenv('MC_PORT') ?: 11211));
        $this->mc->flush();
    }

    private function sampleXml(): string
    {
        return file_get_contents(__DIR__ . '/../fixtures/ria-sample.xml');
    }

    private function makeClient(string $xml): RssClientInterface
    {
        return new class($xml) implements RssClientInterface {
            public function __construct(private readonly string $xml) {}
            public function fetch(string $url): string { return $this->xml; }
        };
    }

    private function makeFailingClient(): RssClientInterface
    {
        return new class implements RssClientInterface {
            public function fetch(string $url): string {
                throw new RssFetchException('network error');
            }
        };
    }

    private function makeHandler(RssClientInterface $client): ParseFeedHandler
    {
        $cacheVersion = new CacheVersion($this->mc);
        return new ParseFeedHandler(
            client:       $client,
            parser:       new RssParser(),
            categoryRepo: new CategoryRepository($this->pdo),
            newsRepo:     new NewsRepository($this->pdo),
            cacheVersion: $cacheVersion,
            logger:       new StderrLogger(),
            rssUrl:       'http://fake.rss',
        );
    }

    public function testInsertsNewsAndCategories(): void
    {
        $this->makeHandler($this->makeClient($this->sampleXml()))->run();

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM news')->fetchColumn();
        self::assertSame(3, $count);

        $cats = (int) $this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        self::assertGreaterThanOrEqual(3, $cats);
    }

    public function testIdempotentOnRepeat(): void
    {
        $client  = $this->makeClient($this->sampleXml());
        $handler = $this->makeHandler($client);

        $handler->run();
        $handler->run();

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM news')->fetchColumn();
        self::assertSame(3, $count);
    }

    public function testFetchFailureDoesNotInvalidateCache(): void
    {
        $cacheVersion = new CacheVersion($this->mc);
        $vBefore      = $cacheVersion->getNewsVersion();

        $handler = new ParseFeedHandler(
            client:       $this->makeFailingClient(),
            parser:       new RssParser(),
            categoryRepo: new CategoryRepository($this->pdo),
            newsRepo:     new NewsRepository($this->pdo),
            cacheVersion: $cacheVersion,
            logger:       new StderrLogger(),
            rssUrl:       'http://fake.rss',
        );
        $handler->run();

        $vAfter = $cacheVersion->getNewsVersion();
        self::assertSame($vBefore, $vAfter);
    }

    public function testSuccessInvalidatesCache(): void
    {
        $cacheVersion = new CacheVersion($this->mc);
        $vBefore      = $cacheVersion->getNewsVersion();

        $this->makeHandler($this->makeClient($this->sampleXml()))->run();

        $vAfter = $cacheVersion->getNewsVersion();
        self::assertGreaterThan($vBefore, $vAfter);
    }

    public function testBrokenItemDoesNotAbortRest(): void
    {
        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <item>
              <title></title>
              <link></link>
              <pubDate>Fri, 22 May 2026 10:00:00 +0000</pubDate>
            </item>
            <item>
              <title>Нормальная новость</title>
              <link>https://ria.ru/20260522/sport-99999.html</link>
              <pubDate>Fri, 22 May 2026 10:00:00 +0000</pubDate>
            </item>
          </channel>
        </rss>
        XML;

        $this->makeHandler($this->makeClient($xml))->run();

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM news')->fetchColumn();
        self::assertSame(1, $count);
    }
}
