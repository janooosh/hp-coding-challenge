<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Relationships
     */

    public function product() {
        return $this->belongsTo(Product::class);
    }

    /**
     * Methods
     */

    /**
     * Returns the optionValue for the given globalFieldId.
     * If no value is set or the field is not mapped, returns null.
     *
     * @param int $globalFieldId
     * @return string|null
     */
    public function getOptionValue(int $globalFieldId):string|null
    {
        for($i = 1; $i<=4; $i++) {
            $optionKey = "option_".$i."_global_field_id";
            $optionValueKey = "option_".$i."_value";
            if($this->{$optionKey} == $globalFieldId) {
                return $this->{$optionValueKey};
            }
        }
        return null;
    }

    /**
     * Make sure an optionVaue is set for the given globalFieldId.
     * Tries to update existing, if neccessary create new.
     * If $value is set to null, it does not delete the option, but sets its value to null.
     *
     * @param int $globalFieldId
     * @param string|null $value
     * @return void
     */
    public function setOptionValue(int $globalFieldId, ?string $value = null):void {
        // Find an existing one
        for($i = 1; $i <= 3; $i++)
        {
            $optionKey = "option_".$i."_global_field_id";
            $optionValueKey = "option_".$i."_value";
            if($this->{$optionKey} == $globalFieldId)
            {
                if(empty($value)) {
                    $value = null;
                }
                $this->update([
                    $optionValueKey => $value
                ]);
                return;
            }
        }

        // No existing -> find next empty one
        for($i = 1; $i <= 3; $i++) {
            $optionKey = "option_".$i."_global_field_id";
            $optionValueKey = "option_".$i."_value";
            if(empty($this->{$optionKey})) {
                if(empty($value)) {
                    $value = null;
                }
                $this->update([
                    $optionKey => $globalFieldId,
                    $optionValueKey => $value
                ]);
                return;
            }
        }

        // Still here? Not good..
        throw new \Exception("No empty option field found");
    }


}
