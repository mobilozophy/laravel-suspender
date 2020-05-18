<?php

namespace Tests\Entities;

use Illuminate\Database\Eloquent\Model;
use mobilozophy\Database\Support\Traits\CascadeSuspensions;
use mobilozophy\Database\Support\Traits\Suspender;

class Comment extends Model
{
    use CascadeSuspensions, Suspender;

    protected $fillable = ['body'];

    public function post()
    {
        return $this->belongsTo('Tests\Entities\Post');
    }
}
