<?php

declare(strict_types=1);

namespace Drupal\Tests\architect_studio\Unit;

use Drupal\architect_studio\Controller\AdminController;
use Drupal\architect_studio\Repository\InquiryRepository;
use Drupal\architect_studio\Repository\OrderRepository;
use Drupal\architect_studio\Repository\ProjectRepository;
use Drupal\Tests\UnitTestCase;

/**
 * Testy jednostkowe renderowania tabel panelu administracyjnego.
 *
 * @group architect_studio
 */
class AdminControllerTablesTest extends UnitTestCase {

  /**
   * Test poprawnego formatowania komórek tabeli zamówień (struktura #markup).
   */
  public function testOrdersTableCellsUseMarkupRenderArray(): void {
    $projectRepo = $this->createMock(ProjectRepository::class);
    $inquiryRepo = $this->createMock(InquiryRepository::class);
    $orderRepo = $this->createMock(OrderRepository::class);

    $orderRepo->method('getAll')->willReturn([
      [
        'id' => 1,
        'order_number' => 'ARCH-20261009-TEST1',
        'project_title' => 'Projekt Testowy 120',
        'variant' => 'print',
        'amount' => 3500.0,
        'customer_name' => 'Jan Kowalski',
        'customer_email' => 'jan@example.com',
        'customer_phone' => '+48 111 222 333',
        'payment_method' => 'blik_p24',
        'transaction_id' => 'TXN-12345',
        'payment_status' => 'paid',
        'created' => 1791530000,
      ],
    ]);

    $controller = new AdminController($projectRepo, $orderRepo, $inquiryRepo);
    $controller->setStringTranslation($this->getStringTranslationStub());

    $build = $controller->orders();

    $this->assertArrayHasKey('table', $build);
    $this->assertSame('table', $build['table']['#type']);
    $this->assertNotEmpty($build['table']['#rows']);

    $firstRow = $build['table']['#rows'][0];

    // Numer zamówienia musi być tablicą renderującą #markup.
    $this->assertIsArray($firstRow[0]);
    $this->assertArrayHasKey('data', $firstRow[0]);
    $this->assertArrayHasKey('#markup', $firstRow[0]['data']);
    $this->assertStringContainsString('<strong>ARCH-20261009-TEST1</strong>', $firstRow[0]['data']['#markup']);

    // Tytuł i wariant to zwykłe stringi.
    $this->assertSame('Projekt Testowy 120', $firstRow[1]);
    $this->assertSame('Drukowana (4 egz.)', $firstRow[2]);

    // Dane inwestora (z podziałem na wiersze) muszą być tablicą #markup.
    $this->assertIsArray($firstRow[4]);
    $this->assertArrayHasKey('data', $firstRow[4]);
    $this->assertArrayHasKey('#markup', $firstRow[4]['data']);
    $this->assertStringContainsString('Jan Kowalski', $firstRow[4]['data']['#markup']);
    $this->assertStringContainsString('jan@example.com', $firstRow[4]['data']['#markup']);

    // Płatność musi być tablicą #markup.
    $this->assertIsArray($firstRow[5]);
    $this->assertArrayHasKey('data', $firstRow[5]);
    $this->assertStringContainsString('<code>blik_p24</code>', $firstRow[5]['data']['#markup']);
    $this->assertStringContainsString('TXN-12345', $firstRow[5]['data']['#markup']);

    // Status płatności musi być tablicą #markup.
    $this->assertIsArray($firstRow[6]);
    $this->assertArrayHasKey('data', $firstRow[6]);
    $this->assertStringContainsString('PAID', $firstRow[6]['data']['#markup']);
  }

  /**
   * Test poprawnego formatowania komórek tabeli zapytań o adaptację.
   */
  public function testInquiriesTableCellsUseMarkupRenderArray(): void {
    $projectRepo = $this->createMock(ProjectRepository::class);
    $orderRepo = $this->createMock(OrderRepository::class);
    $inquiryRepo = $this->createMock(InquiryRepository::class);

    $inquiryRepo->method('getAll')->willReturn([
      [
        'id' => 10,
        'customer_name' => 'Anna Nowak',
        'customer_email' => 'anna@example.pl',
        'customer_phone' => '+48 555 666 777',
        'inquiry_type' => 'adaptacja',
        'plot_location' => 'Kraków, dz. 12',
        'project_link' => 'https://example.com/projekt',
        'message' => "Prośba o wycenę adaptacji.\nDrugi wiersz.",
        'created' => 1791530000,
      ],
    ]);

    $controller = new AdminController($projectRepo, $orderRepo, $inquiryRepo);
    $controller->setStringTranslation($this->getStringTranslationStub());

    $build = $controller->inquiries();

    $this->assertArrayHasKey('table', $build);
    $this->assertSame('table', $build['table']['#type']);
    $this->assertNotEmpty($build['table']['#rows']);

    $firstRow = $build['table']['#rows'][0];

    // Dane klienta muszą być tablicą #markup z formatowaniem HTML.
    $this->assertIsArray($firstRow[1]);
    $this->assertArrayHasKey('data', $firstRow[1]);
    $this->assertStringContainsString('Anna Nowak', $firstRow[1]['data']['#markup']);
    $this->assertStringContainsString('anna@example.pl', $firstRow[1]['data']['#markup']);

    // Wiadomość musi mieć nl2br i być tablicą #markup.
    $this->assertIsArray($firstRow[5]);
    $this->assertArrayHasKey('data', $firstRow[5]);
    $this->assertStringContainsString('<br />', $firstRow[5]['data']['#markup']);
    $this->assertStringContainsString('Prośba o wycenę adaptacji.', $firstRow[5]['data']['#markup']);
  }

}
