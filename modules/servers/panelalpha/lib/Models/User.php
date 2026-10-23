<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * WHMCS user login (tblusers). One user can manage many client accounts.
 *
 * @property int $id
 * @property string $firstname
 * @property string $lastname
 * @property string $email
 * @property-read Collection<int, Client> $clients
 */
class User extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblusers';

    /**
     * @var string[]
     */
    protected $fillable = [
        'firstname',
        'lastname',
    ];

    /**
     * Client accounts this user has access to.
     *
     * @return BelongsToMany
     */
    public function clients()
    {
        return $this->belongsToMany(Client::class, 'tblusers_clients', 'auth_user_id', 'client_id');
    }
}
