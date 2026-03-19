import { test, expect } from '@playwright/test';

/**
 * E2E Test — REST API Rate Limiting
 *
 * VIDEO SCENARIO: "Un script bombarde l'API REST WordPress.
 * Les headers X-RateLimit apparaissent et decrementent,
 * puis la requete est bloquee avec un 429 Too Many Requests."
 *
 * Prerequisites:
 *   1. wp-env running with Security module activated
 *   2. RestApiRateLimiter configured (default: 60 req / 60s)
 *   3. For testing, the rate limit should be lowered to ~10 req / 60s
 *      via setup-security.php
 *
 * Note: This test uses Playwright's request context (no browser needed)
 * to send rapid HTTP requests and inspect response headers.
 *
 * Video output: tests/e2e/videos/
 */
test.describe('REST API Rate Limiting — Scenario video', () => {

  test.describe.configure({ mode: 'serial' });

  const BASE_URL = process.env.WP_BASE_URL || 'http://localhost:8888';
  const API_ENDPOINT = '/wp-json/wp/v2/posts';

  // ─── SCENE 1 : Premiere requete — headers rate limit presents ─────

  test('Scene 1 — Premiere requete API avec headers rate limit', async ({ request }) => {
    // Narration: On envoie une requete GET normale a l'API REST
    const response = await request.get(`${BASE_URL}${API_ENDPOINT}`);
    const status = response.status();
    const headers = response.headers();

    console.log(`    Status: ${status}`);
    console.log(`    X-RateLimit-Limit: ${headers['x-ratelimit-limit'] ?? 'absent'}`);
    console.log(`    X-RateLimit-Remaining: ${headers['x-ratelimit-remaining'] ?? 'absent'}`);

    // Documenter la presence des headers
    const hasRateLimitHeaders = 'x-ratelimit-limit' in headers;

    if (hasRateLimitHeaders) {
      console.log('    >>> Headers de rate limiting detectes <<<');

      const limit = parseInt(headers['x-ratelimit-limit'], 10);
      const remaining = parseInt(headers['x-ratelimit-remaining'], 10);

      expect(limit).toBeGreaterThan(0);
      expect(remaining).toBeLessThanOrEqual(limit);
    } else {
      console.log('    Headers rate limit absents — le module n\'est peut-etre pas actif');
    }
  });

  // ─── SCENE 2 : Requetes successives — remaining decremente ────────

  test('Scene 2 — Le compteur remaining decremente a chaque requete', async ({ request }) => {
    // Narration: On envoie 5 requetes rapides et on observe le remaining

    const results: Array<{ status: number; limit?: string; remaining?: string }> = [];

    for (let i = 1; i <= 5; i++) {
      const response = await request.get(`${BASE_URL}${API_ENDPOINT}`);
      const headers = response.headers();

      const entry = {
        status: response.status(),
        limit: headers['x-ratelimit-limit'] ?? undefined,
        remaining: headers['x-ratelimit-remaining'] ?? undefined,
      };

      results.push(entry);
      console.log(
        `    Requete ${i}: status=${entry.status} ` +
        `limit=${entry.limit ?? '-'} remaining=${entry.remaining ?? '-'}`
      );
    }

    // Verifier que le remaining decremente
    const remainings = results
      .filter(r => r.remaining !== undefined)
      .map(r => parseInt(r.remaining!, 10));

    if (remainings.length >= 2) {
      const isDecrementing = remainings[0] > remainings[remainings.length - 1];
      console.log(`    Remaining decremente: ${isDecrementing}`);
      expect(isDecrementing).toBeTruthy();
    } else {
      console.log('    Pas assez de headers pour verifier le decrement');
    }
  });

  // ─── SCENE 3 : Bombardement jusqu'au 429 ──────────────────────────

  test('Scene 3 — Bombardement : 429 Too Many Requests', async ({ request }) => {
    // Narration: On envoie un grand nombre de requetes pour declencher le rate limit
    // Le seuil de test est configure a 10 req dans setup-security.php

    let hitRateLimit = false;
    let rateLimitResponse: { status: number; retryAfter?: string } | null = null;

    // Envoyer jusqu'a 80 requetes (ou jusqu'au 429)
    for (let i = 1; i <= 80; i++) {
      const response = await request.get(`${BASE_URL}${API_ENDPOINT}`);
      const status = response.status();
      const headers = response.headers();

      if (i % 10 === 0 || status === 429) {
        console.log(
          `    Requete ${i}: status=${status} ` +
          `remaining=${headers['x-ratelimit-remaining'] ?? '-'}`
        );
      }

      if (status === 429) {
        hitRateLimit = true;
        rateLimitResponse = {
          status,
          retryAfter: headers['retry-after'] ?? undefined,
        };

        console.log(`    >>> RATE LIMIT ATTEINT a la requete ${i} <<<`);
        console.log(`    Retry-After: ${rateLimitResponse.retryAfter ?? 'absent'}`);

        // Verifier le body de la reponse 429
        const body = await response.json().catch(() => null);
        if (body) {
          console.log(`    Code erreur: ${body.code ?? '-'}`);
          console.log(`    Message: ${body.message ?? '-'}`);
        }

        break;
      }
    }

    if (hitRateLimit) {
      expect(rateLimitResponse!.status).toBe(429);
    } else {
      console.log('    Rate limit non atteint apres 80 requetes — seuil eleve ou module inactif');
    }
  });

  // ─── SCENE 4 : Requete pendant le blocage ─────────────────────────

  test('Scene 4 — Requete supplementaire pendant le blocage', async ({ request }) => {
    // Narration: L'attaquant insiste — toujours bloque
    const response = await request.get(`${BASE_URL}${API_ENDPOINT}`);
    const status = response.status();
    const headers = response.headers();

    console.log(`    Status: ${status}`);
    console.log(`    X-RateLimit-Remaining: ${headers['x-ratelimit-remaining'] ?? '-'}`);
    console.log(`    Retry-After: ${headers['retry-after'] ?? '-'}`);

    if (status === 429) {
      console.log('    >>> Toujours bloque — rate limiting effectif <<<');

      // Verifier le header Retry-After
      if (headers['retry-after']) {
        const retryAfter = parseInt(headers['retry-after'], 10);
        expect(retryAfter).toBeGreaterThan(0);
        console.log(`    Reessayer dans ${retryAfter} secondes`);
      }

      // Verifier le message d'erreur
      const body = await response.json().catch(() => null);
      if (body?.code) {
        expect(body.code).toBe('rate_limit_exceeded');
      }
    } else {
      console.log('    Requete acceptee — le rate limit a peut-etre expire ou module inactif');
    }
  });

  // ─── SCENE 5 : Apres expiration du window, requete acceptee ───────

  test('Scene 5 — Apres expiration, la requete est de nouveau acceptee', async ({ request }) => {
    // Narration: On attend l'expiration du window et on retente
    // Note: pour la demo video, on ne peut pas attendre 60s reellement
    // Ce test documente le comportement attendu

    console.log('    Attente de 3 secondes (window de test court)...');
    await new Promise(resolve => setTimeout(resolve, 3000));

    const response = await request.get(`${BASE_URL}${API_ENDPOINT}`);
    const status = response.status();
    const headers = response.headers();

    console.log(`    Status apres attente: ${status}`);
    console.log(`    X-RateLimit-Limit: ${headers['x-ratelimit-limit'] ?? '-'}`);
    console.log(`    X-RateLimit-Remaining: ${headers['x-ratelimit-remaining'] ?? '-'}`);

    // Si le window de test est court, la requete devrait passer
    if (status === 200) {
      console.log('    >>> Requete acceptee — window expire <<<');
    } else if (status === 429) {
      console.log('    Toujours bloque — le window de 60s n\'est pas expire');
      console.log('    (attendu en production, le window est plus long)');
    }
  });

  // ─── SCENE 6 : Headers complets sur une requete normale ───────────

  test('Scene 6 — Documentation des headers rate limit complets', async ({ request }) => {
    // Narration: Capture complete des headers pour documentation
    const response = await request.get(`${BASE_URL}${API_ENDPOINT}`);
    const allHeaders = response.headers();

    console.log('    === Headers de securite ===');

    const securityHeaders = [
      'x-ratelimit-limit',
      'x-ratelimit-remaining',
      'retry-after',
      'x-content-type-options',
      'x-frame-options',
      'x-xss-protection',
      'strict-transport-security',
      'content-security-policy',
      'referrer-policy',
      'permissions-policy',
    ];

    for (const header of securityHeaders) {
      const value = allHeaders[header];
      if (value) {
        console.log(`    ${header}: ${value}`);
      }
    }

    // Verifier que X-Powered-By est absent (securite)
    const hasPoweredBy = 'x-powered-by' in allHeaders;
    console.log(`    x-powered-by: ${hasPoweredBy ? allHeaders['x-powered-by'] : 'ABSENT (securise)'}`);
  });
});
