<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZatcaCertificate extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the business that owns the certificate.
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }

    /**
     * Get the latest active certificate for a business
     * 
     * @param int $business_id
     * @return ZatcaCertificate|null
     */
    public static function getActiveCertificate($business_location_id)
    {
        return self::where('business_location_id', $business_location_id)
            ->whereNotNull('csid_certificate')
            ->whereNotNull('private')
            ->whereNotNull('csid_secret')
            ->latest()
            ->first();
    }
}