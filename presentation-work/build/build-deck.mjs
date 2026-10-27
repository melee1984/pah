import fs from "node:fs/promises";
import path from "node:path";
import { pathToFileURL } from "node:url";
import { Presentation, PresentationFile } from "@oai/artifact-tool";

const workspaceDir = "/Users/larryparba/web/pah";
const SKILL_DIR = "/Users/larryparba/.codex/plugins/cache/openai-primary-runtime/presentations/26.909.12148/skills/presentations";
const RUNTIME_PYTHON = "/Users/larryparba/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/bin/python3";
const assetsDir = path.join(workspaceDir, "presentation-work/assets");
const stagingDir = path.join(workspaceDir, "presentation-work/finalizer");
const FINAL_PPTX = path.join(workspaceDir, "presentation-output/PahatudFood_Merchant_Partner_Orientation_2026-10-07_v2.pptx");
await fs.mkdir(stagingDir, { recursive: true });
await fs.mkdir(path.dirname(FINAL_PPTX), { recursive: true });

const { finalizePresentation } = await import(pathToFileURL(path.join(SKILL_DIR, "container_tools/artifact_tool_utils.mjs")).href);

const C = {
  red: "#F43B35",
  redDark: "#B52D28",
  redSoft: "#FFF0EE",
  cream: "#FFF7EF",
  cream2: "#F7F1EB",
  white: "#FFFFFF",
  ink: "#1F2523",
  muted: "#6D7471",
  line: "#E8DED7",
  green: "#2FA66C",
  greenSoft: "#EDF8F2",
  amber: "#D99322",
  amberSoft: "#FFF6E7",
  charcoal: "#29302D",
};
const FONT_HEAD = "Manrope";
const FONT_BODY = "DM Sans";
const W = 1280;
const H = 720;
const EMU = 12700;

const presentation = Presentation.create({ slideSize: { width: W, height: H } });

const files = {};
for (const [key, name] of Object.entries({
  hero: "merchant-hero.png",
  dine: "dine-in.png",
  pickup: "pickup.png",
  delivery: "delivery.png",
  mark: "pahatud-mark.jpg",
  customerHome: "customer-home.png",
  customerRestaurants: "customer-restaurants.png",
  dashboard: "merchant-dashboard.png",
  orders: "merchant-orders.png",
  menu: "merchant-menu.png",
  profile: "merchant-profile.png",
  sales: "merchant-sales.png",
})) files[key] = await fs.readFile(path.join(assetsDir, name));

function slideBase(fill = C.cream) {
  const slide = presentation.slides.add();
  slide.background.fill = fill;
  return slide;
}

function box(slide, x, y, w, h, fill = "none", lineFill = "none", radius = false) {
  return slide.shapes.add({
    geometry: radius ? "roundRect" : "rect",
    position: { left: x, top: y, width: w, height: h },
    fill,
    line: { style: "solid", fill: lineFill, width: lineFill === "none" ? 0 : 1 },
  });
}

function textBox(slide, text, x, y, w, h, options = {}) {
  const shape = slide.shapes.add({
    geometry: "textbox",
    name: options.name,
    position: { left: x, top: y, width: w, height: h },
    fill: options.fill ?? "none",
    line: { style: "solid", fill: options.line ?? "none", width: options.line && options.line !== "none" ? 1 : 0 },
  });
  shape.text = text;
  shape.text.style = {
    typeface: options.font ?? FONT_BODY,
    fontSize: options.size ?? 24,
    bold: options.bold ?? false,
    color: options.color ?? C.ink,
    alignment: options.align ?? "left",
    verticalAlignment: options.valign ?? "top",
    autoFit: options.autoFit ?? "none",
    wrap: "square",
    lineSpacing: options.lineSpacing ?? 1.0,
    insets: options.insets ?? { top: 0, right: 0, bottom: 0, left: 0 },
  };
  return shape;
}

function richText(slide, runs, x, y, w, h, options = {}) {
  const shape = textBox(slide, "", x, y, w, h, options);
  shape.text.set([runs]);
  shape.text.style = {
    typeface: options.font ?? FONT_BODY,
    fontSize: options.size ?? 24,
    color: options.color ?? C.ink,
    alignment: options.align ?? "left",
    verticalAlignment: options.valign ?? "top",
    autoFit: options.autoFit ?? "none",
    wrap: "square",
    lineSpacing: options.lineSpacing ?? 1.0,
    insets: options.insets ?? { top: 0, right: 0, bottom: 0, left: 0 },
  };
  return shape;
}

function addImage(slide, blob, contentType, x, y, w, h, options = {}) {
  return slide.images.add({
    blob,
    contentType,
    alt: options.alt ?? "Presentation image",
    fit: options.fit ?? "cover",
    position: { left: x, top: y, width: w, height: h },
    geometry: options.rounded ? "roundRect" : "rect",
    ...(options.rounded ? { borderRadius: "rounded-2xl" } : {}),
    ...(options.crop ? { crop: options.crop } : {}),
  });
}

