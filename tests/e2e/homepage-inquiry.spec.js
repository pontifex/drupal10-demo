const { test, expect } = require('@playwright/test');
const { clearFloodTable } = require('./test-helpers');

test.describe('Strona Główna i Formularz Zapytania o Adaptację', () => {

  test.beforeEach(() => {
    clearFloodTable();
  });

  test('strona główna ładuje się poprawnie z sekcją Hero, ofertą usług i formularzem wyceny', async ({ page }) => {
    await page.goto('/architekt');

    // Weryfikacja tytułu strony
    await expect(page).toHaveTitle(/Pracownia Architektoniczna/);

    // Sekcja Hero
    const heroTitle = page.locator('.hero-title');
    await expect(heroTitle).toBeVisible();
    await expect(heroTitle).toContainText('Pracownia Architektoniczna');

    // Przyciski CTA w sekcji Hero
    const ctaCatalog = page.locator('.hero-cta-group a', { hasText: 'Zobacz katalog projektów' });
    await expect(ctaCatalog).toBeVisible();
    await expect(ctaCatalog).toHaveAttribute('href', '/projekty');

    const ctaInquiry = page.locator('.hero-cta-group a', { hasText: 'Bezpłatna wycena adaptacji' });
    await expect(ctaInquiry).toBeVisible();
    await expect(ctaInquiry).toHaveAttribute('href', '#wycena');

    // Sekcja usług
    const servicesSection = page.locator('.services-grid');
    await expect(servicesSection).toBeVisible();
    await expect(page.locator('.service-card')).toHaveCount(3);

    // Formularz wyceny w sekcji #wycena
    const inquirySection = page.locator('#wycena');
    await expect(inquirySection).toBeVisible();
    await expect(page.locator('form.architect-inquiry-form')).toBeVisible();
  });

  test('nawigacja do katalogu projektów z sekcji Hero', async ({ page }) => {
    await page.goto('/architekt');
    await page.locator('.hero-cta-group a', { hasText: 'Zobacz katalog projektów' }).click();
    await expect(page).toHaveURL(/\/projekty/);
    await expect(page.locator('.catalog-header h1')).toContainText('Katalog Autorskich Projektów');
  });

  test('formularz wyceny posiada walidację wymaganych pól', async ({ page }) => {
    await page.goto('/architekt#wycena');

    const form = page.locator('form.architect-inquiry-form');
    await expect(form).toBeVisible();

    const nameInput = form.locator('input[name="customer_name"]');
    const emailInput = form.locator('input[name="customer_email"]');
    const phoneInput = form.locator('input[name="customer_phone"]');
    const messageInput = form.locator('textarea[name="message"]');

    // Sprawdzenie atrybutu HTML5 required
    await expect(nameInput).toHaveAttribute('required', 'required');
    await expect(emailInput).toHaveAttribute('required', 'required');
    await expect(phoneInput).toHaveAttribute('required', 'required');
    await expect(messageInput).toHaveAttribute('required', 'required');
  });

  test('poprawne wysłanie zapytania o adaptację wyświetla komunikat z podziękowaniem', async ({ page }) => {
    await page.goto('/architekt#wycena');

    const form = page.locator('form.architect-inquiry-form');
    await expect(form).toBeVisible();

    // Wypełnienie formularza
    await form.locator('input[name="customer_name"]').fill('Piotr Wiśniewski');
    await form.locator('input[name="customer_email"]').fill('piotr.wisniewski@example.com');
    await form.locator('input[name="customer_phone"]').fill('501234567');
    await form.locator('select[name="inquiry_type"]').selectOption('kompleksowo');
    await form.locator('input[name="project_link"]').fill('https://extradom.pl/projekt-domu-gloria-KRD-2489');
    await form.locator('input[name="plot_location"]').fill('Piaseczno, dz. nr 144/2, MPZP dopuszcza dach 40 st.');
    await form.locator('textarea[name="message"]').fill('Dzień dobry, proszę o wycenę adaptacji projektu do działki wraz ze zmianą kotła gazowego na pompę ciepła powietrze-woda.');

    // Wysłanie formularza
    await form.locator('.btn-architect-submit').click();

    // Weryfikacja komunikatu sukcesu Drupala
    const alertSuccess = page.locator('.messages--status');
    await expect(alertSuccess).toBeVisible();
    await expect(alertSuccess).toContainText('Dziękujemy za przesłanie zapytania');
    await expect(alertSuccess).toContainText('Architekt skontaktuje się z Tobą w ciągu 24h');
  });

});
