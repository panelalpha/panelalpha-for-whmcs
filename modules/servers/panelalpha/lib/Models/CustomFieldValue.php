<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static updateOrCreate(array $array, array $array1)
 */
class CustomFieldValue extends Model
{
    protected $table = 'tblcustomfieldsvalues';
    protected $fillable = [
        'fieldid',
        'relid',
        'value',
    ];
}
