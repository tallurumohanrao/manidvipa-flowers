<?php
use App\Http\Controllers\Admin\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('index');
Route::get('/clear', [DashboardController::class, 'clear'])->name('clear');
Route::get('/down', [DashboardController::class, 'down'])->name('down');
Route::get('/up', [DashboardController::class, 'up'])->name('up');
Route::get('/account', [App\Http\Controllers\Admin\AccountController::class, 'index'])->name('account');
Route::get('/account/edit', [App\Http\Controllers\Admin\AccountController::class, 'edit'])->name('account.edit');
Route::patch('/account/{account}/edit', [App\Http\Controllers\Admin\AccountController::class, 'update'])->name('account.update');
Route::post('/changepassword', [App\Http\Controllers\Admin\ChangePasswordController::class, 'changePassword'])->name('changepassword');

Route::get('/settings', [App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
Route::post('/settings', [App\Http\Controllers\Admin\SettingController::class, 'store'])->name('settings.store');

/* ----------------- Addons ------------------*/
Route::resource('addons', AddonController::class);
Route::patch('/addons/update-status/{id}', [App\Http\Controllers\Admin\AddonController::class, 'updateStatus'])->name('addons.update.status');
Route::delete('addons-delete-all', [App\Http\Controllers\Admin\AddonController::class, 'massDestroy'])->name('addons.massdestroy');
/* ----------------- Addons ------------------*/

/* ----------------- Banners ------------------*/
Route::resource('banners', BannerController::class);
Route::patch('/banners/update-status/{id}', [App\Http\Controllers\Admin\BannerController::class, 'updateStatus'])->name('banners.update.status');
Route::delete('banners-delete-all', [App\Http\Controllers\Admin\BannerController::class, 'massDestroy'])->name('banners.massdestroy');
/* ----------------- Banners ------------------*/

/* Order */
Route::resource('orders', OrderController::class);
Route::resource('orderstatuses', OrderStatusController::class);
Route::resource('paymentstatuses', PaymentStatusController::class);
Route::resource('shippingstatuses', ShippingStatusController::class);
Route::resource('shippingprices', ShippingPriceController::class);
Route::patch('/shippingprices/update-status/{id}', [App\Http\Controllers\Admin\ShippingPriceController::class, 'updateStatus'])->name('shippingprices.update.status');
Route::delete('shippingprices-delete-all', [App\Http\Controllers\Admin\ShippingPriceController::class, 'massDestroy'])->name('shippingprices.massdestroy');

Route::resource('featuredproducts', FeaturedProductController::class);
Route::delete('featuredproducts-delete-all', [App\Http\Controllers\Admin\FeaturedProductController::class, 'massDestroy'])->name('featuredproducts.massdestroy');

/* ----------------- Subscriptions ------------------*/
Route::resource('subscriptionplans', App\Http\Controllers\Admin\SubscriptionPlanController::class);
Route::patch('/subscriptionplans/update-status/{id}', [App\Http\Controllers\Admin\SubscriptionPlanController::class, 'updateStatus'])->name('subscriptionplans.update.status');
Route::delete('subscriptionplans-delete-all', [App\Http\Controllers\Admin\SubscriptionPlanController::class, 'massDestroy'])->name('subscriptionplans.massdestroy');

Route::resource('subscriptionenquiries', App\Http\Controllers\Admin\SubscriptionEnquiryController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
Route::delete('subscriptionenquiries-delete-all', [App\Http\Controllers\Admin\SubscriptionEnquiryController::class, 'massDestroy'])->name('subscriptionenquiries.massdestroy');
/* ----------------- Subscriptions ------------------*/

Route::patch('/shipping/update/{id}', [App\Http\Controllers\Admin\OrderController::class, 'updateShipping'])->name('orders.updateShipping');
Route::patch('/payment/update/{id}', [App\Http\Controllers\Admin\OrderController::class, 'updatePayment'])->name('orders.updatePayment');
Route::patch('/order/delivery/update/{id}', [App\Http\Controllers\Admin\OrderController::class, 'updateDeliveryPreference'])->name('orders.updateDeliveryPreference');
Route::patch('/order/update/{id}', [App\Http\Controllers\Admin\OrderController::class, 'updateBooking'])->name('orders.updateBooking');
Route::delete('orders-delete-all', [App\Http\Controllers\Admin\OrderController::class, 'massDestroy'])->name('orders.massdestroy');
/* Order */


/* ----------------- coupon ------------------*/
Route::resource('coupons', CouponController::class);
Route::patch('/coupons/update-status/{id}', [App\Http\Controllers\Admin\CouponController::class, 'updateStatus'])->name('coupons.update.status');
Route::delete('coupons-delete-all', [App\Http\Controllers\Admin\CouponController::class, 'massDestroy'])->name('coupons.massdestroy');
/* ----------------- coupon ------------------*/


/* ----------------- coupon ------------------*/
Route::resource('units', UnitController::class);
Route::patch('/units/update-status/{id}', [App\Http\Controllers\Admin\UnitController::class, 'updateStatus'])->name('units.update.status');
Route::delete('units-delete-all', [App\Http\Controllers\Admin\UnitController::class, 'massDestroy'])->name('units.massdestroy');
/* ----------------- coupon ------------------*/

/* ----------------- Categories ------------------*/
Route::resource('categories', CategoryController::class);
Route::patch('/categories/update-status/{id}', [App\Http\Controllers\Admin\CategoryController::class, 'updateStatus'])->name('categories.update.status');
Route::delete('categories-delete-all', [App\Http\Controllers\Admin\CategoryController::class, 'massDestroy'])->name('categories.massdestroy');
/* ----------------- Categories ------------------*/

/* ----------------- products ------------------*/
Route::resource('products', ProductController::class);
Route::get('/daily-prices', [App\Http\Controllers\Admin\DailyPriceController::class, 'index'])->name('dailyprices.index');
Route::post('/daily-prices', [App\Http\Controllers\Admin\DailyPriceController::class, 'update'])->name('dailyprices.update');
Route::get('/daily-prices/export', [App\Http\Controllers\Admin\DailyPriceController::class, 'export'])->name('dailyprices.export');
Route::post('/daily-prices/import', [App\Http\Controllers\Admin\DailyPriceController::class, 'import'])->name('dailyprices.import');
Route::post('/daily-prices/weights/{id}', [App\Http\Controllers\Admin\DailyPriceController::class, 'updateWeight'])->name('dailyprices.weights.update');
Route::post('/daily-prices/logs/{id}/rollback', [App\Http\Controllers\Admin\DailyPriceController::class, 'rollback'])->name('dailyprices.rollback');
Route::get('/products/images/{id}', [App\Http\Controllers\Admin\ProductController::class, 'images'])->name('products.images');
Route::post('/productImages/{id}', [App\Http\Controllers\Admin\ProductController::class, 'storeImage'])->name('products.imagesstore');
Route::patch('/product-images-update-sort', [App\Http\Controllers\Admin\ProductController::class, 'productImageUpdateSort'])->name('product.images.update.sort');

Route::get('/products/reviews/{id}', [App\Http\Controllers\Admin\ProductController::class, 'reviews'])->name('products.reviews');
Route::get('/products/sizes/{id}', [App\Http\Controllers\Admin\ProductController::class, 'sizes'])->name('products.sizes');
Route::post('/productsizes/{id}', [App\Http\Controllers\Admin\ProductController::class, 'storeSizes'])->name('products.sizesstore');
Route::delete('/products-size-delete/{id}', [App\Http\Controllers\Admin\ProductController::class, 'productsSizeDestroy'])->name('products.size.destroy');
Route::patch('/products-size/update-status/{id}', [App\Http\Controllers\Admin\ProductController::class, 'productsSizeUpdateStatus'])->name('products.size.update.status');

Route::get('/products/weights/{id}', [App\Http\Controllers\Admin\ProductController::class, 'weights'])->name('products.weights');
Route::post('/productweights/{id}', [App\Http\Controllers\Admin\ProductController::class, 'storeWeights'])->name('products.weightsstore');
Route::delete('/products-weight-delete/{id}', [App\Http\Controllers\Admin\ProductController::class, 'productsWeightDestroy'])->name('products.weight.destroy');
Route::patch('/products-weight/update-status/{id}', [App\Http\Controllers\Admin\ProductController::class, 'productsWeightUpdateStatus'])->name('products.weight.update.status');

Route::delete('/products-image-delete/{id}', [App\Http\Controllers\Admin\ProductController::class, 'productsImageDestroy'])->name('products.images.destroy');
Route::patch('/products-image/update-status/{id}', [App\Http\Controllers\Admin\ProductController::class, 'productsImageUpdateStatus'])->name('products.images.update.status');
Route::patch('/products-reviews/update-status/{id}', [App\Http\Controllers\Admin\ProductController::class, 'productsReviewUpdateStatus'])->name('products.review.update.status');
Route::delete('/products-reviews-delete/{id}', [App\Http\Controllers\Admin\ProductController::class, 'productsreviewsDestroy'])->name('products.reviews.destroy');
Route::patch('/products/update-status/{id}', [App\Http\Controllers\Admin\ProductController::class, 'updateStatus'])->name('products.update.status');
Route::delete('products-delete-all', [App\Http\Controllers\Admin\ProductController::class, 'massDestroy'])->name('products.massdestroy');
/* ----------------- products ------------------*/

/* ----------------- Role ------------------*/
Route::resource('roles', RoleController::class);
Route::patch('/roles/update-status/{id}', [App\Http\Controllers\Admin\RoleController::class, 'updateStatus'])->name('roles.update.status');
Route::delete('roles-delete-all', [App\Http\Controllers\Admin\RoleController::class, 'massDestroy'])->name('roles.massdestroy');


/* ----------------- seo ------------------*/
Route::resource('seo', SeoController::class);
Route::patch('/seo/update-status/{id}', [App\Http\Controllers\Admin\SeoController::class, 'updateStatus'])->name('seo.update.status');
Route::delete('seo-delete-all', [App\Http\Controllers\Admin\SeoController::class, 'massDestroy'])->name('seo.massdestroy');
/* ----------------- seo ------------------*/

/* ----------------- Users ------------------*/
Route::resource('users', UserController::class);
Route::patch('/users/update-status/{id}', [App\Http\Controllers\Admin\UserController::class, 'updateStatus'])->name('users.update.status');
Route::delete('users-delete-all', [App\Http\Controllers\Admin\UserController::class, 'massDestroy'])->name('users.massdestroy');
/* ----------------- Users ------------------*/
/* ----------------- Admins ------------------*/
Route::resource('admins', AdminController::class);
Route::patch('/admins/update-status/{id}', [App\Http\Controllers\Admin\AdminController::class, 'updateStatus'])->name('admins.update.status');
Route::delete('admins-delete-all', [App\Http\Controllers\Admin\AdminController::class, 'massDestroy'])->name('admins.massdestroy');
/* ----------------- Admins ------------------*/


/* ----------------- Faq ------------------*/
Route::resource('faqs', FaqController::class);
Route::patch('/faqs/update-status/{id}', [App\Http\Controllers\Admin\FaqController::class, 'updateStatus'])->name('faqs.update.status');
Route::delete('faqs-delete-all', [App\Http\Controllers\Admin\FaqController::class, 'massDestroy'])->name('faqs.massdestroy');
/* ----------------- Faq ------------------*/

/* ----------------- Permissions ------------------*/
Route::resource('permissions', PermissionController::class);
Route::patch('/permissions/update-status/{id}', [App\Http\Controllers\Admin\PermissionController::class, 'updateStatus'])->name('permissions.update.status');
Route::delete('permissions-delete-all', [App\Http\Controllers\Admin\PermissionController::class, 'massDestroy'])->name('permissions.massdestroy');
/* ----------------- Permissions ------------------*/

/* ----------------- posts ------------------*/
Route::resource('posts', PostController::class);
Route::patch('/posts/update-status/{id}', [App\Http\Controllers\Admin\PostController::class, 'updateStatus'])->name('posts.update.status');
Route::delete('posts-delete-all', [App\Http\Controllers\Admin\PostController::class, 'massDestroy'])->name('posts.massdestroy');
/* ----------------- posts ------------------*/

/* ----------------- services ------------------*/
Route::resource('services', ServiceController::class);
Route::patch('/services/update-status/{id}', [App\Http\Controllers\Admin\ServiceController::class, 'updateStatus'])->name('services.update.status');
Route::delete('services-delete-all', [App\Http\Controllers\Admin\ServiceController::class, 'massDestroy'])->name('services.massdestroy');
/* ----------------- services ------------------*/

Route::resource('pages', PageController::class);
Route::patch('/pages/update-status/{id}', [App\Http\Controllers\Admin\PageController::class, 'updateStatus'])->name('pages.update.status');

Route::resource('contentblocks', ContentBlockController::class);
Route::patch('/contentblocks/update-status/{id}', [App\Http\Controllers\Admin\ContentBlockController::class, 'updateStatus'])->name('contentblocks.update.status');

/* ----------------- Contacts ------------------*/
Route::resource('contacts', ContactController::class);
Route::delete('contacts-delete-all', [App\Http\Controllers\Admin\ContactController::class, 'massDestroy'])->name('contacts.massdestroy');
/* ----------------- Contacts ------------------*/

/* ----------------- Clients ------------------*/
Route::resource('clients', ClientController::class);
Route::patch('/clients/update-status/{id}', [App\Http\Controllers\Admin\ClientController::class, 'updateStatus'])->name('clients.update.status');
Route::delete('clients-delete-all', [App\Http\Controllers\Admin\ClientController::class, 'massDestroy'])->name('clients.massdestroy');
/* ----------------- Clients ------------------*/

/* ----------------- Testimonials ------------------*/
Route::resource('testimonials', TestimonialController::class);
Route::patch('/testimonials/update-status/{id}', [App\Http\Controllers\Admin\TestimonialController::class, 'updateStatus'])->name('testimonials.update.status');
Route::patch('/testimonials/update-sort', [App\Http\Controllers\Admin\TestimonialController::class, 'updateSort'])->name('testimonials.update.sort');
Route::delete('testimonials-delete-all', [App\Http\Controllers\Admin\TestimonialController::class, 'massDestroy'])->name('testimonials.massdestroy');
/* ----------------- Testimonials ------------------*/


/* ----------------- Gallery ------------------*/
Route::patch('/gallery/update-sort', [App\Http\Controllers\Admin\GalleryController::class, 'updateSort'])->name('gallery.update.sort');
Route::resource('gallery', GalleryController::class);
Route::delete('gallery-delete-all', [App\Http\Controllers\Admin\GalleryController::class, 'massDestroy'])->name('gallery.massdestroy');
Route::patch('/gallery/update-status/{id}', [App\Http\Controllers\Admin\GalleryController::class, 'updateStatus'])->name('gallery.update.status');
Route::patch('/gallery/is-before-after-status/{id}', [App\Http\Controllers\Admin\GalleryController::class, 'isBeforeAfterStatus'])->name('gallery.isbeforafter.status');
/* ----------------- Gallery ------------------*/


Route::post('ckeditor/upload', [App\Http\Controllers\Admin\DashboardController::class, 'upload'])->name('ckeditor.upload');
