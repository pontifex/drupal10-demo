<?php

declare(strict_types=1);

namespace Drupal\Tests\architect_studio\Unit;

use Drupal\architect_studio\Service\PaymentGatewayService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\NullLogger;

/**
 * Testy jednostkowe serwisu bramek płatności i transakcji BLIK.
 *
 * @group architect_studio
 */
class PaymentGatewayServiceTest extends UnitTestCase {

  /**
   * Serwis płatności.
   *
   * @var \Drupal\architect_studio\Service\PaymentGatewayService
   */
  protected PaymentGatewayService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->service = new PaymentGatewayService(new NullLogger());
  }

  /**
   * Test walidacji poprawnych kodów BLIK.
   *
   * @dataProvider validBlikProvider
   */
  public function testValidBlikCodes(string $code): void {
    $this->assertTrue($this->service->validateBlikCode($code));
  }

  /**
   * Data provider dla poprawnych kodów BLIK.
   *
   * @return array<string, array<int, string>>
   *   Zestaw poprawnych kodów.
   */
  public function validBlikProvider(): array {
    return [
      'standard 6 digits' => ['123456'],
      'leading zeros' => ['012345'],
      'with space' => ['123 456'],
      'with dash' => ['123-456'],
      'with whitespace padding' => [' 789012 '],
    ];
  }

  /**
   * Test walidacji niepoprawnych kodów BLIK.
   *
   * @dataProvider invalidBlikProvider
   */
  public function testInvalidBlikCodes(string $code): void {
    $this->assertFalse($this->service->validateBlikCode($code));
  }

  /**
   * Data provider dla niepoprawnych kodów BLIK.
   *
   * @return array<string, array<int, string>>
   *   Zestaw niepoprawnych kodów.
   */
  public function invalidBlikProvider(): array {
    return [
      'empty' => [''],
      'too short (5 digits)' => ['12345'],
      'too long (7 digits)' => ['1234567'],
      'letters' => ['abcdef'],
      'alphanumeric' => ['12345a'],
      'special chars' => ['12#456'],
    ];
  }

  /**
   * Test pomyślnego przetworzenia płatności BLIK przez Przelewy24.
   */
  public function testProcessBlikPaymentSuccessP24(): void {
    $order = [
      'order_number' => 'ARCH-20261009-TEST1',
      'amount' => 3950.00,
      'currency' => 'PLN',
    ];

    $result = $this->service->processBlikPayment($order, '123456', PaymentGatewayService::GATEWAY_P24);

    $this->assertTrue($result['success']);
    $this->assertSame('paid', $result['status']);
    $this->assertStringStartsWith('TXN-P24-', $result['transaction_id']);
    $this->assertSame(PaymentGatewayService::GATEWAY_P24, $result['gateway']);
  }

  /**
   * Test pomyślnego przetworzenia płatności BLIK przez PayU.
   */
  public function testProcessBlikPaymentSuccessPayU(): void {
    $order = [
      'order_number' => 'ARCH-20261009-TEST2',
      'amount' => 3350.00,
      'currency' => 'PLN',
    ];

    $result = $this->service->processBlikPayment($order, '654321', PaymentGatewayService::GATEWAY_PAYU);

    $this->assertTrue($result['success']);
    $this->assertSame('paid', $result['status']);
    $this->assertStringStartsWith('TXN-PAYU-', $result['transaction_id']);
    $this->assertSame(PaymentGatewayService::GATEWAY_PAYU, $result['gateway']);
  }

  /**
   * Test pomyślnego przetworzenia płatności BLIK przez Autopay.
   */
  public function testProcessBlikPaymentSuccessAutopay(): void {
    $order = [
      'order_number' => 'ARCH-20261009-TEST3',
      'amount' => 4200.00,
      'currency' => 'PLN',
    ];

    $result = $this->service->processBlikPayment($order, '112233', PaymentGatewayService::GATEWAY_AUTOPAY);

    $this->assertTrue($result['success']);
    $this->assertSame('paid', $result['status']);
    $this->assertStringStartsWith('TXN-AUTO-', $result['transaction_id']);
  }

  /**
   * Test odrzucenia płatności przy nieprawidłowym formacie kodu BLIK.
   */
  public function testProcessBlikPaymentInvalidCodeFormat(): void {
    $order = [
      'order_number' => 'ARCH-20261009-FAIL1',
      'amount' => 1900.00,
      'currency' => 'PLN',
    ];

    $result = $this->service->processBlikPayment($order, '12345');

    $this->assertFalse($result['success']);
    $this->assertSame('rejected', $result['status']);
    $this->assertEmpty($result['transaction_id']);
  }

  /**
   * Test symulacji odrzucenia autoryzacji w aplikacji bankowej.
   */
  public function testProcessBlikPaymentBankRejectionSimulation(): void {
    $order = [
      'order_number' => 'ARCH-20261009-FAIL2',
      'amount' => 1900.00,
      'currency' => 'PLN',
    ];

    $result = $this->service->processBlikPayment($order, '777777');

    $this->assertFalse($result['success']);
    $this->assertSame('failed', $result['status']);
    $this->assertStringStartsWith('TXN-BLIK-REJECTED-', $result['transaction_id']);
  }

  /**
   * Test pobrania dostępnych bramek.
   */
  public function testGetAvailableGateways(): void {
    $gateways = $this->service->getAvailableGateways();
    $this->assertArrayHasKey(PaymentGatewayService::GATEWAY_P24, $gateways);
    $this->assertArrayHasKey(PaymentGatewayService::GATEWAY_PAYU, $gateways);
    $this->assertArrayHasKey(PaymentGatewayService::GATEWAY_AUTOPAY, $gateways);
  }

}
