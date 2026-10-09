const { test, expect } = require('@playwright/test');

test.describe('Katalog Projektów Architektonicznych', () => {

  test('katalog wyświetla listę projektów, parametry techniczne i ceny', async ({ page }) => {
    await page.goto('/projekty');

    // Tytuł i nagłówek
    await expect(page).toHaveTitle(/Katalog Projektów/);
    await expect(page.locator('.catalog-header h1')).toContainText('Katalog Autorskich Projektów Architektonicznych');

    // Pasek filtrów
    await expect(page.locator('.catalog-filter-bar')).toBeVisible();

    // Karty projektów
    const cards = page.locator('.project-card');
    await expect(cards).toHaveCount(6); // Domyślny limit na stronę = 6

    // Sprawdzenie pierwszej karty
    const firstCard = cards.first();
    await expect(firstCard.locator('.project-card-image img')).toBeVisible();
    await expect(firstCard.locator('.project-code')).toBeVisible();
    await expect(firstCard.locator('.project-card-title')).toBeVisible();
    await expect(firstCard.locator('.project-card-meta')).toContainText('pow. użytk.');
    await expect(firstCard.locator('.project-card-pricing')).toContainText('Druk');
    await expect(firstCard.locator('.project-card-pricing')).toContainText('Cyfrowy');

    // Przyciski akcji na karcie
    const detailsBtn = firstCard.locator('a', { hasText: 'Szczegóły i rzuty' });
    const orderBtn = firstCard.locator('a', { hasText: 'Kup teraz (BLIK)' });
    await expect(detailsBtn).toBeVisible();
    await expect(orderBtn).toBeVisible();
  });

  test('obrazy w kartach projektów ładują się bez błędów 404', async ({ page }) => {
    await page.goto('/projekty');

    const images = page.locator('.project-card-image img');
    const count = await images.count();
    expect(count).toBeGreaterThan(0);

    for (let i = 0; i < count; i++) {
      const img = images.nth(i);
      await img.scrollIntoViewIfNeeded();
      await expect(img).toBeVisible();
      // Weryfikacja naturalWidth > 0 w DOM po załadowaniu obrazu
      await expect(async () => {
        const isLoaded = await img.evaluate((el) => el.complete && el.naturalWidth > 0);
        expect(isLoaded).toBe(true);
      }).toPass({ timeout: 5000 });
    }
  });

  test('filtrowanie po słowie kluczowym (np. Gloria)', async ({ page }) => {
    await page.goto('/projekty');

    await page.locator('#filter-keyword').fill('Gloria');
    await page.locator('.filter-actions button[type="submit"]').click();

    // Adres URL zawiera parametr query
    await expect(page).toHaveURL(/keyword=Gloria/);

    // Wynik zawiera projekt Gloria
    const cards = page.locator('.project-card');
    await expect(cards).toHaveCount(1);
    await expect(cards.first().locator('.project-card-title')).toContainText('Gloria');

    // Aktywna pigułka filtra
    await expect(page.locator('.filter-pill').first()).toContainText('Gloria');
  });

  test('filtrowanie po kategorii (Domy parterowe)', async ({ page }) => {
    await page.goto('/projekty');

    await page.locator('#filter-category').selectOption('dom_parterowy');
    await page.locator('.filter-actions button[type="submit"]').click();

    await expect(page).toHaveURL(/category=dom_parterowy/);

    const cards = page.locator('.project-card');
    const count = await cards.count();
    expect(count).toBeGreaterThan(0);

    // Każda znaleziona karta ma kategorię dom parterowy
    for (let i = 0; i < count; i++) {
      await expect(cards.nth(i).locator('.project-badge')).toContainText(/Dom parterowy/i);
    }
  });

  test('paginacja katalogu pozwala przejść do strony 2 i z powrotem', async ({ page }) => {
    await page.goto('/projekty');

    const pagination = page.locator('.catalog-pagination');
    await expect(pagination).toBeVisible();

    // Kliknij stronę 2
    const page2Link = pagination.locator('a.page-link', { hasText: '2' });
    await expect(page2Link).toBeVisible();
    await page2Link.click();

    await expect(page).toHaveURL(/page=2/);
    await expect(pagination.locator('.page-link.active')).toHaveText('2');

    // Projekty na stronie 2
    const cardsPage2 = page.locator('.project-card');
    const countPage2 = await cardsPage2.count();
    expect(countPage2).toBeGreaterThan(0);

    // Poprzednia strona jest teraz aktywna
    const prevLink = pagination.locator('a.page-link--prev');
    await expect(prevLink).toBeVisible();
    await prevLink.click();

    await expect(page).toHaveURL(/page=1/);
  });

  test('kliknięcie Szczegóły i rzuty otwiera kartę pojedynczego projektu', async ({ page }) => {
    await page.goto('/projekty');

    const firstCard = page.locator('.project-card').first();
    const projectTitle = await firstCard.locator('.project-card-title').innerText();

    await firstCard.locator('a', { hasText: 'Szczegóły i rzuty' }).click();

    await expect(page).toHaveURL(/\/projekty\/\d+/);
    await expect(page.locator('.detail-header-text h1')).toContainText(projectTitle);
  });

});
