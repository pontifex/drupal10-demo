<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Kontroler dedykowanej strony błędu 404 (Nie znaleziono).
 */
final class NotFoundController extends ControllerBase {

  /**
   * Wyświetla spersonalizowaną stronę błędu 404 pracowni architektonicznej.
   *
   * @return array<string, mixed>
   *   Tablica renderowalna strony błędu 404.
   */
  public function notFound(): array {
    return [
      '#theme' => 'architect_404',
      '#attached' => [
        'library' => [
          'architect_studio/studio-styles',
        ],
      ],
    ];
  }

}
