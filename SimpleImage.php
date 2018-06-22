<?php 

class SimpleImage
{
    /**
     * @var resource
     */
    protected $image;

    /**
     * @var int
     */
    protected $type;

    /**
     * @var int
     */
    protected $size;

    /**
     * @param string $filename
     *
     * @return bool
     */
    public function load($filename)
    {
        $info = getimagesize($filename);

        $this->size = filesize($filename);
        $this->type = $info[2];

        switch ($this->type) {
            case IMAGETYPE_JPEG:
                $this->image = @imagecreatefromjpeg($filename);
                imagealphablending($this->image, true);
                break;
            case IMAGETYPE_GIF:
                $this->image = @imagecreatefromgif($filename);
                break;
            case IMAGETYPE_PNG:
                $this->image = @imagecreatefrompng($filename);
                break;
            default:
                return false;
        }

        return $this->image !== null;
    }

    /**
     * @return string
     */
    public function getRealExtension()
    {
        switch ($this->type) {
            case IMAGETYPE_JPEG:
                return 'jpg';
            case IMAGETYPE_GIF:
                return 'gif';
            case IMAGETYPE_PNG:
                return 'png';
            default:
                return 'jpg';
        }
    }

    /**
     * @param string $filename
     * @param int $imageType
     * @param int $compression
     * @param int $permissions
     */
    public function save($filename, $imageType = null, $compression = 100, $permissions = 0775)
    {
        if (null === $imageType) {
            $imageType = $this->type;
        }

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                imagejpeg($this->image, $filename, $compression);
                break;
            case IMAGETYPE_GIF:
                imagegif($this->image, $filename);
                break;
            case IMAGETYPE_PNG:
                imagepng($this->image, $filename);
                break;
            default:
                imagejpeg($this->image, $filename, $compression);
        }

        if ($permissions !== null) {
            @chmod($filename, $permissions);
        }

        $this->size = filesize($filename);
    }

    /**
     * @param int $imageType
     */
    public function output($imageType = null)
    {
        if ($imageType === null) {
            $imageType = $this->type;
        }

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                header('Content-Type: image/jpeg');
                imagejpeg($this->image, NULL, 100);
                break;
            case IMAGETYPE_GIF:
                header('Content-Type: image/gif');
                imagegif($this->image);
                break;
            case IMAGETYPE_PNG:
                header('Content-Type: image/png');
                imagepng($this->image);
                break;
            default:
                header('Content-Type: image/jpeg');
                imagejpeg($this->image, NULL, 100);
        }
    }

    /**
     * @return int
     */
    public function getFileSize()
    {
        return $this->size;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return imagesx($this->image);
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return imagesy($this->image);
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return $this
     */
    public function resize($width, $height)
    {
        $width = (int) $width;
        $height = (int) $height;
        $newImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->image = $newImage;

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param int $top
     * @param int $left
     *
     * @return $this
     */
    public function crop($width, $height, $top, $left)
    {
        $new = imagecreatetruecolor($width, $height);
        imagecopy($new, $this->image, 0, 0, (int) $left, (int) $top, (int) $width, (int) $height);
        $this->image = $new;

        return $this;
    }

    /**
     * @param int $scale
     *
     * @return $this
     */
    public function scale($scale)
    {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getheight() * $scale / 100;

        return $this->resize($width, $height);
    }

    /**
     * @param int $height
     *
     * @return $this
     */
    public function resizeToHeight($height)
    {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;

        return $this->resize($width, $height);
    }

    /**
     * @param int $width
     *
     * @return $this
     */
    function resizeToWidth($width)
    {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;

        return $this->resize($width, $height);
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return $this
     */
    public function resizeTo($width, $height) {
        $ratioW = $width / $this->getWidth();
        $ratioH = $height / $this->getHeight();

        if ($ratioW >= $ratioH) {
            return $this->resizeToWidth($width)
                ->crop($width, $height, floor(($this->getHeight() - $height) / 2), 0);
        }

        return $this->resizeToHeight($height)
            ->crop($width, $height, 0, floor(($this->getWidth() - $width) / 2));
    }

    /**
     * @param string $hex
     *
     * @return int[]
     */
    public function hex2rgb($hex)
    {
        $hex = str_replace("#", "", $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return [$r, $g, $b];
    }

    /**
     * @param resource $destinationImage
     * @param resource $sourceImage
     * @param int $destinationX
     * @param int $destinationY
     * @param int $sourceX
     * @param int $sourceY
     * @param int $sourceWidth
     * @param int $sourceHeight
     * @param int $opacity
     *
     * @return bool
     */
    protected function imagecopymergeAlpha($destinationImage, $sourceImage, $destinationX, $destinationY, $sourceX, $sourceY, $sourceWidth, $sourceHeight, $opacity)
    {
        $opacity /= 100;
        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);
        imagealphablending($sourceImage, false);

        //Find the most opaque pixel in the image (the one with the smallest alpha value)
        $minimumAlpha = 127;
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $alpha = (imagecolorat($sourceImage, $x, $y) >> 24) & 0xFF;
                if ($alpha < $minimumAlpha) {
                    $minimumAlpha = $alpha;
                }
            }
        }

        //Loop through image pixels and modify alpha for each
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $colorxy = imagecolorat($sourceImage, $x, $y);
                $alpha = ($colorxy >> 24) & 0xFF;

                if ($minimumAlpha !== 127) {
                    $alpha = 127 + 127 * $opacity * ($alpha - 127) / (127 - $minimumAlpha);
                } else {
                    $alpha += 127 * $opacity;
                }

                $alphacolorxy = imagecolorallocatealpha($sourceImage, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);

                if (!imagesetpixel($sourceImage, $x, $y, $alphacolorxy)) {
                    return false;
                }
            }
        }

        imagecopy($destinationImage, $sourceImage, $destinationX, $destinationY, $sourceX, $sourceY, $sourceWidth, $sourceHeight);

        return true;
    }
}