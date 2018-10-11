<?php

namespace DigitSoft\Attachments\Traits;

use DigitSoft\Attachments\Attachment;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;

trait TokenChecksAccess
{
    protected $policyAbility = 'privateDownload';

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
            if ($this->check($this->policyAbility, [$model], $user)) {
                return true;
            }
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
    private function check($ability, $arguments = [], $user = null)
    {
        $gate = $user !== null
            ? app(Gate::class)->forUser($user)
            : app(Gate::class);
        return $gate->check($ability, $arguments);
    }
}