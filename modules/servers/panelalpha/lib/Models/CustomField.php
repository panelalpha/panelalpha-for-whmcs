<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static join(string $table, string $column, string $operator, string $column)
 */
class CustomField extends Model
{
    protected $table = 'tblcustomfields';

    protected $fillable = [
        'type',
        'relid',
        'fieldname',
        'fieldtype',
        'showorder',
        'adminonly',
        'fieldoptions',
        'required',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'relid')->whereHas('customFields', function ($query) {
            $query->where('type', 'product');
        });
    }
}
