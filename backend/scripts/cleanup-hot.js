import { existsSync } from 'node:fs';
import { unlink } from 'node:fs/promises';
import { resolve } from 'node:path';

const hotPath = resolve(process.cwd(), 'public', 'hot');

if (existsSync(hotPath)) {
    await unlink(hotPath);
    console.log('Removed public/hot');
}
