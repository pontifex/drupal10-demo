const { test, expect } = require('@playwright/test');
const { clearFloodTable } = require('./test-helpers');

test.describe('Formularz Zamówienia i Płatność BLIK', () => {

  test.beforeEach(() => {
    clearFloodTable();
  });

  test('formularz zamówienia ładuje się z podsumowaniem projektu i domyślnym wariantem drukowanym', async ({ page }) => {
    await page.goto('/projekty/1/zamow');

    await expect(page).toHaveTitle(/Zamówienie Projektu z Płatnością BLIK/);
    await expect(page.locator('.order-project-summary-box')).toBeVisible();
    await expect(page.locator('.order-project-summary-box')).toContainText('Gloria');

    // Domyślny wariant to wersja drukowana
    const printRadio = page.locator('input[name="variant"][value="print"]');
    await expect(printRadio).toBeChecked();

    // Domyślna bramka to Przelewy24 BLIK
    const p24Radio = page.locator('input[name="payment_gateway"][value="blik_p24"]');
    await expect(p24Radio).toBeChecked();

    // Pole na kod BLIK
    const blikInput = page.locator('input[name="blik_code"]');
    await expect(blikInput).toBeVisible();
    await expect(blikInput).toHaveAttribute('placeholder', '123 456');

    // Checkbox zgód
    const termsCheckbox = page.locator('input[name="terms_accepted"]');
    await expect(termsCheckbox).toBeVisible();
  });

  test('walidacja adresu dostawy dla wersji drukowanej', async ({ page }) => {
    await page.goto('/projekty/1/zamow');

    // Wybieramy wariant drukowany (domyślny), ale nie podajemy adresu dostawy
    await page.locator('input[name="customer_name"]').fill('Anna Nowak');
    await page.locator('input[name="customer_email"]').fill('anna.nowak@example.com');
    await page.locator('input[name="customer_phone"]').fill('600111222');
    await page.locator('input[name="blik_code"]').fill('777123');
    await page.locator('input[name="terms_accepted"]').check();

    // Wysłanie formularza
    await page.locator('.btn-pay-blik').click();

    // Oczekujemy błędu walidacji adresu dostawy
    const errorBox = page.locator('.messages--error, .alert-danger');
    await expect(errorBox).toBeVisible();
    await expect(errorBox).toContainText('Dla wersji drukowanej (4 egzemplarze) podanie adresu dostawy kurierskiej jest wymagane');
  });

  test('symulacja odrzucenia płatności BLIK w banku (kod 000000)', async ({ page }) => {
    await page.goto('/projekty/1/zamow');

    // Wybieramy wersję cyfrową
    await page.locator('input[name="variant"][value="digital"]').check();

    await page.locator('input[name="customer_name"]').fill('Tomasz Testowy');
    await page.locator('input[name="customer_email"]').fill('tomasz.test@example.com');
    await page.locator('input[name="customer_phone"]').fill('600222333');
    // Kod 000000 symuluje anulowanie / odrzucenie transakcji w banku
    await page.locator('input[name="blik_code"]').fill('000000');
    await page.locator('input[name="terms_accepted"]').check();

    await page.locator('.btn-pay-blik').click();

    // Oczekujemy komunikatu o niepowodzeniu płatności
    const errorBox = page.locator('.messages--error, .alert-danger');
    await expect(errorBox).toBeVisible();
    await expect(errorBox).toContainText('Płatność BLIK nie powiodła się');
    await expect(errorBox).toContainText('Płatność została odrzucona');
  });

  test('udana płatność BLIK (wersja cyfrowa) przekierowuje do strony sukcesu z numerem ARCH-...', async ({ page }) => {
    await page.goto('/projekty/1/zamow');

    // Wybór wariantu cyfrowego PDF
    await page.locator('input[name="variant"][value="digital"]').check();

    // Wypełnienie danych inwestora
    await page.locator('input[name="customer_name"]').fill('Marek Kowalczyk');
    await page.locator('input[name="customer_email"]').fill('marek.kowalczyk@example.com');
    await page.locator('input[name="customer_phone"]').fill('501987654');
    await page.locator('textarea[name="notes"]').fill('Proszę o fakturę VAT oraz zgodę na pompę ciepła.');

    // Prawidłowy 6-cyfrowy kod BLIK (np. 777123)
    await page.locator('input[name="blik_code"]').fill('777123');

    // Akceptacja regulaminu i RODO
    await page.locator('input[name="terms_accepted"]').check();

    // Złożenie zamówienia
    await page.locator('.btn-pay-blik').click();

    // Weryfikacja przekierowania do strony sukcesu
    await expect(page).toHaveURL(/\/zamowienie\/sukces\/ARCH-\d+/);
    await expect(page.locator('.success-card h1')).toContainText('Dziękujemy za złożenie zamówienia!');

    // Podsumowanie zamówienia
    const orderNumberEl = page.locator('.order-number-highlight');
    await expect(orderNumberEl).toBeVisible();
    await expect(orderNumberEl).toContainText(/ARCH-\d+/);

    // Status płatności
    const statusBadge = page.locator('.badge-status-paid');
    await expect(statusBadge).toBeVisible();
    await expect(statusBadge).toContainText(/OPŁACONE/);
    await expect(statusBadge).toContainText(/TXN-P24-/);

    // Tabela podsumowania
    const summaryTable = page.locator('.order-summary-table');
    await expect(summaryTable).toContainText('Gloria');
    await expect(summaryTable).toContainText('Wersja cyfrowa (PDF)');
    await expect(summaryTable).toContainText('Marek Kowalczyk');

    // Dostępność przycisków powrotu
    await expect(page.locator('a', { hasText: 'Powrót do strony pracowni' })).toBeVisible();
    await expect(page.locator('a', { hasText: 'Zobacz inne projekty' })).toBeVisible();
  });

  test('udana płatność BLIK (wersja drukowana z adresem kurierskim)', async ({ page }) => {
    await page.goto('/projekty/6/zamow');

    // Pozostawiamy wersję drukowaną
    await page.locator('input[name="variant"][value="print"]').check();

    // Dane zamawiającego
    await page.locator('input[name="customer_name"]').fill('Katarzyna Zielińska');
    await page.locator('input[name="customer_email"]').fill('katarzyna.zielinska@example.com');
    await page.locator('input[name="customer_phone"]').fill('601234567');
    await page.locator('textarea[name="shipping_address"]').fill('ul. Słoneczna 12/4, 05-500 Piaseczno');

    // Wybór operatora PayU BLIK
    await page.locator('input[name="payment_gateway"][value="blik_payu"]').check();

    // 6-cyfrowy kod BLIK
    await page.locator('input[name="blik_code"]').fill('888456');

    // Akceptacja regulaminu
    await page.locator('input[name="terms_accepted"]').check();

    // Złożenie zamówienia
    await page.locator('.btn-pay-blik').click();

    // Weryfikacja sukcesu
    await expect(page).toHaveURL(/\/zamowienie\/sukces\/ARCH-\d+/);
    await expect(page.locator('.success-card h1')).toContainText('Dziękujemy za złożenie zamówienia!');

    const summaryTable = page.locator('.order-summary-table');
    await expect(summaryTable).toContainText('Wersja drukowana (4 egzemplarze do urzędu)');
    await expect(summaryTable).toContainText('ul. Słoneczna 12/4, 05-500 Piaseczno');
    await expect(page.locator('.badge-status-paid')).toContainText(/TXN-PAYU-/);
  });

});
