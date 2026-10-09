# Wytyczne Projektu i Konfiguracja Agentów (AGENTS.md)

Repozytorium: `pontifex/drupal10-demo`
Środowisko: **Drupal 10.6+**, PHP 8.4, MariaDB 10.11 (Docker Compose)

---

## 1. Architektura i Kontekst Biznesowy

Projekt realizuje stronę internetową dla **architekta**:
1. **Strona wizytówkowa i oferta usług**:
   - Adaptacja gotowych projektów domów i budynków gospodarczych do warunków działki (MPZP / WZ, PZT, strefy przemarzania, nośność gruntu).
   - Wprowadzanie modyfikacji i zmian w projektach (zamiana ogrzewania gazowego na pompę ciepła, modyfikacja układu okien, ścian działowych/nośnych zgodnie z Prawem Budowlanym).
   - Formularz wyceny adaptacji i konsultacji z architektem.
2. **Katalog projektów autorskich**:
   - Prezentacja własnych projektów architektonicznych (powierzchnia użytkowa, zabudowy, kąt dachu, minimalne wymiary działki, liczba pokoi, rzuty i wizualizacje).
   - Możliwość dodawania, edycji oraz ukrywania/pokazywania projektów (status publikacji).
3. **Lekki moduł zamówień z płatnościami BLIK**:
   - Dwa warianty zakupu: **Wersja drukowana (4 egzemplarze do urzędu)** z wysyłką oraz **Wersja cyfrowa (PDF/CAD)**.
   - Płatności online z naciskiem na **BLIK** (Przelewy24 / PayU / Autopay) oraz tryb symulacji/testowy.

---

## 2. Standardy Kodowania (Coding Standards)

- Wszystkie moduły niestandardowe umieszczamy w `web/modules/custom/<module_name>`.
- Standardy kodu: **Drupal**, **DrupalPractice** oraz **PSR-12**.
- Wszystkie klasy PHP muszą posiadać deklaracje typów (`declare(strict_types=1);` lub ścisłe typowanie argumentów i zwracanych wartości).
- Tłumaczenia: Wszystkie ciągi tekstowe widoczne dla użytkownika powinny być owinięte w `t()` w PHP lub `{{ 'Tekst'|t }}` w szablonach Twig.

---

## 3. Narzędzia do Analizy Statycznej i Jakości Kodu

- **PHPStan**:
  ```bash
  docker compose exec drupal vendor/bin/phpstan analyse web/modules/custom/architect_studio
  ```
- **PHP_CodeSniffer (Drupal standard)**:
  ```bash
  docker compose exec drupal vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom/architect_studio
  ```

---

## 4. Testy Jednostkowe, Integracyjne i Funkcjonalne

- Framework testowy: **PHPUnit** (`vendor/bin/phpunit`).
- Lokalizacja testów modułu: `web/modules/custom/architect_studio/tests/src/Unit`, `Kernel`, `Functional`.
- Uruchamianie testów:
  ```bash
  docker compose exec drupal vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/custom/architect_studio/tests
  ```

---

## 5. Przepływ Git i Pull Requesty

- Pracujemy na dedykowanych gałęziach `feature/<nazwa-funkcjonalnosci>`.
- Przed otwarciem PR:
  1. Uruchom analizę statyczną (PHPStan).
  2. Uruchom testy jednostkowe i integracyjne (PHPUnit).
  3. Sprawdź czystość drzewa git.
- Tworzenie Pull Requesta z użyciem `gh`:
  ```bash
  gh pr create --title "..." --body "..."
  ```
