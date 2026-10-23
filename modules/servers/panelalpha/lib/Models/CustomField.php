<?php

namespace WHMCS\Module\Server\PanelAlpha\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WHMCS custom field definition (tblcustomfields).
 *
 * @property int $id
 * @property string $type
 * @property int $relid
 * @property string $fieldname
 * @property string $fieldtype
 * @property string $showorder
 * @property string $adminonly
 * @property-read Product|null $product
 *
 * @method static Builder join(string $table, string $first, string $operator, string $second)
 * @method static Builder where(mixed $column, mixed $operator = null, mixed $value = null)
 * @method static bool insert(array $values)
 * @method static CustomField create(array $attributes)
 */
class CustomField extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblcustomfields';

    /**
     * @var string[]
     */
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

    /**
     * Product this field belongs to (only for product custom fields).
     *
     * @return BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'relid')->whereHas('customFields', function ($query) {
            $query->where('type', 'product');
        });
    }
}
