<?php
namespace Uos\Opencast\Helpers;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\AbstractOnlineMediaHelper;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OpencastHelper extends AbstractOnlineMediaHelper
{
    protected $extension = 'opencast';

    private const PATH_PATTERNS = [
        'paella\/ui\/watch\.html\?id=([0-9a-f\-]+)',
        'play\/([0-9a-f\-]+)'
    ];

    /**
     * Try to transform given URL to a File
     *
     * @param string $url
     * @param Folder $targetFolder
     * @return File|null
     */
    public function transformUrlToFile($url, Folder $targetFolder)
    {
        if ($host = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('opencast', 'host')) {
            // Prepare host for regex
            $hostPattern = str_replace(['/', '.'], ['\/', '\.'], $host);

            foreach (self::PATH_PATTERNS as $pathPattern) {
                if (preg_match('/^' . $hostPattern . $pathPattern . '$/i', $url, $match)) {
                    $url     = $match[0];
                    $mediaId = $match[1];

                    $file = $this->findExistingFileByOnlineMediaId($mediaId, $targetFolder, $this->extension);

                    // no existing file create new
                    if ($file === null) {
                        $fileName = $mediaId . '.' . $this->extension;
                        $file = $this->createNewFile($targetFolder, $fileName, $mediaId);
                    }

                    return $file;
                }
            }
        } else {
            DebugUtility::debug(
                'Please make sure the \'host\' is defined within the extension configuration of EXT:opencast',
            );
            die();
        }
    }

    /**
     * Get public url
     *
     * Return NULL if you want to use core default behaviour
     *
     * @param File $file
     * @param bool $relativeToCurrentScript
     * @return string|null
     */
    public function getPublicUrl(File $file, $relativeToCurrentScript = false)
    {
        return null;
    }

    /**
     * Get local absolute file path to preview image
     *
     * Return an empty string when no preview image is available
     *
     * @param File $file
     * @return string
     */
    public function getPreviewImage(File $file)
    {
        return '';
    }

    /**
     * Get meta data for OnlineMedia item
     *
     * See $GLOBALS[TCA][sys_file_metadata][columns] for possible fields to fill/use
     *
     * @param File $file
     * @return array with metadata
     */
    public function getMetaData(File $file)
    {
        $metadata = [];

        $metadata['title'] = $file->getProperty('title');

        return $metadata;
    }
}
