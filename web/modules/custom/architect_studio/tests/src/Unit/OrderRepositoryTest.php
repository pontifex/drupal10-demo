<?php

declare(strict_types=1);

namespace Drupal\Tests\architect_studio\Unit;

use Drupal\architect_studio\Repository\OrderRepository;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Tests\UnitTestCase;

/**
 * Testy jednostkowe generowania numeracji zamówień.
 *
 * @group architect_studio
 */
class OrderRepositoryTest extends UnitTestCase {

  /**
   * Test formatu generowanego numeru zamówienia.
   */
  public function testGenerateOrderNumberFormat(): void {
    $database = $this->createMock(Connection::class);
    $time = $this->createMock(TimeInterface::class);
    // 2024-10-09
    $time->method('getRequestTime')->willReturn(1728460800);

    $repo = new OrderRepository($database, $time);
    $orderNumber = $repo->generateOrderNumber();

    // Sprawdzenie wzorca ARCH-YYYYMMDD-XXXXX.
    $this->assertMatchesRegularExpression('/^ARCH-\d{8}-[A-F0-9]{5}$/', $orderNumber);
  }

  /**
   * Test unikalności kolejnych numerów zamówień.
   */
  public function testGenerateOrderNumberUniqueness(): void {
    $database = $this->createMock(Connection::class);
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(time());

    $repo = new OrderRepository($database, $time);
    $num1 = $repo->generateOrderNumber();
    $num2 = $repo->generateOrderNumber();

    $this->assertNotSame($num1, $num2);
  }

}
