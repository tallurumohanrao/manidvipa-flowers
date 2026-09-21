<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Page;
use Illuminate\Http\Request;
use App\Http\Requests\StorePageRequest;
use App\Support\SeoRouteManager;
use Symfony\Component\HttpFoundation\Response;
use Gate,View,DB,Str,Cache;

class PageController extends Controller
{
    private const FIXED_PAGE_ROUTES = [
        'about-us' => '/about',
        'contact-us' => '/contact-us',
        'privacy-policy' => '/privacy-policy',
        'terms-conditions' => '/terms-conditions',
        'refund-policy' => '/refund-cancellation',
        'refund-return-policy' => '/refund-cancellation',
    ];

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
        $data = Page::all()->each(function ($page) {
            $alias = $this->pageSystemPath($page->slug ?: Str::slug($page->name));
            $page->public_url = SeoRouteManager::findByAlias($alias)?->url ?: $alias;
        });

        return view('admin.pages.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */ 
    public function create()
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.pages.create', ['page' => [], 'pageUrl' => null]);
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
        $alias = $this->pageSystemPath($input['slug']);
        $existingSeo = SeoRouteManager::findByAlias($alias);
        SeoRouteManager::ensurePathIsAvailable($request->url, $alias, $existingSeo?->id);
        if($id = DB::table('pages')->insertGetId($input)) {
            SeoRouteManager::save(
                ['url' => $request->url, 'page_title' => $request->name],
                $this->pageSystemPath($input['slug'])
            );
            $this->clearPageCaches($input['slug']);
            return $this->redirectFormOnSubmit($request->FormButton,$id);
        }
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
        $alias = $this->pageSystemPath($page->slug ?: Str::slug($page->name));
        $pageUrl = SeoRouteManager::findByAlias($alias)?->url ?: $alias;
        return view('admin.pages.edit', compact('page', 'pageUrl'));
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
        $oldSlug = $page->slug;
        $input = $request->only('name','description','status');
        $input['slug'] = $oldSlug ?: Str::slug($request->name);
        $alias = $this->pageSystemPath($input['slug']);
        $seo = SeoRouteManager::findByAlias($alias);
        SeoRouteManager::ensurePathIsAvailable($request->url, $alias, $seo?->id);
        DB::table('pages')->where('id',$page->id)->update($input);
        SeoRouteManager::save(
            ['url' => $request->url, 'page_title' => $request->name],
            $alias,
            $seo?->url
        );
        $this->clearPageCaches($oldSlug, $input['slug']);

        return $this->redirectFormOnSubmit($request->FormButton,$page->id);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $page = Page::findOrFail($id);
            if($page->update(['status'=>$request->status])){
                $this->clearPageCaches($page->slug);
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }

    private function clearPageCaches(?string ...$slugs): void
    {
        $staticSlugs = [
            'about-us',
            'contact-us',
            'privacy-policy',
            'terms-conditions',
            'refund-policy',
            'refund-return-policy',
        ];

        $cacheKeys = [
            'home',
            'contact_page',
            'about',
            'terms',
            'privacy',
            'refund',
        ];

        foreach (array_filter(array_unique(array_merge($staticSlugs, $slugs))) as $slug) {
            $cacheKeys[] = 'api_static_page_'.$slug;
        }

        foreach (array_unique($cacheKeys) as $cacheKey) {
            Cache::forget($cacheKey);
        }
    }

    private function pageSystemPath(string $slug): string
    {
        return self::FIXED_PAGE_ROUTES[$slug] ?? '/content/'.$slug;
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
