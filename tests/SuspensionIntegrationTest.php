<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;
use mobilozophy\Database\Support\Exceptions\CascadeSoftDeleteException;
use PHPUnit\Framework\TestCase;

class SuspensionIntegrationTest extends TestCase
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
    public function it_susupends_post()
    {
        $post = Tests\Entities\Post::create([
            'title' => 'How to suspend in Laravel',
            'body' => 'This is how you suspend in Laravel',
        ]);
        $post = $post->find(1);
        $post->suspend();

        $this->assertCount(0, Tests\Entities\Post::where('id', $post->id)->get());
    }

    /**
     * @test
     */
    public function it_shows_with_suspended()
    {
        $post = Tests\Entities\Post::create([
            'title' => 'How to suspend in Laravel',
            'body' => 'This is how you suspend in Laravel',
        ]);
        $post_id =$post->id;

        $post = Tests\Entities\Post::find($post_id);
        $post->suspend();
        $this->assertCount(0, Tests\Entities\Post::where('id', $post_id)->get());

        $withSuspendedQuery = Tests\Entities\Post::where('id', $post_id)->withSuspended()->get();

        $this->assertCount(1, $withSuspendedQuery);
    }

    /**
     * @test
     */
    public function it_shows_only_suspended()
    {
        $postOne = Tests\Entities\Post::create([
            'title' => 'How to suspend in Laravel',
            'body' => 'This is how you suspend in Laravel',
        ]);
        $postTwo = Tests\Entities\Post::create([
            'title' => 'How to suspend in Laravel',
            'body' => 'This is how you suspend in Laravel',
        ]);
        $postThree = Tests\Entities\Post::create([
            'title' => 'How to suspend in Laravel',
            'body' => 'This is how you suspend in Laravel',
        ]);

        $post_two_id = $postTwo->id;

        $postToSuspend = Tests\Entities\Post::find($post_two_id);
        $postToSuspend->suspend();
        $this->assertCount(0, Tests\Entities\Post::where('id', $post_two_id)->get());
        $this->assertCount(2, Tests\Entities\Post::all());
        $this->assertCount(1, Tests\Entities\Post::onlySuspended()->get());

    }

    /**
     * @test
     */
    public function it_shows_without_suspended()
    {
        $postOne = Tests\Entities\Post::create([
            'title' => 'How to suspend in Laravel',
            'body' => 'This is how you suspend in Laravel',
        ]);
        $postTwo = Tests\Entities\Post::create([
            'title' => 'How to suspend in Laravel',
            'body' => 'This is how you suspend in Laravel',
        ]);
        $postThree = Tests\Entities\Post::create([
            'title' => 'How to suspend in Laravel',
            'body' => 'This is how you suspend in Laravel',
        ]);

        $post_one_id = $postOne->id;
        $post_two_id = $postTwo->id;
        $post_three_id = $postThree->id;

        $postToSuspend = Tests\Entities\Post::find($post_two_id);
        $postToSuspend->suspend();
        $this->assertCount(0, Tests\Entities\Post::where('id', $post_two_id)->get());
        $this->assertCount(2, Tests\Entities\Post::withoutSuspended()->get());
    }

    /**
     * @test
     */
    public function it_shows_recind_suspension()
    {
        $postOne = Tests\Entities\Post::create([
            'title' => 'How to suspend in Laravel',
            'body' => 'This is how you suspend in Laravel',
        ]);
        $postTwo = Tests\Entities\Post::create([
            'title' => 'How to suspend in Laravel',
            'body' => 'This is how you suspend in Laravel',
        ]);
        $postThree = Tests\Entities\Post::create([
            'title' => 'How to suspend in Laravel',
            'body' => 'This is how you suspend in Laravel',
        ]);

        $post_one_id = $postOne->id;
        $post_two_id = $postTwo->id;
        $post_three_id = $postThree->id;

        $postToSuspend = Tests\Entities\Post::find($post_two_id);
        $postToSuspend->suspend();
        $this->assertCount(0, Tests\Entities\Post::where('id', $post_two_id)->get());

        $postToSuspend->rescindSuspension();
        $this->assertCount(1, Tests\Entities\Post::where('id', $post_two_id)->get());
        $this->assertCount(3, Tests\Entities\Post::all());
    }
}