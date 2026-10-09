<?php

declare(strict_types=1);

namespace Drupal\Tests\architect_studio\Unit;

use Drupal\architect_studio\Controller\LegalController;
use Drupal\Tests\UnitTestCase;

/**
 * Testy jednostkowe dla LegalController.
 *
 * @group architect_studio
 */
class LegalControllerTest extends UnitTestCase {

  /**
   * Test zwracania szablonu i tytułu dla regulaminu.
   */
  public function testTermsActionReturnsExpectedRenderArray(): void {
    $controller = new LegalController();
    $controller->setStringTranslation($this->getStringTranslationStub());

    $renderArray = $controller->terms();
    $this->assertIsArray($renderArray);
    $this->assertSame('architect_terms', $renderArray['#theme']);
    $this->assertSame('Regulamin Serwisu i Sprzedaży Projektów', (string) $renderArray['#title']);
  }

  /**
   * Test zwracania szablonu i tytułu dla polityki prywatności.
   */
  public function testPrivacyActionReturnsExpectedRenderArray(): void {
    $controller = new LegalController();
    $controller->setStringTranslation($this->getStringTranslationStub());

    $renderArray = $controller->privacy();
    $this->assertIsArray($renderArray);
    $this->assertSame('architect_privacy', $renderArray['#theme']);
    $this->assertSame('Polityka Prywatności i Informacja o RODO', (string) $renderArray['#title']);
  }

}
