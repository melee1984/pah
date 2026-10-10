import { chromium } from "playwright";
import path from "node:path";
import { pathToFileURL } from "node:url";
import fs from "node:fs/promises";

const buildDir = path.resolve("presentation-work/build");
const assetsDir = path.resolve("presentation-work/assets");
await fs.mkdir(assetsDir, { recursive: true });

const browser = await chromium.launch({
  headless: true,
  executablePath: "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
  args: ["--no-sandbox"],
});
const page = await browser.newPage({ viewport: { width: 1280, height: 720 }, deviceScaleFactor: 1.5 });
await page.goto(pathToFileURL(path.join(buildDir, "merchant-screens.html")).href, { waitUntil: "load" });
for (const id of ["dashboard", "orders", "menu", "profile", "sales"]) {
  await page.locator(`#${id}`).screenshot({ path: path.join(assetsDir, `merchant-${id}.png`) });
}
await browser.close();
