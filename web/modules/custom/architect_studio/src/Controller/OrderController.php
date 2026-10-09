<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Controller;

use Drupal\architect_studio\Form\OrderForm;
use Drupal\architect_studio\Helper\OrderPrivacyHelper;
use Drupal\architect_studio\Repository\OrderRepository;
use Drupal\architect_studio\Repository\ProjectRepository;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Kontroler procesu zamawiania projektu i prezentacji potwierdzenia.
 */
final class OrderController extends ControllerBase {

  public function __construct(
    protected ProjectRepository $projectRepository,
    protected OrderRepository $orderRepository,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('architect_studio.project_repository'),
      $container->get('architect_studio.order_repository')
    );
  }

  /**
   * Wyświetla formularz zamówienia dla projektu.
   *
   * @param int $id
   *   Identyfikator projektu.
   *
   * @return array<string, mixed>
   *   Tablica formularza zamówienia.
   */
  public function order(int $id): array {
    $project = $this->projectRepository->getById($id);

    if (!$project || ((int) $project['is_hidden'] === 1)) {
      throw new NotFoundHttpException('Projekt nie został odnaleziony.');
    }

    $form = $this->formBuilder()->getForm(OrderForm::class);

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['order-page-wrapper']],
      'form' => $form,
      '#attached' => [
        'library' => [
          'architect_studio/studio-styles',
        ],
      ],
    ];
  }

  /**
   * Strona podsumowania i potwierdzenia opłaconego zamówienia.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Bieżące żądanie HTTP.
   * @param string $order_number
   *   Numer zamówienia.
   *
   * @return array<string, mixed>
   *   Tablica strony sukcesu zamówienia.
   */
  public function success(Request $request, string $order_number): array {
    $order = $this->orderRepository->getByOrderNumber($order_number);

    if (!$order) {
      throw new NotFoundHttpException('Zamówienie nie zostało odnalezione.');
    }

    $project = $this->projectRepository->getById((int) $order['project_id']);

    // Sprawdzenie czy użytkownik jest właścicielem w bieżącej sesji
    // lub posiada uprawnienia administratora.
    $sessionKey = 'architect_last_order_' . $order_number;
    $isSessionOwner = $request->hasSession() && ($request->getSession()->get($sessionKey) === TRUE);
    $isAdmin = $this->currentUser()->hasPermission('access administration pages');
    $canViewFull = $isSessionOwner || $isAdmin;

    // Jeżeli zamówienie przegląda osoba trzecia bez autoryzacji w sesji,
    // maskujemy dane osobowe (RODO / anty-IDOR).
    $displayOrder = $canViewFull ? $order : OrderPrivacyHelper::maskOrder($order);

    return [
      '#theme' => 'architect_order_success',
      '#order' => $displayOrder,
      '#project' => $project,
      '#is_masked' => !$canViewFull,
      '#attached' => [
        'library' => [
          'architect_studio/studio-styles',
        ],
      ],
      '#cache' => [
        'contexts' => [
          'session',
          'user.permissions',
        ],
      ],
    ];
  }

}
