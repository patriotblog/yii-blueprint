<?php

/**
 * @author MohammadAli, Saeed
 *
 */
class gdUtility
{

    /**
     *
     * @param unknown $filename
     * @param unknown $string
     */
    public function CreateFile($filename, $string)
    {
        $filename = strtolower($filename);
        $handle = fopen($filename, "w");
        fwrite($handle, $string);
        fclose($handle);
    }

    /**
     *
     * @param unknown $folder
     */
    public function deleteThumbnails($folder)
    {
        $allfiles = glob($folder . '/*@sz*');
        foreach ($allfiles as $file)
            unlink($file);
    }

    /**
     *
     * @param unknown $folder
     * @param unknown $file
     * @param unknown $size
     */
    public function makeThumbnails($folder, $file, $size = array(100, 50))
    {
        $this->deleteThumbnails($folder);

        $jpeg_quality = 90;
        $img_r = imagecreatefromjpeg('./' . $folder . '/' . $file);
        $extension = substr($file, -4);
        $filename = time() . '_' . substr($file, 0, -4);
        foreach ($size as $sz) {
            if (is_array($sz)) {
                $w = $sz[0];
                $h = $sz[1];
                $thumb = imagecreatetruecolor($w, $h);
                imagecopyresampled($thumb, $img_r, 0, 0, 0, 0, $w, $h, imagesx($img_r), imagesy($img_r));
                //imagecopyresized($thumb, $img_r, 0, 0, 0, 0, $w, $h, imagesx($img_r), imagesy($img_r));
                $output_filename = './' . $folder . '/' . $filename . '@sz' . $h . $extension;
                imagejpeg($thumb, $output_filename, $jpeg_quality);
            } else {
                $thumb = imagecreatetruecolor($sz, $sz);
                imagecopyresampled($thumb, $img_r, 0, 0, 0, 0, $sz, $sz, imagesx($img_r), imagesy($img_r));
                //imagecopyresized($thumb, $img_r, 0, 0, 0, 0, $sz, $sz, imagesx($img_r), imagesy($img_r));
                $output_filename = './' . $folder . '/' . $filename . '@sz' . $sz . $extension;
                imagejpeg($thumb, $output_filename, $jpeg_quality);
            }
        }
    }

    /**
     *
     * @param unknown $folder
     */
    public function deleteCropedImages($folder)
    {
        $allfiles = glob($folder . '/*_*.???');
        foreach ($allfiles as $file)
            unlink($file);
    }

    /**
     *
     * @param unknown $folder
     * @param unknown $file
     * @param unknown $x
     * @param unknown $y
     * @param unknown $w
     * @param unknown $h
     * @param unknown $size
     */
    public function cropImage($folder, $file, $x, $y, $w, $h, $size = array(100, 50))
    {
        $this->deleteCropedImages($folder);

        $jpeg_quality = 90;
        $img_r = imagecreatefromjpeg('./' . $folder . '/' . $file);
        //$extension = substr($file, -4);
        //$filename = time().'_'.substr($file, 0, -4);
        $newfile = time() . '_' . $file;

        $thumb = imagecreatetruecolor($w, $h);
        imagecopyresampled($thumb, $img_r, 0, 0, $x, $y, $w, $h, $w, $h);
        //imagecopyresized($thumb, $img_r, 0, 0, $x, $y, $w, $h, $w, $h);
        $output_filename = './' . $folder . '/' . $newfile;
        imagejpeg($thumb, $output_filename, $jpeg_quality);

        $this->makeThumbnails($folder, $newfile, $size);
    }

    /**
     *
     * @param unknown $folder
     * @param unknown $id
     * @param string $sz
     * @return Ambigous <string, mixed>
     */
    public function getImagePath($folder, $id, $sz = '')
    {
        $filename = '';
        if (empty($sz)) {
            //$allfiles = glob($folder.'/*@'.$id.'.???');	// A
            $allfiles = glob($folder . '/' . $id . '/*.???');        // B
            $lastfile = end($allfiles);

            $extension = substr($lastfile, -4);            // B

            $t = substr(strrchr($lastfile, '/'), 1);
            $t = strstr($t, '@', true);
            $ta = explode('_', $t);
            if ($t) {
                //$filename = end($ta).'@'.$id;				// A
                $filename = $id . '/' . end($ta) . $extension;    // B
            }
            /*
             echo '<pre dir="ltr">';
             echo "folder=".$folder."\n";
             echo "id=".$id."\n";
             print_r($allfiles);
             print_r($ta);
             echo "t=".$t."\n";
             echo "filename=".$filename."\n";
             echo '</pre>';
             /**/
        } elseif ($sz == 'thumbs') {
            //$allfiles = glob($folder.'/*@'.$id.'@sz*.???');	// A
            $allfiles = glob($folder . '/' . $id . '/*@sz*.???');        // B
            $lastfile = end($allfiles);

            $extension = substr($lastfile, -4);            // B

            $t = substr(strrchr($lastfile, '/'), 1);
            $t = strstr($t, '@', true);
            //$filename = $t.'@'.$id.'@sz';				// A
            $filename = $id . '/' . $t . $extension;            // B
            /*
             echo '<pre dir="ltr">';
             echo "folder=".$folder."\n";
             echo "id=".$id."\n";
             print_r($allfiles);
             echo "t=".$t."\n";
             echo "filename=".$filename."\n";
             echo '</pre>';
             /**/
        } else {
            if ($sz == 'L')
                $sz = '140';
            elseif ($sz == 'M')
                $sz = '50';
            elseif ($sz == 'S')
                $sz = '32';

            //$allfiles = glob($folder.'/*@'.$id.'@sz'.$sz.'.jpg');	// A
            $allfiles = glob($folder . '/' . $id . '/*@sz' . $sz . '.???');    // B
            $filename = end($allfiles);
            if (empty($filename)) {
                if ($folder == 'a_pics/user') {
                    $filename = 'images/user' . $sz . '.png';
                }
                //elseif ($sz == '140')
                //	$filename = 'images/afsaran100.png';
                else
                    $filename = 'images/afsaran' . $sz . '.png';
            }
        }

        //	echo '<p dir="ltr">getImagePath return filename='.$filename.'</p>';
        return $filename;
        //return '/images/afsaran100.png';
        //return '/images/user100.jpg';
    }

    /**
     *
     * @param unknown $folder
     * @param unknown $id
     * @param number $s
     */
    public function imageAdd($folder, $id, $s = 0)
    {
        $filename = '';
        $allfiles = glob($folder . '/' . $id . '/*.???');        // B
        $lastfile = end($allfiles);

        $extension = substr($lastfile, -4);            // B

        $t = substr(strrchr($lastfile, '/'), 1);
        $t = strstr($t, '@', true);

        if ($t > '' || $t > 0) {
            if ($s > 0) {
                $thumb = $id . '/' . $t . '@sz' . $s . $extension;            // B
            } else {
                $thumb = $id . '/' . $t . $extension;            // B
            }
        } else {

            $thumb = '';
        }

        $ta = explode('_', $t);
        if ($t) {
            $name = end($ta);
            if ($name > '' || $name > 0) {
                $filename = $id . '/' . end($ta) . $extension;
            } else {
                $name = 0;
                $filename = '';
            }

        }

        if ($filename > '' && file_exists($folder . $filename)) {
            return array('filename' => AFS_BASE_URL . '/' . $folder . $filename, 'name' => $name, 'thumb' => AFS_BASE_URL . '/' . $folder . $thumb, 'ext' => $extension);
        } else {
            return array();
        }
    }

}