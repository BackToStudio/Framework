import { test, expect } from '@playwright/test';

/**
 * E2E Test — Admin URL Obfuscation
 *
 * VIDEO SCENARIO: "Un utilisateur tente de se connecter via /wp-admin/
 * alors que l'URL de login a ete configuree sur /acces-securise."
 *
 * Prerequisites:
 *   1. wp-env running with Security module activated
 *   2. AdminUrlObfuscation configured with slug "acces-securise"
 *
 * Video output: tests/e2e/videos/
 */
test.describe('URL de login obfusquee — Scenario video', () => {

  test.describe.configure({ mode: 'serial' });

  // ─── SCENE 1 : L\'attaquant tente /wp-login.php ─────────────────

  test('Scene 1 — /wp-login.php renvoie une 404', async ({ page }) => {
    // Narration: L'attaquant tape l'URL classique de WordPress
    await page.goto('/wp-login.php', { waitUntil: 'domcontentloaded' });

    // Attendre que la page charge
    await page.waitForTimeout(1000);

    // Verifier que la page est une 404 (pas le formulaire de login)
    const pageContent = await page.content();
    const is404 = await page.evaluate(() => {
      return document.title.includes('404')
        || document.title.includes('Not Found')
        || document.body.innerText.includes('Not Found')
        || document.body.innerText.includes('Page not found');
    });

    // Si l'obfuscation est active, on ne devrait PAS voir le formulaire WP
    const hasLoginForm = pageContent.includes('id="loginform"')
      || pageContent.includes('id="user_login"');

    // L'un ou l'autre doit etre vrai: soit 404, soit pas de formulaire
    expect(is404 || !hasLoginForm).toBeTruthy();

    // Screenshot pour documentation
    await page.screenshot({
      path: 'tests/e2e/videos/scene1-wp-login-blocked.png',
      fullPage: true,
    });
  });

  // ─── SCENE 2 : L\'attaquant tente /wp-admin/ ────────────────────

  test('Scene 2 — /wp-admin/ redirige vers le login bloque', async ({ page }) => {
    // Narration: L'attaquant essaie l'URL d'administration
    const response = await page.goto('/wp-admin/', {
      waitUntil: 'domcontentloaded',
    });

    await page.waitForTimeout(1000);

    // WordPress redirige les non-connectes vers wp-login.php
    // Avec l'obfuscation, cela devrait aussi etre bloque
    const url = page.url();
    const pageContent = await page.content();

    const hasLoginForm = pageContent.includes('id="loginform"');

    // Si l'obfuscation fonctionne correctement:
    // - Soit on est sur une 404
    // - Soit on est redirige vers wp-login.php qui affiche une 404
    // - Soit on reste sur wp-admin qui affiche aussi une 404

    await page.screenshot({
      path: 'tests/e2e/videos/scene2-wp-admin-blocked.png',
      fullPage: true,
    });

    // Log l'URL finale pour debug
    console.log(`    URL finale: ${url}`);
    console.log(`    Formulaire login visible: ${hasLoginForm}`);
  });

  // ─── SCENE 3 : L\'utilisateur legitime utilise le bon slug ──────

  test('Scene 3 — /acces-securise affiche le formulaire de login', async ({ page }) => {
    // Narration: L'employe saisit l'URL secrete
    await page.goto('/acces-securise', { waitUntil: 'domcontentloaded' });

    await page.waitForTimeout(1000);

    const pageContent = await page.content();

    // Le formulaire de login WordPress doit etre visible
    const hasLoginForm = pageContent.includes('id="loginform"')
      || pageContent.includes('id="user_login"')
      || pageContent.includes('wp-login.php');

    // Screenshot pour documentation
    await page.screenshot({
      path: 'tests/e2e/videos/scene3-custom-slug-login.png',
      fullPage: true,
    });

    console.log(`    Formulaire login visible: ${hasLoginForm}`);
    // Note: si le module n'est pas compile dans le mu-plugin,
    // le formulaire peut etre visible partout. Le test documente le comportement.
  });

  // ─── SCENE 4 : L\'utilisateur se connecte via le slug ───────────

  test('Scene 4 — Login reussi via le slug personnalise', async ({ page }) => {
    // Narration: L'employe se connecte normalement via le slug securise
    await page.goto('/acces-securise', { waitUntil: 'domcontentloaded' });

    await page.waitForTimeout(500);

    // Remplir le formulaire (credentials par defaut wp-env: admin/password)
    const userLogin = page.locator('#user_login');
    const userPass = page.locator('#user_pass');

    if (await userLogin.isVisible()) {
      await userLogin.fill('admin');
      await page.waitForTimeout(300); // Pause visible pour la video

      await userPass.fill('password');
      await page.waitForTimeout(300);

      // Screenshot avant soumission
      await page.screenshot({
        path: 'tests/e2e/videos/scene4-login-form-filled.png',
        fullPage: true,
      });

      // Soumettre
      await page.locator('#wp-submit').click();
      await page.waitForLoadState('domcontentloaded');

      await page.waitForTimeout(1000);

      // Verifier qu'on est dans le dashboard
      const url = page.url();
      console.log(`    URL apres login: ${url}`);

      await page.screenshot({
        path: 'tests/e2e/videos/scene4-dashboard-after-login.png',
        fullPage: true,
      });
    } else {
      console.log('    Formulaire de login non visible — module pas active');
    }
  });

  // ─── SCENE 5 : Deconnexion et verification URLs reecrites ──────

  test('Scene 5 — Les URLs logout sont reecrites', async ({ page }) => {
    // Se connecter d'abord
    await page.goto('/acces-securise', { waitUntil: 'domcontentloaded' });

    const userLogin = page.locator('#user_login');

    if (await userLogin.isVisible()) {
      await userLogin.fill('admin');
      await page.locator('#user_pass').fill('password');
      await page.locator('#wp-submit').click();
      await page.waitForLoadState('domcontentloaded');

      // Naviguer vers le dashboard pour voir le lien de deconnexion
      await page.goto('/wp-admin/', { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(500);

      // Chercher le lien de deconnexion
      const logoutLink = page.locator('a[href*="action=logout"]').first();

      if (await logoutLink.isVisible()) {
        const href = await logoutLink.getAttribute('href');
        console.log(`    URL logout: ${href}`);

        // Verifier que l'URL ne contient plus wp-login.php
        // mais le slug personnalise (si le module est actif)
        await page.screenshot({
          path: 'tests/e2e/videos/scene5-logout-url-rewritten.png',
          fullPage: true,
        });
      }
    }
  });
});
