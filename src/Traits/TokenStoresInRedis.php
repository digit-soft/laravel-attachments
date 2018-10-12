<?php

namespace DigitSoft\Attachments\Traits;

use DigitSoft\Attachments\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Redis\RedisManager;

/**
 * Trait StoresTokensInRedis
 * @package DigitSoft\Attachments\Traits
 * @property int          $expireTime
 */
trait TokenStoresInRedis
{
    private $tokenKeyPattern = 'att:{token}';
    private $attachmentKeyPattern = 'att:{attachment}:{user}';

    /**
     * Save token to storage
     * @param Attachment|int $attachment
     * @param User|int       $user
     * @param string         $tokenStr
     * @return bool
     */
    public function store($attachment, $user, string $tokenStr)
    {
        $attachmentId = $this->normalizeModelKey($attachment);
        $userId = $this->normalizeModelKey($user);
        $tokenKey = $this->getTokenStorageKey($tokenStr);
        $attachmentKey = $this->getAttachmentStorageKey($attachmentId, $userId);
        list($tokenAttId, $tokenUsrId) = $this->get($tokenStr);
        $valid = $tokenAttId === null && $tokenUsrId === null && $this->getToken($attachmentId, $userId) === null;
        if ($valid) {
            $this->redis()->setex($tokenKey, $this->expireTime, $attachment->getKey() . ':' . $user->getKey());
            $this->redis()->setex($attachmentKey, $this->expireTime, $tokenStr);
        }
        return $valid;
    }

    /**
     * Refresh token (reset expire time)
     * @param string $tokenStr
     * @return bool
     */
    public function refresh($tokenStr)
    {
        list($attachment, $user) = $this->get($tokenStr);
        if ($attachment !== null && $user !== null) {
            $tokenKey = $this->getTokenStorageKey($tokenStr);
            $attachmentKey = $this->getAttachmentStorageKey($attachment, $user);
            $this->redis()->expire($tokenKey, $this->expireTime);
            $this->redis()->expire($attachmentKey, $this->expireTime);
        }
        return false;
    }

    /**
     * Destroy token(s) by attachment and(or) user
     * @param Attachment|int|null $attachment
     * @param User|int|null       $user
     */
    public function destroy($attachment = null, $user = null)
    {
        if ($attachment !== null && $user !== null && ($tokenStr = $this->getToken($attachment, $user)) !== null) {
            $keys = [
                $this->getTokenStorageKey($tokenStr),
                $this->getAttachmentStorageKey($attachment, $user),
            ];
            $this->redis()->del($keys);
        } elseif ($attachment !== null && $user === null) {
            $this->destroyAllAttachmentTokens($attachment);
        } elseif ($attachment === null && $user !== null) {
            $this->destroyAllUserTokens($user);
        }
    }

    /**
     * Destroy token by string representation
     * @param string $tokenStr
     */
    public function destroyStr($tokenStr)
    {
        list($attachmentId, $userId) = $this->get($tokenStr);
        if ($attachmentId !== null && $userId !== null) {
            $keys = [
                $this->getTokenStorageKey($tokenStr),
                $this->getAttachmentStorageKey($attachmentId, $userId),
            ];
            $this->redis()->del($keys);
        }
    }

    /**
     * Get attachment and user IDs by token string
     * @param string $tokenStr
     * @param bool   $loadModels
     * @return array
     */
    public function get($tokenStr, $loadModels = false)
    {
        $data = $this->redis()->get($this->getTokenStorageKey($tokenStr));
        if (!$data || strpos($data, ':') === false) {
            return [null, null];
        }
        $ids = explode(':', $data);
        $data = [
            is_numeric($ids[0]) ? intval($ids[0]) : null, //Attachment ID
            is_numeric($ids[1]) ? intval($ids[1]) : null, //User ID
        ];
        if ($loadModels) {
            /** @var \Eloquent $userModel */
            $userModel = $this->getUserModelClass();
            $data[0] = $data[0] ? Attachment::whereKey($data[0])->first() : null;
            $data[1] = $data[1] ? $userModel::whereKey($data[1])->first() : null;
        }
        return $data;
    }

    /**
     * Get token string for attachment and user
     * @param Attachment|int $attachment
     * @param User|int       $user
     * @return string
     */
    public function getToken($attachment, $user)
    {
        return $this->redis()->get($this->getAttachmentStorageKey($attachment, $user));
    }

    /**
     * Get token string storage key
     * @param string $tokenStr
     * @return string
     */
    protected function getTokenStorageKey($tokenStr)
    {
        return strtr($this->tokenKeyPattern, ['{token}' => $tokenStr]);
    }

    /**
     * Get attachment/user token storage key
     * @param Attachment|int $attachment
     * @param User|int       $user
     * @return string
     */
    protected function getAttachmentStorageKey($attachment, $user)
    {
        $attachmentId = $this->normalizeModelKey($attachment);
        $userId = $this->normalizeModelKey($user);
        return strtr($this->attachmentKeyPattern, ['{attachment}' => $attachmentId, '{user}' => $userId]);
    }

    /**
     * Destroy all tokens for attachment
     * @param Attachment|int $attachment
     */
    protected function destroyAllAttachmentTokens($attachment)
    {
        $attachmentId = $this->normalizeModelKey($attachment);
        $attKeysPattern = $this->getAttachmentStorageKey($attachmentId, '*');
        $keys = $this->redis()->keys($attKeysPattern);
        if (empty($keys)) {
            return;
        }
        $tokens = $this->redis()->mget($keys);
        foreach ($tokens as $tokenStr) {
            $this->destroyStr($tokenStr);
        }
    }

    /**
     * Destroy all tokens for user
     * @param User|int $user
     */
    protected function destroyAllUserTokens($user)
    {
        $attKeysPattern = $this->getAttachmentStorageKey('*', $user);
        $keys = $this->redis()->keys($attKeysPattern);
        if (empty($keys)) {
            return;
        }
        $this->redis()->del($keys);
    }

    /**
     * Get user model class name
     * @return string
     */
    private function getUserModelClass()
    {
        return config('attachments.user_model', 'App\Models\User');
    }

    /**
     * Get model key
     * @param Model|mixed $model
     * @return int|null
     */
    private function normalizeModelKey($model)
    {
        if ($model instanceof Model) {
            return $model->getKey();
        }
        return is_numeric($model) ? intval($model) : null;
    }


    /**
     * Set redis manager
     * @param RedisManager $redis
     */
    abstract public function setRedis(RedisManager $redis);

    /**
     * Get redis connection
     * @return \Illuminate\Redis\Connections\Connection
     */
    abstract protected function redis();
}