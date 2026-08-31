<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpTransportInterface;
use App\Logging\LoggerInterface;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/** 外部APIからスケジュールを取得し、ログ記録とデータ検証を行う */
final class ScheduleClient
{
  private $transport;
  private $logger;
  private $url;
  private $timeout;

  public function __construct(
    HttpTransportInterface $transport,
    LoggerInterface $logger,
    string $url,
    int $timeout = 10
  ) {
    if (filter_var($url, FILTER_VALIDATE_URL) === false || strpos($url, 'https://') !== 0) {
      throw new RuntimeException('The schedule API URL must be a valid HTTPS URL.');
    }

    $this->transport = $transport;
    $this->logger = $logger;
    $this->url = $url;
    $this->timeout = max(1, min(30, $timeout));
  }

  /** @return mixed[] */
  public function fetch(): array
  {
    $response = $this->transport->get(
      $this->url,
      ['Accept: application/json', 'User-Agent: Mixtend Coding Test'],
      $this->timeout
    );

    $this->logger->info('Schedule API response', [
      'url' => $this->url,
      'status' => $response['status'],
      'body' => $response['body'],
    ]);

    if ($response['status'] !== 200) {
      throw new RuntimeException('Schedule API returned HTTP ' . $response['status'] . '.');
    }

    $data = json_decode($response['body'], true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
      throw new RuntimeException('Schedule API returned invalid JSON.');
    }

    $this->validate($data);

    return $data;
  }

  /** @param mixed[] $data */
  private function validate(array $data): void
  {
    // フロントエンドへ一定形式のデータを返すための検証
    if (!isset($data['working_hours']) || !is_array($data['working_hours'])) {
      throw new RuntimeException('Missing working_hours in the schedule response.');
    }

    $start = $data['working_hours']['start'] ?? null;
    $end = $data['working_hours']['end'] ?? null;
    if (!$this->isTime($start) || !$this->isTime($end) || $this->toMinutes($start) >= $this->toMinutes($end)) {
      throw new RuntimeException('Invalid working hours in the schedule response.');
    }

    if (!isset($data['meetings']) || !is_array($data['meetings'])) {
      throw new RuntimeException('Missing meetings in the schedule response.');
    }

    foreach ($data['meetings'] as $date => $meetings) {
      if (!$this->isDate((string) $date) || !is_array($meetings)) {
        throw new RuntimeException('Invalid meeting date in the schedule response.');
      }

      foreach ($meetings as $meeting) {
        if (!is_array($meeting)) {
          throw new RuntimeException('Invalid meeting item in the schedule response.');
        }

        $summary = $meeting['summary'] ?? null;
        $meetingStart = $meeting['start'] ?? null;
        $meetingEnd = $meeting['end'] ?? null;
        $timezone = $meeting['timezone'] ?? null;
        if (!is_string($summary) || trim($summary) === '' ||
          !$this->isTime($meetingStart) || !$this->isTime($meetingEnd) ||
          $this->toMinutes($meetingStart) >= $this->toMinutes($meetingEnd) ||
          !is_string($timezone) || !$this->isTimezone($timezone)) {
          throw new RuntimeException('Invalid meeting data in the schedule response.');
        }
      }
    }
  }

  /** @param mixed $value */
  private function isTime($value): bool
  {
    return is_string($value) && preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $value) === 1;
  }

  private function isDate(string $value): bool
  {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
  }

  private function isTimezone(string $value): bool
  {
    try {
      new DateTimeZone($value);
      return true;
    } catch (\Exception $exception) {
      return false;
    }
  }

  private function toMinutes(string $time): int
  {
    list($hours, $minutes) = array_map('intval', explode(':', $time));
    return ($hours * 60) + $minutes;
  }
}
