import fs from "node:fs/promises";
import path from "node:path";
import { pathToFileURL } from "node:url";
import { Presentation, PresentationFile } from "@oai/artifact-tool";

const workspaceDir = "/Users/larryparba/web/pah";
const buildDir = path.join(workspaceDir, ".codex-presentations/pahatud-merchant-build");
const outputDir = path.join(workspaceDir, "outputs");
const finalPath = path.join(outputDir, "Pahatud_Merchant_Overview_v2.pptx");
const SKILL_DIR = "/Users/larryparba/.codex/plugins/cache/openai-primary-runtime/presentations/26.909.12148/skills/presentations";
const RUNTIME_PYTHON = "/Users/larryparba/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/bin/python3";

const { finalizePresentation } = await import(pathToFileURL(
  path.join(SKILL_DIR, "container_tools/artifact_tool_utils.mjs"),
).href);

await fs.mkdir(buildDir, { recursive: true });
await fs.mkdir(outputDir, { recursive: true });

const W = 1280;
const H = 720;
const FONT = "Avenir Next";
const C = {
  red: "#E63835",
  redDark: "#C92725",
  ink: "#242120",
  muted: "#756F6B",
  pale: "#FFF6F2",
  blush: "#FCE6E2",
  peach: "#F7D9C8",
  cream: "#FFFDF9",
  sand: "#E9D9CC",
  green: "#22845A",
  white: "#FFFFFF",
  charcoal: "#171514",
};

const assets = {
  cover: "public/images/pahatud-logo-with-motor.jpg",
  partner: "public/images/partner-with-us.jpg",
  appHome: "public/images/app-preview/home.png",
  appRestaurants: "public/images/app-preview/restaurants.png",
  appTracking: "public/images/app-preview/order-tracking.png",
  announcement: "public/images/announcements/pahatud-new-website.png",
  promo: "public/images/highlights/ads1.png",
  food: "public/images/banner-left.png",
  rider: "public/images/step2.png",
  payout: "public/images/step3.png",
  merchant: "public/images/step4.png",
};

const bytes = {};
for (const [key, rel] of Object.entries(assets)) {
  bytes[key] = await fs.readFile(path.join(workspaceDir, rel));
}

function mime(rel) {
  return rel.toLowerCase().endsWith(".jpg") || rel.toLowerCase().endsWith(".jpeg")
    ? "image/jpeg"
    : "image/png";
}

function addRect(slide, x, y, w, h, fill, opts = {}) {
  return slide.shapes.add({
    geometry: opts.geometry ?? "rect",
    name: opts.name,
    position: { left: x, top: y, width: w, height: h },
    fill,
    line: { style: "solid", fill: opts.lineFill ?? "none", width: opts.lineWidth ?? 0 },
    borderRadius: opts.radius,
    shadow: opts.shadow,
  });
}

function addLine(slide, x, y, w, h = 0, color = C.sand, width = 2) {
  return slide.shapes.add({
    geometry: "line",
    position: { left: x, top: y, width: w, height: h },
    fill: "none",
    line: { style: "solid", fill: color, width },
  });
}

function addText(slide, text, x, y, w, h, opts = {}) {
  const shape = slide.shapes.add({
    geometry: "textbox",
    name: opts.name,
    position: { left: x, top: y, width: w, height: h },
    fill: opts.fill ?? "none",
    line: { style: "solid", fill: opts.lineFill ?? "none", width: opts.lineWidth ?? 0 },
    borderRadius: opts.radius,
  });
  shape.text = text;
  shape.text.style = {
    typeface: FONT,
    fontSize: opts.size ?? 24,
    bold: opts.bold ?? false,
    color: opts.color ?? C.ink,
    alignment: opts.align ?? "left",
    verticalAlignment: opts.valign ?? "top",
    autoFit: "none",
    wrap: "square",
    lineSpacing: opts.lineSpacing ?? 1.05,
    insets: opts.insets ?? { top: 0, right: 0, bottom: 0, left: 0 },
  };
  return shape;
}

