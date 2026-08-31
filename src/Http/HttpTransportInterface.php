<?php

declare(strict_types=1);

namespace App\Http;

/** HTTP通信クラスのメソッドを定義するインターフェース */
interface HttpTransportInterface
{
  /**
  * @param string[] $headers
  * @return array{status:int, body:string}
  */
  public function get(string $url, array $headers, int $timeout): array;
}
