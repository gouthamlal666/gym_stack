<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>@include('partials.head')</head>
<body class="h-full font-sans antialiased">
<div class="flex min-h-full">
    <div class="relative hidden w-1/2 overflow-hidden bg-slate-900 lg:block">
        <div class="absolute inset-0 bg-gradient-to-br from-brand/80 via-slate-900 to-slate-950"></div>
        <div class="relative flex h-full flex-col justify-between p-12 text-white">
            <div class="flex items-center gap-2 text-lg font-semibold"><x-icon name="bolt" class="size-6"/> GymStack</div>
            <div>
                <h2 class="text-4xl font-semibold leading-tight">Run your gym.<br>Transform your members.</h2>
                <p class="mt-4 max-w-md text-slate-300">Memberships, billing, trainers and a premium personal-training suite — body tracking, assessments, workouts and nutrition in one place.</p>
            </div>
            <p class="text-sm text-slate-400">© {{ date('Y') }} GymStack</p>
        </div>
    </div>
    <div class="flex flex-1 items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">{{ $slot }}</div>
    </div>
</div>
@livewireScriptConfig
</body>
</html>
