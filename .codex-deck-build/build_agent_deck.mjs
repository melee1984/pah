import fs from "node:fs/promises";
import path from "node:path";
import { pathToFileURL } from "node:url";
import { Presentation, PresentationFile } from "@oai/artifact-tool";

const workspaceDir = "/Users/larryparba/web/pah";
const SKILL_DIR = "/Users/larryparba/.codex/plugins/cache/openai-primary-runtime/presentations/26.909.12148/skills/presentations";
const TMP_DIR = path.join(workspaceDir, ".codex-deck-build");
const FINAL_PPTX = path.join(workspaceDir, "artifacts", "Pahatud_Agent_Program_New_Brand_2026-09-21_v3.pptx");
const RUNTIME_PYTHON = "/Users/larryparba/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/bin/python3";

const { finalizePresentation } = await import(
  pathToFileURL(path.join(SKILL_DIR, "container_tools/artifact_tool_utils.mjs")).href,
);

const W = 1280;
const H = 720;
const HEAD_FONT = "Manrope";
const BODY_FONT = "DM Sans";

const C = {
  red: "#EF3B35",
  redDark: "#C72A2E",
  blush: "#FFF1EF",
  blush2: "#FCE0DC",
  warm: "#F7F3ED",
  ink: "#202426",
  muted: "#60686C",
  line: "#D7D9D9",
  white: "#FFFFFF",
  green: "#138A64",
  amber: "#F1A33A",
  paleGreen: "#E6F5EF",
};

const asset = (...segments) => path.join(workspaceDir, "public", ...segments);

const presentation = Presentation.create({ slideSize: { width: W, height: H } });

function addShape(slide, geometry, position, fill, opts = {}) {
  return slide.shapes.add({
    geometry,
    position,
    fill,
    line: opts.line ?? { style: "solid", fill: "none", width: 0 },
    ...(opts.name ? { name: opts.name } : {}),
    ...(opts.borderRadius ? { borderRadius: opts.borderRadius } : {}),
    ...(opts.shadow ? { shadow: opts.shadow } : {}),
  });
}

function addText(slide, text, position, opts = {}) {
  const shape = addShape(slide, "textbox", position, opts.fill ?? "none", {
    line: opts.line,
    name: opts.name,
    borderRadius: opts.borderRadius,
    shadow: opts.shadow,
  });
  shape.text = text;
  shape.text.style = {
    typeface: opts.typeface ?? (opts.bold ? HEAD_FONT : BODY_FONT),
    fontSize: opts.fontSize ?? 24,
    bold: opts.bold ?? false,
    italic: opts.italic ?? false,
    color: opts.color ?? C.ink,
    alignment: opts.alignment ?? "left",
    verticalAlignment: opts.verticalAlignment ?? "top",
    autoFit: opts.autoFit ?? "none",
    wrap: "square",
    lineSpacing: opts.lineSpacing ?? 1.05,
    insets: opts.insets ?? { top: 0, right: 0, bottom: 0, left: 0 },
  };
  return shape;
}

function addLine(slide, x, y, width, height, color = C.line, thickness = 2) {
  return addShape(slide, "line", { left: x, top: y, width, height }, "none", {
    line: { style: "solid", fill: color, width: thickness },
  });
}

function addPill(slide, text, x, y, w, fill, color) {
  return addText(slide, text, { left: x, top: y, width: w, height: 34 }, {
    fill,
    color,
    fontSize: 16,
    bold: true,
    alignment: "center",
    verticalAlignment: "middle",
    borderRadius: "rounded-full",
    insets: { top: 2, right: 12, bottom: 2, left: 12 },
  });
}

function addSectionTitle(slide, kicker, title, subtitle, opts = {}) {
  const left = opts.left ?? 72;
  const top = opts.top ?? 52;
  const width = opts.width ?? 1136;
  const titleColor = opts.titleColor ?? C.ink;
  const bodyColor = opts.bodyColor ?? C.muted;
  addText(slide, kicker.toUpperCase(), { left, top, width, height: 25 }, {
    fontSize: 15, bold: true, color: opts.kickerColor ?? C.red,
  });
  addText(slide, title, { left, top: top + 32, width, height: opts.titleHeight ?? 72 }, {
    fontSize: opts.titleSize ?? 47, bold: true, color: titleColor, lineSpacing: 0.95,
  });
  if (subtitle) {
    addText(slide, subtitle, { left, top: top + (opts.subtitleTop ?? 111), width, height: 52 }, {
      fontSize: 22, color: bodyColor, lineSpacing: 1.1,
    });
  }
}

