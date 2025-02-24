<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Store the image in the 'public' disk and get the file path
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time().'.'.$image->getClientOriginalExtension();

            // Move the file to public/images/ instead of storage/app/public
            $image->move(public_path('images'), $filename);

            return response()->json([
                'message' => 'Image uploaded successfully!',
                'image_path' => '/images/'.$filename,
            ], 200);
        }

        return response()->json(['message' => 'No image file provided'], 400);
    }
}
