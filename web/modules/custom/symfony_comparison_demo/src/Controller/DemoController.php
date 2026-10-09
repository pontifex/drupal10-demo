<?php

namespace Drupal\symfony_comparison_demo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\symfony_comparison_demo\Service\DemoService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Kontroler Drupala - bazuje na Symfony HttpKernel!
 */
class DemoController extends ControllerBase {

  public function __construct(
    protected DemoService $demoService,
  ) {}

  /**
   * {@inheritdoc}
   * W Drupalu kontrolery używają ContainerInjectionInterface::create()
   * do fabrykowania instancji z kontenera Symfony DI.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('symfony_comparison_demo.greeter')
    );
  }

  /**
   * Akcja generująca stronę HTML.
   *
   * W Symfony zwraca się Response(render(...)).
   * W Drupalu zwraca się tzw. "Render Array" (tablicę asocjacyjną z metadanymi cache,
   * szablonem Twig i parametrami), a Drupal sam układa całą stronę (Layout, Theme, Bloki).
   */
  public function index(): array {
    $greeting = $this->demoService->getGreeting();
    $recipes = $this->demoService->getLatestRecipes(6);

    return [
      '#theme' => 'symfony_demo_page',
      '#greeting' => $greeting,
      '#recipes' => $recipes,
      '#cache' => [
        // Drupal ma potężny system Cache Tags i Contexts!
        // Unieważnia cache automatycznie, gdy jakikolwiek przepis zostanie zaktualizowany!
        'tags' => ['node_list:recipe'],
        'contexts' => ['user'],
      ],
    ];
  }

  /**
   * Akcja zwracająca czyste JSON API.
   *
   * Tutaj Drupal zachowuje się dokładnie jak czysty Symfony Controller -
   * zwracamy bezpośrednio instancję Symfony\Component\HttpFoundation\JsonResponse!
   */
  public function api(): JsonResponse {
    $recipes = $this->demoService->getLatestRecipes(10);

    return new JsonResponse([
      'framework' => 'Drupal 10 on Symfony',
      'message' => 'To jest bezpośrednia odpowiedź Symfony JsonResponse w Drupalu!',
      'count' => count($recipes),
      'recipes' => $recipes,
    ]);
  }

}
