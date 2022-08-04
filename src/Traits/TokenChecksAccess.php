<?php

namespace DigitSoft\Attachments\Traits;

use DigitSoft\Attachments\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;

trait TokenChecksAccess
{
    /**
     * Policy rule name
     *
     * @var string
     */
    protected string $policyAbility = 'privateDownload';
    /**
     * Name of model method to call
     *
     * @var string
     */
    protected string $modelMethod = 'attachmentCanDownload';

    /**
     * Check that user can download this attachment
     *
     * @param  Attachment           $attachment
     * @param  Authenticatable|null $user
     * @return bool
     */
    public function canDownload(Attachment $attachment, ?Authenticatable $user = null): bool
    {
        return $this->checkPolicies($attachment, $user) || $this->checkModels($attachment, $user);
    }

    /**
     * Check ability from policies
     *
     * @param  Attachment           $attachment
     * @param  Authenticatable|null $user
     * @return bool
     */
    private function checkPolicies(Attachment $attachment, ?Authenticatable $user = null): bool
    {
        $models = $attachment->models;
        if (empty($models)) {
            return false;
        }
        foreach ($models as $model) {
            if ($this->checkByGate($this->policyAbility, [$model, $attachment], $user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check models by method
     *
     * @param  Attachment           $attachment
     * @param  Authenticatable|null $user
     * @return bool
     */
    private function checkModels(Attachment $attachment, ?Authenticatable $user = null): bool
    {
        if (empty($models = $attachment->models)) {
            return false;
        }
        foreach ($models as $model) {
            if ($this->checkByModel($model, $attachment, $user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check by model method
     *
     * @param  Model                $model
     * @param  Attachment           $attachment
     * @param  Authenticatable|null $user
     * @return bool
     */
    private function checkByModel(Model $model, Attachment $attachment, ?Authenticatable $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (method_exists($model, $this->modelMethod)) {
            return $model->{$this->modelMethod}($user, $attachment);
        }

        return false;
    }

    /**
     * Check ability by gate
     *
     * @param  string               $ability
     * @param  array                $arguments
     * @param  Authenticatable|null $user
     * @return bool
     */
    private function checkByGate(string $ability, array $arguments = [], ?Authenticatable $user = null): bool
    {
        $gate = $user !== null
            ? app(Gate::class)->forUser($user)
            : app(Gate::class);

        return $gate->check($ability, $arguments);
    }
}
