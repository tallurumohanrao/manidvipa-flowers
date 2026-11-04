import fs from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";
import sharp from "sharp";

const toolsDirectory = path.dirname(fileURLToPath(import.meta.url));
const projectRoot = path.resolve(toolsDirectory, "..");
const sourcePath = path.join(projectRoot, "public", "manidvipa-favicon.png");
const appDirectory = path.join(projectRoot, "src", "app");

function pngToIco(png) {
  const header = Buffer.alloc(22);

  header.writeUInt16LE(0, 0); // Reserved
  header.writeUInt16LE(1, 2); // ICO image
  header.writeUInt16LE(1, 4); // One image
  header.writeUInt8(0, 6); // 256px width
  header.writeUInt8(0, 7); // 256px height
  header.writeUInt8(0, 8); // No palette
  header.writeUInt8(0, 9); // Reserved
  header.writeUInt16LE(1, 10); // Color planes
  header.writeUInt16LE(32, 12); // Bits per pixel
  header.writeUInt32LE(png.length, 14);
  header.writeUInt32LE(header.length, 18);

  return Buffer.concat([header, png]);
}

const source = sharp(sourcePath).ensureAlpha();
const faviconPng = await source
  .clone()
  .resize(256, 256, { fit: "contain", background: "transparent" })
  .png()
  .toBuffer();
const appleIcon = await source
  .clone()
  .resize(180, 180, { fit: "contain", background: "#ffffff" })
  .png()
  .toBuffer();

await fs.writeFile(path.join(appDirectory, "favicon.ico"), pngToIco(faviconPng));
await fs.writeFile(path.join(appDirectory, "apple-icon.png"), appleIcon);

console.log("Generated src/app/favicon.ico and src/app/apple-icon.png");
