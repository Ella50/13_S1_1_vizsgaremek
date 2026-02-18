<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\Api\PersonalOrderController;
use App\Http\Controllers\Api\OrdersController;
use App\Http\Controllers\AdminRfidController;
use App\Http\Controllers\LunchTimeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Api\UserHealthController;
use App\Models\User;
use App\Http\Controllers\AdminInvoiceController;

// Publikus utvonalak
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/allergens', [UserHealthController::class, 'getAllergens']);

Route::post('/reset-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password/confirm', [PasswordResetController::class, 'reset']);
// Opcionális tokenellenőrzés???
Route::post('/reset-password/check-token', [PasswordResetController::class, 'checkToken']);


//Menü utvonalak (nem a sanctumba van ezért publikusak??)

Route::prefix('menu')->group(function () {
    Route::get('/available-dates', [MenuController::class, 'availableDates']);
    Route::get('/existing-dates', [MenuController::class, 'existingDates']);
    Route::get('/{date}', [MenuController::class, 'getMenuByDate']) ->where('date', '^\d{4}-\d{2}-\d{2}$');
    Route::post('/', [MenuController::class, 'saveMenu']);
    Route::put('/{id}', [MenuController::class, 'store']);
    //Route::put('/{menu}', [MenuController::class, 'update']);

    Route::get('/today', [MenuController::class, 'getTodayMenu']);
    Route::get('/week', [MenuController::class, 'getWeeklyMenu']);
});





