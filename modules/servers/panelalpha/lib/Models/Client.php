<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Description of Client
 *
 * @var int id
 * @var string uuid
 * @var string firstname
 * @var string lastname
 * @var string companyname
 * @var string email
 * @var string address1
 * @var string address2
 * @var string city
 * @var string state
 * @var string postcode
 * @var string country
 * @var string phonenumber
 * @var string password
 * @var string authmodule
 * @var string authdata
 * @var int currency
 * @var string defaultgateway
 * @var double credit
 * @var int taxexempt
 * @var int latefeeoveride
 * @var int overideduenotices
 * @var int separateinvoices
 * @var int disableautocc
 * @var datetime datecreated
 * @var string notes
 * @var int billingcid
 * @var int securityqid
 * @var string securityqans
 * @var int groupid
 * @var text cardtype
 * @var text cardlastfour
 * @var string cardnum
 * @var string startdate
 * @var string expdate
 * @var string issuenumber
 * @var string bankname
 * @var string banktype
 * @var string bankcode
 * @var string bankacct
 * @var string gatewayid
 * @var datetime lastlogin
 * @var string ip
 * @var string host
 * @var enum('Active', 'Inactive', 'Closed') status
 * @var string language
 * @var string pwresetkey
 * @var int emailoptout
 * @var int overrideautoclose
 * @var int allow_sso
 * @var int email_verified
 * @var timestamp created_at
 * @var timestamp updated_at
 * @var timestamp pwresetexpiry
 */
class Client extends EloquentModel
{
    protected $table = 'tblclients';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    protected $fillable = ['uuid', 'firstname', 'lastname', 'companyname', 'email', 'address1', 'address2', 'city', 'state', 'postcode', 'country', 'phonenumber', 'password', 'authmodule', 'authdata', 'currency', 'defaultgateway', 'credit', 'taxexempt', 'latefeeoveride', 'overideduenotices', 'separateinvoices', 'disableautocc', 'datecreated', 'notes', 'billingcid', 'securityqid', 'securityqans', 'groupid', 'cardtype', 'cardlastfour', 'bankname', 'banktype', 'gatewayid', 'lastlogin', 'ip', 'host', 'status', 'language', 'pwresetkey', 'emailoptout', 'overrideautoclose', 'allow_sso', 'email_verified', 'created_at', 'updated_at', 'pwresetexpiry'];

    protected $softDelete = false;

    public $timestamps = false;

    public function services()
    {
        return $this->hasMany(Service::class, 'userid');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'tblusers_clients', 'client_id', 'auth_user_id');
    }

    public function getFirstnameAttribute()
    {
        return preg_replace_callback("/(&#[0-9]+;)/", function ($m) {
            return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
        }, html_entity_decode($this->attributes["firstname"]));
    }

    public function getLastnameAttribute()
    {
        return preg_replace_callback("/(&#[0-9]+;)/", function ($m) {
            return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
        }, html_entity_decode($this->attributes["lastname"]));
    }
}
