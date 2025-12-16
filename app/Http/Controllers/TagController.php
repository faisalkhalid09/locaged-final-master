<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    // List all tags
    public function index()
    {
        Gate::authorize('viewAny', Tag::class);

        $tags = Tag::latest()->get();
        return view('tags.index', compact('tags'));
    }

    // Store a new tag
    public function store(Request $request)
    {
        Gate::authorize('create', Tag::class);


        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
        ]);

        Tag::create(['name' => $validated['name']]);

        return back()->with('success','Tag added succesfully');
    }



    // Update a tag
    public function update(Request $request, Tag $tag)
    {
        Gate::authorize('update', $tag);

        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:tags,name,' . $tag->id,
        ]);

        $tag->update($data);

        return redirect()->route('tags.index')->with('success', 'Tag updated.');
    }

    // Delete a tag
    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);
        Gate::authorize('delete', $tag);

        $tag->delete();

        return back()->with('success','Tag deleted successfully');
    }
}
