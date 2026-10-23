<?php

namespace WHMCS\Module\Addon\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * WHMCS client (tblclients), used to look up clients by email.
 */
class Client extends EloquentModel
{
    /** @var string */
    protected $table = 'tblclients';

    /** @var string */
    protected $primaryKey = 'id';

    /** @var array<int, string> */
    protected $guarded = ['id'];

    /** @var array<int, string> */
    protected $fillable = ['uuid', 'firstname', 'lastname', 'companyname', 'email', 'address1', 'address2', 'city', 'state', 'postcode', 'country', 'phonenumber', 'password', 'authmodule', 'authdata', 'currency', 'defaultgateway', 'credit', 'taxexempt', 'latefeeoveride', 'overideduenotices', 'separateinvoices', 'disableautocc', 'datecreated', 'notes', 'billingcid', 'securityqid', 'securityqans', 'groupid', 'cardtype', 'cardlastfour', 'bankname', 'banktype', 'gatewayid', 'lastlogin', 'ip', 'host', 'status', 'language', 'pwresetkey', 'emailoptout', 'overrideautoclose', 'allow_sso', 'email_verified', 'created_at', 'updated_at', 'pwresetexpiry'];

    /** @var bool */
    protected $softDelete = false;

    /** @var bool */
    public $timestamps = false;
}
