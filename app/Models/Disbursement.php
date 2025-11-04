<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Disbursement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'amount',
        'disbursement_date',
        'description',
        'created_by_id',
    ];

    protected $casts = [
        'disbursement_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected $appends = [
        'formatted_amount',
        'formatted_date',
    ];

    // Boot method - Model Events
    protected static function boot()
    {
        parent::boot();

        // After creating a disbursement, it should already have account transactions created
        // We just need to ensure project totals are updated
        static::created(function ($disbursement) {
            if ($disbursement->project_id) {
                $project = Project::find($disbursement->project_id);
                if ($project) {
                    $project->recalculateFromTransactions();
                }
            }
        });

        // After deleting a disbursement, delete related account transactions
        // and update project totals
        static::deleting(function ($disbursement) {
            // Delete related account transactions
            AccountTransaction::where('related_disbursement_id', $disbursement->id)->delete();
        });

        static::deleted(function ($disbursement) {
            if ($disbursement->project_id) {
                $project = Project::find($disbursement->project_id);
                if ($project) {
                    $project->recalculateFromTransactions();
                }
            }
        });
    }

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function accountTransactions()
    {
        return $this->hasMany(AccountTransaction::class, 'related_disbursement_id');
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return 'UGX ' . number_format($this->amount, 2);
    }

    public function getFormattedDateAttribute()
    {
        return $this->disbursement_date->format('d M Y');
    }

    // Scopes
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('disbursement_date', [$startDate, $endDate]);
    }
}
