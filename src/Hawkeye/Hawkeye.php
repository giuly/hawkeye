<?php namespace Viraj\Hawkeye;

use Intervention\Image\ImageManagerStatic as Image;
use Viraj\Hawkeye\Exceptions\InvalidFileException;

class Hawkeye
{
    use HawkeyeTrait;

    private $files;
    private $uploadedFiles;
    private $processedFiles;

    public function request($filename)
    {
        if ($filename != '') {
            return new UploadedFile($filename, new FileRepository());
        }

        throw new InvalidFileException("File is corrupted or Invalid");
    }

    /**
     * @param $filename
     * @return $this
     * @throws \Viraj\Hawkeye\Exceptions\InvalidUploadedFileException
     */
    public function upload($filename)
    {
        $fileObjects = $this->normalize($_FILES[$filename])
                            ->createFileObjects();

        foreach ($fileObjects as $fileObject) {
            $upload = new Upload($fileObject['uploaded'], $fileObject['original'], new FileRepository());

            $this->uploadedFiles[]['original'] = $upload->upload();
        }

        return $this;
    }

    /**
     * Returns array of file objects.
     *
     * File objects are created using SplFileInfo Class.
     *
     * @return array
     */
    private function createFileObjects()
    {
        foreach ($this->files as $file) {
            $fileObjects[] = [
                'original' => new \SplFileInfo($file['name']),
                'uploaded' => new \SplFileInfo($file['tmp_name']),
            ];
        }

        return $fileObjects;
    }

    private function normalize($argument)
    {
        if (is_array($argument['name'])) {
            foreach ($argument as $file_parameter => $value_array) {
                foreach ($value_array as $key => $value) {
                    $this->files[$key][$file_parameter] = $value;
                }
            }
        } else {
            foreach ($argument as $key => $value) {
                $this->files[0][$key] = $value;
            }
        }

        return $this;
    }

    public function scaleImages()
    {
        $count = 0;
        foreach ($this->uploadedFiles as $file) {
            $file_meta = explode('.', $file['original']);
            $hash = $file_meta[0];
            $directoryPath = $this->generateDirectoryPathFromName($hash);
            $file_name = $directoryPath . '/' . $file['original'];

            foreach (config('hawkeye.images') as $key => $value) {
                $dimensions = explode('x', $value);

                $scaled_image = $directoryPath . '/' . $hash . '_' . $dimensions[0] . '_' . $dimensions[1] . '.' . $file_meta[1];

                Image::make($file_name)
                     ->resize($dimensions[0], $dimensions[1])
                     ->save($scaled_image);

                $this->uploadedFiles[$count][$key] = $scaled_image;
            }
            $count++;
        }

        return $this;
    }

    public function get()
    {
        return $this->uploadedFiles;
    }
}
