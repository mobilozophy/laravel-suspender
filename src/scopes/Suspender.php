<?php

namespace mobilozophy\Database\Support\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class Suspender implements Scope
{

    /**
     * All of the extensions to be added to the builder.
     *
     * @var array
     */
    protected $extensions = ['RescindSuspension', 'WithSuspended', 'WithoutSuspended', 'OnlySuspended'];

    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    private $model;

    /**
     * @var string The suspended at column, exactly like deleted_at
     */
    protected $suspended_at_col = 'suspended_at';

    /**
     * @param Builder $builder
     * @param Model $model
     */
    public function apply(Builder $builder, Model $model)
    {
        if (! $this->enabled) {
            return;
        }

        $builder->whereNull($this->suspended_at_col);
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }


    /**
     * Add the restore extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addRescindSuspension(Builder $builder)
    {
        $builder->macro('rescindSuspension', function (Builder $builder) {
            $builder->withSuspended();

            return $builder->update([$builder->getModel()->getSuspendedAtColumn() => null]);
        });
    }

    /**
     * Add the with-trashed extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addWithSuspended(Builder $builder)
    {
        $builder->macro('withSuspended', function (Builder $builder, $withSuspended = true) {
            if (! $withSuspended) {
                return $builder->withoutSuspended();
            }

            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the without-trashed extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addWithoutSuspended(Builder $builder)
    {
        $builder->macro('withoutSuspended', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->whereNull(
                $model->getQualifiedSuspendAtColumn()
            );

            return $builder;
        });
    }

    /**
     * Add the only-trashed extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addOnlySuspended(Builder $builder)
    {
        $builder->macro('onlySuspended', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->whereNotNull(
                $model->getQualifiedSuspendAtColumn()
            );

            return $builder;
        });
    }


    /**
     * Get the "suspended at" column for the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return string
     */
    protected function getSuspendedAtColumn(Builder $builder)
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedSuspendAtColumn();
        }

        return $builder->getModel()->getSuspendAtColumn();
    }

    /**
     * Disables the scoping of tenants.
     *
     * @return void
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Enables the scoping of tenants.
     *
     * @return void
     */
    public function enable()
    {
        $this->enabled = true;
    }
}