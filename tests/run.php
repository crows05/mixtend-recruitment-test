<?php

declare(strict_types=1);

use App\Http\HttpTransportInterface;
use App\Logging\LoggerInterface;
use App\ScheduleClient;

require dirname(__DIR__) . '/src/bootstrap.php';

final class FakeTransport implements HttpTransportInterface
{
  public $headers = [];
  private $response;

  public function __construct(array $response)
  {
    $this->response = $response;
  }

  public function get(string $url, array $headers, int $timeout): array
  {
    $this->headers = $headers;
    return $this->response;
  }
}

final class MemoryLogger implements LoggerInterface
{
  public $entries = [];

  public function info(string $message, array $context = []): void
  {
    $this->entries[] = compact('message', 'context');
  }
}

function fixture(array $overrides = []): array
{
  return array_replace_recursive([
    'working_hours' => ['start' => '10:00', 'end' => '19:00'],
    'meetings' => [
      '2021-03-22' => [[
        'summary' => 'Meeting 1',
        'start' => '10:00',
        'end' => '11:00',
        'timezone' => 'Asia/Tokyo',
      ]],
    ],
  ], $overrides);
}

function expect(bool $condition, string $message): void
{
  if (!$condition) {
    throw new RuntimeException($message);
  }
}

function expectException(callable $callback, string $contains): void
{
  try {
    $callback();
  } catch (RuntimeException $exception) {
    expect(strpos($exception->getMessage(), $contains) !== false, 'Unexpected exception: ' . $exception->getMessage());
    return;
  }
  throw new RuntimeException('Expected an exception containing: ' . $contains);
}

$tests = [];

// 正常なレスポンスを取得し、User-AgentとAPIレスポンスのログが記録されることを確認する
$tests['fetches, logs and validates a schedule'] = function (): void {
  $body = json_encode(fixture());
  $transport = new FakeTransport(['status' => 200, 'body' => $body]);
  $logger = new MemoryLogger();
  $client = new ScheduleClient($transport, $logger, 'https://example.com/schedule.json');

  $result = $client->fetch();
  expect($result['working_hours']['start'] === '10:00', 'Schedule was not returned.');
  expect(in_array('User-Agent: Mixtend Coding Test', $transport->headers, true), 'User-Agent header is missing.');
  expect(count($logger->entries) === 1, 'API response was not logged.');
  expect($logger->entries[0]['context']['body'] === $body, 'Raw API body was not logged.');
};

// 外部APIがHTTP 200以外を返した場合に例外となることを確認する
$tests['rejects a non-200 response'] = function (): void {
  $client = new ScheduleClient(
    new FakeTransport(['status' => 503, 'body' => '{}']),
    new MemoryLogger(),
    'https://example.com/schedule.json'
  );
  expectException(function () use ($client): void { $client->fetch(); }, 'HTTP 503');
};

// 外部APIのレスポンスが不正なJSONの場合に例外となることを確認する
$tests['rejects invalid JSON'] = function (): void {
  $client = new ScheduleClient(
    new FakeTransport(['status' => 200, 'body' => '{invalid']),
    new MemoryLogger(),
    'https://example.com/schedule.json'
  );
  expectException(function () use ($client): void { $client->fetch(); }, 'invalid JSON');
};

// ミーティングの終了時刻が開始時刻以前の場合に例外となることを確認する
$tests['rejects invalid meeting data'] = function (): void {
  $data = fixture();
  $data['meetings']['2021-03-22'][0]['end'] = '09:00';
  $client = new ScheduleClient(
    new FakeTransport(['status' => 200, 'body' => json_encode($data)]),
    new MemoryLogger(),
    'https://example.com/schedule.json'
  );
  expectException(function () use ($client): void { $client->fetch(); }, 'Invalid meeting data');
};

$failed = 0;
foreach ($tests as $name => $test) {
  try {
    $test();
    echo "✓ {$name}\n";
  } catch (Throwable $exception) {
    $failed++;
    fwrite(STDERR, "✗ {$name}: {$exception->getMessage()}\n");
  }
}

echo sprintf("\n%d tests, %d failures\n", count($tests), $failed);
exit($failed === 0 ? 0 : 1);
