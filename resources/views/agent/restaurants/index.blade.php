@extends('agent.layouts.app')

@section('title', 'Restaurants')

@section('content')
    <div class="agent-page-head">
        <div>
            <p class="agent-eyebrow">Your network</p>
            <h1>Merchant applications and restaurants</h1>
            <p>Track every merchant linked to your agent account from application through enrollment.</p>
        </div>
        <a class="agent-button agent-button-primary" href="{{ route('agent.restaurants.create') }}"><span>＋</span> Enroll restaurant</a>
    </div>

    <div class="agent-tier-note"><strong>Tier reminder:</strong> only restaurants with a final <em>Approved</em> enrollment status count toward your commission tier. Pending Approval, Pending Review, and Declined restaurants remain linked to you but do not raise the tier. <a class="agent-text-link" href="{{ route('agent.help') }}#earnings">See how tiers work →</a></div>

    <section class="agent-card" id="referral-signups">
        <div class="agent-card-header">
            <div><h2>Merchant directory</h2><p>{{ number_format($restaurants->total()) }} linked {{ Str::plural('merchant', $restaurants->total()) }}, shown once across recruitment and enrollment.</p></div>
        </div>
        @if ($restaurants->isEmpty())
            <div class="agent-empty"><strong>Your merchant list is empty</strong>Share your referral link or enroll your first restaurant to start building your network.</div>
        @else
            <div class="agent-table-wrap">
                <table class="agent-table">
                    <thead><tr><th>Business</th><th>Contact</th><th>Location</th><th>Submitted details</th><th>Orders</th><th>Commission earned</th><th>Status</th><th>Last submitted</th><th>Action</th></tr></thead>
                    <tbody>
                    @foreach ($restaurants as $restaurant)
                        <tr data-merchant-id="{{ $restaurant->id }}">
                            <td class="agent-table-primary">{{ $restaurant->restaurant_name }}<span class="agent-table-secondary">{{ $restaurant->registered_business_name ?: 'Registered name unavailable' }}</span><span class="agent-table-secondary">ID #{{ $restaurant->id }}</span></td>
                            <td>{{ $restaurant->owner_name ?: ($restaurant->enrollmentContact?->name ?: trim(($restaurant->enrollmentContact?->firstname ?? '').' '.($restaurant->enrollmentContact?->lastname ?? ''))) }}<span class="agent-table-secondary">{{ $restaurant->email }}</span><span class="agent-table-secondary">{{ $restaurant->mobile }}</span></td>
                            <td>{{ $restaurant->city }}<span class="agent-table-secondary">{{ Str::limit($restaurant->address, 34) }}</span></td>
                            <td>{{ Str::headline($restaurant->business_structure ?: 'Not provided') }}
                                <details><summary>View details</summary><small>Role: {{ Str::headline($restaurant->enrolling_as ?: 'Not provided') }}<br>TIN: {{ $restaurant->tin ?: '—' }}<br>Payout account name and details: {{ $restaurant->payout_account_name ?: '—' }}<br>Telephone: {{ $restaurant->telephone ?: '—' }}<br>Description: {{ $restaurant->description ?: '—' }}<br>Documents:<br>
                                @foreach (\App\RestaurantEnrollmentDocument::LABELS as $type => $label)
                                    @if ($type !== 'authorization_document' || $restaurant->enrolling_as === 'authorized_representative')
                                        {{ $label }}: {{ Str::headline($restaurant->enrollmentDocuments->firstWhere('document_type', $type)?->currentStatus() ?? 'missing') }}<br>
                                    @endif
                                @endforeach
                                </small></details>
                            </td>
                            <td>{{ number_format($restaurant->orders_count) }}</td>
                            <td class="agent-money agent-money-positive">₱{{ number_format($restaurant->commission_total ?? 0, 2) }}</td>
                            <td>
                                <div class="agent-status-stack">
                                    @if ($restaurant->application_source === \App\Partners::APPLICATION_SOURCE_REGISTRATION)
                                        <span class="agent-table-secondary">Recruitment</span>
                                        <span class="agent-badge agent-badge-{{ $restaurant->recruitment_status === 'declined' ? 'reversed' : $restaurant->recruitment_status }}">{{ $restaurant->recruitmentStatusLabel() }}</span>
                                    @endif
                                    @if ($restaurant->application_source !== \App\Partners::APPLICATION_SOURCE_REGISTRATION || $restaurant->recruitment_status === \App\Partners::STATUS_APPROVED)
                                        <span class="agent-table-secondary">Enrollment</span>
                                        <span class="agent-badge {{ $restaurant->application_status === 'approved' ? 'agent-badge-active' : 'agent-badge-review' }}">{{ Str::headline($restaurant->application_status ?: 'Under review') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ ($restaurant->recruitment_submitted_at ?? $restaurant->created_at)?->format('M d, Y') }}<span class="agent-table-secondary">{{ ($restaurant->recruitment_submitted_at ?? $restaurant->created_at)?->format('g:i A') }}</span></td>
                            <td>
                                @if ($restaurant->application_source === \App\Partners::APPLICATION_SOURCE_REGISTRATION)
                                    <a class="agent-text-link" href="{{ route('agent.merchant-applications.edit', $restaurant) }}">Update application</a>
                                @else
                                    <a class="agent-text-link" href="{{ route('agent.restaurants.show', $restaurant) }}">View / update application</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="agent-pagination">{{ $restaurants->links('pagination::bootstrap-4') }}</div>
        @endif
    </section>
@endsection
