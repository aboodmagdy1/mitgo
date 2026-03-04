<?php

namespace App\Services;

use App\Models\VehicleType;

class VehicleTypeService extends BaseService
{
    protected $vehicleType;

    public function __construct(VehicleType $vehicleType)
    {
        $this->vehicleType = $vehicleType;
        parent::__construct($vehicleType);
    }

    /**
     * Get all active vehicle types
     */
    public function getActiveVehicleTypes()
    {
        return VehicleType::where('active', true)->get();
    }

    /**
     * Get vehicle type with pricing for a zone
     */
    public function getVehicleTypeWithZonePricing(int $vehicleTypeId, int $zoneId)
    {
        return VehicleType::with(['zonePricing' => function ($query) use ($zoneId) {
            $query->where('zone_id', $zoneId);
        }])->find($vehicleTypeId);
    }
}