function addPageMark(slide, n, dark = false) {
  addText(slide, String(n).padStart(2, "0"), { left: 1170, top: 666, width: 40, height: 22 }, {
    fontSize: 14, bold: true, color: dark ? "#FFFFFF99" : "#8B9295",
    alignment: "right",
  });
}

async function addImage(slide, filePath, position, opts = {}) {
  const bytes = await fs.readFile(filePath);
  const ext = path.extname(filePath).toLowerCase();
  const contentType = ext === ".png" ? "image/png" : (ext === ".svg" ? "image/svg+xml" : "image/jpeg");
  return slide.images.add({
    blob: bytes,
    contentType,
    alt: opts.alt ?? path.basename(filePath),
    fit: opts.fit ?? "cover",
    position,
    ...(opts.crop ? { crop: opts.crop } : {}),
    ...(opts.geometry ? { geometry: opts.geometry } : {}),
    ...(opts.borderRadius ? { borderRadius: opts.borderRadius } : {}),
  });
}

function addBullet(slide, heading, body, x, y, w, accent = C.red) {
  addShape(slide, "ellipse", { left: x, top: y + 5, width: 12, height: 12 }, accent);
  addText(slide, heading, { left: x + 25, top: y, width: w - 25, height: 30 }, {
    fontSize: 23, bold: true, color: C.ink,
  });
  addText(slide, body, { left: x + 25, top: y + 34, width: w - 25, height: 58 }, {
    fontSize: 21, color: C.muted, lineSpacing: 1.1,
  });
}

// Slide 1: Cover
{
  const slide = presentation.slides.add();
  slide.background.fill = C.red;
  addShape(slide, "ellipse", { left: 742, top: 0, width: 538, height: 720 }, C.blush2);
  addShape(slide, "roundRect", { left: 785, top: 70, width: 420, height: 580 }, C.white, {
    borderRadius: "rounded-3xl", shadow: "shadow-lg",
  });
  await addImage(slide, asset("images", "banner-right.png"), { left: 803, top: 88, width: 384, height: 544 }, {
    fit: "contain", geometry: "roundRect", borderRadius: "rounded-2xl",
    alt: "Food available through PahatudFood restaurant partners",
  });
  addShape(slide, "roundRect", { left: 68, top: 52, width: 74, height: 74 }, C.white, { borderRadius: "rounded-xl" });
  await addImage(slide, path.join(TMP_DIR, "pahatud-new-logo.svg"), { left: 74, top: 58, width: 62, height: 62 }, {
    fit: "contain", alt: "PahatudFood logo",
  });
  addText(slide, "PAHATUDFOOD AGENT PROGRAM", { left: 152, top: 67, width: 480, height: 35 }, {
    fontSize: 18, bold: true, color: C.white, verticalAlignment: "middle",
  });
  addText(slide, "Connect local\nrestaurants.\nGrow together.", { left: 74, top: 176, width: 620, height: 258 }, {
    fontSize: 70, bold: true, color: C.white, lineSpacing: 0.9,
  });
  addText(slide, "A practical introduction to the Pahatud Agent role, benefits, earnings, and tools.", { left: 78, top: 472, width: 560, height: 82 }, {
    fontSize: 24, color: C.white, lineSpacing: 1.12,
  });
  addPill(slide, "AGENT ORIENTATION", 78, 594, 206, C.white, C.red);
  addText(slide, "2026", { left: 306, top: 598, width: 90, height: 28 }, { fontSize: 18, bold: true, color: C.white });
  slide.speakerNotes.textFrame.setText("Pahatud Agent orientation deck. Program language is based on resources/views/agent/auth/register.blade.php and resources/views/agent/help.blade.php.");
}

