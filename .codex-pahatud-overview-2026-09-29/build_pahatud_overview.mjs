import fs from "node:fs/promises";
import path from "node:path";
import { pathToFileURL } from "node:url";
import { Presentation, PresentationFile } from "@oai/artifact-tool";

const workspaceDir = "/Users/larryparba/web/pah";
const SKILL_DIR = "/Users/larryparba/.codex/plugins/cache/openai-primary-runtime/presentations/26.909.12148/skills/presentations";
const TMP_DIR = path.join(workspaceDir, ".codex-pahatud-overview-2026-09-29");
const FINAL_PPTX = path.join(workspaceDir, "artifacts", "Pahatud_Customer_Investor_Overview_2026-09-29_v3.pptx");
const RUNTIME_PYTHON = "/Users/larryparba/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/bin/python3";

const { finalizePresentation } = await import(
  pathToFileURL(path.join(SKILL_DIR, "container_tools/artifact_tool_utils.mjs")).href,
);

const W = 1280;
const H = 720;
const HEAD = "Manrope";
const BODY = "DM Sans";

const C = {
  red: "#EF3B35",
  redDark: "#C92D2A",
  coral: "#FF7068",
  blush: "#FFF0ED",
  cream: "#FFF8F1",
  sand: "#F4E7D8",
  ink: "#202426",
  muted: "#62696D",
  line: "#DDD8D2",
  green: "#188761",
  amber: "#E59A2F",
  blue: "#2A70B8",
  white: "#FFFFFF",
};

const asset = (...parts) => path.join(workspaceDir, "public", ...parts);
const presentation = Presentation.create({ slideSize: { width: W, height: H } });

function shape(slide, geometry, position, fill = "none", opts = {}) {
  return slide.shapes.add({
    geometry,
    position,
    fill,
    line: opts.line ?? { style: "solid", fill: "none", width: 0 },
    ...(opts.borderRadius ? { borderRadius: opts.borderRadius } : {}),
    ...(opts.shadow ? { shadow: opts.shadow } : {}),
    ...(opts.name ? { name: opts.name } : {}),
  });
}

function textBox(slide, text, position, opts = {}) {
  const box = shape(slide, "textbox", position, opts.fill ?? "none", {
    line: opts.line,
    borderRadius: opts.borderRadius,
    shadow: opts.shadow,
    name: opts.name,
  });
  box.text = text;
  box.text.style = {
    typeface: opts.typeface ?? (opts.bold ? HEAD : BODY),
    fontSize: opts.fontSize ?? 24,
    bold: opts.bold ?? false,
    italic: opts.italic ?? false,
    color: opts.color ?? C.ink,
    alignment: opts.alignment ?? "left",
    verticalAlignment: opts.verticalAlignment ?? "top",
    autoFit: opts.autoFit ?? "none",
    wrap: "square",
    lineSpacing: opts.lineSpacing ?? 1.06,
    insets: opts.insets ?? { top: 0, right: 0, bottom: 0, left: 0 },
  };
  return box;
}

function line(slide, left, top, width, height = 0, color = C.line, thickness = 2) {
  return shape(slide, "line", { left, top, width, height }, "none", {
    line: { style: "solid", fill: color, width: thickness },
  });
}

async function image(slide, filePath, position, opts = {}) {
  const bytes = await fs.readFile(filePath);
  const ext = path.extname(filePath).toLowerCase();
  const contentType = ext === ".png" ? "image/png" : ext === ".svg" ? "image/svg+xml" : "image/jpeg";
  return slide.images.add({
    blob: bytes,
    contentType,
    alt: opts.alt ?? path.basename(filePath),
    fit: opts.fit ?? "cover",
    position,
    ...(opts.geometry ? { geometry: opts.geometry } : {}),
    ...(opts.borderRadius ? { borderRadius: opts.borderRadius } : {}),
  });
}

