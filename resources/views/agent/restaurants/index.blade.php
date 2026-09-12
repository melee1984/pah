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
                    <thead><tr><th>Restaurant</th><th>Contact</th><th>Location</th><th>Orders</th><th>Commission earned</th><th>Enrollment</th><th>Date enrolled</th></tr></thead>
                    <tbody>
                    @foreach ($restaurants as $restaurant)
                        <tr>
                            <td class="agent-table-primary">{{ $restaurant->restaurant_name }}<span class="agent-table-secondary">ID #{{ $restaurant->id }}</span></td>
                            <td>{{ $restaurant->email }}<span class="agent-table-secondary">{{ $restaurant->mobile }}</span></td>
                            <td>{{ $restaurant->city }}<span class="agent-table-secondary">{{ Str::limit($restaurant->address, 34) }}</span></td>
                            <td>{{ number_format($restaurant->orders_count) }}</td>
                            <td class="agent-money agent-money-positive">₱{{ number_format($restaurant->commission_total ?? 0, 2) }}</td>
                            <td><span class="agent-badge {{ $restaurant->active && $restaurant->verified_at ? 'agent-badge-active' : 'agent-badge-review' }}">{{ $restaurant->active && $restaurant->verified_at ? 'Active' : 'Under review' }}</span></td>
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
