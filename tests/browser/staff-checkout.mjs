// Run with: node tests/browser/staff-checkout.mjs (requires Playwright + Chromium).
// Uses real Vue components with isolated HTTP fixtures; never touches the live database.
import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
import {createServer} from 'vite';
import vue from '@vitejs/plugin-vue2';
const {chromium} = createRequire(import.meta.url)('playwright');
const totals = {sub_total: 22050, total: 22050, delivery_fee: 0, convenience_fee: 0, vat_amount: 0, discount: 0};
const methods = [{id: 2, title: 'GCash'}, {id: 4, title: 'Cash', is_cash: false}]; // Regression: separate Cash record with the old incorrect flag.
const order = {id: 19, number: '19', bill_id: 19, table_id: 1, table_name: 'Table 1', round: 1, type: 'dine_in', status: 'preparing', status_id: 3, paid: false, total: 22050, created: new Date().toISOString(), notes: 'No onions', items: [{qty: 1, name: 'Lunch plate', unit_price: 22050, variants: '', note: ''}]};
const bill = {id: 19, table_id: 1, table_name: 'Table 1', order_ids: [19], orders: [order], totals, payment_method_id: 2, payment_method: 'GCash', paid: false};
const abilities = {cashier: true, pos: true, pay: true, serve: true, prepare: true, tables: true};
const initial = {abilities, orders: [order], bills: [bill], payments: [], fulfillment_types: [{type: 'dine_in'}, {type: 'pickup'}], tables: [{id: 1, name: 'Table 1', active: true, is_available: true, capacity: 4}], payment_methods: methods, updated_at: new Date().toISOString(), menu: [{id: 1, name: 'Lunch plate', category: 'Meals', category_id: 1, price: 22050, variants: [], image: null}]};
const config = {initial, abilities, view: 'tables', user: {name: 'Cashier'}, merchant: 'PahatudFood', branch: {id: 1, name: 'Main'}, branches: [], base: '/merchant/staff', endpoint: '/merchant/staff/data', logout: '/logout'};
const server = await createServer({configFile: false, plugins: [vue()], server: {host: '127.0.0.1', port: 0}, appType: 'custom'});
server.middlewares.use('/__checkout_test', (_req, res) => {
  res.setHeader('Content-Type', 'text/html');
  res.end(`<html><head><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="fixture"><link rel="stylesheet" href="/css/merchant-staff.css"><link rel="stylesheet" href="/css/merchant-pos.css"></head><body class="staff-page"><div id="app"></div><script type="module">import Vue from '/node_modules/vue/dist/vue.esm.js';import Workspace from '/resources/js/components/merchant/pages/staff/StaffWorkspace.vue';const config=${JSON.stringify(config)};config.view=new URLSearchParams(location.search).get('view')||'tables';window.workspace=new Vue({render:h=>h(Workspace,{props:{config}})}).$mount('#app').$children[0];</script></body></html>`);
});
await server.listen();
const browser = await chromium.launch({headless: true, ...(process.env.CHROME_PATH ? {executablePath: process.env.CHROME_PATH} : {})});
const page = await browser.newPage({viewport: {width: 1440, height: 1000}});
const errors = []; page.on('pageerror', error => errors.push(error.message));
let paymentRequests = [], failPayment = false, lostResponse = false, receipt = structuredClone(bill), creates = 0;
await page.route('**/merchant/staff/**', async route => {
  const url = new URL(route.request().url()), data = route.request().postDataJSON();
  let body;
  if (url.pathname.endsWith('/payment')) {
    paymentRequests.push(data);
    if (failPayment) return route.fulfill({status: 503, json: {message: 'Payment failed. Please retry.'}});
    receipt.paid = true; receipt.payment = {method: Number(data.payment_method_id) === 4 ? 'Cash' : 'GCash', cash_tendered: data.cash_tendered_cents, change: data.cash_tendered_cents === null ? null : data.cash_tendered_cents - totals.total};
    if (lostResponse) return route.abort();
    // Keep the request pending long enough to exercise duplicate-submit guards.
    await new Promise(resolve => setTimeout(resolve, 200));
    body = {message: 'Payment recorded.', payment_id: 1};
  } else if (url.pathname.endsWith('/receipt')) body = receipt;
  else if (url.pathname.endsWith('/quote')) body = {totals};
  else if (url.pathname.endsWith('/orders')) { creates++; body = {id: 19, number: '19', bill_id: 19}; }
  else body = initial;
  await route.fulfill({json: body});
});
const base = `http://127.0.0.1:${server.httpServer.address().port}/__checkout_test`;
const checkout = () => page.locator('.staff-checkout');
const cash = () => checkout().getByLabel('Amount Received (₱)');
const confirm = () => checkout().getByRole('button', {name: 'Confirm Payment', exact: true});
const selectCash = async () => { await checkout().locator('.pos-payment-card').filter({hasText: 'Cash'}).filter({has: page.locator('input[value="4"]')}).click(); await cash().waitFor({state: 'visible'}); assert.equal(await cash().evaluate(el => el === document.activeElement), true); };
try {
  await page.goto(base);
  await page.getByRole('button', {name: 'Proceed to Payment'}).click();
  await selectCash();
  assert.equal(await cash().getAttribute('inputmode'), 'decimal');
  assert.equal(await confirm().isDisabled(), true);
  await cash().fill('220'); assert.equal(await confirm().isDisabled(), true);
  await cash().fill('220.50'); assert.equal(await confirm().isEnabled(), true);
  await cash().fill('300'); assert.match(await checkout().locator('.checkout-change').innerText(), /79.50/);
  await checkout().getByText('GCash', {exact: true}).click();
  assert.equal(await cash().count(), 0);
  await selectCash(); assert.equal(await cash().inputValue(), '');
  assert.equal(await checkout().getByLabel('Payment reference (optional)').count(), 0);
  await checkout().getByRole('button', {name: 'Back to Order'}).click();
  assert.equal(await page.evaluate(() => window.workspace.selected), 1);
  await page.getByRole('button', {name: 'Proceed to Payment'}).click(); await selectCash(); await cash().fill('300');
  failPayment = true; await confirm().click(); await checkout().getByRole('alert').waitFor();
  await page.waitForFunction(() => !document.querySelector('.checkout-confirm').disabled);
  const retryKey = paymentRequests.at(-1).idempotency_key;
  failPayment = false;
  await confirm().dblclick();
  await checkout().getByRole('heading', {name: 'Payment successful'}).waitFor();
  assert.equal(await checkout().getByRole('button', {name: 'New Order'}).isVisible(), true);
  assert.equal(paymentRequests.length, 2); assert.equal(paymentRequests[1].idempotency_key, retryKey);
  assert.equal(paymentRequests[1].cash_tendered_cents, 30000); assert.equal(paymentRequests[1].reference, null);
  assert.match(await checkout().innerText(), /Preparing/);
  assert.equal(creates, 0);
  console.log('PASS table checkout: separate Cash record detection, focus, amounts, switching, back, failure/retry, duplicate guard, preparing order');

  // POS integration: place an order through the real UI, then return without losing its state.
  receipt = structuredClone(bill); paymentRequests = [];
  await page.goto(base + '?view=pos');
  await page.getByRole('button', {name: /Dine in/}).click();
  await page.getByRole('button', {name: /Customize Lunch plate/}).click();
  await page.getByRole('button', {name: /Add to order/}).click();
  await page.getByRole('button', {name: /Table 1, /}).click();
  await page.getByLabel('Order notes').fill('No onions');
  await page.getByRole('button', {name: 'Review Order'}).click();
  assert.deepEqual(await page.evaluate(() => {const pos = window.workspace.$children.find(c => c.$options.name !== 'StaffCheckout' && 'lines' in c); return {reviewing: pos.reviewing, lines: pos.lines.length};}), {reviewing: true, lines: 1});
  assert.equal(creates, 0);
  await page.getByRole('button', {name: 'Back to add more items'}).click();
  assert.equal(await page.evaluate(() => window.workspace.$children.find(c => 'lines' in c).lines.length), 1);
  await page.getByRole('button', {name: 'Review Order'}).click();
  await page.getByRole('button', {name: 'Place Order'}).click();
  await page.getByRole('button', {name: 'Proceed to Payment'}).click();
  await cash().waitFor();
  await checkout().getByRole('button', {name: 'Back to Order'}).click();
  assert.deepEqual(await page.evaluate(() => {const pos = window.workspace.$children.find(c => c.$options.name !== 'StaffCheckout' && 'lines' in c); return {lines: pos.lines.length, table: pos.table, notes: pos.notes, bill: pos.submitted.bill_id};}), {lines: 1, table: 1, notes: 'No onions', bill: 19});
  await page.getByRole('button', {name: 'Proceed to Payment'}).click();
  await checkout().getByText('GCash', {exact: true}).click();
  lostResponse = true;
  await confirm().click();
  await checkout().getByRole('heading', {name: 'Payment successful'}).waitFor();
  assert.equal(await checkout().getByRole('button', {name: 'New Order'}).isVisible(), true);
  assert.equal(paymentRequests.length, 1); assert.equal(paymentRequests[0].cash_tendered_cents, null); assert.equal(paymentRequests[0].reference, null);
  assert.equal(creates, 1);
  console.log('PASS POS: order placement, preserved cart/table/notes, non-cash payment, lost-response reconciliation, existing bill');

  // A concurrent new round must be reviewed before another confirmation.
  receipt = structuredClone(bill); lostResponse = false; failPayment = true;
  await page.goto(base);
  await page.getByRole('button', {name: 'Proceed to Payment'}).click(); await selectCash(); await cash().fill('300');
  receipt.totals.total = 25050;
  await confirm().click();
  await checkout().getByText('The bill changed. Review the updated order and total before confirming again.').waitFor();
  assert.equal(await cash().inputValue(), ''); assert.equal(await confirm().isDisabled(), true);
  assert.match(await checkout().locator('.checkout-total').innerText(), /250.50/);
  console.log('PASS changed bill: updated total requires review and cash re-entry');

  receipt = structuredClone(bill); failPayment = false; lostResponse = false;
  await page.setViewportSize({width: 390, height: 844});
  await page.goto(base);
  await page.getByRole('button', {name: 'Proceed to Payment'}).click(); await selectCash(); await cash().fill('300');
  assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
  assert.ok((await confirm().boundingBox()).height >= 48);
  if (process.env.CHECKOUT_SCREENSHOT) await page.screenshot({path: process.env.CHECKOUT_SCREENSHOT, fullPage: true});
  assert.deepEqual(errors, []);
  console.log('PASS mobile: no horizontal overflow, touch-sized controls, cash focus; no browser errors');
} finally { await browser.close(); await server.close(); }