function addImage(slide, key, x, y, w, h, opts = {}) {
  const rel = assets[key];
  return slide.images.add({
    blob: bytes[key],
    contentType: mime(rel),
    alt: opts.alt ?? key,
    fit: opts.fit ?? "cover",
    position: { left: x, top: y, width: w, height: h },
    crop: opts.crop,
    geometry: opts.geometry,
    borderRadius: opts.radius,
  });
}

function addSectionTitle(slide, section, title, subtitle) {
  addText(slide, section.toUpperCase(), 72, 42, 300, 24, { size: 14, bold: true, color: C.red });
  addText(slide, title, 72, 75, 1136, 62, { size: 38, bold: true, color: C.ink, lineSpacing: 0.95 });
  if (subtitle) addText(slide, subtitle, 72, 139, 1020, 46, { size: 20, color: C.muted });
  addLine(slide, 72, 192, 1136, 0, C.sand, 1);
}

function addFooter(slide, n, dark = false) {
  const color = dark ? "#FBD6D2" : "#A49A94";
  addText(slide, "PAHATUD MERCHANT", 72, 680, 260, 18, { size: 11, bold: true, color });
  addText(slide, String(n).padStart(2, "0"), 1148, 678, 60, 20, { size: 12, bold: true, color, align: "right" });
}

function addNote(slide, sources, talkTrack) {
  slide.speakerNotes.textFrame.setText(`Sources: ${sources.join("; ")}\n\nPresenter note: ${talkTrack}`);
  slide.speakerNotes.setVisible(true);
}

function addNumberCircle(slide, n, x, y, fill = C.red) {
  addRect(slide, x, y, 40, 40, fill, { geometry: "ellipse" });
  addText(slide, String(n), x, y + 1, 40, 39, { size: 18, bold: true, color: C.white, align: "center", valign: "middle" });
}

const presentation = Presentation.create({ slideSize: { width: W, height: H } });

// 1. Cover
{
  const slide = presentation.slides.add();
  slide.background.fill = C.cream;
  addRect(slide, 0, 0, 34, H, C.red);
  addText(slide, "Pahatud Merchant", 76, 132, 520, 110, { size: 54, bold: true, color: C.ink, lineSpacing: 0.92 });
  addText(slide, "The merchant workspace behind the Pahatud customer experience", 78, 258, 470, 88, { size: 25, color: C.muted, lineSpacing: 1.12 });
  addLine(slide, 78, 377, 98, 0, C.red, 5);
  addText(slide, "Store setup  ·  catalog  ·  orders  ·  promotions  ·  sales", 78, 404, 500, 58, { size: 17, bold: true, color: C.redDark });
  addRect(slide, 652, 86, 556, 548, C.white, { radius: "rounded-3xl", shadow: "shadow-md" });
  addImage(slide, "cover", 671, 106, 518, 508, { fit: "contain", geometry: "roundRect", radius: "rounded-3xl", alt: "Pahatud delivery services logo over a map" });
  addText(slide, "Merchant overview", 78, 638, 330, 25, { size: 15, bold: true, color: C.muted });
  addNote(slide, ["public/images/pahatud-logo-with-motor.jpg", "routes/web.php"], "Introduce Pahatud Merchant as the operational workspace that keeps the customer-facing app accurate and responsive.");
}

