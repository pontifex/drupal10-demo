<?php

declare(strict_types=1);

namespace Drupal\Tests\architect_studio\Unit;

use Drupal\architect_studio\Helper\OrderPrivacyHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Testy jednostkowe dla pomocnika ochrony danych OrderPrivacyHelper.
 *
 * @group architect_studio
 */
class OrderPrivacyHelperTest extends UnitTestCase {

  /**
   * Test maskowania imienia i nazwiska.
   */
  public function testMaskName(): void {
    $this->assertSame('', OrderPrivacyHelper::maskName(''));
    $this->assertSame('J** K*******', OrderPrivacyHelper::maskName('Jan Kowalski'));
    $this->assertSame('A*** M**** N****', OrderPrivacyHelper::maskName('Anna Maria Nowak'));
  }

  /**
   * Test maskowania adresu e-mail.
   */
  public function testMaskEmail(): void {
    $masked = OrderPrivacyHelper::maskEmail('jan.kowalski@example.pl');
    $this->assertStringStartsWith('j***i@', $masked);
    $this->assertStringEndsWith('.pl', $masked);
    $this->assertStringNotContainsString('kowalski', $masked);

    // Krótki e-mail.
    $maskedShort = OrderPrivacyHelper::maskEmail('jk@test.com');
    $this->assertStringStartsWith('j***@', $maskedShort);
    $this->assertStringEndsWith('.com', $maskedShort);
  }

  /**
   * Test maskowania numeru telefonu.
   */
  public function testMaskPhone(): void {
    $masked = OrderPrivacyHelper::maskPhone('+48 600 123 456');
    $this->assertSame('+48 *** *** 456', $masked);

    $maskedShort = OrderPrivacyHelper::maskPhone('600123456');
    $this->assertSame('*** *** 456', $maskedShort);

    $this->assertSame('', OrderPrivacyHelper::maskPhone(''));
  }

  /**
   * Test maskowania adresu do doręczeń.
   */
  public function testMaskAddress(): void {
    $address = 'ul. Polna 15, 05-500 Piaseczno';
    $masked = OrderPrivacyHelper::maskAddress($address);

    $this->assertStringContainsString('**-***', $masked);
    $this->assertStringNotContainsString('05-500', $masked);
    $this->assertStringNotContainsString('Piaseczno', $masked);
    $this->assertStringContainsString('15', $masked);
  }

  /**
   * Test maskowania numeru NIP.
   */
  public function testMaskNip(): void {
    $this->assertSame('******7890', OrderPrivacyHelper::maskNip('1234567890'));
    $this->assertSame('******', OrderPrivacyHelper::maskNip('123'));
  }

  /**
   * Test maskowania pełnej tablicy zamówienia.
   */
  public function testMaskOrder(): void {
    $order = [
      'order_number' => 'ARCH-20261009-A1B2C',
      'project_title' => 'Projekt Gloria',
      'amount' => 4290.00,
      'currency' => 'PLN',
      'customer_name' => 'Jan Kowalski',
      'customer_email' => 'jan.kowalski@example.pl',
      'customer_phone' => '+48 600 123 456',
      'shipping_address' => 'ul. Polna 15, 05-500 Piaseczno',
      'invoice_nip' => '5250001234',
    ];

    $masked = OrderPrivacyHelper::maskOrder($order);

    $this->assertSame('ARCH-20261009-A1B2C', $masked['order_number']);
    $this->assertSame(4290.00, $masked['amount']);
    $this->assertSame('J** K*******', $masked['customer_name']);
    $this->assertNotSame($order['customer_email'], $masked['customer_email']);
    $this->assertSame('+48 *** *** 456', $masked['customer_phone']);
    $this->assertStringContainsString('**-***', $masked['shipping_address']);
    $this->assertSame('******1234', $masked['invoice_nip']);
  }

}
