<?php

namespace Cloakr\Client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RequestLog extends Model
{

    protected $primaryKey = 'request_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    public function response(): ?HasOne
    {
        return $this->hasOne(ResponseLog::class, 'request_id', 'request_id');
    }
}
