<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ isset($title) ? $title.' · ' : '' }}{{ auth()->user()?->gym?->name ?? config('app.name') }}</title>
@php $brand = auth()->user()?->gym?->primary_color ?? '#4f46e5'; @endphp
<style>:root { --brand: {{ preg_match('/^#[0-9a-fA-F]{6}$/', $brand) ? $brand : '#4f46e5' }}; }</style>
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
