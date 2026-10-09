<?php

declare(strict_types=1);

namespace Drupal\Tests\architect_studio\Unit;

use Drupal\architect_studio\Service\MailNotificationService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Testy jednostkowe dla serwisu powiadomień e-mail MailNotificationService.
 *
 * @coversDefaultClass \Drupal\architect_studio\Service\MailNotificationService
 * @group architect_studio
 */
class MailNotificationServiceTest extends UnitTestCase {

  /**
   * Mock managera poczty Drupala.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected MailManagerInterface $mailManager;

  /**
   * Mock fabryki konfiguracji.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mock konfiguracji strony system.site.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ImmutableConfig $siteConfig;

  /**
   * Testowany serwis powiadomień.
   */
  protected MailNotificationService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->siteConfig = $this->createMock(ImmutableConfig::class);

    $this->configFactory->method('get')
      ->with('system.site')
      ->willReturn($this->siteConfig);

    $this->siteConfig->method('get')
      ->with('mail')
      ->willReturn('architekt@pracownia-krasnik.pl');

    $this->service = new MailNotificationService(
      $this->mailManager,
      $this->configFactory
    );
  }

  /**
   * Test pobierania skonfigurowanego adresu e-mail pracowni.
   */
  public function testGetArchitectEmail(): void {
    $this->assertSame('architekt@pracownia-krasnik.pl', $this->service->getArchitectEmail());
  }

  /**
   * Test wysyłania powiadomienia o zapytaniu do architekta.
   */
  public function testNotifyArchitectInquiry(): void {
    $inquiry = [
      'customer_name' => 'Anna Nowak',
      'customer_email' => 'anna@example.com',
      'customer_phone' => '500111222',
      'inquiry_type' => 'adaptacja',
      'plot_location' => 'Kraśnik, działka 123/4',
      'project_link' => 'https://example.com/projekt',
      'message' => 'Proszę o wycenę adaptacji',
    ];

    $this->mailManager->expects($this->once())
      ->method('mail')
      ->with(
        'architect_studio',
        'inquiry_notify_architect',
        'architekt@pracownia-krasnik.pl',
        'pl',
        ['inquiry' => $inquiry],
        NULL,
        TRUE
      )
      ->willReturn(['result' => TRUE]);

    $this->assertTrue($this->service->notifyArchitectInquiry($inquiry));
  }

  /**
   * Test wysyłania potwierdzenia zapytania do klienta.
   */
  public function testConfirmCustomerInquiry(): void {
    $inquiry = [
      'customer_name' => 'Anna Nowak',
      'customer_email' => 'anna@example.com',
    ];

    $this->mailManager->expects($this->once())
      ->method('mail')
      ->with(
        'architect_studio',
        'inquiry_confirm_customer',
        'anna@example.com',
        'pl',
        ['inquiry' => $inquiry],
        NULL,
        TRUE
      )
      ->willReturn(['result' => TRUE]);

    $this->assertTrue($this->service->confirmCustomerInquiry($inquiry));
  }

  /**
   * Test wysyłania potwierdzenia zapytania bez adresu e-mail klienta.
   */
  public function testConfirmCustomerInquiryEmptyEmail(): void {
    $this->mailManager->expects($this->never())->method('mail');
    $this->assertFalse($this->service->confirmCustomerInquiry(['customer_name' => 'Anonim']));
  }

  /**
   * Test wysyłania powiadomienia o zamówieniu do architekta.
   */
  public function testNotifyArchitectOrder(): void {
    $order = [
      'order_number' => 'ARCH-202610-1234',
      'project_title' => 'Willa Optima',
      'amount' => 3800.00,
    ];
    $project = ['id' => 1, 'title' => 'Willa Optima'];

    $this->mailManager->expects($this->once())
      ->method('mail')
      ->with(
        'architect_studio',
        'order_notify_architect',
        'architekt@pracownia-krasnik.pl',
        'pl',
        ['order' => $order, 'project' => $project],
        NULL,
        TRUE
      )
      ->willReturn(['result' => TRUE]);

    $this->assertTrue($this->service->notifyArchitectOrder($order, $project));
  }

  /**
   * Test wysyłania potwierdzenia zamówienia do klienta.
   */
  public function testConfirmCustomerOrder(): void {
    $order = [
      'order_number' => 'ARCH-202610-1234',
      'customer_email' => 'klient@example.com',
      'amount' => 3800.00,
    ];

    $this->mailManager->expects($this->once())
      ->method('mail')
      ->with(
        'architect_studio',
        'order_confirm_customer',
        'klient@example.com',
        'pl',
        ['order' => $order, 'project' => NULL],
        NULL,
        TRUE
      )
      ->willReturn(['result' => TRUE]);

    $this->assertTrue($this->service->confirmCustomerOrder($order));
  }

}
