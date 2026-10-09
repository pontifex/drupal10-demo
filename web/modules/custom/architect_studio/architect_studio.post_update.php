<?php

/**
 * @file
 * Post-update functions for architect_studio module.
 */

declare(strict_types=1);

/**
 * Usuwa pozostałości demonstracyjnych widoków i czyści menu główne.
 */
function architect_studio_post_update_cleanup_demo_views_and_menu(): void {
  $config_factory = \Drupal::configFactory();
  foreach (['views.view.featured_articles', 'views.view.articles_aside', 'views.view.recipe_collections'] as $config_name) {
    $config = $config_factory->getEditable($config_name);
    if (!$config->isNew()) {
      $config->delete();
    }
  }

  /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
  $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
  if ($menu_link_manager->hasDefinition('standard.front_page')) {
    $menu_link_manager->updateDefinition('standard.front_page', ['enabled' => 0]);
  }
}
