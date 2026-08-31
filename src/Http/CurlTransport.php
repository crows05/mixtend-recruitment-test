<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

/** HTTPSのGETリクエストを送信する */
final class CurlTransport implements HttpTransportInterface
{
  public function get(string $url, array $headers, int $timeout): array
  {
    $handle = curl_init($url);
    if ($handle === false) {
      throw new RuntimeException('Failed to initialize the HTTP client.');
    }

    curl_setopt_array($handle, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS => 3,
      CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
      CURLOPT_TIMEOUT => $timeout,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
      CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
    ]);

    $body = curl_exec($handle);
    if ($body === false) {
      $message = curl_error($handle);
      throw new RuntimeException('Schedule API request failed: ' . $message);
    }

    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);

    return ['status' => $status, 'body' => (string) $body];
  }
}
