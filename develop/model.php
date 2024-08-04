<?php

namespace App\ModelsMODEL_DIRNAME;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MODEL_BASENAME extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        FORM_LIST,
    ];
}