// védett utvonalak
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    
    

    //RFID
    Route::get('/admin/rfid/latest-scan', [AdminRfidController::class, 'latestScan']);
    Route::post('/admin/users/{id}/rfid/assign', [AdminRfidController::class, 'assign']);

    Route::get('/kitchen/rfid/latest-scan', [LunchTimeController::class, 'latestScan']);
    Route::post('/kitchen/lunchtime/verify', [LunchTimeController::class, 'verify']);
    Route::post('/kitchen/lunchtime/consume', [LunchTimeController::class, 'consume']);
  
    //Számla
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'pdf']);
    //Számla-Admin
    



  
    // User profil
    Route::prefix('user')->group(function () {
        Route::get('/me', [UserController::class, 'me']); //alapból AuthCotroller volt 
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/update', [UserController::class, 'updateProfile']);
        Route::put('/password', [UserController::class, 'changePassword']);


        Route::get('/health', [UserHealthController::class, 'getUserHealthData']);
        Route::get('/allergens', [UserHealthController::class, 'getUserAllergens']);
        Route::post('/allergens', [UserHealthController::class, 'addAllergen']); 
        Route::delete('/allergens/{id}', [UserHealthController::class, 'removeAllergen']); 
        Route::put('/diabetes', [UserHealthController::class, 'updateDiabetes']); 
    
    
    

        
        // Személyes rendelések
        Route::prefix('personal-orders')->group(function () {
            Route::get('/', [PersonalOrderController::class, 'index']);
            Route::post('/{id}/reorder', [PersonalOrderController::class, 'reorder']);
            Route::patch('/{order}/update-option', [PersonalOrderController::class, 'updateOption']);

            Route::get('/date/{date}', [PersonalOrderController::class, 'getByDate']);
            Route::get('/available-dates', [PersonalOrderController::class, 'getAvailableDates']);
            Route::get('/available-months', [PersonalOrderController::class, 'getAvailableMonths']);
            Route::get('/month/{year}/{month}', [PersonalOrderController::class, 'getAvailableDatesByMonth']);
            Route::post('/', [PersonalOrderController::class, 'store']);
            Route::patch('/{order}/update-option', [PersonalOrderController::class, 'updateOption']);
            Route::delete('/{order}', [PersonalOrderController::class, 'destroy']); // Ez a destroy
            Route::delete('/{order}/cancel', [PersonalOrderController::class, 'cancel']); // Ez az alias
            Route::post('/{order}/reorder', [PersonalOrderController::class, 'reorder']);

        });

        Route::prefix('personal-invoices')->group(function () {
            Route::get('/', [InvoiceController::class, 'userInvoices']);
            Route::get('/{invoice}/orders', [InvoiceController::class, 'invoiceOrders']);
            Route::get('/{invoice}/preview', [InvoiceController::class, 'previewInvoice']);
            Route::get('/{invoice}/download', [InvoiceController::class, 'downloadInvoice']);
        });

            
    
    
    });
    

    
    
    // Admin
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::post('/users/bulk-status', [AdminController::class, 'bulkUpdateUserStatus']);
        Route::put('/users/{user}/status', [AdminController::class, 'updateUserStatus']);

        Route::get('/users/{user}', [AdminController::class, 'getUserDetails']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
        Route::get('/counties', [AdminController::class, 'getCounties']);
        Route::get('/cities/by-county/{county_id}', [AdminController::class, 'getCitiesByCounty']);
        Route::get('/cities/search', [AdminController::class, 'searchCities']);

        //Számlázáshoz
        Route::get('/invoices', [AdminInvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [AdminInvoiceController::class, 'show']);
        Route::post('/invoices/generate-month', [AdminInvoiceController::class, 'generateMonth']);
        Route::get('/invoices/{invoice}/pdf', [AdminInvoiceController::class, 'downloadPdf']);
    });
    
    // Konyha
    Route::prefix('kitchen')->group(function () {

        Route::get('/meals', [KitchenController::class, 'getMeals']);
        Route::get('/meals/with-allergens', [KitchenController::class, 'mealsWithAllergens']);
        Route::post('/meals', [KitchenController::class, 'storeMeal']);
        Route::get('/meals/{id}', [KitchenController::class, 'showMeal']);
        Route::put('/meals/{id}', [KitchenController::class, 'updateMeal']);
        Route::delete('/meals/{id}', [KitchenController::class, 'deleteMeal']);

        Route::get('/meals/{id}/ingredients', [KitchenController::class, 'getMealIngredients']);
        Route::put('/meals/{id}/ingredients', [KitchenController::class, 'updateMealIngredients']);
        Route::get('/ingredients/search', [KitchenController::class, 'searchIngredients']);
        Route::get('/ingredients', [KitchenController::class, 'getAllIngredients']);
        
        Route::get('/categories', [KitchenController::class, 'getCategories']);

        // Konyhai rendelések összesítése
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrdersController::class, 'index']);
            Route::get('/today', [OrdersController::class, 'getTodaySummary']);
            Route::get('/date/{date}', [OrdersController::class, 'getByDate']);
            Route::get('/weekly', [OrdersController::class, 'getWeeklySummary']);
            Route::get('/preparation/{date}', [OrdersController::class, 'getPreparationList']);
            Route::get('/export', [OrdersController::class, 'exportOrders']);
            Route::get('/monthly-preparation/{year}/{month}', [OrdersController::class, 'getMonthlyPreparation']);
    
        });

   

        //Hozzávalók kezelése (ingredient.vue)
        Route::prefix('ingredients')->group(function () {
            Route::get('/', [KitchenController::class, 'getIngredientsList']);
            Route::post('/', [KitchenController::class, 'createIngredient']); 
            Route::get('/{id}', [KitchenController::class, 'showIngredientDetail']); 
            Route::put('/{id}', [KitchenController::class, 'updateIngredientItem']); 
            Route::delete('/{id}', [KitchenController::class, 'deleteIngredientItem']); 
            Route::post('/bulk-availability', [KitchenController::class, 'bulkUpdateIngredientAvailability']); 
        });


        Route::get('/rfid/latest-scan', [LunchTimeController::class, 'latestScan']);
        Route::post('/lunchtime/verify', [LunchTimeController::class, 'verify']);
        Route::post('/lunchtime/consume', [LunchTimeController::class, 'consume']);
        Route::get('/orders/today', [KitchenController::class, 'getTodayOrders']);

        });

});


/*
Route::post('/reset-password', [App\Http\Controllers\Api\PasswordResetController::class, 'sendResetLinkEmail']);

Route::post('/test-simple', function() { return response()->json(['ok' => true]); });

Route::post('/test-simple', function() { return response()->json(['ok' => true]); });

Route::post('/test-simple', function() { return response()->json(['ok' => true]); });

Route::post('/test-simple', function() { return response()->json(['ok' => true]); });

Route::post("/api/simple-password-reset", [App\Http\Controllers\VerySimplePasswordController::class, "sendReset"]);

Route::post("/test-password", function(Illuminate\Http\Request $request) {
    return response()->json([
        "status" => "OK",
        "message" => "Backend működik!",
        "received_email" => $request->input("email", "nincs"),
        "server_time" => date("Y-m-d H:i:s")
    ]);
});*/