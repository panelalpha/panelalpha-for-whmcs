<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'tblusers';

    protected $fillable = [
        'firstname',
        'lastname',
    ];

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'tblusers_clients', 'auth_user_id', 'client_id');
    }
}
