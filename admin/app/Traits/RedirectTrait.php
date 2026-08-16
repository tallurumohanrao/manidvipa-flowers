<?php

namespace App\Traits;

use Illuminate\Http\Request;
use File,Storage;

trait RedirectTrait {

    public function redirectAfterSave($formButton,$id)
    {
        switch($formButton)
        {
            case 'SAVE':
                    return redirect()->route('admin.'.$this->module.'.index')->with('success','Saved successfully.');
                break;

            case 'SAVEEDIT':
                    return redirect()->route('admin.'.$this->module.'.edit',$id)->with('success','Saved successfully.');
                break;

            case 'SAVENEW':
                    return redirect()->route('admin.'.$this->module.'.create')->with('success','Saved successfully.');
                break;
        }
    }


}