// 2. Connected experience
{
  const slide = presentation.slides.add();
  slide.background.fill = C.cream;
  addSectionTitle(slide, "Product overview", "One connected service for customers and merchants", "Customers discover and order in Pahatud. Merchants manage the storefront and fulfillment behind it.");
  addText(slide, "CUSTOMER APP", 86, 226, 260, 26, { size: 14, bold: true, color: C.red });
  addRect(slide, 95, 260, 280, 374, C.white, { radius: "rounded-3xl", shadow: "shadow-sm" });
  addImage(slide, "appHome", 109, 271, 252, 352, { fit: "cover", crop: { left: 0, top: 0, right: 0, bottom: 0.43 }, geometry: "roundRect", radius: "rounded-2xl", alt: "Pahatud customer app home screen" });
  addLine(slide, 432, 446, 86, 0, C.red, 4);
  addRect(slide, 503, 430, 32, 32, C.red, { geometry: "ellipse" });
  addText(slide, "+", 503, 428, 32, 32, { size: 21, bold: true, color: C.white, align: "center", valign: "middle" });
  addText(slide, "MERCHANT WORKSPACE", 584, 226, 420, 26, { size: 14, bold: true, color: C.red });
  const items = [
    ["Storefront", "Profile, branches, categories, and online status"],
    ["Catalog", "Products, pricing, availability, variants, and add-ons"],
    ["Operations", "Incoming orders, preparation, rider pickup, and proof"],
    ["Growth", "Coupons, promotional banners, and app visibility"],
    ["Reporting", "Completed sales, commission, and merchant net"],
  ];
  items.forEach(([title, body], i) => {
    const y = 270 + i * 72;
    addRect(slide, 584, y + 3, 12, 12, i === 4 ? C.green : C.red, { geometry: "ellipse" });
    addText(slide, title, 615, y - 5, 170, 30, { size: 21, bold: true });
    addText(slide, body, 790, y - 3, 405, 44, { size: 17, color: C.muted });
    if (i < items.length - 1) addLine(slide, 615, y + 52, 580, 0, "#E8DDD6", 1);
  });
  addFooter(slide, 2);
  addNote(slide, ["resources/views/merchant/includes/menu.blade.php", "resources/views/merchant/pages/dashboard.blade.php", "public/images/app-preview/home.png"], "Explain that the customer app and merchant portal are two sides of the same transaction, with merchant updates flowing into the customer experience.");
}

// 3. Onboarding and verification
{
  const slide = presentation.slides.add();
  slide.background.fill = C.pale;
  addSectionTitle(slide, "Merchant onboarding", "From application to an order-ready store", "The merchant journey includes business details, required documents, review, and operational setup.");
  addImage(slide, "partner", 72, 228, 415, 390, { fit: "cover", crop: { left: 0, top: 0.13, right: 0, bottom: 0 }, geometry: "roundRect", radius: "rounded-3xl", alt: "Pahatud partner illustration" });
  addLine(slide, 553, 267, 0, 302, C.peach, 4);
  const steps = [
    ["Create the merchant account", "Provide the restaurant and administrator information."],
    ["Complete the application", "Confirm business, contact, address, and payout details."],
    ["Upload required documents", "Government ID and business registration are standard requirements."],
    ["Resolve review findings", "Replace rejected or expired files using administrator remarks."],
    ["Prepare the store", "Check menu, categories, branches, pricing, and availability before going online."],
  ];
  steps.forEach(([title, body], i) => {
    const y = 238 + i * 76;
    addNumberCircle(slide, i + 1, 533, y);
    addText(slide, title, 596, y - 1, 540, 29, { size: 20, bold: true });
    addText(slide, body, 596, y + 29, 590, 42, { size: 16, color: C.muted });
  });
  addFooter(slide, 3);
  addNote(slide, ["resources/views/merchant/pages/help.blade.php", "resources/views/merchant/pages/application.blade.php", "routes/web.php"], "Clarify that approval documents apply to the application flow and that merchants should not open the store until operational information is complete.");
}

