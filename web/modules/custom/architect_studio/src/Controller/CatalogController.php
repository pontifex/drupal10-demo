<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Controller;

use Drupal\architect_studio\Repository\ProjectRepository;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Kontroler katalogu projektów autorskich architekta.
 */
final class CatalogController extends ControllerBase {

  public function __construct(
    protected ProjectRepository $projectRepository,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('architect_studio.project_repository')
    );
  }

  /**
   * Wyświetla publiczny katalog projektów z filtrami.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Żądanie HTTP z opcjonalnymi parametrami filtrów.
   *
   * @return array<string, mixed>
   *   Tablica strony katalogu projektów.
   */
  public function catalog(Request $request): array {
    $filters = [
      'category' => (string) $request->query->get('category', ''),
      'max_area' => $request->query->get('max_area', ''),
      'max_lot_width' => $request->query->get('max_lot_width', ''),
    ];

    $projects = $this->projectRepository->getAll(FALSE, $filters);

    return [
      '#theme' => 'architect_catalog',
      '#projects' => $projects,
      '#filters' => $filters,
      '#attached' => [
        'library' => [
          'architect_studio/studio-styles',
        ],
      ],
    ];
  }

  /**
   * Wyświetla szczegółową kartę pojedynczego projektu.
   *
   * @param int $id
   *   Identyfikator projektu.
   *
   * @return array<string, mixed>
   *   Tablica strony szczegółów projektu.
   */
  public function detail(int $id): array {
    $project = $this->projectRepository->getById($id);

    if (!$project || ((int) $project['is_hidden'] === 1)) {
      throw new NotFoundHttpException('Projekt nie został znaleziony lub jest ukryty.');
    }

    return [
      '#theme' => 'architect_project_detail',
      '#project' => $project,
      '#attached' => [
        'library' => [
          'architect_studio/studio-styles',
        ],
      ],
    ];
  }

}