function brandMark(slide, inverse = false) {
  if (inverse) {
    box(slide, 52, 43, 34, 34, C.white, C.white, true);
    textBox(slide, "P", 54, 43, 30, 32, { font: FONT_HEAD, size: 19, bold: true, color: C.red, align: "center", valign: "middle" });
  } else {
    addImage(slide, files.mark, "image/jpeg", 52, 43, 34, 34, { alt: "Pahatud mark", fit: "cover", rounded: true });
  }
}

function header(slide, title, subtitle, number, options = {}) {
  brandMark(slide, options.inverse);
  textBox(slide, title, 105, 42, 1055, 52, { font: FONT_HEAD, size: 31, bold: true, color: options.inverse ? C.white : C.ink, valign: "middle" });
  if (subtitle) textBox(slide, subtitle, 106, 98, 1055, 32, { size: 15, color: options.inverse ? "#FFE7E4" : C.muted });
  if (number) textBox(slide, String(number).padStart(2, "0"), 1178, 654, 50, 22, { size: 11, bold: true, color: options.inverse ? "#FFE7E4" : "#9B9F9D", align: "right" });
}

function note(slide, text) {
  slide.speakerNotes.textFrame.setText(text);
}

function bulletList(slide, items, x, y, w, h, options = {}) {
  const shape = textBox(slide, "", x, y, w, h, { size: options.size ?? 18, color: options.color ?? C.ink });
  shape.text = items.map((item) => ({
    bulletCharacter: "•",
    marginLeft: (options.marginLeft ?? 20) * EMU,
    indent: -(options.hanging ?? 10) * EMU,
    spaceAfter: (options.spaceAfter ?? 11) * 100,
    runs: typeof item === "string" ? [item] : item,
  }));
  shape.text.style = {
    typeface: FONT_BODY,
    fontSize: options.size ?? 18,
    color: options.color ?? C.ink,
    autoFit: "none",
    wrap: "square",
    lineSpacing: 1.05,
    insets: { top: 0, right: 0, bottom: 0, left: 0 },
  };
  return shape;
}

function step(slide, n, title, body, x, y, w, options = {}) {
  const color = options.color ?? C.red;
  box(slide, x, y, 42, 42, color, color, true);
  textBox(slide, String(n).padStart(2, "0"), x, y, 42, 42, { font: FONT_HEAD, size: 16, bold: true, color: C.white, align: "center", valign: "middle" });
  textBox(slide, title, x + 58, y - 1, w - 58, 27, { font: FONT_HEAD, size: 17, bold: true });
  textBox(slide, body, x + 58, y + 30, w - 58, 55, { size: 14, color: C.muted, lineSpacing: 1.04 });
}

function styleTable(table, headerFill = C.charcoal) {
  table.styleOptions = { headerRow: true, bandedRows: false };
  table.borders.assign({ style: "solid", fill: C.line, width: 1 });
  for (let c = 0; c < table.columns.count; c += 1) {
    const cell = table.getCell(0, c);
    cell.fill = headerFill;
    cell.text.style = { typeface: FONT_HEAD, fontSize: 15, bold: true, color: C.white, verticalAlignment: "middle", autoFit: "shrinkText" };
  }
  for (let r = 1; r < table.rows.count; r += 1) {
    for (let c = 0; c < table.columns.count; c += 1) {
      const cell = table.getCell(r, c);
      cell.fill = r % 2 ? C.white : "#FBF8F5";
      cell.text.style = { typeface: FONT_BODY, fontSize: 14, color: C.ink, verticalAlignment: "middle", autoFit: "shrinkText" };
    }
  }
}

function tableCell(value, options = {}) {
  return [{
    run: String(value),
    textStyle: {
      typeface: options.font ?? FONT_BODY,
      fontSize: options.size ?? "11pt",
      bold: options.bold ?? false,
      color: options.color ?? C.ink,
    },
  }];
}

function tableMatrix(rows) {
  return rows.map((row, rowIndex) => row.map((value) => tableCell(value, rowIndex === 0
    ? { font: FONT_HEAD, size: "11pt", bold: true, color: C.redDark }
    : { font: FONT_BODY, size: "10.5pt", color: C.ink })));
}

// 1. Cover
{
  const slide = slideBase(C.cream);
  addImage(slide, files.hero, "image/png", 0, 0, W, H, { alt: "Restaurant owner working with a food ordering platform", fit: "cover" });
  box(slide, 0, 0, 545, H, { type: "solid", color: C.cream, transparency: 4 }, "none");
  brandMark(slide);
  textBox(slide, "PAHATUDFOOD", 105, 48, 300, 28, { font: FONT_HEAD, size: 13, bold: true, color: C.red, valign: "middle" });
  textBox(slide, "Merchant Partner\nOrientation", 64, 142, 470, 190, { font: FONT_HEAD, size: 48, bold: true, color: C.ink, lineSpacing: 0.95 });
  textBox(slide, "Commission, dashboard, and service journeys", 67, 346, 410, 62, { size: 23, color: C.redDark, lineSpacing: 1.05 });
  box(slide, 66, 438, 390, 1, C.red, C.red);
  textBox(slide, "Prepared for prospective restaurant partners", 67, 460, 400, 30, { size: 15, color: C.muted });
  textBox(slide, "October 2026", 67, 625, 230, 24, { size: 13, bold: true, color: C.muted });
  note(slide, "Brand and layout reference: Pahatud_Agent_Orientation_2026-09-29_v3 (1).pptx. Hero illustration generated for this presentation with OpenAI image generation.");
}

