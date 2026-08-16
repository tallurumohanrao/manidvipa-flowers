<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Page;
use Illuminate\Http\Request;
use App\Http\Requests\StorePageRequest;
use Symfony\Component\HttpFoundation\Response;
use Gate,View,DB,Str;

class PageController extends Controller
{
    public function __construct(Page $model)
    {
        $this->model = $model;
        $this->module = 'pages';
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
        return view('admin.pages.index', ['data' => Page::all()]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */ 
    public function create()
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.pages.create', ['page' => []]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePageRequest $request)
    {  
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $input = $request->only('name','description','status');
        $input['slug'] = Str::slug($request->name);
        if($id = DB::table('pages')->insertGetId($input))  
            return $this->redirectFormOnSubmit($request->FormButton,$id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function show(Page $page)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function edit(Page $page)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.pages.edit', compact('page'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function update(StorePageRequest $request, Page $page)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $input = $request->only('name','description','status');
        $input['slug'] = Str::slug($request->name);
        DB::table('pages')->where('id',$page->id)->update($input);
            return $this->redirectFormOnSubmit($request->FormButton,$page->id);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $page = Page::findOrFail($id);
            if($page->update(['status'=>$request->status])){
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function destroy(Page $page)
    {
        //
    }

    public static function redirectFormOnSubmit($formButton,$id)
    {
        
        switch($formButton)
        {
            case 'SAVE':
                    return redirect()->route('admin.pages.index')->with('success','Saved successfully.');
                break;

            case 'SAVEEDIT':
                    return redirect()->route('admin.pages.edit',$id)->with('success','Saved successfully.');
                break;

            case 'SAVENEW':
                    return redirect()->route('admin.pages.create')->with('success','Saved successfully.');
                break;
        }
    }
}
