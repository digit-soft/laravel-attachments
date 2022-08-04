<?php

namespace DigitSoft\Attachments;

use Illuminate\Support\Str;
use Illuminate\Redis\RedisManager;
use Illuminate\Contracts\Auth\Authenticatable;
use DigitSoft\Attachments\Traits\TokenChecksAccess;
use DigitSoft\Attachments\Traits\TokenStoresInRedis;

class TokenManager
{
    use TokenStoresInRedis, TokenChecksAccess;

    /**
     * Redis manager
     *
     * @var RedisManager
     */
    protected RedisManager $redis;
    /**
     * Redis connection
     *
     * @var string|null
     */
    protected ?string $connection;
    /**
     * Expire time in seconds
     *
     * @var int
     */
    protected int $expireTime;
    /**
     * Token string length
     *
     * @var int
     */
    protected int $tokenLength;

    /**
     * TokenManager constructor.
     *
     * @param  \Illuminate\Redis\RedisManager $redis
     * @param  string|null                    $connection
     * @param  int                            $expireTime
     * @param  int                            $tokenLength
     */
    public function __construct(RedisManager $redis, ?string $connection = null, int $expireTime = 3600, int $tokenLength = 60)
    {
        $this->redis = $redis;
        $this->expireTime = $expireTime;
        $this->tokenLength = $tokenLength;
        $this->connection = $connection;
    }

    /**
     * Obtain token for attachment and user
     *
     * @param  Attachment      $attachment
     * @param  Authenticatable $user
     * @return null|string
     */
    public function obtain(Attachment $attachment, Authenticatable $user): ?string
    {
        $tokenStr = $this->getToken($attachment, $user);

        return $tokenStr ?? $this->createToken($attachment, $user);
    }

    /**
     * Create new token for attachment and user
     *
     * @param  Attachment      $attachment
     * @param  Authenticatable $user
     * @return string|null
     */
    public function createToken(Attachment $attachment, Authenticatable $user): ?string
    {
        $tokenStr = $this->generateTokenStr();

        return $this->store($attachment, $user, $tokenStr) ? $tokenStr : null;
    }

    /**
     * Forget token for attachment and user
     *
     * @param  Attachment      $attachment
     * @param  Authenticatable $user
     */
    public function forget(Attachment $attachment, Authenticatable $user): void
    {
        $this->destroy($attachment, $user);
    }

    /**
     * Check that user has token for this attachment
     *
     * @param  Attachment      $attachment
     * @param  Authenticatable $user
     * @return bool
     */
    public function has(Attachment $attachment, Authenticatable $user): bool
    {
        return $this->getToken($attachment, $user) !== null;
    }

    /**
     * Validate token string.
     *
     * @param  string $token
     * @return bool
     */
    public function validateTokenStr(string $token): bool
    {
        $hashLn = 64; //for sha256 (256/4)
        if (strlen($token) !== ($this->tokenLength + $hashLn)) {
            return false;
        }
        $pos = ceil($this->tokenLength / 2);
        $randStr = substr($token, 0, $pos) . substr($token, -($this->tokenLength - $pos));
        $hash = strtolower(substr($token, $pos, $hashLn));

        return hash('sha256', $randStr) === $hash;
    }


    /**
     * Set redis manager.
     *
     * @param  RedisManager $redis
     */
    public function setRedis(RedisManager $redis): void
    {
        $this->redis = $redis;
    }

    /**
     * Get redis connection.
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function redis()
    {
        return $this->redis->connection($this->connection);
    }

    /**
     * Generate token string.
     *
     * @return string
     * @throws null
     */
    protected function generateTokenStr(): string
    {
        $randomStr = Str::random($this->tokenLength);
        $hash = hash('sha256', $randomStr);
        $hashLn = 64; //for sha256 (256/4)
        for ($i = 0; $i < $hashLn; $i++) {
            if (! is_numeric($hash[$i]) && random_int(0, 1) % 2) {
                $hash[$i] = strtoupper($hash[$i]);
            }
        }
        $pos = ceil($this->tokenLength / 2);

        return substr($randomStr, 0, $pos) . $hash . substr($randomStr, $pos);
    }
}
