const { test, expect } = require('@playwright/test');

test.describe('Optymalizacja SEO i Pozycjonowanie Lokalne (Kraśnik, Annopol)', () => {

  test('strona główna zawiera zoptymalizowany tytuł, meta description oraz tagi geolokalizacyjne', async ({ page }) => {
    await page.goto('/architekt');

    // Title tag nasycony frazami lokalnymi
    await expect(page).toHaveTitle(/Architekt Kraśnik, Annopol/);

    // Meta description
    const metaDescription = page.locator('meta[name="description"]');
    await expect(metaDescription).toHaveAttribute('content', /Architekt Kraśnik.*Starostwie Powiatowym w Kraśniku/);

    // Tagi geolokalizacyjne dla wyszukiwarek (woj. lubelskie, Kraśnik)
    await expect(page.locator('meta[name="geo.region"]')).toHaveAttribute('content', 'PL-06');
    await expect(page.locator('meta[name="geo.placename"]')).toHaveAttribute('content', 'Kraśnik, Annopol, Powiat Kraśnicki');
    await expect(page.locator('meta[name="geo.position"]')).toHaveAttribute('content', '50.9234;22.2274');

    // OpenGraph
    await expect(page.locator('meta[property="og:site_name"]')).toHaveAttribute('content', 'Pracownia Architektoniczna Kraśnik & Annopol');
    await expect(page.locator('meta[property="og:locale"]')).toHaveAttribute('content', 'pl_PL');
  });

  test('strona główna zawiera dane strukturalne Schema.org JSON-LD (Architect & FAQPage)', async ({ page }) => {
    await page.goto('/architekt');

    const ldJsonScripts = page.locator('script[type="application/ld+json"]');
    const count = await ldJsonScripts.count();
    expect(count).toBeGreaterThanOrEqual(2);

    let foundArchitect = false;
    let foundFaq = false;

    for (let i = 0; i < count; i++) {
      const text = await ldJsonScripts.nth(i).innerText();
      const parsed = JSON.parse(text);

      if (parsed['@type'] && (parsed['@type'].includes('Architect') || parsed['@type'] === 'Architect')) {
        foundArchitect = true;
        expect(parsed.name).toContain('Architekt Kraśnik');
        expect(parsed.name).toContain('Annopol');
        expect(parsed.address.addressLocality).toBe('Kraśnik');
        expect(parsed.address.postalCode).toBe('23-200');

        // Obszar obsługi (areaServed)
        const areaServedNames = parsed.areaServed.map((item) => item.name);
        expect(areaServedNames).toContain('Kraśnik');
        expect(areaServedNames).toContain('Annopol');
        expect(areaServedNames).toContain('Powiat kraśnicki');
      }

      if (parsed['@type'] === 'FAQPage') {
        foundFaq = true;
        expect(parsed.mainEntity.length).toBeGreaterThanOrEqual(4);
        const questions = parsed.mainEntity.map((q) => q.name);
        expect(questions[0]).toContain('Kraśniku i Annopolu');
        expect(questions[1]).toContain('Starostwie Powiatowym w Kraśniku');
      }
    }

    expect(foundArchitect).toBe(true);
    expect(foundFaq).toBe(true);
  });

  test('strona główna wyświetla sekcję lokalną oraz interaktywny akordeon FAQ', async ({ page }) => {
    await page.goto('/architekt');

    // Sekcja specyfiki lokalnej
    const localSection = page.locator('#obszar-dzialania');
    await expect(localSection).toBeVisible();
    await expect(localSection.locator('h2')).toContainText('Architekt Kraśnik & Annopol');
    await expect(localSection).toContainText('Starostwo Powiatowe w Kraśniku');
    await expect(localSection).toContainText('Gmina i Miasto Annopol');
    await expect(localSection).toContainText('Gminy Powiatu Kraśnickiego');

    // Sekcja FAQ
    const faqSection = page.locator('#faq');
    await expect(faqSection).toBeVisible();
    await expect(faqSection.locator('.faq-item')).toHaveCount(4);
    await expect(faqSection).toContainText('Ile kosztuje adaptacja projektu gotowego w Kraśniku i Annopolu?');
  });

  test('mapa strony sitemap.xml zwraca poprawny XML z adresami stron i projektów', async ({ page }) => {
    const response = await page.goto('/sitemap.xml');
    expect(response?.status()).toBe(200);

    const contentType = response?.headers()['content-type'];
    expect(contentType).toContain('xml');

    const content = await page.content();
    expect(content).toContain('/architekt');
    expect(content).toContain('/projekty');
    expect(content).toContain('/projekty/1');
    expect(content).toContain('/regulamin');
  });

  test('plik robots.txt wskazuje lokalizację mapy strony sitemap.xml', async ({ page }) => {
    const response = await page.goto('/robots.txt');
    expect(response?.status()).toBe(200);

    const text = await response?.text();
    expect(text).toMatch(/sitemap:\s*.*sitemap\.xml/i);
  });
});
