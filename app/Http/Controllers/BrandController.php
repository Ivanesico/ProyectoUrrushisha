<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\User;

class BrandController extends Controller {

    /**
     * Display a listing of the resource.
     */
    public function index() {
        $brands = Brand::query()
            ->withCount(['flavors as public_flavors_count' => fn ($query) => $query->where('is_public', true)])
            ->with(['flavors' => fn ($query) => $query
                ->select('id', 'brand_id', 'name', 'image_url')
                ->where('is_public', true)
                ->whereNotNull('image_url')
                ->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('brands.index', compact('brands'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand) {
        
        $flavors = $brand->flavors()->with(['category', 'tobaccoType'])->where('is_public', true)->orderBy('name')->get();
        return view('brands.show', compact('brand', 'flavors'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) {
        //
    }
}
