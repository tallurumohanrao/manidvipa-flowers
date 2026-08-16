<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RazorpayController;
use App\Http\Controllers\Admin\Auth\ConfirmPasswordController as AdminConfirmPasswordController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController as AdminForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController as AdminResetPasswordController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
//use App\Models\SeoUrl;

Route::get('/clear', function () {
    Artisan::call('route:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    return 'Cache Clear All';
});

Route::get('/storage', function () {
    Artisan::call('storage:link');
    return 'Storage linked';
});

Auth::routes();

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'showAdminLoginForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    Route::get('/password/reset', [AdminForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [AdminForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [AdminResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [AdminResetPasswordController::class, 'reset'])->name('password.update');

    Route::get('/password/confirm', [AdminConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
    Route::post('/password/confirm', [AdminConfirmPasswordController::class, 'confirm'])->name('password.confirm.submit');
});


Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about-us', [HomeController::class, 'about'])->name('about');
Route::get('/gallery', [HomeController::class, 'gallery'])->name('gallery');
Route::get('/terms-conditions', [HomeController::class, 'terms'])->name('terms');
Route::get('/privacy-policy', [HomeController::class, 'privacy'])->name('privacy');
Route::get('/refund', [HomeController::class, 'refund'])->name('refund');
Route::get('/posts', [HomeController::class, 'posts'])->name('posts');
Route::get('/posts/{slug}', [HomeController::class, 'postView'])->name('posts.show');
Route::get('/store', [ProductController::class, 'store'])->name('store');
Route::get('/categories/{slug}', [ProductController::class, 'categories'])->name('categories');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/reviews', [ProductController::class, 'reviewStore'])->name('products.reviews');
Route::get('/contact-us', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'contactStore'])->name('contact.store');
Route::get('/gallery', [HomeController::class, 'gallery'])->name('gallery');

Route::get('/account', [AccountController::class, 'index'])->name('user.index');
Route::get('/my-orders', [ AccountController::class,'orders' ])->name('user.orders');
Route::get('/orders/{id}', [ AccountController::class,'show' ])->name('user.order.show');
Route::get('/wishlist', [ AccountController::class,'wishlist' ])->name('user.wishlist');
Route::get('/add-remove-wishlist', [ ProductController::class,'addRemoveWishlist' ])->name('addRemoveWishlist');
Route::delete('/wishlist/delete', [ AccountController::class,'wishlistdelete' ])->name('user.wishlist.delete');
Route::patch('/my-account-update', [ AccountController::class,'update' ])->name('user.update');
Route::get('/changepassword', [ AccountController::class,'changePassword' ])->name('user.changepassword');
Route::post('/updatePassword', [ AccountController::class,'updatePassword' ])->name('user.updatepassword');
Route::resource('addresses',AddressController::class);

Route::resource('cart', CartController::class);
Route::post('/cart-bulk-update', [ App\Http\Controllers\CartController::class, 'cartbulkupdate' ])->name('cart.cartbulkupdate');
Route::get('/cart-products', [ App\Http\Controllers\CartController::class, 'products' ])->name('cart.products');
Route::get('/cart-mini', [ App\Http\Controllers\CartController::class, 'miniCart' ])->name('miniCart');
Route::get('/checkout-cart', function(){
    return view('cart.checkout-cart');
})->name('checkoutCart');
Route::get('/checkout', [ App\Http\Controllers\CheckoutController::class, 'index' ])->name('checkout.index');
Route::post('/add-coupon', [ App\Http\Controllers\CheckoutController::class, 'addCoupon' ])->name('checkout.addcoupon');
Route::get('/remove-coupon', [ App\Http\Controllers\CheckoutController::class, 'removeCoupon' ])->name('checkout.removecoupon');

Route::get('/checkout/get-address/{type}/{action}', [CheckoutController::class,'getAddress'])->name('checkout.getaddress');
Route::post('/checkout/select-address/{type}', [CheckoutController::class,'selectAddress'])->name('checkout.selectaddress');
Route::post('/checkout/store-address/{type}', [CheckoutController::class,'storeAddress'])->name('checkout.storeaddress');
Route::delete('/checkout/destroy-address/{type}/{id}', [CheckoutController::class,'destroyAddress'])->name('checkout.destroyaddress');
Route::post('/checkout/store-shippingmethod', [CheckoutController::class,'storeShippingmethod'])->name('checkout.storeshippingmethod');

Route::get('/checkout-addresses-store/{type}', [CheckoutController::class,'checkoutaddressesstore'])->name('checkout.addressesstore');
Route::post('/orders/store', [OrderController::class,'store'])->name('orders.store');
Route::get('/orders/success/{order_encrypt_key}', [OrderController::class,'success'])->name('orders.success');

Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');

Route::name('razorpay.')
    ->prefix('razorpay')
    ->group(function () {
        Route::get('index', [ RazorpayController::class, 'index' ])->name('create.payment');
        Route::post('handle-payment', [ RazorpayController::class, 'handlePayment' ])->name('make.payment');
        Route::get('success', [ RazorpayController::class, 'success' ])->name('success');
    });
// Route::post('/carts/store/{id}', [HomeController::class, 'cartstore'])->name('cart.store');
// Route::post('/carts/update/{cartid}', [HomeController::class, 'cartupdate'])->name('cart.update');

// Route::get('/payment/razorpay', [RazorpayController::class,'payWithRazorpay'])->name('paywithrazorpay');
// Route::post('/payment', [RazorpayController::class,'payment'])->name('payment');
// Route::get('/payment/success', [RazorpayController::class,'paymentsuccess'])->name('payments.success');
// $seo = SeoUrl::whereStatus(1)->get();
// foreach($seo as $row){
//     $route = app('router')->getRoutes()->match(app('request')->create($row->url));
//     list($controller, $action) = explode('@', $route->action['uses']);
//     if($row->alias){
//         Route::get($row->alias,[$controller,$action]);
//     }
// }
