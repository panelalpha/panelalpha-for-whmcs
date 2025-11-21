<?php

namespace WHMCS\Module\Addon\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use WHMCS\Database\Capsule;

class Client extends EloquentModel
{
    protected $table = 'tblclients';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    protected $fillable = ['uuid', 'firstname', 'lastname', 'companyname', 'email', 'address1', 'address2', 'city', 'state', 'postcode', 'country', 'phonenumber', 'password', 'authmodule', 'authdata', 'currency', 'defaultgateway', 'credit', 'taxexempt', 'latefeeoveride', 'overideduenotices', 'separateinvoices', 'disableautocc', 'datecreated', 'notes', 'billingcid', 'securityqid', 'securityqans', 'groupid', 'cardtype', 'cardlastfour', 'bankname', 'banktype', 'gatewayid', 'lastlogin', 'ip', 'host', 'status', 'language', 'pwresetkey', 'emailoptout', 'overrideautoclose', 'allow_sso', 'email_verified', 'created_at', 'updated_at', 'pwresetexpiry'];

    protected $softDelete = false;

    public $timestamps = false;
}