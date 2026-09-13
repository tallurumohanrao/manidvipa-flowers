<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Faq;
use Illuminate\Http\Request;
use App\Http\Requests\StoreFaqRequest;
use App\Traits\RedirectTrait;
use Symfony\Component\HttpFoundation\Response;
use Gate,View,Cache;

class FaqController extends Controller
{
    use RedirectTrait;
    public function __construct(Faq $model)
    {
        $this->model = $model;
        $this->module = 'faqs';
        View::share ( 'module', $this->module );
    }

    private function clearStorefrontCache(): void
    {
        Cache::forget('api_faqs');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        abort_if(Gate::denies($this->module.'_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $perPage = $request->input('per_page') ?: config('PER_PAGE');
        $question = $request->input('question');
        $answer = $request->input('answer');
        $status = $request->input('status');
        $query = $this->model::query();
        if ($request->filled('question')) {
            $query->where('question', 'like', '%' . $question . '%');
        }
        if ($request->filled('answer')) {
            $query->where('answer', 'like', '%' . $answer . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $status);
        }
        $data = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
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
    public function store(StoreFaqRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $request->request->add(['slug'=>'']);
        $formInput = $request->all();
        $faq = $this->model::create($formInput);
        $this->clearStorefrontCache();
        return $this->redirectAfterSave($request->FormButton, $faq->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function show(Faq $faq)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function edit(Faq $faq)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.edit', ['row' => $faq]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function update(StoreFaqRequest $request, Faq $faq)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $request->request->add(['slug'=>'']);
        $formInput = $request->all();
        if($faq->update($formInput) === true) {
            $this->clearStorefrontCache();
            return $this->redirectAfterSave($request->FormButton, $faq->id);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $faq = $this->model::findOrFail($id);
            if($faq->update(['status'=>$request->status])){
                $this->clearStorefrontCache();
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function destroy(Faq $faq)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($faq->delete() == 1) {
            $this->clearStorefrontCache();
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }

        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = explode(',',$request->ids);
        $result = 0;
        foreach($ids as $id) :
            $model = $this->model::find($id);
            if ($model) {
                $result = $model->delete();
            }
        endforeach;

        if($result == 1) {
            $this->clearStorefrontCache();
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }

        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

}