function title(slide, heading, subheading = null, opts = {}) {
  const left = opts.left ?? 72;
  const top = opts.top ?? 44;
  const width = opts.width ?? 1136;
  shape(slide, "rect", { left, top: top + 6, width: 8, height: 50 }, opts.accent ?? C.red);
  textBox(slide, heading, { left: left + 24, top, width: width - 24, height: opts.titleHeight ?? 66 }, {
    fontSize: opts.titleSize ?? 46,
    bold: true,
    color: opts.color ?? C.ink,
    lineSpacing: 0.97,
  });
  if (subheading) {
    textBox(slide, subheading, { left: left + 24, top: top + (opts.subTop ?? 74), width: width - 24, height: opts.subHeight ?? 55 }, {
      fontSize: opts.subSize ?? 22,
      color: opts.subColor ?? C.muted,
      lineSpacing: 1.08,
    });
  }
}

function page(slide, number, dark = false) {
  textBox(slide, String(number).padStart(2, "0"), { left: 1170, top: 678, width: 40, height: 18 }, {
    fontSize: 13,
    bold: true,
    alignment: "right",
    color: dark ? "#FFFFFFA6" : "#8D9295",
  });
}

function numberBlock(slide, number, heading, body, x, y, width, opts = {}) {
  textBox(slide, String(number).padStart(2, "0"), { left: x, top: y, width: 48, height: 28 }, {
    fontSize: 18,
    bold: true,
    color: opts.numberColor ?? C.red,
  });
  textBox(slide, heading, { left: x + 62, top: y - 4, width: width - 62, height: 34 }, {
    fontSize: opts.headingSize ?? 25,
    bold: true,
    color: opts.headingColor ?? C.ink,
  });
  textBox(slide, body, { left: x + 62, top: y + 38, width: width - 62, height: opts.bodyHeight ?? 70 }, {
    fontSize: opts.bodySize ?? 20,
    color: opts.bodyColor ?? C.muted,
    lineSpacing: 1.08,
  });
}

// 1. Cover
{
  const slide = presentation.slides.add();
  slide.background.fill = C.red;
  shape(slide, "ellipse", { left: 716, top: -168, width: 760, height: 982 }, C.cream);
  shape(slide, "roundRect", { left: 868, top: 34, width: 300, height: 640 }, C.white, {
    borderRadius: "rounded-3xl",
    shadow: "shadow-lg",
  });
  await image(slide, asset("images", "app-preview", "home.png"), { left: 891, top: 57, width: 254, height: 594 }, {
    fit: "cover",
    alt: "PahatudFood mobile app home screen",
  });
  await image(slide, asset("images", "logo-white.jpg"), { left: 66, top: 52, width: 288, height: 96 }, {
    fit: "cover",
    alt: "Pahatud Delivery Services logo",
  });
  textBox(slide, "What Pahatud Really Is", { left: 66, top: 210, width: 690, height: 104 }, {
    fontSize: 63,
    bold: true,
    color: C.white,
    lineSpacing: 0.94,
  });
  textBox(slide, "A customer and investor overview of Pahatud's local commerce and delivery platform.", { left: 70, top: 352, width: 622, height: 105 }, {
    fontSize: 27,
    color: C.white,
    lineSpacing: 1.12,
  });
  await image(slide, asset("images", "motor.png"), { left: 82, top: 508, width: 402, height: 164 }, {
    fit: "contain",
    alt: "Pahatud delivery rider illustration",
  });
  textBox(slide, "READY, SET, DELIVERED", { left: 518, top: 601, width: 254, height: 42 }, {
    fill: C.white,
    color: C.redDark,
    fontSize: 16,
    bold: true,
    alignment: "center",
    verticalAlignment: "middle",
    borderRadius: "rounded-full",
    insets: { top: 3, right: 12, bottom: 3, left: 12 },
  });
  slide.speakerNotes.textFrame.setText(
    "Sources: resources/views/pages/about.blade.php and resources/views/pages/home.blade.php. Assets: public/images/logo-big.png, public/images/motor.png, public/images/app-preview/home.png.",
  );
}

