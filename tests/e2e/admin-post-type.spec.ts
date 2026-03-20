import { test, expect } from '@playwright/test';

/**
 * E2E Test — WordPress Admin Post Type Management
 *
 * VIDEO SCENARIO: "Verification du bon fonctionnement de l'interface
 * d'administration WordPress pour la gestion des types de contenu."
 *
 * Prerequisites:
 *   1. wp-env running with the framework activated
 *   2. Default admin credentials (admin/password)
 *
 * Video output: tests/e2e/videos/
 */
test.describe('Admin Post Type Management — Scenario video', () => {

  const LOGIN_URL = '/wp-login.php';
  const ADMIN_URL = '/wp-admin/';
  const POSTS_URL = '/wp-admin/edit.php';
  const NEW_POST_URL = '/wp-admin/post-new.php';
  const PAGES_URL = '/wp-admin/edit.php?post_type=page';

  // ─── Helper: Login as admin ──────────────────────────────

  async function loginAsAdmin(page: any) {
    await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });

    const userLogin = page.locator('#user_login');
    if (!(await userLogin.isVisible())) {
      console.log('    Login form not visible — may be already logged in');
      return;
    }

    await userLogin.fill('admin');
    await page.locator('#user_pass').fill('password');
    await page.locator('#wp-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(1000);
  }

  // ─── SCENE 1 : Login et acces au Dashboard ──────────────

  test('Scene 1 — Login admin et verification du dashboard', async ({ page }) => {
    await loginAsAdmin(page);

    // Verify we're on the dashboard
    await page.goto(ADMIN_URL, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    // Capture the dashboard
    await page.screenshot({
      path: 'tests/e2e/videos/admin-scene1-dashboard.png',
      fullPage: true,
    });

    // Verify admin menu is present
    const adminMenu = page.locator('#adminmenu');
    const isMenuVisible = await adminMenu.isVisible();
    console.log(`    Admin menu visible: ${isMenuVisible}`);
  });

  // ─── SCENE 2 : Liste des articles ────────────────────────

  test('Scene 2 — Navigation vers la liste des articles', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(POSTS_URL, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    await page.screenshot({
      path: 'tests/e2e/videos/admin-scene2-posts-list.png',
      fullPage: true,
    });

    // Check that the posts table exists
    const postsTable = page.locator('.wp-list-table');
    if (await postsTable.isVisible()) {
      console.log('    Posts table is visible');
    }

    // Verify heading
    const heading = page.locator('.wp-heading-inline');
    if (await heading.isVisible()) {
      const text = await heading.innerText();
      console.log(`    Page heading: "${text}"`);
    }
  });

  // ─── SCENE 3 : Creation d'un nouvel article ─────────────

  test('Scene 3 — Acces au formulaire de creation d\'article', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(NEW_POST_URL, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);

    await page.screenshot({
      path: 'tests/e2e/videos/admin-scene3-new-post.png',
      fullPage: true,
    });

    // Check if the block editor or classic editor is loaded
    const blockEditor = page.locator('.block-editor');
    const classicEditor = page.locator('#post');

    if (await blockEditor.isVisible()) {
      console.log('    Block editor (Gutenberg) detected');
    } else if (await classicEditor.isVisible()) {
      console.log('    Classic editor detected');
    } else {
      console.log('    Editor interface loaded');
    }
  });

  // ─── SCENE 4 : Navigation vers les pages ────────────────

  test('Scene 4 — Gestion des pages (post type hierarchique)', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(PAGES_URL, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    await page.screenshot({
      path: 'tests/e2e/videos/admin-scene4-pages-list.png',
      fullPage: true,
    });

    // Verify we're on the pages screen
    const heading = page.locator('.wp-heading-inline');
    if (await heading.isVisible()) {
      const text = await heading.innerText();
      console.log(`    Page heading: "${text}"`);
      expect(text.toLowerCase()).toContain('page');
    }
  });

  // ─── SCENE 5 : Navigation Categories (Taxonomie) ────────

  test('Scene 5 — Gestion des categories (taxonomie hierarchique)', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/wp-admin/edit-tags.php?taxonomy=category', {
      waitUntil: 'domcontentloaded',
    });
    await page.waitForTimeout(500);

    await page.screenshot({
      path: 'tests/e2e/videos/admin-scene5-categories.png',
      fullPage: true,
    });

    // Check add category form
    const addForm = page.locator('#addtag');
    if (await addForm.isVisible()) {
      console.log('    Add category form is visible');
    }

    // Check categories list table
    const tagsTable = page.locator('.wp-list-table');
    if (await tagsTable.isVisible()) {
      console.log('    Categories table is visible');
    }
  });

  // ─── SCENE 6 : Navigation Tags (Taxonomie plate) ────────

  test('Scene 6 — Gestion des tags (taxonomie non-hierarchique)', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/wp-admin/edit-tags.php?taxonomy=post_tag', {
      waitUntil: 'domcontentloaded',
    });
    await page.waitForTimeout(500);

    await page.screenshot({
      path: 'tests/e2e/videos/admin-scene6-tags.png',
      fullPage: true,
    });

    const heading = page.locator('.wp-heading-inline');
    if (await heading.isVisible()) {
      const text = await heading.innerText();
      console.log(`    Page heading: "${text}"`);
    }
  });
});
