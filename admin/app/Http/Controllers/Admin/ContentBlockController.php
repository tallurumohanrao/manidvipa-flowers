<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreContentblockRequest;
use App\Models\Admin\ContentBlock;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\RedirectTrait;
use Gate,View;

class ContentBlockController extends Controller
{
    use RedirectTrait;
    public function __construct(ContentBlock $model)
    {
        $this->model = $model;
        $this->module = 'contentblocks';
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
        return view('admin.'.$this->module.'.index', ['data' => ContentBlock::all()]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.create', ['contentblock' => []]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreContentBlockRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($contentblock = ContentBlock::create($request->all()))
            return $this->redirectAfterSave($request->FormButton, $contentblock->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Models\Contentblock  $contentblock
     * @return \Illuminate\Http\Response
     */
    public function show(ContentBlock $contentblock)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Models\Contentblock  $contentblock
     * @return \Illuminate\Http\Response
     */
    public function edit(ContentBlock $contentblock)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.edit', compact('contentblock'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Models\Contentblock  $contentblock
     * @return \Illuminate\Http\Response
     */
    public function update(StoreContentblockRequest $request, ContentBlock $contentblock)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($contentblock->update($request->all())===true)
            return $this->redirectAfterSave($request->FormButton,$contentblock->id);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $contentblock = ContentBlock::findOrFail($id);
            if($contentblock->update(['status'=>$request->status])){
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Models\Contentblock  $contentblock
     * @return \Illuminate\Http\Response
     */
    public function destroy(ContentBlock $contentblock)
    {
        //
    }
}
