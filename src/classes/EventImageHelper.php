<?php


namespace OracularApp;


class EventImageHelper
{
    private $image;

    public function __construct(array $image)
    {
        $this->image = $image;
    }


    public function getImageBlob()
    {
        $imageData = file_get_contents($this->image['tmp_name']);
        return $imageData;
    }
}