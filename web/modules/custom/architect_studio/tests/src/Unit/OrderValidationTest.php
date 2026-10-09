<?php

declare(strict_types=1);

namespace Drupal\Tests\architect_studio\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Testy jednostkowe reguł biznesowych zamówień projektów.
 *
 * @group architect_studio
 */
class OrderValidationTest extends UnitTestCase {

  /**
   * Test wymagania adresu wysyłki dla wersji drukowanej.
   */
  public function testPrintVariantRequiresShippingAddress(): void {
    $validateOrder = function (string $variant, string $shippingAddress): bool {
      if ($variant === 'print' && empty(trim($shippingAddress))) {
        return FALSE;
      }
      return TRUE;
    };

    $this->assertFalse($validateOrder('print', ''));
    $this->assertFalse($validateOrder('print', '   '));
    $this->assertTrue($validateOrder('print', 'ul. Warszawska 1, 00-001 Warszawa'));
    $this->assertTrue($validateOrder('digital', ''));
  }

  /**
   * Test kalkulacji kwoty zamówienia na podstawie wariantu projektu.
   */
  public function testOrderAmountCalculation(): void {
    $project = [
      'price_digital' => 3350.00,
      'price_print' => 3950.00,
    ];

    $calculateAmount = function (array $proj, string $variant): float {
      return ($variant === 'print') ? (float) $proj['price_print'] : (float) $proj['price_digital'];
    };

    $this->assertSame(3950.00, $calculateAmount($project, 'print'));
    $this->assertSame(3350.00, $calculateAmount($project, 'digital'));
  }

  /**
   * Test wymogu akceptacji regulaminu i polityki prywatności przy zamówieniu.
   */
  public function testTermsAcceptanceRequirement(): void {
    $validateConsent = function (mixed $termsAccepted): bool {
      return !empty($termsAccepted);
    };

    $this->assertFalse($validateConsent(0));
    $this->assertFalse($validateConsent(NULL));
    $this->assertFalse($validateConsent(''));
    $this->assertTrue($validateConsent(1));
    $this->assertTrue($validateConsent('1'));
  }

}
