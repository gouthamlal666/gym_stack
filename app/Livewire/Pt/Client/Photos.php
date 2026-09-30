<?php

namespace App\Livewire\Pt\Client;

use App\Models\BodyAssessment;
use App\Models\Member;
use App\Models\ProgressPhoto;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

/** Progress photos live on the private disk; only the member, assigned trainer and gym admin can see them. */
class Photos extends ClientTab
{
    use WithFileUploads;

    public string $taken_on = '';
    public array $uploads = [];
    public string $angle = 'front';
    public $beforeDate = '';
    public $afterDate = '';

    public function mount(Member $member): void
    {
        parent::mount($member);
        Gate::authorize('view-progress-photos', $member);
        $this->taken_on = today()->toDateString();
    }

    public function upload(): void
    {
        Gate::authorize('upload-progress-photos', $this->member);
        $this->validate([
            'taken_on' => 'required|date|before_or_equal:today',
            'uploads' => 'required|array|min:1',
            'uploads.*' => 'image|max:6144',
            'angle' => 'required|in:'.implode(',', array_keys(config('gym.photo_angles'))),
        ]);
        $assessment = BodyAssessment::where('member_id', $this->member->id)->whereDate('assessed_on', $this->taken_on)->first();
        foreach ($this->uploads as $file) {
            ProgressPhoto::create([
                'member_id' => $this->member->id,
                'body_assessment_id' => $assessment?->id,
                'uploaded_by' => auth()->id(),
                'angle' => $this->angle,
                'path' => $file->store("progress/{$this->member->gym_id}/{$this->member->id}", 'local'),
                'taken_on' => $this->taken_on,
            ]);
        }
        $this->reset('uploads');
        $this->toast('Photo(s) uploaded.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('upload-progress-photos', $this->member);
        $photo = ProgressPhoto::where('member_id', $this->member->id)->findOrFail($id);
        Storage::disk('local')->delete($photo->path);
        $photo->delete();
        $this->toast('Photo deleted.');
    }

    public function render()
    {
        $photos = ProgressPhoto::where('member_id', $this->member->id)->orderByDesc('taken_on')->get();
        $dates = $photos->pluck('taken_on')->map->toDateString()->unique()->values();
        $before = $this->beforeDate ?: $dates->last();
        $after = $this->afterDate ?: $dates->first();
        $assessments = BodyAssessment::where('member_id', $this->member->id)->get();
        $nearest = fn ($date) => $date ? $assessments->sortBy(fn ($a) => abs($a->assessed_on->diffInDays($date)))->first() : null;

        return view('livewire.pt.client.photos', [
            'groups' => $photos->groupBy(fn ($p) => $p->taken_on->toDateString()),
            'dates' => $dates,
            'before' => $before, 'after' => $after,
            'beforePhotos' => $photos->filter(fn ($p) => $p->taken_on->toDateString() === $before)->keyBy('angle'),
            'afterPhotos' => $photos->filter(fn ($p) => $p->taken_on->toDateString() === $after)->keyBy('angle'),
            'beforeStats' => $nearest($before),
            'afterStats' => $nearest($after),
            'canUpload' => Gate::allows('upload-progress-photos', $this->member),
        ]);
    }
}