// 2. Definition and origin
{
  const slide = presentation.slides.add();
  slide.background.fill = C.white;
  title(slide, "A local delivery marketplace", "Pahatud connects customers with neighborhood restaurants and merchants, then coordinates the people and tools needed to complete delivery.", { width: 680, subHeight: 82 });
  textBox(slide, "Pahatud means “to request to deliver.”", { left: 76, top: 224, width: 610, height: 56 }, {
    fontSize: 32,
    bold: true,
    color: C.red,
  });
  numberBlock(slide, 1, "For customers", "Discover local choices, place orders, use discounts, and follow delivery from the mobile experience.", 76, 316, 610);
  numberBlock(slide, 2, "For local businesses", "Publish products, manage locations and orders, run promotions, and review sales and commission.", 76, 445, 610, { numberColor: C.amber });
  numberBlock(slide, 3, "For the local network", "Coordinate riders, agents, merchant applications, support, and operational reporting.", 76, 574, 610, { numberColor: C.green });
  shape(slide, "roundRect", { left: 760, top: 58, width: 448, height: 586 }, C.cream, { borderRadius: "rounded-3xl" });
  await image(slide, asset("images", "pahatud-logo-with-motor.jpg"), { left: 786, top: 96, width: 396, height: 264 }, {
    fit: "cover",
    alt: "Pahatud Delivery Services brand with rider graphic",
  });
  textBox(slide, "Davao-born", { left: 812, top: 398, width: 320, height: 48 }, {
    fontSize: 34,
    bold: true,
    color: C.ink,
  });
  textBox(slide, "Built around the daily needs of Davao customers, restaurants, merchants, riders, and local entrepreneurs.", { left: 812, top: 458, width: 332, height: 122 }, {
    fontSize: 23,
    color: C.muted,
    lineSpacing: 1.12,
  });
  page(slide, 2);
  slide.speakerNotes.textFrame.setText(
    "Sources: resources/views/pages/about.blade.php; resources/views/pages/home.blade.php; AGENTS.md platform summary. Brand image: public/images/pahatud-logo-with-motor.jpg.",
  );
}

// 3. Customer journey
{
  const slide = presentation.slides.add();
  slide.background.fill = C.cream;
  title(slide, "The customer experience", "One simple journey from local discovery to delivery at the door.");
  shape(slide, "roundRect", { left: 76, top: 172, width: 332, height: 482 }, C.white, { borderRadius: "rounded-3xl", shadow: "shadow-md" });
  await image(slide, asset("images", "app-preview", "restaurants.png"), { left: 133, top: 193, width: 218, height: 439 }, {
    fit: "cover",
    alt: "Nearby restaurants in the PahatudFood app",
  });
  const journey = [
    ["01", "Choose", "Browse nearby restaurants and see delivery estimates and fees."],
    ["02", "Order", "Select products, quantities, add-ons, address, and payment method."],
    ["03", "Save", "Apply available coupon discounts when an order qualifies."],
    ["04", "Track", "Follow the order and delivery status until it reaches the customer."],
  ];
  journey.forEach(([n, h, b], i) => {
    const y = 198 + i * 112;
    textBox(slide, n, { left: 488, top: y, width: 58, height: 36 }, {
      fontSize: 17,
      bold: true,
      color: C.white,
      fill: i === 3 ? C.green : C.red,
      alignment: "center",
      verticalAlignment: "middle",
      borderRadius: "rounded-full",
    });
    textBox(slide, h, { left: 572, top: y - 2, width: 164, height: 38 }, { fontSize: 28, bold: true });
    textBox(slide, b, { left: 744, top: y - 1, width: 440, height: 62 }, { fontSize: 21, color: C.muted, lineSpacing: 1.08 });
    if (i < journey.length - 1) line(slide, 518, y + 43, 0, 63, C.line, 2);
  });
  textBox(slide, "Convenient cash on delivery is promoted in the current customer experience.", { left: 488, top: 626, width: 696, height: 28 }, {
    fontSize: 17,
    italic: true,
    color: C.muted,
  });
  page(slide, 3);
  slide.speakerNotes.textFrame.setText(
    "Sources: resources/views/pages/home.blade.php; routes/api.php checkout coupon routes; app/Http/Controllers/Api/User/Cart/CheckoutController.php. Screenshot: public/images/app-preview/restaurants.png.",
  );
}

