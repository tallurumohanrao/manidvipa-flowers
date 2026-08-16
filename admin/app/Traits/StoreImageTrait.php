<?php

namespace App\Traits;

use Illuminate\Http\Request;
use File,Storage,Image,Str;

trait StoreImageTrait {

    /**
     * Does very basic image validity checking and stores it. Redirects back if somethings wrong.
     * @Notice: This is not an alternative to the model validation for this field.
     *
     * @param Request $request
     * @return $this|false|string
     */
    public function verifyAndStoreImage( Request $request, $fieldname = 'image', $directory = 'unknown' ) {
        /*$image = $request->file($fieldname);
        $imageName = time().'.'.$image->extension();

        $destinationPathThumbnail = public_path('thumbnail');
        $img = Image::make($image->path());
        $img->resize(100, 100, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPathThumbnail.'/'.$imageName);
        return $imageName;*/
        $old = 'old_'.$fieldname;
        $old_file = $request->$old;
        if( $request->hasFile( $fieldname ) ) {
            $file = $request->file($fieldname);
            if (!$request->file($fieldname)->isValid()) {
                return redirect()->back()->withInput();
            }
            $exp = explode('.',$file->getClientOriginalName());
            $extension = $request->file($fieldname)->getClientOriginalExtension();
            $mimeType = $request->file($fieldname)->getClientMimeType();
            if($extension == 'png'){
                $name = preg_replace("/[^a-zA-Z0-9]+/", "-",$exp['0']).time().'.jpg';
            }else{
                $name = preg_replace("/[^a-zA-Z0-9]+/", "-",$exp['0']) . time() .'.'. $extension;
            }
            if(Str::is('image/*',$mimeType) && !in_array($extension,['svg'])){
                //$name = $exp['0'].time().'.'.$file->getClientOriginalExtension();
                //$name = $exp['0'].time().'.jpg';
                #$name = $this->getNewFileName(preg_replace("/[^a-zA-Z0-9]+/", "-",$exp['0']).time(), $file->getClientOriginalExtension(), $directory);
                if(Image::make($file->getRealPath())->save(storage_path('app/public/'.$directory.'/'.$name), 50)){
                    if(!in_array($directory,['banners'])){
                        $sizes = ['100X100','280X280'];
                        foreach($sizes as $size) :
                            $destinationPathThumbnail = storage_path('app/public/'.$directory.'/'.$size);
                            $img = Image::make($file->path());
                            $hw = explode('X',$size);
                            $img->resize($hw[0], $hw[1], function ($constraint) {
                                $constraint->aspectRatio();
                            })->save($destinationPathThumbnail.'/'.$name);
                        endforeach;
                    }
                //if($file->storeAs( $directory , $name , 'public' )){
                    Storage::delete('public/'.$directory.'/'.$old_file);
                    return $name;
                }
            }else{
                if($file->storeAs( $directory , $name , 'public' )){
                    Storage::delete('public/'.$directory.'/'.$old_file);
                    return $name;
                }
            }
        }
        return $old_file;
    }

    public function verifyAndStoreImageOld( Request $request, $fieldname = 'image', $directory = 'unknown' ) {
        $old = 'old_'.$fieldname;
        $old_file = $request->$old;
        if( $request->hasFile( $fieldname ) ) {
            $file = $request->file($fieldname);
            if (!$request->file($fieldname)->isValid()) {
                return redirect()->back()->withInput();
            }
            $exp = explode('.',$file->getClientOriginalName());
            $name = $this->getNewFileName(preg_replace("/[^a-zA-Z0-9]+/", "-",$exp['0']).time(), $file->getClientOriginalExtension(), $directory);
            if($file->storeAs( $directory , $name , 'public' )){
                Storage::delete('public/'.$directory.'/'.$old_file);
                return $name;
            }
        }
        return $old_file;
    }

    public function verifyAndStoreMultipleImage( $file, $fieldname = 'image', $directory = 'unknown' ) {
        $exp = explode('.',$file->getClientOriginalName());
        $name = $this->getNewFileName($exp['0'], $file->getClientOriginalExtension(), $directory);
        if($file->storeAs( $directory , $name , 'public' )){
            return $name;
        }
    }

    public function getNewFileName($filename, $extension, $path)
    {
        $i = 1;
        $new_filename = $filename . '.' . $extension;
        while (File::exists(public_path('storage/'.$path.'/'.$new_filename))){
            $new_filename = $filename . $i++ . '.' . $extension;
        }
        return $new_filename;
    }

    public function getCurlAndStoreImage($filename)
    {
        if($filename){
        #ini_set('allow_url_fopen','on');
        $link = 'https://www.domain.com/assets/uploads/banks/'.$filename;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_POST, 0);
        #curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch,CURLOPT_URL,$link);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result=curl_exec($ch);

        curl_close($ch);

        $savefile = fopen($_SERVER['DOCUMENT_ROOT'].'/storage/app/public/banks/' . $filename, 'w');
        fwrite($savefile, $result);
        fclose($savefile);
        return $filename;
        }else{
            return null;
        }
    }


}
