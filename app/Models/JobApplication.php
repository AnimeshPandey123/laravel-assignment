<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobApplication extends BaseModel
{
    use HasFactory;
    protected $fillable = ['resume_id', 'company', 'position', 'date_applied', 'description', 'notes', 'link', 'status'];

    /**
     * Get the resume that owns the job application.
     */
    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
    /**
     * Get the user that owns the job application.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    /**
     * Get the status of the job application.
     */
    public function getStatusAttribute($value)
    {
        return $this->attributes['status'] = $value;
    }
    /**
     * Set the status of the job application.
     */
    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = $value;
    }
    /**
     * Get the date applied of the job application.
     */
    public function getDateAppliedAttribute($value) 
    {
        return $this->attributes['date_applied'] = $value;
    }
}
