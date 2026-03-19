import { test, expect } from '@playwright/test';

/**
 * E2E Test — Security Audit Log Admin Page
 *
 * VIDEO SCENARIO: "L'administrateur consulte le journal d'audit securite,
 * filtre les evenements par type et severite, exporte en CSV,
 * et purge les anciens evenements."
 *
 * Prerequisites:
 *   1. wp-env running with Security module activated
 *   2. SecurityAuditLogger + AuditLogAdminPage registered
 *   3. Admin user: admin / password
 *   4. Some security events already logged (login attempts, etc.)
 *
 * Video output: tests/e2e/videos/
 */
test.describe('Audit Log admin page — Scenario video', () => {

  test.describe.configure({ mode: 'serial' });

  const LOGIN_URL = '/wp-login.php';

  // Helper: se connecter comme admin
  async function loginAsAdmin(page: any) {
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

    // Si 2FA actif, le test documente le cas sans 2FA
    const url = page.url();
    if (!url.includes('wp-admin')) {
      console.log('    Login bloque (peut-etre 2FA actif) — on continue quand meme');
    }
  }

  // ─── SCENE 0 : Generer des evenements de securite ─────────────────

  test('Scene 0 — Generer des evenements en tentant des logins errones', async ({ page }) => {
    // Narration: On genere volontairement des evenements pour peupler l'audit log

    for (let i = 1; i <= 3; i++) {
      await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });
      const userLogin = page.locator('#user_login');
      if (!(await userLogin.isVisible())) {
        await page.goto('/acces-securise', { waitUntil: 'domcontentloaded' });
      }

      const loginField = page.locator('#user_login');
      if (await loginField.isVisible()) {
        await loginField.fill('admin');
        await page.locator('#user_pass').fill(`bad-password-${i}`);
        await page.locator('#wp-submit').click();
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(300);
        console.log(`    Tentative echouee ${i} generee`);
      }
    }

    // Maintenant un login reussi
    await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });
    const userLogin = page.locator('#user_login');
    if (!(await userLogin.isVisible())) {
      await page.goto('/acces-securise', { waitUntil: 'domcontentloaded' });
    }

    const loginField = page.locator('#user_login');
    if (await loginField.isVisible()) {
      await loginField.fill('admin');
      await page.locator('#user_pass').fill('password');
      await page.locator('#wp-submit').click();
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(500);
      console.log('    Login reussi genere');
    }

    await page.screenshot({
      path: 'tests/e2e/videos/audit-scene0-events-generated.png',
      fullPage: true,
    });
  });

  // ─── SCENE 1 : Navigation vers la page Audit Log ──────────────────

  test('Scene 1 — Acces a la page Audit Log dans wp-admin', async ({ page }) => {
    // Narration: L'admin navigue vers le menu "Audit Log"
    await loginAsAdmin(page);

    // Le menu slug est "backto-audit-log"
    await page.goto('/wp-admin/admin.php?page=backto-audit-log', {
      waitUntil: 'domcontentloaded',
    });
    await page.waitForTimeout(1000);

    const pageContent = await page.content();
    const hasAuditPage = pageContent.includes('Security Audit Log')
      || pageContent.includes('backto-audit-log');

    console.log(`    Page Audit Log trouvee: ${hasAuditPage}`);

    // Verifier la presence de la table
    const hasTable = pageContent.includes('<table')
      || pageContent.includes('widefat');

    console.log(`    Table d'evenements presente: ${hasTable}`);

    await page.screenshot({
      path: 'tests/e2e/videos/audit-scene1-audit-page.png',
      fullPage: true,
    });
  });

  // ─── SCENE 2 : La table affiche les evenements ────────────────────

  test('Scene 2 — La table affiche les derniers evenements', async ({ page }) => {
    // Narration: La table montre login_success, login_failed, etc.
    await loginAsAdmin(page);
    await page.goto('/wp-admin/admin.php?page=backto-audit-log', {
      waitUntil: 'domcontentloaded',
    });
    await page.waitForTimeout(500);

    const pageContent = await page.content();

    // Verifier la presence d'evenements connus
    const eventTypes = {
      'login_success': pageContent.includes('login_success'),
      'login_failed': pageContent.includes('login_failed'),
    };

    console.log('    Evenements detectes dans la table:');
    for (const [event, present] of Object.entries(eventTypes)) {
      console.log(`      ${event}: ${present ? 'present' : 'absent'}`);
    }

    // Verifier les colonnes (Time, Event, Severity, Details)
    const hasColumns = pageContent.includes('Time')
      && pageContent.includes('Event')
      && pageContent.includes('Severity')
      && pageContent.includes('Details');

    console.log(`    Colonnes correctes: ${hasColumns}`);

    await page.screenshot({
      path: 'tests/e2e/videos/audit-scene2-events-table.png',
      fullPage: true,
    });
  });

  // ─── SCENE 3 : Filtrage par type d'evenement ──────────────────────

  test('Scene 3 — Filtrage par type login_failed', async ({ page }) => {
    // Narration: L'admin filtre pour ne voir que les echecs de login
    await loginAsAdmin(page);
    await page.goto('/wp-admin/admin.php?page=backto-audit-log&event=login_failed', {
      waitUntil: 'domcontentloaded',
    });
    await page.waitForTimeout(500);

    const pageContent = await page.content();

    // Le filtre Event devrait avoir "login_failed" selectionne
    const hasFilter = pageContent.includes('login_failed');
    console.log(`    Filtre login_failed applique: ${hasFilter}`);

    // Verifier le select du filtre
    const eventSelect = page.locator('select[name="event"]');
    if (await eventSelect.isVisible()) {
      const selectedValue = await eventSelect.inputValue();
      console.log(`    Valeur selectionnee: ${selectedValue}`);
    }

    await page.screenshot({
      path: 'tests/e2e/videos/audit-scene3-filter-login-failed.png',
      fullPage: true,
    });
  });

  // ─── SCENE 4 : Filtrage par severite "critical" ───────────────────

  test('Scene 4 — Filtrage par severite critical', async ({ page }) => {
    // Narration: L'admin filtre les evenements critiques
    await loginAsAdmin(page);
    await page.goto('/wp-admin/admin.php?page=backto-audit-log&severity=critical', {
      waitUntil: 'domcontentloaded',
    });
    await page.waitForTimeout(500);

    const pageContent = await page.content();

    // Le filtre severity devrait avoir "critical" selectionne
    const severitySelect = page.locator('select[name="severity"]');
    if (await severitySelect.isVisible()) {
      const selectedValue = await severitySelect.inputValue();
      console.log(`    Severite selectionnee: ${selectedValue}`);
    }

    // Les evenements critiques sont affiches en rouge/gras
    const hasCriticalStyle = pageContent.includes('color:#dc3232')
      || pageContent.includes('CRITICAL');

    console.log(`    Evenements critiques visibles: ${hasCriticalStyle}`);

    await page.screenshot({
      path: 'tests/e2e/videos/audit-scene4-filter-critical.png',
      fullPage: true,
    });
  });

  // ─── SCENE 5 : Bouton Export CSV ──────────────────────────────────

  test('Scene 5 — Le bouton Export CSV est present', async ({ page }) => {
    // Narration: L'admin voit le bouton d'export CSV
    await loginAsAdmin(page);
    await page.goto('/wp-admin/admin.php?page=backto-audit-log', {
      waitUntil: 'domcontentloaded',
    });
    await page.waitForTimeout(500);

    const pageContent = await page.content();

    // Verifier la presence du bouton Export CSV
    const hasExportButton = pageContent.includes('Export CSV');
    console.log(`    Bouton Export CSV present: ${hasExportButton}`);

    // Verifier que le formulaire d'export contient le nonce
    const hasExportForm = pageContent.includes('action" value="export"')
      || pageContent.includes('_export_nonce');

    console.log(`    Formulaire d'export securise (nonce): ${hasExportForm}`);

    // Highlight du bouton pour la video
    const exportButton = page.locator('button:has-text("Export CSV")');
    if (await exportButton.isVisible()) {
      await exportButton.scrollIntoViewIfNeeded();
    }

    await page.screenshot({
      path: 'tests/e2e/videos/audit-scene5-export-csv.png',
      fullPage: true,
    });
  });

  // ─── SCENE 6 : Formulaire de purge ────────────────────────────────

  test('Scene 6 — Le formulaire de purge est present et configurable', async ({ page }) => {
    // Narration: L'admin peut purger les evenements anciens
    await loginAsAdmin(page);
    await page.goto('/wp-admin/admin.php?page=backto-audit-log', {
      waitUntil: 'domcontentloaded',
    });
    await page.waitForTimeout(500);

    const pageContent = await page.content();

    // Verifier la presence du formulaire de purge
    const hasPurgeButton = pageContent.includes('Purge');
    console.log(`    Bouton Purge present: ${hasPurgeButton}`);

    // Verifier le champ "days" avec valeur par defaut 90
    const daysInput = page.locator('input[name="days"]');
    if (await daysInput.isVisible()) {
      const defaultValue = await daysInput.inputValue();
      console.log(`    Valeur par defaut (jours): ${defaultValue}`);
      expect(defaultValue).toBe('90');

      // Modifier pour la demo video
      await daysInput.fill('30');
      await page.waitForTimeout(500);
    }

    // Verifier que le formulaire de purge est securise (nonce + confirmation)
    const hasPurgeNonce = pageContent.includes('_purge_nonce');
    const hasConfirmation = pageContent.includes('confirm(');

    console.log(`    Purge securisee (nonce): ${hasPurgeNonce}`);
    console.log(`    Purge avec confirmation JS: ${hasConfirmation}`);

    await page.screenshot({
      path: 'tests/e2e/videos/audit-scene6-purge-form.png',
      fullPage: true,
    });
  });

  // ─── SCENE 7 : Pagination ─────────────────────────────────────────

  test('Scene 7 — La pagination est presente', async ({ page }) => {
    // Narration: Si plus de 50 evenements, la pagination apparait
    await loginAsAdmin(page);
    await page.goto('/wp-admin/admin.php?page=backto-audit-log', {
      waitUntil: 'domcontentloaded',
    });
    await page.waitForTimeout(500);

    const pageContent = await page.content();

    // Verifier la presence de la pagination
    const hasPageIndicator = pageContent.includes('Page 1')
      || pageContent.includes('paged=');

    console.log(`    Indicateur de page present: ${hasPageIndicator}`);

    // Verifier la presence du bouton Next
    const hasNextButton = pageContent.includes('Next');
    console.log(`    Bouton Next present: ${hasNextButton}`);

    // Tester la page 2
    await page.goto('/wp-admin/admin.php?page=backto-audit-log&paged=2', {
      waitUntil: 'domcontentloaded',
    });
    await page.waitForTimeout(500);

    const page2Content = await page.content();
    const hasPage2 = page2Content.includes('Page 2');
    const hasPrevButton = page2Content.includes('Previous');

    console.log(`    Page 2 accessible: ${hasPage2}`);
    console.log(`    Bouton Previous present: ${hasPrevButton}`);

    await page.screenshot({
      path: 'tests/e2e/videos/audit-scene7-pagination.png',
      fullPage: true,
    });
  });
});
