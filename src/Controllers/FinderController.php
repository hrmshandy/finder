<?php

namespace Hrmshandy\Finder\Controllers;

use Hrmshandy\Finder\Finder;
use League\Glide\Server;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FinderController extends Controller
{
    protected $finder;

    function __construct(Finder $finder)
    {
    	$this->finder = $finder;
    }

    public function index($folder = '/')
    {
        if(request()->ajax()) {
            return $this->finder->scan($folder);
        }
        return view('finder::finder');
    }

    public function downloadFile($path)
    {
        return $this->finder->downloadFile($path);
    }

    public function saveFile()
    {
        $file = request()->file('file');
        $folder = request()->folder;

        try {
            $result = $this->finder->folder($folder)->saveFile($file);
            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteFile($path)
    {
        try {
            $this->finder->deleteFile($path);
            return response()->json(['status' => 200, 'message' => 'The file has been deleted.'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function getFile(Server $server, Request $request, $path) {
	    $server->outputImage($path, $request->all());
	}
}