// 2. Partnership scope
{
  const slide = slideBase();
  header(slide, "What the partnership includes", "A single merchant account connects the customer marketplace, restaurant operations, and fulfilment", 2);
  textBox(slide, "One storefront. Three ways to serve.", 64, 165, 545, 52, { font: FONT_HEAD, size: 30, bold: true });
  bulletList(slide, [
    [{ run: "Customer discovery: ", textStyle: { bold: true } }, "Your restaurant and menu appear in the PahatudFood marketplace."],
    [{ run: "Order control: ", textStyle: { bold: true } }, "Your team reviews, prepares, and updates every order from the merchant dashboard."],
    [{ run: "Flexible service: ", textStyle: { bold: true } }, "Activate dine-in, pickup, delivery, or the service mix agreed for each branch."],
    [{ run: "Clear reporting: ", textStyle: { bold: true } }, "Track sales, recorded platform commission, and estimated net revenue."],
  ], 68, 240, 545, 258, { size: 18, spaceAfter: 15 });
  box(slide, 66, 542, 540, 90, C.charcoal, C.charcoal, true);
  textBox(slide, "Merchant manages the menu and orders. PahatudFood provides marketplace access and delivery coordination.", 90, 560, 490, 55, { size: 17, bold: true, color: C.white, valign: "middle" });
  box(slide, 736, 160, 195, 430, C.white, C.line, true);
  box(slide, 958, 160, 195, 430, C.white, C.line, true);
  addImage(slide, files.customerHome, "image/png", 757, 177, 154, 395, { alt: "PahatudFood customer app home screen", fit: "contain", rounded: true });
  addImage(slide, files.customerRestaurants, "image/png", 979, 177, 154, 395, { alt: "PahatudFood restaurant list screen", fit: "contain", rounded: true });
  textBox(slide, "Customer app", 736, 607, 417, 25, { size: 13, bold: true, color: C.muted, align: "center" });
  note(slide, "Sources: public/images/app-preview/home.png; public/images/app-preview/restaurants.png; resources/views/merchant/partials/documentation.blade.php; routes/web.php merchant dashboard routes.");
}

// 3. Merchant journey
{
  const slide = slideBase(C.white);
  header(slide, "Merchant journey from setup to growth", "The operating model becomes simple once the commercial policy and service modes are agreed", 3);
  box(slide, 95, 287, 1088, 4, C.line, C.line);
  const steps = [
    ["Apply and receive approval", "Submit the restaurant and required documents."],
    ["Complete the account", "Confirm profile, branch, contact, and payout details."],
    ["Build the menu", "Add categories, products, prices, add-ons, and availability."],
    ["Go online", "Receive and fulfil orders through the dashboard."],
    ["Review and improve", "Check sales, commission, estimated earnings, and order outcomes."],
  ];
  steps.forEach((entry, i) => {
    const x = 73 + i * 235;
    box(slide, x + 61, 255, 64, 64, i === 3 ? C.red : C.charcoal, i === 3 ? C.red : C.charcoal, true);
    textBox(slide, String(i + 1).padStart(2, "0"), x + 61, 255, 64, 64, { font: FONT_HEAD, size: 19, bold: true, color: C.white, align: "center", valign: "middle" });
    textBox(slide, entry[0], x, 347, 186, 50, { font: FONT_HEAD, size: 17, bold: true, align: "center", valign: "middle" });
    textBox(slide, entry[1], x, 405, 186, 84, { size: 14, color: C.muted, align: "center", lineSpacing: 1.03 });
  });
  box(slide, 253, 552, 774, 58, C.redSoft, "none", true);
  textBox(slide, "Service availability depends on the modes activated for the restaurant or branch.", 275, 570, 730, 25, { size: 16, bold: true, color: C.redDark, align: "center" });
  note(slide, "Sources: resources/views/merchant/partials/documentation.blade.php; routes/web.php merchant routes; resources/views/merchant/pages/application.blade.php.");
}

