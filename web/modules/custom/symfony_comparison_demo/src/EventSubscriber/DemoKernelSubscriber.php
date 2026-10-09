<?php

namespace Drupal\symfony_comparison_demo\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Standardowy Symfony EventSubscriber działający w cyklu życia HttpKernel Drupala!
 */
class DemoKernelSubscriber implements EventSubscriberInterface {

  /**
   * Dodaje własny nagłówek HTTP do każdej odpowiedzi generowanej przez Drupala.
   */
  public function onResponse(ResponseEvent $event): void {
    $response = $event->getResponse();
    $response->headers->set('X-Drupal-Symfony-Demo', 'Hello from Symfony EventSubscriber in Drupal 10!');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onResponse', 0],
    ];
  }

}
