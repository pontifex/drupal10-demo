<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Form;

use Drupal\architect_studio\Repository\ProjectRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formularz dodawania i edycji projektu architektonicznego przez architekta.
 */
final class ProjectEditForm extends FormBase {

  public function __construct(
    protected ProjectRepository $projectRepository,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('architect_studio.project_repository'),
      $container->get('entity_type.manager'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'architect_studio_project_edit_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   Struktura formularza.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Stan formularza.
   * @param int|null $id
   *   Identyfikator edytowanego projektu.
   *
   * @return array<string, mixed>
   *   Wygenerowany formularz.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?int $id = NULL): array {
    $projectId = $id;
    $project = $projectId ? $this->projectRepository->getById($projectId) : NULL;
    $form_state->set('project_id', $projectId);

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nazwa projektu'),
      '#default_value' => $project['title'] ?? '',
      '#required' => TRUE,
      '#placeholder' => 'np. Dom Parterowy Moderno 130',
    ];

    $form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Kod katalogowy'),
      '#default_value' => $project['code'] ?? '',
      '#required' => TRUE,
      '#placeholder' => 'np. MOD-130',
    ];

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Kategoria budynku'),
      '#options' => [
        'dom_parterowy' => $this->t('Dom parterowy'),
        'dom_z_poddaszem' => $this->t('Dom z poddaszem użytkowym'),
        'dom_pietrowy' => $this->t('Dom piętrowy (rezydencja)'),
        'gospodarczy_garaz' => $this->t('Budynek gospodarczy / garaż'),
      ],
      '#default_value' => $project['category'] ?? 'dom_parterowy',
      '#required' => TRUE,
    ];

    $form['params'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Parametry techniczne i wymiary'),
    ];

    $form['params']['usable_area'] = [
      '#type' => 'number',
      '#title' => $this->t('Powierzchnia użytkowa (m²)'),
      '#step' => '0.01',
      '#default_value' => $project['usable_area'] ?? 120.0,
      '#required' => TRUE,
    ];

    $form['params']['building_area'] = [
      '#type' => 'number',
      '#title' => $this->t('Powierzchnia zabudowy (m²)'),
      '#step' => '0.01',
      '#default_value' => $project['building_area'] ?? 150.0,
      '#required' => TRUE,
    ];

    $form['params']['roof_angle'] = [
      '#type' => 'number',
      '#title' => $this->t('Kąt nachylenia dachu (stopnie)'),
      '#step' => '0.1',
      '#default_value' => $project['roof_angle'] ?? 35.0,
      '#required' => TRUE,
    ];

    $form['params']['building_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Wysokość budynku (m)'),
      '#step' => '0.01',
      '#default_value' => $project['building_height'] ?? 7.5,
      '#required' => TRUE,
    ];

    $form['params']['min_lot_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimalna szerokość działki (m)'),
      '#step' => '0.01',
      '#default_value' => $project['min_lot_width'] ?? 19.0,
      '#required' => TRUE,
    ];

    $form['params']['min_lot_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimalna długość działki (m)'),
      '#step' => '0.01',
      '#default_value' => $project['min_lot_length'] ?? 22.0,
      '#required' => TRUE,
    ];

    $form['params']['rooms_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Liczba pokoi'),
      '#default_value' => $project['rooms_count'] ?? 4,
      '#required' => TRUE,
    ];

    $form['params']['bathrooms_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Liczba łazienek'),
      '#default_value' => $project['bathrooms_count'] ?? 2,
      '#required' => TRUE,
    ];

    $form['params']['garage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Garaż'),
      '#default_value' => $project['garage'] ?? '1-stanowiskowy w bryle',
    ];

    $form['params']['heating_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rekomendowane ogrzewanie'),
      '#default_value' => $project['heating_type'] ?? 'Pompa ciepła powietrze-woda + podłogówka',
      '#required' => TRUE,
    ];

    $form['pricing'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Ceny wariantów'),
    ];

    $form['pricing']['price_digital'] = [
      '#type' => 'number',
      '#title' => $this->t('Cena wersji cyfrowej PDF (PLN)'),
      '#step' => '0.01',
      '#default_value' => $project['price_digital'] ?? 3200.00,
      '#required' => TRUE,
    ];

    $form['pricing']['price_print'] = [
      '#type' => 'number',
      '#title' => $this->t('Cena wersji drukowanej 4 egz. (PLN)'),
      '#step' => '0.01',
      '#default_value' => $project['price_print'] ?? 3800.00,
      '#required' => TRUE,
    ];

    $form['is_hidden'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ukryj projekt na front-endzie (niewidoczny dla klientów)'),
      '#description' => $this->t('Projekt nie zostanie usunięty z bazy danych, ale nie będzie wyświetlany na stronie głównej ani w katalogu.'),
      '#default_value' => !empty($project['is_hidden']),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Opis projektu i zalety'),
      '#default_value' => $project['description'] ?? '',
      '#rows' => 5,
    ];

    $form['media'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Materiały graficzne (wizualizacje i rzuty)'),
    ];

    $currentImageUrl = (string) ($project['image_url'] ?? '');
    $imagePreview = '';
    if ($currentImageUrl !== '') {
      $imagePreview = '<div class="media-preview" style="margin-bottom:12px;">'
        . '<label style="font-weight:600;display:block;margin-bottom:4px;">'
        . $this->t('Bieżąca wizualizacja:') . '</label>'
        . '<img src="' . htmlspecialchars($currentImageUrl) . '" alt="" '
        . 'style="max-height:140px;max-width:260px;border-radius:6px;border:1px solid #e5e7eb;box-shadow:0 1px 3px rgba(0,0,0,0.1);display:block;">'
        . '</div>';
    }

    $form['media']['image_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Wgraj plik wizualizacji 3D (PNG, JPG, WEBP, SVG)'),
      '#description' => $this->t('Wybierz plik graficzny z dysku komputera (maksymalnie 10 MB).'),
      '#upload_location' => 'public://projects/',
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'png jpg jpeg svg webp',
        ],
        'FileSizeLimit' => [
          'fileLimit' => 10 * 1024 * 1024,
        ],
      ],
      '#prefix' => $imagePreview,
    ];

    $form['media']['image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ścieżka do wizualizacji (lub pozostaw wgrany powyżej plik)'),
      '#default_value' => $project['image_url'] ?? '/modules/custom/architect_studio/images/moderno125.svg',
      '#description' => $this->t('Ścieżka do pliku na serwerze lub zewnętrzny adres URL.'),
    ];

    $currentPlanUrl = (string) ($project['floor_plan_url'] ?? '');
    $planPreview = '';
    if ($currentPlanUrl !== '') {
      $planPreview = '<div class="media-preview" style="margin-bottom:12px;">'
        . '<label style="font-weight:600;display:block;margin-bottom:4px;">'
        . $this->t('Bieżący rzut kondygnacji:') . '</label>'
        . '<img src="' . htmlspecialchars($currentPlanUrl) . '" alt="" '
        . 'style="max-height:140px;max-width:260px;border-radius:6px;border:1px solid #e5e7eb;box-shadow:0 1px 3px rgba(0,0,0,0.1);background:#fafafa;display:block;">'
        . '</div>';
    }

    $form['media']['floor_plan_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Wgraj plik rzutu kondygnacji (SVG, PNG, JPG, WEBP)'),
      '#description' => $this->t('Wybierz rzut z wymiarami pomieszczeń (maksymalnie 10 MB).'),
      '#upload_location' => 'public://projects/',
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'png jpg jpeg svg webp',
        ],
        'FileSizeLimit' => [
          'fileLimit' => 10 * 1024 * 1024,
        ],
      ],
      '#prefix' => $planPreview,
    ];

    $form['media']['floor_plan_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ścieżka do rzutu (lub pozostaw wgrany powyżej plik)'),
      '#default_value' => $project['floor_plan_url'] ?? '/modules/custom/architect_studio/images/plan_moderno125.svg',
      '#description' => $this->t('Ścieżka do rzutu na serwerze lub zewnętrzny adres URL.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $id ? $this->t('Zapisz zmiany w projekcie') : $this->t('Dodaj projekt do katalogu'),
      '#attributes' => ['class' => ['btn', 'btn-primary']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   Struktura formularza.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Stan formularza.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $id = $form_state->get('project_id');

    // Obsługa wgranego pliku wizualizacji głównej.
    $imageUpload = $form_state->getValue('image_upload');
    $imageUrl = trim((string) $form_state->getValue('image_url'));
    if (!empty($imageUpload) && is_array($imageUpload)) {
      $fid = reset($imageUpload);
      if ($fid) {
        $file = $this->entityTypeManager->getStorage('file')->load($fid);
        if ($file instanceof FileInterface) {
          $file->setPermanent();
          $file->save();
          $imageUrl = $this->fileUrlGenerator->generateString($file->getFileUri());
        }
      }
    }

    // Obsługa wgranego pliku rzutu kondygnacji.
    $planUpload = $form_state->getValue('floor_plan_upload');
    $floorPlanUrl = trim((string) $form_state->getValue('floor_plan_url'));
    if (!empty($planUpload) && is_array($planUpload)) {
      $fid = reset($planUpload);
      if ($fid) {
        $file = $this->entityTypeManager->getStorage('file')->load($fid);
        if ($file instanceof FileInterface) {
          $file->setPermanent();
          $file->save();
          $floorPlanUrl = $this->fileUrlGenerator->generateString($file->getFileUri());
        }
      }
    }

    $data = [
      'title' => (string) $form_state->getValue('title'),
      'code' => (string) $form_state->getValue('code'),
      'category' => (string) $form_state->getValue('category'),
      'usable_area' => (float) $form_state->getValue('usable_area'),
      'building_area' => (float) $form_state->getValue('building_area'),
      'roof_angle' => (float) $form_state->getValue('roof_angle'),
      'building_height' => (float) $form_state->getValue('building_height'),
      'min_lot_width' => (float) $form_state->getValue('min_lot_width'),
      'min_lot_length' => (float) $form_state->getValue('min_lot_length'),
      'rooms_count' => (int) $form_state->getValue('rooms_count'),
      'bathrooms_count' => (int) $form_state->getValue('bathrooms_count'),
      'garage' => (string) $form_state->getValue('garage'),
      'heating_type' => (string) $form_state->getValue('heating_type'),
      'price_digital' => (float) $form_state->getValue('price_digital'),
      'price_print' => (float) $form_state->getValue('price_print'),
      'is_hidden' => $form_state->getValue('is_hidden') ? 1 : 0,
      'description' => (string) $form_state->getValue('description'),
      'image_url' => $imageUrl,
      'floor_plan_url' => $floorPlanUrl,
    ];

    if ($id) {
      $data['id'] = (int) $id;
    }

    $this->projectRepository->save($data);

    $this->messenger()->addStatus($this->t('Projekt "%title" został pomyślnie zapisany.', ['%title' => $data['title']]));
    $form_state->setRedirect('architect_studio.admin_projects');
  }

}
