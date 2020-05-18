<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Events\Dispatcher;
use mobilozophy\Database\Support\Exceptions\CascadeSoftDeleteException;
use PHPUnit\Framework\TestCase;

class CascadeSuspendIntegrationTest extends TestCase
{
    public static function setupBeforeClass(): void
    {
        $manager = new Manager();
        $manager->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $manager->setEventDispatcher(new Dispatcher(new Container()));

        $manager->setAsGlobal();
        $manager->bootEloquent();

        $manager->schema()->create('authors', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
            $table->softDeletes('suspended_at');
        });

        $manager->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->integer('author_id')->unsigned()->nullable();
            $table->string('title');
            $table->string('body');
            $table->timestamps();
            $table->softDeletes();
            $table->softDeletes('suspended_at');
        });

        $manager->schema()->create('comments', function ($table) {
            $table->increments('id');
            $table->integer('post_id')->unsigned();
            $table->string('body');
            $table->timestamps();
            $table->softDeletes('suspended_at');
        });

        $manager->schema()->create('post_types', function ($table) {
            $table->increments('id');
            $table->integer('post_id')->unsigned()->nullable();
            $table->string('label');
            $table->timestamps();
        });

        $manager->schema()->create('authors__post_types', function ($table) {

            $table->increments('id');
            $table->integer('author_id');
            $table->integer('posttype_id');
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('author');
            $table->foreign('posttype_id')->references('id')->on('post_types');
        });
    }

    /** @test */
    public function it_cascades_suspends_when_suspendinging_a_parent_model()
    {
        $post = Tests\Entities\Post::create([
            'title' => 'How to cascade soft deletes in Laravel',
            'body' => 'This is how you cascade soft deletes in Laravel',
        ]);

        $this->attachCommentsToPost($post);

        $this->assertCount(3, $post->comments);
        $post->suspend();
        $this->assertCount(0, Tests\Entities\Comment::where('post_id', $post->id)->get());
    }

    /** @test */
    public function it_cascades_suspends_when_suspendinging_a_parent_model_and_restores()
    {
        $post = Tests\Entities\Post::create([
            'title' => 'How to cascade soft deletes in Laravel',
            'body' => 'This is how you cascade soft deletes in Laravel',
        ]);
        $post_id = $post->id;
        $this->attachCommentsToPost($post);

        $this->assertCount(3, $post->comments);
        $post->suspend();
        $this->assertCount(0, Tests\Entities\Comment::where('post_id', $post->id)->get());

        $postToRestore = Tests\Entities\Post::where('id' , '=', $post_id)->withSuspended()->first();
        $postToRestore->rescindSuspension();

        $postRestored = Tests\Entities\Post::where('id' , '=', $post_id)->get();
        $this->assertCount(1, $postRestored);

        $restoredComments = Tests\Entities\Comment::where('post_id', $postRestored->first()->id)->get();
        $this->assertCount(3, $restoredComments);

        $this->assertCount(3, $post->comments);

    }




    /** @test */
    public function it_can_accept_cascade_deletes_as_a_single_string()
    {
        $post = Tests\Entities\PostWithStringCascade::create([
            'title' => 'Testing you can use a string for a single relationship',
            'body' => 'This falls more closely in line with how other things work in Eloquent',
        ]);

        $this->attachCommentsToPost($post);

        $post->suspend();

        $this->assertNull(Tests\Entities\Post::find($post->id));
        $this->assertCount(1, Tests\Entities\Post::withSuspended()->where('id', $post->id)->get());
        $this->assertCount(0, Tests\Entities\Comment::where('post_id', $post->id)->get());
    }

    /**
     * @test
     */
    public function it_handles_situations_where_the_relationship_method_does_not_exist()
    {
        $this->expectException(\mobilozophy\Database\Support\Exceptions\CascadeSuspensionException::class);
        $this->expectExceptionMessage('Relationship [comments] must exist and return an object of type Illuminate\Database\Eloquent\Relations\Relation');

        $post = Tests\Entities\PostWithMissingRelationshipMethod::create([
            'title' => 'Testing that missing relationship methods are accounted for',
            'body' => 'In this way, you need not worry about Laravel returning fatal errors',
        ]);

        $post->suspend();
    }

    /** @test */
    public function it_handles_soft_deletes_inherited_from_a_parent_model()
    {
        $post = Tests\Entities\ChildPost::create([
            'title' => 'Testing child model inheriting model trait',
            'body' => 'This should allow a child class to inherit the soft deletes trait',
        ]);

        $this->attachCommentsToPost($post);

        $post->suspend();

        $this->assertNull(Tests\Entities\ChildPost::find($post->id));
        $this->assertCount(1, Tests\Entities\ChildPost::withSuspended()->where('id', $post->id)->get());
        $this->assertCount(0, Tests\Entities\Comment::where('post_id', $post->id)->get());
    }

    /** @test */
    public function it_handles_grandchildren()
    {
        $author = Tests\Entities\Author::create([
            'name' => 'Testing grandchildren are deleted',
        ]);

        $this->attachPostsAndCommentsToAuthor($author);

        $author->suspend();

        $this->assertNull(Tests\Entities\Author::find($author->id));
        $this->assertCount(1, Tests\Entities\Author::withSuspended()->where('id', $author->id)->get());
        $this->assertCount(0, Tests\Entities\Post::where('author_id', $author->id)->get());

        $deletedPosts = Tests\Entities\Post::withSuspended()->where('author_id', $author->id)->get();
        $this->assertCount(2, $deletedPosts);

        foreach ($deletedPosts as $deletedPost) {
            $this->assertCount(0, Tests\Entities\Comment::where('post_id', $deletedPost->id)->get());
        }
    }

    /**
     * Attach some post types to the given author.
     *
     * @return void
     */
    public function attachPostTypesToAuthor($author)
    {
        $author->posttypes()->saveMany([

            Tests\Entities\PostType::create([
                'label' => 'First Post Type',
            ]),

            Tests\Entities\PostType::create([
                'label' => 'Second Post Type',
            ]),
        ]);
    }

    /**
     * Attach some dummy posts (w/ comments) to the given author.
     *
     * @return void
     */
    private function attachPostsAndCommentsToAuthor($author)
    {
        $author->posts()->saveMany([
            $this->attachCommentsToPost(
                Tests\Entities\Post::create([
                    'title' => 'First post',
                    'body' => 'This is the first test post',
                ])
            ),
            $this->attachCommentsToPost(
                Tests\Entities\Post::create([
                    'title' => 'Second post',
                    'body' => 'This is the second test post',
                ])
            ),
        ]);

        return $author;
    }

    /**
     * Attach some dummy comments to the given post.
     *
     * @return void
     */
    private function attachCommentsToPost($post)
    {
        $post->comments()->saveMany([
            new Tests\Entities\Comment(['body' => 'This is the first test comment']),
            new Tests\Entities\Comment(['body' => 'This is the second test comment']),
            new Tests\Entities\Comment(['body' => 'This is the third test comment']),
        ]);

        return $post;
    }
}