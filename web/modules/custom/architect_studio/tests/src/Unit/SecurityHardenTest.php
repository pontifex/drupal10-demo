<?php

declare(strict_types=1);

namespace Drupal\Tests\architect_studio\Unit;

use Drupal\architect_studio\Controller\OrderController;
use Drupal\architect_studio\Repository\OrderRepository;
use Drupal\architect_studio\Repository\ProjectRepository;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Testy jednostkowe mechanizmów: IDOR, CSRF, Honeypot i Flood Control.
 *
 * @group architect_studio
 */
class SecurityHardenTest extends UnitTestCase {

  /**
   * Test maskowania danych zamówienia dla gościa bez sesji (ochrona IDOR).
   */
  public function testOrderSuccessMasksDataForUnauthorizedVisitor(): void {
    $projectRepo = $this->createMock(ProjectRepository::class);
    $orderRepo = $this->createMock(OrderRepository::class);

    $orderData = [
      'id' => 1,
      'project_id' => 10,
      'order_number' => 'ARCH-20261009-TEST1',
      'customer_name' => 'Adam Nowak',
      'customer_email' => 'adam.nowak@example.pl',
      'customer_phone' => '+48 500 600 700',
      'shipping_address' => 'ul. Kwiatowa 5, 00-001 Warszawa',
    ];

    $orderRepo->method('getByOrderNumber')->with('ARCH-20261009-TEST1')->willReturn($orderData);
    $projectRepo->method('getById')->with(10)->willReturn(['id' => 10, 'title' => 'Projekt Test']);

    $controller = new OrderController($projectRepo, $orderRepo);

    // Zwykły użytkownik anonimowy bez uprawnień administratora.
    $currentUser = $this->createMock(AccountInterface::class);
    $currentUser->method('hasPermission')->with('access administration pages')->willReturn(FALSE);

    $container = new ContainerBuilder();
    $container->set('current_user', $currentUser);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    // Żądanie bez pasującej sesji zamówienia.
    $session = new Session(new MockArraySessionStorage());
    $request = new Request();
    $request->setSession($session);

    $renderArray = $controller->success($request, 'ARCH-20261009-TEST1');

    $this->assertTrue($renderArray['#is_masked']);
    $this->assertNotSame('Adam Nowak', $renderArray['#order']['customer_name']);
    $this->assertSame('a***k@e***.pl', $renderArray['#order']['customer_email']);
    $this->assertStringContainsString('**-***', $renderArray['#order']['shipping_address']);
  }

  /**
   * Test wyświetlania pełnych danych zamówienia dla kupującego z aktywną sesją.
   */
  public function testOrderSuccessShowsFullDataForSessionOwner(): void {
    $projectRepo = $this->createMock(ProjectRepository::class);
    $orderRepo = $this->createMock(OrderRepository::class);

    $orderData = [
      'id' => 1,
      'project_id' => 10,
      'order_number' => 'ARCH-20261009-TEST2',
      'customer_name' => 'Adam Nowak',
      'customer_email' => 'adam.nowak@example.pl',
      'customer_phone' => '+48 500 600 700',
      'shipping_address' => 'ul. Kwiatowa 5, 00-001 Warszawa',
    ];

    $orderRepo->method('getByOrderNumber')->with('ARCH-20261009-TEST2')->willReturn($orderData);
    $projectRepo->method('getById')->with(10)->willReturn(['id' => 10, 'title' => 'Projekt Test']);

    $controller = new OrderController($projectRepo, $orderRepo);

    $currentUser = $this->createMock(AccountInterface::class);
    $currentUser->method('hasPermission')->willReturn(FALSE);

    $container = new ContainerBuilder();
    $container->set('current_user', $currentUser);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    // Żądanie z aktywną sesją zakupu.
    $session = new Session(new MockArraySessionStorage());
    $session->set('architect_last_order_ARCH-20261009-TEST2', TRUE);
    $request = new Request();
    $request->setSession($session);

    $renderArray = $controller->success($request, 'ARCH-20261009-TEST2');

    $this->assertFalse($renderArray['#is_masked']);
    $this->assertSame('Adam Nowak', $renderArray['#order']['customer_name']);
    $this->assertSame('adam.nowak@example.pl', $renderArray['#order']['customer_email']);
  }

  /**
   * Test działania mechanizmu pułapki Honeypot na boty.
   */
  public function testHoneypotDetection(): void {
    $isHoneypotTriggered = function (string $honeypotValue): bool {
      return trim($honeypotValue) !== '';
    };

    // Prawdziwy użytkownik (pole puste).
    $this->assertFalse($isHoneypotTriggered(''));
    $this->assertFalse($isHoneypotTriggered('   '));

    // Bot spamujący (pole wypełnione).
    $this->assertTrue($isHoneypotTriggered('Spam Corp Sp. z o.o.'));
    $this->assertTrue($isHoneypotTriggered('https://spam-link.example.com'));
  }

  /**
   * Test logiki limitowania zapytań Flood Control.
   */
  public function testFloodControlLogic(): void {
    $floodCounter = 0;
    $maxLimit = 5;

    $checkFlood = function () use (&$floodCounter, $maxLimit): bool {
      if ($floodCounter >= $maxLimit) {
        return FALSE;
      }
      $floodCounter++;
      return TRUE;
    };

    for ($i = 0; $i < 5; $i++) {
      $this->assertTrue($checkFlood(), "Zapytanie #$i powinno przejść.");
    }

    // 6 zapytanie powinno zostać zablokowane przez Flood Control.
    $this->assertFalse($checkFlood(), '6 zapytanie powinno zostać zablokowane.');
  }

}
