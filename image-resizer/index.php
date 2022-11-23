<html>
<head>
  <meta charset="utf-8">
  <title>Image Resizer Tool</title>
  <link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>


  <?php

  function calcSize($fw, $fh, $mw, $mh, $w, $h) {
    // fixed width, fixed height, max width, max height, image width, image height
    // nw = new width, nh = new height

    if ($fw) {
      $nw = $fw;
      $nh = ($nw / $w) * $h;
    }
    elseif ($fh) {
      $nh = $fh;
      $nw = ($nh / $h) * $w;
    }
    elseif ($w < $h) {     // image is portrait
      $nh = $mh;
      $nw = ($nh / $h) * $w;
      if ($nw > $mw) {
        $nw = $mw;
        $nh = ($nw / $w) * $h;
      }
    }
    elseif ($w > $h) {     // image is landscape
      $nw = $mw;
      $nh = ($nw / $w) * $h;
      if ($nh > $mh) {
        $nh = $mh;
        $nw = ($nh / $h) * $w;
      }
    }
    else {                 // image is square
      $nw = $mh;
      $nh = $mh;
    }
    return(array($nw, $nh));

  }

  function resizer($fileName, $maxWidth, $maxHeight, $fixedWidth, $fixedHeight, $oldDir, $newDir, $quality) {

    $file = $oldDir.'/'.$fileName;
    $fileDest = $newDir.'/'.strtolower($fileName);   // save with lowercase file name
    list($width, $height) = getimagesize($file);

    list($newWidth, $newHeight) = calcSize($fixedWidth, $fixedHeight, $maxWidth, $maxHeight, $width, $height);

    $extn = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    // it's a jpeg
    if ($extn === 'jpg' || $extn === 'jpeg') {
      $imageSrc  = imagecreatefromjpeg($file);
      // rotate image if necessary
      $exif = @exif_read_data($file);
      if (isset($exif['Orientation'])) {
        switch ($exif['Orientation']) {
          case 3:
            $imageSrc = imagerotate($imageSrc, 180, 0);
            break;
          case 6:
            $imageSrc = imagerotate($imageSrc, -90, 0);
            list($height, $width) = array($width, $height);  // swap width and height
            list($newWidth, $newHeight) = calcSize($fixedWidth, $fixedHeight, $maxWidth, $maxHeight, $width, $height);  // recalculate the size
            break;
          case 8:
            $imageSrc = imagerotate($imageSrc, 90, 0);
            list($height, $width) = array($width, $height);  // swap width and height
            list($newWidth, $newHeight) = calcSize($fixedWidth, $fixedHeight, $maxWidth, $maxHeight, $width, $height);  // recalculate the size
            break;
        }
      }
      $imageDest = imagecreatetruecolor($newWidth, $newHeight);
      if (imagecopyresampled($imageDest, $imageSrc, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
        imagejpeg($imageDest, $fileDest, $quality);
        imagedestroy($imageSrc);
        imagedestroy($imageDest);
        return true;
      }
      return false;
    }

    // it's a png
    if ($extn === 'png') {
      $imageSrc  = imagecreatefrompng($file);
      $imageDest = imagecreatetruecolor($newWidth, $newHeight);
      imagealphablending($imageDest, false);
      imagesavealpha($imageDest, true);
      if (imagecopyresampled($imageDest, $imageSrc, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
        imagepng($imageDest, $fileDest, ($quality / 10) - 1);
        imagedestroy($imageSrc);
        imagedestroy($imageDest);
        return true;
      }
      return false;
    }

  }


  // set as needed depending on the number and size of photos (60 should usually suffice)
  set_time_limit(600);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') :
    $maxWidth    = (int)$_POST['maxWidth'];
    $maxHeight   = (int)$_POST['maxHeight'];
    $fixedWidth  = (int)$_POST['fixedWidth'];
    $fixedHeight = (int)$_POST['fixedHeight'];
    $oldDir      = htmlspecialchars($_POST['oldDir']);
    $newDir      = htmlspecialchars($_POST['newDir']);
    $quality     = (int)$_POST['quality'];

    // create destination directory if it doesn't exist
    if (!file_exists($newDir))
      mkdir($newDir);
    // check source directory exists
    if (!file_exists($oldDir))
      die('Source directory does not exist.');
    // get all files
    $files = scandir($oldDir);
    if (count($files) <= 1)
      die('Source directory is empty.');

    echo '<p>Settings: fixed width ', $fixedWidth, ', fixed height ', $fixedHeight, ', max width ', $maxWidth, ', max height ', $maxHeight, ', quality ', $quality, '%</p>', "\n";
    echo '<ul>', "\n";
    // process each file
    foreach ($files as $file) {
      if ($file !== '.' && $file !== '..') {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === 'jpg' || $ext === 'jpeg' || $ext === 'png') {
          if (resizer($file, $maxWidth, $maxHeight, $fixedWidth, $fixedHeight, $oldDir, $newDir, $quality)) {
            echo '<li>Resized image: ', $file, '</li>', "\n";
          } else {
            echo '<li>** Failed to resize image: ', $file, ' **</li>', "\n";
          }
        } else {
          echo '<li>** ', $file, ' is not a jpeg or png **</li>', "\n";
        }
      }
    }
    echo '</ul>', "\n";
    echo '<p>*** Finished ***</p>', "\n";
    echo '<p><a href="', htmlspecialchars($_SERVER['PHP_SELF']), '">Resize more</a></p>', "\n";

  else :
  ?>


  <div class="login-root">
    <div class="box-root flex-flex flex-direction--column" style="min-height: 100vh;flex-grow: 1;">
      <div class="loginbackground box-background--white padding-top--64">
        <div class="loginbackground-gridContainer">
          <div class="box-root flex-flex" style="grid-area: top / start / 8 / end;">
            <div class="box-root" style="background-image: linear-gradient(white 0%, rgb(247, 250, 252) 33%); flex-grow: 1;">
            </div>
          </div>
          <div class="box-root flex-flex" style="grid-area: 4 / 2 / auto / 5;">
            <div class="box-root box-divider--light-all-2 animationLeftRight tans3s" style="flex-grow: 1;"></div>
          </div>
          <div class="box-root flex-flex" style="grid-area: 6 / start / auto / 2;">
            <div class="box-root box-background--blue800" style="flex-grow: 1;"></div>
          </div>
          <div class="box-root flex-flex" style="grid-area: 8 / 4 / auto / 6;">
            <div class="box-root box-background--gray100 animationLeftRight tans3s" style="flex-grow: 1;"></div>
          </div>
          <div class="box-root flex-flex" style="grid-area: 2 / 15 / auto / end;">
            <div class="box-root box-background--cyan200 animationRightLeft tans4s" style="flex-grow: 1;"></div>
          </div>
          <div class="box-root flex-flex" style="grid-area: 4 / 17 / auto / 20;">
            <div class="box-root box-background--gray100 animationRightLeft tans4s" style="flex-grow: 1;"></div>
          </div>
          <div class="box-root flex-flex" style="grid-area: 5 / 14 / auto / 17;">
            <div class="box-root box-divider--light-all-2 animationRightLeft tans3s" style="flex-grow: 1;"></div>
          </div>
        </div>
      </div>
      <div class="box-root padding-top--24 flex-flex flex-direction--column" style="flex-grow: 1; z-index: 9;">
        <div class="box-root padding-top--48 padding-bottom--24 flex-flex flex-justifyContent--center">
          <h1><a href="https://widyagama.ac.id/" rel="dofollow">Informatics Engineering RnD Bulk Image Resizer Tools</a></h1>
        </div>
        <div class="formbg-outer">
          <div class="formbg">
            <div class="formbg-inner padding-horizontal--48">
              <form id="form1" name="form1" method="post">
                <div class="field padding-bottom--24">
                  <div class="grid--50-50">
                    <div class="field padding-bottom--24">
                      <label for="maxWidth">Max width</label>
                      <input type="number" name="maxWidth" id="maxWidth" value="600">
                    </div>
                    <div class="field padding-bottom--24">
                      <label for="maxHeight">Max height</label>
                      <input type="number" name="maxHeight" id="maxHeight" value="600">
                    </div>
                  </div>
                </div>
                <div class="field padding-bottom--24">
                  <div class="grid--50-50">
                    <div class="field padding-bottom--24">
                      <label for="fixedWidth">Fixed width</label>
                      <input type="number" name="fixedWidth" id="fixedWidth" value="0">
                    </div>
                    <div class="field padding-bottom--24">
                      <label for="fixedHeight">Fixed height</label>
                      <input type="number" name="fixedHeight" id="fixedHeight" value="0">
                    </div>
                  </div>
                </div>
                <div class="field padding-bottom--24">
                  <label for="oldDir">Folder File Original</label>
                  <input type="text" name="oldDir" id="oldDir" placeholder="C:\folder\folder_berisi_gambar">
                </div>
                <div class="field padding-bottom--24">
                  <label for="newDir">Folder Hasil Export</label>
                  <input type="text" name="newDir" id="newDir" placeholder="C:\folder\folder_hasil_resize">
                </div>
                <div class="field padding-bottom--24">
                  <label for="quality">Quality %</label>
                  <input type="number" name="quality" id="quality" min="10" max="100" value="80">
                </div>
                <div class="field padding-bottom--24">
                  <input type="submit" name="submit" value="Resize">
                </div>
              </form>
            </div>
          </div>
          <div class="footer-link padding-top--24">
            <div class="listing padding-top--24 padding-bottom--24 flex-flex center-center">
              <span><a href="#">© Informatics Engineering of Widyagama University</a></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php
  endif;
  ?>
</body>

</html>
