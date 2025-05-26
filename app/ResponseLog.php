<?php

namespace Cloakr\Client;

use Illuminate\Database\Eloquent\Model;

class ResponseLog extends Model
{
    public $timestamps = false;


    public function request()
    {
        return $this->belongsTo(RequestLog::class, 'request_id');
    }
}
