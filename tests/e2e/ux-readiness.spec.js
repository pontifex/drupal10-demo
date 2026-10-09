// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Gotowość Produkcyjna: UX, Branding, 404 i Cookie Banner', () => {

  test('dedykowana strona 404 wyświetla się dla nieistniejących adresów URL ze statusem 404', async ({ page }) => {
    const response = await page.goto('/losowy-nieistniejacy-adres-projektu-999');
    expect(response?.status()).toBe(404);

    const heading = page.locator('.card-404 h1');
    await expect(heading).toBeVisible();
    await expect(heading).toContainText('Nie odnaleziono projektu ani strony');

    const badge = page.locator('.badge-404');
    await expect(badge).toBeVisible();
    await expect(badge).toContainText('Błąd 404');

    const catalogLink = page.locator('.actions-404 a[href="/projekty"]');
    await expect(catalogLink).toBeVisible();

    const homeLink = page.locator('.actions-404 a[href="/architekt"]');
    await expect(homeLink).toBeVisible();
  });

  test('strona główna zawiera dedykowany favicon SVG i Apple Touch Icon w nagłówku', async ({ page }) => {
    await page.goto('/architekt');

    const faviconSvg = page.locator('link[rel="icon"][type="image/svg+xml"]');
    await expect(faviconSvg).toHaveCount(1);
    const svgHref = await faviconSvg.getAttribute('href');
    expect(svgHref).toContain('favicon.svg');

    // Weryfikacja że plik favicon fizycznie istnieje i zwraca 200
    const faviconResponse = await page.goto(svgHref ?? '/modules/custom/architect_studio/images/favicon.svg');
    expect(faviconResponse?.status()).toBe(200);
  });

  test('serwis szanuje prywatność: brak zbędnego banera cookies oraz brak ciasteczek śledzących', async ({ page, context }) => {
    await page.goto('/architekt');

    // Baner cookies nie powinien istnieć w DOM
    const banner = page.locator('#architect-cookie-banner');
    await expect(banner).toHaveCount(0);

    // Brak jakichkolwiek cookies śledzących dla użytkownika anonimowego
    const cookies = await context.cookies();
    expect(cookies.length).toBe(0);
  });

  test('na urządzeniach mobilnych widoczny jest pływający przycisk szybkiego telefonu do architekta', async ({ page, isMobile }) => {
    await page.goto('/architekt');

    const floatingBar = page.locator('#mobile-floating-contact');
    const callBtn = page.locator('#mobile-call-btn');

    if (isMobile) {
      await expect(floatingBar).toBeVisible();
      await expect(callBtn).toBeVisible();
      await expect(callBtn).toHaveAttribute('href', 'tel:+48500123456');
      await expect(callBtn).toContainText('Zadzwoń do architekta');

      const quoteBtn = page.locator('#mobile-quote-btn');
      await expect(quoteBtn).toBeVisible();
      await expect(quoteBtn).toHaveAttribute('href', '/architekt#wycena');
    } else {
      // Na desktopie pasek jest ukryty za pomocą media query (display: none)
      await expect(floatingBar).toBeHidden();
    }
  });

});
