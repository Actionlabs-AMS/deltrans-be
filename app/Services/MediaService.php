<?php

namespace App\Services;

use App\Models\User;
use App\Models\MediaLibrary;
use App\Http\Resources\MediaResource;
use Illuminate\Support\Facades\Storage;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Filters\Frame\CustomFrameFilter;
use FFMpeg\Format\Video\Ogg;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use Image;
use App\Helpers\S3Helper;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class MediaService extends BaseService
{
	protected $useS3;

	public function __construct()
  {
      // Pass the UserResource class to the parent constructor
      parent::__construct(new MediaResource(new MediaLibrary), new MediaLibrary());
      $this->useS3 = !empty(config('filesystems.disks.s3.bucket'));
  }
  /**
  * Retrieve all resources with paginate.
  */
  public function list($perPage = 10)
  {
    $allMedia = MediaLibrary::query()->get()->count();
    $perPage = (request('mode') == 'list') ? 10 : 27;

    return MediaResource::collection(MediaLibrary::query()->when(request('search'), function ($query) {
      return $query->where('file_name', 'LIKE', '%' . request('search') . '%')
                    ->orWhere('file_type', 'LIKE', '%' . request('search') . '%')
                    ->orWhere('file_dimensions', 'LIKE', '%' . request('search') . '%')
                    ->orWhere('caption', 'LIKE', '%' . request('search') . '%')
                    ->orWhere('short_descriptions', 'LIKE', '%' . request('search') . '%');
    })
    ->when(request('type'), function ($query) {
      return $query->where('file_type', 'LIKE', '%' . request('type') . '%');
    })
    ->when(request('date'), function ($query) {
      $timestamp = strtotime(request('date'));
      $year = date("Y",$timestamp);
      $month = date("m",$timestamp);

      return $query->whereYear('created_at', '=', $year)
                    ->whereMonth('created_at', '=', $month);
    })
    ->when(request('order'), function ($query) {
      return $query->orderBy(request('order'), request('sort'));
    })
    ->when(!request('order'), function ($query) {
      return $query->orderBy('id', 'desc');
    })
    ->paginate($perPage)->withQueryString())
    ->additional(['meta' => ['media_total' => $allMedia]]);
  }

  public function uploadFiles(array $files, User $user) 
  {
    foreach($files as $file) {
      $mime_type = explode("/", $file->getClientMimeType());
      if ($this->useS3) {
        $data = $this->saveToS3($file, $user->id, $mime_type[0]);
      } else {
        switch($mime_type[0]) {
          case 'image':
            $data = $this->saveImage($file, $user->id);
            break;
          case 'video':
            $data = $this->saveVideo($file, $user->id);
            break;
          default:
            $data = $this->saveFile($file, $user->id);
        }
      }
      $media = MediaLibrary::create($data);
    }
    return new MediaResource($media);
  }

  public function getStoragePath() 
	{
		$yr = date('Y');
		$mon = date('m');
		$path = Storage::disk('public')->path($yr.'/'.$mon);
		if(!Storage::exists($path)) {
			Storage::disk('public')->makeDirectory($yr.'/'.$mon);
		}

		return [
			'storage_path' => $path,
			'public_path' => 'storage/'.$yr.'/'.$mon
		];
	}

	public function saveImage($file, $id) 
	{
		$timestamp = time();
		$path = $this->getStoragePath();

		$filename =  $file->getClientOriginalName();
		$file_type = $file->getClientMimeType();
		$mime_type = explode("/", $file->getClientMimeType());
		$file_size = $file->getSize();

		$file_path = $file->move($path['storage_path'], $timestamp.'-'.$filename);
		$image_size = getimagesize($file_path);

		$img = Image::make($file_path);
		$img->resize(150, 150, function ($constraint) {
				$constraint->aspectRatio();
		})->save($path['storage_path'].'/'.$timestamp.'-150x150-'.$filename);

		$data = [
			'user_id' => $id,
			'file_name' => $timestamp.'-'.$filename,
			'file_type' => $file_type,
			'file_size' => $file_size,
			'width' => $image_size[0],
			'height' => $image_size[1],
			'file_dimensions' => $image_size[0].'x'.$image_size[1],
			'file_url' => asset($path['public_path'].'/'.$timestamp.'-'.$filename),
			'thumbnail_url' => asset($path['public_path'].'/'.$timestamp.'-150x150-'.$filename)
		];

		return $data;
	}

	public function saveVideo($file, $id) 
	{
		$timestamp = time();
		$path = $this->getStoragePath();

		$filename =  $file->getClientOriginalName();
		$video_filename = pathinfo($filename, PATHINFO_FILENAME);

		$file_type = $file->getClientMimeType();
		$mime_type = explode("/", $file->getClientMimeType());
		$file_size = $file->getSize();

		$ffmpeg = FFMpeg::create([
			'ffmpeg.binaries' => config('app.ffmpeg'),
			'ffprobe.binaries' => config('app.ffprobe'),
		]);

		$file_path = $file->move($path['storage_path'], $timestamp.'-'.$filename);
		// VIDEO CONVERSION PART
		$video = $ffmpeg->open($file_path);
		$dimensions = $video->getStreams()->videos()->first();
		$video->frame(TimeCode::fromSeconds(1))
					->addFilter(new CustomFrameFilter('scale=150x150'))
					->save($path['storage_path'].'/'.$timestamp.'-150x150-'.$video_filename.'.jpg');
		$video->save(new WebM(), $path['storage_path'].'/'.$timestamp.'-'.$video_filename.'.webm');

		$data = [
			'user_id' => $id,
			'file_name' => $timestamp.'-'.$filename,
			'file_type' => $file_type,
			'file_size' => $file_size,
			'width' => $dimensions->get('width'),
			'height' => $dimensions->get('height'),
			'file_dimensions' => $dimensions->get('width').'x'.$dimensions->get('height'),
			'file_url' => asset($path['public_path'].'/'.$timestamp.'-'.$video_filename.'.webm'),
			'thumbnail_url' => asset($path['public_path'].'/'.$timestamp.'-150x150-'.$video_filename.'.jpg')
		];

		return $data;
	}

	public function saveFile($file, $id) 
	{
		$timestamp = time();
		$path = $this->getStoragePath();

		$filename =  $file->getClientOriginalName();
		$file_type = $file->getClientMimeType();
		$mime_type = explode("/", $file->getClientMimeType());
		$file_size = $file->getSize();

		$file_path = $file->move($path['storage_path'], $timestamp.'-'.$filename);
		$default_icon = '';
		switch($mime_type[0]) {
			case 'audio':
				$default_icon = '/assets/img/mp3-icon.png';
				break;
			case 'application' :
			case 'text':
				$default_icon = '/assets/img/file-icon.png';
				break;		
		}

		$data = [
			'user_id' => $id,
			'file_name' => $timestamp.'-'.$filename,
			'file_type' => $file_type,
			'file_size' => $file_size,
			'file_url' => asset($path['public_path'].'/'.$timestamp.'-'.$filename),
			'thumbnail_url' => asset($default_icon)
		];

		return $data;
	}

	protected function saveToS3($file, $userId, $type)
  {
    $folder = 'PATHCAST-PROJECT';
    $filename = $file->getClientOriginalName();
    $file_type = $file->getClientMimeType();
    $file_size = $file->getSize();
    // Save file to a temp location first
    $tmpPath = $file->storeAs('tmp', uniqid() . '-' . $filename);
    $tmpFullPath = storage_path('app/' . $tmpPath);

    // Optimize image before upload if type is image
    if ($type === 'image') {
      $img = Image::make($tmpFullPath);
      $img->save($tmpFullPath, 85);
      $optimizerChain = OptimizerChainFactory::create();
      $optimizerChain->optimize($tmpFullPath);
    }

    $url = S3Helper::uploadFile($tmpFullPath, $filename, $folder);
    @unlink($tmpFullPath);
    $data = [
      'user_id' => $userId,
      'file_name' => $filename,
      'file_type' => $file_type,
      'file_size' => $file_size,
      'file_url' => $url,
      'thumbnail_url' => $url, // S3: no thumb, use main url or generate thumb if needed
    ];
    if ($type === 'image') {
      $image = getimagesize($file->getPathname());
      $data['width'] = $image[0];
      $data['height'] = $image[1];
      $data['file_dimensions'] = $image[0] . 'x' . $image[1];
    } elseif ($type === 'video') {
      $data['width'] = null;
      $data['height'] = null;
      $data['file_dimensions'] = null;
    }
    return $data;
  }

  /**
  * Storage folders.
  */
  public function folderList() 
  {
    $dates = [];
		$directories = Storage::disk('public')->directories('/');
		foreach($directories as $directory) {
			$sub_directories = Storage::disk('public')->directories('/'.$directory);
			foreach($sub_directories as $sub){
				$dates[] = [
					'value' => date_format(date_create(str_replace("/","-",$sub)."-01"), "d-m-Y"),
					'label' => date_format(date_create(str_replace("/","-",$sub)."-01"), "F Y")
				];
			}
		}
		return $dates;
  }
}