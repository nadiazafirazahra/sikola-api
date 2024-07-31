<?php

namespace App\Http\Controllers\Api;

use App\Models\m_category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\MCategoryResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class MCategoryController extends Controller
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        //get all category
        $m_categories = m_category::latest()->paginate(5);

        //return collection of category as a resource
        return new MCategoryResource(true, 'List Data Category', $m_categories);
    }

    /**
     * store
     *
     * @param  mixed $request
     * @return void
     */
    public function store(Request $request)
    {
        //define validation rules
        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'content'   => 'required',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //create category
        $m_category = m_category::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MCategoryResource(true, 'Data Category Berhasil Ditambahkan!', $m_category);
    }

    /**
     * show
     *
     * @param  mixed $id
     * @return void
     */
    public function show($id)
    {
        //find category by ID
        $m_category = m_category::find($id);

        //return single category as a resource
        return new MCategoryResource(true, 'Detail Data Category!', $m_category);
    }

    /**
     * update
     *
     * @param  mixed $request
     * @param  mixed $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        //define validation rules
        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'content'   => 'required',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //find category by ID
        $m_category = m_category::find($id);

            //update category with new image
            $m_category->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);


        //return response
        return new MCategoryResource(true, 'Data Category Berhasil Diubah!', $m_category);
    }

    /**
     * destroy
     *
     * @param  mixed $id
     * @return void
     */
    public function destroy($id)
    {

        //find category by ID
        $m_category = m_category::find($id);

        //delete category
        $m_category->delete();

        //return response
        return new MCategoryResource(true, 'Data Category Berhasil Dihapus!', null);
    }
}
