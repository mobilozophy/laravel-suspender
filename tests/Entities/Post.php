<?php

namespace Tests\Entities;

use mobilozophy\Database\Support\Traits\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use mobilozophy\Database\Support\Traits\CascadeSuspensions;
use mobilozophy\Database\Support\Traits\Suspender;

class Post extends Model
{
    use SoftDeletes, CascadeSoftDeletes, CascadeSuspensions, Suspender;

    public $dates = ['deleted_at'];

    protected $cascadeDeletes = ['comments', 'postType'];

    protected $cascadeSuspensions = ['comments'];

    protected $fillable = ['title', 'body'];

    public function comments()
    {
        return $this->hasMany('Tests\Entities\Comment');
    }

    public function postType()
    {
        return $this->hasOne('Tests\Entities\PostType', 'post_id');
    }
}
