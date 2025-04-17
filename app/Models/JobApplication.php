<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobApplication extends BaseModel
{
    use HasFactory;
    protected $fillable = ['resume_id', 'company', 'position', 'date_applied', 'description', 'notes', 'link', 'status'];

}
