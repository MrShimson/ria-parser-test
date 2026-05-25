<?php /** @var \App\Dto\NewsRow $item */ ?>
<div class="col">
    <a href="<?= htmlspecialchars($item->url, ENT_QUOTES) ?>"
       target="_blank"
       rel="noopener noreferrer"
       class="card h-100 news-card shadow-sm text-decoration-none text-reset">
        <?php if ($item->imageUrl !== null): ?>
            <img src="<?= htmlspecialchars($item->imageUrl, ENT_QUOTES) ?>" alt="" loading="lazy">
        <?php else: ?>
            <div class="news-card__placeholder" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                     fill="currentColor" viewBox="0 0 16 16">
                    <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/>
                    <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1z"/>
                </svg>
            </div>
        <?php endif; ?>
        <div class="card-body d-flex flex-column overflow-hidden">
            <span class="badge bg-secondary badge-slug mb-2 align-self-start"
                  title="<?= htmlspecialchars($item->slug, ENT_QUOTES) ?>">
                <?= htmlspecialchars($item->slug, ENT_QUOTES) ?>
            </span>
            <p class="card-title fw-semibold flex-grow-1">
                <?= htmlspecialchars($item->title, ENT_QUOTES) ?>
            </p>
            <?php if ($item->description !== null): ?>
                <p class="card-text text-muted small">
                    <?= htmlspecialchars(mb_strimwidth($item->description, 0, 160, '…'), ENT_QUOTES) ?>
                </p>
            <?php endif; ?>
            <p class="card-text mt-auto">
                <small class="text-muted">
                    <?= htmlspecialchars(
                        $item->publishedAtUtc
                            ->setTimezone(new DateTimeZone('Europe/Moscow'))
                            ->format('d.m.Y H:i'),
                        ENT_QUOTES
                    ) ?> МСК
                </small>
            </p>
        </div>
    </a>
</div>
