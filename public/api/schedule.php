<?php

declare(strict_types=1);

use App\Http\CurlTransport;
use App\Logging\FileLogger;
use App\ScheduleClient;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// 外部APIのスケジュールを取得し、ログへ保存してからフロントエンドへ返却する
$projectRoot = dirname(__DIR__, 2);
$apiUrl = 'https://mixtend.github.io/schedule.json';
$logPath = $projectRoot . '/logs/schedule-api.log';
$timeout = 10;

try {
  $client = new ScheduleClient(new CurlTransport(), new FileLogger($logPath), $apiUrl, $timeout);
  echo json_encode($client->fetch(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
  // 外部APIの通信やデータ検証に失敗した場合は、詳細を公開せず固定メッセージを返す。
  http_response_code(502);
  echo json_encode(['error' => '予定を取得できませんでした。'], JSON_UNESCAPED_UNICODE);
}
