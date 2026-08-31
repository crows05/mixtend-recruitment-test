<?php

declare(strict_types=1);

namespace App\Logging;

use RuntimeException;

/** APIレスポンスの情報をローカルのログファイルへ追記 */
final class FileLogger implements LoggerInterface
{
  private $path;

  public function __construct(string $path)
  {
    $this->path = $path;
  }

  public function info(string $message, array $context = []): void
  {
    $directory = dirname($this->path);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
      throw new RuntimeException('Unable to create the log directory.');
    }

    $line = sprintf(
      "[%s] INFO %s %s\n",
      gmdate('c'),
      $message,
      json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );

    // 同時リクエスト時のログの混在を防ぐための排他制御
    if (file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX) === false) {
      throw new RuntimeException('Unable to write the log file.');
    }
  }
}