// 4. Commission structure
{
  const slide = slideBase();
  header(slide, "PahatudFood commission structure", "The proposed standard rate is calculated from the eligible food subtotal for each completed order", 4);
  box(slide, 65, 165, 425, 444, C.red, C.red, true);
  textBox(slide, "PROPOSED STANDARD RATE", 96, 197, 330, 25, { font: FONT_HEAD, size: 12, bold: true, color: "#FFE5E2" });
  textBox(slide, "15%", 92, 225, 330, 120, { font: FONT_HEAD, size: 82, bold: true, color: C.white, valign: "middle" });
  textBox(slide, "of eligible item prices and paid add-ons", 98, 352, 330, 58, { size: 22, bold: true, color: C.white });
  box(slide, 97, 443, 316, 1, "#FF8D87", "#FF8D87");
  textBox(slide, "The merchant agreement confirms the final rate before activation.", 98, 465, 315, 87, { size: 16, color: "#FFF2F0", lineSpacing: 1.05 });
  textBox(slide, "Platform commission", 559, 178, 560, 32, { font: FONT_HEAD, size: 18, bold: true, color: C.redDark });
  richText(slide, [
    { run: "Eligible food subtotal", textStyle: { bold: true } },
    " × ",
    { run: "agreed commission rate", textStyle: { bold: true, color: C.red } },
    " = platform commission",
  ], 559, 220, 626, 55, { size: 24 });
  textBox(slide, "Merchant amount before adjustments", 559, 302, 560, 32, { font: FONT_HEAD, size: 18, bold: true, color: C.redDark });
  richText(slide, [
    { run: "Eligible food subtotal", textStyle: { bold: true } },
    " − ",
    { run: "platform commission", textStyle: { bold: true, color: C.red } },
    " = merchant amount",
  ], 559, 344, 626, 55, { size: 24 });
  box(slide, 557, 442, 283, 124, C.white, C.line, true);
  box(slide, 861, 442, 283, 124, C.white, C.line, true);
  textBox(slide, "₱500 subtotal", 581, 462, 235, 26, { font: FONT_HEAD, size: 17, bold: true });
  textBox(slide, "PahatudFood ₱75\nMerchant ₱425", 581, 496, 235, 54, { size: 18, color: C.muted });
  textBox(slide, "₱2,000 subtotal", 885, 462, 235, 26, { font: FONT_HEAD, size: 17, bold: true });
  textBox(slide, "PahatudFood ₱300\nMerchant ₱1,700", 885, 496, 235, 54, { size: 18, color: C.muted });
  textBox(slide, "Examples exclude other applicable adjustments.", 560, 590, 580, 22, { size: 12, color: C.muted });
  note(slide, "Sources: resources/views/pages/home.blade.php states a straightforward 15% commission confirmed before activation. app/Http/Controllers/Api/Merchant/ItemController.php stores commission from item price and the merchant percentage. Examples are arithmetic illustrations, not contract terms.");
}

// 5. Dedicated 15% example
{
  const slide = slideBase(C.white);
  header(slide, "15% commission on a ₱1,000 food subtotal", "A simple settlement example before discounts, refunds, taxes, or other applicable adjustments", 5);
  richText(slide, [
    { run: "₱1,000", textStyle: { bold: true, color: C.ink, fontSize: "42pt" } },
    { run: " × 15% = ", textStyle: { color: C.muted, fontSize: "28pt" } },
    { run: "₱150", textStyle: { bold: true, color: C.red, fontSize: "42pt" } },
  ], 68, 176, 705, 95, { valign: "middle" });
  textBox(slide, "PahatudFood commission", 70, 274, 700, 28, { size: 17, bold: true, color: C.redDark });
  richText(slide, [
    { run: "₱1,000", textStyle: { bold: true, color: C.ink, fontSize: "38pt" } },
    { run: " − ₱150 = ", textStyle: { color: C.muted, fontSize: "26pt" } },
    { run: "₱850", textStyle: { bold: true, color: C.green, fontSize: "38pt" } },
  ], 68, 332, 705, 88, { valign: "middle" });
  textBox(slide, "Merchant amount before other adjustments", 70, 422, 700, 28, { size: 17, bold: true, color: "#257B50" });
  const table = slide.tables.add({
    rows: 4, columns: 2, left: 814, top: 170, width: 390, height: 268,
    columnWidths: [238, 152],
    values: tableMatrix([
      ["Settlement line", "Amount (₱)"],
      ["PahatudFood commission", "150.00"],
      ["Merchant share", "850.00"],
      ["Total food subtotal", "1,000.00"],
    ]),
  });
  styleTable(table, C.red);
  table.getCell(2, 1).text.style = { typeface: FONT_HEAD, fontSize: 17, bold: true, color: C.green, verticalAlignment: "middle" };
  table.getCell(3, 0).fill = C.charcoal; table.getCell(3, 1).fill = C.charcoal;
  table.cells.set(3, 0, tableCell("Total food subtotal", { font: FONT_HEAD, size: "11pt", bold: true, color: C.white }));
  table.cells.set(3, 1, tableCell("1,000.00", { font: FONT_HEAD, size: "12pt", bold: true, color: C.white }));
  table.getCell(3, 0).text.style = { typeface: FONT_HEAD, fontSize: 15, bold: true, color: C.white, verticalAlignment: "middle" };
  table.getCell(3, 1).text.style = { typeface: FONT_HEAD, fontSize: 17, bold: true, color: C.white, verticalAlignment: "middle" };
  box(slide, 810, 474, 398, 118, C.redSoft, "none", true);
  textBox(slide, "Current product logic keeps the delivery fee outside the item commission base.", 836, 496, 348, 54, { font: FONT_HEAD, size: 17, bold: true, color: C.redDark, align: "center", valign: "middle" });
  textBox(slide, "Confirm the final merchant agreement before using this example as a contractual statement.", 87, 557, 640, 48, { size: 15, color: C.muted });
  note(slide, "Sources: resources/views/pages/home.blade.php; app/Model/Cart.php; app/Http/Controllers/Api/Merchant/ItemController.php. Arithmetic: ₱1,000 × 15% = ₱150; ₱1,000 − ₱150 = ₱850.");
}

