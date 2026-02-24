npm install bootstrap bootstrap-vue-3
npm install vue-router vue-axios axios

composer require laravel/ui php artisan ui vue --auth
composer require laravel/sanctum
composer require barryvdh/laravel-dompdf
composer require andreaselia/laravel-api-to-postman --dev

php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
php artisan export:postman (exportot készít amit importálni lehet postman-be)



pip install pyserial