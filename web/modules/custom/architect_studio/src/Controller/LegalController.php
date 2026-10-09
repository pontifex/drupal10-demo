<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Kontroler dla podstron prawnych (Regulamin i Polityka Prywatności).
 */
class LegalController extends ControllerBase {

  /**
   * Wyświetla stronę Regulaminu serwisu i sprzedaży projektów.
   *
   * @return array<string, mixed>
   *   Render array dla szablonu regulaminu.
   */
  public function terms(): array {
    return [
      '#theme' => 'architect_terms',
      '#title' => $this->t('Regulamin Serwisu i Sprzedaży Projektów'),
    ];
  }

  /**
   * Wyświetla stronę Polityki Prywatności i informacji RODO.
   *
   * @return array<string, mixed>
   *   Render array dla szablonu polityki prywatności.
   */
  public function privacy(): array {
    return [
      '#theme' => 'architect_privacy',
      '#title' => $this->t('Polityka Prywatności i Informacja o RODO'),
    ];
  }

}
