CREATE DATABASE IF NOT EXISTS news_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON news_test.* TO 'news'@'%';

CREATE TABLE IF NOT EXISTS news_test.categories (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS news_test.news (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    url          VARCHAR(512) NOT NULL,
    title        VARCHAR(512) NOT NULL,
    description  TEXT NULL,
    category_id  INT UNSIGNED NOT NULL,
    image_url    VARCHAR(512) NULL,
    published_at DATETIME NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_url (url),
    KEY idx_cat_pub (category_id, published_at),
    KEY idx_pub (published_at),
    CONSTRAINT fk_news_test_cat FOREIGN KEY (category_id) REFERENCES news_test.categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