// 4. Storefront control
{
  const slide = presentation.slides.add();
  slide.background.fill = C.cream;
  addSectionTitle(slide, "Storefront management", "Merchant updates shape what customers see before ordering", "The portal controls essential discovery and availability information shown through Pahatud.");
  const rows = [
    ["Profile", "Restaurant name and customer-facing contact information"],
    ["Branches", "Address, telephone, mobile, and service location details"],
    ["Categories", "Menu organization that helps customers browse"],
    ["Online status", "Whether the store is currently accepting orders"],
  ];
  rows.forEach(([title, body], i) => {
    const y = 242 + i * 91;
    addText(slide, `0${i + 1}`, 80, y, 48, 34, { size: 16, bold: true, color: C.red });
    addText(slide, title, 145, y - 7, 218, 35, { size: 25, bold: true });
    addText(slide, body, 145, y + 30, 390, 43, { size: 17, color: C.muted });
    if (i < rows.length - 1) addLine(slide, 145, y + 75, 380, 0, C.sand, 1);
  });
  addRect(slide, 644, 220, 488, 420, C.white, { radius: "rounded-3xl", shadow: "shadow-sm" });
  addImage(slide, "appRestaurants", 708, 238, 360, 384, { fit: "cover", crop: { left: 0, top: 0, right: 0, bottom: 0.24 }, geometry: "roundRect", radius: "rounded-2xl", alt: "Pahatud restaurant discovery screen" });
  addText(slide, "Visible to customers", 977, 594, 190, 26, { size: 13, bold: true, color: C.red, align: "right" });
  addFooter(slide, 4);
  addNote(slide, ["resources/views/merchant/includes/menu.blade.php", "resources/views/merchant/pages/help.blade.php", "public/images/app-preview/restaurants.png"], "Use the customer screen to show why profile, branch, category, and status accuracy matters.");
}

// 5. Catalog management
{
  const slide = presentation.slides.add();
  slide.background.fill = C.charcoal;
  addText(slide, "CATALOG MANAGEMENT", 72, 45, 310, 24, { size: 14, bold: true, color: "#FF7772" });
  addText(slide, "A menu customers can order with confidence", 72, 80, 665, 92, { size: 41, bold: true, color: C.white, lineSpacing: 0.94 });
  addText(slide, "Clear product information reduces avoidable substitutions and cancellations.", 74, 173, 620, 48, { size: 20, color: "#D7CFCA" });
  addImage(slide, "food", 778, 38, 446, 640, { fit: "contain", alt: "Food products including pizza and burgers" });
  const points = [
    ["Product basics", "Name, description, price, image, and category"],
    ["Availability", "Publish active items and hide sold-out products quickly"],
    ["Customer choices", "Use variants and add-ons for size, flavor, extras, or preparation"],
    ["Catalog hygiene", "Keep labels short and show every additional charge clearly"],
  ];
  points.forEach(([title, body], i) => {
    const y = 272 + i * 88;
    addRect(slide, 74, y + 2, 28, 28, i === 1 ? C.green : C.red, { geometry: "ellipse" });
    addText(slide, "✓", 74, y - 1, 28, 28, { size: 16, bold: true, color: C.white, align: "center", valign: "middle" });
    addText(slide, title, 122, y - 4, 250, 30, { size: 22, bold: true, color: C.white });
    addText(slide, body, 122, y + 29, 555, 42, { size: 17, color: "#C7BFBA" });
  });
  addFooter(slide, 5, true);
  addNote(slide, ["resources/js/components/merchant/pages/products/ViewComponent.vue", "resources/views/merchant/pages/help.blade.php", "public/images/banner-left.png"], "Emphasize real-time availability and transparent add-on pricing as practical controls for order accuracy.");
}

