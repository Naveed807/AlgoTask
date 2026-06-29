<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinancialRelease extends Model
{
    use HasFactory;
    protected $fillable = [
        'case_id',
        'first_release_date',
        'second_release_date',
        'final_release_date',
        'first_amount',
        'second_amount',
        'final_amount',
    ];

    public function case()
    {
        return $this->belongsTo(ApplicantCase::class, 'case_id', 'id');
    }
}
