<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <style>:root { --brand: {{ $gym->primary_color ?? '#4f46e5' }}; }</style>
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none } body { background: #fff } }</style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased">
<div class="no-print mx-auto flex max-w-3xl justify-end gap-2 px-4 pt-6">
    <button onclick="window.print()" class="btn-primary">Print / Save as PDF</button>
</div>
<div class="mx-auto my-6 max-w-3xl bg-white p-10 shadow-sm print:my-0 print:shadow-none">
    <div class="flex items-start justify-between border-b-4 pb-6" style="border-color: var(--brand)">
        <div class="flex items-center gap-3">
            @if($gym->logoUrl())<img src="{{ $gym->logoUrl() }}" class="size-14 rounded-lg object-cover">@endif
            <div><div class="text-xl font-bold">{{ $gym->name }}</div><div class="text-sm whitespace-pre-line text-slate-500">{{ $gym->address }}</div><div class="text-sm text-slate-500">{{ $gym->phone }} {{ $gym->email }}</div></div>
        </div>
        <div class="text-right">@yield('heading')</div>
    </div>
    @yield('content')
    @if($gym->setting('invoice_footer'))<div class="mt-10 border-t border-slate-200 pt-4 text-xs whitespace-pre-line text-slate-500">{{ $gym->setting('invoice_footer') }}</div>@endif
</div>
</body>
</html>
