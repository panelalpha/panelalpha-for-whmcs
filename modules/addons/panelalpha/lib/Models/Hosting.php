<?php

namespace WHMCS\Module\Addon\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use WHMCS\Database\Capsule;

class Hosting extends EloquentModel
{
    protected $table = 'tblhosting';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    protected $fillable = ['userid', 'orderid', 'packageid', 'server', 'regdate', 'domain', 'paymentmethod', 'firstpaymentamount', 'amount', 'billingcycle', 'nextduedate', 'nextinvoicedate', 'termination_date', 'completed_date', 'domainstatus', 'username', 'password', 'notes', 'subscriptionid', 'promoid', 'suspendreason', 'overideautosuspend', 'overidesuspenduntil', 'dedicatedip', 'assignedips', 'ns1', 'ns2', 'diskusage', 'disklimit', 'bwusage', 'bwlimit', 'lastupdate'];

    protected $softDelete = false;

    public $timestamps = false;
}