# Pracownia Architektoniczna – Serwis dla Architekta (Drupal 10)

Projekt dedykowany dla **architekta**: usługi adaptacji projektów gotowych do warunków działki inwestora, wprowadzanie modyfikacji (np. zmiana źródła ogrzewania na pompę ciepła, zmiany układu okien i ścian), katalog autorskich projektów oraz lekki moduł sprzedaży z płatnościami **BLIK**.

---

## 🚀 Szybki start

Aplikacja działa w kontenerach Docker Compose pod adresem:
- **Strona główna (Wizytówka i oferta architekta):** [http://localhost:8080](http://localhost:8080)
- **Katalog projektów autorskich:** [http://localhost:8080/projekty](http://localhost:8080/projekty)
- **Zamówienie projektu z płatnością BLIK:** [http://localhost:8080/projekty/1/zamow](http://localhost:8080/projekty/1/zamow)
- **Formularz wyceny adaptacji i konsultacji:** [http://localhost:8080/architekt#wycena](http://localhost:8080/architekt#wycena)

### Panel administracyjny architekta:
- **Katalog projektów (dodawanie, edycja, ukrywanie/pokazywanie):** [http://localhost:8080/admin/architect/projekty](http://localhost:8080/admin/architect/projekty)
- **Złożone zamówienia i transakcje BLIK:** [http://localhost:8080/admin/architect/zamowienia](http://localhost:8080/admin/architect/zamowienia)
- **Zgłoszenia inwestorów (adaptacja i modyfikacje):** [http://localhost:8080/admin/architect/zapytania](http://localhost:8080/admin/architect/zapytania)
- **Logowanie jednorazowe:** `./drush uli`

---

## 🏛️ Funkcjonalności Biznesowe

### 1. Część Wizytówkowa i Oferta Usług
- **Adaptacja projektów gotowych**:
  - Weryfikacja ustaleń MPZP (Planu Miejscowego) lub decyzji WZ (Warunków Zabudowy).
  - Sporządzenie Projektu Zagospodarowania Terenu (PZT) na mapie do celów projektowych.
  - Dostosowanie fundamentów do strefy przemarzania i warunków geotechnicznych.
  - Uzgodnienia zjazdów i przyłączy mediów.
- **Modyfikacje i zmiany w projektach**:
  - Zamiana źródła ciepła (np. kocioł gazowy $\to$ pompa ciepła z podłogówką i fotowoltaiką).
  - Zmiana układu stolarki okiennej i drzwiowej (np. duże przeszklenia przesuwne HS, okna dachowe).
  - Modyfikacja ścian działowych, nośnych, stropów i poddasza zgodnie z Prawem Budowlanym.
  - Pisemne zgody na zmiany wydawane w pakiecie z projektem.
- **Formularz bezpłatnej wyceny**:
  - Inwestor przesyła dane działki oraz link do projektu z dowolnego biura.

### 2. Katalog Projektów Autorskich
- Parametry techniczne: powierzchnia użytkowa, zabudowy, kąt dachu, wysokość, min. wymiary działki, liczba pokoi, łazienek, rodzaj ogrzewania.
- Rzuty techniczne kondygnacji (blueprint) oraz wizualizacje 3D.
- Dwa warianty dokumentacji:
  - **Wersja drukowana (4 egzemplarze do urzędu)** z oryginalnymi pieczęciami i wysyłką kurierem.
  - **Wersja cyfrowa (PDF)** do natychmiastowego pobrania po zakupie.
- Zarządzanie widocznością: możliwość ukrycia/pokazania projektu w katalogu jednym kliknięciem.

### 3. Płatności BLIK (Przelewy24 / PayU / Autopay)
- Lekki, zoptymalizowany proces zamówienia bez konieczności przechodzenia przez koszyk.
- Natychmiastowa autoryzacja 6-cyfrowym kodem **BLIK**.
- Obsługa symulatora testowego oraz integracji z bramkami produkcyjnymi.
- Generowanie unikalnego numeru zamówienia (`ARCH-YYYYMMDD-XXXXX`) i identyfikatora transakcji bramki.

---

## 🧪 Testy i Jakość Kodu

Wszystkie klasy posiadają ścisłe typowanie i przechodzą testy oraz analizę statyczną:

```bash
# Analiza statyczna (PHPStan - poziom 6)
docker compose exec drupal vendor/bin/phpstan analyse

# Standardy kodu Drupal i DrupalPractice (PHP_CodeSniffer)
docker compose exec drupal vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom/architect_studio

# Testy jednostkowe i integracyjne (PHPUnit)
docker compose exec drupal vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/custom/architect_studio/tests
```

---

## 🛠️ CLI: Drush

```bash
# Czyszczenie cache
./drush cr

# Logowanie do panelu administratora
./drush uli

# Status instalacji
./drush status
```
