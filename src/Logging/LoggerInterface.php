<?php

declare(strict_types=1);

namespace App\Logging;

/** ログ出力クラスのメソッドを定義するインターフェース */
interface LoggerInterface
{
  /** @param mixed[] $context */
  public function info(string $message, array $context = []): void;
}
