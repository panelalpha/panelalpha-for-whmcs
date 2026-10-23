<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Value of a WHMCS custom field for a service or addon (tblcustomfieldsvalues).
 *
 * @property int $id
 * @property int $fieldid
 * @property int $relid
 * @property string $value
 * @property-read CustomField|null $customField
 * @property-read Service|null $service
 *
 * @method static CustomFieldValue updateOrCreate(array $attributes, array $values = [])
 * @method static Builder with(array|string $relations)
 */
class CustomFieldValue extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblcustomfieldsvalues';

    /**
     * @var string[]
     */
    protected $fillable = [
        'fieldid',
        'relid',
        'value',
    ];

    /**
     * Field definition of this value.
     *
     * @return BelongsTo
     */
    public function customField()
    {
        return $this->belongsTo(CustomField::class, 'fieldid');
    }

    /**
     * Service this value belongs to.
     *
     * @return BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'relid');
    }
}
