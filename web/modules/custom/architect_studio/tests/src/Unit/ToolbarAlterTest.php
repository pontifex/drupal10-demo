<?php

declare(strict_types=1);

namespace Drupal\Tests\architect_studio\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Testy jednostkowe dla modyfikacji paska narzędziowego (toolbar).
 *
 * @group architect_studio
 */
class ToolbarAlterTest extends UnitTestCase {

  /**
   * Test usuwania ostrzeżenia profilu demonstracyjnego.
   */
  public function testToolbarAlterRemovesExperimentalWarning(): void {
    require_once __DIR__ . '/../../../architect_studio.module';

    $items = [
      'administration' => ['#type' => 'toolbar_item'],
      'experimental-profile-warning' => ['#type' => 'toolbar_item'],
      'user' => ['#type' => 'toolbar_item'],
    ];

    architect_studio_toolbar_alter($items);

    $this->assertArrayNotHasKey('experimental-profile-warning', $items);
    $this->assertArrayHasKey('administration', $items);
    $this->assertArrayHasKey('user', $items);
  }

}
