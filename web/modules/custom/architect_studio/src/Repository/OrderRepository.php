<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Repository;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;

/**
 * Repozytorium zamówień projektów architektonicznych.
 */
class OrderRepository {

  public function __construct(
    protected Connection $database,
    protected TimeInterface $time,
  ) {}

  /**
   * Tworzy nowe zamówienie w bazie danych.
   *
   * @param array<string, mixed> $data
   *   Dane nowego zamówienia.
   *
   * @return int
   *   ID utworzonego zamówienia.
   */
  public function create(array $data): int {
    if (empty($data['created'])) {
      $data['created'] = $this->time->getRequestTime();
    }
    if (empty($data['order_number'])) {
      $data['order_number'] = $this->generateOrderNumber();
    }
    if (empty($data['currency'])) {
      $data['currency'] = 'PLN';
    }
    if (empty($data['payment_status'])) {
      $data['payment_status'] = 'pending';
    }

    return (int) $this->database->insert('architect_orders')
      ->fields($data)
      ->execute();
  }

  /**
   * Pobiera zamówienie po ID.
   *
   * @param int $id
   *   Identyfikator zamówienia.
   *
   * @return array<string, mixed>|null
   *   Dane zamówienia lub NULL.
   */
  public function getById(int $id): ?array {
    $result = $this->database->select('architect_orders', 'o')
      ->fields('o')
      ->condition('o.id', $id)
      ->execute()
      ->fetchAssoc();

    return $result ?: NULL;
  }

  /**
   * Pobiera zamówienie po unikalnym numerze zamówienia.
   *
   * @param string $orderNumber
   *   Unikalny numer zamówienia.
   *
   * @return array<string, mixed>|null
   *   Dane zamówienia lub NULL.
   */
  public function getByOrderNumber(string $orderNumber): ?array {
    $result = $this->database->select('architect_orders', 'o')
      ->fields('o')
      ->condition('o.order_number', $orderNumber)
      ->execute()
      ->fetchAssoc();

    return $result ?: NULL;
  }

  /**
   * Aktualizuje status płatności zamówienia.
   *
   * @param int $id
   *   Identyfikator zamówienia.
   * @param string $status
   *   Nowy status płatności.
   * @param string|null $transactionId
   *   Identyfikator transakcji z bramki płatności.
   *
   * @return bool
   *   TRUE jeśli zaktualizowano, FALSE w przeciwnym razie.
   */
  public function updatePaymentStatus(int $id, string $status, ?string $transactionId = NULL): bool {
    $fields = ['payment_status' => $status];
    if ($transactionId !== NULL) {
      $fields['transaction_id'] = $transactionId;
    }

    $affected = $this->database->update('architect_orders')
      ->fields($fields)
      ->condition('id', $id)
      ->execute();

    return $affected > 0;
  }

  /**
   * Pobiera wszystkie zamówienia (dla panelu architekta).
   *
   * @return array<int, array<string, mixed>>
   *   Lista zamówień.
   */
  public function getAll(): array {
    return (array) $this->database->select('architect_orders', 'o')
      ->fields('o')
      ->orderBy('o.id', 'DESC')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Generuje unikalny numer zamówienia.
   */
  public function generateOrderNumber(): string {
    $datePart = date('Ymd', $this->time->getRequestTime());
    $randomPart = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    return sprintf('ARCH-%s-%s', $datePart, $randomPart);
  }

}
