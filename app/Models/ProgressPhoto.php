<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressPhoto extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array { return ['taken_on' => 'date']; }

    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
    public function assessment(): BelongsTo { return $this->belongsTo(BodyAssessment::class, 'body_assessment_id'); }

    /** Photos are on the private disk and served only through the authorised route. */
    public function url(): string { return route('pt.photos.show', $this); }

    public function activityLabel(): string { return ucfirst($this->angle).' photo '.$this->taken_on?->format('d M Y'); }
}
