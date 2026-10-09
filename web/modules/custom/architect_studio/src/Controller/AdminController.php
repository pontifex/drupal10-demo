<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Controller;

use Drupal\architect_studio\Form\ProjectEditForm;
use Drupal\architect_studio\Repository\InquiryRepository;
use Drupal\architect_studio\Repository\OrderRepository;
use Drupal\architect_studio\Repository\ProjectRepository;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Panel administracyjny architekta: projekty, zamówienia i zapytania.
 */
final class AdminController extends ControllerBase {

  public function __construct(
    protected ProjectRepository $projectRepository,
    protected OrderRepository $orderRepository,
    protected InquiryRepository $inquiryRepository,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('architect_studio.project_repository'),
      $container->get('architect_studio.order_repository'),
      $container->get('architect_studio.inquiry_repository')
    );
  }

  /**
   * Lista wszystkich projektów z opcją szybkiego ukrywania/pokazywania.
   *
   * @return array<string, mixed>
   *   Tablica strony projektów w panelu.
   */
  public function projects(): array {
    $projects = $this->projectRepository->getAll(TRUE);

    $totalCount = count($projects);
    $hiddenCount = count(array_filter($projects, static fn(array $p): bool => (int) ($p['is_hidden'] ?? 0) === 1));
    $visibleCount = $totalCount - $hiddenCount;

    $header = [
      $this->t('ID / Kod'),
      $this->t('Nazwa projektu'),
      $this->t('Kategoria'),
      $this->t('Powierzchnia'),
      $this->t('Cena cyfrowa'),
      $this->t('Cena drukowana'),
      $this->t('Widoczność na stronie'),
      $this->t('Operacje'),
    ];

    $rows = [];
    foreach ($projects as $project) {
      $isHidden = (int) $project['is_hidden'] === 1;
      $statusBadge = $isHidden
        ? ['data' => ['#markup' => '<span class="badge" style="background:#64748b;color:#fff;padding:3px 8px;border-radius:4px;font-size:12px;font-weight:600;">Ukryty na front-end</span>']]
        : ['data' => ['#markup' => '<span class="badge" style="background:#16a34a;color:#fff;padding:3px 8px;border-radius:4px;font-size:12px;font-weight:600;">Widoczny na stronie</span>']];

      $toggleTitle = $isHidden ? $this->t('Pokaż na stronie') : $this->t('Ukryj');
      $toggleStyle = $isHidden
        ? 'margin-right:6px;background:#16a34a;color:#fff;border-color:#16a34a;'
        : 'margin-right:6px;background:#d97706;color:#fff;border-color:#d97706;';
      $toggleTooltip = $isHidden
        ? 'Opublikuj ten projekt z powrotem na front-endzie'
        : 'Ukryj ten projekt na front-endzie bez usuwania z bazy danych';

      $toggleUrl = Url::fromRoute('architect_studio.admin_project_toggle', ['id' => $project['id']]);
      $editUrl = Url::fromRoute('architect_studio.admin_project_edit', ['id' => $project['id']]);
      $deleteUrl = Url::fromRoute('architect_studio.admin_project_delete', ['id' => $project['id']]);

      $operations = [
        'data' => [
          '#markup' => sprintf(
            '<a href="%s" class="button button--small" style="%s" title="%s">%s</a>
             <a href="%s" class="button button--small" style="margin-right:6px;">Edytuj</a>
             <a href="%s" class="button button--small button--danger" onclick="return confirm(\'Czy na pewno bezpowrotnie usunąć ten projekt? Aby tylko wycofać go ze strony, użyj opcji Ukryj.\')">Usuń</a>',
            $toggleUrl->toString(),
            $toggleStyle,
            $toggleTooltip,
            $toggleTitle,
            $editUrl->toString(),
            $deleteUrl->toString()
          ),
        ],
      ];

      $titleMarkup = $isHidden
        ? htmlspecialchars((string) $project['title'], ENT_QUOTES, 'UTF-8') . ' <small style="color:#d97706;font-weight:600;">(Ukryty)</small>'
        : htmlspecialchars((string) $project['title'], ENT_QUOTES, 'UTF-8');

      $rows[] = [
        'data' => [
          $project['code'] . ' (#' . $project['id'] . ')',
          ['data' => ['#markup' => $titleMarkup]],
          $project['category'],
          $project['usable_area'] . ' m²',
          number_format((float) $project['price_digital'], 2, ',', ' ') . ' zł',
          number_format((float) $project['price_print'], 2, ',', ' ') . ' zł',
          $statusBadge,
          $operations,
        ],
        'style' => $isHidden ? 'background-color:#f8fafc;' : '',
      ];
    }

    $addUrl = Url::fromRoute('architect_studio.admin_project_add')->toString();

    $statsHtml = sprintf(
      '<div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
        <div style="background:#fff;border:1px solid #e2e8f0;padding:8px 14px;border-radius:6px;font-size:13px;"><strong>Wszystkie projekty:</strong> %d</div>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:8px 14px;border-radius:6px;font-size:13px;"><strong>Widoczne na stronie (front-end):</strong> %d</div>
        <div style="background:#fffbeb;border:1px solid #fef3c7;color:#92400e;padding:8px 14px;border-radius:6px;font-size:13px;"><strong>Ukryte (szkice/robocze):</strong> %d</div>
      </div>',
      $totalCount,
      $visibleCount,
      $hiddenCount
    );

    return [
      'stats' => [
        '#markup' => $statsHtml,
      ],
      'add_button' => [
        '#markup' => sprintf(
          '<div style="margin-bottom:15px;">
            <a href="%s" class="button button--primary button--action">Dodaj nowy projekt architektoniczny</a>
          </div>',
          $addUrl
        ),
      ],
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('Brak projektów w bazie.'),
      ],
      '#attached' => [
        'library' => ['architect_studio/studio-styles'],
      ],
    ];
  }

  /**
   * Przełącza widoczność projektu (ukryj / pokaż na front-endzie).
   *
   * @param int $id
   *   Identyfikator projektu.
   */
  public function toggleProject(int $id): RedirectResponse {
    $project = $this->projectRepository->getById($id);
    if (!$project) {
      throw new NotFoundHttpException('Projekt nie istnieje.');
    }

    $wasHidden = ((int) $project['is_hidden'] === 1);
    $this->projectRepository->toggleVisibility($id);

    if ($wasHidden) {
      $this->messenger()->addStatus($this->t('Projekt "%title" został przywrócony i jest teraz widoczny na front-endzie.', [
        '%title' => $project['title'],
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Projekt "%title" został ukryty — nie jest widoczny dla odwiedzających na front-endzie, ale pozostaje zachowany w panelu.', [
        '%title' => $project['title'],
      ]));
    }

    return $this->redirect('architect_studio.admin_projects');
  }

  /**
   * Usuwa projekt z bazy.
   *
   * @param int $id
   *   Identyfikator projektu.
   */
  public function deleteProject(int $id): RedirectResponse {
    $project = $this->projectRepository->getById($id);
    if ($project) {
      $this->projectRepository->delete($id);
      $this->messenger()->addStatus($this->t('Projekt "%title" został usunięty.', ['%title' => $project['title']]));
    }

    return $this->redirect('architect_studio.admin_projects');
  }

  /**
   * Dodawanie nowego projektu.
   *
   * @return array<string, mixed>
   *   Tablica formularza dodawania projektu.
   */
  public function addProject(): array {
    $form = $this->formBuilder()->getForm(ProjectEditForm::class);
    return [
      'form' => $form,
      '#attached' => [
        'library' => ['architect_studio/studio-styles'],
      ],
    ];
  }

  /**
   * Edycja istniejącego projektu.
   *
   * @param int $id
   *   Identyfikator projektu.
   *
   * @return array<string, mixed>
   *   Tablica formularza edycji projektu.
   */
  public function editProject(int $id): array {
    $project = $this->projectRepository->getById($id);
    if (!$project) {
      throw new NotFoundHttpException('Projekt nie istnieje.');
    }

    $form = $this->formBuilder()->getForm(ProjectEditForm::class);
    return [
      'form' => $form,
      '#attached' => [
        'library' => ['architect_studio/studio-styles'],
      ],
    ];
  }

  /**
   * Lista zamówień złożonych przez inwestorów.
   *
   * @return array<string, mixed>
   *   Tablica strony zamówień.
   */
  public function orders(): array {
    $orders = $this->orderRepository->getAll();

    $header = [
      $this->t('Numer zamówienia'),
      $this->t('Projekt'),
      $this->t('Wariant'),
      $this->t('Kwota'),
      $this->t('Inwestor'),
      $this->t('Płatność / BLIK'),
      $this->t('Status'),
      $this->t('Data'),
    ];

    $rows = [];
    foreach ($orders as $order) {
      $variantLabel = ($order['variant'] === 'print') ? 'Drukowana (4 egz.)' : 'Cyfrowa (PDF)';
      $statusColor = match ($order['payment_status']) {
        'paid' => '#16a34a',
        'pending' => '#d97706',
        default => '#dc2626',
      };

      $rows[] = [
        [
          'data' => [
            '#markup' => '<strong>' . htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8') . '</strong>',
          ],
        ],
        (string) $order['project_title'],
        $variantLabel,
        number_format((float) $order['amount'], 2, ',', ' ') . ' zł',
        [
          'data' => [
            '#markup' => htmlspecialchars((string) $order['customer_name'], ENT_QUOTES, 'UTF-8')
            . '<br><small style="color:#64748b;">'
            . htmlspecialchars((string) $order['customer_email'], ENT_QUOTES, 'UTF-8')
            . ' | '
            . htmlspecialchars((string) $order['customer_phone'], ENT_QUOTES, 'UTF-8')
            . '</small>',
          ],
        ],
        [
          'data' => [
            '#markup' => '<code>' . htmlspecialchars((string) $order['payment_method'], ENT_QUOTES, 'UTF-8') . '</code>'
            . '<br><small style="color:#64748b;">'
            . htmlspecialchars((string) ($order['transaction_id'] ?? ''), ENT_QUOTES, 'UTF-8')
            . '</small>',
          ],
        ],
        [
          'data' => [
            '#markup' => sprintf(
              '<span style="color:#fff;background:%s;padding:3px 8px;border-radius:4px;font-size:12px;font-weight:600;">%s</span>',
              $statusColor,
              strtoupper((string) $order['payment_status'])
            ),
          ],
        ],
        date('Y-m-d H:i', (int) $order['created']),
      ];
    }

    return [
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('Brak zamówień w systemie.'),
      ],
      '#attached' => [
        'library' => ['architect_studio/studio-styles'],
      ],
    ];
  }

  /**
   * Lista zapytań o adaptację i modyfikacje projektów.
   *
   * @return array<string, mixed>
   *   Tablica strony zapytań o adaptację.
   */
  public function inquiries(): array {
    $inquiries = $this->inquiryRepository->getAll();

    $header = [
      $this->t('ID'),
      $this->t('Inwestor'),
      $this->t('Typ usługi'),
      $this->t('Lokalizacja działki'),
      $this->t('Projekt gotowy'),
      $this->t('Zakres zmian / Wiadomość'),
      $this->t('Data'),
    ];

    $rows = [];
    foreach ($inquiries as $inq) {
      $rows[] = [
        '#' . $inq['id'],
        [
          'data' => [
            '#markup' => htmlspecialchars((string) $inq['customer_name'], ENT_QUOTES, 'UTF-8')
            . '<br><small style="color:#64748b;">'
            . htmlspecialchars((string) $inq['customer_email'], ENT_QUOTES, 'UTF-8')
            . '<br>'
            . htmlspecialchars((string) $inq['customer_phone'], ENT_QUOTES, 'UTF-8')
            . '</small>',
          ],
        ],
        (string) $inq['inquiry_type'],
        (string) ($inq['plot_location'] ?? '-'),
        (string) ($inq['project_link'] ?? '-'),
        [
          'data' => [
            '#markup' => '<div style="max-width:320px;font-size:13px;line-height:1.4;">'
            . nl2br(htmlspecialchars((string) $inq['message'], ENT_QUOTES, 'UTF-8'))
            . '</div>',
          ],
        ],
        date('Y-m-d H:i', (int) $inq['created']),
      ];
    }

    return [
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('Brak zapytań o adaptację.'),
      ],
      '#attached' => [
        'library' => ['architect_studio/studio-styles'],
      ],
    ];
  }

}