// 4. Service categories
{
  const slide = presentation.slides.add();
  slide.background.fill = C.white;
  title(slide, "More than restaurant ordering", "The platform already supports several ways for local customers to request products and delivery.");
  const columns = [
    {
      x: 72,
      imagePath: asset("uploads", "61", "2021-04-12-cheese-burger.jpg"),
      alt: "Restaurant burger product listed in Pahatud",
      heading: "Food and restaurants",
      body: "Local restaurant discovery, menu ordering, cart, checkout, discounts, and order history.",
    },
    {
      x: 444,
      imagePath: asset("uploads", "280", "2021-06-26-red.png"),
      alt: "Flower bouquet offered through the Pahatud flower store",
      heading: "Flowers and gifts",
      body: "A dedicated flower-store experience supports local florists and same-day delivery offers.",
    },
    {
      x: 816,
      imagePath: asset("images", "booking", "banner1.jpg"),
      alt: "Booking and request delivery illustration",
      heading: "Delivery requests",
      body: "Booking routes and rider workflows support customer requests beyond standard restaurant orders.",
    },
  ];
  for (const item of columns) {
    shape(slide, "roundRect", { left: item.x, top: 176, width: 332, height: 274 }, C.blush, { borderRadius: "rounded-3xl" });
    await image(slide, item.imagePath, { left: item.x + 12, top: 188, width: 308, height: 250 }, {
      fit: "cover",
      alt: item.alt,
      borderRadius: "rounded-2xl",
    });
    textBox(slide, item.heading, { left: item.x, top: 478, width: 332, height: 42 }, { fontSize: 28, bold: true });
    textBox(slide, item.body, { left: item.x, top: 532, width: 332, height: 106 }, { fontSize: 20, color: C.muted, lineSpacing: 1.1 });
  }
  page(slide, 4);
  slide.speakerNotes.textFrame.setText(
    "Sources: routes/web.php restaurant, flower-store, and booking routes; resources/views/pages/flower/view.blade.php; resources/views/pages/booking/view.blade.php; checkout and customer profile routes. Assets are existing Pahatud files under public/uploads and public/images.",
  );
}

// 5. Merchant value
{
  const slide = presentation.slides.add();
  slide.background.fill = C.blush;
  title(slide, "A practical operating system for merchants", "Pahatud gives restaurants a way to join the marketplace and run daily commerce from one dashboard.", { width: 1150 });
  shape(slide, "roundRect", { left: 72, top: 174, width: 384, height: 486 }, C.white, { borderRadius: "rounded-3xl", shadow: "shadow-md" });
  await image(slide, asset("images", "partner-with-us.jpg"), { left: 100, top: 192, width: 328, height: 450 }, {
    fit: "contain",
    alt: "Pahatud merchant partnership illustration",
  });
  const functions = [
    ["Get ready", "Maintain business profile, branch addresses, contacts, and payout details."],
    ["Build the menu", "Add products, prices, categories, availability, and add-ons."],
    ["Run orders", "Review new orders, prepare items, and follow delivery status."],
    ["Learn and improve", "Review sales, commission, discounts, net amount, vouchers, and coupons."],
  ];
  functions.forEach(([h, b], i) => {
    const x = i % 2 === 0 ? 520 : 868;
    const y = i < 2 ? 212 : 432;
    textBox(slide, String(i + 1).padStart(2, "0"), { left: x, top: y, width: 42, height: 26 }, { fontSize: 16, bold: true, color: i === 3 ? C.green : C.red });
    textBox(slide, h, { left: x, top: y + 36, width: 294, height: 38 }, { fontSize: 27, bold: true });
    textBox(slide, b, { left: x, top: y + 87, width: 300, height: 112 }, { fontSize: 20, color: C.muted, lineSpacing: 1.1 });
  });
  page(slide, 5);
  slide.speakerNotes.textFrame.setText(
    "Source: resources/views/merchant/partials/documentation.blade.php and merchant routes in routes/web.php. Illustration: public/images/partner-with-us.jpg.",
  );
}

