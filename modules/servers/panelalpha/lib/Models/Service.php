<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use \Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'tblhosting';

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

    public function product()
    {
        return $this->hasOne('WHMCS\Module\Server\PanelAlpha\Models\Product', 'id', 'packageid');
    }
}