// 6. Fees and adjustments table
{
  const slide = slideBase();
  header(slide, "Treatment of fees, discounts, and other charges", "The current implementation answers some questions. Commercial policy must confirm the rest.", 6);
  const table = slide.tables.add({
    rows: 7, columns: 3, left: 60, top: 160, width: 1160, height: 430,
    columnWidths: [260, 620, 280],
    values: tableMatrix([
      ["Order component", "Current product logic", "Status for partner discussion"],
      ["Item prices and paid add-ons", "Included in the stored item commission amount.", "Confirmed in current code"],
      ["Delivery fee", "Calculated and displayed separately from item commission.", "Confirmed in current code"],
      ["Convenience fee", "Added as a separate customer charge and excluded from the stored item commission field.", "Confirmed in current code"],
      ["VAT on convenience fee", "Calculated separately from item commission when configured.", "Confirmed in current code"],
      ["Discounts and vouchers", "Reduce the customer total. The merchant or platform funding rule is not defined as a commercial policy in the materials reviewed.", "Needs confirmation"],
      ["Refunds, cancellations, and other adjustments", "The final settlement and any commission reversal or clawback rule require a written policy.", "Needs confirmation"],
    ]),
  });
  styleTable(table, C.charcoal);
  for (const row of [5, 6]) {
    table.getCell(row, 2).fill = C.amberSoft;
    table.getCell(row, 2).text.style = { typeface: FONT_HEAD, fontSize: 14, bold: true, color: "#8E621C", verticalAlignment: "middle", autoFit: "shrinkText" };
  }
  for (const row of [1, 2, 3, 4]) {
    table.getCell(row, 2).fill = C.greenSoft;
    table.getCell(row, 2).text.style = { typeface: FONT_HEAD, fontSize: 14, bold: true, color: "#257B50", verticalAlignment: "middle", autoFit: "shrinkText" };
  }
  textBox(slide, "Use the signed merchant agreement as the final authority if it differs from current system behavior.", 68, 620, 1060, 28, { size: 14, bold: true, color: C.redDark });
  note(slide, "Sources: app/Model/Cart.php cartItemAmounts(); app/Http/Controllers/Api/Merchant/ItemController.php; resources/js/components/merchant/pages/report/OrderListComponent.vue. Items marked Needs confirmation are intentionally unresolved because the supplied materials do not define a final commercial policy.");
}

// 7. Dashboard overview
{
  const slide = slideBase(C.white);
  header(slide, "Merchant dashboard at a glance", "The home view combines store status, order health, sales activity, commission, and estimated net revenue", 7);
  addImage(slide, files.dashboard, "image/png", 55, 154, 790, 444, { alt: "Illustrative PahatudFood merchant dashboard", fit: "contain", rounded: true });
  textBox(slide, "Five questions answered", 895, 164, 300, 32, { font: FONT_HEAD, size: 20, bold: true });
  const qs = [
    ["01", "Is the store online?"],
    ["02", "What needs attention now?"],
    ["03", "How much did we sell?"],
    ["04", "What commission was recorded?"],
    ["05", "What is the estimated net revenue?"],
  ];
  qs.forEach((q, i) => {
    textBox(slide, q[0], 896, 221 + i * 67, 40, 25, { font: FONT_HEAD, size: 13, bold: true, color: C.red });
    textBox(slide, q[1], 946, 218 + i * 67, 250, 38, { size: 17, bold: true, valign: "middle" });
    if (i < qs.length - 1) box(slide, 895, 266 + i * 67, 292, 1, C.line, C.line);
  });
  textBox(slide, "Illustrative data. Layout follows the current merchant dashboard.", 62, 620, 760, 22, { size: 11, color: C.muted });
  note(slide, "Sources: resources/js/components/merchant/pages/orders/SummaryComponent.vue; app/Http/Controllers/Api/Merchant/OrderController.php. Screenshot is a presentation preview based on the current UI code and uses sample data only.");
}