// Slide 2: Definition and purpose
{
  const slide = presentation.slides.add();
  slide.background.fill = C.white;
  addSectionTitle(slide, "Your role", "Agents connect restaurants to Pahatud.", "You introduce, guide, and support restaurants as they join the marketplace.", { width: 720, titleHeight: 118, subtitleTop: 160 });
  addBullet(slide, "Connect", "Find restaurants that can benefit from online ordering and delivery.", 76, 322, 560);
  addBullet(slide, "Guide", "Help owners complete enrollment, documents, and requested corrections.", 76, 425, 560, C.amber);
  addBullet(slide, "Grow", "Stay connected as approved restaurants receive qualifying orders.", 76, 528, 560, C.green);
  addShape(slide, "roundRect", { left: 820, top: 56, width: 342, height: 608 }, C.blush, {
    borderRadius: "rounded-3xl",
  });
  addShape(slide, "ellipse", { left: 764, top: 505, width: 170, height: 170 }, C.red);
  await addImage(slide, asset("images", "app-preview", "restaurants.png"), { left: 882, top: 84, width: 220, height: 550 }, {
    fit: "contain", geometry: "roundRect", borderRadius: "rounded-2xl", alt: "Pahatud mobile marketplace showing local restaurants",
  });
  addPill(slide, "LOCAL MARKETPLACE", 730, 590, 218, C.ink, C.white);
  addPageMark(slide, 2);
  slide.speakerNotes.textFrame.setText("Source: resources/views/agent/auth/register.blade.php, sections 'How the Agent Program works' and 'What you get as a Pahatud agent'.");
}

// Slide 3: Benefits
{
  const slide = presentation.slides.add();
  slide.background.fill = C.blush;
  addSectionTitle(slide, "What agents get", "A clear way to build a network and see the value it creates.", null, { width: 1000, titleHeight: 78 });
  addShape(slide, "ellipse", { left: 484, top: 214, width: 312, height: 312 }, C.red);
  addText(slide, "WHAT\nYOU GET", { left: 520, top: 300, width: 240, height: 130 }, {
    fontSize: 42, bold: true, color: C.white, alignment: "center", lineSpacing: 0.9,
  });

  const benefits = [
    ["01", "Commission opportunities", "Earn a share of Pahatud's commission on qualifying successful orders.", 76, 208, 350],
    ["02", "Your restaurant network", "Enroll restaurants and monitor every partner connected to your account.", 84, 444, 354],
    ["03", "Order visibility", "See submitted orders and eligible sales tied to commission activity.", 864, 184, 342],
    ["04", "Transparent reporting", "Review pending, approved, paid, and reversed entries by restaurant and order.", 856, 382, 350],
    ["05", "Help when you need it", "Use the built-in guide and email agent support at info@pahatud.com.", 850, 558, 356],
  ];
  for (const [num, heading, body, x, y, w] of benefits) {
    addText(slide, num, { left: x, top: y, width: 46, height: 26 }, { fontSize: 16, bold: true, color: C.red });
    addText(slide, heading, { left: x + 52, top: y - 2, width: w - 52, height: 34 }, { fontSize: 22, bold: true });
    addText(slide, body, { left: x + 52, top: y + 34, width: w - 52, height: 72 }, { fontSize: 20, color: C.muted, lineSpacing: 1.06 });
  }
  addPageMark(slide, 3);
  slide.speakerNotes.textFrame.setText("Source: resources/views/agent/auth/register.blade.php and resources/views/agent/help.blade.php. Benefits describe portal capabilities and do not promise a fixed income.");
}

