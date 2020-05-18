<?php

namespace Tests\Entities;

use mobilozophy\Database\Support\Traits\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use mobilozophy\Database\Support\Traits\CascadeSuspensions;
use mobilozophy\Database\Support\Traits\Suspender;

class Author extends Model
{
    use SoftDeletes, CascadeSoftDeletes, CascadeSuspensions, Suspender;

    public $dates = ['deleted_at'];

    protected $cascadeDeletes = ['posts', 'posttypes'];

    protected $cascadeSuspensions = ['posts', 'posttypes'];

    protected $fillable = ['name'];

    public function posts()
    {
        return $this->hasMany('Tests\Entities\Post');
    }

    public function posttypes()
    {
        return $this->belongsToMany('Tests\Entities\PostType', 'authors__post_types', 'author_id', 'posttype_id');
    }
}