// 8. Orders
{
  const slide = slideBase();
  header(slide, "Manage orders from one queue", "A clear status workflow helps the kitchen, customer, and rider stay aligned", 8);
  step(slide, 1, "Review", "Check items, add-ons, instructions, payment, and service mode.", 60, 165, 390);
  step(slide, 2, "Accept and prepare", "Confirm the order promptly and start kitchen preparation.", 60, 269, 390);
  step(slide, 3, "Mark ready", "Signal that a table, pickup customer, or rider can receive the order.", 60, 373, 390);
  step(slide, 4, "Complete or cancel", "Close the order accurately and preserve the reason when it cannot be fulfilled.", 60, 477, 390);
  addImage(slide, files.orders, "image/png", 477, 165, 748, 421, { alt: "Illustrative merchant order management screen", fit: "contain", rounded: true });
  box(slide, 477, 607, 748, 42, C.redSoft, "none", true);
  textBox(slide, "Illustrative data. Actual status labels and actions depend on the enabled service mode.", 500, 619, 702, 19, { size: 12, color: C.redDark, align: "center" });
  note(slide, "Sources: resources/js/components/merchant/pages/report/OrderListComponent.vue; routes/merchant.php; resources/views/merchant/partials/documentation.blade.php. Screenshot uses sample data.");
}

// 9. Menu
{
  const slide = slideBase(C.white);
  header(slide, "Update menu items and prices", "Menu accuracy reduces order issues and keeps commission calculations aligned with current prices", 9);
  addImage(slide, files.menu, "image/png", 55, 160, 755, 425, { alt: "Illustrative merchant menu management screen", fit: "contain", rounded: true });
  textBox(slide, "What merchants can maintain", 857, 167, 335, 32, { font: FONT_HEAD, size: 20, bold: true });
  bulletList(slide, [
    "Product name and description",
    "Category and branch availability",
    "Regular and promotional prices",
    "Add-ons and item variations",
    "Available or sold-out status",
  ], 860, 224, 335, 255, { size: 17, spaceAfter: 14 });
  box(slide, 850, 505, 355, 100, C.charcoal, C.charcoal, true);
  textBox(slide, "New item prices store a commission amount using the merchant’s assigned percentage.", 878, 526, 300, 58, { size: 16, bold: true, color: C.white, align: "center", valign: "middle" });
  textBox(slide, "Illustrative data based on current product fields.", 60, 610, 730, 20, { size: 11, color: C.muted });
  note(slide, "Sources: app/Http/Controllers/Api/Merchant/ItemController.php; resources/js/components/merchant/pages/products/ViewComponent.vue; app/Products.php. Screenshot uses sample menu data.");
}

// 10. Sales
{
  const slide = slideBase();
  header(slide, "Sales and earnings", "Merchant reports separate completed sales, platform commission, and estimated net revenue", 10);
  textBox(slide, "What to review", 62, 165, 300, 32, { font: FONT_HEAD, size: 20, bold: true });
  bulletList(slide, [
    "Sales today, this week, and this month",
    "Completed, pending, and cancelled orders",
    "Gross completed revenue",
    "Recorded platform commission",
    "Estimated net revenue and date-based reports",
  ], 65, 220, 360, 272, { size: 17, spaceAfter: 15 });
  box(slide, 62, 516, 367, 100, C.redSoft, "none", true);
  textBox(slide, "Current dashboard formula", 85, 535, 320, 22, { font: FONT_HEAD, size: 14, bold: true, color: C.redDark, align: "center" });
  textBox(slide, "Order total − recorded commission\n= estimated net revenue", 85, 566, 320, 42, { size: 17, bold: true, color: C.redDark, align: "center" });
  addImage(slide, files.sales, "image/png", 476, 165, 748, 421, { alt: "Illustrative merchant sales report", fit: "contain", rounded: true });
  textBox(slide, "Final payout may differ if the confirmed policy applies discounts, refunds, tax, or other settlement adjustments.", 487, 607, 716, 38, { size: 13, bold: true, color: C.redDark, align: "center" });
  note(slide, "Sources: resources/js/components/merchant/pages/orders/SummaryComponent.vue; app/Http/Controllers/Api/Merchant/OrderController.php; resources/views/merchant/pages/reports/today.blade.php. The screenshot is reused here because the same merchant dashboard is the source for the sales and earnings view; data are illustrative.");
}

// 11. Restaurant info
{
  const slide = slideBase(C.white);
  header(slide, "Restaurant information and branches", "Keeping customer-facing and payout information current supports smoother fulfilment", 11);
  textBox(slide, "Merchant controls", 61, 167, 325, 32, { font: FONT_HEAD, size: 20, bold: true });
  bulletList(slide, [
    "Restaurant name and description",
    "Customer contact information",
    "Branch addresses and availability",
    "Payout account name and details",
    "Activated service modes and store status",
  ], 64, 222, 365, 274, { size: 17, spaceAfter: 15 });
  box(slide, 62, 520, 365, 92, C.greenSoft, "none", true);
  textBox(slide, "Profile changes should match the restaurant’s current operating details before the store goes online.", 86, 540, 317, 53, { size: 15, bold: true, color: "#257B50", align: "center", valign: "middle" });
  addImage(slide, files.profile, "image/png", 476, 165, 748, 421, { alt: "Illustrative restaurant profile screen", fit: "contain", rounded: true });
  textBox(slide, "Illustrative data based on current merchant settings fields.", 488, 607, 710, 22, { size: 11, color: C.muted, align: "center" });
  note(slide, "Sources: resources/views/merchant/partials/documentation.blade.php; resources/js/components/merchant/pages/profile/ViewComponent.vue; routes/web.php. Screenshot uses sample data.");
}