// 6. Operations
{
  const slide = presentation.slides.add();
  slide.background.fill = C.white;
  title(slide, "The operating network behind each order", "Riders, agents, and Pahatud operations turn marketplace demand into completed local delivery.");
  textBox(slide, "Rider operations", { left: 76, top: 190, width: 316, height: 42 }, { fontSize: 30, bold: true, color: C.red });
  textBox(slide, "Nearby job offers\nRoute and delivery events\nPickup and customer verification\nCOD, proof, wallet, and earnings tools", { left: 76, top: 252, width: 354, height: 188 }, {
    fontSize: 22,
    color: C.ink,
    lineSpacing: 1.4,
  });
  line(slide, 76, 463, 354, 0, C.line, 2);
  textBox(slide, "Agent growth program", { left: 76, top: 488, width: 338, height: 42 }, { fontSize: 30, bold: true, color: C.green });
  textBox(slide, "Agents enroll restaurants, help complete document review, monitor approval, and earn a share of Pahatud's commission on qualifying delivered orders.", { left: 76, top: 548, width: 368, height: 108 }, {
    fontSize: 21,
    color: C.muted,
    lineSpacing: 1.1,
  });
  shape(slide, "roundRect", { left: 520, top: 164, width: 334, height: 498 }, C.cream, { borderRadius: "rounded-3xl", shadow: "shadow-md" });
  await image(slide, asset("images", "app-preview", "order-tracking.png"), { left: 574, top: 184, width: 226, height: 454 }, {
    fit: "cover",
    alt: "Pahatud order tracking screen",
  });
  shape(slide, "roundRect", { left: 904, top: 214, width: 304, height: 352 }, C.red, { borderRadius: "rounded-3xl" });
  textBox(slide, "Pahatud operations", { left: 940, top: 252, width: 232, height: 72 }, { fontSize: 31, bold: true, color: C.white, lineSpacing: 1 });
  textBox(slide, "Approves applications\nCoordinates support\nMonitors orders and bookings\nReviews reports and payouts", { left: 940, top: 352, width: 232, height: 150 }, {
    fontSize: 21,
    color: C.white,
    lineSpacing: 1.35,
  });
  page(slide, 6);
  slide.speakerNotes.textFrame.setText(
    "Sources: routes/rider.php; app/Services/RiderOfferDispatcher.php; resources/views/agent/dashboard.blade.php; app/Services/AgentCommissionService.php; admin routes in routes/web.php. Screenshot: public/images/app-preview/order-tracking.png.",
  );
}

