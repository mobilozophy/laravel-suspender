<?php

namespace Tests\Entities;

use mobilozophy\Database\Support\Traits\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use mobilozophy\Database\Support\Traits\CascadeSuspensions;
use mobilozophy\Database\Support\Traits\Suspender;

class InvalidRelationshipPost extends Model
{
    use SoftDeletes, CascadeSoftDeletes, CascadeSuspensions, Suspender;

    protected $table = 'posts';

    public $dates = ['deleted_at'];

    protected $cascadeDeletes = ['comments', 'invalidRelationship', 'anotherInvalidRelationship'];

    protected $cascadeSuspensions = ['comments', 'invalidRelationship', 'anotherInvalidRelationship'];

    protected $fillable = ['title', 'body'];

    public function comments()
    {
        return $this->hasMany('Tests\Entities\Comment', 'post_id');
    }

    public function invalidRelationship()
    {
        return;
    }

    public function anotherInvalidRelationship()
    {
        return;
    }
}
