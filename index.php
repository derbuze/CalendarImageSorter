<?php
include 'ImageSorter.php';

$calendarImageSorter = new ImageSorter();
$calendarImageSorter->reset();
$calendarImageSorter->sortImages();
$calendarImageSorter->writeCsv(
    $calendarImageSorter->getSucceededFiles(),
    $calendarImageSorter::SUCCEEDED_FILES_CSV_FILENAME
);
$calendarImageSorter->writeCsv(
    $calendarImageSorter->getFailedFiles(),
    $calendarImageSorter::FAILED_FILES_CSV_FILENAME
);