// 7. Marketplace network diagram
{
  const slide = presentation.slides.add();
  slide.background.fill = C.cream;
  title(slide, "A stronger network creates more value", "More local supply creates more customer choice. More orders create more value for merchants, riders, agents, and Pahatud.");

  const center = { x: 554, y: 271, w: 172, h: 172 };
  shape(slide, "ellipse", { left: center.x, top: center.y, width: center.w, height: center.h }, C.red, { shadow: "shadow-lg" });
  textBox(slide, "PAHATUD", { left: 570, top: 322, width: 140, height: 42 }, {
    fontSize: 24,
    bold: true,
    color: C.white,
    alignment: "center",
    verticalAlignment: "middle",
  });

  const nodes = [
    { x: 86, y: 196, w: 308, h: 138, color: C.white, label: "CUSTOMERS", body: "Choice, convenience, discounts, and delivery tracking" },
    { x: 86, y: 454, w: 308, h: 138, color: C.white, label: "MERCHANTS", body: "Digital storefront, orders, promotions, and reporting" },
    { x: 886, y: 196, w: 308, h: 138, color: C.white, label: "RIDERS", body: "Delivery offers, routing, earnings, and service proof" },
    { x: 886, y: 454, w: 308, h: 138, color: C.white, label: "AGENTS", body: "Merchant enrollment, guidance, and network growth" },
  ];
  nodes.forEach((n, i) => {
    shape(slide, "roundRect", { left: n.x, top: n.y, width: n.w, height: n.h }, n.color, { borderRadius: "rounded-2xl", shadow: "shadow-sm" });
    textBox(slide, n.label, { left: n.x + 26, top: n.y + 26, width: n.w - 52, height: 26 }, { fontSize: 17, bold: true, color: i === 3 ? C.green : C.red });
    textBox(slide, n.body, { left: n.x + 26, top: n.y + 62, width: n.w - 52, height: 62 }, { fontSize: 19, color: C.muted, lineSpacing: 1.08 });
  });

  line(slide, 394, 265, 164, 75, C.red, 3);
  line(slide, 394, 443, 164, 11, C.amber, 3);
  line(slide, 726, 334, 160, 6, C.blue, 3);
  line(slide, 726, 438, 160, 84, C.green, 3);
  textBox(slide, "The same platform coordinates demand, local supply, delivery, and merchant growth.", { left: 410, top: 542, width: 460, height: 58 }, {
    fontSize: 21,
    bold: true,
    color: C.ink,
    alignment: "center",
  });
  page(slide, 7);
  slide.speakerNotes.textFrame.setText(
    "Diagram synthesized from current customer, merchant, rider, agent, and admin capabilities in routes/web.php, routes/api.php, routes/rider.php, resources/views/pages/home.blade.php, resources/views/merchant/partials/documentation.blade.php, and resources/views/agent/dashboard.blade.php.",
  );
}

// 8. Business model
{
  const slide = presentation.slides.add();
  slide.background.fill = C.white;
  title(slide, "How value and revenue move", "Pahatud's current transaction mechanics connect revenue to completed marketplace activity.");
  const lanes = [
    {
      y: 190,
      color: C.red,
      n: "01",
      heading: "Customer transaction",
      body: "The customer pays for products, applies any discount, and pays the calculated delivery fee.",
      value: "ORDER VALUE + DELIVERY FEE",
    },
    {
      y: 332,
      color: C.amber,
      n: "02",
      heading: "Merchant commission",
      body: "Pahatud records an agreed commission percentage on the eligible product subtotal of delivered orders.",
      value: "TRANSACTION-LINKED PLATFORM REVENUE",
    },
    {
      y: 474,
      color: C.green,
      n: "03",
      heading: "Delivery commission and incentives",
      body: "The rider workflow records a platform commission on delivery earnings. Qualifying merchant commission can also fund agent incentives.",
      value: "DELIVERY ECONOMICS + NETWORK GROWTH",
    },
  ];
  lanes.forEach((l) => {
    textBox(slide, l.n, { left: 76, top: l.y, width: 64, height: 64 }, {
      fontSize: 19,
      bold: true,
      color: C.white,
      fill: l.color,
      alignment: "center",
      verticalAlignment: "middle",
      borderRadius: "rounded-full",
    });
    textBox(slide, l.heading, { left: 174, top: l.y - 2, width: 292, height: 42 }, { fontSize: 28, bold: true });
    textBox(slide, l.body, { left: 488, top: l.y - 2, width: 414, height: 86 }, { fontSize: 20, color: C.muted, lineSpacing: 1.08 });
    shape(slide, "roundRect", { left: 936, top: l.y - 3, width: 272, height: 74 }, C.cream, { borderRadius: "rounded-xl" });
    textBox(slide, l.value, { left: 957, top: l.y + 11, width: 230, height: 44 }, { fontSize: 15, bold: true, color: l.color, alignment: "center", verticalAlignment: "middle" });
  });
  textBox(slide, "Rates are configurable and should be confirmed in commercial agreements. This slide explains the mechanics, not historical financial performance.", { left: 76, top: 635, width: 1055, height: 32 }, {
    fontSize: 16,
    italic: true,
    color: C.muted,
  });
  page(slide, 8);
  slide.speakerNotes.textFrame.setText(
    "Sources: app/Services/AgentCommissionService.php; app/Services/RiderCommissionService.php; app/Services/RiderOfferDispatcher.php; app/Model/Cart.php; resources/views/merchant/pages/reports/today.blade.php. No financial totals or forecasts are claimed.",
  );
}

