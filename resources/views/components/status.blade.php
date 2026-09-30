@props(['value'])
@php
$map = [
    'active' => 'green', 'paid' => 'green', 'completed' => 'green', 'achieved' => 'green',
    'upcoming' => 'blue', 'scheduled' => 'blue', 'in_progress' => 'purple', 'partial' => 'amber', 'frozen' => 'blue',
    'unpaid' => 'red', 'expired' => 'slate', 'cancelled' => 'slate', 'void' => 'slate', 'refunded' => 'purple',
    'no_show' => 'red', 'suspended' => 'red', 'inactive' => 'slate', 'abandoned' => 'slate', 'trial' => 'amber',
];
@endphp
<x-badge :color="$map[$value] ?? 'slate'" {{ $attributes }}>{{ \Illuminate\Support\Str::headline($value) }}</x-badge>
