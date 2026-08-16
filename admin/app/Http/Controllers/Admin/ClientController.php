<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Client;
use Illuminate\Http\Request;
use App\Http\Requests\StoreClientRequest;
use App\Traits\StoreImageTrait;
use App\Traits\RedirectTrait;
use Symfony\Component\HttpFoundation\Response;
use Storage,Gate,View,DB,Arr;

class ClientController extends Controller
{
    use StoreImageTrait,RedirectTrait;
    public function __construct(Client $model)
    {
        $this->model = $model;
        $this->module = 'clients';
        View::share ( 'module', $this->module );
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        abort_if(Gate::denies($this->module.'_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $data = $this->model::orderBy('priority')->paginate(config('PER_PAGE'));
        return view('admin.'.$this->module.'.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.create', ['row' => []]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreClientRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        $client = $this->model::create($formInput);

        return $this->redirectAfterSave($request->FormButton, $client->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function show(Client $client)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function edit(Client $client)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.edit', ['row' => $client]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function update(StoreClientRequest $request, Client $client)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        if($client->update($formInput) === true){
            return $this->redirectAfterSave($request->FormButton, $client->id);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $client = $this->model::findOrFail($id);
            if($client->update(['status'=>$request->status])){
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function destroy(Client $client)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        Storage::delete('public/'.$this->module.'/'.$client->image);
        if($client->delete() == 1){
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }else{
            return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
        }
    }
    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = explode(',',$request->ids);
        foreach($ids as $id) :
            $model = $this->model::find($id);
            $images = 'public/'.$this->module.'/'.$model->image;
            Storage::delete($images);
            $result = $model->delete();
        endforeach;

        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

}
