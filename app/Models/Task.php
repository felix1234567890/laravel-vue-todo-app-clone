<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['title','priority'];

    // Laravel 12 uses timestamps by default, but let's be explicit
    public $timestamps = true;
}
