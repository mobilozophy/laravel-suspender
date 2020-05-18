<?php

namespace Tests\Entities;

use mobilozophy\Database\Support\Traits\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use mobilozophy\Database\Support\Traits\CascadeSuspensions;
use mobilozophy\Database\Support\Traits\Suspender;

class PostWithStringCascade extends Model
{
    use SoftDeletes, CascadeSoftDeletes, CascadeSuspensions, Suspender;

    protected $table = 'posts';

    public $dates = ['deleted_at'];

    protected $cascadeDeletes = 'comments';

    protected $cascadeSuspensions = 'comments';

    protected $fillable = ['title', 'body'];

    public function comments()
    {
        return $this->hasMany('Tests\Entities\Comment', 'post_id');
    }
}
