<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inspection extends Model
{
    use HasFactory;
     protected $fillable = [
        'case_id',
        'foundation_inspection_date',
        'structure_inspection_date',
        'foundation_status',
        'structure_status',
    ];

    public function case()
    {
        return $this->belongsTo(ApplicantCase::class, 'case_id', 'id');
    }
}
