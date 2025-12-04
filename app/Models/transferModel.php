<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class transferModel extends Model
{
    use HasFactory;

    protected $table = "transfer";

    public $timestamps = false;
}
