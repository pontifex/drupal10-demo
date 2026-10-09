<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Form;

use Drupal\architect_studio\Repository\InquiryRepository;
use Drupal\architect_studio\Service\MailNotificationService;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formularz zapytania o adaptację projektu i modyfikacje budowlane.
 */
final class AdaptationInquiryForm extends FormBase {

  public function __construct(
    protected InquiryRepository $inquiryRepository,
    protected FloodInterface $flood,
    protected MailNotificationService $mailNotification,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('architect_studio.inquiry_repository'),
      $container->get('flood'),
      $container->get('architect_studio.mail_notification')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'architect_studio_adaptation_inquiry_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   Struktura formularza.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Stan formularza.
   *
   * @return array<string, mixed>
   *   Wygenerowany formularz.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attributes']['class'][] = 'architect-inquiry-form';

    $form['customer_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Imię i nazwisko'),
      '#required' => TRUE,
      '#placeholder' => 'np. Jan Kowalski',
    ];

    $form['customer_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Adres e-mail'),
      '#required' => TRUE,
      '#placeholder' => 'jan.kowalski@example.pl',
    ];

    $form['customer_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Numer telefonu'),
      '#required' => TRUE,
      '#placeholder' => '+48 600 000 000',
    ];

    $form['inquiry_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Rodzaj usługi'),
      '#options' => [
        'adaptacja' => $this->t('Adaptacja projektu gotowego do działki (PZT, MPZP/WZ)'),
        'modyfikacje' => $this->t('Zmiany i modyfikacje (zamiana na pompę ciepła, okna, ściany)'),
        'kompleksowo' => $this->t('Kompleksowo: Adaptacja do działki + Modyfikacje projektu'),
        'wycena' => $this->t('Indywidualna konsultacja architektoniczna'),
      ],
      '#default_value' => 'adaptacja',
      '#required' => TRUE,
    ];

    $form['project_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link lub nazwa projektu gotowego'),
      '#description' => $this->t('Wklej link do projektu z dowolnego biura (np. Archon, Murator, Z500 itp.) lub podaj nazwę.'),
      '#placeholder' => 'https://... lub nazwa projektu',
    ];

    $form['plot_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lokalizacja działki i warunki zabudowy'),
      '#description' => $this->t('Miejscowość, gmina lub numer działki; czy działka posiada MPZP czy decyzję o Warunkach Zabudowy (WZ)?'),
      '#placeholder' => 'np. Piaseczno, dz. nr 124/2, obowiązuje MPZP',
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Opis planowanych zmian lub pytania'),
      '#description' => $this->t('Opisz pożądane modyfikacje (np. zmiana kotła gazowego na pompę ciepła, zmiana wymiarów okien, podpiwniczenie, strop monolityczny).'),
      '#rows' => 4,
      '#required' => TRUE,
      '#placeholder' => 'Chciałbym zaadaptować projekt do działki oraz zmienić projektowane ogrzewanie gazowe na pompę ciepła powietrze-woda z ogrzewaniem podłogowym...',
    ];

    // Pole pułapka (honeypot) przeciwko automatom spamującym.
    $form['architect_hp_company'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Firma (pozostaw puste)'),
      '#attributes' => [
        'tabindex' => '-1',
        'autocomplete' => 'off',
        'aria-hidden' => 'true',
      ],
      '#wrapper_attributes' => [
        'style' => 'position: absolute !important; left: -9999px !important; width: 1px !important; height: 1px !important; overflow: hidden !important;',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Wyślij zapytanie o bezpłatną wycenę'),
      '#attributes' => [
        'class' => ['btn', 'btn-primary', 'btn-architect-submit'],
      ],
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
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // 1. Weryfikacja pola Honeypot.
    $honeypot = trim((string) $form_state->getValue('architect_hp_company'));
    if ($honeypot !== '') {
      $form_state->setErrorByName('architect_hp_company', $this->t('Wykryto nieprawidłowe wypełnienie formularza.'));
      return;
    }

    // 2. Ochrona Flood Control (max 5 zapytań na godzinę z jednego IP).
    $clientIp = $this->getRequest()->getClientIp() ?? 'unknown';
    if (!$this->flood->isAllowed('architect_studio.inquiry', 5, 3600, $clientIp)) {
      $form_state->setErrorByName('customer_email', $this->t('Z Twojego adresu IP przesłano zbyt wiele zapytań w krótkim czasie. Odczekaj chwilę przed kolejną próbą lub zadzwoń bezpośrednio do pracowni.'));
      return;
    }

    $email = (string) $form_state->getValue('customer_email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('customer_email', $this->t('Podaj poprawny adres e-mail.'));
    }

    $phone = (string) $form_state->getValue('customer_phone');
    if (strlen(preg_replace('/\D/', '', $phone) ?? '') < 7) {
      $form_state->setErrorByName('customer_phone', $this->t('Podaj poprawny numer telefonu.'));
    }
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
    $clientIp = $this->getRequest()->getClientIp() ?? 'unknown';
    $this->flood->register('architect_studio.inquiry', 3600, $clientIp);

    $data = [
      'customer_name' => (string) $form_state->getValue('customer_name'),
      'customer_email' => (string) $form_state->getValue('customer_email'),
      'customer_phone' => (string) $form_state->getValue('customer_phone'),
      'inquiry_type' => (string) $form_state->getValue('inquiry_type'),
      'project_link' => (string) $form_state->getValue('project_link'),
      'plot_location' => (string) $form_state->getValue('plot_location'),
      'message' => (string) $form_state->getValue('message'),
      'status' => 'new',
    ];

    $this->inquiryRepository->create($data);

    // Powiadomienie e-mail do architekta oraz potwierdzenie dla klienta.
    $this->mailNotification->notifyArchitectInquiry($data);
    $this->mailNotification->confirmCustomerInquiry($data);

    $this->messenger()->addStatus($this->t('Dziękujemy za przesłanie zapytania! Architekt skontaktuje się z Tobą w ciągu 24h z analizą możliwości adaptacji i wyceną zmian.'));
  }

}
