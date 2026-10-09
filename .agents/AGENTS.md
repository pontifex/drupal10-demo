# Workspace Agents Configuration (.agents/AGENTS.md)

Workspace Root: `/home/lwaw/work/drupal10-demo`
Project: Architekt Studio - Serwis wizytówkowy, katalog projektów i płatności BLIK (Drupal 10)

## Instrukcje dla agentów AI

1. Przestrzegaj struktury modułów Drupal 10 (`web/modules/custom/`).
2. Każda nowa funkcjonalność biznesowa (projekty, zamówienia, płatności BLIK, wycena adaptacji) powinna posiadać testy jednostkowe (`tests/src/Unit`) lub integracyjne.
3. Przed zakończeniem pracy uruchom narzędzia do analizy statycznej:
   - PHPStan: `docker compose exec drupal vendor/bin/phpstan analyse web/modules/custom/architect_studio`
   - PHPCS: `docker compose exec drupal vendor/bin/phpcs --standard=Drupal web/modules/custom/architect_studio`
4. Upewnij się, że testy jednostkowe/integracyjne przechodzą bez błędów.
5. Przygotuj Pull Request na GitHub i przekaż link użytkownikowi.
