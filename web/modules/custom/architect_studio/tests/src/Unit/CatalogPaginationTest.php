<?php

declare(strict_types=1);

namespace Drupal\Tests\architect_studio\Unit;

use Drupal\architect_studio\Controller\CatalogController;
use Drupal\architect_studio\Repository\ProjectRepository;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Testy jednostkowe dla paginacji katalogu projektów.
 *
 * @group architect_studio
 */
class CatalogPaginationTest extends UnitTestCase {

  /**
   * Test kalkulacji liczby stron i offsetu dla pierwszej strony.
   */
  public function testPaginationPageOne(): void {
    $repo = $this->createMock(ProjectRepository::class);
    $repo->method('countFiltered')->willReturn(9);

    $repo->expects($this->once())
      ->method('getAll')
      ->with(FALSE, $this->isType('array'), 6, 0)
      ->willReturn([
        ['id' => 1, 'title' => 'Projekt 1'],
        ['id' => 2, 'title' => 'Projekt 2'],
        ['id' => 3, 'title' => 'Projekt 3'],
        ['id' => 4, 'title' => 'Projekt 4'],
        ['id' => 5, 'title' => 'Projekt 5'],
        ['id' => 6, 'title' => 'Projekt 6'],
      ]);

    $controller = new CatalogController($repo);
    $controller->setStringTranslation($this->getStringTranslationStub());

    $request = new Request(['page' => 1]);
    $renderArray = $controller->catalog($request);

    $this->assertIsArray($renderArray);
    $this->assertArrayHasKey('#pagination', $renderArray);

    $pagination = $renderArray['#pagination'];
    $this->assertSame(1, $pagination['current_page']);
    $this->assertSame(2, $pagination['total_pages']);
    $this->assertSame(9, $pagination['total_items']);
    $this->assertSame(6, $pagination['limit']);
    $this->assertSame(1, $pagination['start_item']);
    $this->assertSame(6, $pagination['end_item']);
    $this->assertFalse($pagination['has_prev']);
    $this->assertTrue($pagination['has_next']);
    $this->assertSame(2, $pagination['next_page']);
    $this->assertSame([1, 2], $pagination['pages']);
  }

  /**
   * Test kalkulacji offsetu i granic dla drugiej strony.
   */
  public function testPaginationPageTwo(): void {
    $repo = $this->createMock(ProjectRepository::class);
    $repo->method('countFiltered')->willReturn(9);

    $repo->expects($this->once())
      ->method('getAll')
      ->with(FALSE, $this->isType('array'), 6, 6)
      ->willReturn([
        ['id' => 7, 'title' => 'Projekt 7'],
        ['id' => 8, 'title' => 'Projekt 8'],
        ['id' => 9, 'title' => 'Projekt 9'],
      ]);

    $controller = new CatalogController($repo);
    $controller->setStringTranslation($this->getStringTranslationStub());

    $request = new Request(['page' => 2]);
    $renderArray = $controller->catalog($request);

    $pagination = $renderArray['#pagination'];
    $this->assertSame(2, $pagination['current_page']);
    $this->assertSame(2, $pagination['total_pages']);
    $this->assertSame(9, $pagination['total_items']);
    $this->assertSame(7, $pagination['start_item']);
    $this->assertSame(9, $pagination['end_item']);
    $this->assertTrue($pagination['has_prev']);
    $this->assertFalse($pagination['has_next']);
    $this->assertSame(1, $pagination['prev_page']);
  }

  /**
   * Test przycinania niepoprawnych numerów stron (ujemnych lub zbyt dużych).
   */
  public function testPaginationClampingOutOfBounds(): void {
    $repo = $this->createMock(ProjectRepository::class);
    $repo->method('countFiltered')->willReturn(9);
    $repo->method('getAll')->willReturn([]);

    $controller = new CatalogController($repo);
    $controller->setStringTranslation($this->getStringTranslationStub());

    // Strona 99 powinna zostać przycięta do 2.
    $requestTooHigh = new Request(['page' => 99]);
    $renderHigh = $controller->catalog($requestTooHigh);
    $this->assertSame(2, $renderHigh['#pagination']['current_page']);

    // Strona -5 powinna zostać przycięta do 1.
    $requestNegative = new Request(['page' => -5]);
    $renderNegative = $controller->catalog($requestNegative);
    $this->assertSame(1, $renderNegative['#pagination']['current_page']);
  }

  /**
   * Test zachowywania aktywnych filtrów w metadanych paginacji.
   */
  public function testPaginationPreservesFilters(): void {
    $repo = $this->createMock(ProjectRepository::class);
    $repo->method('countFiltered')->willReturn(3);
    $repo->method('getAll')->willReturn([]);

    $controller = new CatalogController($repo);
    $controller->setStringTranslation($this->getStringTranslationStub());

    $request = new Request([
      'keyword' => 'Gloria',
      'category' => 'dom_z_poddaszem',
      'max_area' => '150',
    ]);
    $renderArray = $controller->catalog($request);

    $queryParams = $renderArray['#pagination']['query_params'];
    $this->assertSame('Gloria', $queryParams['keyword']);
    $this->assertSame('dom_z_poddaszem', $queryParams['category']);
    $this->assertSame('150', $queryParams['max_area']);
    $this->assertArrayNotHasKey('max_lot_width', $queryParams);
  }

}
