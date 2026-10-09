<?php

namespace Drupal\symfony_comparison_demo\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Przykładowy serwis zarejestrowany w kontenerze Symfony DI Drupala.
 */
class DemoService {

  public function __construct(
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Zwraca powitanie dla aktualnego użytkownika.
   */
  public function getGreeting(): string {
    $name = $this->currentUser->getDisplayName();
    return "Cześć $name! Ten komunikat pochodzi z serwisu Symfony wstrzykniętego przez DI w Drupalu 10.";
  }

  /**
   * Pobiera ostatnie artykuły/przepisy z bazy danych za pomocą Drupal Entity Query.
   * Odpowiednik Doctrine QueryBuilder w świecie Drupala!
   */
  public function getLatestRecipes(int $limit = 5): array {
    $storage = $this->entityTypeManager->getStorage('node');

    // Drupal Entity Query API
    $nids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'recipe')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->execute();

    if (empty($nids)) {
      return [];
    }

    $nodes = $storage->loadMultiple($nids);
    $result = [];

    foreach ($nodes as $node) {
      $result[] = [
        'id' => (int) $node->id(),
        'title' => $node->label(),
        'difficulty' => $node->get('field_difficulty')->value ?? 'N/A',
        'prep_time' => $node->get('field_preparation_time')->value ?? 'N/A',
        'cooking_time' => $node->get('field_cooking_time')->value ?? 'N/A',
        'url' => $node->toUrl()->toString(),
      ];
    }

    return $result;
  }

}
