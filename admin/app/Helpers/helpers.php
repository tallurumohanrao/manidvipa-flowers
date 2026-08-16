<?php
function block($id){
    return Cache::rememberForever('block_'.$id, function () use ($id) {
        return DB::table('content_blocks')->where('id',$id)->first();
    });
}

function pageNumbers(){
    return ['25' => '25', '50' => '50', '100' => '100', '250' => '250','500'=>'500','1000'=>'1000','10000'=>'10000'];
}

function currency($number){
    $decimal = (string)($number - floor($number));
    if( $decimal == '0'){
        $decimal = '.00';
    }
    $money = floor($number);
    $length = strlen($money);
    $delimiter = '';
    $money = strrev($money);

    for($i=0;$i<$length;$i++){
        if(( $i==3 || ($i>3 && ($i-1)%2==0) )&& $i!=$length){
            $delimiter .=',';
        }
        $delimiter .=$money[$i];
    }

    $result = strrev($delimiter);
    $decimal = preg_replace("/0\./i", ".", $decimal);
    $decimal = substr($decimal, 0, 3);

    $result = $result.$decimal;

    return '₹'.$result;
}

function bookingStatuses(){
    return [1=>'Accepted',2=>'Pending',3=>'Checkout',4=>'Cancelled'];
}

function paymentstatuses(){
    return ['Paid'=>'Paid','Partly Paid'=>'Partly Paid','Pending'=>'Pending','Captured'=>'Captured','Authorized'=>'Authorized','Refunded'=>'Refunded','Failed'=>'Failed'];
}

function paymentmethods(){
    return ['Online'=>'Online','Cash'=>'Cash'];
}

function serviceTypes(){
    return ['Installation' => 'Installation', 'Repairing' => 'Repairing','Uninstallation'=>'Uninstallation'];
}

function addressTypes(){
    $types = [''=>'Address Type','1'=>'Home (7am - 9pm Delivery)','2'=>'Office / Commercial (10am - 6pm Delivery)'];
    return $types;
}

function setParam($key,$value){
    return session()->put('cart.'.$key, $value);
}

function getParam($key){
    return session()->get('cart.'.$key);
}

function hasParam($key){
    return session()->has('cart.'.$key);
}

function removeParam($key){ 
    return session()->forget('cart.'.$key);
}

function miscwords(){
    return ['xx','Sex','sex','fuck','pussy','porn','http','www','@','!','#','$','%','^','&','*','(',')','[',']','{','}'];
}

function getAddress($type){
    $addr = '<h6>'.getParam($type.'.full_name') .'</h6>';
    if($type.'.company_name'){
        $addr .= '<h6>'.getParam($type.'.company_name') .'</h6>';
    }
    $addr .= '<span>'.getParam($type.'.email').", ".getParam($type.'.phone_number').'<span>';
    $addr .= '<address>'.getParam($type.'.address_line1').', '.getParam($type.'.address_line2').',</br> ';
    if(hasParam($type.'.landmark')){
        $addr .= getParam($type.'.landmark').', ';
    }
    $addr .= getParam($type.'.city').', '.getParam($type.'.state').',<br>'.getParam($type.'.country').', '.getParam($type.'.pincode').'.</address>';
    return $addr;
}

function getOrderAddress($address){
    $addr = '<h6>'.$address->full_name .'</h6>';
    if($address->company_name){
        $addr .= '<h6>'.$address->company_name .'</h6>';
    }
    $addr .= '<span>'.$address->email.", ".$address->phone_number.'<span>';
    $addr .= '<address>'.$address->address_line1 .', '.$address->address_line2 .',</br> ';
    if($address->landmark){
        $addr .= $address->landmark .', ';
    }
    $addr .= $address->city .', '. $address->state .',<br>'.$address->country .', '.$address->pincode .'.</address>';
    return $addr;
}
