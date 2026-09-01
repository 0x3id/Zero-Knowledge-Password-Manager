/**
 * Build the ZeroKnowledgePM app icon set from public/favicon.svg.
 *
 * Generates:
 *  - public/icons/favicon-{16,32,48}.png      browser fallback sizes
 *  - public/icons/icon-192.png                PWA icon
 *  - public/icons/icon-512.png                PWA icon
 *  - public/icons/icon-512-maskable.png       PWA maskable icon (full-bleed tile)
 *  - public/apple-touch-icon.png              iOS home-screen icon (180x180)
 *  - public/favicon.ico                       legacy multi-size favicon (PNG-in-ICO)
 *
 * Usage: node scripts/build-icons.mjs
 */
import { readFile, writeFile } from 'node:fs/promises';
import { mkdirSync } from 'node:fs';
import sharp from 'sharp';

const src = await readFile('public/favicon.svg', 'utf8');

// Full-bleed variant for the PWA maskable icon: the tile fills the canvas
// so the platform's circular mask never crops the artwork. The shield stays
// inside the 80% safe zone.
const maskableSrc = src
    .replace('<rect x="16" y="16" width="480" height="480" rx="118" fill="url(#zkpmTile)"/>',
        '<rect width="512" height="512" rx="92" fill="url(#zkpmTile)"/>')
    .replace('<rect x="24" y="24" width="464" height="464" rx="110" fill="none" stroke="#38bdf8" stroke-opacity="0.22" stroke-width="5"/>', '');

mkdirSync('public/icons', { recursive: true });

const sizes = [
    ['public/icons/favicon-16.png', 16, src],
    ['public/icons/favicon-32.png', 32, src],
    ['public/icons/favicon-48.png', 48, src],
    ['public/icons/icon-192.png', 192, src],
    ['public/icons/icon-512.png', 512, src],
    ['public/icons/icon-512-maskable.png', 512, maskableSrc],
    ['public/apple-touch-icon.png', 180, src],
];

for (const [file, size, svg] of sizes) {
    await sharp(Buffer.from(svg), { density: 384 }).resize(size, size).png().toFile(file);
    console.log(`✓ ${file} (${size}x${size})`);
}

// Wrap the 16/32/48 PNGs in a legacy ICO container (PNG-compressed entries
// are supported by every modern browser).
const icoPngs = [
    ['public/icons/favicon-16.png', 16],
    ['public/icons/favicon-32.png', 32],
    ['public/icons/favicon-48.png', 48],
];

const entries = [];
for (const [file, size] of icoPngs) {
    const png = await readFile(file);
    entries.push({
        png,
        width: size === 16 ? 0 : size, // 16 is stored as 0 in the ICO header
        height: size === 16 ? 0 : size,
    });
}

const header = Buffer.alloc(6);
header.writeUInt16LE(0, 0); // reserved
header.writeUInt16LE(1, 2); // ICO type
header.writeUInt16LE(entries.length, 4);

const dir = Buffer.alloc(16 * entries.length);
let offset = 6 + dir.length;
entries.forEach((entry, i) => {
    const e = dir.subarray(i * 16, (i + 1) * 16);
    e.writeUInt8(entry.width, 0);
    e.writeUInt8(entry.height, 1);
    e.writeUInt8(0, 2); // palette
    e.writeUInt8(0, 3); // reserved
    e.writeUInt16LE(1, 4); // planes
    e.writeUInt16LE(32, 6); // bpp
    e.writeUInt32LE(entry.png.length, 8);
    e.writeUInt32LE(offset, 12);
    offset += entry.png.length;
});

await writeFile('public/favicon.ico', Buffer.concat([header, dir, ...entries.map((e) => e.png)]));
console.log('✓ public/favicon.ico');
