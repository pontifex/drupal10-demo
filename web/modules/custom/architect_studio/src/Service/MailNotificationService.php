<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Serwis wysyłający powiadomienia e-mail o zapytaniach i zamówieniach.
 */
class MailNotificationService {

  public function __construct(
    protected MailManagerInterface $mailManager,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Pobiera adres e-mail pracowni architektonicznej.
   */
  public function getArchitectEmail(): string {
    $siteMail = (string) $this->configFactory->get('system.site')->get('mail');
    return !empty($siteMail) ? $siteMail : 'kontakt@architekt-krasnik.pl';
  }

  /**
   * Wysyła powiadomienie do architekta o nowym zapytaniu o wycenę adaptacji.
   *
   * @param array<string, mixed> $inquiry
   *   Dane zapytania.
   *
   * @return bool
   *   Czy wysłanie powiodło się.
   */
  public function notifyArchitectInquiry(array $inquiry): bool {
    $to = $this->getArchitectEmail();
    $params = [
      'inquiry' => $inquiry,
    ];
    $result = $this->mailManager->mail('architect_studio', 'inquiry_notify_architect', $to, 'pl', $params, NULL, TRUE);
    return (bool) ($result['result'] ?? FALSE);
  }

  /**
   * Wysyła potwierdzenie do klienta o przyjęciu zapytania o adaptację.
   *
   * @param array<string, mixed> $inquiry
   *   Dane zapytania.
   *
   * @return bool
   *   Czy wysłanie powiodło się.
   */
  public function confirmCustomerInquiry(array $inquiry): bool {
    $to = (string) ($inquiry['customer_email'] ?? '');
    if (empty($to)) {
      return FALSE;
    }
    $params = [
      'inquiry' => $inquiry,
    ];
    $result = $this->mailManager->mail('architect_studio', 'inquiry_confirm_customer', $to, 'pl', $params, NULL, TRUE);
    return (bool) ($result['result'] ?? FALSE);
  }

  /**
   * Wysyła powiadomienie do architekta o nowym opłaconym zamówieniu projektu.
   *
   * @param array<string, mixed> $order
   *   Dane zamówienia.
   * @param array<string, mixed>|null $project
   *   Dane projektu.
   *
   * @return bool
   *   Czy wysłanie powiodło się.
   */
  public function notifyArchitectOrder(array $order, ?array $project = NULL): bool {
    $to = $this->getArchitectEmail();
    $params = [
      'order' => $order,
      'project' => $project,
    ];
    $result = $this->mailManager->mail('architect_studio', 'order_notify_architect', $to, 'pl', $params, NULL, TRUE);
    return (bool) ($result['result'] ?? FALSE);
  }

  /**
   * Wysyła potwierdzenie do klienta o pomyślnej płatności BLIK za projekt.
   *
   * @param array<string, mixed> $order
   *   Dane zamówienia.
   * @param array<string, mixed>|null $project
   *   Dane projektu.
   *
   * @return bool
   *   Czy wysłanie powiodło się.
   */
  public function confirmCustomerOrder(array $order, ?array $project = NULL): bool {
    $to = (string) ($order['customer_email'] ?? '');
    if (empty($to)) {
      return FALSE;
    }
    $params = [
      'order' => $order,
      'project' => $project,
    ];
    $result = $this->mailManager->mail('architect_studio', 'order_confirm_customer', $to, 'pl', $params, NULL, TRUE);
    return (bool) ($result['result'] ?? FALSE);
  }

}
