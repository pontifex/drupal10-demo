<?php

declare(strict_types=1);

namespace Drupal\architect_studio\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Przekierowuje przestarzałe ścieżki /en na czyste polskie adresy URL.
 */
class LanguageRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Obsługuje żądania HTTP przed routingiem.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Zdarzenie żądania jądra.
   */
  public function onKernelRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();
    $path = $request->getPathInfo();

    if ($path === '/en' || str_starts_with($path, '/en/')) {
      $newPath = substr($path, 3);
      if ($newPath === '' || $newPath === '/') {
        $newPath = '/';
      }
      $queryString = $request->getQueryString();
      if ($queryString) {
        $newPath .= '?' . $queryString;
      }
      $event->setResponse(new RedirectResponse($newPath, 301));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 50],
    ];
  }

}
