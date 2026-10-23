<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WHMCS client account (tblclients).
 *
 * @property int $id
 * @property string $uuid
 * @property string $firstname
 * @property string $lastname
 * @property string $companyname
 * @property string $email
 * @property string $address1
 * @property string $address2
 * @property string $city
 * @property string $state
 * @property string $postcode
 * @property string $country
 * @property string $phonenumber
 * @property string $password
 * @property string $authmodule
 * @property string $authdata
 * @property int $currency
 * @property string $defaultgateway
 * @property float $credit
 * @property int $taxexempt
 * @property int $latefeeoveride
 * @property int $overideduenotices
 * @property int $separateinvoices
 * @property int $disableautocc
 * @property string $datecreated
 * @property string $notes
 * @property int $billingcid
 * @property int $securityqid
 * @property string $securityqans
 * @property int $groupid
 * @property string $cardtype
 * @property string $cardlastfour
 * @property string $cardnum
 * @property string $startdate
 * @property string $expdate
 * @property string $issuenumber
 * @property string $bankname
 * @property string $banktype
 * @property string $bankcode
 * @property string $bankacct
 * @property string $gatewayid
 * @property string $lastlogin
 * @property string $ip
 * @property string $host
 * @property string $status One of: Active, Inactive, Closed.
 * @property string $language
 * @property string $pwresetkey
 * @property int $emailoptout
 * @property int $overrideautoclose
 * @property int $allow_sso
 * @property int $email_verified
 * @property string $created_at
 * @property string $updated_at
 * @property string $pwresetexpiry
 * @property-read Collection<int, Service> $services
 * @property-read Collection<int, User> $users
 */
class Client extends EloquentModel
{
    /**
     * @var string
     */
    protected $table = 'tblclients';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string[]
     */
    protected $guarded = ['id'];

    /**
     * @var string[]
     */
    protected $fillable = ['uuid', 'firstname', 'lastname', 'companyname', 'email', 'address1', 'address2', 'city', 'state', 'postcode', 'country', 'phonenumber', 'password', 'authmodule', 'authdata', 'currency', 'defaultgateway', 'credit', 'taxexempt', 'latefeeoveride', 'overideduenotices', 'separateinvoices', 'disableautocc', 'datecreated', 'notes', 'billingcid', 'securityqid', 'securityqans', 'groupid', 'cardtype', 'cardlastfour', 'bankname', 'banktype', 'gatewayid', 'lastlogin', 'ip', 'host', 'status', 'language', 'pwresetkey', 'emailoptout', 'overrideautoclose', 'allow_sso', 'email_verified', 'created_at', 'updated_at', 'pwresetexpiry'];

    /**
     * @var bool
     */
    protected $softDelete = false;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * Services owned by this client.
     *
     * @return HasMany
     */
    public function services()
    {
        return $this->hasMany(Service::class, 'userid');
    }

    /**
     * Users that have access to this client account.
     *
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'tblusers_clients', 'client_id', 'auth_user_id');
    }

    /**
     * Return the first name with HTML entities decoded, as WHMCS stores it encoded.
     *
     * @return string|null
     */
    public function getFirstnameAttribute()
    {
        return preg_replace_callback('/(&#[0-9]+;)/', function ($m) {
            return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }, html_entity_decode($this->attributes['firstname']));
    }

    /**
     * Return the last name with HTML entities decoded, as WHMCS stores it encoded.
     *
     * @return string|null
     */
    public function getLastnameAttribute()
    {
        return preg_replace_callback('/(&#[0-9]+;)/', function ($m) {
            return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }, html_entity_decode($this->attributes['lastname']));
    }
}
