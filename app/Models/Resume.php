<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Resume extends BaseModel
{
    use HasFactory;


    protected $fillable = ['user_id', 'title', 'summary'];

    public function experiences()
    {
        return $this->hasMany(Experience::class);
    }

    public function education()
    {
        return $this->hasMany(Education::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'skills_resumes');
    }
}
