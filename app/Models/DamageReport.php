<?php

namespace App\Models;

use App\Enums\DamageSeverity;
use App\Enums\DamageStatus;
use Database\Factories\DamageReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
