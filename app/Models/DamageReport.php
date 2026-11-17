<?php

namespace App\Models;

use App\Enums\DamageSeverity;
use App\Enums\DamageStatus;
use Database\Factories\DamageReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * reported_by, status, resolution_notes and resolved_at are set by controller code, not mass-assigned from forms.
 */
#[Fillable(['equipment_id', 'reservation_id', 'description', 'severity', 'photo_path'])]
class DamageReport extends Model
{
    /** @use HasFactory<DamageReportFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'severity' => DamageSeverity::class,
            'status' => DamageStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /** withTrashed(): the report still shows its item even after the item is soft-deleted. */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class)->withTrashed();
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /** List filters: search by item tag/name or description, plus status and severity. */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $term) => $q->where(fn (Builder $q) => $q
                ->where('description', 'like', "%{$term}%")
                ->orWhereHas('equipment', fn (Builder $e) => $e->withTrashed()
                    ->where(fn (Builder $e) => $e->where('asset_tag', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%")))))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['severity'] ?? null, fn (Builder $q, $severity) => $q->where('severity', $severity));
    }

    /** Public URL of the photo, or null. */
    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }
}
