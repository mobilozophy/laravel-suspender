<?php

namespace mobilozophy\Database\Support\traits;

use LogicException;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use mobilozophy\Database\Support\Exceptions\CascadeSuspensionException;

trait CascadeSuspensions
{
    /**
     * Boot the trait.
     *
     * Listen for the deleting event of a soft deleting model, and run
     * the delete operation for any configured relationship methods.
     *
     * @throws \LogicException
     */
    protected static function bootCascadeSuspensions()
    {
        static::suspending(function ($model) {
            $model->validateCascadingSuspension();

            $model->runCascadingSuspension();
        });

        static::rescindingSuspension(function ($model) {
            $model->validateCascadingRestore();

            $model->runCascadingRestoresSuspended();
        });
    }


    /**
     * Validate that the calling model is correctly setup for cascading soft deletes.
     *
     * @throws CascadeSuspensionException
     */
    protected function validateCascadingSuspension()
    {
        if (! $this->implementsSuspend()) {
            throw CascadeSuspensionException::suspensionNotImplemented(get_called_class());
        }

        if ($invalidCascadingRelationships = $this->hasInvalidSuspensionCascadingRelationships()) {
            throw CascadeSuspensionException::invalidRelationships($invalidCascadingRelationships);
        }
    }

    /**
     * Validate that the calling model is correctly setup for cascading restores.
     *
     * @throws CascadeSuspensionException
     */
    protected function validateCascadingRestore()
    {
        if (! $this->implementsSuspend()) {
            throw CascadeSuspensionException::suspensionNotImplemented(get_called_class());
        }

        if ($invalidCascadingRestoresRelationships = $this->hasInvalidSuspensionCascadingRelationships()) {
            throw CascadeSuspensionException::invalidRelationships($invalidCascadingRestoresRelationships);
        }
    }


    /**
     * Run the cascading soft delete for this model.
     *
     * @return void
     */
    protected function runCascadingSuspension()
    {
        foreach ($this->getActiveCascadingSuspensions() as $relationship) {
            $this->cascadeSuspend($relationship);
        }
    }

    /**
     * Run the cascading restore for this model.
     *
     * @return void
     */
    protected function runCascadingRestoresSuspended()
    {
        foreach ($this->getActiveCascadingRestores() as $relationship) {
            $this->cascadeRestores($relationship);
        }
    }

    /**
     * Cascade suspend the given relationship on the given mode.
     *
     * @param  string  $relationship
     * @return return
     */
    protected function cascadeSuspend($relationship)
    {
        $suspend = 'suspend';

        foreach ($this->{$relationship}()->get() as $model) {
            $model->pivot ? $model->pivot->{$suspend}() : $model->{$suspend}();
        }
    }

    /**
     * Cascade restore the given relationship.
     *
     * @param  string  $relationship
     * @return void
     */
    protected function cascadeRestores($relationship)
    {
        foreach ($this->{$relationship}()->onlySuspended()->get() as $model) {
            /** @var \Illuminate\Database\Eloquent\SoftDeletes $model */
            $model->pivot ? $model->pivot->rescindSuspension() : $model->rescindSuspension();
        }
    }

    /**
     * Determine if the current model implements soft deletes.
     *
     * @return bool
     */
    protected function implementsSuspend()
    {
        return method_exists($this, 'suspend');
    }


    /**
     * Determine if the current model has any invalid cascading relationships defined.
     *
     * A relationship is considered invalid when the method does not exist, or the relationship
     * method does not return an instance of Illuminate\Database\Eloquent\Relations\Relation.
     *
     * @return array
     */
    protected function hasInvalidSuspensionCascadingRelationships()
    {
        return array_filter($this->getCascadingSuspensions(), function ($relationship) {
            return ! method_exists($this, $relationship) || ! $this->{$relationship}() instanceof Relation;
        });
    }

    /**
     * Fetch the defined cascading restores for this model.
     *
     * @return array
     */
    protected function getCascadingSuspensionRestores()
    {
        return isset($this->cascadeSuspensions) ? (array) $this->cascadeSuspensions : [];
    }

    /**
     * Fetch the defined cascading suspensions for this model.
     *
     * @return array
     */
    protected function getCascadingSuspensions()
    {
        return isset($this->cascadeSuspensions) ? (array) $this->cascadeSuspensions : [];
    }


    /**
     * For the cascading deletes defined on the model, return only those that are not null.
     *
     * @return array
     */
    protected function getActiveCascadingSuspensions()
    {
        return array_filter($this->getCascadingSuspensions(), function ($relationship) {
            return ! is_null($this->{$relationship});
        });
    }

    /**
     * For the cascading restores defined on the model, return only those that are not null.
     *
     * @return array
     */
    protected function getActiveCascadingRestores()
    {
        return array_filter($this->getCascadingSuspensionRestores(), function ($relationship) {
            try{
                return ! is_null($this->{$relationship}()->onlySuspended());
            } catch (\Exception $e) {}
        });
    }
}
