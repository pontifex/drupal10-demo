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
      'keyword' => trim((string) $request->query->get('keyword', '')),
      'category' => (string) $request->query->get('category', ''),
      'min_area' => $request->query->get('min_area', ''),
      'max_area' => $request->query->get('max_area', ''),
      'max_lot_width' => $request->query->get('max_lot_width', ''),
    ];

    $limit = 6;
    $pageParam = $request->query->get('page', 1);
    $page = is_numeric($pageParam) ? (int) $pageParam : 1;
    if ($page < 1) {
      $page = 1;
    }

    $totalFiltered = $this->projectRepository->countFiltered(FALSE, $filters);
    $allProjectsCount = $this->projectRepository->countFiltered(FALSE, []);

    $totalPages = (int) max(1, (int) ceil($totalFiltered / $limit));
    $currentPage = min($page, $totalPages);
    $offset = ($currentPage - 1) * $limit;

    $projects = $this->projectRepository->getAll(FALSE, $filters, $limit, $offset);

    $startItem = $totalFiltered > 0 ? $offset + 1 : 0;
    $endItem = min($offset + $limit, $totalFiltered);

    $queryParams = [];
    foreach (['keyword', 'category', 'min_area', 'max_area', 'max_lot_width'] as $key) {
      if ($filters[$key] !== '' && $filters[$key] !== NULL) {
        $queryParams[$key] = $filters[$key];
      }
    }

    $pagination = [
      'current_page' => $currentPage,
      'total_pages' => $totalPages,
      'total_items' => $totalFiltered,
      'limit' => $limit,
      'start_item' => $startItem,
      'end_item' => $endItem,
      'has_prev' => $currentPage > 1,
      'has_next' => $currentPage < $totalPages,
      'prev_page' => $currentPage - 1,
      'next_page' => $currentPage + 1,
      'pages' => range(1, $totalPages),
      'query_params' => $queryParams,
    ];

    return [
      '#theme' => 'architect_catalog',
      '#projects' => $projects,
      '#filters' => $filters,
      '#total_count' => $allProjectsCount,
      '#pagination' => $pagination,
      '#attached' => [
        'library' => [
          'architect_studio/studio-styles',
        ],
      ],
      '#cache' => [
        'contexts' => [
          'url.query_args',
        ],
        'tags' => [
          'architect_projects_list',
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
