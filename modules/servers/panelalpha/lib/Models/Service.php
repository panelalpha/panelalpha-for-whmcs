<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WHMCS service (tblhosting).
 *
 * @property int $id
 * @property int $userid
 * @property int $packageid
 * @property int $server
 * @property string $domain
 * @property string $domainstatus
 * @property string $username
 * @property string $password
 * @property-read Product|null $product
 * @property-read Server|null $serverModel
 * @property-read Client|null $client
 * @property-read Collection<int, CustomFieldValue> $customFieldValues
 * @property-read int|null $panelalphaServiceId Value of the "Service ID" custom field.
 *
 * @method static Service findOrFail(int $id)
 * @method static Service|null find(int $id)
 * @method static Collection<int, Service> all(array|string $columns = ['*'])
 * @method static Builder active()
 * @method static Builder panelalpha()
 */
class Service extends Model
{
    protected $table = 'tblhosting';

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
     * Get the first active PanelAlpha service of a client.
     *
     * The result also has the product's configoption5 (SSO link in the main menu).
     *
     * @param int $clientId
     * @return Service|null
     */
    public static function getFirstServiceForClient(int $clientId)
    {
        return self::join('tblclients', 'tblhosting.userid', '=', 'tblclients.id')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->join('tblservers', 'tblservers.type', '=', 'tblproducts.servertype')
            ->where('tblservers.type', 'panelalpha')
            ->where('tblclients.id', $clientId)
            ->where('tblhosting.domainstatus', 'Active')
            ->select([
                'tblproducts.configoption5',
                'tblhosting.*'
            ])
            ->first();
    }

    /**
     * Find the WHMCS service linked to a PanelAlpha service by the "Service ID" custom field.
     *
     * @param int $panelalphaServiceId
     * @return Service|null
     */
    public static function getService(int $panelalphaServiceId): ?Service
    {
        $query = CustomFieldValue::with(['customField', 'service.product'])
            ->whereHas('customField', function ($query) {
                $query->where('fieldname', 'Service ID');
                $query->where('type', 'product');
            })
            ->whereHas('service.product', function ($query) {
                $query->where('servertype', 'panelalpha');
            })
            ->where('value', $panelalphaServiceId)
            ->first();

        return $query?->service;
    }

    /**
     * Product of this service.
     *
     * @return BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'packageid');
    }

    /**
     * Custom field values of this service.
     *
     * @return HasMany
     */
    public function customFieldValues()
    {
        return $this->hasMany(CustomFieldValue::class, 'relid');
    }

    /**
     * Server of this service. Named serverModel because "server" is the column name.
     *
     * @return BelongsTo
     */
    public function serverModel()
    {
        return $this->belongsTo(Server::class, 'server');
    }

    /**
     * Client that owns this service.
     *
     * @return BelongsTo
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'userid');
    }

    /**
     * Get the PanelAlpha service ID from the "Service ID" custom field.
     *
     * @return int|null
     */
    public function getPanelalphaServiceIdAttribute(): ?int
    {
        $serviceId = $this->getCustomFieldValue('Service ID');

        return !empty($serviceId) ? (int) $serviceId : null;
    }

    /**
     * Limit the query to active services.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('domainstatus', 'Active');
    }

    /**
     * Limit the query to services of PanelAlpha products.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePanelalpha($query)
    {
        return $query->whereHas('product', function ($query) {
            $query->where('servertype', 'panelalpha');
        });
    }

    /**
     * Get the value of a custom field of this service by field name.
     *
     * @param string $fieldName
     * @return string|null
     */
    public function getCustomFieldValue(string $fieldName): ?string
    {
        return $this->customFieldValues()->whereHas('customField', function ($query) use ($fieldName) {
            $query->where('fieldname', $fieldName);
        })->first()?->value;
    }
}
