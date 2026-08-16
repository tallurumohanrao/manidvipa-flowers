<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAccountRequest;
use App\Traits\StoreImageTrait;
use Auth;

class AccountController extends Controller
{
    use StoreImageTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.account.index', ['data' => Auth::user()]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        $admin = Auth::user();
        return view('admin.account.edit', compact('admin'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreAccountRequest $request,  $id)
    {
        $admin = Auth::user();
        $formInput = $request->all();

        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', 'admins'); 
        if($admin->update($formInput) === true){
            return back()->with('success', 'Profile updated successfully!');
        }
    }

}
