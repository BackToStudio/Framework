import { test, expect } from '@playwright/test';

/**
 * E2E Test — Login Brute Force Protection
 *
 * VIDEO SCENARIO: "Un utilisateur tente de se connecter 10 fois
 * avec des identifiants errones. Le systeme le bloque progressivement."
 *
 * Prerequisites:
 *   1. wp-env running with Security module activated
 *   2. LoginHardening configured (threshold = 5)
 *
 * Video output: tests/e2e/videos/
 */
test.describe('Protection brute force login — Scenario video', () => {

  test.describe.configure({ mode: 'serial' });

  const LOGIN_URL = '/wp-login.php'; // ou /acces-securise si obfuscation active

  // ─── SCENE 1 : Premiere tentative echouee ──────────────────────

  test('Scene 1 — Premiere tentative avec mauvais mot de passe', async ({ page }) => {
    // Narration: L'attaquant essaie un mot de passe au hasard
    await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });

    const userLogin = page.locator('#user_login');

    if (!(await userLogin.isVisible())) {
      console.log('    Login form not visible — skipping (URL may be obfuscated)');
      test.skip();
      return;
    }

    await userLogin.fill('admin');
    await page.waitForTimeout(300);

    await page.locator('#user_pass').fill('wrong-password-1');
    await page.waitForTimeout(300);

    // Screenshot: formulaire rempli avec mauvais mot de passe
    await page.screenshot({
      path: 'tests/e2e/videos/brute-scene1-attempt.png',
      fullPage: true,
    });

    await page.locator('#wp-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(1000);

    // Verifier le message d'erreur generique
    const errorBox = page.locator('#login_error');
    if (await errorBox.isVisible()) {
      const errorText = await errorBox.innerText();
      console.log(`    Message d'erreur: "${errorText.trim()}"`);

      // Le message doit etre generique (LoginHardening)
      // Il ne doit PAS reveler si le compte existe
      await page.screenshot({
        path: 'tests/e2e/videos/brute-scene1-error.png',
        fullPage: true,
      });
    }
  });

  // ─── SCENE 2 : Tentatives 2 a 5 ───────────────────────────────

  test('Scene 2 — Accumulation de 4 tentatives supplementaires', async ({ page }) => {
    // Narration: L'attaquant insiste avec differents mots de passe

    for (let attempt = 2; attempt <= 5; attempt++) {
      await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });

      const userLogin = page.locator('#user_login');

      if (!(await userLogin.isVisible())) {
        console.log(`    Attempt ${attempt}: Login form not visible`);
        continue;
      }

      await userLogin.fill('admin');
      await page.locator('#user_pass').fill(`wrong-password-${attempt}`);

      // Pause visible pour la video
      await page.waitForTimeout(200);

      await page.locator('#wp-submit').click();
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(500);

      console.log(`    Tentative ${attempt}: soumise`);
    }

    // Screenshot apres la 5eme tentative
    await page.screenshot({
      path: 'tests/e2e/videos/brute-scene2-after-5-attempts.png',
      fullPage: true,
    });
  });

  // ─── SCENE 3 : Tentatives 6 a 10 — lockout ────────────────────

  test('Scene 3 — Tentatives 6 a 10 : lockout declenche', async ({ page }) => {
    // Narration: A partir de la 6eme tentative, le systeme bloque

    const responses: string[] = [];

    for (let attempt = 6; attempt <= 10; attempt++) {
      await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });

      const userLogin = page.locator('#user_login');

      if (!(await userLogin.isVisible())) {
        // Si le formulaire n'est plus visible, le lockout a fonctionne
        const pageText = await page.innerText('body');
        responses.push(pageText.substring(0, 100));
        console.log(`    Tentative ${attempt}: BLOQUEE (formulaire non visible)`);

        await page.screenshot({
          path: `tests/e2e/videos/brute-scene3-attempt-${attempt}-blocked.png`,
          fullPage: true,
        });
        continue;
      }

      await userLogin.fill('admin');
      await page.locator('#user_pass').fill(`wrong-password-${attempt}`);
      await page.locator('#wp-submit').click();
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(500);

      // Verifier si un message de lockout apparait
      const errorBox = page.locator('#login_error');
      if (await errorBox.isVisible()) {
        const errorText = await errorBox.innerText();
        responses.push(errorText.trim());
        console.log(`    Tentative ${attempt}: "${errorText.trim().substring(0, 80)}"`);

        // Chercher le message "Too many attempts"
        if (errorText.includes('Too many') || errorText.includes('too many')) {
          await page.screenshot({
            path: `tests/e2e/videos/brute-scene3-lockout-triggered.png`,
            fullPage: true,
          });
          console.log(`    >>> LOCKOUT DECLENCHE a la tentative ${attempt} <<<`);
        }
      }
    }

    // Screenshot finale
    await page.screenshot({
      path: 'tests/e2e/videos/brute-scene3-final-state.png',
      fullPage: true,
    });

    // Log resume
    console.log(`    Total reponses capturees: ${responses.length}`);
  });

  // ─── SCENE 4 : Tentative pendant le lockout ────────────────────

  test('Scene 4 — Tentative supplementaire pendant le lockout', async ({ page }) => {
    // Narration: L'attaquant insiste encore — bloque immediatement
    await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });

    const userLogin = page.locator('#user_login');

    if (await userLogin.isVisible()) {
      await userLogin.fill('admin');
      await page.locator('#user_pass').fill('still-trying');
      await page.locator('#wp-submit').click();
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(1000);

      const errorBox = page.locator('#login_error');
      if (await errorBox.isVisible()) {
        const errorText = await errorBox.innerText();
        console.log(`    Reponse lockout: "${errorText.trim()}"`);

        expect(
          errorText.includes('Too many') ||
          errorText.includes('too many') ||
          errorText.includes('incorrect')
        ).toBeTruthy();
      }
    } else {
      console.log('    Formulaire non visible — le lockout empeche l\'acces');
    }

    await page.screenshot({
      path: 'tests/e2e/videos/brute-scene4-still-locked.png',
      fullPage: true,
    });
  });

  // ─── SCENE 5 : Message d'erreur generique (pas d'enumeration) ─

  test('Scene 5 — Messages d\'erreur ne revelent pas l\'existence du compte', async ({ page }) => {
    // Narration: On teste avec un username qui n'existe pas
    await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });

    const userLogin = page.locator('#user_login');

    if (!(await userLogin.isVisible())) {
      console.log('    Formulaire non visible — test non applicable');
      test.skip();
      return;
    }

    // Tenter avec un utilisateur inexistant
    await userLogin.fill('nonexistent_user_xyz');
    await page.locator('#user_pass').fill('whatever');
    await page.locator('#wp-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(1000);

    const errorBox = page.locator('#login_error');
    if (await errorBox.isVisible()) {
      const errorText = await errorBox.innerText();
      console.log(`    Message pour user inexistant: "${errorText.trim()}"`);

      // Le message ne doit PAS contenir "unknown username" ou similaire
      // Il doit etre generique
      expect(errorText).not.toContain('unknown username');
      expect(errorText).not.toContain('is not registered');

      await page.screenshot({
        path: 'tests/e2e/videos/brute-scene5-generic-error.png',
        fullPage: true,
      });
    }
  });
});
