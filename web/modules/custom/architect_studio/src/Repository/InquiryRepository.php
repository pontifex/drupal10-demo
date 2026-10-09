<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Repository;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;

/**
 * Repozytorium zapytań o adaptację projektów i zmiany budowlane.
 */
class InquiryRepository {

  public function __construct(
    protected Connection $database,
    protected TimeInterface $time,
  ) {}

  /**
   * Tworzy nowe zapytanie o adaptację lub modyfikacje projektu.
   *
   * @param array<string, mixed> $data
   *   Dane zapytania.
   *
   * @return int
   *   ID utworzonego zapytania.
   */
  public function create(array $data): int {
    if (empty($data['created'])) {
      $data['created'] = $this->time->getRequestTime();
    }
    if (empty($data['status'])) {
      $data['status'] = 'new';
    }

    return (int) $this->database->insert('architect_inquiries')
      ->fields($data)
      ->execute();
  }

  /**
   * Pobiera wszystkie zapytania.
   *
   * @return array<int, array<string, mixed>>
   *   Lista zapytań.
   */
  public function getAll(): array {
    return (array) $this->database->select('architect_inquiries', 'i')
      ->fields('i')
      ->orderBy('i.id', 'DESC')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Zmienia status zapytania.
   *
   * @param int $id
   *   Identyfikator zapytania.
   * @param string $status
   *   Nowy status zapytania.
   *
   * @return bool
   *   TRUE jeśli zaktualizowano, FALSE w przeciwnym razie.
   */
  public function updateStatus(int $id, string $status): bool {
    $affected = $this->database->update('architect_inquiries')
      ->fields(['status' => $status])
      ->condition('id', $id)
      ->execute();

    return $affected > 0;
  }

}
