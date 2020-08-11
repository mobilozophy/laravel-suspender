<?php

namespace mobilozophy\Database\Support\traits;


/**
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withSuspended()
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder onlySuspended()
 * @method static static|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder withoutSuspended()
 */
trait Suspender
{
    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSuspender()
    {
        static::addGlobalScope(new \mobilozophy\Database\Support\scopes\Suspender);
    }

    /**
     * Initialize the suspender trait for an instance.
     *
     * @return void
     */
    public function initializeSuspender()
    {
        $this->dates[] = $this->getSuspendAtColumn();
    }


    /*
     * PRIMARY TRAIT FUNCTIONS FOR SUSPENDING AND RESCINDING SUSPENSION
     */
    /**
     * Suspends a model instance.
     * @return mixed
     */
    public function suspend()
    {
        $this->fireModelEvent('suspending', false);

        $this->{$this->getSuspendAtColumn()} = date('Y-m-d h:i:s');
        $result =  $this->save();

        $this->fireModelEvent('suspended', false);

        return $result;
    }

    /**
     * Restore a suspended model instance.
     * @return bool
     */
    public function rescindSuspension()
    {
        if ($this->fireModelEvent('rescindingsuspension') === false) {
            return false;
        }
        $this->{$this->getSuspendAtColumn()} = null;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('rescindedsuspension', false);

        return $result;
    }

    /*
     * BOOLEAN CHECKS FOR SUSPENSIONS
     */
    /**
     * Determine if the model instance has been suspended
     *
     * @return bool
     */
    public function isSuspended()
    {
        return ! is_null($this->{$this->getSuspendAtColumn()});
    }

    /**
     | MODEL EVENT REGISTRATION
     | The purpose of the model event registration is to register our pre and post
     | suspension/rescinding suspension with the dispatcher.
     | Supported Events:
     |    - suspending - fires before suspension
     |    - suspended - fires after suspension
     |    - rescindingsuspension - fires before rescinding suspension
     |    - rescindedsuspension - fires after rescinding suspension
     */
    /**
     * Register a suspending model event with the dispatcher
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function suspending($callback)
    {
        static::registerModelEvent('suspending', $callback);
    }

    /**
     * Register a suspended model event with the dispatcher
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function suspended($callback)
    {
        static::registerModelEvent('suspended', $callback);
    }

    /**
     * Register a rescinding model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function rescindingSuspension($callback)
    {
        static::registerModelEvent('rescindingsuspension', $callback);
    }

    /**
     * Register a rescinded model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function rescindedSuspension($callback)
    {
        static::registerModelEvent('rescindedsuspension', $callback);
    }


    /*
     * COLUMN DEFINITIONS
     */
    /**
     * Get the name of the "suspended at" column.
     *
     * @return string
     */
    public function getSuspendAtColumn()
    {
        return defined('static::SUSPENDED_AT') ? static::SUSPENDED_AT : 'suspended_at';
    }

    /**
     * Get the fully qualified "suspended at" column.
     *
     * @return string
     */
    public function getQualifiedSuspendAtColumn()
    {
        return $this->qualifyColumn($this->getSuspendAtColumn());
    }

}
