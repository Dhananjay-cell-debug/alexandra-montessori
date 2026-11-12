import * as baseline from '../frontend/src/data/site.js';
import { writeFile } from 'node:fs/promises';
await writeFile(new URL('./baseline.json', import.meta.url), JSON.stringify({
  settings:baseline.allSettings,
  testimonials:baseline.testimonials,
  welcome:baseline.philosophy.paragraphs.join('\n\n'),
  photos:baseline.allPhotos,
  brand:baseline.brand,
}, null, 2));
console.log('Saved the current source baseline for local CMS setup.');
