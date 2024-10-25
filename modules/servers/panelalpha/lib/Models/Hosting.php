<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use \Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Description of Product
 *
 * @author Paweł Złamaniec <pawel.zl@modulesgarden.com>
 */
class Hosting extends EloquentModel
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'tblhosting';

    protected $primaryKey = 'id';

    /**
     * Eloquent guarded parameters
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Eloquent fillable parameters
     * @var array
     */
    protected $fillable = [
        'userid',
        'orderid',
        'packageid',
        'server',
        'regdate',
        'domain',
        'paymentmethod',
        'firstpaymentamount',
        'amount',
        'billingcycle',
        'nextduedate',
        'nextinvoicedate',
        'termination_date',
        'completed_date',
        'domainstatus',
        'username',
        'password',
        'notes',
        'subscriptionid',
        'promoid',
        'suspendreason',
        'overideautosuspend',
        'overidesuspenduntil',
        'dedicatedip',
        'assignedips',
        'ns1',
        'ns2',
        'diskusage',
        'disklimit',
        'bwusage',
        'bwlimit',
        'lastupdate'
    ];

    /**
     * Indicates if the model should soft delete.
     *
     * @var bool
     */
    protected $softDelete = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(Product::class, "packageid");
    }

    public function customField()
    {
        return $this->hasMany(CustomField::class, "packageid");
    }


    public static function getService(int $panelalphaServiceId)
    {
        $query = CustomFieldValue::with(['customField', 'hosting.product'])
            ->whereHas('customField', function ($query) {
                $query->where('fieldname', 'Service ID');
                $query->where('type', 'product');
            })
            ->whereHas('hosting.product', function ($query) {
                $query->where('servertype', 'panelalpha');
            })
            ->where('value', $panelalphaServiceId)
            ->first();

        return $query->hosting;
    }
}
