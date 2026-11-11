import { test as base } from '@playwright/test';

export const test = base.extend({
  authenticatedPage: async ({ page }, use) => {
    // Escuchar errores de consola para diagnóstico
    page.on('console', msg => {
      if (msg.type() === 'error') console.log(`[Browser Error]: ${msg.text()}`);
    });

    // 1. Ir a la pantalla de login
    await page.goto('/login');

    const emailInput = page.locator('#loginEmail');
    if (await emailInput.isVisible()) {
      await emailInput.fill('admin@datamind.local');
      await page.locator('#loginPassword').fill('datamind2026');
      
      // Enviar formulario y esperar redirección
      await page.locator('#loginFormPanel button[type="submit"]').click();
      await page.waitForURL((url) => !url.pathname.includes('/login'));
    }

    await page.goto('/chat');
    // Esperar a que el textarea del chat esté completamente montado en el DOM
    await page.locator('#promptInput').waitFor({ state: 'visible' });

    await use(page);
  },
});

export { expect } from '@playwright/test';
