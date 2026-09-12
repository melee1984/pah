@php
    $agentManifestPath = public_path('build/manifest.json');
    $agentManifestHasStyles = false;

    if (is_file($agentManifestPath)) {
        try {
            $agentManifest = json_decode(file_get_contents($agentManifestPath), true, 512, JSON_THROW_ON_ERROR);
            $agentManifestHasStyles = isset($agentManifest['resources/css/agent.css']);
        } catch (Throwable) {
            $agentManifestHasStyles = false;
        }
    }

    $agentViteIsAvailable = is_file(public_path('hot')) || $agentManifestHasStyles;
@endphp

@if ($agentViteIsAvailable)
    @vite('resources/css/agent.css')
@elseif (is_file(resource_path('css/agent.css')))
    <style>{!! file_get_contents(resource_path('css/agent.css')) !!}</style>
@endif
