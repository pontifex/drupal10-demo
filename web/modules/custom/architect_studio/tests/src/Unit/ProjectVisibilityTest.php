<?php

declare(strict_types=1);

namespace Drupal\Tests\architect_studio\Unit;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\architect_studio\Controller\AdminController;
use Drupal\architect_studio\Controller\ShowcaseController;
use Drupal\architect_studio\Repository\InquiryRepository;
use Drupal\architect_studio\Repository\OrderRepository;
use Drupal\architect_studio\Repository\ProjectRepository;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Testy jednostkowe dla opcji ukrywania projektów i inwalidacji cache.
 *
 * @group architect_studio
 */
class ProjectVisibilityTest extends UnitTestCase {

  /**
   * Test inwalidacji cache przy przełączaniu widoczności projektu.
   */
  public function testToggleVisibilityInvalidatesCache(): void {
    $db = $this->createMock(Connection::class);
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(1791540000);
    $cacheInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);

    $cacheInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['architect_projects_list']);

    // Mock pobierania projektu przed przełączeniem.
    $select = $this->createMock(SelectInterface::class);
    $stmt = $this->createMock(StatementInterface::class);
    $stmt->method('fetchAssoc')->willReturn([
      'id' => 1,
      'is_hidden' => 0,
      'title' => 'Projekt Testowy',
    ]);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($stmt);

    // Mock aktualizacji w bazie.
    $update = $this->createMock(Update::class);
    $update->method('fields')->willReturnSelf();
    $update->method('condition')->willReturnSelf();
    $update->method('execute')->willReturn(1);

    $db->method('select')->willReturn($select);
    $db->method('update')->willReturn($update);

    $repo = new ProjectRepository($db, $time, $cacheInvalidator);
    $success = $repo->toggleVisibility(1);

    $this->assertTrue($success);
  }

  /**
   * Test inwalidacji cache przy zapisie projektu.
   */
  public function testSaveInvalidatesCache(): void {
    $db = $this->createMock(Connection::class);
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(1791540000);
    $cacheInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);

    $cacheInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['architect_projects_list']);

    $insert = $this->createMock(Insert::class);
    $insert->method('fields')->willReturnSelf();
    $insert->method('execute')->willReturn(10);

    $db->method('insert')->willReturn($insert);

    $repo = new ProjectRepository($db, $time, $cacheInvalidator);
    $newId = $repo->save([
      'title' => 'Nowy Projekt',
      'is_hidden' => 1,
    ]);

    $this->assertSame(10, $newId);
  }

  /**
   * Test inwalidacji cache przy usunięciu projektu.
   */
  public function testDeleteInvalidatesCache(): void {
    $db = $this->createMock(Connection::class);
    $time = $this->createMock(TimeInterface::class);
    $cacheInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);

    $cacheInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['architect_projects_list']);

    $delete = $this->createMock(Delete::class);
    $delete->method('condition')->willReturnSelf();
    $delete->method('execute')->willReturn(1);

    $db->method('delete')->willReturn($delete);

    $repo = new ProjectRepository($db, $time, $cacheInvalidator);
    $deleted = $repo->delete(5);

    $this->assertTrue($deleted);
  }

  /**
   * Test wyświetlania statusu i przycisków Ukryj / Pokaż w panelu admina.
   */
  public function testAdminProjectsTableRendersHideButtons(): void {
    $repo = $this->createMock(ProjectRepository::class);
    $orderRepo = $this->createMock(OrderRepository::class);
    $inquiryRepo = $this->createMock(InquiryRepository::class);

    $repo->method('getAll')->with(TRUE)->willReturn([
      [
        'id' => 1,
        'code' => 'DOM-01',
        'title' => 'Projekt Widoczny',
        'category' => 'house',
        'usable_area' => 120.0,
        'price_digital' => 3000.0,
        'price_print' => 3500.0,
        'is_hidden' => 0,
      ],
      [
        'id' => 2,
        'code' => 'DOM-02',
        'title' => 'Projekt Ukryty',
        'category' => 'house',
        'usable_area' => 150.0,
        'price_digital' => 4000.0,
        'price_print' => 4500.0,
        'is_hidden' => 1,
      ],
    ]);

    $container = new ContainerBuilder();
    $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    $urlGenerator->method('generateFromRoute')->willReturnCallback(
      function (string $name, array $parameters = []): string {
        return '/admin/test/' . ($parameters['id'] ?? '');
      }
    );
    $container->set('url_generator', $urlGenerator);
    \Drupal::setContainer($container);

    $controller = new AdminController($repo, $orderRepo, $inquiryRepo);
    $controller->setStringTranslation($this->getStringTranslationStub());

    $build = $controller->projects();

    $this->assertArrayHasKey('stats', $build);
    $this->assertStringContainsString('Wszystkie projekty:</strong> 2', $build['stats']['#markup']);
    $this->assertStringContainsString('Widoczne na stronie (front-end):</strong> 1', $build['stats']['#markup']);
    $this->assertStringContainsString('Ukryte (szkice/robocze):</strong> 1', $build['stats']['#markup']);

    $this->assertArrayHasKey('table', $build);
    $rows = $build['table']['#rows'];
    $this->assertCount(2, $rows);

    // Wiersz widoczny (ma przycisk "Ukryj").
    $row1 = $rows[0];
    $operations1 = $row1['data'][7]['data']['#markup'];
    $this->assertStringContainsString('>Ukryj<', $operations1);
    $this->assertStringContainsString('Widoczny na stronie', $row1['data'][6]['data']['#markup']);

    // Wiersz ukryty (ma przycisk "Pokaż na stronie" oraz oznaczenie Ukryty).
    $row2 = $rows[1];
    $operations2 = $row2['data'][7]['data']['#markup'];
    $this->assertStringContainsString('>Pokaż na stronie<', $operations2);
    $this->assertStringContainsString('Ukryty na front-end', $row2['data'][6]['data']['#markup']);
    $this->assertStringContainsString('(Ukryty)', $row2['data'][1]['data']['#markup']);
  }

  /**
   * Test obecności tagu cache w ShowcaseController.
   */
  public function testShowcaseControllerHasCacheTags(): void {
    $repo = $this->createMock(ProjectRepository::class);
    $repo->method('getAll')->with(FALSE)->willReturn([]);

    $controller = new ShowcaseController($repo);
    $formBuilder = $this->createMock(FormBuilderInterface::class);
    $formBuilder->method('getForm')->willReturn([]);

    // Refleksja do ustawienia formBuilder w kontrolerze.
    $reflection = new \ReflectionClass(ShowcaseController::class);
    $property = $reflection->getProperty('formBuilder');
    $property->setAccessible(TRUE);
    $property->setValue($controller, $formBuilder);

    $build = $controller->index();
    $this->assertArrayHasKey('#cache', $build);
    $this->assertSame(['architect_projects_list'], $build['#cache']['tags']);
  }

}
