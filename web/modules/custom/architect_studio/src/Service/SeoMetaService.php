<?php

declare(strict_types=1);

namespace Drupal\architect_studio\Service;

use Drupal\architect_studio\Repository\ProjectRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Serwis generowania i dołączania meta tagów SEO oraz mikrodanych Schema.org.
 *
 * Zoptymalizowany pod pozycjonowanie lokalne:
 * Kraśnik, Annopol, powiat kraśnicki, woj. lubelskie.
 */
class SeoMetaService {

  public function __construct(
    protected RequestStack $requestStack,
    protected ProjectRepository $projectRepository,
  ) {}

  /**
   * Dołącza znaczniki SEO (meta, OpenGraph, Schema.org) do strony.
   *
   * @param array<string, mixed> $attachments
   *   Tablica załączników strony (#attached).
   * @param string $routeName
   *   Nazwa bieżącej trasy Drupala.
   * @param array<string, mixed> $parameters
   *   Parametry trasy (np. id projektu).
   */
  public function attachSeoTags(array &$attachments, string $routeName, array $parameters = []): void {
    $request = $this->requestStack->getCurrentRequest();
    $baseUrl = $request ? $request->getSchemeAndHttpHost() : 'https://drupal10-demo-production.up.railway.app';
    $currentUrl = $request ? $request->getUri() : $baseUrl . '/architekt';

    $metaTags = $this->getMetaTagsForRoute($routeName, $parameters, $baseUrl, $currentUrl);

    foreach ($metaTags as $key => $tag) {
      $attachments['#attached']['html_head'][] = [
        $tag,
        'architect_studio_seo_' . $key,
      ];
    }

    $schemaOrg = $this->getSchemaOrgForRoute($routeName, $parameters, $baseUrl, $currentUrl);
    if (!empty($schemaOrg)) {
      foreach ($schemaOrg as $key => $jsonSchema) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'script',
            '#attributes' => [
              'type' => 'application/ld+json',
            ],
            '#value' => (string) json_encode($jsonSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
          ],
          'architect_studio_schema_' . $key,
        ];
      }
    }

    // Resource hints: Preconnect for fonts and Preload for LCP hero image.
    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'link',
        '#attributes' => [
          'rel' => 'preconnect',
          'href' => 'https://fonts.googleapis.com',
        ],
      ],
      'architect_studio_perf_preconnect_fonts_api',
    ];
    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'link',
        '#attributes' => [
          'rel' => 'preconnect',
          'href' => 'https://fonts.gstatic.com',
          'crossorigin' => 'anonymous',
        ],
      ],
      'architect_studio_perf_preconnect_fonts_static',
    ];

    if ($routeName === 'architect_studio.showcase') {
      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'link',
          '#attributes' => [
            'rel' => 'preload',
            'as' => 'image',
            'href' => '/modules/custom/architect_studio/images/willa_optima1.jpg',
            'fetchpriority' => 'high',
          ],
        ],
        'architect_studio_perf_preload_hero_lcp',
      ];
    }
  }

  /**
   * Generuje zestaw meta tagów dla danej podstrony.
   *
   * @param string $routeName
   *   Nazwa trasy.
   * @param array<string, mixed> $parameters
   *   Parametry trasy.
   * @param string $baseUrl
   *   Główny adres URL serwisu.
   * @param string $currentUrl
   *   Bieżący adres URL.
   *
   * @return array<string, array<string, mixed>>
   *   Zestaw meta tagów.
   */
  public function getMetaTagsForRoute(string $routeName, array $parameters, string $baseUrl, string $currentUrl): array {
    $defaultDescription = 'Pracownia Architektoniczna – Architekt Kraśnik i Annopol. Kompleksowa adaptacja projektów gotowych do działki, zmiany w projektach i formalności w Starostwie Powiatowym w Kraśniku. Sprawdź ofertę!';
    $defaultTitle = 'Architekt Kraśnik, Annopol | Pracownia Architektoniczna – Adaptacja Projektów';
    $ogImage = $baseUrl . '/modules/custom/architect_studio/images/willa_optima1.jpg';

    $description = $defaultDescription;
    $title = $defaultTitle;
    $ogType = 'website';

    if ($routeName === 'architect_studio.catalog') {
      $title = 'Katalog Projektów Domów z Adaptacją – Kraśnik, Annopol | Pracownia Architektoniczna';
      $description = 'Autorski katalog projektów domów jednorodzinnych i garaży z adaptacją w Kraśniku i Annopolu. Płatność BLIK, wersja cyfrowa i 4 egzemplarze do urzędu.';
    }
    elseif ($routeName === 'architect_studio.project_detail' && !empty($parameters['id'])) {
      $project = $this->projectRepository->getById((int) $parameters['id']);
      if ($project) {
        $title = sprintf('%s – Projekt z Adaptacją w Kraśniku i Annopolu', (string) $project['title']);
        $description = sprintf('Projekt architektoniczny %s (%s). Powierzchnia %s m². Adaptacja w powiecie kraśnickim, zgodność z MPZP i WZ. Zamów online z płatnością BLIK.', (string) $project['title'], (string) $project['code'], (string) $project['usable_area']);
        if (!empty($project['image_url'])) {
          $ogImage = str_starts_with((string) $project['image_url'], 'http') ? (string) $project['image_url'] : $baseUrl . $project['image_url'];
        }
        $ogType = 'product';
      }
    }
    elseif ($routeName === 'architect_studio.terms') {
      $title = 'Regulamin Serwisu i Sprzedaży Projektów – Architekt Kraśnik';
      $description = 'Regulamin sprzedaży projektów architektonicznych i usług adaptacji w pracowni architektonicznej w Kraśniku. Zasady zamówień, prawa autorskie i płatności BLIK.';
    }
    elseif ($routeName === 'architect_studio.privacy') {
      $title = 'Polityka Prywatności i Informacja o RODO – Pracownia Architektoniczna Kraśnik';
      $description = 'Polityka prywatności i ochrona danych osobowych inwestorów w pracowni architektonicznej w Kraśniku i Annopolu.';
    }

    return [
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'description',
          'content' => $description,
        ],
      ],
      'keywords' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'keywords',
          'content' => 'architekt kraśnik, architekt annopol, pracownia architektoniczna kraśnik, biuro architektoniczne kraśnik, adaptacja projektów kraśnik, adaptacja projektów annopol, projekty domów powiat kraśnicki, starostwo powiatowe kraśnik pozwolenie na budowę',
        ],
      ],
      'canonical' => [
        '#type' => 'html_tag',
        '#tag' => 'link',
        '#attributes' => [
          'rel' => 'canonical',
          'href' => $currentUrl,
        ],
      ],
      // Lokalne tagi geolokalizacyjne dla wyszukiwarek
      // (Kraśnik, woj. lubelskie).
      'geo_region' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'geo.region',
          'content' => 'PL-06',
        ],
      ],
      'geo_placename' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'geo.placename',
          'content' => 'Kraśnik, Annopol, Powiat Kraśnicki',
        ],
      ],
      'geo_position' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'geo.position',
          'content' => '50.9234;22.2274',
        ],
      ],
      'icbm' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'ICBM',
          'content' => '50.9234, 22.2274',
        ],
      ],
      // OpenGraph.
      'og_title' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:title',
          'content' => $title,
        ],
      ],
      'og_description' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:description',
          'content' => $description,
        ],
      ],
      'og_url' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:url',
          'content' => $currentUrl,
        ],
      ],
      'og_type' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:type',
          'content' => $ogType,
        ],
      ],
      'og_image' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:image',
          'content' => $ogImage,
        ],
      ],
      'og_site_name' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:site_name',
          'content' => 'Pracownia Architektoniczna Kraśnik & Annopol',
        ],
      ],
      'og_locale' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:locale',
          'content' => 'pl_PL',
        ],
      ],
      // Twitter Card.
      'twitter_card' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'twitter:card',
          'content' => 'summary_large_image',
        ],
      ],
      'twitter_title' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'twitter:title',
          'content' => $title,
        ],
      ],
      'twitter_description' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'twitter:description',
          'content' => $description,
        ],
      ],
      'twitter_image' => [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'twitter:image',
          'content' => $ogImage,
        ],
      ],
    ];
  }

  /**
   * Generuje strukturę mikrodanych Schema.org (JSON-LD) dla danej trasy.
   *
   * @param string $routeName
   *   Nazwa trasy.
   * @param array<string, mixed> $parameters
   *   Parametry trasy.
   * @param string $baseUrl
   *   Główny adres URL serwisu.
   * @param string $currentUrl
   *   Bieżący adres URL.
   *
   * @return array<string, array<string, mixed>>
   *   Struktury mikrodanych JSON-LD.
   */
  public function getSchemaOrgForRoute(string $routeName, array $parameters, string $baseUrl, string $currentUrl): array {
    $schemas = [];

    // Schemat główny: Architect / LocalBusiness
    // (na stronie głównej oraz w katalogu).
    if ($routeName === 'architect_studio.showcase' || $routeName === 'architect_studio.catalog') {
      $schemas['architect_local_business'] = [
        '@context' => 'https://schema.org',
        '@type' => ['Architect', 'ProfessionalService'],
        '@id' => $baseUrl . '/#architect-studio',
        'name' => 'Pracownia Architektoniczna – Architekt Kraśnik i Annopol',
        'alternateName' => [
          'Biuro Architektoniczne Kraśnik',
          'Architekt Kraśnik Jan Kowalski',
          'Adaptacja Projektów Annopol i Powiat Kraśnicki',
        ],
        'description' => 'Profesjonalne usługi architektoniczne w Kraśniku, Annopolu i powiecie kraśnickim. Kompleksowa adaptacja projektów gotowych (PZT, WZ, MPZP), modyfikacje oraz projekty autorskie.',
        'url' => $baseUrl . '/architekt',
        'telephone' => '+48 22 123 45 67',
        'email' => 'biuro@architekt-studio.pl',
        'priceRange' => '$$',
        'address' => [
          '@type' => 'PostalAddress',
          'streetAddress' => 'ul. Lubelska 14',
          'addressLocality' => 'Kraśnik',
          'postalCode' => '23-200',
          'addressRegion' => 'Lubelskie',
          'addressCountry' => 'PL',
        ],
        'geo' => [
          '@type' => 'GeoCoordinates',
          'latitude' => 50.9234,
          'longitude' => 22.2274,
        ],
        'areaServed' => [
          ['@type' => 'City', 'name' => 'Kraśnik'],
          ['@type' => 'City', 'name' => 'Annopol'],
          ['@type' => 'AdministrativeArea', 'name' => 'Powiat kraśnicki'],
          ['@type' => 'City', 'name' => 'Urzędów'],
          ['@type' => 'AdministrativeArea', 'name' => 'Gmina Dzierzkowice'],
          ['@type' => 'AdministrativeArea', 'name' => 'Gmina Wilkołaz'],
          ['@type' => 'AdministrativeArea', 'name' => 'Gmina Zakrzówek'],
          ['@type' => 'AdministrativeArea', 'name' => 'Gmina Szastarka'],
          ['@type' => 'AdministrativeArea', 'name' => 'Gmina Trzydnik Duży'],
          ['@type' => 'AdministrativeArea', 'name' => 'Gmina Gościeradów'],
        ],
        'openingHoursSpecification' => [
          [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'opens' => '08:00',
            'closes' => '18:00',
          ],
        ],
        'paymentAccepted' => 'BLIK, Przelew bankowy, Gotówka',
      ];

      // Schemat FAQPage dla strony głównej.
      if ($routeName === 'architect_studio.showcase') {
        $schemas['faq_page'] = [
          '@context' => 'https://schema.org',
          '@type' => 'FAQPage',
          'mainEntity' => [
            [
              '@type' => 'Question',
              'name' => 'Ile kosztuje adaptacja projektu gotowego w Kraśniku i Annopolu?',
              'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Koszt adaptacji typowego projektu domu w Kraśniku, Annopolu i powiecie kraśnickim wynosi zazwyczaj od 2 500 zł do 4 500 zł. Cena obejmuje sporządzenie Projektu Zagospodarowania Terenu (PZT) na mapie do celów projektowych, dostosowanie fundamentów do strefy przemarzania dla woj. lubelskiego (1,0 m) oraz weryfikację z ustaleniami MPZP lub decyzją o Warunkach Zabudowy (WZ).',
              ],
            ],
            [
              '@type' => 'Question',
              'name' => 'Jakie dokumenty są potrzebne do pozwolenia na budowę w Starostwie Powiatowym w Kraśniku?',
              'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'W Wydziale Architektury i Budownictwa Starostwa Powiatowego w Kraśniku wymagane są: 3 egzemplarze projektu budowlanego (PZT i PAB), decyzja o Warunkach Zabudowy lub wypis z MPZP, oświadczenie o prawie do dysponowania nieruchomością na cele budowlane oraz warunki techniczne przyłączy mediów.',
              ],
            ],
            [
              '@type' => 'Question',
              'name' => 'Czy pracownia obsługuje inwestycje w gminie Annopol?',
              'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Tak, wykonujemy adaptacje projektów oraz indywidualne projekty architektoniczne na terenie miasta i gminy Annopol, w tym z uwzględnieniem specyficznych warunków gruntowo-wodnych w rejonie doliny Wisły.',
              ],
            ],
            [
              '@type' => 'Question',
              'name' => 'Czy do zakupionego projektu autorskiego dołączana jest bezpłatna zgoda na zmiany?',
              'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Tak, każdy projekt zakupiony w naszej pracowni (w wersji drukowanej lub PDF z płatnością BLIK) zawiera pisemną, bezpłatną zgodę autora na modyfikacje: zamianę ogrzewania na pompę ciepła, zmianę układu okien czy przesunięcie ścian działowych.',
              ],
            ],
          ],
        ];
      }
    }
    elseif ($routeName === 'architect_studio.project_detail' && !empty($parameters['id'])) {
      $project = $this->projectRepository->getById((int) $parameters['id']);
      if ($project) {
        $img = str_starts_with((string) $project['image_url'], 'http') ? (string) $project['image_url'] : $baseUrl . $project['image_url'];
        $schemas['product_project'] = [
          '@context' => 'https://schema.org',
          '@type' => 'Product',
          'name' => (string) $project['title'],
          'description' => (string) $project['description'],
          'sku' => (string) $project['code'],
          'image' => $img,
          'brand' => [
            '@type' => 'Brand',
            'name' => 'Pracownia Architektoniczna Kraśnik & Annopol',
          ],
          'offers' => [
            '@type' => 'AggregateOffer',
            'priceCurrency' => 'PLN',
            'lowPrice' => (float) $project['price_digital'],
            'highPrice' => (float) $project['price_print'],
            'offerCount' => 2,
            'availability' => 'https://schema.org/InStock',
            'url' => $currentUrl,
          ],
        ];
      }
    }

    return $schemas;
  }

}