// 12. Service comparison
{
  const slide = slideBase();
  header(slide, "Dine-in, pickup, and delivery at a glance", "Each mode changes the handoff. The merchant still receives, prepares, and completes the order.", 12);
  const table = slide.tables.add({
    rows: 6, columns: 4, left: 55, top: 158, width: 1170, height: 430,
    columnWidths: [225, 315, 315, 315],
    values: tableMatrix([
      ["Journey point", "Dine-in / order to table", "Pickup", "Delivery"],
      ["Customer starts", "At the restaurant table", "Away from the restaurant", "Away from the restaurant"],
      ["Customer receives", "Prepared food at the table", "Prepared order at the counter", "Prepared order at the delivery address"],
      ["Merchant handoff", "Serve the identified table", "Release to the customer", "Release to the assigned rider"],
      ["Rider involved", "No", "No", "Yes"],
      ["Delivery fee", "Not expected", "Not expected", "Applies according to the confirmed delivery-fee policy"],
    ]),
  });
  styleTable(table, C.red);
  for (let r = 1; r < 6; r += 1) {
    table.getCell(r, 0).text.style = { typeface: FONT_HEAD, fontSize: 14, bold: true, color: C.ink, verticalAlignment: "middle", autoFit: "shrinkText" };
  }
  table.getCell(5, 3).fill = C.amberSoft;
  table.getCell(5, 3).text.style = { typeface: FONT_BODY, fontSize: 14, bold: true, color: "#8E621C", verticalAlignment: "middle", autoFit: "shrinkText" };
  textBox(slide, "Confirm service availability, operating hours, handoff verification, and delivery-fee rules before activation.", 70, 620, 1120, 28, { size: 14, bold: true, color: C.redDark, align: "center" });
  note(slide, "The three service definitions follow the user's supplied brief. Delivery fee treatment is intentionally subject to confirmation because no final commercial policy was supplied.");
}

// 13. Dine-in
{
  const slide = slideBase(C.white);
  header(slide, "Dine-in / Order to table", "The customer orders for a specific table. The merchant prepares and serves the meal in the restaurant.", 13);
  addImage(slide, files.dine, "image/png", 625, 152, 600, 430, { alt: "Customer ordering at a restaurant table while the kitchen prepares the meal", fit: "cover", rounded: true });
  const flow = [
    ["Customer starts", "Selects the restaurant, confirms the table, and submits the order."],
    ["Merchant receives", "Reviews the order and table reference in the dashboard."],
    ["Kitchen prepares", "Prepares the items and updates the order status."],
    ["Merchant serves", "Delivers the prepared order to the correct table."],
    ["Order closes", "Marks the order complete after service."],
  ];
  flow.forEach((f, i) => step(slide, i + 1, f[0], f[1], 60, 155 + i * 88, 520, { color: i === 3 ? C.red : C.charcoal }));
  box(slide, 625, 604, 600, 50, C.amberSoft, "none", true);
  textBox(slide, "Confirm how the customer identifies the table: QR code, table number, or staff-provided link.", 648, 617, 554, 24, { size: 13, bold: true, color: "#8E621C", align: "center" });
  note(slide, "Journey definition supplied by the user. Illustration generated for this presentation with OpenAI image generation. Table-identification method requires product confirmation.");
}

// 14. Pickup
{
  const slide = slideBase();
  header(slide, "Pickup", "The customer orders ahead. The merchant prepares the food and releases it at the restaurant.", 14);
  addImage(slide, files.pickup, "image/png", 55, 154, 600, 430, { alt: "Customer receiving a pickup order from a restaurant merchant", fit: "cover", rounded: true });
  const flow = [
    ["Customer orders ahead", "Chooses pickup and submits the order before arriving."],
    ["Merchant confirms", "Reviews the order and expected pickup timing."],
    ["Kitchen prepares", "Prepares and packs the order for collection."],
    ["Merchant marks ready", "Lets the customer know the order can be collected."],
    ["Customer collects", "Receives the order at the agreed pickup point."],
  ];
  flow.forEach((f, i) => step(slide, i + 1, f[0], f[1], 700, 155 + i * 88, 520, { color: i === 3 ? C.red : C.charcoal }));
  box(slide, 55, 604, 600, 50, C.amberSoft, "none", true);
  textBox(slide, "Confirm pickup windows and the order-verification method used at handoff.", 78, 617, 554, 24, { size: 13, bold: true, color: "#8E621C", align: "center" });
  note(slide, "Journey definition supplied by the user. Illustration generated for this presentation with OpenAI image generation. Pickup timing and handoff verification require confirmation.");
}