// Slide 4: Earnings formula
{
  const slide = presentation.slides.add();
  slide.background.fill = C.ink;
  addSectionTitle(slide, "How earnings work", "Your earnings come from Pahatud's commission.", "Your share is not calculated from the full order value. Example uses the current default rates.", {
    titleColor: C.white, bodyColor: "#C6CBCE", kickerColor: "#FF8A84", width: 1120, titleHeight: 72,
  });
  addLine(slide, 210, 346, 860, 0, "#61696C", 4);
  const nodes = [
    { x: 150, fill: C.white, top: "ORDER VALUE", value: "₱1,000", bottom: "eligible order" },
    { x: 520, fill: C.amber, top: "PAHATUD RATE", value: "15%", bottom: "₱150 commission" },
    { x: 890, fill: C.red, top: "AGENT SHARE", value: "30%", bottom: "₱45 earned" },
  ];
  for (const node of nodes) {
    addShape(slide, "ellipse", { left: node.x, top: 246, width: 240, height: 200 }, node.fill);
    addText(slide, node.top, { left: node.x + 30, top: 278, width: 180, height: 24 }, {
      fontSize: 14, bold: true, color: node.fill === C.white ? C.red : C.ink, alignment: "center",
    });
    addText(slide, node.value, { left: node.x + 18, top: 309, width: 204, height: 64 }, {
      fontSize: 45, bold: true, color: node.fill === C.red ? C.white : C.ink, alignment: "center",
    });
    addText(slide, node.bottom, { left: node.x + 20, top: 378, width: 200, height: 28 }, {
      fontSize: 16, bold: true, color: node.fill === C.red ? C.white : C.ink, alignment: "center",
    });
  }
  addText(slide, "₱1,000 × 15% × 30% = ₱45", { left: 252, top: 506, width: 776, height: 58 }, {
    fontSize: 37, bold: true, color: C.white, alignment: "center",
  });
  addText(slide, "Actual rates are stored with each order. Your confirmed share appears in the Agent Dashboard.", { left: 260, top: 580, width: 760, height: 48 }, {
    fontSize: 20, color: "#C6CBCE", alignment: "center",
  });
  addPageMark(slide, 4, true);
  slide.speakerNotes.textFrame.setText("Source: config/agent.php. Current defaults are 15% Pahatud commission and a 30% agent share, producing ₱45 on a qualifying ₱1,000 order. Actual agent and restaurant rates may vary and are stored per order.");
}

// Slide 5: Commission lifecycle
{
  const slide = presentation.slides.add();
  slide.background.fill = C.white;
  addSectionTitle(slide, "From order to payout", "Track every commission from order to payout.", "The ledger keeps qualifying and reversed activity visible.", { width: 1080 });
  addLine(slide, 170, 338, 820, 0, C.line, 5);
  const states = [
    { x: 140, color: C.amber, label: "PENDING", title: "Recorded for review", body: "A qualifying delivered order creates a commission entry." },
    { x: 520, color: C.green, label: "APPROVED", title: "Confirmed as eligible", body: "Pahatud validates the entry before payout." },
    { x: 900, color: C.ink, label: "PAID", title: "Payout recorded", body: "Operations coordinates payment and marks the entry paid." },
  ];
  for (const s of states) {
    addShape(slide, "ellipse", { left: s.x, top: 300, width: 76, height: 76 }, s.color);
    addText(slide, "✓", { left: s.x, top: 314, width: 76, height: 48 }, { fontSize: 31, bold: true, color: C.white, alignment: "center" });
    addText(slide, s.label, { left: s.x - 16, top: 400, width: 220, height: 25 }, { fontSize: 14, bold: true, color: s.color });
    addText(slide, s.title, { left: s.x - 16, top: 434, width: 260, height: 36 }, { fontSize: 23, bold: true });
    addText(slide, s.body, { left: s.x - 16, top: 476, width: 276, height: 76 }, { fontSize: 20, color: C.muted, lineSpacing: 1.06 });
  }
  addShape(slide, "roundRect", { left: 76, top: 596, width: 1128, height: 74 }, C.blush, { borderRadius: "rounded-xl" });
  addText(slide, "Reversed entries remain visible for transparency. The current portal tracks commissions but does not initiate self-service withdrawals.", { left: 104, top: 615, width: 1072, height: 42 }, {
    fontSize: 18, bold: true, color: C.redDark, alignment: "center", verticalAlignment: "middle",
  });
  addPageMark(slide, 5);
  slide.speakerNotes.textFrame.setText("Source: resources/views/agent/auth/register.blade.php and resources/views/agent/help.blade.php. Pahatud operations coordinates approved payouts; the dashboard does not currently offer self-service withdrawal.");
}

