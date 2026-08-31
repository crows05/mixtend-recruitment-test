<?php

declare(strict_types=1);

// Composerを使わずにApp名前空間のクラスを読み込む
spl_autoload_register(function (string $class): void {
  $prefix = 'App\\';
  if (strpos($class, $prefix) !== 0) {
    return;
  }

  $relativeClass = substr($class, strlen($prefix));
  $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';

  if (is_file($file)) {
    require $file;
  }
});
