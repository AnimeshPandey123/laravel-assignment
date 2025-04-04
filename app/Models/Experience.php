<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Experience extends BaseModel
{
    use HasFactory;

    protected $fillable = ['resume_id', 'title', 'company', 'location', 'start_date', 'end_date', 'description'];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