// 9. Investor view
{
  const slide = presentation.slides.add();
  slide.background.fill = C.blush;
  title(slide, "The investor view", "The opportunity is to scale a working local marketplace foundation, then prove repeatable demand and healthy unit economics.");
  textBox(slide, "What already exists", { left: 76, top: 190, width: 470, height: 44 }, { fontSize: 31, bold: true, color: C.red });
  const existing = [
    "Customer ordering and delivery tracking",
    "Merchant onboarding and operations dashboard",
    "Rider onboarding, dispatch, proof, COD, and wallet flows",
    "Agent-led restaurant acquisition and commission reporting",
    "Food, flower-store, coupon, voucher, and booking capabilities",
  ];
  existing.forEach((item, i) => {
    textBox(slide, String(i + 1).padStart(2, "0"), { left: 76, top: 254 + i * 69, width: 38, height: 26 }, { fontSize: 15, bold: true, color: C.red });
    textBox(slide, item, { left: 130, top: 250 + i * 69, width: 456, height: 52 }, { fontSize: 21, color: C.ink, lineSpacing: 1.05 });
  });

  shape(slide, "roundRect", { left: 660, top: 178, width: 548, height: 464 }, C.ink, { borderRadius: "rounded-3xl" });
  textBox(slide, "Investment priorities", { left: 704, top: 220, width: 444, height: 48 }, { fontSize: 31, bold: true, color: C.white });
  const priorities = [
    ["Merchant density", "Add strong local supply in focused service areas."],
    ["Customer frequency", "Improve repeat ordering, offers, and retention."],
    ["Delivery reliability", "Strengthen rider availability, support, and service quality."],
    ["Measurement", "Track acquisition cost, order frequency, contribution margin, and cohort retention."],
  ];
  priorities.forEach(([h, b], i) => {
    const y = 294 + i * 78;
    shape(slide, "ellipse", { left: 706, top: y + 2, width: 18, height: 18 }, i === 3 ? C.green : C.red);
    textBox(slide, h, { left: 744, top: y - 4, width: 190, height: 30 }, { fontSize: 21, bold: true, color: C.white });
    textBox(slide, b, { left: 950, top: y - 4, width: 212, height: 54 }, { fontSize: 18, color: "#D7DADD", lineSpacing: 1.05 });
  });
  textBox(slide, "Investor diligence should add verified traction, financial history, market size, and fundraising terms.", { left: 76, top: 653, width: 1030, height: 28 }, { fontSize: 16, italic: true, color: C.muted });
  page(slide, 9);
  slide.speakerNotes.textFrame.setText(
    "Current-capability sources: routes/web.php, routes/api.php, routes/rider.php, resources/views/pages/home.blade.php, resources/views/merchant/partials/documentation.blade.php, resources/views/agent/dashboard.blade.php. Growth priorities are strategic opportunities, not promises or forecasts. No traction or financial metrics were supplied.",
  );
}

