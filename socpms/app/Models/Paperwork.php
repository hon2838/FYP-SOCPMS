<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paperwork extends Model
{
    protected $table = 'tbl_ppw';
    protected $primaryKey = 'ppw_id';

    protected $fillable = [
        'ppw_type',
        'session',
        'project_name',
        'objective',
        'purpose',
        'background',
        'aim',
        'startdate',
        'end_date',
        'pgrm_involve',
        'external_sponsor'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
