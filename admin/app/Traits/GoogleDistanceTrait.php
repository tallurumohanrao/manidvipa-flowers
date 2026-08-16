<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait GoogleDistanceTrait {
    /**
     * Does very basic image validity checking and stores it. Redirects back if somethings wrong.
     * @Notice: This is not an alternative to the model validation for this field.
     *
     * @param Request $request
     * @return $this|false|string
     */
    public function getDistance($address_id)
    {
        $address_row = \DB::table('addresses')->select('address_line1','address_line2','city','state')->where('id',$address_id)->first();
        
        if (!$address_row) {
            return ['status' => 'fail', 'distance' => null];
        }
    
        $addr_array = [
            $address_row->address_line1,
            $address_row->address_line2,
            $address_row->city,
            $address_row->state
        ];
    
        $address = implode(',', array_filter($addr_array));
    
        $googleMapsKey = 'AIzaSyDlKY2KTdCEnc20M8HHNuDEdgqI8vQxAY';
        $origins = urlencode('8-3-241/21, Srinivasa Colony, Vengal Rao Nagar, SR Nagar, Hyderabad');
        $destinations = urlencode($address);
    
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$origins&destinations=$destinations&mode=driving&language=en-EN&sensor=false&units=imperial&key=$googleMapsKey";
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
    
        $result = json_decode($response, true);
    
        $element = $result['rows'][0]['elements'][0] ?? null;
    
        if ($element && $element['status'] == "OK") {
            $distanceText = $element['distance']['text'];
            $distanceValue = floatval(str_replace([',', ' mi', ' ft'], '', $distanceText));
    
            if (strpos($distanceText, 'mi') !== false) {
                $distanceValue *= 1.60934; // Convert miles to kilometers
            }
    
            return ['status' => 'success', 'distance' => round($distanceValue, 2)];
        }
    
        return ['status' => 'fail', 'distance' => null];
    }
    public function getDistanceOld($address_id)
    {
        $address_row = \DB::table('addresses')->select('address_line1','address_line2','city','state')->where('id',$address_id)->first();
        
        if(!$address_row){
            return ['status'=>'fail','distance'=>null];
        }
        $addr_array[] = $address_row->address_line1;
        $addr_array[] = $address_row->address_line2;
        $addr_array[] = $address_row->city;
        $addr_array[] = $address_row->state;
        $address = implode(',',array_filter($addr_array));
        
        $googleMapsKey = 'AIzaSyDlKY2mKTdCNEc20M8HHNuDEdgqI8vQxCY';
        $origins = str_replace(" ", "", '8-3-241/21, Srinivasa Colony, Vengal Rao Nagar, SR Nagar, Hyderabad');
        $destinations = str_replace(" ", "", $address);
        $result = array();
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$origins&destinations=$destinations&mode=driving&language=en-EN&sensor=false&units=imperial&key=".$googleMapsKey; //miles
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = json_decode(curl_exec($ch), true);
        
        $var = $result['rows'][0]['elements'][0]; 
        if($var['status'] == "OK"){  
            $dis = str_replace(" ft", "", str_replace(" mi", "", $var['distance']['text']));
            if(strpos('mi',$var['distance']['text'])){
                $dis = $dis * 1.60934;
            }
            return ['status'=>'success','distance'=>$dis];
        }else{
            return ['status'=>'fail','distance'=>null];
        }
    }
}
