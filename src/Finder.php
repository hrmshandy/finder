<?php

namespace Hrmshandy\Finder;

use Carbon\Carbon;
use Hrmshandy\Finder\Helpers\Str;
use Hrmshandy\Finder\Helpers\File;
use Hrmshandy\Finder\Helpers\Path;
use Hrmshandy\Finder\Exceptions\FileTooBig;
use Hrmshandy\Finder\Exceptions\FileDoesNotExist;
use Hrmshandy\Finder\Exceptions\FileCannotBeImported;
use Hrmshandy\Finder\Exceptions\FileNotAllowed;
use Illuminate\Contracts\Filesystem\Factory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Finder
{
	/**
     * @var \Illuminate\Contracts\Filesystem\Factory
     */
    protected $fileSystem;

	/**
     * @var string|\Symfony\Component\HttpFoundation\File\UploadedFile
     */
    protected $file;

    /**
     * @var string
     */
    protected $pathToFile;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var string
     */
    protected $fileExtension;

    /**
     * @var string
     */
    protected $folder = null;

    /**
     * @param Factory  $fileSystem
     */
    public function __construct(Factory $fileSystem)
    {
    	$disk = config('filesystems.default');
        $this->fileSystem = $fileSystem->disk($disk);
    }

    public function scan($folder)
    {
    	$folder = $this->cleanFolder($folder);
        $breadcrumbs = $this->breadcrumbs($folder);
        $slice = array_slice($breadcrumbs, -1);
        $folderName = current($slice);
        $breadcrumbs = array_slice($breadcrumbs, 0, -1);
        $subfolders = [];
        foreach (array_unique($this->fileSystem->directories($folder)) as $subfolder) {
            $hiddenfolder = strpos($subfolder, '.');
            $excludeFolder = config('finder.exclude.folders');
            if($hiddenfolder !== 0 and !in_array($subfolder, $excludeFolder)){
                $subfolders[] = $this->folderDetails($subfolder);
            }
        }
        $files = [];
        foreach ($this->fileSystem->files($folder) as $path) {
            $excludeFile = config('finder.exclude.files');
            if(!in_array($path, $excludeFile)){
                $files[] = $this->fileDetails($path);
            }
        }

        // $paginator = $this->buildPagination($files, config('filemanager.per_page'));

        // $files = $paginator->getCollection();

        return compact('folder', 'folderName', 'breadcrumbs', 'subfolders', 'files');
    }

    protected function buildPagination($items, $perPage = 10)
    {
    	$collection = collect($items);
    	$page = request()->input('page', 1);

    	$paginator = new LengthAwarePaginator(
    						$collection->forPage($page, $perPage),
    						$collection->count(),
    						$perPage,
    						$page,
    						['path' => request()->url(), 'query' => request()->query()]);

	    return $paginator;
    }

    /**
     * Sanitize the folder name
     */
    protected function cleanFolder($folder)
    {
        return '/' . trim(str_replace('..', '', $folder), '/');
    }

    /**
     * Return breadcrumbs to current folder
     */
    protected function breadcrumbs($folder)
    {
        $folder = trim($folder, '/');
        $crumbs = ['/' => 'Root'];
        if (empty($folder)) {
            return $crumbs;
        }
        $folders = explode('/', $folder);
        $build = '';
        foreach ($folders as $folder) {
            $build .= '/' . $folder;
            $crumbs[$build] = $folder;
        }
        return $crumbs;
    }

    /**
     * return an array of folder details.
     * @param  string $folder
     * @return array
     */
    protected function folderDetails($folder)
    {
    	$path = '/' . ltrim($folder, '/');
        return [
            'id' => uniqid(),
            'name' => basename($folder),
            'fullPath' => $path,
            'type' => 'folder',
            'items' => count($this->scan($folder)['files']) + count($this->scan($folder)['subfolders'])
        ];
    }

    /**
     * Return an array of file details for a file.
     *
     * @return array
     */
    protected function fileDetails($path)
    {
        $path = '/' . ltrim($path, '/');
        return [
            'id' => uniqid(),
            'name' => basename($path),
            'fullPath' => $path,
            'mimeType' => File::getMimeType($path),
            'extension' => File::getExtension($path),
            'size' => File::getHumanReadableSize($this->fileSize($path)),
            'modified' => $this->fileModified($path),
        ];
    }

    /**
     * Return the last modified time.
     */
    public function fileModified($path)
    {
        return Carbon::createFromTimestamp(
            $this->fileSystem->lastModified($path)
        );
    }

    /**
     * Return the file size.
     */
    public function fileSize($path)
    {
        return $this->fileSystem->size($path);
    }

    /**
     * Set the file that needs to be imported.
     *
     * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     *
     * @return $this
     *
     * @throws FileCannotBeImported
     */
    public function setFile($file)
    {
    	$this->file = $file;

        if (is_string($file)) {
            $this->pathToFile = $file;
            $this->setBasicFileInfo(pathinfo($file, PATHINFO_BASENAME));

            return $this;
        }

        if ($file instanceof UploadedFile) {
            $this->pathToFile = $file->getPath().'/'.$file->getFilename();
            $this->setBasicFileInfo($file->getClientOriginalName());

            return $this;
        }

        // if ($file instanceof File) {
        //     $this->pathToFile = $file->getPath().'/'.$file->getFilename();
        //     $this->baseName = pathinfo($file->getFilename(), PATHINFO_BASENAME);

        //     return $this;
        // }

        throw new FileCannotBeImported('Only strings, FileObjects and UploadedFileObjects can be imported');
    }

    /**
     * Set fodler
     * @param  string $folder
     * @return $this
     */
    public function folder($folder = null)
    {
    	$this->folder = $folder;
    	return $this;
    }

    /**
     * upload file
     * @param  [type] $file [description]
     * @return [type]       [description]
     */
    public function saveFile($file)
    {
    	$this->setFile($file);

        $filename = $this->fileName;
        $ext = $this->fileExtension;

        $directory = $this->folder;
        $path = Path::tidy($directory . '/' . $filename . '.' . $ext);

        $maxFileSize = config('finder.max_file_size');
        if (filesize($this->pathToFile) > $maxFileSize) {
            throw new FileTooBig('File too large, file size should not be more than '.File::getHumanReadableSize($maxFileSize));
        }

        $allowedExtensions = config('finder.allowed_extensions');
        if(!in_array($ext, $allowedExtensions)) {
            $message = "$filename.$ext has an invalid extension. Valid extension(s): ".implode(', ', $allowedExtensions);
            throw new FileNotAllowed($message);
        }

        // If the file exists, we'll append a timestamp to prevent overwriting.
        if ($this->fileSystem->exists($path)) {
            $basename = $filename . '-' . time() . '.' . $ext;
            $path = Path::assemble($directory, $basename);
        }

        $path = Str::removeLeft($path, '/');
    	$content = file_get_contents($this->pathToFile);

    	if($this->fileSystem->put($path, $content)) {
            return $this->fileDetails($path);
        }

    	return false;
    }

    /**
     * Delete a file.
     */
    public function deleteFile($path)
    {
        $path = $this->cleanFolder($path);
        if (!$this->fileSystem->exists($path)) {
            throw new FileDoesNotExist('File does not exist.');
        }
        return $this->fileSystem->delete($path);
    }

    /**
     * Download a file.
     */
    public function downloadFile($path){
        $path = $this->cleanFolder($path);
        $parts = explode('/', $path);
        $filename = array_pop($parts);
        $content = $this->fileSystem->get($path);
        $mime = $this->fileSystem->mimeType($path);
        if (!$this->fileSystem->exists($path)) {
            throw new FileDoesNotExist('File does not exist.');
        }
        return response($content, 200)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    /**
     * Set the name of the file that is stored on disk.
     *
     * @param $fileName
     *
     * @return $this
     */
    public function setBasicFileInfo($basename)
    {
    	$filename = pathinfo($basename)['filename'];
        $this->fileName = File::sanitizeFileName($filename);
        $this->fileExtension = File::getExtension($basename);

        return $this;
    }
}