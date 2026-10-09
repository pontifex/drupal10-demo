const { test, expect } = require('@playwright/test');

test('smoke: homepage responds and has correct title', async ({ page }) => {
  await page.goto('/architekt');
  await expect(page).toHaveTitle(/Pracownia Architektoniczna/);
  await expect(page.locator('.hero-title')).toBeVisible();
});
