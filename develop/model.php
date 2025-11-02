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

    public function getDisabledAtAttribute($value)
    {
        return isset($value) ? 1 : 0;
    }

    public function setDisabledAtAttribute($value)
    {
        $this->attributes['disabled_at'] = empty($value) ? null : now();
    }
}
