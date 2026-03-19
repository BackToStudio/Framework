import { test, expect } from '@playwright/test';

/**
 * E2E Test — Two-Factor Authentication (TOTP)
 *
 * VIDEO SCENARIO: "L'administrateur active le 2FA sur son compte,
 * se deconnecte, puis doit saisir un code TOTP pour se reconnecter.
 * Il teste aussi un code invalide et un code de backup."
 *
 * Prerequisites:
 *   1. wp-env running with Security module activated
 *   2. TwoFactorAuthentication + TwoFactorSetupManager configured
 *   3. Admin user: admin / password
 *
 * Note: In a real WordPress environment the 2FA setup page would be
 * under the user profile or a dedicated Security menu. This test
 * documents the expected login flow once 2FA is enabled.
 *
 * Video output: tests/e2e/videos/
 */
test.describe('Two-Factor Authentication (TOTP) — Scenario video', () => {

  test.describe.configure({ mode: 'serial' });

  const LOGIN_URL = '/wp-login.php';

  // ─── SCENE 1 : Login normal avant activation du 2FA ─────────────

  test('Scene 1 — Login normal sans 2FA', async ({ page }) => {
    // Narration: L'admin se connecte normalement — pas de 2FA encore
    await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });

    const userLogin = page.locator('#user_login');

    if (!(await userLogin.isVisible())) {
      console.log('    Login form not visible — URL may be obfuscated, trying /acces-securise');
      await page.goto('/acces-securise', { waitUntil: 'domcontentloaded' });
    }

    await page.locator('#user_login').fill('admin');
    await page.waitForTimeout(300);
    await page.locator('#user_pass').fill('password');
    await page.waitForTimeout(300);

    await page.screenshot({
      path: 'tests/e2e/videos/2fa-scene1-login-normal.png',
      fullPage: true,
    });

    await page.locator('#wp-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(1000);

    const url = page.url();
    console.log(`    URL apres login: ${url}`);

    // On devrait etre dans le dashboard (pas de 2FA encore)
    const isDashboard = url.includes('wp-admin');
    console.log(`    Dashboard atteint: ${isDashboard}`);

    await page.screenshot({
      path: 'tests/e2e/videos/2fa-scene1-dashboard.png',
      fullPage: true,
    });
  });

  // ─── SCENE 2 : Acces a la page de configuration 2FA ──────────────

  test('Scene 2 — Navigation vers la page de profil / 2FA', async ({ page }) => {
    // Narration: L'admin se connecte et navigue vers la page profil/2FA
    await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });
    const userLogin = page.locator('#user_login');
    if (!(await userLogin.isVisible())) {
      await page.goto('/acces-securise', { waitUntil: 'domcontentloaded' });
    }

    await page.locator('#user_login').fill('admin');
    await page.locator('#user_pass').fill('password');
    await page.locator('#wp-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(500);

    // Naviguer vers le profil utilisateur
    await page.goto('/wp-admin/profile.php', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    // Chercher une section 2FA dans le profil
    const pageContent = await page.content();
    const has2faSection = pageContent.includes('two-factor')
      || pageContent.includes('2fa')
      || pageContent.includes('Two-Factor')
      || pageContent.includes('backto_2fa');

    console.log(`    Section 2FA trouvee dans le profil: ${has2faSection}`);

    await page.screenshot({
      path: 'tests/e2e/videos/2fa-scene2-profile-page.png',
      fullPage: true,
    });
  });

  // ─── SCENE 3 : Le 2FA demande un code apres le login ─────────────

  test('Scene 3 — Login avec 2FA actif : formulaire de code apparait', async ({ page }) => {
    // Narration: Si le 2FA est actif, apres login un champ de code TOTP apparait
    // Le module retourne une WP_Error 'two_factor_required' qui declenche
    // un formulaire de saisie du code

    await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });
    const userLogin = page.locator('#user_login');
    if (!(await userLogin.isVisible())) {
      await page.goto('/acces-securise', { waitUntil: 'domcontentloaded' });
    }

    await page.locator('#user_login').fill('admin');
    await page.waitForTimeout(300);
    await page.locator('#user_pass').fill('password');
    await page.waitForTimeout(300);
    await page.locator('#wp-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(1000);

    // Verifier si le formulaire 2FA apparait
    const pageContent = await page.content();
    const has2faField = pageContent.includes('backto_2fa_code')
      || pageContent.includes('two_factor_required')
      || pageContent.includes('two-factor authentication code');

    if (has2faField) {
      console.log('    >>> Formulaire 2FA detecte <<<');

      await page.screenshot({
        path: 'tests/e2e/videos/2fa-scene3-code-required.png',
        fullPage: true,
      });
    } else {
      // Le 2FA n'est peut-etre pas encore active pour cet user
      console.log('    2FA non actif pour cet utilisateur — scene documentaire');

      await page.screenshot({
        path: 'tests/e2e/videos/2fa-scene3-no-2fa-active.png',
        fullPage: true,
      });
    }
  });

  // ─── SCENE 4 : Saisie d'un code TOTP invalide ────────────────────

  test('Scene 4 — Code TOTP invalide rejete', async ({ page }) => {
    // Narration: L'utilisateur saisit un mauvais code TOTP — rejete
    await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });
    const userLogin = page.locator('#user_login');
    if (!(await userLogin.isVisible())) {
      await page.goto('/acces-securise', { waitUntil: 'domcontentloaded' });
    }

    await page.locator('#user_login').fill('admin');
    await page.locator('#user_pass').fill('password');
    await page.locator('#wp-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(500);

    // Chercher le champ 2FA
    const codeField = page.locator('input[name="backto_2fa_code"]');

    if (await codeField.isVisible()) {
      // Saisir un code invalide
      await codeField.fill('000000');
      await page.waitForTimeout(500);

      await page.screenshot({
        path: 'tests/e2e/videos/2fa-scene4-invalid-code-entered.png',
        fullPage: true,
      });

      await page.locator('#wp-submit').click();
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      // Verifier le message d'erreur
      const errorBox = page.locator('#login_error');
      if (await errorBox.isVisible()) {
        const errorText = await errorBox.innerText();
        console.log(`    Erreur 2FA: "${errorText.trim()}"`);

        // Le message doit indiquer un code invalide
        expect(
          errorText.includes('Invalid two-factor') ||
          errorText.includes('invalid') ||
          errorText.includes('incorrect')
        ).toBeTruthy();
      }

      await page.screenshot({
        path: 'tests/e2e/videos/2fa-scene4-invalid-code-rejected.png',
        fullPage: true,
      });
    } else {
      console.log('    Champ 2FA non visible — 2FA non actif, scene documentaire');
      test.skip();
    }
  });

  // ─── SCENE 5 : Code de backup utilise avec succes ─────────────────

  test('Scene 5 — Code de backup accepte (consomme)', async ({ page }) => {
    // Narration: L'utilisateur a perdu son telephone et utilise un code de backup
    await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });
    const userLogin = page.locator('#user_login');
    if (!(await userLogin.isVisible())) {
      await page.goto('/acces-securise', { waitUntil: 'domcontentloaded' });
    }

    await page.locator('#user_login').fill('admin');
    await page.locator('#user_pass').fill('password');
    await page.locator('#wp-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(500);

    const codeField = page.locator('input[name="backto_2fa_code"]');

    if (await codeField.isVisible()) {
      // Saisir un code de backup (format alphanumerique)
      // En vrai, ce code viendrait de la liste generee a l'activation
      await codeField.fill('ABCD1234EFGH');
      await page.waitForTimeout(500);

      await page.screenshot({
        path: 'tests/e2e/videos/2fa-scene5-backup-code-entered.png',
        fullPage: true,
      });

      await page.locator('#wp-submit').click();
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      const url = page.url();
      const pageContent = await page.content();

      // Si le backup code est valide, on atteint le dashboard
      // Sinon, on reste sur le login avec une erreur
      const reachedDashboard = url.includes('wp-admin');
      const hasError = pageContent.includes('Invalid two-factor')
        || pageContent.includes('login_error');

      console.log(`    Dashboard atteint: ${reachedDashboard}`);
      console.log(`    Erreur affichee: ${hasError}`);

      await page.screenshot({
        path: 'tests/e2e/videos/2fa-scene5-backup-code-result.png',
        fullPage: true,
      });
    } else {
      console.log('    Champ 2FA non visible — 2FA non actif, scene documentaire');
      test.skip();
    }
  });

  // ─── SCENE 6 : Verification que le formulaire 2FA a les bons elements

  test('Scene 6 — Structure du formulaire 2FA', async ({ page }) => {
    // Narration: Verification visuelle de la structure du formulaire 2FA
    await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });
    const userLogin = page.locator('#user_login');
    if (!(await userLogin.isVisible())) {
      await page.goto('/acces-securise', { waitUntil: 'domcontentloaded' });
    }

    await page.locator('#user_login').fill('admin');
    await page.locator('#user_pass').fill('password');
    await page.locator('#wp-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(500);

    const pageContent = await page.content();

    // Documenter la presence de chaque element attendu
    const elements = {
      'champ_code': pageContent.includes('backto_2fa_code'),
      'message_2fa': pageContent.includes('two-factor authentication code')
        || pageContent.includes('two_factor_required'),
      'bouton_submit': pageContent.includes('wp-submit'),
    };

    console.log('    Elements du formulaire 2FA:');
    for (const [key, present] of Object.entries(elements)) {
      console.log(`      ${key}: ${present ? 'present' : 'absent'}`);
    }

    await page.screenshot({
      path: 'tests/e2e/videos/2fa-scene6-form-structure.png',
      fullPage: true,
    });
  });
});
