<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Skill extends BaseModel
{
    use HasFactory;

    protected $fillable = ['name', 'proficiency'];

    public function resumes()
    {
        return $this->belongsToMany(Resume::class, 'skills_resumes');
    }
}
