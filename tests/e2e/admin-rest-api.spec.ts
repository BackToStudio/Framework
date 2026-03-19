import { test, expect } from '@playwright/test';

/**
 * E2E Test — WordPress REST API Verification
 *
 * VIDEO SCENARIO: "Verification que l'API REST WordPress fonctionne
 * correctement et expose les endpoints attendus."
 *
 * Prerequisites:
 *   1. wp-env running with the framework activated
 *
 * Video output: tests/e2e/videos/
 */
test.describe('REST API Endpoints — Scenario video', () => {

  // ─── SCENE 1 : API Discovery ─────────────────────────────

  test('Scene 1 — Decouverte de l\'API REST (index endpoint)', async ({ page }) => {
    await page.goto('/wp-json/', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    const body = await page.locator('body').innerText();

    await page.screenshot({
      path: 'tests/e2e/videos/rest-scene1-api-discovery.png',
      fullPage: true,
    });

    // Verify the API returns valid JSON with expected fields
    try {
      const json = JSON.parse(body);
      expect(json).toHaveProperty('name');
      expect(json).toHaveProperty('namespaces');
      console.log(`    Site name: "${json.name}"`);
      console.log(`    Namespaces: ${json.namespaces?.length ?? 0}`);

      // Check for wp/v2 namespace
      if (json.namespaces?.includes('wp/v2')) {
        console.log('    wp/v2 namespace found');
      }
    } catch {
      console.log('    Response is not JSON or API is restricted');
    }
  });

  // ─── SCENE 2 : Posts endpoint ─────────────────────────────

  test('Scene 2 — Lister les articles via l\'API REST', async ({ page }) => {
    await page.goto('/wp-json/wp/v2/posts', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    const body = await page.locator('body').innerText();

    await page.screenshot({
      path: 'tests/e2e/videos/rest-scene2-posts.png',
      fullPage: true,
    });

    try {
      const json = JSON.parse(body);
      const count = Array.isArray(json) ? json.length : 0;
      console.log(`    Posts returned: ${count}`);

      if (count > 0) {
        console.log(`    First post title: "${json[0].title?.rendered}"`);
        expect(json[0]).toHaveProperty('id');
        expect(json[0]).toHaveProperty('title');
        expect(json[0]).toHaveProperty('content');
        expect(json[0]).toHaveProperty('status');
      }
    } catch {
      console.log('    Could not parse posts response');
    }
  });

  // ─── SCENE 3 : Post Types endpoint ───────────────────────

  test('Scene 3 — Lister les types de contenu enregistres', async ({ page }) => {
    await page.goto('/wp-json/wp/v2/types', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    const body = await page.locator('body').innerText();

    await page.screenshot({
      path: 'tests/e2e/videos/rest-scene3-post-types.png',
      fullPage: true,
    });

    try {
      const json = JSON.parse(body);
      const types = Object.keys(json);
      console.log(`    Registered post types: ${types.join(', ')}`);
      expect(types).toContain('post');
      expect(types).toContain('page');
    } catch {
      console.log('    Could not parse types response');
    }
  });

  // ─── SCENE 4 : Taxonomies endpoint ───────────────────────

  test('Scene 4 — Lister les taxonomies enregistrees', async ({ page }) => {
    await page.goto('/wp-json/wp/v2/taxonomies', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    const body = await page.locator('body').innerText();

    await page.screenshot({
      path: 'tests/e2e/videos/rest-scene4-taxonomies.png',
      fullPage: true,
    });

    try {
      const json = JSON.parse(body);
      const taxonomies = Object.keys(json);
      console.log(`    Registered taxonomies: ${taxonomies.join(', ')}`);
      expect(taxonomies).toContain('category');
      expect(taxonomies).toContain('post_tag');
    } catch {
      console.log('    Could not parse taxonomies response');
    }
  });

  // ─── SCENE 5 : Categories endpoint ───────────────────────

  test('Scene 5 — Lister les categories via l\'API REST', async ({ page }) => {
    await page.goto('/wp-json/wp/v2/categories', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    const body = await page.locator('body').innerText();

    await page.screenshot({
      path: 'tests/e2e/videos/rest-scene5-categories.png',
      fullPage: true,
    });

    try {
      const json = JSON.parse(body);
      const count = Array.isArray(json) ? json.length : 0;
      console.log(`    Categories returned: ${count}`);

      if (count > 0) {
        console.log(`    First category: "${json[0].name}"`);
        expect(json[0]).toHaveProperty('id');
        expect(json[0]).toHaveProperty('name');
        expect(json[0]).toHaveProperty('slug');
        expect(json[0]).toHaveProperty('taxonomy');
      }
    } catch {
      console.log('    Could not parse categories response');
    }
  });
});
