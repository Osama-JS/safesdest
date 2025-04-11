<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FunctionsController extends Controller
{
  public function convert($file, $path)
  {
    $extension = $file->getClientOriginalExtension();
    if (in_array($extension, ["jpeg", "jpg", "png"])) {

      //old image
      $result = $file->store($path, 'public');
      $webp = 'storage/' . $result;

      $im = imagecreatefromstring(file_get_contents($webp));
      imagepalettetotruecolor($im);

      // have exact value with WEBP extension
      $new_webp = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $webp);
      //del old image
      unlink($webp);

      if (imagewebp($im, $new_webp, 50)) {
        // echo "coooool";
        return $new_webp;
      }
      return 0;
    } else {
      $result = $file->store($path, 'public');
      $webp = 'storage/' . $result;
      // echo "no";
      return $webp;
    }
  }
}