// 10. Closing
{
  const slide = presentation.slides.add();
  slide.background.fill = C.ink;
  await image(slide, asset("uploads", "user", "29", "banner", "2021-04-10-banner-burgerjoint.jpg"), { left: 0, top: 0, width: 1280, height: 720 }, {
    fit: "cover",
    alt: "Local restaurant food available through Pahatud",
  });
  shape(slide, "rect", { left: 0, top: 0, width: 1280, height: 720 }, "#171A1CDD");
  await image(slide, asset("images", "logo-white.jpg"), { left: 72, top: 55, width: 284, height: 96 }, { fit: "cover", alt: "Pahatud logo" });
  textBox(slide, "Pahatud makes local commerce easier to discover, operate, and deliver.", { left: 76, top: 192, width: 824, height: 150 }, {
    fontSize: 53,
    bold: true,
    color: C.white,
    lineSpacing: 0.98,
  });
  textBox(slide, "For customers", { left: 80, top: 418, width: 250, height: 38 }, { fontSize: 24, bold: true, color: C.coral });
  textBox(slide, "Discover local favorites and order through the PahatudFood app.", { left: 80, top: 466, width: 324, height: 76 }, { fontSize: 21, color: C.white, lineSpacing: 1.08 });
  textBox(slide, "For partners and investors", { left: 476, top: 418, width: 340, height: 38 }, { fontSize: 24, bold: true, color: C.coral });
  textBox(slide, "Help build a stronger local marketplace with more merchants, reliable delivery, and measurable growth.", { left: 476, top: 466, width: 418, height: 86 }, { fontSize: 21, color: C.white, lineSpacing: 1.08 });
  shape(slide, "roundRect", { left: 932, top: 420, width: 278, height: 160 }, C.red, { borderRadius: "rounded-2xl" });
  textBox(slide, "www.pahatud.com\ninfo@pahatud.com", { left: 958, top: 462, width: 226, height: 72 }, { fontSize: 22, bold: true, color: C.white, alignment: "center", lineSpacing: 1.25 });
  textBox(slide, "Ready, set, delivered.", { left: 80, top: 630, width: 430, height: 42 }, { fontSize: 27, bold: true, color: C.white });
  page(slide, 10, true);
  slide.speakerNotes.textFrame.setText(
    "Sources: resources/views/pages/about.blade.php, resources/views/pages/home.blade.php, and resources/views/merchant/partials/documentation.blade.php for contact details. Background: public/uploads/user/29/banner/2021-04-10-banner-burgerjoint.jpg. Logo: public/images/logo-big.png.",
  );
}

await fs.mkdir(TMP_DIR, { recursive: true });
const stagingDir = path.join(TMP_DIR, ".finalizer");
await fs.mkdir(stagingDir, { recursive: true });
await fs.mkdir(path.dirname(FINAL_PPTX), { recursive: true });
const candidatePath = path.join(stagingDir, "candidate.pptx");
await (await PresentationFile.exportPptx(presentation)).save(candidatePath);

const requirements = {
  explicitTotalSlideCount: 10,
  requiredNativeTableOwnerSlides: [],
  requiredNativeChartOwnerSlides: [],
};
const fontPolicy = { basis: "design", families: [HEAD, BODY] };
const expectedSlideSizeEmu = "12192000,6858000";

const result = await finalizePresentation({
  ...requirements,
  workspaceDir,
  candidatePath,
  finalPath: FINAL_PPTX,
  pythonExecutable: RUNTIME_PYTHON,
  integrityValidatorPath: path.join(SKILL_DIR, "container_tools/inspect_presentation_package_integrity.py"),
  layoutValidatorPath: path.join(SKILL_DIR, "container_tools/inspect_presentation_layout_geometry.py"),
  layoutArgs: [
    "--expected-slide-size-emu", expectedSlideSizeEmu,
    "--validate-bullet-geometry",
    "--validate-heading-fit",
  ],
  requiredNativeTableOwnerSlides: [],
  fontPolicy,
  verifyArtifactToolImport: true,
  receiptPath: path.join(stagingDir, `${path.basename(FINAL_PPTX)}.validation.json`),
});

console.log(JSON.stringify({ finalPath: FINAL_PPTX, result }, null, 2));
