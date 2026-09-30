@props(['condition' => 'Cerah', 'temp' => '24°C'])

<div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium border border-blue-200">
    <span>🌤️ {{ $condition }}</span>
    <span class="border-l border-blue-200 pl-2 font-bold">{{ $temp }}</span>
</div>