// Slide 6: Journey
{
  const slide = presentation.slides.add();
  slide.background.fill = C.red;
  addSectionTitle(slide, "Your journey", "Five steps from application to active earnings.", null, {
    titleColor: C.white, kickerColor: C.white, width: 980, titleHeight: 72,
  });
  const steps = [
    ["01", "Apply", "Create your agent account application."],
    ["02", "Get approved", "Wait for Pahatud's review and email decision."],
    ["03", "Enroll", "Add a restaurant and send its private invitation."],
    ["04", "Activate", "Complete details, documents, and final review."],
    ["05", "Earn and track", "Qualifying delivered orders record commission."],
  ];
  const xs = [62, 308, 554, 800, 1046];
  addLine(slide, 108, 338, 972, 0, "#FFFFFF88", 4);
  steps.forEach((step, i) => {
    const [num, title, body] = step;
    addShape(slide, "ellipse", { left: xs[i], top: 292, width: 94, height: 94 }, i === 4 ? C.ink : C.white);
    addText(slide, num, { left: xs[i], top: 317, width: 94, height: 40 }, {
      fontSize: 26, bold: true, color: i === 4 ? C.white : C.red, alignment: "center",
    });
    addText(slide, title, { left: xs[i] - 46, top: 420, width: 188, height: 36 }, {
      fontSize: 23, bold: true, color: C.white, alignment: "center",
    });
    addText(slide, body, { left: xs[i] - 55, top: 462, width: 206, height: 90 }, {
      fontSize: 19, color: C.white, alignment: "center", lineSpacing: 1.06,
    });
  });
  addShape(slide, "roundRect", { left: 314, top: 598, width: 652, height: 62 }, C.white, { borderRadius: "rounded-full" });
  addText(slide, "Restaurant enrollment alone does not generate commission.", { left: 346, top: 613, width: 588, height: 34 }, {
    fontSize: 18, bold: true, color: C.redDark, alignment: "center", verticalAlignment: "middle",
  });
  addPageMark(slide, 6, true);
  slide.speakerNotes.textFrame.setText("Source: resources/views/agent/auth/register.blade.php. Agent application and restaurant enrollment alone do not generate commission; a linked approved restaurant must complete a qualifying delivered order.");
}

// Slide 7: Enrollment requirements
{
  const slide = presentation.slides.add();
  slide.background.fill = C.white;
  addShape(slide, "rect", { left: 0, top: 0, width: 336, height: 720 }, C.ink);
  addText(slide, "HELP A LOCAL\nRESTAURANT\nGET READY", { left: 62, top: 110, width: 250, height: 210 }, {
    fontSize: 34, bold: true, color: C.white, lineSpacing: 0.94,
  });
  addText(slide, "Complete details and current documents make review easier.", { left: 64, top: 402, width: 228, height: 104 }, {
    fontSize: 22, color: "#D6DADC", lineSpacing: 1.12,
  });
  addPill(slide, "ENROLLMENT CHECKLIST", 64, 560, 218, C.red, C.white);

  addText(slide, "Required business details", { left: 400, top: 70, width: 360, height: 46 }, { fontSize: 32, bold: true });
  const detailItems = [
    "Restaurant and registered business names",
    "Contact details and full address",
    "TIN and business structure",
    "Payout account name and account details",
    "Enrollment authority",
  ];
  detailItems.forEach((item, i) => addBullet(slide, item, "", 410, 150 + i * 76, 390, i === 3 ? C.amber : C.red));

  addText(slide, "Required documents", { left: 834, top: 70, width: 352, height: 46 }, { fontSize: 32, bold: true });
  addShape(slide, "roundRect", { left: 828, top: 144, width: 362, height: 136 }, C.blush, { borderRadius: "rounded-2xl" });
  addText(slide, "VALID GOVERNMENT ID", { left: 858, top: 173, width: 302, height: 30 }, { fontSize: 20, bold: true, color: C.redDark });
  addText(slide, "A clear, current ID for the owner or authorized enrollee.", { left: 858, top: 214, width: 298, height: 50 }, { fontSize: 20, color: C.muted });
  addShape(slide, "roundRect", { left: 828, top: 304, width: 362, height: 156 }, C.paleGreen, { borderRadius: "rounded-2xl" });
  addText(slide, "BUSINESS REGISTRATION", { left: 858, top: 334, width: 302, height: 30 }, { fontSize: 20, bold: true, color: C.green });
  addText(slide, "DTI, SEC, or CDA registration certificate, as applicable.", { left: 858, top: 375, width: 298, height: 64 }, { fontSize: 20, color: C.muted });
  addShape(slide, "roundRect", { left: 828, top: 486, width: 362, height: 146 }, C.warm, { borderRadius: "rounded-2xl" });
  addText(slide, "WHEN REPRESENTING THE OWNER", { left: 858, top: 512, width: 302, height: 30 }, { fontSize: 17, bold: true, color: C.ink });
  addText(slide, "Include an authorization letter, secretary's certificate, board resolution, or SPA.", { left: 858, top: 550, width: 298, height: 74 }, { fontSize: 18, color: C.muted });
  addPageMark(slide, 7);
  slide.speakerNotes.textFrame.setText("Source: resources/views/agent/help.blade.php and resources/views/agent/dashboard.blade.php. Required standard documents are a valid government-issued ID and a DTI, SEC, or CDA business registration certificate. Representatives also need an authorization document.");
}

