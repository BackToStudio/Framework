import { execSync } from 'child_process';

/**
 * Global setup: verify wp-env is running before tests.
 */
export default async function globalSetup() {
  const baseUrl = process.env.WP_BASE_URL || 'http://localhost:8888';

  try {
    // Quick health check
    const response = await fetch(baseUrl, { method: 'HEAD' });
    if (!response.ok) {
      throw new Error(`WordPress returned ${response.status}`);
    }
    console.log(`\n  WordPress is running at ${baseUrl}\n`);
  } catch {
    console.error(`\n  WordPress is not reachable at ${baseUrl}`);
    console.error('  Start it with: npm run env:start\n');
    process.exit(1);
  }
}
