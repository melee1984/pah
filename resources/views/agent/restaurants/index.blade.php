@extends('agent.layouts.app')

@section('title', 'Restaurants')

@section('content')
    <div class="agent-page-head">
        <div>
            <p class="agent-eyebrow">Your network</p>
            <h1>Enrolled restaurants</h1>
            <p>Every restaurant listed here is securely linked to your agent account.</p>
        </div>
        <a class="agent-button agent-button-primary" href="{{ route('agent.restaurants.create') }}"><span>＋</span> Enroll restaurant</a>
    </div>

    <section class="agent-card">
        <div class="agent-card-header">
            <div><h2>Restaurant directory</h2><p>{{ number_format($restaurants->total()) }} {{ Str::plural('restaurant', $restaurants->total()) }} enrolled by you.</p></div>
        </div>
        @if ($restaurants->isEmpty())
            <div class="agent-empty"><strong>Your restaurant list is empty</strong>Enroll your first restaurant to start building your network.</div>
        @else
            <div class="agent-table-wrap">
                <table class="agent-table">
                    <thead><tr><th>Restaurant</th><th>Contact</th><th>Location</th><th>Business</th><th>Orders</th><th>Commission earned</th><th>Enrollment</th><th>Date enrolled</th></tr></thead>
                    <tbody>
                    @foreach ($restaurants as $restaurant)
                        <tr>
                            <td class="agent-table-primary">{{ $restaurant->restaurant_name }}<span class="agent-table-secondary">ID #{{ $restaurant->id }}</span><a href="{{ route('agent.restaurants.show', $restaurant) }}">View / update application</a></td>
                            <td>{{ $restaurant->email }}<span class="agent-table-secondary">{{ $restaurant->enrollmentContact?->name ?: trim(($restaurant->enrollmentContact?->firstname ?? '').' '.($restaurant->enrollmentContact?->lastname ?? '')) }}</span><span class="agent-table-secondary">{{ $restaurant->mobile }}</span></td>
                            <td>{{ $restaurant->city }}<span class="agent-table-secondary">{{ Str::limit($restaurant->address, 34) }}</span></td>
                            <td>{{ Str::headline($restaurant->business_structure ?: 'Not provided') }}<span class="agent-table-secondary">{{ $restaurant->registered_business_name ?: 'Registered name unavailable' }}</span>
                                <details><summary>Submitted details</summary><small>Role: {{ Str::headline($restaurant->enrolling_as ?: 'Not provided') }}<br>TIN: {{ $restaurant->tin ?: '—' }}<br>Registration: {{ $restaurant->business_registration_number ?: '—' }}<br>Payout account: {{ $restaurant->payout_account_name ?: '—' }}<br>Telephone: {{ $restaurant->telephone ?: '—' }}<br>Description: {{ $restaurant->description ?: '—' }}<br>Documents:<br>
                                @foreach (\App\RestaurantEnrollmentDocument::LABELS as $type => $label)
                                    @if ($type !== 'authorization_document' || $restaurant->enrolling_as === 'authorized_representative')
                                        {{ $label }}: {{ Str::headline($restaurant->enrollmentDocuments->firstWhere('document_type', $type)?->currentStatus() ?? 'missing') }}<br>
                                    @endif
                                @endforeach
                                </small></details>
                            </td>
                            <td>{{ number_format($restaurant->orders_count) }}</td>
                            <td class="agent-money agent-money-positive">₱{{ number_format($restaurant->commission_total ?? 0, 2) }}</td>
                            <td><span class="agent-badge {{ $restaurant->application_status === 'approved' ? 'agent-badge-active' : 'agent-badge-review' }}">{{ Str::headline($restaurant->application_status ?: 'Under review') }}</span></td>
                            <td>{{ optional($restaurant->created_at)->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="agent-pagination">{{ $restaurants->links('pagination::bootstrap-4') }}</div>
        @endif
    </section>
@endsection
