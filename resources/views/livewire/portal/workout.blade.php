<div>
    <x-page-header title="My workout plan" subtitle="Designed by your personal trainer."/>
    @if($plan) @include('pt.partials.workout-plan', ['plan' => $plan])
    @else <div class="card"><x-empty icon="dumbbell" title="No workout plan yet" text="Your trainer will assign one soon."/></div> @endif
</div>
