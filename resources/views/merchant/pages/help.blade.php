@extends('merchant.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper">
  <section class="content-header admin-page-header"><div class="container-fluid">
    <div class="admin-page-heading"><div><span class="admin-eyebrow">Merchant support</span><h1>Help desk &amp; documentation</h1><p>Find instructions for your account, application, menu, orders, sales, and payouts.</p></div><a class="btn admin-btn-primary" href="mailto:info@pahatud.com?subject=Merchant%20support%20request"><i class="fas fa-envelope mr-2"></i>Contact support</a></div>
  </div></section>
  <section class="content"><div class="container-fluid merchant-help-page">
    <nav class="merchant-help-jump" aria-label="Help topics">
      <a href="#getting-started">Getting started</a><a href="#application">Application</a><a href="#products">Products</a><a href="#orders">Orders</a><a href="#sales">Sales &amp; payouts</a><a href="#support">Support</a>
    </nav>
    <div class="merchant-help-layout">
      <main class="merchant-help-main">
        <section class="merchant-help-section" id="getting-started">
          <div class="merchant-help-section-head"><span>01</span><div><h2>Getting started</h2><p>Set up the information your customers and Pahatud operations rely on.</p></div></div>
          <div class="merchant-help-questions">
            <details open><summary>What should I complete before opening my store?</summary><p>Review the restaurant name, contact information, address and location, operating status, categories, and menu. Confirm that prices and product availability are correct before switching the store online.</p></details>
            <details><summary>Where do I update restaurant information?</summary><p>Open <a href="{{ route('merchant.dashboard.settings') }}">Profile</a> to manage restaurant and contact details. Use <a href="{{ route('merchant.dashboard.location') }}">Branches</a> for branch addresses and contact numbers, and <a href="{{ route('merchant.dashboard.category') }}">Category</a> for menu organization.</p></details>
            <details><summary>What does the online switch do?</summary><p>The online status tells customers whether the store is accepting orders. Only switch the store online when someone is available to monitor incoming orders and prepare them within the expected time.</p></details>
          </div>
        </section>

        <section class="merchant-help-section" id="application">
          <div class="merchant-help-section-head"><span>02</span><div><h2>Application &amp; verification</h2><p>Understand the restaurant approval requirements and document statuses.</p></div></div>
          <div class="merchant-help-questions">
            <details open><summary>Which standard documents are required?</summary><p>A valid government-issued ID and a business registration certificate from DTI, SEC, or CDA, as applicable. If an authorized representative enrolled the restaurant, an authorization letter, secretary’s certificate, board resolution, or SPA is also required.</p></details>
            <details><summary>What do the document statuses mean?</summary><p><strong>Missing</strong> means no file is saved. <strong>Pending Verification</strong> means the file is waiting for review. <strong>Approved</strong> means it was accepted. <strong>Rejected</strong> means you should read the administrator’s remarks and upload a replacement. <strong>Expired</strong> means a current document is required.</p></details>
            <details><summary>How do I replace a rejected document?</summary><p>@if (Auth::User()->merchant?->agent_id)Open <a href="{{ route('merchant.application.show') }}">Application &amp; documents</a>, read the remarks beside the rejected file, and upload a clear PDF, JPG, or PNG replacement up to 10 MB.@else Contact Pahatud support and include your restaurant name so the team can review your account requirements.@endif</p></details>
            <details><summary>What payout information should I provide?</summary><p>Enter the account holder name, bank or e-wallet provider, and complete account details in the payout account field. Check the information carefully to avoid payout delays.</p></details>
          </div>
        </section>

        <section class="merchant-help-section" id="products">
          <div class="merchant-help-section-head"><span>03</span><div><h2>Products &amp; menu</h2><p>Keep the customer-facing menu accurate and easy to order from.</p></div></div>
          <div class="merchant-help-questions">
            <details open><summary>How do I manage menu items?</summary><p>Open <a href="{{ route('merchant.dashboard.product') }}">Products</a> to review and edit menu items. Use clear names and descriptions, correct prices, appropriate categories, and current availability.</p></details>
            <details><summary>When should I mark a product unavailable?</summary><p>Mark an item unavailable as soon as it cannot be prepared. This prevents customers from ordering sold-out items and reduces cancellations or substitutions.</p></details>
            <details><summary>How should add-ons and variants be used?</summary><p>Use add-ons and variants for genuine customer choices such as size, flavor, extras, or preparation options. Keep labels short and show every additional charge clearly.</p></details>
          </div>
        </section>

        <section class="merchant-help-section" id="orders">
          <div class="merchant-help-section-head"><span>04</span><div><h2>Orders &amp; delivery</h2><p>Handle each order accurately from acceptance through rider pickup.</p></div></div>
          <div class="merchant-help-questions">
            <details open><summary>What should I do when a new order arrives?</summary><p>Open <a href="{{ route('merchant.dashboard.orders') }}">Orders</a>, check every item and customer note, confirm that the order can be prepared, and respond promptly. Keep the order visible while it is being prepared.</p></details>
            <details><summary>When is an order ready for pickup?</summary><p>Mark it ready only after every item is complete, packed, and checked against the order. This helps the rider collect the correct package without unnecessary waiting.</p></details>
            <details><summary>What information should I include when reporting an order problem?</summary><p>Provide the order number, customer or rider issue, current order status, what action has already been taken, and screenshots or delivery proof when available. Do not share passwords or one-time codes.</p></details>
          </div>
        </section>

        <section class="merchant-help-section" id="sales">
          <div class="merchant-help-section-head"><span>05</span><div><h2>Sales &amp; payouts</h2><p>Read sales figures and keep payout information accurate.</p></div></div>
          <div class="merchant-help-questions">
            <details open><summary>Where can I review sales?</summary><p>Open the <a href="{{ route('merchant.dashboard.report.salestoday') }}">Sales report</a> to review order totals, discounts, Pahatud commission, and estimated merchant net amounts for the selected period.</p></details>
            <details><summary>Why can gross sales differ from the payout amount?</summary><p>Gross sales are the order value before applicable discounts, commissions, adjustments, refunds, or reversals. The estimated merchant net reflects the amounts assigned to the restaurant after those entries.</p></details>
            <details><summary>How are payout questions handled?</summary><p>Pahatud operations coordinates merchant payouts using the account name and details saved with the application. Contact support if the account information changes or if a paid amount needs clarification.</p></details>
          </div>
        </section>

        <section class="merchant-help-section" id="support">
          <div class="merchant-help-section-head"><span>06</span><div><h2>Account &amp; support</h2><p>Get assistance while protecting your account.</p></div></div>
          <div class="merchant-help-questions">
            <details open><summary>What should I send with a support request?</summary><p>Include the restaurant name, merchant account email, a clear subject, the affected order number if applicable, and a concise description of the expected and actual result. Screenshots are helpful when they do not contain sensitive information.</p></details>
            <details><summary>How do I protect the merchant account?</summary><p>Use a unique password, limit access to trusted staff, sign out on shared devices, and never send your password or one-time code through email or chat. Contact support immediately if you suspect unauthorized access.</p></details>
            <details><summary>How can I contact Pahatud?</summary><p>Email <a href="mailto:info@pahatud.com">info@pahatud.com</a>, call <a href="tel:+639162986547">+63 916 298 6547</a> or <a href="tel:+63822243919">(082) 224 3919</a>, or use the public <a href="{{ route('contactus') }}">contact form</a>.</p></details>
          </div>
        </section>
      </main>

      <aside class="merchant-help-aside">
        <span class="merchant-help-aside-icon"><i class="fas fa-headset"></i></span>
        <h2>Still need help?</h2><p>Send the Pahatud team the relevant account or order details so the issue can be routed quickly.</p>
        <a href="mailto:info@pahatud.com?subject=Merchant%20support%20request"><i class="fas fa-envelope"></i><span>Email support<small>info@pahatud.com</small></span></a>
        <a href="tel:+639162986547"><i class="fas fa-phone"></i><span>Call support<small>+63 916 298 6547</small></span></a>
        <a href="{{ route('merchant.dashboard.index') }}"><i class="fas fa-arrow-left"></i><span>Return to dashboard</span></a>
      </aside>
    </div>
  </div></section>
</div>
@endsection
