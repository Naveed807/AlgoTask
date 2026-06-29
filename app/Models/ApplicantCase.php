<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApplicantCase extends Model
{
    use HasFactory;
    protected $table = 'cases';
    
     protected $fillable = [
        'case_uuid',
        'applicant_name',
        'applicant_cnic',
        'district',
        'tehsil',
        'partner_name',
        'bank_name',
        'branch_name',
    ];

    public function financialRelease()
    {
        return $this->hasOne(FinancialRelease::class, 'case_id', 'id');
    }

    public function inspection()
    {
        return $this->hasOne(Inspection::class, 'case_id', 'id');
    }
}
