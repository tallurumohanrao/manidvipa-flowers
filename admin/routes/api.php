<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\AccountController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\HomeController;
use App\Http\Controllers\API\SubscriptionController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('sitemap', [HomeController::class, 'sitemap']);
Route::get('cities', [HomeController::class, 'cities']);
Route::get('states', [HomeController::class, 'states']);
Route::get('seo-meta-data', [HomeController::class, 'seoMetaData']);
Route::get('settings', [HomeController::class, 'settings']);
Route::post('contact-us', [HomeController::class, 'contactStore']);
Route::get('static-page', [HomeController::class, 'staticPage']);
Route::get('faqs', [HomeController::class, 'faqs']);
Route::get('daily-price-status', [HomeController::class, 'dailyPriceStatus']);
Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [RegisterController::class, 'login']);
Route::post('logout', [RegisterController::class, 'logout']);
Route::post('send-password-reset-notification',[ RegisterController::class, 'sendPasswordResetNotification']);
Route::post('password/update',[ RegisterController::class, 'resetPassword']);
Route::get('auth-check', [RegisterController::class, 'authCheck']);

/* Home */
Route::get('search', [ProductController::class, 'search']);
Route::get('add-review', [ProductController::class, 'addReview']);
Route::get('reviews', [ProductController::class, 'reviews']);
Route::get('banners', [HomeController::class, 'banners']);
Route::get('brands', [HomeController::class, 'brands']);
Route::get('clients', [HomeController::class, 'clients']);
Route::get('home-categories', [HomeController::class, 'categories']);
Route::get('home-featured-products', [HomeController::class, 'featuredProducts']);
Route::get('subscription-plans', [SubscriptionController::class, 'plans']);
Route::get('subscription-plan', [SubscriptionController::class, 'plan']);
Route::post('subscription-enquiry', [SubscriptionController::class, 'enquiry']);
/* Home */

/* Product */
Route::get('categories', [CategoryController::class, 'index']);
Route::get('products-by-category', [ProductController::class, 'productsbycategory']);
Route::get('product-details', [ProductController::class, 'show']);
/* Product */
/* Cart */
Route::get('cart-count', [CartController::class, 'getCartCount']);
Route::get('get-cart-coupon', [CartController::class, 'getCartCoupon']);
Route::get('get-coupons', [CartController::class, 'getCoupons']);
Route::get('get-cart', [CartController::class, 'getcart']);
Route::post('add-to-cart', [CartController::class, 'addtocart']);
Route::post('add-coupon', [CartController::class, 'addCoupon']);
Route::delete('remove-coupon', [CartController::class, 'removeCoupon']);
Route::post('update-cart', [CartController::class, 'update']);
Route::delete('delete-cart', [CartController::class, 'destroy']);
Route::post('checkout-store-address', [CartController::class, 'storeAddress']);
Route::post('checkout-edit-address', [CartController::class, 'editAddressById']);
Route::get('checkout-get-address-by-id', [CartController::class, 'getAddressById']);
/* Cart */

/* addresses */
Route::get('addresses', [AccountController::class, 'addresses']);
Route::post('store-address', [AccountController::class, 'storeAddress']);
Route::post('edit-address', [AccountController::class, 'editAddressById']);
Route::get('get-address-by-id', [AccountController::class, 'getAddressById']);
Route::delete('delete-address-by-id', [AccountController::class, 'deleteAddressById']);
/* addresses */

/* order */
Route::get('payment-methods', [CartController::class, 'paymentmethods']);
Route::post('store-order',[ OrderController::class, 'store']);
Route::post('store-whatsapp-order',[ OrderController::class, 'storeWhatsAppOrder']);
Route::post('cancel-order',[ OrderController::class, 'cancelorder']);
Route::post('update-payment-details',[ OrderController::class, 'updatePayment']);
Route::get('order-summary/{order_encrypt_key}', [OrderController::class, 'orderSummary']);
/* order */
    
Route::middleware('auth:sanctum')->group( function () {
    /* Account */
    Route::get('orders', [AccountController::class, 'orders']);
    Route::post('cancel-order',[ AccountController::class, 'cancelorder']);
    Route::get('wishlist', [AccountController::class, 'wishlist']);
    Route::delete('delete-wishlist', [AccountController::class, 'deleteWishlistById']);
    Route::post('add-wishlist', [AccountController::class, 'addWishlist']);
    Route::get('order-details', [AccountController::class, 'orderDetails']);
    Route::post('update-user-password', [AccountController::class, 'updatePassword']);
    Route::post('update-profile',[ AccountController::class, 'profileUpdate']);
    /* Account */
});
