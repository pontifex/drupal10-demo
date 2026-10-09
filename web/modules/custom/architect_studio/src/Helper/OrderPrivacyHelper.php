<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Helper;

/**
 * Pomocnik do ochrony danych osobowych i maskowania wrażliwych pól zamówienia.
 */
final class OrderPrivacyHelper {

  /**
   * Maskuje imię i nazwisko (np. Jan Kowalski -> J** K*******).
   */
  public static function maskName(string $name): string {
    $trimmed = trim($name);
    if ($trimmed === '') {
      return '';
    }

    $words = preg_split('/\s+/u', $trimmed) ?: [];
    $maskedWords = [];

    foreach ($words as $word) {
      $length = mb_strlen($word);
      if ($length <= 1) {
        $maskedWords[] = $word;
      }
      elseif ($length === 2) {
        $maskedWords[] = mb_substr($word, 0, 1) . '*';
      }
      else {
        $maskedWords[] = mb_substr($word, 0, 1) . str_repeat('*', $length - 1);
      }
    }

    return implode(' ', $maskedWords);
  }

  /**
   * Maskuje adres e-mail (np. jan.kowalski@example.pl -> j***@e***.pl).
   */
  public static function maskEmail(string $email): string {
    $trimmed = trim($email);
    if (!str_contains($trimmed, '@')) {
      return '***@***.***';
    }

    [$local, $domain] = explode('@', $trimmed, 2);

    $localLength = mb_strlen($local);
    $maskedLocal = ($localLength <= 2)
      ? mb_substr($local, 0, 1) . '***'
      : mb_substr($local, 0, 1) . '***' . mb_substr($local, -1, 1);

    $domainParts = explode('.', $domain);
    $maskedDomainParts = [];

    foreach ($domainParts as $index => $part) {
      if ($index === count($domainParts) - 1) {
        // TLD pozostawiamy widoczne (np. pl, com).
        $maskedDomainParts[] = $part;
      }
      else {
        $maskedDomainParts[] = mb_substr($part, 0, 1) . '***';
      }
    }

    return $maskedLocal . '@' . implode('.', $maskedDomainParts);
  }

  /**
   * Maskuje numer telefonu (np. +48 600 123 456 -> +48 *** *** 456).
   */
  public static function maskPhone(string $phone): string {
    $clean = trim($phone);
    if ($clean === '') {
      return '';
    }

    $digits = preg_replace('/\D/', '', $clean) ?? '';
    if (strlen($digits) < 4) {
      return '*** *** ***';
    }

    $hasPlus = str_starts_with($clean, '+');
    $lastThree = substr($digits, -3);

    return ($hasPlus ? '+48 ' : '') . '*** *** ' . $lastThree;
  }

  /**
   * Maskuje adres pocztowy (np. kod pocztowy, nazwy ulic i miejscowości).
   */
  public static function maskAddress(string $address): string {
    $trimmed = trim($address);
    if ($trimmed === '') {
      return '';
    }

    // Zamiana kodów pocztowych XX-XXX na **-***.
    $masked = preg_replace('/\b\d{2}-\d{3}\b/', '**-***', $trimmed) ?? $trimmed;

    // Maskowanie poszczególnych słów o długości > 3 liter.
    $words = preg_split('/(\s+|,)/u', $masked, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
    $result = '';

    foreach ($words as $token) {
      if (preg_match('/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{4,}$/u', $token)) {
        $length = mb_strlen($token);
        $result .= mb_substr($token, 0, 1) . str_repeat('*', $length - 1);
      }
      else {
        $result .= $token;
      }
    }

    return $result;
  }

  /**
   * Maskuje NIP (np. 1234567890 -> ******7890).
   */
  public static function maskNip(string $nip): string {
    $clean = preg_replace('/\D/', '', $nip) ?? '';
    if (strlen($clean) <= 4) {
      return '******';
    }

    return str_repeat('*', strlen($clean) - 4) . substr($clean, -4);
  }

  /**
   * Zwraca kopię zamówienia z zamaskowanymi danymi wrażliwymi.
   *
   * @param array<string, mixed> $order
   *   Oryginalna tablica danych zamówienia.
   *
   * @return array<string, mixed>
   *   Tablica z zanonimizowanymi polami osobowymi.
   */
  public static function maskOrder(array $order): array {
    $masked = $order;

    if (!empty($masked['customer_name']) && is_string($masked['customer_name'])) {
      $masked['customer_name'] = self::maskName($masked['customer_name']);
    }

    if (!empty($masked['customer_email']) && is_string($masked['customer_email'])) {
      $masked['customer_email'] = self::maskEmail($masked['customer_email']);
    }

    if (!empty($masked['customer_phone']) && is_string($masked['customer_phone'])) {
      $masked['customer_phone'] = self::maskPhone($masked['customer_phone']);
    }

    if (!empty($masked['shipping_address']) && is_string($masked['shipping_address'])) {
      $masked['shipping_address'] = self::maskAddress($masked['shipping_address']);
    }

    if (!empty($masked['invoice_nip']) && is_string($masked['invoice_nip'])) {
      $masked['invoice_nip'] = self::maskNip($masked['invoice_nip']);
    }

    return $masked;
  }

}
