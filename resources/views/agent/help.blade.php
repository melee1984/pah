@extends('agent.layouts.app')

@section('title', 'Help & FAQ')

@section('content')
    <div class="agent-page-head"><div><p class="agent-eyebrow">Agent support</p><h1>Help &amp; frequently asked questions</h1><p>Find the next step for enrollment, documents, earnings, and payouts.</p></div><div class="agent-page-head-actions"><a class="agent-button agent-button-secondary" href="mailto:info@pahatud.com?subject=Agent%20support%20request">Contact support</a><a class="agent-button agent-button-primary" href="{{ route('agent.restaurants.create') }}">Enroll restaurant</a></div></div>

    <nav class="agent-help-jump" aria-label="Help topics">
        <a href="#getting-started">Getting started</a><a href="#documents">Documents &amp; review</a><a href="#approval">Approval guide</a><a href="#earnings">Earnings</a><a href="#account">Account &amp; payouts</a><a href="#support">Support</a>
    </nav>

    <div class="agent-help-layout">
        <main class="agent-help-main">
            <section class="agent-help-section" id="getting-started"><div class="agent-help-section-head"><span>01</span><div><h2>Getting started</h2><p>Register and manage your restaurant network.</p></div></div>
                <div class="agent-help-questions">
                    <details open><summary>How do I register a restaurant?</summary><p>Select <a href="{{ route('agent.restaurants.create') }}">Enroll</a>, enter the restaurant and contact details, choose its business structure, and add the payout account name and details. Submit the application even if some documents are unavailable. The restaurant contact receives a private invitation to set up their account.</p></details>
                    <details><summary>Where can I see restaurants I enrolled?</summary><p>Open <a href="{{ route('agent.restaurants.index') }}">Restaurants</a> to see your linked restaurants and their enrollment status. Select a restaurant to view or update its application.</p></details>
                    <details><summary>Can business information be corrected later?</summary><p>Yes. You or the restaurant can update submitted details. An approved application returns to pending review when business details change, and previously approved documents need re-verification.</p></details>
                </div>
            </section>

            <section class="agent-help-section" id="documents"><div class="agent-help-section-head"><span>02</span><div><h2>Documents &amp; review</h2><p>What to upload and what each status means.</p></div></div>
                <div class="agent-help-questions">
                    <details><summary>Which documents are needed before approval?</summary><p>A valid government-issued ID and a business registration certificate (DTI, SEC, or CDA as applicable). If the person enrolling is not the owner, include an authorization letter, secretary’s certificate, board resolution, or SPA.</p></details>
                    <details><summary>Can I submit an application with missing documents?</summary><p>Yes. Submit what is available now. Missing documents can be uploaded later from the restaurant application page. The restaurant cannot be approved until every required document is approved and current.</p></details>
                    <details><summary>What do the document statuses mean?</summary><p><strong>Missing</strong> means no file has been uploaded. <strong>Pending Verification</strong> means the admin has not finished checking it. <strong>Approved</strong> means accepted. <strong>Rejected</strong> means a replacement or correction is needed; read the admin remarks. <strong>Expired</strong> means a current file must be supplied.</p></details>
                    <details><summary>Who receives review updates?</summary><p>The restaurant and enrolling agent receive notifications when the application or a document status changes, including admin remarks when supplied.</p></details>
                </div>
            </section>

            <section class="agent-help-section" id="approval"><div class="agent-help-section-head"><span>03</span><div><h2>Restaurant approval guide</h2><p>What must happen before an application can be activated.</p></div></div>
                <div class="agent-help-questions">
                    <details open><summary>How is a restaurant application approved?</summary><div class="agent-approval-guide"><p>Only a Pahatud administrator can approve the final application. The agent’s role is to make sure the restaurant information and files are complete, monitor the review, and help the restaurant respond to requested corrections.</p><ol><li><strong>Complete the business information.</strong> Restaurant and registered business names, TIN, payout account name and details, contact details, address, city, business structure, and enrollment authority are required.</li><li><strong>Supply every required document.</strong> The application needs a valid government-issued ID and a DTI, SEC, or CDA business registration certificate. An authorized representative must also provide an authorization document.</li><li><strong>Wait for each individual review.</strong> An administrator opens every file, chooses Approved or Rejected, enters the true expiration date when applicable, and clicks <em>Save document review</em>. Merely changing the dropdown does not save the status.</li><li><strong>Resolve anything rejected or expired.</strong> Read the administrator’s remarks, upload a replacement, and wait for the replacement to be reviewed again.</li><li><strong>Final approval.</strong> The Approve application button becomes available only after all required information is complete and every required document’s saved status is Approved and current.</li></ol></div></details>
                    <details><summary>Why does the application still say Pending Verification after Approved was selected?</summary><p>Each document has its own form. The administrator must click <strong>Save document review</strong> directly below that document. Confirm that the status text above the form changes from Pending Verification to Approved before moving to the next file.</p></details>
                    <details><summary>What expiration date should be entered?</summary><p>Use the expiration date printed on the document. Leave it blank if that document has no expiration date. Do not enter today’s date or invent a date just to enable approval; an expired document must be replaced.</p></details>
                    <details><summary>When are remarks required?</summary><p>Remarks are optional for an approval. They are required when rejecting a document or declining the application so the restaurant and agent know exactly what must be corrected or replaced.</p></details>
                    <details><summary>What happens after final approval?</summary><p>The restaurant application becomes Approved, the merchant is activated and verified, and status notifications are sent to both the restaurant and its enrolling agent.</p></details>
                </div>
            </section>

            <section class="agent-help-section" id="earnings"><div class="agent-help-section-head"><span>04</span><div><h2>Earnings &amp; commission tiers</h2><p>How your approved restaurant count sets the rate saved on each qualifying order.</p></div></div>
                <div class="agent-help-tier-guide">
                    @php($helpApprovedRestaurantCount = $agent->approvedRestaurantCount())
                    <h3>Your current position</h3>
                    <p>You currently have <strong>{{ number_format($helpApprovedRestaurantCount) }} approved {{ Str::plural('restaurant', $helpApprovedRestaurantCount) }}</strong> and receive <strong>{{ number_format($agent->commissionPercentage($helpApprovedRestaurantCount), 2) }}%</strong> of Pahatud’s commission on new qualifying orders.</p>
                    @include('agent.partials.commission-tier-guide', ['approvedRestaurantCount' => $helpApprovedRestaurantCount])
                </div>
                <div class="agent-help-questions">
                    <details><summary>When do I start earning?</summary><p>After your agent account and linked restaurant are approved, a qualifying delivered order records commission automatically. Enrollment alone does not generate earnings.</p></details>
                    @php($currentAgentRate = $agent->commissionPercentage())
                    <details><summary>How is my commission calculated?</summary><p>The formula is eligible order subtotal × the restaurant’s Pahatud commission rate × your active tier percentage. Your share is taken from Pahatud’s commission, not from the full customer payment. Delivery fees are shown in the report but are not part of the commission base. At your current {{ number_format($currentAgentRate, 2) }}% tier, an eligible ₱1,000 subtotal using a {{ number_format(config('agent.pahatud_commission_percentage'), 2) }}% Pahatud rate produces ₱{{ number_format(1000 * config('agent.pahatud_commission_percentage') / 100 * $currentAgentRate / 100, 2) }} in agent commission.</p></details>
                    <details><summary>How do commission tiers work?</summary><p>Your share is {{ number_format(config('agent.commission_tiers.0'), 0) }}% of Pahatud’s commission with 0–34 approved restaurants, {{ number_format(config('agent.commission_tiers.35'), 0) }}% with 35–49, and {{ number_format(config('agent.commission_tiers.50'), 0) }}% with 50 or more. The system selects the tier automatically from your current approved-restaurant total whenever a delivered order qualifies.</p></details>
                    <details><summary>What counts as an approved restaurant?</summary><p>The restaurant’s final application status must be Approved. Merely submitting an enrollment, accepting an invitation, uploading documents, or receiving approval for one document does not count. Pending Review and Declined applications do not count. If an approved restaurant returns to review because its information changes or a required document is rejected or expires, it stops counting until final approval is restored.</p></details>
                    <details><summary>Exactly when does my new tier take effect?</summary><p>The new tier applies to qualifying orders recorded after the approved-restaurant threshold is reached. At 35 approved restaurants the rate becomes {{ number_format(config('agent.commission_tiers.35'), 0) }}%; at 50 it becomes {{ number_format(config('agent.commission_tiers.50'), 0) }}%. The new rate covers future qualifying orders from every restaurant assigned to you, including restaurants you enrolled before reaching the tier.</p></details>
                    <details><summary>Will a new tier change commissions already in my report?</summary><p>No. Each delivered qualifying order saves the Pahatud rate, your tier rate, and the calculated amount. A later tier change never recalculates pending, approved, paid, or reversed commission entries that already exist.</p></details>
                    <details><summary>Can my tier go down?</summary><p>Yes. Only restaurants that are currently Approved count. If your approved count falls below a threshold, future qualifying orders use the lower tier. Previously recorded commissions remain unchanged.</p></details>
                    <details><summary>Do cancelled or reversed orders count?</summary><p>No. Cancelled, failed, refunded, and reversed orders are excluded from earned commission. A reversed entry remains visible in the report for transparency.</p></details>
                    <details><summary>Where do I check individual commissions?</summary><p>Open <a href="{{ route('agent.reports.index') }}">Reports</a> to review qualifying orders, the rate used, commission amount, and pending, approved, paid, or reversed status.</p></details>
                </div>
            </section>

            <section class="agent-help-section" id="account"><div class="agent-help-section-head"><span>05</span><div><h2>Account &amp; payouts</h2><p>Access and payment questions.</p></div></div>
                <div class="agent-help-questions">
                    <details><summary>Why can’t I sign in immediately after applying?</summary><p>New agent applications wait for an admin decision. You receive an email when your application is approved or declined, along with any admin message. An invited agent must also replace their temporary password on first sign-in.</p></details>
                    <details><summary>Can I withdraw commission from the dashboard?</summary><p>The portal tracks earnings and their status; it does not provide self-service withdrawals. Pahatud operations coordinates approved payouts and marks entries as paid.</p></details>
                </div>
            </section>

            <section class="agent-help-section" id="support"><div class="agent-help-section-head"><span>06</span><div><h2>Contact agent support</h2><p>Send the details Pahatud needs to assist you quickly.</p></div></div>
                <div class="agent-help-questions">
                    <details open><summary>How do I send a support request?</summary><p>Email <a href="mailto:info@pahatud.com?subject=Agent%20support%20request">info@pahatud.com</a>. Include your full name, agent account email, the restaurant name when applicable, and a short description of the issue.</p></details>
                    <details><summary>What should I include for an enrollment or commission concern?</summary><p>For enrollment concerns, include the restaurant name and the application or document status. For commission concerns, include the order number, restaurant, transaction date, and the amount you expected. Attach a screenshot when it helps explain the issue, but never send your password or one-time code.</p></details>
                </div>
            </section>
        </main>
        <aside class="agent-help-aside"><span class="agent-help-aside-icon">?</span><h2>Still need help?</h2><p>Send Pahatud your agent email and the relevant restaurant or order details so the concern can be routed quickly.</p><a href="mailto:info@pahatud.com?subject=Agent%20support%20request">Email info@pahatud.com →</a><a href="{{ route('agent.restaurants.index') }}">View restaurants →</a><a href="{{ route('agent.reports.index') }}">Open reports →</a></aside>
    </div>
@endsection
