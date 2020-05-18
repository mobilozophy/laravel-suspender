<?php

namespace Tests\Entities;

use mobilozophy\Database\Support\Traits\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use mobilozophy\Database\Support\Traits\CascadeSuspensions;
use mobilozophy\Database\Support\Traits\Suspender;

class NonSoftDeletingPost extends Model
{
    use CascadeSoftDeletes, CascadeSuspensions, Suspender;

    protected $table = 'posts';

    protected $cascadeDeletes = ['comments'];

    protected $cascadeSuspensions = ['comments'];

    protected $fillable = ['title', 'body'];

    public function comments()
    {
        return $this->hasMany('Tests\Entities\Comment', 'post_id');
    }
}
