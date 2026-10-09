<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Form;

use Drupal\architect_studio\Repository\OrderRepository;
use Drupal\architect_studio\Repository\ProjectRepository;
use Drupal\architect_studio\Service\PaymentGatewayService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lekki formularz zamówienia projektu z natychmiastową płatnością BLIK.
 */
final class OrderForm extends FormBase {

  /**
   * Projekt, którego dotyczy zamówienie.
   *
   * @var array<string, mixed>|null
   */
  protected ?array $project = NULL;

  public function __construct(
    protected ProjectRepository $projectRepository,
    protected OrderRepository $orderRepository,
    protected PaymentGatewayService $paymentGateway,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('architect_studio.project_repository'),
      $container->get('architect_studio.order_repository'),
      $container->get('architect_studio.payment_gateway')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'architect_studio_order_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   Struktura formularza.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Stan formularza.
   * @param int|null $id
   *   Opcjonalny identyfikator projektu.
   *
   * @return array<string, mixed>
   *   Wygenerowany formularz.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?int $id = NULL): array {
    $projectId = $id ?? (int) $this->getRouteMatch()->getParameter('id');
    $this->project = $this->projectRepository->getById($projectId);

    if (!$this->project) {
      $form['error'] = [
        '#markup' => '<div class="alert alert-danger">' . $this->t('Wybrany projekt nie istnieje lub został wycofany.') . '</div>',
      ];
      return $form;
    }

    $form_state->set('project', $this->project);
    $form['#attributes']['class'][] = 'architect-order-form';

    // Podsumowanie wybranego projektu.
    $form['project_summary'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['order-project-summary-box']],
      'info' => [
        '#markup' => sprintf(
          '<div class="summary-content">
            <h3>Zamawiasz: <strong>%s</strong> (Kod: %s)</h3>
            <p>Powierzchnia użytkowa: <strong>%s m²</strong> | Kategoria: <strong>%s</strong></p>
          </div>',
          htmlspecialchars((string) $this->project['title']),
          htmlspecialchars((string) $this->project['code']),
          htmlspecialchars((string) $this->project['usable_area']),
          htmlspecialchars((string) $this->project['category'])
        ),
      ],
    ];

    // Wybór wariantu projektu.
    $form['variant'] = [
      '#type' => 'radios',
      '#title' => $this->t('Wybierz wariant dokumentacji projektu:'),
      '#options' => [
        'print' => sprintf(
          'Wersja drukowana (4 egzemplarze projektu budowlanego z uprawnieniami do urzędu + wysyłka kurierem GRATIS) – %s zł',
          number_format((float) $this->project['price_print'], 2, ',', ' ')
        ),
        'digital' => sprintf(
          'Wersja cyfrowa (PDF do wglądu i adaptacji własnej, wysyłka e-mailem natychmiast) – %s zł',
          number_format((float) $this->project['price_digital'], 2, ',', ' ')
        ),
      ],
      '#default_value' => 'print',
      '#required' => TRUE,
    ];