// Slide 8: Portal tools
{
  const slide = presentation.slides.add();
  slide.background.fill = C.warm;
  addSectionTitle(slide, "Your working space", "The Agent Dashboard keeps progress visible.", "Four focused areas help you move from enrollment to commission tracking.", { width: 1136, titleSize: 45 });
  addShape(slide, "ellipse", { left: 470, top: 246, width: 340, height: 340 }, C.ink);
  addText(slide, "AGENT\nDASHBOARD", { left: 524, top: 352, width: 232, height: 104 }, {
    fontSize: 35, bold: true, color: C.white, alignment: "center", lineSpacing: 0.9,
  });
  const tools = [
    ["DASHBOARD", "At-a-glance restaurants, orders, sales, and commission.", 72, 224, C.red],
    ["RESTAURANTS", "Enrollment status, files, corrections, and restaurant details.", 66, 462, C.amber],
    ["REPORTS", "Order references, rates, commission amounts, and status.", 864, 224, C.green],
    ["HELP & FAQ", "Practical guidance plus direct support by email.", 864, 462, C.ink],
  ];
  tools.forEach(([heading, body, x, y, color]) => {
    addText(slide, heading, { left: x, top: y, width: 310, height: 34 }, { fontSize: 22, bold: true, color });
    addText(slide, body, { left: x, top: y + 42, width: 320, height: 78 }, { fontSize: 20, color: C.muted, lineSpacing: 1.06 });
    addLine(slide, x, y + 125, 300, 0, color, 3);
  });
  addPageMark(slide, 8);
  slide.speakerNotes.textFrame.setText("Source: resources/views/agent/layouts/app.blade.php, resources/views/agent/dashboard.blade.php, resources/views/agent/reports/index.blade.php, and resources/views/agent/help.blade.php.");
}

// Slide 9: Agent playbook
{
  const slide = presentation.slides.add();
  slide.background.fill = C.ink;
  addSectionTitle(slide, "Agent playbook", "The best agents make the process easier for restaurant owners.", null, {
    titleColor: C.white, kickerColor: "#FF8A84", width: 920, titleHeight: 82,
  });
  const habits = [
    ["01", "Set clear expectations", "Approval comes first. Earnings begin only after qualifying delivered orders."],
    ["02", "Check every detail", "Use accurate business, contact, address, and payout account information."],
    ["03", "Follow the review", "Watch document statuses and help owners respond to admin remarks."],
    ["04", "Use the report", "Match questions to the restaurant, order number, date, and expected amount."],
  ];
  habits.forEach((h, i) => {
    const y = 214 + i * 104;
    addText(slide, h[0], { left: 76, top: y, width: 48, height: 26 }, { fontSize: 15, bold: true, color: "#FF8A84" });
    addText(slide, h[1], { left: 144, top: y - 2, width: 350, height: 34 }, { fontSize: 24, bold: true, color: C.white });
    addText(slide, h[2], { left: 506, top: y, width: 500, height: 66 }, { fontSize: 20, color: "#C6CBCE", lineSpacing: 1.06 });
    if (i < habits.length - 1) addLine(slide, 76, y + 82, 936, 0, "#454B4E", 1);
  });
  const partnerImgs = ["ken-cakeshoppe.jpg", "kuya-jans-kitchen.jpg", "sandoks.jpg"];
  for (let i = 0; i < partnerImgs.length; i++) {
    addShape(slide, "ellipse", { left: 1014 + i * 64, top: 504 + i * 18, width: 126, height: 126 }, C.white, { shadow: "shadow-lg" });
    await addImage(slide, asset("images", "partners", partnerImgs[i]), { left: 1021 + i * 64, top: 511 + i * 18, width: 112, height: 112 }, {
      fit: "cover", geometry: "ellipse", alt: `Pahatud partner restaurant ${i + 1}`,
    });
  }
  addPageMark(slide, 9, true);
  slide.speakerNotes.textFrame.setText("Source: resources/views/agent/help.blade.php. Restaurant images are existing Pahatud partner assets from public/images/partners.");
}

