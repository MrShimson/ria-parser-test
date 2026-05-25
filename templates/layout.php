<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Новостной агрегатор — РИА</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body { background: #f8f9fa; }
        a.news-card {
            transition: transform .12s ease, box-shadow .12s ease;
        }
        a.news-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .12) !important;
        }
        a.news-card:hover .card-title {
            color: var(--bs-primary);
        }
        .news-card img,
        .news-card .news-card__placeholder {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .news-card .news-card__placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e9ecef 0%, #ced4da 100%);
            color: #6c757d;
        }
        .news-card .card-title { font-size: .95rem; }
        .badge-slug {
            font-size: .75rem;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="/">РИА Агрегатор</a>
    </div>
</nav>

<div class="container">
    <?= $content ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
<script src="/assets/app.js"></script>
</body>
</html>
