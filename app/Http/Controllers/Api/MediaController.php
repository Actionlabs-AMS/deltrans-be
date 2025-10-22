<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MediaLibrary;
use Illuminate\Http\Request;
use App\Services\MediaService;
use App\Services\MessageService;

class MediaController extends Controller
{
	protected $mediaService;
  protected $messageService;

	public function __construct(MediaService $mediaService, MessageService $messageService)
  {
    $this->mediaService = $mediaService;
    $this->messageService = $messageService;
  }
	/**
	 * Display a listing of the resource.
	 */
	public function index()
	{
		try {
			return $this->mediaService->list();
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
		try {
			$user = $request->user();
			$files = $request->file('files');

			$media = $this->mediaService->uploadFiles($files, $user);
			return response($media, 201);

		} catch (\Exception $e) {
      return $this->messageService->responseError();
    }
	}

	/**
	 * Display the specified resource.
	 */
	public function show($id)
	{
		try {
			return $this->mediaService->show($id);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update($id, Request $request)
	{
		try {
			$mediaLibrary = $this->mediaService->update($request->all(), $id);
			return response($mediaLibrary, 201);
		} catch (\Exception $e) {
      return $this->messageService->responseError();
    }
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy($id)
	{
		try {
			$this->mediaService->destroy($id);
			$message = 'Media has been deleted permanently.';
      return response(compact('message'));
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
	}

	public function bulkDelete(Request $request) 
	{
		try {
			$this->mediaService->bulkDelete($request->ids);
			$message = 'Media/s has been deleted permanently.';
      return response(compact('message'));
		} catch (\Exception $e) {
      return $this->messageService->responseError();
    }
	}

	public function dateFolder() 
	{
		try {
			return $this->mediaService->folderList();
		} catch (\Exception $e) {
      return $this->messageService->responseError();
    }
	}
}
