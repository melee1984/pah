<section class="merchant-guide" aria-labelledby="merchant-guide-title">
  <div class="merchant-guide-heading">
    <div><span class="admin-eyebrow">Merchant documentation</span><h2 id="merchant-guide-title">Run your store with confidence</h2><p>Use this quick guide for the daily tasks that keep your account, menu, and orders ready.</p></div>
    <a class="btn admin-btn-primary" href="{{ route('merchant.help') }}"><i class="fas fa-book-open mr-2"></i>Open help desk</a>
  </div>
  <div class="merchant-guide-steps">
    <article><span>01</span><h3>Complete your account</h3><p>Keep your profile, branch addresses, contact information, and payout account name and details current.</p><a href="{{ route('merchant.dashboard.settings') }}">Review profile</a></article>
    <article><span>02</span><h3>Prepare your menu</h3><p>Add clear product names, prices, categories, availability, and add-ons before opening the store.</p><a href="{{ route('merchant.dashboard.product') }}">Manage products</a></article>
    <article><span>03</span><h3>Manage orders</h3><p>Keep the store online, review new orders promptly, prepare every item, and follow the delivery status.</p><a href="{{ route('merchant.dashboard.orders') }}">Open orders</a></article>
    <article><span>04</span><h3>Review sales</h3><p>Use the sales report to check order totals, commission, discounts, and your estimated net amount.</p><a href="{{ route('merchant.dashboard.report.salestoday') }}">View sales</a></article>
  </div>
  <div class="merchant-guide-bottom">
    <div><h3>Application documents</h3><p>Agent-enrolled restaurants only need a valid government-issued ID and a DTI, SEC, or CDA business registration certificate for the standard document review. An authorization document is also needed when an authorized representative is enrolling the business.</p>
      @if (Auth::User()->merchant?->agent_id)<a href="{{ route('merchant.application.show') }}">View application and document status</a>@endif
    </div>
    <div><h3>Need help from Pahatud?</h3><p>Include your restaurant name, account email, order number when applicable, and a short description of what happened.</p><div class="merchant-guide-contact"><a href="mailto:info@pahatud.com"><i class="fas fa-envelope"></i> info@pahatud.com</a><a href="tel:+639162986547"><i class="fas fa-phone"></i> +63 916 298 6547</a></div></div>
  </div>
</section>
