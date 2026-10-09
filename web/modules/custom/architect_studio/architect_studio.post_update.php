<?php

/**
 * @file
 * Post-update functions for architect_studio module.
 */

declare(strict_types=1);

/**
 * Usuwa pozostałości demonstracyjnych widoków i czyści menu główne.
 */
function architect_studio_post_update_cleanup_demo_views_and_menu(): void {
  $config_factory = \Drupal::configFactory();
  foreach (['views.view.featured_articles', 'views.view.articles_aside', 'views.view.recipe_collections'] as $config_name) {
    $config = $config_factory->getEditable($config_name);
    if (!$config->isNew()) {
      $config->delete();
    }
  }

  /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
  $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
  if ($menu_link_manager->hasDefinition('standard.front_page')) {
    $menu_link_manager->updateDefinition('standard.front_page', ['enabled' => 0]);
  }
}

/**
 * Wgrywa nowe przykładowe projekty bazowane na serwisie Extradom.pl.
 */
function architect_studio_post_update_load_extradom_projects(): void {
  $database = \Drupal::database();
  $time = \Drupal::time()->getRequestTime();

  // Wyczyść dotychczasowe przykładowe projekty demo i wgraj nowe.
  $database->truncate('architect_projects')->execute();

  $projects = [
    [
      'code' => 'KRD-2489',
      'title' => 'Projekt domu Gloria (1351)',
      'category' => 'dom_z_poddaszem',
      'usable_area' => 135.00,
      'building_area' => 135.90,
      'roof_angle' => 40.0,
      'building_height' => 9.00,
      'min_lot_width' => 19.00,
      'min_lot_length' => 22.20,
      'rooms_count' => 5,
      'bathrooms_count' => 2,
      'garage' => '1-stanowiskowy w bryle',
      'heating_type' => 'Pompa ciepła powietrze-woda + ogrzewanie podłogowe (możliwość kotła gazowego)',
      'price_digital' => 4290.00,
      'price_print' => 5090.00,
      'is_hidden' => 0,
      'description' => 'Klasyczny, reprezentacyjny dom jednorodzinny z poddaszem użytkowym i dachem czterospadowym (kopertowym), inspirowany bestsellerowym projektem z Extradom.pl. Na parterze zaprojektowano jasną strefę dzienną z kominkiem i wyjściem na zadaszony taras, kuchnię ze spiżarnią, dodatkowy pokój/gabinet oraz łazienkę. Z wiatrołapu dostępny jest garaż i kotłownia. Poddasze mieści trzy wygodne sypialnie, garderobę oraz dużą łazienkę z wanną i prysznicem.',
      'image_url' => '/modules/custom/architect_studio/images/gloria1351.jpg',
      'floor_plan_url' => '/modules/custom/architect_studio/images/plan_gloria1351.svg',
      'created' => $time,
      'updated' => $time,
    ],
    [
      'code' => 'BSB-1083',
      'title' => 'Projekt domu Maja (BSB1083)',
      'category' => 'dom_parterowy',
      'usable_area' => 78.68,
      'building_area' => 135.83,
      'roof_angle' => 30.0,
      'building_height' => 7.29,
      'min_lot_width' => 21.28,
      'min_lot_length' => 20.49,
      'rooms_count' => 3,
      'bathrooms_count' => 1,
      'garage' => '1-stanowiskowy w bryle',
      'heating_type' => 'Pompa ciepła + ogrzewanie podłogowe + rekuperacja',
      'price_digital' => 4490.00,
      'price_print' => 5300.00,
      'is_hidden' => 0,
      'description' => 'Popularny, energooszczędny dom parterowy z dachem dwuspadowym, znany z serwisu Extradom.pl. Doskonały wybór dla rodziny szukającej ekonomicznego i funkcjonalnego domu bez barier architektonicznych: słoneczny salon z kominkiem, otwarta kuchnia z jadalnią, dwie ustawne sypialnie, łazienka oraz jednostanowiskowy garaż zintegrowany z bryłą budynku.',
      'image_url' => '/modules/custom/architect_studio/images/maja_bsb1083.jpg',
      'floor_plan_url' => '/modules/custom/architect_studio/images/plan_maja_bsb1083.svg',
      'created' => $time - 3600,
      'updated' => $time - 3600,
    ],
    [
      'code' => 'WAW-1192',
      'title' => 'Willa Optima 1 (WAW1192)',
      'category' => 'dom_pietrowy',
      'usable_area' => 169.30,
      'building_area' => 148.50,
      'roof_angle' => 25.0,
      'building_height' => 8.85,
      'min_lot_width' => 22.40,
      'min_lot_length' => 24.80,
      'rooms_count' => 5,
      'bathrooms_count' => 3,
      'garage' => '2-stanowiskowy w bryle',
      'heating_type' => 'Pompa ciepła gruntowa + rekuperacja z odzyskiem wilgoci + fotowoltaika',
      'price_digital' => 8290.00,
      'price_print' => 9830.00,
      'is_hidden' => 0,
      'description' => 'Luksusowa, pełnopiętrowa willa miejska bez skosów na poddaszu, wzorowana na projekcie z Extradom.pl. Przestronna strefa dzienna z panoramicznymi oknami HS, kuchnia ze spiżarnią, gabinet do pracy na parterze oraz obszerny dwustanowiskowy garaż. Na piętrze ekskluzywny master bedroom z własną łazienką i garderobą, dwie duże sypialnie i tarasy widokowe.',
      'image_url' => '/modules/custom/architect_studio/images/willa_optima1.jpg',
      'floor_plan_url' => '/modules/custom/architect_studio/images/plan_willa_optima1.png',
      'created' => $time - 7200,
      'updated' => $time - 7200,
    ],
    [
      'code' => 'HFX-1050',
      'title' => 'Nowoczesna Stodoła 170 (HFX1050)',
      'category' => 'dom_z_poddaszem',
      'usable_area' => 175.12,
      'building_area' => 128.60,
      'roof_angle' => 35.0,
      'building_height' => 8.50,
      'min_lot_width' => 21.30,
      'min_lot_length' => 16.80,
      'rooms_count' => 4,
      'bathrooms_count' => 3,
      'garage' => 'Wiata garażowa z panelami fotowoltaicznymi',
      'heating_type' => 'Pompa ciepła powietrze-woda + instalacja PV 10 kWp + klimatyzacja',
      'price_digital' => 5190.00,
      'price_print' => 5990.00,
      'is_hidden' => 0,
      'description' => 'Nowoczesna interpretacja domu z dwuspadowym, bezokapowym dachem w stylu „modern barn”. Imponująca przeszklona ściana szczytowa otwiera salon na ogród. Pustka nad strefą dzienną z antresolą dodaje niesamowitej przestrzeni. Wykończenie elewacji deską modrzewiową i blachą na rąbek stojący.',
      'image_url' => '/modules/custom/architect_studio/images/stodola170.png',
      'floor_plan_url' => '/modules/custom/architect_studio/images/plan_debowa_polana.svg',
      'created' => $time - 10800,
      'updated' => $time - 10800,
    ],
    [
      'code' => 'GOS-54',
      'title' => 'Budynek Gospodarczy z Garażem i Warsztatem G-54',
      'category' => 'gospodarczy_garaz',
      'usable_area' => 54.30,
      'building_area' => 64.00,
      'roof_angle' => 30.0,
      'building_height' => 5.60,
      'min_lot_width' => 16.00,
      'min_lot_length' => 18.00,
      'rooms_count' => 2,
      'bathrooms_count' => 1,
      'garage' => 'Garaż + warsztat',
      'heating_type' => 'Klimatyzator z funkcją grzania / pompa ciepła powietrze-powietrze',
      'price_digital' => 1900.00,
      'price_print' => 2300.00,
      'is_hidden' => 0,
      'description' => 'Funkcjonalny budynek gospodarczy z wydzielonym stanowiskiem garażowym, warsztatem oraz poddaszem nieużytkowym jako schowek sezonowy. Doskonały do uzupełnienia zagospodarowania działki.',
      'image_url' => '/modules/custom/architect_studio/images/garaz_g54.svg',
      'floor_plan_url' => '/modules/custom/architect_studio/images/plan_garaz_g54.svg',
      'created' => $time - 14400,
      'updated' => $time - 14400,
    ],
  ];

  foreach ($projects as $project) {
    $database->insert('architect_projects')
      ->fields($project)
      ->execute();
  }
}
