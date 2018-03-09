<?php

namespace ImageSorter;

class ImageSorter
{
    const TARGET_FOLDER = 'target_folders';
    const SOURCE_IMAGE_FOLDER = 'source_images';
    const SUCCEEDED_FILES_CSV_FILENAME = 'succeededFiles.csv';
    const FAILED_FILES_CSV_FILENAME = 'failedFiles.csv';

    /** @var array $failedFiles */
    private $failedFiles = array();

    /** @var array $succeededFiled*/
    private $succeededFiles = array();

    /**
     * @return array
     */
    public function getFailedFiles(): array
    {
        return $this->failedFiles;
    }

    /**
     * @param array $failedFiles
     */
    public function setFailedFiles(array $failedFiles): void
    {
        $this->failedFiles = $failedFiles;
    }

    /**
     * @return array
     */
    public function getSucceededFiles(): array
    {
        return $this->succeededFiles;
    }

    /**
     * @param array $succeededFiles
     */
    public function setSucceededFiles(array $succeededFiles): void
    {
        $this->succeededFiles = $succeededFiles;
    }

    /**
     * Function scans folders and sorts images by original creation date
     */
    public function sortImages()
    {
        $directoryIterator = new DirectoryIterator(self::SOURCE_IMAGE_FOLDER);

        $this->createFoldersForMonths();

        foreach ($directoryIterator as $fileInfo) {
            if (!$fileInfo->isDot()) {
                // TODO write log message
                // echo 'Working on file... ' . $fileInfo->getFilename();

                $month = null;
                $sourcePath = self::SOURCE_IMAGE_FOLDER.'/'.$fileInfo->getFilename();
                $exifData = @exif_read_data($sourcePath, 'FILE', true);
                $month = $this->determineMonth($exifData);

                if ($month != null) {
                    $this->succeededFiles[] = array($fileInfo->getFilename(), $month);
                    $this->copyFile($sourcePath, $month, $fileInfo);
                    // TODO write log info

                } else {
                    $this->failedFiles[] = array($fileInfo->getFilename(), 'undefined');
                    $this->copyFile($sourcePath, 'undefined', $fileInfo);
                    // TODO write log info
                }
            }
        }
    }

    /**
     * Create target folder for each month
     */
    private function createFoldersForMonths()
    {
        if (!is_dir(self::TARGET_FOLDER)) {
            mkdir(self::TARGET_FOLDER);
        }

        // define array containing folder names
        $months = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');

        foreach ($months as $month) {
            if (!is_dir(self::TARGET_FOLDER.'/'.$month)) {
                mkdir(self::TARGET_FOLDER.'/'.$month);
            }
        }
        // TODO Add log file info
    }

    /**
     * Create CSV file for succeeded and failed files
     *
     * @param array $files
     * @param string $csvFileName
     */
    public function writeCsv(array $files, string $csvFileName)
    {
        if (count($files) > 0) {

            $fp = fopen($csvFileName, 'w');
            fputcsv($fp, array('filename', 'target folder'), ';', '"');
            foreach ($files as $fields) {
                fputcsv($fp, $fields, ';', '"');
            }
            fclose($fp);
        }
        // TODO Add log file info
    }

    /**
     * Remove existing target folder
     */
    public function reset()
    {
        $this->removeDirectory(self::TARGET_FOLDER);
        if (is_file(self::SUCCEEDED_FILES_CSV_FILENAME)) {
            unlink(self::SUCCEEDED_FILES_CSV_FILENAME);
        }
        if (is_file(self::FAILED_FILES_CSV_FILENAME)) {
            unlink(self::FAILED_FILES_CSV_FILENAME);
        }
    }

    /**
     * Function removes given folder and all contained sub-folders and files
     *
     * @param $path
     */
    private function removeDirectory($path)
    {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        if (is_dir($path)) {
            rmdir($path);
        }
        // TODO Add log file info
    }

    /**
     * Function determines month by given EXIF data
     *
     * @param $exifData
     * @return string|null
     */
    private function determineMonth($exifData)
    {
        if (array_key_exists('EXIF', $exifData) &&
            array_key_exists('DateTimeOriginal', $exifData['EXIF'])) {
            $dateInfo = ($exifData['EXIF']['DateTimeOriginal']);

            $date = explode(' ', $dateInfo);
            $date = str_replace(':', '-', $date[0]); // date format in EXIF is like 2018:01:31
            $date = new DateTime($date);
            return $date->format('m');

        } else {
            return null;
        }
    }

    /**
     * @param string $sourcePath
     * @param string $targetFolder
     * @param DirectoryIterator $fileInfo
     */
    private function copyFile(string $sourcePath, string $targetFolder, DirectoryIterator $fileInfo)
    {
        $destinationPath = self::TARGET_FOLDER . '/' . $targetFolder . '/' . $fileInfo->getFilename() ;

        if (!is_dir(self::TARGET_FOLDER.'/'.$targetFolder)) {
            mkdir(self::TARGET_FOLDER.'/'.$targetFolder);
        }

        // copy file to target folder
        try {
            copy($sourcePath, $destinationPath);
        } catch (Exception $e) {
            // TODO log exception
        }

    }

}