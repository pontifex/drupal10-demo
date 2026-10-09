<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Repository;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;

/**
 * Repozytorium zarządzania projektami architektonicznymi.
 */
class ProjectRepository {

  public function __construct(
    protected Connection $database,
    protected TimeInterface $time,
    protected ?CacheTagsInvalidatorInterface $cacheTagsInvalidator = NULL,
  ) {}

  /**
   * Pobiera listę projektów z opcją filtrowania.
   *
   * @param bool $includeHidden
   *   Czy uwzględniać ukryte projekty.
   * @param array<string, mixed> $filters
   *   Filtry (np. category, max_area, max_price).
   * @param int $limit
   *   Limit wyników (0 = bez limitu).
   * @param int $offset
   *   Przesunięcie od początku listy.
   *
   * @return array<int, array<string, mixed>>
   *   Lista projektów.
   */
  public function getAll(bool $includeHidden = FALSE, array $filters = [], int $limit = 0, int $offset = 0): array {
    $query = $this->database->select('architect_projects', 'p')
      ->fields('p')
      ->orderBy('p.id', 'ASC');

    if ($limit > 0) {
      $query->range($offset, $limit);
    }

    if (!$includeHidden) {
      $query->condition('p.is_hidden', 0);
    }

    if (!empty($filters['keyword']) && is_string($filters['keyword'])) {
      $keyword = trim($filters['keyword']);
      if ($keyword !== '') {
        $escaped = '%' . $this->database->escapeLike($keyword) . '%';
        $or = $query->orConditionGroup()
          ->condition('p.title', $escaped, 'LIKE')
          ->condition('p.code', $escaped, 'LIKE')
          ->condition('p.description', $escaped, 'LIKE');
        $query->condition($or);
      }
    }

    if (!empty($filters['category'])) {
      $query->condition('p.category', $filters['category']);
    }

    if (!empty($filters['min_area']) && is_numeric($filters['min_area'])) {
      $query->condition('p.usable_area', (string) $filters['min_area'], '>=');
    }

    if (!empty($filters['max_area']) && is_numeric($filters['max_area'])) {
      $query->condition('p.usable_area', (string) $filters['max_area'], '<=');
    }

    if (!empty($filters['max_lot_width']) && is_numeric($filters['max_lot_width'])) {
      $query->condition('p.min_lot_width', (string) $filters['max_lot_width'], '<=');
    }

    return (array) $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Zlicza liczbę projektów pasujących do kryteriów filtrów.
   *
   * @param bool $includeHidden
   *   Czy uwzględniać ukryte projekty.
   * @param array<string, mixed> $filters
   *   Filtry wyszukiwania.
   *
   * @return int
   *   Łączna liczba pasujących projektów.
   */
  public function countFiltered(bool $includeHidden = FALSE, array $filters = []): int {
    $query = $this->database->select('architect_projects', 'p');

    if (!$includeHidden) {
      $query->condition('p.is_hidden', 0);
    }

    if (!empty($filters['keyword']) && is_string($filters['keyword'])) {
      $keyword = trim($filters['keyword']);
      if ($keyword !== '') {
        $escaped = '%' . $this->database->escapeLike($keyword) . '%';
        $or = $query->orConditionGroup()
          ->condition('p.title', $escaped, 'LIKE')
          ->condition('p.code', $escaped, 'LIKE')
          ->condition('p.description', $escaped, 'LIKE');
        $query->condition($or);
      }
    }

    if (!empty($filters['category'])) {
      $query->condition('p.category', $filters['category']);
    }

    if (!empty($filters['min_area']) && is_numeric($filters['min_area'])) {
      $query->condition('p.usable_area', (string) $filters['min_area'], '>=');
    }

    if (!empty($filters['max_area']) && is_numeric($filters['max_area'])) {
      $query->condition('p.usable_area', (string) $filters['max_area'], '<=');
    }

    if (!empty($filters['max_lot_width']) && is_numeric($filters['max_lot_width'])) {
      $query->condition('p.min_lot_width', (string) $filters['max_lot_width'], '<=');
    }

    return (int) $query->countQuery()->execute()->fetchField();
  }

  /**
   * Pobiera pojedynczy projekt po ID.
   *
   * @param int $id
   *   Identyfikator projektu.
   *
   * @return array<string, mixed>|null
   *   Dane projektu lub NULL.
   */
  public function getById(int $id): ?array {
    $result = $this->database->select('architect_projects', 'p')
      ->fields('p')
      ->condition('p.id', $id)
      ->execute()
      ->fetchAssoc();

    return $result ?: NULL;
  }

  /**
   * Przełącza widoczność projektu (ukryj / pokaż).
   *
   * @param int $id
   *   Identyfikator projektu.
   *
   * @return bool
   *   TRUE jeśli sukces, FALSE w przeciwnym razie.
   */
  public function toggleVisibility(int $id): bool {
    $project = $this->getById($id);
    if (!$project) {
      return FALSE;
    }

    $newStatus = ((int) $project['is_hidden'] === 1) ? 0 : 1;
    $this->database->update('architect_projects')
      ->fields([
        'is_hidden' => $newStatus,
        'updated' => $this->time->getRequestTime(),
      ])
      ->condition('id', $id)
      ->execute();

    $this->invalidateListCache();

    return TRUE;
  }

  /**
   * Zapisuje lub aktualizuje projekt.
   *
   * @param array<string, mixed> $data
   *   Dane projektu do zapisu.
   *
   * @return int
   *   ID zapisanego projektu.
   */
  public function save(array $data): int {
    $now = $this->time->getRequestTime();
    $data['updated'] = $now;

    if (!empty($data['id'])) {
      $id = (int) $data['id'];
      unset($data['id']);
      $this->database->update('architect_projects')
        ->fields($data)
        ->condition('id', $id)
        ->execute();

      $this->invalidateListCache();
      return $id;
    }

    $data['created'] = $now;
    $newId = (int) $this->database->insert('architect_projects')
      ->fields($data)
      ->execute();

    $this->invalidateListCache();
    return $newId;
  }

  /**
   * Usuwa projekt.
   */
  public function delete(int $id): bool {
    $deleted = $this->database->delete('architect_projects')
      ->condition('id', $id)
      ->execute();

    if ($deleted > 0) {
      $this->invalidateListCache();
    }

    return $deleted > 0;
  }

  /**
   * Inwaliduje tagi pamięci podręcznej listy projektów.
   */
  protected function invalidateListCache(): void {
    if ($this->cacheTagsInvalidator !== NULL) {
      $this->cacheTagsInvalidator->invalidateTags(['architect_projects_list']);
    }
  }

}
