<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Models\Folder;
use App\Http\Requests\FolderRequest;
use PhpParser\Node\Scalar\String_;

class FolderController extends BaseController
{
    public function index()
    {
        return Folder::all();
    }
    public function store(FolderRequest $request)
    {
        $value = $request->validated();
        if (!in_array('users', $value)){
            $value['users'] = json_encode([]);
        }
        $folder = Folder::create($value);
        return $this::sendResponse([
            'id' => $folder['id']
        ],'OK');
    }
    public function show(Folder $folder)
    {
        return $folder = Folder::findOrFail($folder);
    }

    public function update(FolderRequest $request, String $id)
    {
        $folder = Folder::findOrFail($id);
        $folder->fill($request->except(['folder_id']));
        $folder->save();
        return response()->json($folder);
    }

    public function destroy(Folder $folder,$id)
    {
        $folder = Folder::findOrFail($id);
        if($folder->delete()) return response(null, 204);
    }
}
