// Copies the production build into ../backend/public so Laravel serves the whole app from one folder.
import { cpSync, existsSync, mkdirSync, renameSync, rmSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const dist = fileURLToPath(new URL('../dist/', import.meta.url));
const pub = fileURLToPath(new URL('../../backend/public/', import.meta.url));

if (!existsSync(dist)) throw new Error('Run "vite build" first.');
rmSync(`${pub}assets`, { recursive: true, force: true });
mkdirSync(pub, { recursive: true });
cpSync(dist, pub, { recursive: true });
// index.html would clash with Laravel's index.php, so the app shell is served by a route instead
renameSync(`${pub}index.html`, `${pub}spa.html`);
console.log('Published to backend/public');
