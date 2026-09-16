@if (config('services.turnstile.enabled'))
    @once
        <link rel="preconnect" href="https://challenges.cloudflare.com">
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce

    <div class="cf-turnstile"
         data-sitekey="{{ config('services.turnstile.site_key') }}"
         data-action="{{ $action }}"
         data-theme="auto"
         data-size="flexible"></div>

    @error('cf-turnstile-response')
        <span class="invalid-feedback d-block" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
@endif
