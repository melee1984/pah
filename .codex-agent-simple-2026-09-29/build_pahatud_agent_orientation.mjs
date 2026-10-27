import fs from "node:fs/promises";
import path from "node:path";
import { pathToFileURL } from "node:url";
import { Presentation, PresentationFile } from "@oai/artifact-tool";

const workspaceDir = "/Users/larryparba/web/pah";
const SKILL_DIR = "/Users/larryparba/.codex/plugins/cache/openai-primary-runtime/presentations/26.909.12148/skills/presentations";
const TMP_DIR = path.join(workspaceDir, ".codex-agent-simple-2026-09-29");
const FINAL_PPTX = path.join(workspaceDir, "artifacts", "Pahatud_Agent_Orientation_2026-09-29_v3.pptx");
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
  redDark: "#C92D2A",
  coral: "#FF6A61",
  blush: "#FFF0ED",
  cream: "#FFF8F1",
  warm: "#F4EFE8",
  ink: "#202426",
  muted: "#62696D",
  line: "#D9D7D3",
  green: "#188761",
  amber: "#E59A2F",
  white: "#FFFFFF",
};

const asset = (...segments) => path.join(workspaceDir, "public", ...segments);
const presentation = Presentation.create({ slideSize: { width: W, height: H } });

function addShape(slide, geometry, position, fill = "none", opts = {}) {
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

function addText(slide, text, position, opts = {}) {
  const shape = addShape(slide, "textbox", position, opts.fill ?? "none", {
    line: opts.line,
    borderRadius: opts.borderRadius,
    shadow: opts.shadow,
    name: opts.name,
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
    lineSpacing: opts.lineSpacing ?? 1.06,
    insets: opts.insets ?? { top: 0, right: 0, bottom: 0, left: 0 },
  };
  return shape;
}

function addLine(slide, left, top, width, height = 0, color = C.line, thickness = 2) {
  return addShape(slide, "line", { left, top, width, height }, "none", {
    line: { style: "solid", fill: color, width: thickness },
  });
}

async function addImage(slide, filePath, position, opts = {}) {
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

function addTitle(slide, title, subtitle = null, opts = {}) {
  const left = opts.left ?? 72;
  const top = opts.top ?? 50;
  const width = opts.width ?? 1136;
  addShape(slide, "rect", { left, top: top + 4, width: 8, height: 50 }, opts.accent ?? C.red);
  addText(slide, title, { left: left + 24, top, width: width - 24, height: opts.titleHeight ?? 66 }, {
    fontSize: opts.titleSize ?? 48,
    bold: true,
    color: opts.color ?? C.ink,
    lineSpacing: 0.96,
  });
  if (subtitle) {
    addText(slide, subtitle, { left: left + 24, top: top + (opts.subtitleTop ?? 76), width: width - 24, height: 56 }, {
      fontSize: opts.subtitleSize ?? 23,
      color: opts.subtitleColor ?? C.muted,
      lineSpacing: 1.08,
    });
  }
}

function addPage(slide, number, dark = false) {
  addText(slide, String(number).padStart(2, "0"), { left: 1170, top: 675, width: 40, height: 20 }, {
    fontSize: 14,
    bold: true,
    alignment: "right",
    color: dark ? "#FFFFFF99" : "#8D9295",
  });
}

function addStep(slide, number, heading, body, x, y, width, opts = {}) {
  addText(slide, String(number).padStart(2, "0"), { left: x, top: y, width: 54, height: 32 }, {
    fontSize: 20,
    bold: true,
    color: opts.numberColor ?? C.red,
  });
  addText(slide, heading, { left: x + 66, top: y - 3, width: width - 66, height: 36 }, {
    fontSize: opts.headingSize ?? 25,
    bold: true,
    color: opts.headingColor ?? C.ink,
  });
  addText(slide, body, { left: x + 66, top: y + 39, width: width - 66, height: opts.bodyHeight ?? 72 }, {
    fontSize: opts.bodySize ?? 21,
    color: opts.bodyColor ?? C.muted,
    lineSpacing: 1.08,
  });
}

// Slide 1: Cover
{
  const slide = presentation.slides.add();
  slide.background.fill = C.red;
  addShape(slide, "ellipse", { left: 748, top: -86, width: 630, height: 830 }, C.cream);
  addShape(slide, "roundRect", { left: 800, top: 62, width: 405, height: 594 }, C.white, {
    borderRadius: "rounded-3xl",
    shadow: "shadow-lg",
  });
  await addImage(slide, asset("images", "banner-right.png"), { left: 824, top: 86, width: 357, height: 544 }, {
    fit: "contain",
    alt: "Food from restaurants in the Pahatud marketplace",
  });
  addShape(slide, "roundRect", { left: 72, top: 52, width: 74, height: 74 }, C.white, { borderRadius: "rounded-xl" });
  await addImage(slide, asset("images", "logo.jpg"), { left: 79, top: 59, width: 60, height: 60 }, {
    fit: "cover",
    alt: "Pahatud logo",
  });
  addText(slide, "PAHATUD AGENT PROGRAM", { left: 164, top: 70, width: 410, height: 32 }, {
    fontSize: 19,
    bold: true,
    color: C.white,
  });
  addText(slide, "Becoming a\nPahatud Agent", { left: 74, top: 190, width: 630, height: 180 }, {
    fontSize: 68,
    bold: true,
    color: C.white,
    lineSpacing: 0.92,
  });
  addText(slide, "How the role works, what you earn, and how to get started.", { left: 78, top: 408, width: 560, height: 86 }, {
    fontSize: 27,
    color: C.white,
    lineSpacing: 1.12,
  });
  addText(slide, "SIMPLE AGENT ORIENTATION", { left: 78, top: 588, width: 318, height: 42 }, {
    fill: C.white,
    color: C.redDark,
    fontSize: 17,
    bold: true,
    alignment: "center",
    verticalAlignment: "middle",
    borderRadius: "rounded-full",
    insets: { top: 3, right: 12, bottom: 3, left: 12 },
  });
  slide.speakerNotes.textFrame.setText(
    "Program overview based on resources/views/agent/auth/register.blade.php and resources/views/agent/help.blade.php. Food artwork and logo are existing Pahatud assets from public/images.",
  );
}

// Slide 2: Role
{
  const slide = presentation.slides.add();
  slide.background.fill = C.white;
  addTitle(slide, "The Pahatud agent role", "You connect local restaurants to Pahatud and help them complete onboarding.", { width: 710 });
  addStep(slide, 1, "Introduce", "Find restaurants that can benefit from online ordering and delivery.", 76, 238, 610);
  addStep(slide, 2, "Guide", "Help owners submit restaurant details, documents, and corrections.", 76, 368, 610, { numberColor: C.amber });
  addStep(slide, 3, "Follow progress", "Track approval and the qualifying orders that create commission.", 76, 498, 610, { numberColor: C.green });
  addShape(slide, "roundRect", { left: 775, top: 50, width: 420, height: 620 }, C.cream, { borderRadius: "rounded-3xl" });
  await addImage(slide, asset("images", "partner-with-us.jpg"), { left: 810, top: 86, width: 350, height: 548 }, {
    fit: "contain",
    alt: "Pahatud partnership illustration",
  });
  addPage(slide, 2);
  slide.speakerNotes.textFrame.setText(
    "Source: resources/views/agent/auth/register.blade.php, sections 'How the Agent Program works' and 'What you get as a Pahatud agent'. Illustration: public/images/partner-with-us.jpg.",
  );
}

// Slide 3: Benefits
{
  const slide = presentation.slides.add();
  slide.background.fill = C.blush;
  addTitle(slide, "Benefits of being an agent", "The program gives you a clear way to grow a restaurant network and see its results.", { width: 1130 });
  addShape(slide, "roundRect", { left: 72, top: 182, width: 344, height: 472 }, C.white, {
    borderRadius: "rounded-3xl",
    shadow: "shadow-md",
  });
  await addImage(slide, asset("images", "app-preview", "restaurants.png"), { left: 148, top: 202, width: 192, height: 432 }, {
    fit: "contain",
    alt: "Restaurants shown in the Pahatud marketplace",
  });
  const benefits = [
    ["Earn from qualifying orders", "Your share starts at 20% of Pahatud's commission and can reach 30% as your approved restaurant network grows."],
    ["Build your own restaurant network", "Enroll restaurants from the portal and monitor each application's status."],
    ["See your results", "Track orders, completed order value, total commission, and monthly commission."],
    ["Support local businesses", "Help restaurants join the marketplace and connect with delivery customers."],
  ];
  benefits.forEach(([heading, body], index) => {
    const x = index % 2 === 0 ? 490 : 865;
    const y = index < 2 ? 208 : 438;
    addText(slide, String(index + 1).padStart(2, "0"), { left: x, top: y, width: 44, height: 26 }, {
      fontSize: 16,
      bold: true,
      color: index === 2 ? C.green : C.red,
    });
    addText(slide, heading, { left: x, top: y + 36, width: 310, height: 62 }, {
      fontSize: 26,
      bold: true,
      lineSpacing: 1,
    });
    addText(slide, body, { left: x, top: y + 106, width: 315, height: 105 }, {
      fontSize: 20,
      color: C.muted,
      lineSpacing: 1.08,
    });
  });
  addPage(slide, 3);
  slide.speakerNotes.textFrame.setText(
    "Sources: resources/views/agent/auth/register.blade.php and resources/views/agent/dashboard.blade.php. Marketplace screenshot: public/images/app-preview/restaurants.png. Benefits describe portal features and commission opportunities, not guaranteed income.",
  );
}

// Slide 4: Application
{
  const slide = presentation.slides.add();
  slide.background.fill = C.white;
  addTitle(slide, "Agent application process", "Registration is simple, but Pahatud approval is required before portal access.");
  const steps = [
    ["01", "Open the registration page", "Choose the Agent Program registration page on the Pahatud website."],
    ["02", "Enter your details", "Provide your full name, email, mobile number, and a password of at least eight characters."],
    ["03", "Submit and check email", "Accept the program terms and look for the registration confirmation."],
    ["04", "Wait for the decision", "Pahatud emails the approval or decline decision. Approved agents can sign in."],
  ];
  steps.forEach(([number, heading, body], index) => {
    const x = 72 + index * 296;
    addText(slide, number, { left: x, top: 236, width: 88, height: 72 }, {
      fontSize: 54,
      bold: true,
      color: index === 3 ? C.white : C.red,
      fill: index === 3 ? C.red : C.blush,
      alignment: "center",
      verticalAlignment: "middle",
      borderRadius: "rounded-2xl",
    });
    addText(slide, heading, { left: x, top: 332, width: 246, height: 68 }, {
      fontSize: 25,
      bold: true,
      lineSpacing: 1,
    });
    addText(slide, body, { left: x, top: 416, width: 246, height: 128 }, {
      fontSize: 20,
      color: C.muted,
      lineSpacing: 1.1,
    });
  });
  addShape(slide, "roundRect", { left: 72, top: 610, width: 1136, height: 58 }, C.ink, { borderRadius: "rounded-xl" });
  addText(slide, "No application fee. Account access begins after approval.", { left: 98, top: 624, width: 1084, height: 31 }, {
    fontSize: 20,
    bold: true,
    color: C.white,
    alignment: "center",
    verticalAlignment: "middle",
  });
  addPage(slide, 4);
  slide.speakerNotes.textFrame.setText(
    "Sources: app/Http/Controllers/Agent/RegistrationController.php and resources/views/agent/auth/register.blade.php. Required registration fields are name, email, mobile, password, password confirmation, and accepted terms. No application fee is stated on the registration page.",
  );
}

// Slide 5: Restaurant enrollment
{
  const slide = presentation.slides.add();
  slide.background.fill = C.cream;
  addTitle(slide, "Restaurant enrollment and approval", "After your account is approved, your main job is helping restaurants complete the review.");
  addText(slide, "What you submit", { left: 76, top: 205, width: 310, height: 42 }, { fontSize: 31, bold: true });
  addStep(slide, 1, "Business information", "Restaurant name, contacts, address, city, business structure, TIN, and payout details.", 76, 278, 520, { headingSize: 24, bodyHeight: 78 });
  addStep(slide, 2, "Required documents", "A valid government ID and the correct DTI, SEC, or CDA registration certificate.", 76, 408, 520, { headingSize: 24, bodyHeight: 78, numberColor: C.amber });
  addStep(slide, 3, "Authorization when needed", "A representative must include an authorization letter, secretary's certificate, board resolution, or SPA.", 76, 538, 520, { headingSize: 24, bodyHeight: 82, numberColor: C.green });
  addShape(slide, "roundRect", { left: 674, top: 195, width: 532, height: 434 }, C.white, {
    borderRadius: "rounded-3xl",
    shadow: "shadow-md",
  });
  addText(slide, "Approval rule", { left: 714, top: 236, width: 300, height: 42 }, { fontSize: 30, bold: true, color: C.red });
  addText(slide, "A restaurant counts toward your commission tier only after Pahatud gives final approval.", { left: 714, top: 296, width: 438, height: 100 }, {
    fontSize: 28,
    bold: true,
    lineSpacing: 1.08,
  });
  addText(slide, "Missing files may be uploaded later. Approval waits until all required information and documents are complete, approved, and current.", { left: 714, top: 426, width: 438, height: 110 }, {
    fontSize: 21,
    color: C.muted,
    lineSpacing: 1.1,
  });
  const partnerImages = ["ken-cakeshoppe.jpg", "kuya-jans-kitchen.jpg", "sandoks.jpg"];
  for (let i = 0; i < partnerImages.length; i++) {
    addShape(slide, "ellipse", { left: 738 + i * 116, top: 548, width: 90, height: 90 }, C.blush);
    await addImage(slide, asset("images", "partners", partnerImages[i]), { left: 744 + i * 116, top: 554, width: 78, height: 78 }, {
      fit: "cover",
      geometry: "ellipse",
      alt: "Pahatud restaurant partner logo",
    });
  }
  addPage(slide, 5);
  slide.speakerNotes.textFrame.setText(
    "Sources: resources/views/agent/help.blade.php and app/Http/Controllers/Agent/RestaurantController.php. Restaurant logos are existing Pahatud partner assets in public/images/partners.",
  );
}

// Slide 6: Commission tiers based on the supplied portal reference
{
  const slide = presentation.slides.add();
  slide.background.fill = "#FCFAF8";
  addTitle(slide, "Commission tiers", null, { titleSize: 44, top: 35 });
  addText(slide, "Your current position", { left: 76, top: 105, width: 360, height: 34 }, {
    fontSize: 22,
    bold: true,
  });
  addText(slide, "You currently have 3 approved restaurants and receive 20.00% of Pahatud’s commission on new qualifying orders.", { left: 76, top: 143, width: 1080, height: 34 }, {
    fontSize: 17,
    color: C.muted,
  });

  const tiers = [
    {
      range: "0–34",
      rate: "20.00%",
      earnings: "₱40.00",
      current: true,
    },
    {
      range: "35–49",
      rate: "25.00%",
      earnings: "₱50.00",
      current: false,
    },
    {
      range: "50 or more",
      rate: "30.00%",
      earnings: "₱60.00",
      current: false,
    },
  ];

  tiers.forEach((tier, index) => {
    const x = 72 + index * 380;
    const fill = tier.current ? "#EF302D" : C.white;
    const primary = tier.current ? C.white : C.ink;
    const secondary = tier.current ? "#FFFFFFD9" : C.muted;
    addShape(slide, "roundRect", { left: x, top: 188, width: 360, height: 246 }, fill, {
      borderRadius: "rounded-2xl",
      line: tier.current ? { style: "solid", fill: "none", width: 0 } : { style: "solid", fill: "#E0DBD5", width: 1 },
      shadow: tier.current ? "shadow-md" : undefined,
    });
    addText(slide, "APPROVED RESTAURANTS", { left: x + 24, top: 214, width: 208, height: 20 }, {
      fontSize: 13,
      bold: true,
      color: secondary,
    });
    if (tier.current) {
      addText(slide, "YOUR CURRENT TIER", { left: x + 224, top: 207, width: 116, height: 28 }, {
        fontSize: 11,
        bold: true,
        color: C.white,
        alignment: "center",
        verticalAlignment: "middle",
        borderRadius: "rounded-full",
        line: { style: "solid", fill: "#FFFFFF99", width: 1 },
      });
    }
    addText(slide, tier.range, { left: x + 24, top: 247, width: 280, height: 40 }, {
      fontSize: 30,
      bold: true,
      color: primary,
    });
    addText(slide, tier.rate, { left: x + 24, top: 300, width: 166, height: 56 }, {
      fontSize: 40,
      bold: true,
      color: tier.current ? C.white : C.red,
    });
    addText(slide, "of Pahatud’s commission", { left: x + 183, top: 326, width: 155, height: 24 }, {
      fontSize: 14,
      color: secondary,
    });
    addLine(slide, x + 24, 365, 312, 0, tier.current ? "#FFFFFF66" : "#E3DED8", 1);
    addText(slide, "On an example ₱1,000.00 eligible subtotal at a 20.00% Pahatud rate, the agent earns", { left: x + 24, top: 382, width: 310, height: 42 }, {
      fontSize: 14,
      color: secondary,
      lineSpacing: 1.06,
    });
    addText(slide, tier.earnings, { left: x + 258, top: 408, width: 78, height: 20 }, {
      fontSize: 14,
      bold: true,
      color: tier.current ? C.white : C.green,
      alignment: "right",
    });
  });

  const rules = [
    ["1", "Approval determines the count", "A restaurant counts only while its application status is Approved. Pending, under-review, declined, or expired-document applications do not count."],
    ["2", "The rate is checked when an order qualifies", "When an assigned restaurant’s eligible order is delivered, the system checks the agent’s approved-restaurant total and selects the active tier."],
    ["3", "The tier covers the whole network", "After a threshold is reached, that rate applies to future qualifying orders from every restaurant assigned to the agent, including restaurants enrolled earlier."],
    ["4", "Recorded commissions stay unchanged", "Each commission entry saves the rate used for that order. Moving to a higher or lower tier never recalculates an existing entry."],
  ];
  rules.forEach(([number, heading, body], index) => {
    const x = index % 2 === 0 ? 72 : 650;
    const y = index < 2 ? 456 : 572;
    addShape(slide, "roundRect", { left: x, top: y, width: 558, height: 100 }, C.white, {
      borderRadius: "rounded-xl",
      line: { style: "solid", fill: "#E0DBD5", width: 1 },
    });
    addText(slide, number, { left: x + 16, top: y + 24, width: 36, height: 36 }, {
      fontSize: 15,
      bold: true,
      color: C.red,
      fill: C.blush,
      alignment: "center",
      verticalAlignment: "middle",
      borderRadius: "rounded-lg",
    });
    addText(slide, heading, { left: x + 66, top: y + 17, width: 466, height: 25 }, {
      fontSize: 17,
      bold: true,
    });
    addText(slide, body, { left: x + 66, top: y + 48, width: 466, height: 44 }, {
      fontSize: 13,
      color: C.muted,
      lineSpacing: 1.04,
    });
  });
  addPage(slide, 6);
  slide.speakerNotes.textFrame.setText(
    "Slide recreated from the user-supplied screenshot of the Pahatud commission tier guide. Supporting sources: resources/views/agent/partials/commission-tier-guide.blade.php, resources/views/agent/help.blade.php, config/agent.php, and app/Services/AgentCommissionService.php.",
  );
}

// Slide 7: Dashboard
{
  const slide = presentation.slides.add();
  slide.background.fill = C.white;
  addTitle(slide, "What the Agent Dashboard tracks", "Your portal connects restaurant progress, order activity, and commission reporting.", { width: 748, titleSize: 44 });
  const metrics = [
    ["Approved restaurants", "Partners currently counted toward your tier"],
    ["Current commission share", "The tier rate used for new qualifying orders"],
    ["Orders generated", "Orders connected to your restaurant network"],
    ["Completed order value", "Delivered order value recorded by the platform"],
    ["Total commission", "Earned commission across qualifying orders"],
    ["This month's commission", "Commission activity for the current month"],
  ];
  metrics.forEach(([label, description], index) => {
    const col = index % 2;
    const row = Math.floor(index / 2);
    const x = 72 + col * 330;
    const y = 205 + row * 145;
    addText(slide, label, { left: x, top: y, width: 286, height: 56 }, {
      fontSize: 24,
      bold: true,
      color: index === 5 ? C.red : C.ink,
      lineSpacing: 1,
    });
    addText(slide, description, { left: x, top: y + 64, width: 292, height: 58 }, {
      fontSize: 18,
      color: C.muted,
      lineSpacing: 1.08,
    });
  });
  addShape(slide, "roundRect", { left: 825, top: 72, width: 383, height: 580 }, C.blush, { borderRadius: "rounded-3xl" });
  await addImage(slide, asset("images", "app-preview", "home.png"), { left: 915, top: 104, width: 202, height: 445 }, {
    fit: "contain",
    alt: "Pahatud customer marketplace home screen",
  });
  addText(slide, "Reports show pending, approved, paid, and reversed entries. Pahatud operations coordinates approved payouts.", { left: 850, top: 566, width: 333, height: 72 }, {
    fontSize: 16,
    bold: true,
    color: C.redDark,
    alignment: "center",
    lineSpacing: 1.04,
  });
  addPage(slide, 7);
  slide.speakerNotes.textFrame.setText(
    "Sources: resources/views/agent/dashboard.blade.php, resources/views/agent/reports/index.blade.php, and resources/views/agent/help.blade.php. Marketplace screenshot: public/images/app-preview/home.png. The current portal tracks payout status but does not offer self-service withdrawals.",
  );
}

// Slide 8: Closing
{
  const slide = presentation.slides.add();
  slide.background.fill = C.red;
  addShape(slide, "ellipse", { left: 716, top: -28, width: 660, height: 776 }, C.cream);
  addShape(slide, "roundRect", { left: 72, top: 54, width: 74, height: 74 }, C.white, { borderRadius: "rounded-xl" });
  await addImage(slide, asset("images", "logo.jpg"), { left: 79, top: 61, width: 60, height: 60 }, { fit: "cover", alt: "Pahatud logo" });
  addText(slide, "Your first actions", { left: 74, top: 176, width: 570, height: 74 }, { fontSize: 58, bold: true, color: C.white });
  const actions = [
    "Open the Pahatud Agent Program registration page.",
    "Submit your details and wait for approval.",
    "Sign in and enroll your first restaurant.",
    "Check restaurant status and commission reports regularly.",
  ];
  actions.forEach((action, index) => {
    addText(slide, String(index + 1), { left: 80, top: 288 + index * 66, width: 36, height: 34 }, {
      fontSize: 20,
      bold: true,
      color: C.red,
      fill: C.white,
      alignment: "center",
      verticalAlignment: "middle",
      borderRadius: "rounded-full",
    });
    addText(slide, action, { left: 136, top: 290 + index * 66, width: 520, height: 42 }, {
      fontSize: 22,
      color: C.white,
      lineSpacing: 1.05,
    });
  });
  addText(slide, "Agent support", { left: 78, top: 586, width: 140, height: 24 }, { fontSize: 16, bold: true, color: C.white });
  addText(slide, "info@pahatud.com", { left: 78, top: 617, width: 330, height: 38 }, { fontSize: 25, bold: true, color: C.white });
  await addImage(slide, asset("images", "motor.png"), { left: 790, top: 176, width: 434, height: 360 }, {
    fit: "contain",
    alt: "Pahatud delivery rider illustration",
  });
  addText(slide, "Approval comes first. Earnings begin only after qualifying delivered orders from approved restaurants.", { left: 792, top: 548, width: 420, height: 86 }, {
    fontSize: 22,
    bold: true,
    color: C.ink,
    alignment: "center",
    lineSpacing: 1.08,
  });
  addPage(slide, 8, true);
  slide.speakerNotes.textFrame.setText(
    "Sources: routes/web.php, resources/views/agent/auth/register.blade.php, and resources/views/agent/help.blade.php. Support email: info@pahatud.com. Illustration: public/images/motor.png.",
  );
}

await fs.mkdir(TMP_DIR, { recursive: true });
await fs.mkdir(path.dirname(FINAL_PPTX), { recursive: true });

const candidatePath = path.join(TMP_DIR, "candidate.pptx");
await (await PresentationFile.exportPptx(presentation)).save(candidatePath);

const montage = await presentation.export({ format: "webp", montage: true, scale: 1 });
await fs.writeFile(path.join(TMP_DIR, "draft-montage.webp"), new Uint8Array(await montage.arrayBuffer()));

for (let i = 0; i < presentation.slides.items.length; i++) {
  const preview = await presentation.export({ slide: presentation.slides.items[i], format: "png", scale: 1 });
  await fs.writeFile(
    path.join(TMP_DIR, `draft-slide-${String(i + 1).padStart(2, "0")}.png`),
    new Uint8Array(await preview.arrayBuffer()),
  );
}

const stagingDir = path.join(workspaceDir, ".codex-finalizer");
await fs.mkdir(stagingDir, { recursive: true });

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
  explicitTotalSlideCount: 8,
  requiredNativeTableOwnerSlides: [],
  requiredNativeChartOwnerSlides: [],
  fontPolicy: { basis: "design", families: [HEAD_FONT, BODY_FONT] },
  verifyArtifactToolImport: true,
  receiptPath: path.join(stagingDir, "Pahatud_Agent_Orientation_2026-09-29_v3.validation.json"),
});

console.log(JSON.stringify({ final: FINAL_PPTX, candidate: candidatePath, result }, null, 2));
