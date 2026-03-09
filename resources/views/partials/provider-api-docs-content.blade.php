<div class="grid gap-4 lg:grid-cols-2">
    <div class="rounded-2xl bg-base-200 p-5 space-y-2 shadow-sm">
        <h3 class="font-semibold text-lg">Base URL</h3>
        <div class="mockup-code text-sm">
            <pre>{{ url('/api/v1') }}</pre>
        </div>
    </div>
    <div class="rounded-2xl bg-base-200 p-5 space-y-2 shadow-sm">
        <h3 class="font-semibold text-lg">Recommended Headers</h3>
        <div class="mockup-code text-sm">
            <pre>Content-Type: application/json</pre>
            <pre>X-API-KEY: YOUR_API_KEY</pre>
            <pre>Authorization: Bearer YOUR_API_KEY</pre>
            <pre>X-Client-Domain: yourdomain.com</pre>
        </div>
    </div>
</div>

<div class="grid gap-4 xl:grid-cols-2">
    @foreach($apiDocs as $doc)
    <div class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <h3 class="text-lg font-semibold">{{ $doc['title'] }}</h3>
            @if($doc['service_label'])<span class="badge badge-outline">{{ $doc['service_label'] }}</span>@endif
        </div>
        <p class="text-sm opacity-70">{{ $doc['description'] }}</p>
        <div class="badge badge-neutral badge-outline">{{ $doc['endpoint'] }}</div>
        <div class="overflow-x-auto rounded-xl bg-neutral text-neutral-content p-4 text-sm">
            <pre>@json($doc['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)</pre>
        </div>
    </div>
    @endforeach
</div>