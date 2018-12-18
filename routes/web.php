<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
    // return view(view: 'welcome', ['website' => 'Laravel']);
});

Route::get('hello', function () 
{
    return 'Hello, Welcome to LaravelAcademy.org';
});

// 一个路由响应多种 HTTP 请求动作
Route::match(['get', 'post'], 'foo', function () 
{
    return 'This is a request from get or post';
});

Route::any('bar', function () 
{
    return 'This is a request from any HTTP verb';
});

// 路由重定向
// Route::redirect('/bar', '/hello', 301);

// 路由视图
// Route::view('view', 'welcome', ['website' => 'YYP']);

// test
// Route::get('lottery', 'Turntable@get_gift');
