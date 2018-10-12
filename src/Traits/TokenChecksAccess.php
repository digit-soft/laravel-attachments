<?php

namespace DigitSoft\Attachments\Traits;

use DigitSoft\Attachments\Attachment;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

trait TokenChecksAccess
{
    /**
     * Policy rule name
     * @var string
     */
    protected $policyAbility = 'privateDownload';
    /**
     * Name of model method to call
     * @var string
     */
    protected $modelMethod = 'attachmentCanDownload';

    /**
     * Check that user can download this attachment
     * @param  Attachment           $attachment
     * @param  Authenticatable|null $user
     * @return bool
     */
    public function canDownload(Attachment $attachment, $user = null)
    {
        if ($this->checkPolicies($attachment, $user)) {
            return true;
        } elseif ($this->checkModels($attachment, $user)) {
            return true;
        }
        return false;
    }

    /**
     * Check ability from policies
     * @param Attachment           $attachment
     * @param Authenticatable|null $user
     * @return bool
     */
    private function checkPolicies(Attachment $attachment, $user = null)
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
     * @param Attachment           $attachment
     * @param Authenticatable|null $user
     * @return bool
     */
    private function checkModels(Attachment $attachment, $user = null)
    {
        $models = $attachment->models;
        if (empty($models)) {
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
     * @param Model                $model
     * @param Attachment           $attachment
     * @param Authenticatable|null $user
     * @return bool
     */
    private function checkByModel($model, Attachment $attachment, $user = null)
    {
        $user = $user ?? auth()->user();
        if (empty($models)) {
            return false;
        }
        if (!method_exists($model, $this->modelMethod)) {
            return call_user_func_array([$model, $this->modelMethod], [$user, $attachment]);
        }
        return false;
    }

    /**
     * Check ability by gate
     * @param string               $ability
     * @param array                $arguments
     * @param Authenticatable|null $user
     * @return bool
     */
    private function checkByGate($ability, $arguments = [], $user = null)
    {
        $gate = $user !== null
            ? app(Gate::class)->forUser($user)
            : app(Gate::class);
        return $gate->check($ability, $arguments);
    }
}