# Drupal 10 Demo – Przewodnik dla programisty Symfony

Witaj w środowisku **Drupal 10.6** z oficjalnym profilem demonstracyjnym **Umami Food Magazine** oraz dedykowanym modułem porównawczym dla programistów Symfony.

---

## 🚀 Szybki start

Aplikacja działa w kontenerach Docker Compose pod adresem:
- **Strona główna (Demo Umami):** [http://localhost:8080](http://localhost:8080)
- **Dedykowane demo Symfony vs Drupal:** [http://localhost:8080/demo/symfony](http://localhost:8080/demo/symfony)
- **Czysty endpoint Symfony JsonResponse:** [http://localhost:8080/api/v1/demo-recipes](http://localhost:8080/api/v1/demo-recipes)
- **Logowanie do panelu administratora:**
  - Login: `admin`
  - Hasło: `admin123`
  - Jednorazowy link logowania (one-click login): uruchom `./drush uli`

---

## 🛠️ CLI: Drush (Odpowiednik Symfony `bin/console`)

W katalogu głównym masz gotowy skrypt `./drush`:

```bash
# Czyszczenie cache (odpowiednik php bin/console cache:clear)
./drush cr

# Wygenerowanie natychmiastowego linku do zalogowania admina
./drush uli

# Status instalacji Drupala i bazy danych
./drush status

# Lista modułów (włączonych / wyłączonych)
./drush pm:list --type=module

# Eksport konfiguracji całej strony do plików YAML (Configuration Management)
./drush cex -y

# Import konfiguracji z plików YAML do aktywnej bazy
./drush cim -y
```

---

## 🧩 Porównanie: Symfony vs Drupal 10

Drupal od wersji 8 (a obecnie w wersji 10) jest w ogromnym stopniu **zbudowany na komponentach Symfony** (`symfony/http-kernel`, `symfony/dependency-injection`, `symfony/routing`, `symfony/event-dispatcher`, `twig/twig` itp.).

| Symfony | Drupal 10 | Gdzie to znaleźć w projekcie? |
| :--- | :--- | :--- |
| **Bundle / Moduł** | **Moduł Drupala** | `web/modules/custom/*` |
| **`config/routes.yaml`** | **`*.routing.yml`** | [`symfony_comparison_demo.routing.yml`](file:///home/lwaw/work/drupal10-demo/web/modules/custom/symfony_comparison_demo/symfony_comparison_demo.routing.yml) |
| **`config/services.yaml`** | **`*.services.yml`** | [`symfony_comparison_demo.services.yml`](file:///home/lwaw/work/drupal10-demo/web/modules/custom/symfony_comparison_demo/symfony_comparison_demo.services.yml) |
| **Controller** | **`ControllerBase`** | [`DemoController.php`](file:///home/lwaw/work/drupal10-demo/web/modules/custom/symfony_comparison_demo/src/Controller/DemoController.php) |
| **`EventSubscriber`** | **`EventSubscriber`** | [`DemoKernelSubscriber.php`](file:///home/lwaw/work/drupal10-demo/web/modules/custom/symfony_comparison_demo/src/EventSubscriber/DemoKernelSubscriber.php) |
| **`Doctrine ORM`** | **Entity API / Entity Query** | [`DemoService.php`](file:///home/lwaw/work/drupal10-demo/web/modules/custom/symfony_comparison_demo/src/Service/DemoService.php) |
| **Szablony Twig** | **Twig 3** | [`symfony-demo-page.html.twig`](file:///home/lwaw/work/drupal10-demo/web/modules/custom/symfony_comparison_demo/templates/symfony-demo-page.html.twig) |
| **`bin/console`** | **Drush (`./drush`)** | `./drush` |

---

## 📁 Struktura katalogów

- `web/core/` – rdzeń Drupala (traktuj jak `vendor/symfony/*`, nie edytujemy)
- `web/modules/`
  - `custom/` – **Twój własny kod** (np. nasz [`symfony_comparison_demo`](file:///home/lwaw/work/drupal10-demo/web/modules/custom/symfony_comparison_demo))
  - `contrib/` – moduły pobrane z drupal.org przez Composer
- `web/themes/` – motywy graficzne (frontend)
- `web/sites/default/` – konfiguracja środowiska ([`settings.php`](file:///home/lwaw/work/drupal10-demo/web/sites/default/settings.php)) i pliki publiczne (`files/`)
- `composer.json` – zależności projektu (drush, core itp.)

---

## ⚙️ Zarządzanie kontenerami

- Zatrzymanie: `docker compose down`
- Ponowne uruchomienie: `docker compose up -d`
- Logi www: `docker compose logs -f drupal`