    // Sekcja: Dane Inwestora.
    $form['customer_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Dane Inwestora i dostawy'),
    ];

    $form['customer_fieldset']['customer_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Imię i nazwisko'),
      '#required' => TRUE,
      '#placeholder' => 'Jan Kowalski',
    ];

    $form['customer_fieldset']['customer_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Adres e-mail'),
      '#required' => TRUE,
      '#placeholder' => 'jan.kowalski@example.pl',
      '#description' => $this->t('Na ten adres wyślemy potwierdzenie zamówienia oraz dokumentację w wariancie cyfrowym.'),
    ];

    $form['customer_fieldset']['customer_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Numer telefonu (dla kuriera)'),
      '#required' => TRUE,
      '#placeholder' => '+48 600 000 000',
    ];

    $form['customer_fieldset']['shipping_address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Adres dostawy (ulica, nr domu, kod pocztowy, miejscowość)'),
      '#rows' => 2,
      '#description' => $this->t('Wymagany przy wyborze wersji drukowanej (4 egzemplarze).'),
      '#placeholder' => 'ul. Polna 15, 05-500 Piaseczno',
    ];

    $form['customer_fieldset']['invoice_nip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NIP (opcjonalnie do faktury VAT)'),
      '#placeholder' => 'np. 1234567890',
    ];

    $form['customer_fieldset']['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Uwagi i bezpłatne zgody na modyfikacje w projekcie'),
      '#rows' => 2,
      '#placeholder' => 'Proszę o dołączenie bezpłatnej pisemnej zgody na zmianę ogrzewania na pompę ciepła oraz modyfikację otworów okiennych...',
      '#description' => $this->t('Wpisz, jakie zgody na zmiany chcesz otrzymać w pakiecie z projektem (np. zmiana źródła ciepła na pompę ciepła, zamiana stropu, rezygnacja z komina).'),
    ];

    // Sekcja: Płatność BLIK.
    $form['payment_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bezpieczna płatność online – BLIK'),
      '#attributes' => ['class' => ['payment-blik-box']],
    ];

    $form['payment_fieldset']['payment_gateway'] = [
      '#type' => 'radios',
      '#title' => $this->t('Wybierz operatora płatności BLIK:'),
      '#options' => $this->paymentGateway->getAvailableGateways(),
      '#default_value' => PaymentGatewayService::GATEWAY_P24,
      '#required' => TRUE,
    ];

    $form['payment_fieldset']['blik_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wprowadź 6-cyfrowy kod BLIK z aplikacji banku'),
      '#description' => $this->t('Wygeneruj kod BLIK w aplikacji swojego banku (np. mBank, PKO BP, Santander, ING itp.) i przepisz go tutaj. Po kliknięciu zatwierdź transakcję w telefonie.'),
      '#required' => TRUE,
      '#maxlength' => 7,
      '#placeholder' => '123 456',
      '#attributes' => [
        'class' => ['input-blik-code'],
        'autocomplete' => 'off',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Zamów projekt i zapłać BLIK-iem'),
      '#attributes' => [
        'class' => ['btn', 'btn-success', 'btn-lg', 'btn-pay-blik'],
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
    $variant = (string) $form_state->getValue('variant');
    $shippingAddress = trim((string) $form_state->getValue('shipping_address'));

    if ($variant === 'print' && empty($shippingAddress)) {
      $form_state->setErrorByName('shipping_address', $this->t('Dla wersji drukowanej (4 egzemplarze) podanie adresu dostawy kurierskiej jest wymagane.'));
    }

    $email = (string) $form_state->getValue('customer_email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('customer_email', $this->t('Podaj poprawny adres e-mail.'));
    }

    $blikCode = (string) $form_state->getValue('blik_code');
    if (!$this->paymentGateway->validateBlikCode($blikCode)) {
      $form_state->setErrorByName('blik_code', $this->t('Kod BLIK musi składać się dokładnie z 6 cyfr.'));
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
    /** @var array<string, mixed>|null $project */
    $project = $form_state->get('project');
    if (!$project) {
      $this->messenger()->addError($this->t('Błąd: brak danych projektu.'));
      return;
    }

    $variant = (string) $form_state->getValue('variant');
    $amount = ($variant === 'print') ? (float) $project['price_print'] : (float) $project['price_digital'];
    $orderNumber = $this->orderRepository->generateOrderNumber();

    $orderData = [
      'order_number' => $orderNumber,
      'project_id' => (int) $project['id'],
      'project_title' => (string) $project['title'],
      'variant' => $variant,
      'amount' => $amount,
      'currency' => 'PLN',
      'customer_name' => (string) $form_state->getValue('customer_name'),
      'customer_email' => (string) $form_state->getValue('customer_email'),
      'customer_phone' => (string) $form_state->getValue('customer_phone'),
      'shipping_address' => (string) $form_state->getValue('shipping_address'),
      'invoice_nip' => (string) $form_state->getValue('invoice_nip'),
      'notes' => (string) $form_state->getValue('notes'),
      'payment_method' => (string) $form_state->getValue('payment_gateway'),
      'payment_status' => 'pending',
    ];

    $orderId = $this->orderRepository->create($orderData);

    // Przetwarzanie płatności BLIK przez bramkę.
    $blikCode = (string) $form_state->getValue('blik_code');
    $gateway = (string) $form_state->getValue('payment_gateway');
    $paymentResult = $this->paymentGateway->processBlikPayment($orderData, $blikCode, $gateway);

    if ($paymentResult['success']) {
      $this->orderRepository->updatePaymentStatus($orderId, 'paid', $paymentResult['transaction_id']);
      $this->messenger()->addStatus($this->t('Płatność BLIK zakończona sukcesem! Twoje zamówienie @nr zostało opłacone.', ['@nr' => $orderNumber]));
      $form_state->setRedirect('architect_studio.order_success', ['order_number' => $orderNumber]);
    }
    else {
      $this->orderRepository->updatePaymentStatus($orderId, 'failed', $paymentResult['transaction_id']);
      $this->messenger()->addError($this->t('Płatność BLIK nie powiodła się: @msg', ['@msg' => $paymentResult['message']]));
    }
  }

}
