const { test, expect } = require('@playwright/test');

test.describe('Strony Prawne i Informacyjne (Regulamin i RODO)', () => {

  test('strona regulaminu serwisu wyświetla dane architekta, zasady sprzedaży i płatności BLIK', async ({ page }) => {
    await page.goto('/regulamin');

    await expect(page).toHaveTitle(/Regulamin Serwisu i Sprzedaży Projektów/);
    await expect(page.locator('h1.legal-title')).toContainText('Regulamin Serwisu i Sprzedaży Projektów');

    // Dane sprzedawcy
    const companyBox = page.locator('.company-details-box');
    await expect(companyBox).toBeVisible();
    await expect(companyBox).toContainText('Pracownia Architektoniczna');
    await expect(companyBox).toContainText('NIP: 525-241-89-02');
    await expect(companyBox).toContainText('Izby Architektów RP');

    // Sekcje regulaminu
    await expect(page.locator('.legal-content')).toContainText('Płatność BLIK');
    await expect(page.locator('.legal-content')).toContainText('Wersja drukowana');
    await expect(page.locator('.legal-content')).toContainText('Wersja cyfrowa');
  });

  test('strona polityki prywatności wyświetla informacje o RODO i przetwarzaniu danych', async ({ page }) => {
    await page.goto('/polityka-prywatnosci');

    await expect(page).toHaveTitle(/Polityka Prywatności i Informacja o RODO/);
    await expect(page.locator('h1.legal-title')).toContainText('Polityka Prywatności i Informacja o RODO');

    const content = page.locator('.legal-content');
    await expect(content).toBeVisible();
    await expect(content).toContainText('Administrator Danych Osobowych');
    await expect(content).toContainText('Cele i podstawy prawne przetwarzania danych');
    await expect(content).toContainText('Prawa przysługujące osobom');
  });

  test('stopka serwisu zawiera działające odnośniki do Regulaminu i Polityki Prywatności', async ({ page }) => {
    await page.goto('/architekt');

    const termsLink = page.locator('.architect-footer a[href="/regulamin"]').first();
    const privacyLink = page.locator('.architect-footer a[href="/polityka-prywatnosci"]').first();

    await expect(termsLink).toBeVisible();
    await expect(privacyLink).toBeVisible();

    // Sprawdzenie przejścia do regulaminu
    await termsLink.click();
    await expect(page).toHaveURL('/regulamin');
  });

});
