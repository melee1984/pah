@unless ($merchantSetup['is_complete'])
    <section class="merchant-setup-banner" aria-labelledby="merchant-setup-title">
        <div class="merchant-setup-heading">
            <span class="merchant-setup-heading-icon"><i class="fas fa-rocket" aria-hidden="true"></i></span>
            <div class="merchant-setup-copy">
                <span class="admin-eyebrow">Store setup</span>
                <h2 id="merchant-setup-title">Finish setting up your store</h2>
                <p>Complete these steps so your storefront is ready for customers.</p>
            </div>
            <div class="merchant-setup-progress-summary">
                <span><strong>{{ $merchantSetup['completed'] }}</strong> of {{ $merchantSetup['total'] }} complete</span>
                <div class="merchant-setup-progress" role="progressbar" aria-label="Store setup progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $merchantSetup['percentage'] }}">
                    <span style="width: {{ $merchantSetup['percentage'] }}%"></span>
                </div>
            </div>
        </div>

        <div class="merchant-setup-tasks">
            @foreach ($merchantSetup['tasks'] as $task)
                <a class="merchant-setup-task {{ $task['complete'] ? 'is-complete' : '' }}" href="{{ $task['url'] }}">
                    <span class="merchant-setup-task-icon">
                        <i class="{{ $task['complete'] ? 'fas fa-check' : $task['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <span class="merchant-setup-task-copy">
                        <strong>{{ $task['title'] }}</strong>
                        <small>{{ $task['description'] }}</small>
                    </span>
                    <span class="merchant-setup-task-action">
                        {{ $task['complete'] ? 'Done' : 'Set up' }}
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endunless
