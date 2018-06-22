# simple-image

Usage:

```
$image = new SimpleImage();

if (!$image->load('my-image.jpg)) {
    return;
}

$image->resizeTo(150, 150);
$image->save('my-image-thumb.jpg');
```