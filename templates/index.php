<?php
/**
 * @var \App\Dto\NewsPage    $newsPage
 * @var array<int, string>  $categories   id => slug
 * @var \App\Dto\NewsFilters $filters
 * @var \DateTimeZone        $tz           MSK
 */

$msk          = new DateTimeZone('Europe/Moscow');
$currentCat   = $filters->categoryId;
$fromMsk      = $filters->fromUtc->setTimezone($msk)->format('Y-m-d');
$toMsk        = $filters->toUtc->setTimezone($msk)->format('Y-m-d');
$totalPages   = $newsPage->totalPages();
$currentPage  = $newsPage->page;

function qurl(array $override): string
{
    $params = array_merge($_GET, $override);
    return '?' . http_build_query(array_filter($params, static fn($v) => $v !== null && $v !== ''));
}

ob_start();
?>
<!-- Фильтры -->
<form method="get" class="row g-2 mb-4 align-items-end">
    <div class="col-sm-3">
        <label for="filter-category" class="form-label small fw-semibold">Категория</label>
        <select name="category" id="filter-category" class="form-select form-select-sm">
            <option value="">Все категории</option>
            <?php foreach ($categories as $id => $slug): ?>
                <option value="<?= $id ?>" <?= $currentCat === $id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($slug, ENT_QUOTES) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-3">
        <label for="date-from" class="form-label small fw-semibold">С</label>
        <input type="text" name="from" id="date-from" class="form-control form-control-sm"
               value="<?= htmlspecialchars($fromMsk, ENT_QUOTES) ?>" autocomplete="off">
    </div>
    <div class="col-sm-3">
        <label for="date-to" class="form-label small fw-semibold">По</label>
        <input type="text" name="to" id="date-to" class="form-control form-control-sm"
               value="<?= htmlspecialchars($toMsk, ENT_QUOTES) ?>" autocomplete="off">
    </div>
    <div class="col-sm-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm">Применить</button>
        <a href="/" class="btn btn-outline-secondary btn-sm">Сбросить</a>
    </div>
</form>

<!-- Счётчик -->
<p class="text-muted small mb-3">
    Найдено: <strong><?= $newsPage->total ?></strong>
    <?php if ($totalPages > 1): ?>
        &nbsp;· Страница <?= $currentPage ?> из <?= $totalPages ?>
    <?php endif; ?>
</p>

<!-- Карточки -->
<?php if ($newsPage->items === []): ?>
    <div class="alert alert-info">Новостей за выбранный период не найдено.</div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 mb-4">
        <?php foreach ($newsPage->items as $item): ?>
            <?php require __DIR__ . '/partials/_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Пагинация -->
<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination pagination-sm justify-content-center flex-wrap">
        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= qurl(['page' => $currentPage - 1]) ?>">&#8249;</a>
        </li>
        <?php
        $start = max(1, $currentPage - 2);
        $end   = min($totalPages, $currentPage + 2);
        if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= qurl(['page' => 1]) ?>">1</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <?php endif; ?>
        <?php for ($p = $start; $p <= $end; $p++): ?>
            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="<?= qurl(['page' => $p]) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?= qurl(['page' => $totalPages]) ?>"><?= $totalPages ?></a></li>
        <?php endif; ?>
        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= qurl(['page' => $currentPage + 1]) ?>">&#8250;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
