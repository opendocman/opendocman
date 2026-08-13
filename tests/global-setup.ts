import * as fs from 'fs';
import * as path from 'path';
import * as child_process from 'child_process';

// Playwright does not load .env automatically (bundled dotenv is not wired up
// in this project), so load it here. Only sets vars that are not already set
// in the environment so explicit shell overrides still win.
function loadEnvFile(filePath: string): void {
  if (!fs.existsSync(filePath)) return;
  const lines = fs.readFileSync(filePath, 'utf8').split('\n');
  for (let raw of lines) {
    const line = raw.trim();
    if (!line || line.startsWith('#') || !line.includes('=')) continue;
    const eq = line.indexOf('=');
    const key = line.slice(0, eq).trim();
    const value = line.slice(eq + 1).trim();
    if (!key) continue;
    if (process.env[key] === undefined) {
      process.env[key] = value;
    }
  }
}

export default async function globalSetup() {
  const root = path.resolve(__dirname, '..');
  loadEnvFile(path.join(root, '.env'));

  // Seed a non-admin user (idempotent) required by the Permission-inheritance
  // E2E suite. Best-effort: skip if php/db isn't reachable from the test
  // runner (e.g. the app runs in Docker); the seed can be run manually via
  // `php scripts/seed_test_user.php`.
  try {
    child_process.execFileSync('php', ['scripts/seed_test_user.php'], {
      cwd: root,
      env: process.env,
      stdio: 'pipe',
    });
  } catch (err: any) {
    console.warn(
      '[globalSetup] seed_test_user.php skipped — run `php scripts/seed_test_user.php` manually. ' +
        (err?.message || err)
    );
  }
}