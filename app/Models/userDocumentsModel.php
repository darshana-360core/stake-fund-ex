<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class userDocumentsModel extends Model
{
    use HasFactory;

    protected $table = "user_documents";

    public $timestamps = false;
}