// 6. Order and delivery operations
{
  const slide = presentation.slides.add();
  slide.background.fill = C.cream;
  addSectionTitle(slide, "Order operations", "Each order stays visible from acceptance to rider pickup", "The merchant workspace refreshes order activity and exposes the details needed to prepare the right package.");
  addRect(slide, 846, 224, 300, 416, C.white, { radius: "rounded-3xl", shadow: "shadow-sm" });
  addImage(slide, "appTracking", 870, 237, 252, 390, { fit: "cover", crop: { left: 0, top: 0.05, right: 0, bottom: 0.40 }, geometry: "roundRect", radius: "rounded-2xl", alt: "Pahatud customer order tracking screen" });
  addImage(slide, "rider", 704, 490, 142, 142, { fit: "contain", alt: "Pahatud delivery rider illustration" });
  addLine(slide, 109, 280, 0, 270, C.peach, 4);
  const steps = [
    ["Review", "Check every item, quantity, option, customer note, and delivery time."],
    ["Confirm", "Respond promptly and keep the order visible during preparation."],
    ["Prepare", "Complete, pack, and check the full order before changing its status."],
    ["Release", "Mark ready when the package can be collected, then coordinate rider pickup."],
  ];
  steps.forEach(([title, body], i) => {
    const y = 241 + i * 94;
    addNumberCircle(slide, i + 1, 89, y, i === 3 ? C.green : C.red);
    addText(slide, title, 154, y - 4, 160, 31, { size: 23, bold: true });
    addText(slide, body, 154, y + 31, 525, 48, { size: 17, color: C.muted });
  });
  addText(slide, "Order detail includes totals, discounts, customer information, rider status, and delivery proof when available.", 154, 613, 570, 46, { size: 15, bold: true, color: C.redDark });
  addFooter(slide, 6);
  addNote(slide, ["resources/views/merchant/pages/help.blade.php", "resources/js/components/merchant/pages/orders/ViewComponent.vue", "routes/web.php", "public/images/app-preview/order-tracking.png"], "Connect merchant preparation steps with the customer's order-tracking experience. Avoid promising a specific rider assignment time.");
}

// 7. Promotions and coupons
{
  const slide = presentation.slides.add();
  slide.background.fill = C.pale;
  addSectionTitle(slide, "Merchant marketing", "Two tools support store-specific offers", "Coupons change the checkout price. Promotional banners increase visibility after administrator approval.");
  addImage(slide, "promo", 72, 231, 515, 291, { fit: "cover", geometry: "roundRect", radius: "rounded-3xl", alt: "Pahatud merchant partner discount banner" });
  addText(slide, "COUPONS", 648, 235, 190, 24, { size: 15, bold: true, color: C.red });
  addText(slide, "Flexible checkout discounts", 648, 268, 470, 38, { size: 27, bold: true });
  addText(slide, "Choose a fixed peso value or percentage. Add a minimum order, usage limit, validity window, and active switch.", 648, 318, 480, 78, { size: 19, color: C.muted, lineSpacing: 1.12 });
  addLine(slide, 648, 420, 494, 0, C.sand, 1);
  addText(slide, "PROMOTIONAL BANNERS", 648, 451, 250, 24, { size: 15, bold: true, color: C.red });
  addText(slide, "Scheduled app visibility", 648, 484, 450, 38, { size: 27, bold: true });
  addText(slide, "Upload a 1600 × 800 banner, set timing and display order, then submit it for review. Approved offers can move through scheduled, live, and expired states.", 648, 534, 510, 98, { size: 19, color: C.muted, lineSpacing: 1.12 });
  addText(slide, "Administrator approval protects what appears in the customer app.", 72, 554, 515, 52, { size: 18, bold: true, color: C.redDark, align: "center" });
  addFooter(slide, 7);
  addNote(slide, ["resources/views/merchant/pages/promotions/index.blade.php", "resources/views/merchant/pages/promotions/form.blade.php", "resources/views/merchant/pages/coupons/form.blade.php", "public/images/highlights/ads1.png"], "Separate the two mechanics clearly: checkout coupons take effect through coupon settings, while promotional banners require administrator approval before app publication.");
}

