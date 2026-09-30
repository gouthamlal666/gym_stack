<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToGym, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array { return ['expense_date' => 'date', 'amount' => 'decimal:2']; }

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }

    public function categoryLabel(): string { return config("gym.expense_categories.{$this->category}", $this->category); }

    public function activityLabel(): string { return $this->categoryLabel().' '.$this->amount; }
}
