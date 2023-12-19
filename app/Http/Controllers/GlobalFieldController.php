<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGlobalFieldRequest;
use App\Http\Requests\UpdateGlobalFieldRequest;
use App\Models\GlobalField;

class GlobalFieldController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreGlobalFieldRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreGlobalFieldRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\GlobalField  $globalField
     * @return \Illuminate\Http\Response
     */
    public function show(GlobalField $globalField)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\GlobalField  $globalField
     * @return \Illuminate\Http\Response
     */
    public function edit(GlobalField $globalField)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateGlobalFieldRequest  $request
     * @param  \App\Models\GlobalField  $globalField
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateGlobalFieldRequest $request, GlobalField $globalField)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\GlobalField  $globalField
     * @return \Illuminate\Http\Response
     */
    public function destroy(GlobalField $globalField)
    {
        //
    }
}