// 15. Delivery
{
  const slide = slideBase(C.white);
  header(slide, "Delivery", "The merchant prepares the order. A Pahatud rider collects it and completes the last-mile delivery.", 15);
  addImage(slide, files.delivery, "image/png", 625, 152, 600, 430, { alt: "Restaurant merchant handing a prepared order to a delivery rider", fit: "cover", rounded: true });
  const flow = [
    ["Customer submits", "Chooses delivery, confirms the address, and places the order."],
    ["Merchant accepts", "Reviews the items and starts preparation."],
    ["Merchant marks ready", "Seals the order and signals that it is ready for pickup."],
    ["Rider collects", "Receives the order from the merchant and verifies the handoff."],
    ["Rider delivers", "Transports the order to the customer’s address."],
  ];
  flow.forEach((f, i) => step(slide, i + 1, f[0], f[1], 60, 155 + i * 88, 520, { color: i === 3 ? C.red : C.charcoal }));
  box(slide, 625, 604, 600, 50, C.amberSoft, "none", true);
  textBox(slide, "Confirm delivery-fee calculation, rider assignment, proof of delivery, and exception handling.", 648, 617, 554, 24, { size: 13, bold: true, color: "#8E621C", align: "center" });
  note(slide, "Journey definition supplied by the user. Illustration generated for this presentation with OpenAI image generation. Delivery-fee and exception policies require confirmation.");
}

// 16. Launch checklist
{
  const slide = slideBase(C.red);
  header(slide, "Before launch", "Resolve the commercial details, complete the dashboard setup, and test each activated service mode", 16, { inverse: true });
  box(slide, 60, 166, 555, 410, C.white, C.white, true);
  box(slide, 665, 166, 555, 410, C.cream, C.cream, true);
  textBox(slide, "Merchant readiness", 91, 197, 470, 34, { font: FONT_HEAD, size: 23, bold: true });
  bulletList(slide, [
    "Business profile and branch details are current",
    "Menu, prices, add-ons, and availability are complete",
    "Operating hours and service modes are set",
    "Contact and payout information are verified",
    "Team completes a test order for each active service mode",
  ], 92, 255, 470, 252, { size: 17, spaceAfter: 14 });
  textBox(slide, "Pahatud confirmations", 697, 197, 470, 34, { font: FONT_HEAD, size: 23, bold: true });
  bulletList(slide, [
    "Final commission rate and eligible subtotal definition",
    "Discount and voucher funding rules",
    "Delivery-fee and other customer-charge treatment",
    "Payout schedule, refunds, and settlement adjustments",
    "Support contacts and escalation process",
  ], 698, 255, 470, 252, { size: 17, spaceAfter: 14 });
  textBox(slide, "Merchant support", 60, 608, 160, 23, { size: 13, bold: true, color: "#FFE6E3" });
  textBox(slide, "info@pahatud.com   +63 916 298 6547", 218, 607, 520, 25, { size: 16, bold: true, color: C.white });
  textBox(slide, "Agree the policy. Activate the dashboard. Run the test orders.", 718, 607, 503, 28, { font: FONT_HEAD, size: 17, bold: true, color: C.white, align: "right" });
  note(slide, "Sources: resources/views/merchant/partials/documentation.blade.php for support contact and merchant tasks. Commercial-policy items remain intentionally flagged for confirmation.");
}

const candidatePath = path.join(stagingDir, "candidate.pptx");
await (await PresentationFile.exportPptx(presentation)).save(candidatePath);

const requirements = {
  explicitTotalSlideCount: 16,
  requiredNativeTableOwnerSlides: [5, 6, 12],
  requiredNativeChartOwnerSlides: [],
  tableArithmeticContracts: [{
    slide: 5,
    table: 1,
    label_column: 0,
    total_row: 3,
    value_columns: [1],
    component_rows: [1, 2],
  }],
};
const fontPolicy = {
  basis: "reference",
  families: [FONT_HEAD, FONT_BODY],
  referencePath: "/Users/larryparba/Downloads/Pahatud_Agent_Orientation_2026-09-29_v3 (1).pptx",
  referenceSha256: "8172fdb1a2fcdd6932eda3a1676ef2ba9ed56eb37a42037a9d0d22d316f9e237",
};

const result = await finalizePresentation({
  ...requirements,
  workspaceDir,
  candidatePath,
  finalPath: FINAL_PPTX,
  pythonExecutable: RUNTIME_PYTHON,
  integrityValidatorPath: path.join(SKILL_DIR, "container_tools/inspect_presentation_package_integrity.py"),
  layoutValidatorPath: path.join(SKILL_DIR, "container_tools/inspect_presentation_layout_geometry.py"),
  layoutArgs: [
    "--expected-slide-size-emu", "12192000,6858000",
    "--validate-bullet-geometry",
    "--validate-heading-fit",
    "--require-native-table-slide", "5",
    "--require-native-table-slide", "6",
    "--require-native-table-slide", "12",
  ],
  requiredNativeTableOwnerSlides: [5, 6, 12],
  fontPolicy,
  verifyArtifactToolImport: true,
  receiptPath: path.join(stagingDir, "PahatudFood_Merchant_Partner_Orientation_v2.validation.json"),
});

console.log(JSON.stringify({ final: FINAL_PPTX, result }, null, 2));
