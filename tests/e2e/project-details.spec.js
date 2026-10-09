const { test, expect } = require('@playwright/test');

test.describe('Karta Szczegółów Projektu', () => {

  test('strona projektu wyświetla nagłówek, wizualizację, rzut i tabelę parametrów technicznych', async ({ page }) => {
    // Projekt 1 (Gloria)
    await page.goto('/projekty/1');

    // Breadcrumb
    const breadcrumb = page.locator('.breadcrumb-nav a');
    await expect(breadcrumb).toBeVisible();
    await expect(breadcrumb).toHaveAttribute('href', '/projekty');

    // Tytuł i kod w nagłówku szczegółów
    await expect(page.locator('.detail-header-text h1')).toBeVisible();
    await expect(page.locator('.project-code-badge')).toBeVisible();
    await expect(page.locator('.detail-lead-desc')).toBeVisible();

    // Kolumna mediów (wizualizacja i rysunek techniczny)
    const mediaCards = page.locator('.media-card');
    await expect(mediaCards.first()).toBeVisible();

    // Rysunek techniczny / rzut kondygnacji
    const blueprintCard = page.locator('.blueprint-card');
    await expect(blueprintCard).toBeVisible();
    await expect(blueprintCard.locator('img')).toBeVisible();

    // Tabela parametrów technicznych
    const specsTable = page.locator('.specs-table');
    await expect(specsTable).toBeVisible();
    await expect(specsTable).toContainText('Powierzchnia użytkowa:');
    await expect(specsTable).toContainText('Powierzchnia zabudowy:');
    await expect(specsTable).toContainText('Kąt nachylenia dachu:');
    await expect(specsTable).toContainText('Minimalne wymiary działki:');
    await expect(specsTable).toContainText('Zalecane ogrzewanie:');

    // Box zakupu
    const purchaseBox = page.locator('.purchase-box');
    await expect(purchaseBox).toBeVisible();
    await expect(purchaseBox).toContainText('Wersja drukowana (4 egzemplarze)');
    await expect(purchaseBox).toContainText('Wersja cyfrowa (PDF)');

    // Przycisk przejścia do zamówienia BLIK
    const orderBtn = purchaseBox.locator('a', { hasText: 'Przejdź do zamówienia (BLIK)' });
    await expect(orderBtn).toBeVisible();
    await expect(orderBtn).toHaveAttribute('href', '/projekty/1/zamow');
  });

  test('interaktywny Lightbox powiększa rzuty i wizualizacje po kliknięciu', async ({ page }) => {
    await page.goto('/projekty/1');

    const zoomWrapper = page.locator('.zoomable-image-wrapper').first();
    await expect(zoomWrapper).toBeVisible();

    // Kliknij, aby powiększyć w modalu lightbox
    await zoomWrapper.click();

    // Dialog Lightbox staje się widoczny
    const dialog = page.locator('#architect-lightbox-dialog');
    await expect(dialog).toBeVisible();

    const activeImg = page.locator('#lightbox-active-img');
    await expect(activeImg).toBeVisible();

    // Zamknięcie dialogu
    const closeBtn = page.locator('#lightbox-close');
    await expect(closeBtn).toBeVisible();
    await closeBtn.click();

    // Po zamknięciu dialog nie jest widoczny
    await expect(dialog).not.toBeVisible();
  });

  test('przejście z karty projektu do formularza zamówienia BLIK', async ({ page }) => {
    await page.goto('/projekty/1');

    await page.locator('.purchase-cta a', { hasText: 'Przejdź do zamówienia (BLIK)' }).click();

    await expect(page).toHaveURL('/projekty/1/zamow');
    await expect(page.locator('h3', { hasText: 'Zamawiasz:' })).toBeVisible();
  });

});
