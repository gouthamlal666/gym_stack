<div>
    <x-page-header title="My nutrition" subtitle="Your dedicated PT nutrition plan."/>
    @if($plan) @include('pt.partials.nutrition-plan', ['plan' => $plan])
    @else <div class="card"><x-empty icon="leaf" title="No nutrition plan yet" text="Your trainer will set your calorie and macro targets."/></div> @endif
</div>
