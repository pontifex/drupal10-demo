<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Controller;

use Drupal\architect_studio\Form\AdaptationInquiryForm;
use Drupal\architect_studio\Repository\ProjectRepository;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Kontroler strony wizytówkowej architekta z ofertą adaptacji i zmian.
 */
final class ShowcaseController extends ControllerBase {

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
   * Renderuje stronę główną / wizytówkę biura architektonicznego.
   *
   * @return array<string, mixed>
   *   Tablica renderowalna strony wizytówki.
   */
  public function index(): array {
    $featuredProjects = $this->projectRepository->getAll(FALSE);
    $inquiryForm = $this->formBuilder()->getForm(AdaptationInquiryForm::class);

    return [
      '#theme' => 'architect_showcase',
      '#featured_projects' => array_slice($featuredProjects, 0, 3),
      '#inquiry_form' => $inquiryForm,
      '#attached' => [
        'library' => [
          'architect_studio/studio-styles',
        ],
      ],
      '#cache' => [
        'tags' => [
          'architect_projects_list',
        ],
      ],
    ];
  }

}
