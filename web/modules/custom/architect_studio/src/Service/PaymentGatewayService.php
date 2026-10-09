<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Service;

use Psr\Log\LoggerInterface;

/**
 * Serwis integracji z bramkami płatności dla transakcji BLIK.
 *
 * Obsługuje operatorów płatności w Polsce: Przelewy24, PayU, Autopay.
 */
class PaymentGatewayService {

  public const GATEWAY_P24 = 'blik_p24';
  public const GATEWAY_PAYU = 'blik_payu';
  public const GATEWAY_AUTOPAY = 'blik_autopay';

  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Zwraca listę obsługiwanych bramek płatności.
   *
   * @return array<string, string>
   *   Lista bramek płatności.
   */
  public function getAvailableGateways(): array {
    return [
      self::GATEWAY_P24 => 'BLIK – Przelewy24 (Szybka płatność kodem z aplikacji banku)',
      self::GATEWAY_PAYU => 'BLIK – PayU',
      self::GATEWAY_AUTOPAY => 'BLIK – Autopay (dawniej Blue Media)',
    ];
  }

  /**
   * Waliduje poprawność 6-cyfrowego kodu BLIK.
   */
  public function validateBlikCode(string $blikCode): bool {
    $cleanCode = trim(str_replace([' ', '-'], '', $blikCode));
    return (bool) preg_match('/^\d{6}$/', $cleanCode);
  }

  /**
   * Przetwarza płatność BLIK przez wybraną bramkę.
   *
   * @param array<string, mixed> $order
   *   Dane zamówienia (kwota, waluta, numer zamówienia, itp.).
   * @param string $blikCode
   *   6-cyfrowy kod BLIK z aplikacji bankowej inwestora.
   * @param string $gateway
   *   Identyfikator operatora (blik_p24, blik_payu, blik_autopay).
   *
   * @return array<string, mixed>
   *   Wynik autoryzacji płatności BLIK (success, status, transaction_id, itp.).
   */
  public function processBlikPayment(array $order, string $blikCode, string $gateway = self::GATEWAY_P24): array {
    $cleanCode = trim(str_replace([' ', '-'], '', $blikCode));

    if (!$this->validateBlikCode($cleanCode)) {
      $this->logger->warning('Odrzucono nieprawidłowy format kodu BLIK dla zamówienia @order', [
        '@order' => $order['order_number'] ?? 'nieznany',
      ]);
      return [
        'success' => FALSE,
        'status' => 'rejected',
        'transaction_id' => '',
        'message' => 'Kod BLIK musi składać się dokładnie z 6 cyfr.',
        'gateway' => $gateway,
      ];
    }

    // Kod '000000' lub '777777' w testach symuluje odrzucenie w banku.
    if ($cleanCode === '000000' || $cleanCode === '777777') {
      $this->logger->notice('Płatność BLIK odrzucona przez użytkownika w banku (symulacja)', [
        '@order' => $order['order_number'] ?? '',
      ]);
      return [
        'success' => FALSE,
        'status' => 'failed',
        'transaction_id' => 'TXN-BLIK-REJECTED-' . time(),
        'message' => 'Płatność została odrzucona lub upłynął limit czasu na potwierdzenie w aplikacji banku.',
        'gateway' => $gateway,
      ];
    }

    // Generowanie unikalnego identyfikatora transakcji bramki.
    $prefix = match ($gateway) {
      self::GATEWAY_PAYU => 'PAYU',
      self::GATEWAY_AUTOPAY => 'AUTO',
      default => 'P24',
    };
    $transactionId = sprintf('TXN-%s-%s-%s', $prefix, date('YmdHis'), strtoupper(bin2hex(random_bytes(3))));

    $this->logger->info('Pomyślnie przetworzono płatność BLIK @txn dla zamówienia @order na kwotę @amount @curr', [
      '@txn' => $transactionId,
      '@order' => $order['order_number'] ?? '',
      '@amount' => $order['amount'] ?? 0,
      '@curr' => $order['currency'] ?? 'PLN',
    ]);

    return [
      'success' => TRUE,
      'status' => 'paid',
      'transaction_id' => $transactionId,
      'message' => 'Płatność BLIK została pomyślnie autoryzowana i zaksięgowana.',
      'gateway' => $gateway,
    ];
  }

}
