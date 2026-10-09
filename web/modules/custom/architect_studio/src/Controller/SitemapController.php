<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Controller;

use Drupal\architect_studio\Repository\ProjectRepository;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kontroler generujący dynamiczną mapę witryny sitemap.xml.
 */
final class SitemapController extends ControllerBase {

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
   * Generuje i zwraca zawartość pliku sitemap.xml.
   */
  public function sitemap(Request $request): Response {
    $baseUrl = $request->getSchemeAndHttpHost();
    $projects = $this->projectRepository->getAll(FALSE);

    $urls = [
      [
        'loc' => $baseUrl . '/architekt',
        'priority' => '1.0',
        'changefreq' => 'daily',
        'lastmod' => date('Y-m-d'),
      ],
      [
        'loc' => $baseUrl . '/projekty',
        'priority' => '0.9',
        'changefreq' => 'daily',
        'lastmod' => date('Y-m-d'),
      ],
    ];

    foreach ($projects as $project) {
      $urls[] = [
        'loc' => $baseUrl . '/projekty/' . $project['id'],
        'priority' => '0.8',
        'changefreq' => 'weekly',
        'lastmod' => date('Y-m-d', (int) ($project['updated'] ?? time())),
      ];
    }

    $urls[] = [
      'loc' => $baseUrl . '/regulamin',
      'priority' => '0.3',
      'changefreq' => 'monthly',
      'lastmod' => '2026-01-01',
    ];

    $urls[] = [
      'loc' => $baseUrl . '/polityka-prywatnosci',
      'priority' => '0.3',
      'changefreq' => 'monthly',
      'lastmod' => '2026-01-01',
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ($urls as $url) {
      $xml .= "  <url>\n";
      $xml .= '    <loc>' . htmlspecialchars($url['loc']) . "</loc>\n";
      $xml .= '    <lastmod>' . htmlspecialchars($url['lastmod']) . "</lastmod>\n";
      $xml .= '    <changefreq>' . htmlspecialchars($url['changefreq']) . "</changefreq>\n";
      $xml .= '    <priority>' . htmlspecialchars($url['priority']) . "</priority>\n";
      $xml .= "  </url>\n";
    }

    $xml .= '</urlset>';

    return new Response($xml, 200, [
      'Content-Type' => 'application/xml; charset=UTF-8',
      'X-Robots-Tag' => 'noindex',
    ]);
  }

}