// 8. Sales reporting
{
  const slide = presentation.slides.add();
  slide.background.fill = C.cream;
  addSectionTitle(slide, "Sales reporting", "Completed orders explain gross sales and merchant net", "Merchants can run a date-range report and inspect the components behind each completed order.");
  addText(slide, "GROSS COMPLETED SALES", 72, 253, 290, 24, { size: 15, bold: true, color: C.red });
  addText(slide, "Order value", 72, 288, 270, 48, { size: 34, bold: true });
  addText(slide, "Includes completed order totals for the selected reporting period.", 72, 343, 290, 66, { size: 18, color: C.muted });
  addText(slide, "−", 382, 300, 50, 50, { size: 39, bold: true, color: C.red, align: "center" });
  addText(slide, "PLATFORM COMMISSION", 450, 253, 285, 24, { size: 15, bold: true, color: C.red });
  addText(slide, "Commission", 450, 288, 250, 48, { size: 34, bold: true });
  addText(slide, "Reported separately so the merchant can see the platform share.", 450, 343, 290, 66, { size: 18, color: C.muted });
  addText(slide, "=", 758, 300, 50, 50, { size: 35, bold: true, color: C.green, align: "center" });
  addRect(slide, 830, 231, 365, 210, C.blush, { radius: "rounded-3xl" });
  addText(slide, "ESTIMATED MERCHANT NET", 862, 258, 302, 24, { size: 15, bold: true, color: C.redDark });
  addText(slide, "Net earnings", 862, 296, 300, 52, { size: 38, bold: true, color: C.ink });
  addText(slide, "The amount assigned to the restaurant after commission and applicable entries.", 862, 354, 290, 70, { size: 17, color: C.muted });
  addLine(slide, 72, 486, 1123, 0, C.sand, 1);
  addImage(slide, "payout", 72, 519, 120, 120, { fit: "contain", alt: "Merchant payout illustration" });
  addText(slide, "The order-level report also shows items, subtotal, delivery fee, discount, rider, completion time, and status.", 222, 530, 950, 52, { size: 22, bold: true });
  addText(slide, "Only completed orders contribute to the displayed sales totals.", 222, 592, 760, 32, { size: 17, color: C.redDark });
  addFooter(slide, 8);
  addNote(slide, ["resources/js/components/merchant/pages/report/OrderReportTodayComponent.vue", "resources/views/merchant/pages/reports/today.blade.php", "resources/views/merchant/pages/help.blade.php"], "Do not insert sample revenue figures. Explain the relationship between gross, commission, and estimated merchant net using the labels from the implementation.");
}

// 9. Operating rhythm
{
  const slide = presentation.slides.add();
  slide.background.fill = C.pale;
  addSectionTitle(slide, "Merchant routine", "A simple operating rhythm keeps the store reliable", "Most merchant work falls into four moments across the service day.");
  const columns = [
    ["01", "Before opening", "Review profile and branch details. Confirm menu prices and stock. Switch the store online only when staff can monitor orders."],
    ["02", "During service", "Keep sold-out items unavailable. Watch incoming orders and respond quickly when a new request appears."],
    ["03", "For every order", "Check options and notes. Prepare the complete package, mark it ready, and release it to the correct rider."],
    ["04", "After service", "Review completed sales, commission, and net. Update tomorrow's availability and switch the store offline when needed."],
  ];
  columns.forEach(([n, title, body], i) => {
    const x = 72 + i * 286;
    addText(slide, n, x, 242, 60, 38, { size: 18, bold: true, color: C.red });
    addLine(slide, x, 292, 238, 0, i === 3 ? C.green : C.red, 4);
    addText(slide, title, x, 317, 238, 66, { size: 25, bold: true, lineSpacing: 0.98 });
    addText(slide, body, x, 392, 238, 154, { size: 18, color: C.muted, lineSpacing: 1.13 });
  });
  addImage(slide, "merchant", 1018, 521, 148, 148, { fit: "contain", alt: "Merchant holding a burger illustration" });
  addRect(slide, 72, 586, 874, 64, C.white, { radius: "rounded-xl", lineFill: C.sand, lineWidth: 1 });
  addText(slide, "Help and documentation covers account setup, application status, products, orders, sales, payouts, and support.", 96, 603, 826, 36, { size: 17, bold: true, color: C.ink, valign: "middle" });
  addFooter(slide, 9);
  addNote(slide, ["resources/views/merchant/pages/help.blade.php", "resources/views/merchant/pages/dashboard.blade.php", "resources/js/components/merchant/includes/MerchantStoreOnlineComponent.vue"], "Frame these as practical habits based on the implemented controls, not as contractual service-level commitments.");
}

