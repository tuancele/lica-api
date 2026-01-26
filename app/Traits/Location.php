<?php

declare(strict_types=1);

namespace App\Traits;

use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Province;
use App\Modules\Location\Models\Ward;

trait Location
{
    public function getWard($district, $select = null)
    {
        $wards = Ward::where('districtid', $district)->orderBy('wardid', 'asc')->get();
        $html = "<option value=''>---</option>";
        if ($wards->count() > 0) {
            foreach ($wards as $ward) {
                if ($ward->wardid == $select) {
                    $html .= '<option value="'.$ward->wardid.'" selected="">'.$ward->name.'</option>';
                } else {
                    $html .= '<option value="'.$ward->wardid.'">'.$ward->name.'</option>';
                }
            }
        }

        return $html;
    }

    public function getDistrict($province, $select = null)
    {
        $districts = District::where('provinceid', $province)->orderBy('districtid', 'asc')->get();
        $html = "<option value=''>---</option>";
        if ($districts->count() > 0) {
            foreach ($districts as $district) {
                if ($district->districtid == $select) {
                    $html .= '<option value="'.$district->districtid.'" selected="">'.$district->name.'</option>';
                } else {
                    $html .= '<option value="'.$district->districtid.'">'.$district->name.'</option>';
                }
            }
        }

        return $html;
    }

    public function getProvince($select = null)
    {
        $provinces = Province::orderBy('provinceid', 'asc')->get();
        $html = "<option value=''>---</option>";
        if ($provinces->count() > 0) {
            foreach ($provinces as $province) {
                if ($province->provinceid == $select) {
                    $html .= '<option value="'.$province->provinceid.'" selected="">'.str_replace(['Tỉnh', 'Thành phố'], '', $province->name).'</option>';
                } else {
                    $html .= '<option value="'.$province->provinceid.'">'.str_replace(['Tỉnh', 'Thành phố'], '', $province->name).'</option>';
                }
            }
        }

        return $html;
    }
}