// Slide 10: Call to action
{
  const slide = presentation.slides.add();
  slide.background.fill = C.white;
  addShape(slide, "rect", { left: 0, top: 0, width: 744, height: 720 }, C.red);
  addShape(slide, "roundRect", { left: 68, top: 50, width: 84, height: 84 }, C.white, { borderRadius: "rounded-xl" });
  await addImage(slide, path.join(TMP_DIR, "pahatud-new-logo.svg"), { left: 76, top: 58, width: 68, height: 68 }, {
    fit: "contain", alt: "PahatudFood logo",
  });
  addText(slide, "READY TO\nBECOME A\nPAHATUD AGENT?", { left: 76, top: 174, width: 580, height: 224 }, {
    fontSize: 58, bold: true, color: C.white, lineSpacing: 0.9,
  });
  addText(slide, "Apply. Get approved. Start building your restaurant network.", { left: 80, top: 448, width: 530, height: 82 }, {
    fontSize: 24, color: C.white, lineSpacing: 1.1,
  });
  addPill(slide, "pahatud.com/agent/register", 80, 574, 328, C.white, C.red);
  addText(slide, "Agent support: info@pahatud.com", { left: 80, top: 630, width: 380, height: 30 }, { fontSize: 18, bold: true, color: C.white });

  addShape(slide, "ellipse", { left: 826, top: 68, width: 330, height: 330 }, C.blush);
  await addImage(slide, asset("images", "app-preview", "home.png"), { left: 870, top: 82, width: 242, height: 500 }, {
    fit: "contain", geometry: "roundRect", borderRadius: "rounded-2xl", alt: "Pahatud mobile marketplace home screen",
  });
  addText(slide, "Help more local restaurants become discoverable, order-ready, and connected to delivery.", { left: 788, top: 590, width: 406, height: 74 }, {
    fontSize: 20, bold: true, color: C.ink, alignment: "center", lineSpacing: 1.05,
  });
  addPageMark(slide, 10);
  slide.speakerNotes.textFrame.setText("Call to action. Agent application route: /agent/register. Support email: info@pahatud.com.");
}

await fs.mkdir(TMP_DIR, { recursive: true });
await fs.mkdir(path.dirname(FINAL_PPTX), { recursive: true });

const stagingDir = path.join(workspaceDir, ".codex-finalizer");
await fs.mkdir(stagingDir, { recursive: true });
const candidatePath = path.join(stagingDir, "pahatud-agent-candidate.pptx");
await (await PresentationFile.exportPptx(presentation)).save(candidatePath);

const montage = await presentation.export({ format: "webp", montage: true, scale: 1 });
await fs.writeFile(path.join(TMP_DIR, "pahatud-agent-montage.webp"), new Uint8Array(await montage.arrayBuffer()));

for (let i = 0; i < presentation.slides.items.length; i++) {
  const preview = await presentation.export({ slide: presentation.slides.items[i], format: "png", scale: 1 });
  await fs.writeFile(path.join(TMP_DIR, `slide-${String(i + 1).padStart(2, "0")}.png`), new Uint8Array(await preview.arrayBuffer()));
}

const result = await finalizePresentation({
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
  ],
  explicitTotalSlideCount: 10,
  requiredNativeTableOwnerSlides: [],
  requiredNativeChartOwnerSlides: [],
  fontPolicy: { basis: "design", families: [HEAD_FONT, BODY_FONT] },
  verifyArtifactToolImport: true,
  receiptPath: path.join(stagingDir, "Pahatud_Agent_Program_New_Brand_2026-09-21_v3.validation.json"),
});

console.log(JSON.stringify({ final: FINAL_PPTX, candidate: candidatePath, result }, null, 2));
