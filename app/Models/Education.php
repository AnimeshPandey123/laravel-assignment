<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Education extends BaseModel
{
    use HasFactory;

    protected $fillable = ['resume_id', 'institution', 'degree', 'field_of_study', 'start_date', 'end_date', 'grade', 'description'];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
