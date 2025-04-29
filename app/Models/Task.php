<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;
    protected $fillable = ['title','priority'];

    // Laravel 12 uses timestamps by default, but let's be explicit
    public $timestamps = true;
}
