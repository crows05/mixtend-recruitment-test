<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Schedule Calendar</title>
  <link rel="stylesheet" href="/assets/css/app.css">
  <script src="/assets/js/app.js" defer></script>
</head>
<body>
  <main class="page">
    <p id="status" class="message" role="status">予定を読み込んでいます...</p>

    <div id="error" class="message message--error" hidden>
      <p id="error-message">予定を取得できませんでした。</p>
      <button id="retry" type="button">再読み込み</button>
    </div>

    <div id="calendar-scroll" hidden>
      <section id="calendar" class="calendar" aria-label="週間カレンダー"></section>
    </div>
  </main>
</body>
</html>
