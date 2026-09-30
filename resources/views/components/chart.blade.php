@props(['config', 'height' => 'h-64'])
<div wire:ignore x-data="chart(@js($config))" {{ $attributes->merge(['class' => "relative $height"]) }}>
    <canvas x-ref="canvas"></canvas>
</div>