// 10. Merchant outcomes and support
{
  const slide = presentation.slides.add();
  slide.background.fill = C.red;
  addText(slide, "PAHATUD MERCHANT", 72, 50, 320, 25, { size: 14, bold: true, color: "#FFD7D4" });
  addText(slide, "Merchant outcomes", 72, 93, 585, 64, { size: 46, bold: true, color: C.white });
  addText(slide, "A clear operating workspace connected to the Pahatud customer experience", 72, 160, 620, 64, { size: 22, color: "#FFE8E6" });
  const outcomes = [
    "A customer-facing storefront in the Pahatud app",
    "Direct control of catalog and product availability",
    "Order visibility through preparation and rider pickup",
    "Sales reporting with gross, commission, and net amounts",
  ];
  outcomes.forEach((text, i) => {
    const y = 264 + i * 66;
    addRect(slide, 76, y + 2, 26, 26, C.white, { geometry: "ellipse" });
    addText(slide, "✓", 76, y, 26, 26, { size: 15, bold: true, color: C.red, align: "center", valign: "middle" });
    addText(slide, text, 121, y - 2, 615, 38, { size: 21, bold: true, color: C.white });
  });
  addLine(slide, 72, 555, 646, 0, "#F98A85", 1);
  addText(slide, "Merchant support", 72, 580, 210, 28, { size: 16, bold: true, color: "#FFD7D4" });
  addText(slide, "info@pahatud.com   ·   +63 916 298 6547   ·   (082) 224 3919", 72, 615, 670, 30, { size: 18, bold: true, color: C.white });
  addRect(slide, 790, 60, 430, 596, C.white, { radius: "rounded-3xl" });
  addImage(slide, "announcement", 790, 60, 430, 596, { fit: "contain", geometry: "roundRect", radius: "rounded-3xl", alt: "Pahatud website and customer app announcement" });
  addFooter(slide, 10, true);
  addNote(slide, ["resources/views/merchant/pages/help.blade.php", "resources/views/merchant/includes/menu.blade.php", "public/images/announcements/pahatud-new-website.png"], "Close with concrete merchant capabilities and the support channels implemented in the merchant help page.");
}

const candidatePath = path.join(buildDir, "candidate.pptx");
await (await PresentationFile.exportPptx(presentation)).save(candidatePath);

const montage = await presentation.export({ format: "webp", montage: true, scale: 0.55 });
await fs.writeFile(path.join(buildDir, "draft-montage.webp"), new Uint8Array(await montage.arrayBuffer()));

const result = await finalizePresentation({
  explicitTotalSlideCount: 10,
  requiredNativeTableOwnerSlides: [],
  requiredNativeChartOwnerSlides: [],
  workspaceDir,
  candidatePath,
  finalPath,
  pythonExecutable: RUNTIME_PYTHON,
  integrityValidatorPath: path.join(SKILL_DIR, "container_tools/inspect_presentation_package_integrity.py"),
  layoutValidatorPath: path.join(SKILL_DIR, "container_tools/inspect_presentation_layout_geometry.py"),
  layoutArgs: [
    "--expected-slide-size-emu", "12192000,6858000",
    "--validate-bullet-geometry",
    "--validate-heading-fit",
  ],
  requiredNativeTableOwnerSlides: [],
  fontPolicy: { basis: "design", families: [FONT] },
  verifyArtifactToolImport: true,
  receiptPath: path.join(buildDir, "Pahatud_Merchant_Overview_v2.validation.json"),
});

console.log(JSON.stringify({ finalPath, result }, null, 2));
