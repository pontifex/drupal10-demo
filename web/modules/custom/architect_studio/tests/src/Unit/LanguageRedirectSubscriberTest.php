<?php

declare(strict_types=1);

namespace Drupal\Tests\architect_studio\Unit;

use Drupal\architect_studio\EventSubscriber\LanguageRedirectSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Testy jednostkowe dla LanguageRedirectSubscriber.
 *
 * @group architect_studio
 */
class LanguageRedirectSubscriberTest extends UnitTestCase {

  /**
   * Test przekierowania /en do /.
   */
  public function testRedirectsEnRootToCleanSlash(): void {
    $kernel = $this->createMock(HttpKernelInterface::class);
    $request = Request::create('/en');
    $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

    $subscriber = new LanguageRedirectSubscriber();
    $subscriber->onKernelRequest($event);

    $response = $event->getResponse();
    $this->assertNotNull($response);
    $this->assertSame(301, $response->getStatusCode());
    $this->assertSame('/', $response->headers->get('location'));
  }

  /**
   * Test przekierowania /en/projekty do /projekty.
   */
  public function testRedirectsEnSubpath(): void {
    $kernel = $this->createMock(HttpKernelInterface::class);
    $request = Request::create('/en/projekty', 'GET', ['category' => 'dom_parterowy']);
    $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

    $subscriber = new LanguageRedirectSubscriber();
    $subscriber->onKernelRequest($event);

    $response = $event->getResponse();
    $this->assertNotNull($response);
    $this->assertSame(301, $response->getStatusCode());
    $this->assertSame('/projekty?category=dom_parterowy', $response->headers->get('location'));
  }

  /**
   * Test braku przekierowania dla zwykłych ścieżek.
   */
  public function testNoRedirectForStandardPaths(): void {
    $kernel = $this->createMock(HttpKernelInterface::class);
    $request = Request::create('/projekty');
    $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

    $subscriber = new LanguageRedirectSubscriber();
    $subscriber->onKernelRequest($event);

    $this->assertNull($event->getResponse());
  }

  /**
   * Test zarejestrowanych zdarzeń.
   */
  public function testSubscribedEvents(): void {
    $events = LanguageRedirectSubscriber::getSubscribedEvents();
    $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
    $this->assertSame(50, $events[KernelEvents::REQUEST][1]);
  }

}
